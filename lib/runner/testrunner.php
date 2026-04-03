<?php

namespace Acrit\Perfmon\Runner;

use Acrit\Perfmon\Main;
use Acrit\Perfmon\Tests\Registry;
use Acrit\Perfmon\Tests\TestResult;

/**
 * Запускает все тесты.
 */
class TestRunner
{
    /**
     * @return TestResult[]
     */
    public function runAll(): array
    {
        $results = [];
        foreach (Registry::getTests() as $test) {
            try {
                $results[] = $test->run();
            } catch (\Throwable $exception) {
                $results[] = new TestResult([
                    'CODE' => $test->getCode(),
                    'TITLE' => $test->getTitle(),
                    'GROUP' => $test->getGroup(),
                    'SUCCESS' => false,
                    'STATUS_TEXT' => Main::getMessage('ACRIT_PERFMON_STATUS_FAIL'),
                    'MESSAGE' => Main::getMessage('ACRIT_PERFMON_TEST_EXCEPTION', ['#MESSAGE#' => $exception->getMessage()]),
                    'DESCRIPTION' => $test->getDescription(),
                    'RECOMMENDATION' => $test->getRecommendation(),
                    'META' => [],
                    'SORT' => $test->getSort(),
                ]);
            }
        }

        usort($results, static function (TestResult $left, TestResult $right): int {
            $a = $left->toArray();
            $b = $right->toArray();
            if ($a['GROUP'] === $b['GROUP']) {
                if ((int)$a['SORT'] === (int)$b['SORT']) {
                    return strcmp((string)$a['TITLE'], (string)$b['TITLE']);
                }
                return (int)$a['SORT'] <=> (int)$b['SORT'];
            }
            return strcmp((string)$a['GROUP'], (string)$b['GROUP']);
        });

        return $results;
    }

    public function getSummary(array $results): array
    {
        return Main::getSummary($results);
    }
}
