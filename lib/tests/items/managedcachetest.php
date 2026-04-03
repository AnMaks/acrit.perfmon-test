<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Managedcachetest extends AbstractTest
{
    public function getCode(): string
    {
        return 'MANAGED_CACHE';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_MANAGED_CACHE_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_CACHING');
    }

    public function getSort(): int
    {
        return 20;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_MANAGED_CACHE_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_MANAGED_CACHE_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        $managedCacheDefined = defined('BX_COMP_MANAGED_CACHE');
        $enabled = $managedCacheDefined && BX_COMP_MANAGED_CACHE === true;

        return $this->createResult(
            $enabled,
            (string)Loc::getMessage($enabled ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
            (string)Loc::getMessage($enabled ? 'ACRIT_PERFMON_TEST_MANAGED_CACHE_OK' : 'ACRIT_PERFMON_TEST_MANAGED_CACHE_FAIL')
        );
    }

}
