<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Sessionhandlertest extends AbstractTest
{
    public function getCode(): string
    {
        return 'SESSION_HANDLER';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SESSION_HANDLER_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_ENVIRONMENT');
    }

    public function getSort(): int
    {
        return 30;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SESSION_HANDLER_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_SESSION_HANDLER_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $handler = (string)(ini_get('session.save_handler') ?: 'files');
        $knownHandlers = ['files', 'redis', 'memcache', 'memcached'];
        $success = in_array($handler, $knownHandlers, true);

        return $this->createResult(
            $success,
            (string)Loc::getMessage($success ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            (string)Loc::getMessage('ACRIT_PERFMON_TEST_SESSION_HANDLER_MESSAGE', ['#HANDLER#' => $handler]),
            ['HANDLER' => $handler]
        );
    }

}
