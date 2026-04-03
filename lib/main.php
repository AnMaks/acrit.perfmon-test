<?php

namespace Acrit\Perfmon;

use Acrit\Perfmon\Tables\RunHistoryTable;
use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Type\DateTime;

Loc::loadMessages(__FILE__);

class Main
{
    public const MODULE_ID = 'acrit.perfmon';
    public const SESSION_SUPPORT_REPORT = 'ACRIT_PERFMON_SUPPORT_REPORT';

    public static function getModuleId(): string
    {
        return self::MODULE_ID;
    }

    public static function moduleRoot(): string
    {
        return dirname(__DIR__);
    }

    public static function getMessage(string $code, array $replace = []): string
    {
        return (string)(Loc::getMessage($code, $replace) ?: $code);
    }

    public static function getOption(string $name, string $default = ''): string
    {
        return (string)Option::get(self::MODULE_ID, $name, $default);
    }

    public static function setOption(string $name, string $value): void
    {
        Option::set(self::MODULE_ID, $name, $value);
    }

    public static function isEnabled(): bool
    {
        return self::getOption(OptionHelper::OPTION_ENABLED, 'Y') === 'Y';
    }

    public static function isLogEnabled(): bool
    {
        return self::getOption(OptionHelper::OPTION_LOG_ENABLED, 'Y') === 'Y';
    }

    public static function getSupportEmail(): string
    {
        return self::getOption(OptionHelper::OPTION_SUPPORT_EMAIL, 'support@example.com');
    }

    public static function getHistoryLimit(): int
    {
        $value = (int)self::getOption(OptionHelper::OPTION_HISTORY_LIMIT, '20');
        return $value > 0 ? $value : 20;
    }

    public static function getHistoryRetentionDays(): int
    {
        $value = (int)self::getOption(OptionHelper::OPTION_HISTORY_TTL_DAYS, '30');
        return $value > 0 ? $value : 30;
    }

    public static function getSiteHost(): string
    {
        $request = Application::getInstance()->getContext()->getRequest();
        $host = (string)$request->getHttpHost();
        if ($host === '') {
            $host = (string)($_SERVER['SERVER_NAME'] ?? 'localhost');
        }
        return preg_replace('#:\d+$#', '', $host) ?: 'localhost';
    }

    public static function getSiteUrl(): string
    {
        $isHttps = (
            (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );
        return ($isHttps ? 'https://' : 'http://') . self::getSiteHost();
    }

    public static function getPhpVersion(): string
    {
        return PHP_VERSION;
    }

    public static function getBitrixVersion(): string
    {
        if (defined('SM_VERSION')) {
            $date = defined('SM_VERSION_DATE') ? ' (' . SM_VERSION_DATE . ')' : '';
            return SM_VERSION . $date;
        }
        return self::getMessage('ACRIT_PERFMON_BITRIX_VERSION_UNKNOWN');
    }

    public static function saveSupportReportToSession(string $report): void
    {
        $_SESSION[self::SESSION_SUPPORT_REPORT] = $report;
    }

    public static function getSupportReportFromSession(): string
    {
        return (string)($_SESSION[self::SESSION_SUPPORT_REPORT] ?? '');
    }

    public static function clearSupportReportFromSession(): void
    {
        unset($_SESSION[self::SESSION_SUPPORT_REPORT]);
    }

    public static function getSummary(array $results): array
    {
        $summary = [
            'TOTAL' => count($results),
            'SUCCESS' => 0,
            'FAIL' => 0,
        ];

        foreach ($results as $result) {
            $data = is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : (array)$result;
            if (!empty($data['SUCCESS'])) {
                $summary['SUCCESS']++;
            } else {
                $summary['FAIL']++;
            }
        }

        return $summary;
    }

    public static function saveRunHistory(array $results, string $report): void
    {
        if (!self::isLogEnabled() || !class_exists(RunHistoryTable::class)) {
            return;
        }

        try {
            global $USER;
            $summary = self::getSummary($results);
            $summaryText = self::getMessage(
                'ACRIT_PERFMON_HISTORY_SUMMARY',
                [
                    '#SUCCESS#' => $summary['SUCCESS'],
                    '#FAIL#' => $summary['FAIL'],
                    '#TOTAL#' => $summary['TOTAL'],
                ]
            );

            RunHistoryTable::add([
                'CREATED_AT' => new DateTime(),
                'USER_ID' => is_object($USER) ? (int)$USER->GetID() : null,
                'SUMMARY' => $summaryText,
                'REPORT_TEXT' => $report,
                'TOTAL_COUNT' => $summary['TOTAL'],
                'SUCCESS_COUNT' => $summary['SUCCESS'],
                'FAIL_COUNT' => $summary['FAIL'],
            ]);

            self::trimHistory();
        } catch (\Throwable $exception) {
        }
    }

    public static function trimHistory(): void
    {
        if (!class_exists(RunHistoryTable::class)) {
            return;
        }

        $limit = self::getHistoryLimit();
        $ttlDays = self::getHistoryRetentionDays();

        try {
            if ($ttlDays > 0) {
                $border = new DateTime(date('Y-m-d H:i:s', time() - ($ttlDays * 86400)), 'Y-m-d H:i:s');
                $expiredIds = [];
                $expiredResult = RunHistoryTable::getList([
                    'select' => ['ID'],
                    'filter' => ['<CREATED_AT' => $border],
                ]);
                while ($row = $expiredResult->fetch()) {
                    $expiredIds[] = (int)$row['ID'];
                }
                foreach ($expiredIds as $id) {
                    RunHistoryTable::delete($id);
                }
            }

            if ($limit > 0) {
                $ids = [];
                $result = RunHistoryTable::getList([
                    'select' => ['ID'],
                    'order' => ['ID' => 'DESC'],
                    'offset' => $limit,
                ]);
                while ($row = $result->fetch()) {
                    $ids[] = (int)$row['ID'];
                }
                foreach ($ids as $id) {
                    RunHistoryTable::delete($id);
                }
            }
        } catch (\Throwable $exception) {
        }
    }

    public static function getRecentHistory(int $limit = 10): array
    {
        if (!class_exists(RunHistoryTable::class)) {
            return [];
        }

        $limit = max(1, $limit);
        try {
            return RunHistoryTable::getList([
                'order' => ['ID' => 'DESC'],
                'limit' => $limit,
            ])->fetchAll();
        } catch (\Throwable $exception) {
            return [];
        }
    }
}
