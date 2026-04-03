<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Bitrixcacheenabledtest extends AbstractTest
{
    public function getCode(): string
    {
        return 'BITRIX_CACHE';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_BITRIX_CACHE_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_BITRIX');
    }

    public function getSort(): int
    {
        return 10;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_BITRIX_CACHE_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_BITRIX_CACHE_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $enabled = \Bitrix\Main\Config\Option::get('main', 'component_cache_on', 'Y') === 'Y';
        return $this->createResult(
            $enabled,
            (string)Loc::getMessage($enabled ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            (string)Loc::getMessage($enabled ? 'ACRIT_PERFMON_TEST_BITRIX_CACHE_OK' : 'ACRIT_PERFMON_TEST_BITRIX_CACHE_FAIL')
        );
    }

}
