<?php
defined('B_PROLOG_INCLUDED') || die();

use Acrit\Perfmon\Main;
use Acrit\Perfmon\OptionHelper;
use Acrit\Perfmon\Tests\Registry;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

global $APPLICATION, $REQUEST_METHOD, $Update, $Apply, $RestoreDefaults;

Loc::loadMessages(__FILE__);
Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . BX_ROOT . '/modules/main/options.php');

$moduleId = 'acrit.perfmon';
Loader::includeModule($moduleId);
CJSCore::Init(['ajax', 'popup']);

$module_id = $moduleId;
$POST_RIGHT = $APPLICATION->GetGroupRight($moduleId);
if ($POST_RIGHT < 'R') {
    return;
}

if (!function_exists('acrit_perfmon_ini_to_bytes')) {
    function acrit_perfmon_ini_to_bytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }

        $unit = strtolower(substr($value, -1));
        $bytes = (float)$value;

        switch ($unit) {
            case 'g':
                $bytes *= 1024;
            case 'm':
                $bytes *= 1024;
            case 'k':
                $bytes *= 1024;
                break;
        }

        return (int)round($bytes);
    }
}

if (!function_exists('acrit_perfmon_format_bytes')) {
    function acrit_perfmon_format_bytes(int $bytes): string
    {
        if ($bytes < 0) {
            return (string)Loc::getMessage('ACRIT_PERFMON_MEMORY_UNLIMITED');
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float)$bytes;
        $unitIndex = 0;

        while ($value >= 1024 && $unitIndex < count($units) - 1) {
            $value /= 1024;
            $unitIndex++;
        }

        return round($value, $unitIndex === 0 ? 0 : 1) . ' ' . $units[$unitIndex];
    }
}

$aTabs = [
    [
        'DIV' => 'tests',
        'TAB' => Loc::getMessage('ACRIT_PERFMON_OPTIONS_TAB_TESTS'),
        'ICON' => 'settings',
        'TITLE' => Loc::getMessage('ACRIT_PERFMON_OPTIONS_TAB_TESTS_TITLE'),
    ],
    [
        'DIV' => 'rights',
        'TAB' => Loc::getMessage('ACRIT_PERFMON_OPTIONS_TAB_RIGHTS'),
        'ICON' => 'settings',
        'TITLE' => Loc::getMessage('ACRIT_PERFMON_OPTIONS_TAB_RIGHTS_TITLE'),
    ],
];
$tabControl = new CAdminTabControl('tabControl', $aTabs);

if (
    $REQUEST_METHOD === 'POST'
    && strlen((string)$Update . (string)$Apply . (string)$RestoreDefaults) > 0
    && $POST_RIGHT >= 'W'
    && check_bitrix_sessid()
) {
    if (isset($_POST['RestoreDefaults'])) {
        Option::delete($moduleId, [
            'name' => [
                OptionHelper::OPTION_LOG_ENABLED,
                OptionHelper::OPTION_HISTORY_LIMIT,
                OptionHelper::OPTION_HISTORY_TTL_DAYS,
            ],
        ]);
    } else {
        Option::set(
            $moduleId,
            OptionHelper::OPTION_LOG_ENABLED,
            (string)(($_POST[OptionHelper::OPTION_LOG_ENABLED] ?? 'N') === 'Y' ? 'Y' : 'N')
        );
        Option::set(
            $moduleId,
            OptionHelper::OPTION_HISTORY_LIMIT,
            (string)max(1, (int)($_POST[OptionHelper::OPTION_HISTORY_LIMIT] ?? 20))
        );
        Option::set(
            $moduleId,
            OptionHelper::OPTION_HISTORY_TTL_DAYS,
            (string)max(1, (int)($_POST[OptionHelper::OPTION_HISTORY_TTL_DAYS] ?? 30))
        );
    }

    ob_start();
    require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/admin/group_rights.php';
    ob_end_clean();

    $redirect = $APPLICATION->GetCurPage()
        . '?mid=' . urlencode($moduleId)
        . '&lang=' . urlencode(LANGUAGE_ID)
        . '&saved=Y&' . $tabControl->ActiveTabParam();

    if (strlen((string)($_REQUEST['back_url_settings'] ?? '')) > 0) {
        if (isset($_POST['Apply'])) {
            $redirect .= '&back_url_settings=' . urlencode((string)$_REQUEST['back_url_settings']);
            LocalRedirect($redirect);
        }

        LocalRedirect((string)$_REQUEST['back_url_settings']);
    }

    LocalRedirect($redirect);
}

$historyRows = Main::getRecentHistory(5);
$historyEnabled = Main::isLogEnabled();
$historyLimit = Main::getHistoryLimit();
$historyTtlDays = Main::getHistoryRetentionDays();
$canEditSettings = $POST_RIGHT >= 'W';

