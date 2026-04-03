<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Serveruptimetest extends AbstractTest
{
    public function getCode(): string
    {
        return 'SERVER_UPTIME';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SERVER_UPTIME_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_ENVIRONMENT');
    }

    public function getSort(): int
    {
        return 20;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SERVER_UPTIME_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SERVER_UPTIME_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $uptimeSeconds = null;
        if (is_readable('/proc/uptime')) {
            $raw = trim((string)file_get_contents('/proc/uptime'));
            $parts = explode(' ', $raw);
            $uptimeSeconds = isset($parts[0]) ? (int)floor((float)$parts[0]) : null;
        }

        if ($uptimeSeconds === null) {
            return $this->createResult(
                false,
                (string)Loc::getMessage('ACRIT_PERFMON_STATUS_FAIL'),
                (string)Loc::getMessage('ACRIT_PERFMON_TEST_SERVER_UPTIME_UNKNOWN')
            );
        }

        $days = floor($uptimeSeconds / 86400);
        $hours = floor(($uptimeSeconds % 86400) / 3600);
        $text = $days . ' ' . Loc::getMessage('ACRIT_PERFMON_TEST_SERVER_UPTIME_DAYS') . ', ' . $hours . ' ' . Loc::getMessage('ACRIT_PERFMON_TEST_SERVER_UPTIME_HOURS');
        $success = $uptimeSeconds >= 86400;

        return $this->createResult(
            $success,
            (string)Loc::getMessage($success ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            (string)Loc::getMessage($success ? 'ACRIT_PERFMON_TEST_SERVER_UPTIME_OK' : 'ACRIT_PERFMON_TEST_SERVER_UPTIME_FAIL', ['#VALUE#' => $text]),
            ['UPTIME_SECONDS' => $uptimeSeconds]
        );
    }

}
