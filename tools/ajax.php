<?php

define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
define('PUBLIC_AJAX_MODE', true);

use Acrit\Perfmon\Main;
use Acrit\Perfmon\Report\ReportBuilder;
use Acrit\Perfmon\Runner\TestRunner;
use Acrit\Perfmon\Tests\Registry;
use Bitrix\Main\Loader;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';

header('Content-Type: application/json; charset=UTF-8');

if (!Loader::includeModule('acrit.perfmon')) {
    echo json_encode(['success' => false, 'error' => 'Module not found'], JSON_UNESCAPED_UNICODE);
    die();
}

$action = (string)($_REQUEST['action'] ?? '');
if (!check_bitrix_sessid()) {
    echo json_encode(['success' => false, 'error' => 'Invalid session'], JSON_UNESCAPED_UNICODE);
    die();
}

function acrit_perfmon_group_order(): array
{
    return [
        'PHP' => 100,
        'Кеширование' => 200,
        'Битрикс' => 300,
        'База данных' => 400,
        'Окружение' => 500,
    ];
}

function acrit_perfmon_sort_test_rows(array &$rows): void
{
    $groupOrder = acrit_perfmon_group_order();
    usort($rows, static function (array $a, array $b) use ($groupOrder): int {
        $groupCompare = (($groupOrder[$a['GROUP']] ?? 9999) <=> ($groupOrder[$b['GROUP']] ?? 9999));
        if ($groupCompare !== 0) {
            return $groupCompare;
        }

        $sortCompare = ((int)($a['SORT'] ?? 0) <=> (int)($b['SORT'] ?? 0));
        if ($sortCompare !== 0) {
            return $sortCompare;
        }

        return strcasecmp((string)($a['TITLE'] ?? ''), (string)($b['TITLE'] ?? ''));
    });
}

switch ($action) {
    case 'get_tests_list':
        $tests = [];
        foreach (Registry::getTests() as $test) {
            $tests[] = [
                'CODE' => $test->getCode(),
                'TITLE' => $test->getTitle(),
                'GROUP' => $test->getGroup(),
                'SORT' => $test->getSort(),
                'DESCRIPTION' => $test->getDescription(),
                'RECOMMENDATION' => $test->getRecommendation(),
            ];
        }
        acrit_perfmon_sort_test_rows($tests);
        echo json_encode([
            'success' => true,
            'tests' => $tests,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'run_single_test':
        $requestedCode = (string)($_REQUEST['code'] ?? '');
        $matchedTest = null;
        foreach (Registry::getTests() as $test) {
            if ($test->getCode() === $requestedCode) {
                $matchedTest = $test;
                break;
            }
        }

        if ($matchedTest === null) {
            echo json_encode(['success' => false, 'error' => 'Test not found'], JSON_UNESCAPED_UNICODE);
            break;
        }

        try {
            $result = $matchedTest->run()->toArray();
        } catch (\Throwable $exception) {
            $result = [
                'CODE' => $matchedTest->getCode(),
                'TITLE' => $matchedTest->getTitle(),
                'GROUP' => $matchedTest->getGroup(),
                'SUCCESS' => false,
                'STATUS_TEXT' => Main::getMessage('ACRIT_PERFMON_STATUS_FAIL'),
                'MESSAGE' => Main::getMessage('ACRIT_PERFMON_TEST_EXCEPTION', ['#MESSAGE#' => $exception->getMessage()]),
                'DESCRIPTION' => $matchedTest->getDescription(),
                'RECOMMENDATION' => $matchedTest->getRecommendation(),
                'META' => [],
                'SORT' => $matchedTest->getSort(),
            ];
        }

        echo json_encode([
            'success' => true,
            'result' => $result,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'finalize_run':
        $results = $_POST['results'] ?? [];
        if (is_string($results)) {
            $decoded = json_decode($results, true);
            $results = is_array($decoded) ? $decoded : [];
        }
        if (!is_array($results)) {
            $results = [];
        }

        acrit_perfmon_sort_test_rows($results);
        $summary = Main::getSummary($results);
        $report = (new ReportBuilder())->build($results);
        Main::saveSupportReportToSession($report);
        Main::saveRunHistory($results, $report);

        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'report' => $report,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'run_tests':
        $runner = new TestRunner();
        $results = $runner->runAll();
        $summary = $runner->getSummary($results);
        $report = (new ReportBuilder())->build($results);
        Main::saveSupportReportToSession($report);
        Main::saveRunHistory($results, $report);
        echo json_encode([
            'success' => true,
            'summary' => $summary,
            'results' => array_map(static function ($result) {
                return $result->toArray();
            }, $results),
            'report' => $report,
        ], JSON_UNESCAPED_UNICODE);
        break;

    case 'clear_support_report':
        Main::clearSupportReportFromSession();
        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Unknown action'], JSON_UNESCAPED_UNICODE);
        break;
}

die();