$memoryLimitBytes = acrit_perfmon_ini_to_bytes((string)ini_get('memory_limit'));
$memoryPeakBytes = (int)memory_get_peak_usage(true);
$memoryCaption = $memoryLimitBytes > 0
    ? Loc::getMessage('ACRIT_PERFMON_MEMORY_CAPTION', [
        '#USED#' => acrit_perfmon_format_bytes($memoryPeakBytes),
        '#LIMIT#' => acrit_perfmon_format_bytes($memoryLimitBytes),
    ])
    : Loc::getMessage('ACRIT_PERFMON_MEMORY_CAPTION_UNLIMITED', [
        '#USED#' => acrit_perfmon_format_bytes($memoryPeakBytes),
    ]);

$uiConfig = [
    'ajaxUrl' => '/bitrix/tools/acrit.perfmon/ajax.php',
    'sessid' => bitrix_sessid(),
    'groupOrder' => [
        'PHP' => 100,
        'Кеширование' => 200,
        'Битрикс' => 300,
        'База данных' => 400,
        'Окружение' => 500,
    ],
    'messages' => [
        'idle' => Loc::getMessage('ACRIT_PERFMON_PROGRESS_IDLE'),
        'preparing' => Loc::getMessage('ACRIT_PERFMON_PROGRESS_PREPARING'),
        'running' => Loc::getMessage('ACRIT_PERFMON_PROGRESS_RUNNING'),
        'finalizing' => Loc::getMessage('ACRIT_PERFMON_PROGRESS_FINALIZING'),
        'done' => Loc::getMessage('ACRIT_PERFMON_PROGRESS_DONE'),
        'stopped' => Loc::getMessage('ACRIT_PERFMON_PROGRESS_STOPPED'),
        'noTests' => Loc::getMessage('ACRIT_PERFMON_NO_TESTS'),
        'ajaxError' => Loc::getMessage('ACRIT_PERFMON_AJAX_ERROR'),
        'statusOk' => Loc::getMessage('ACRIT_PERFMON_STATUS_OK_SHORT'),
        'statusFail' => Loc::getMessage('ACRIT_PERFMON_STATUS_FAIL_SHORT'),
        'result' => Loc::getMessage('ACRIT_PERFMON_RESULT_MESSAGE'),
        'recommendation' => Loc::getMessage('ACRIT_PERFMON_RESULT_RECOMMENDATION'),
    ],
    'supportUrl' => 'acrit_perfmon_support.php?lang=' . LANGUAGE_ID . '&tabControl_active_tab=ask',
];
?>
<?php
$tabControl->Begin();
$configEncoded = base64_encode((string)json_encode($uiConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
?>
<link rel="stylesheet" href="/local/modules/acrit.perfmon/assets/css/style.css?ver=17">
<script src="/local/modules/acrit.perfmon/assets/js/options.js?ver=17"></script>
<form method="post" action="<?=htmlspecialcharsbx($APPLICATION->GetCurPage())?>?mid=<?=urlencode($moduleId)?>&amp;lang=<?=LANGUAGE_ID?>">
    <?php $tabControl->BeginNextTab(); ?>

    <div id="acrit-perfmon-app" class="acrit-perfmon-system-check" data-config="<?=htmlspecialcharsbx($configEncoded)?>">
        <?php if (!empty($_REQUEST['saved'])): ?>
            <div class="adm-info-message acrit-perfmon-inline-message"><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_SETTINGS_SAVED')?></div>
        <?php endif; ?>

        <div class="acrit-perfmon-system-check__intro"><?=Loc::getMessage('ACRIT_PERFMON_BLOCK_TEXT')?></div>

        <div class="acrit-perfmon-system-check__panel">
            <div class="acrit-perfmon-system-check__title-wrap">
                <div class="acrit-perfmon-system-check__title"><?=Loc::getMessage('ACRIT_PERFMON_BLOCK_TITLE')?></div>
            </div>

            <div class="acrit-perfmon-system-check__controls">
                <input type="button" id="acrit-perfmon-run-button" value="<?=Loc::getMessage('ACRIT_PERFMON_RUN_BUTTON')?>" class="adm-btn-save">
                <input type="button" id="acrit-perfmon-stop-button" value="<?=Loc::getMessage('ACRIT_PERFMON_STOP_BUTTON')?>" class="adm-btn" disabled>
            </div>

            <div class="acrit-perfmon-system-check__progress-title"><?=Loc::getMessage('ACRIT_PERFMON_PROGRESS_RUN_TITLE')?></div>
            <div class="acrit-perfmon-system-check__progress-row">
                <div class="acrit-perfmon-system-check__progress-bar">
                    <div class="acrit-perfmon-system-check__progress-fill" id="acrit-perfmon-progress-fill"></div>
                </div>
                <div class="acrit-perfmon-system-check__progress-percent" id="acrit-perfmon-progress-percent">0%</div>
            </div>
            <div class="acrit-perfmon-system-check__progress-note" id="acrit-perfmon-progress-state"></div>
            <div class="acrit-perfmon-system-check__progress-note acrit-perfmon-system-check__progress-note--muted"><?=$memoryCaption?></div>

            <div class="acrit-perfmon-summary">
                <div class="acrit-perfmon-summary__item">
                    <span class="acrit-perfmon-summary__label"><?=Loc::getMessage('ACRIT_PERFMON_SUMMARY_CHECKED')?></span>
                    <span class="acrit-perfmon-summary__value" id="acrit-perfmon-summary-checked">0</span>
                </div>
                <div class="acrit-perfmon-summary__item acrit-perfmon-summary__item--success">
                    <span class="acrit-perfmon-summary__label"><?=Loc::getMessage('ACRIT_PERFMON_SUMMARY_SUCCESS')?></span>
                    <span class="acrit-perfmon-summary__value" id="acrit-perfmon-summary-success">0</span>
                </div>
                <div class="acrit-perfmon-summary__item acrit-perfmon-summary__item--fail">
                    <span class="acrit-perfmon-summary__label"><?=Loc::getMessage('ACRIT_PERFMON_SUMMARY_FAIL_FOUND')?></span>
                    <span class="acrit-perfmon-summary__value" id="acrit-perfmon-summary-fail">0</span>
                </div>
            </div>

            <div id="acrit-perfmon-results" class="acrit-perfmon-results is-hidden">
                <table class="acrit-perfmon-result-table">
                    <tbody id="acrit-perfmon-results-body"></tbody>
                </table>
            </div>

            <div id="acrit-perfmon-audit-wrap" class="acrit-perfmon-system-check__footer is-hidden">
                <a href="acrit_perfmon_support.php?lang=<?=LANGUAGE_ID?>&tabControl_active_tab=ask" class="adm-btn adm-btn-save">
                    <?=Loc::getMessage('ACRIT_PERFMON_AUDIT_BUTTON')?>
                </a>
            </div>

            <div class="acrit-perfmon-panel-section">
                <div class="acrit-perfmon-settings-block">
                    <div class="acrit-perfmon-settings-block__title"><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_SETTINGS_TITLE')?></div>
                    <div class="acrit-perfmon-settings-block__body">
                        <table class="acrit-perfmon-settings-table">
                            <tbody>
                            <tr>
                                <td><label for="acrit-perfmon-log-enabled"><?=Loc::getMessage('ACRIT_PERFMON_OPTION_LOG_ENABLED')?></label></td>
                                <td>
                                    <input
                                        type="checkbox"
                                        name="<?=htmlspecialcharsbx(OptionHelper::OPTION_LOG_ENABLED)?>"
                                        id="acrit-perfmon-log-enabled"
                                        value="Y"
                                        <?=$historyEnabled ? ' checked' : ''?>
                                        <?=$canEditSettings ? '' : ' disabled'?>
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td><label for="acrit-perfmon-history-limit"><?=Loc::getMessage('ACRIT_PERFMON_OPTION_HISTORY_LIMIT')?></label></td>
                                <td>
                                    <input
                                        type="text"
                                        class="adm-input"
                                        size="6"
                                        name="<?=htmlspecialcharsbx(OptionHelper::OPTION_HISTORY_LIMIT)?>"
                                        id="acrit-perfmon-history-limit"
                                        value="<?=htmlspecialcharsbx((string)$historyLimit)?>"
                                        <?=$canEditSettings ? '' : ' disabled'?>
                                    >
                                </td>
                            </tr>
                            <tr>
                                <td><label for="acrit-perfmon-history-ttl-days"><?=Loc::getMessage('ACRIT_PERFMON_OPTION_HISTORY_TTL_DAYS')?></label></td>
                                <td>
                                    <input
                                        type="text"
                                        class="adm-input"
                                        size="6"
                                        name="<?=htmlspecialcharsbx(OptionHelper::OPTION_HISTORY_TTL_DAYS)?>"
                                        id="acrit-perfmon-history-ttl-days"
                                        value="<?=htmlspecialcharsbx((string)$historyTtlDays)?>"
                                        <?=$canEditSettings ? '' : ' disabled'?>
                                    >
                                </td>
                            </tr>
                            </tbody>
                        </table>
                        <div class="acrit-perfmon-settings-note"><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_SETTINGS_NOTE')?></div>
                    </div>
                </div>
            </div>

            <?php if ($historyEnabled && !empty($historyRows)): ?>
                <div class="acrit-perfmon-panel-section">
                    <div class="acrit-perfmon-history-block">
                        <div class="acrit-perfmon-history-block__title"><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_TITLE')?></div>
                        <table class="acrit-perfmon-result-table acrit-perfmon-result-table--history">
                            <tbody>
                            <tr class="acrit-perfmon-result-table__group-row">
                                <td><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_DATE')?></td>
                                <td><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_USER')?></td>
                                <td><?=Loc::getMessage('ACRIT_PERFMON_HISTORY_SUMMARY_COL')?></td>
                            </tr>
                            <?php foreach ($historyRows as $row): ?>
                                <tr>
                                    <td><?=htmlspecialcharsbx((string)$row['CREATED_AT'])?></td>
                                    <td><?=htmlspecialcharsbx((string)$row['USER_ID'])?></td>
                                    <td><?=htmlspecialcharsbx((string)$row['SUMMARY'])?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</form>
