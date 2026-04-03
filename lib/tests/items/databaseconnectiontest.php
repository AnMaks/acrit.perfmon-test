<?php

namespace Acrit\Perfmon\Tests\Items;

use Acrit\Perfmon\Tests\AbstractTest;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);


class Databaseconnectiontest extends AbstractTest
{
    public function getCode(): string
    {
        return 'DATABASE_CONNECTION';
    }

    public function getTitle(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_DATABASE_CONNECTION_TITLE');
    }

    public function getGroup(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_GROUP_DATABASE');
    }

    public function getSort(): int
    {
        return 10;
    }

    public function getDescription(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_DATABASE_CONNECTION_DESCRIPTION');
    }

    public function getRecommendation(): string
    {
        return (string)Loc::getMessage('ACRIT_PERFMON_TEST_DATABASE_CONNECTION_RECOMMENDATION');
    }


    public function run(): \Acrit\Perfmon\Tests\TestResult
    {
        try {
            $connection = \Bitrix\Main\Application::getConnection();
            $versionRow = $connection->query('SELECT VERSION() AS VERSION')->fetch();
            $charsetRow = $connection->query('SELECT @@character_set_server AS CHARSET_NAME, @@collation_server AS COLLATION_NAME')->fetch();
            $charset = (string)($charsetRow['CHARSET_NAME'] ?? '');
            $collation = (string)($charsetRow['COLLATION_NAME'] ?? '');
            $version = (string)($versionRow['VERSION'] ?? '');
            $success = in_array($charset, ['utf8', 'utf8mb4'], true);

            return $this->createResult(
                $success,
                (string)Loc::getMessage($success ? 'ACRIT_PERFMON_STATUS_OK' : 'ACRIT_PERFMON_STATUS_FAIL'),
                (string)Loc::getMessage('ACRIT_PERFMON_TEST_DATABASE_CONNECTION_MESSAGE', [
                    '#VERSION#' => $version,
                    '#CHARSET#' => $charset,
                    '#COLLATION#' => $collation,
                ]),
                [
                    'VERSION' => $version,
                    'CHARSET' => $charset,
                    'COLLATION' => $collation,
                ]
            );
        } catch (\Throwable $exception) {
            return $this->createResult(
                false,
                (string)Loc::getMessage('ACRIT_PERFMON_STATUS_FAIL'),
                (string)Loc::getMessage('ACRIT_PERFMON_TEST_DATABASE_CONNECTION_FAIL', ['#MESSAGE#' => $exception->getMessage()])
            );
        }
    }

}
