<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Phpbasictest extends AbstractTest
{
    public function getCode(): string
    {
        return 'PHP_BASIC';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_PHP_BASIC_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_PHP');
    }

    public function getSort(): int
    {
        return 10;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_PHP_BASIC_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_PHP_BASIC_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $requiredExtensions = ['json', 'mbstring', 'session', 'openssl'];
        $missing = [];
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }

        $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
        $success = $phpOk && empty($missing);

        $message = $success
            ? (string)Loc::getMessage('ACRIT_PERFMON_TEST_PHP_BASIC_OK', ['#VERSION#' => PHP_VERSION])
            : (string)Loc::getMessage(
                'ACRIT_PERFMON_TEST_PHP_BASIC_FAIL',
                [
                    '#VERSION#' => PHP_VERSION,
                    '#MISSING#' => empty($missing) ? '-' : implode(', ', $missing),
                ]
            );

        return $this->createResult(
            $success,
            (string)Loc::getMessage($success ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            $message,
            ['MISSING' => $missing]
        );
    }

}
