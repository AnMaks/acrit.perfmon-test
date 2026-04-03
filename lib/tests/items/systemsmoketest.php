<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Systemsmoketest extends AbstractTest
{
    public function getCode(): string
    {
        return 'SYSTEM_SMOKE';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SYSTEM_SMOKE_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_ENVIRONMENT');
    }

    public function getSort(): int
    {
        return 10;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SYSTEM_SMOKE_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SYSTEM_SMOKE_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $checks = [];
        $checks[] = extension_loaded('json');
        $checks[] = is_dir($_SERVER['DOCUMENT_ROOT'] . '/upload');
        $checks[] = is_writable($_SERVER['DOCUMENT_ROOT'] . '/upload');
        $checks[] = (bool)date_default_timezone_get();

        $success = !in_array(false, $checks, true);

        $message = $success
            ? (string)Loc::getMessage('ACRIT_PERFMON_TEST_SYSTEM_SMOKE_OK')
            : (string)Loc::getMessage('ACRIT_PERFMON_TEST_SYSTEM_SMOKE_FAIL');

        return $this->createResult(
            $success,
            (string)Loc::getMessage($success ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            $message,
            [
                'UPLOAD_DIR' => $_SERVER['DOCUMENT_ROOT'] . '/upload',
                'TIMEZONE' => date_default_timezone_get(),
            ]
        );
    }

}
