<?php

namespace Acrit\Perfmon\Report;

use Acrit\Perfmon\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

/**
 * Отчёт по результатам проверки.
 */
class ReportBuilder
{
    public function build(array $results): string
    {
        $summary = Main::getSummary($results);
        $lines = [];

        $lines[] = Loc::getMessage('ACRIT_PERFMON_REPORT_TITLE');
        $lines[] = str_repeat('=', 80);
        $lines[] = Loc::getMessage('ACRIT_PERFMON_REPORT_DATE') . ': ' . date('d.m.Y H:i:s');
        $lines[] = Loc::getMessage('ACRIT_PERFMON_REPORT_SITE') . ': ' . Main::getSiteUrl();
        $lines[] = Loc::getMessage('ACRIT_PERFMON_REPORT_PHP') . ': ' . Main::getPhpVersion();
        $lines[] = Loc::getMessage('ACRIT_PERFMON_REPORT_BITRIX') . ': ' . Main::getBitrixVersion();
        $lines[] = '';
        $lines[] = Loc::getMessage('ACRIT_PERFMON_REPORT_SUMMARY');
        $lines[] = '- ' . Loc::getMessage('ACRIT_PERFMON_REPORT_TOTAL') . ': ' . $summary['TOTAL'];
        $lines[] = '- ' . Loc::getMessage('ACRIT_PERFMON_REPORT_SUCCESS') . ': ' . $summary['SUCCESS'];
        $lines[] = '- ' . Loc::getMessage('ACRIT_PERFMON_REPORT_FAIL') . ': ' . $summary['FAIL'];
        $lines[] = '';

        $currentGroup = null;
        foreach ($results as $result) {
            $data = is_object($result) && method_exists($result, 'toArray') ? $result->toArray() : (array)$result;
            if ($currentGroup !== $data['GROUP']) {
                $currentGroup = $data['GROUP'];
                $lines[] = '';
                $lines[] = '[' . $currentGroup . ']';
            }
            $lines[] = '- ' . $data['TITLE'];
            $lines[] = '  ' . Loc::getMessage('ACRIT_PERFMON_REPORT_STATUS') . ': ' . $data['STATUS_TEXT'];
            $lines[] = '  ' . Loc::getMessage('ACRIT_PERFMON_REPORT_MESSAGE') . ': ' . trim((string)$data['MESSAGE']);
            $lines[] = '  ' . Loc::getMessage('ACRIT_PERFMON_REPORT_DESCRIPTION') . ': ' . trim((string)$data['DESCRIPTION']);
        }

        return trim(implode(PHP_EOL, $lines));
    }
}
