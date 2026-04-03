<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Opcacheenabledtest extends AbstractTest
{
    public function getCode(): string
    {
        return 'OPCACHE_ENABLED';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_OPCACHE_ENABLED_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_PHP');
    }

    public function getSort(): int
    {
        return 20;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_OPCACHE_ENABLED_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_OPCACHE_ENABLED_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $enabled = extension_loaded('Zend OPcache') && $this->iniBool('opcache.enable');
        return $this->createResult(
            $enabled,
            (string)Loc::getMessage($enabled ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            (string)Loc::getMessage($enabled ? 'ACRIT_PERFMON_TEST_OPCACHE_ENABLED_OK' : 'ACRIT_PERFMON_TEST_OPCACHE_ENABLED_FAIL')
        );
    }

}
