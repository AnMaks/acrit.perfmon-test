<?php

namespace Acrit\Perfmon\Tests;

use Acrit\Perfmon\Tests\Items\Bitrixcacheenabledtest;
use Acrit\Perfmon\Tests\Items\Compositemodetest;
use Acrit\Perfmon\Tests\Items\Databaseconnectiontest;
use Acrit\Perfmon\Tests\Items\Debugmodetest;
use Acrit\Perfmon\Tests\Items\Managedcachetest;
use Acrit\Perfmon\Tests\Items\Memorycachebackendtest;
use Acrit\Perfmon\Tests\Items\Opcacheenabledtest;
use Acrit\Perfmon\Tests\Items\Opcachesettingstest;
use Acrit\Perfmon\Tests\Items\Phpbasictest;
use Acrit\Perfmon\Tests\Items\Serveruptimetest;
use Acrit\Perfmon\Tests\Items\Sessionhandlertest;
use Acrit\Perfmon\Tests\Items\Systemsmoketest;

/**
 * Хранит список подключённых тестов
 */
class Registry
{
    public static function getTestClasses(): array
    {
        return [
            Systemsmoketest::class,
            Serveruptimetest::class,
            Phpbasictest::class,
            Opcacheenabledtest::class,
            Bitrixcacheenabledtest::class,
            Managedcachetest::class,
            Sessionhandlertest::class,
            Databaseconnectiontest::class,
        ];
    }

    public static function getTests(): array
    {
        $tests = [];
        foreach (self::getTestClasses() as $className) {
            if (class_exists($className)) {
                $tests[] = new $className();
            }
        }
        return $tests;
    }
}
