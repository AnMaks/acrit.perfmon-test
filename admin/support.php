<?php

use Acrit\Perfmon\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

Loc::loadMessages(__FILE__);
Loader::includeModule('acrit.perfmon');

$moduleId = Main::getModuleId();
if ($APPLICATION->GetGroupRight($moduleId) < 'R') {
    $APPLICATION->AuthForm(Loc::getMessage('ACRIT_PERFMON_ACCESS_DENIED'));
}

global $APPLICATION;
require_once dirname(__DIR__) . '/install/version.php';

\CJSCore::Init(['ajax']);

$strPageTitle = Loc::getMessage('ACRIT_PERFMON_PAGE_TITLE_SUPPORT');
$prefilledReport = Main::getSupportReportFromSession();
$supportMessage = $prefilledReport !== '' ? $prefilledReport : '';
$autoFilled = $prefilledReport !== '';

$documentationUrl = 'https://www.acrit-studio.ru/technical-support/';
$requirementsUrl = '/bitrix/admin/site_checker.php?lang=' . LANGUAGE_ID;
$ideaUrl = 'https://www.acrit-studio.ru/services/idea/';
$siteDomain = preg_replace('#:(\d+)$#', '', (string)\Bitrix\Main\Context::getCurrent()->getServer()->getHttpHost());
$bitrixVersion = defined('SM_VERSION') ? SM_VERSION . ' (' . SM_VERSION_DATE . ')' : '';
$siteEncoding = defined('SITE_CHARSET') ? SITE_CHARSET : '';
$moduleVersion = is_array($arModuleVersion ?? null) ? (string)($arModuleVersion['VERSION'] ?? '') : '';

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

echo '<link rel="stylesheet" href="/local/modules/acrit.perfmon/assets/css/style.css?ver=17">';
echo '<script src="/local/modules/acrit.perfmon/assets/js/support.js?ver=17"></script>';

$APPLICATION->SetTitle($strPageTitle);

$arTabs = [
    [
        'DIV' => 'documentation',
        'TAB' => Loc::getMessage('ACRIT_PERFMON_TAB_DOCUMENTATION_NAME'),
        'TITLE' => Loc::getMessage('ACRIT_PERFMON_TAB_DOCUMENTATION_DESC'),
    ],
    [
        'DIV' => 'ask',
        'TAB' => Loc::getMessage('ACRIT_PERFMON_TAB_ASK_NAME'),
        'TITLE' => Loc::getMessage('ACRIT_PERFMON_TAB_ASK_DESC'),
    ],
    [
        'DIV' => 'idea',
        'TAB' => Loc::getMessage('ACRIT_PERFMON_TAB_IDEA_NAME'),
        'TITLE' => Loc::getMessage('ACRIT_PERFMON_TAB_IDEA_DESC'),
    ],
];
?>
<div id="acrit_exp_support"><?php
$tabControl = new \CAdminForm('AcritPerfmonSupport', $arTabs);
$tabControl->Begin([
    'FORM_ACTION' => $APPLICATION->GetCurPageParam('', []),
]);

$tabControl->BeginNextFormTab();

$tabControl->BeginCustomField('FAQ', Loc::getMessage('ACRIT_PERFMON_FAQ'));
?>
    <tr class="heading">
        <td><?= $tabControl->GetCustomLabelHTML(); ?></td>
    </tr>
    <tr>
        <td class="acrit-perfmon-support-center">
            <div><a href="<?= htmlspecialcharsbx($documentationUrl); ?>" target="_blank"><?= htmlspecialcharsbx($documentationUrl); ?></a></div>
            <br/>
        </td>
    </tr>
<?php
$tabControl->EndCustomField('FAQ');

$tabControl->BeginCustomField('REQUIREMENTS_1', Loc::getMessage('ACRIT_PERFMON_REQUIREMENTS_1'));
?>
    <tr class="heading">
        <td colspan="2"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    </tr>
    <tr>
        <td class="acrit-perfmon-support-center">
            <div><?= Loc::getMessage('ACRIT_PERFMON_REQUIREMENTS_TEXT', ['#URL#' => $requirementsUrl]); ?></div>
            <br/>
        </td>
    </tr>
<?php
$tabControl->EndCustomField('REQUIREMENTS_1');

$tabControl->BeginNextFormTab();

$tabControl->BeginCustomField('REQUIREMENTS_2', Loc::getMessage('ACRIT_PERFMON_REQUIREMENTS_2'));
?>
    <tr class="heading">
        <td colspan="2"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <div class="acrit-perfmon-support-text"><?= Loc::getMessage('ACRIT_PERFMON_REQUIREMENTS_TEXT', ['#URL#' => $requirementsUrl]); ?></div>
            <br/>
        </td>
    </tr>
<?php
$tabControl->EndCustomField('REQUIREMENTS_2');

$tabControl->BeginCustomField('ASK_FORM', Loc::getMessage('ACRIT_PERFMON_ASK_FORM'));
?>
    <tr class="heading">
        <td colspan="2"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    </tr>
    <tr>
        <td width="40%" class="adm-detail-content-cell-l acrit-perfmon-support-field-label">
            <?= Loc::getMessage('ACRIT_PERFMON_ASK_FORM_FIELD'); ?>
        </td>
        <td width="60%" class="adm-detail-content-cell-r">
            <div class="acrit-perfmon-support-textarea-wrap">
                <textarea
                    cols="70"
                    rows="10"
                    class="acrit-perfmon-support-textarea"
                    id="acrit-perfmon-support-message"
                    data-role="ticket-message"
                    data-autofilled="<?= $autoFilled ? 'Y' : 'N'; ?>"
                    data-error="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_FORM_ERROR_EMPTY')); ?>"
                ><?= htmlspecialcharsbx($supportMessage); ?></textarea>
            </div>
            <div id="acrit-perfmon-autofill-note" class="acrit-perfmon-autofill-note<?= $autoFilled ? ' acrit-perfmon-autofill-note--visible' : ''; ?>">
                <?= Loc::getMessage('ACRIT_PERFMON_SUPPORT_AUTOFILL_NOTE'); ?>
            </div>
            <div class="acrit-perfmon-support-button-wrap">
                <input type="button" id="acrit-perfmon-send-button" value="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_FORM_BUTTON')); ?>" data-role="ticket-send" class="adm-btn-save"/>
            </div>
        </td>
    </tr>
<?php
$tabControl->EndCustomField('ASK_FORM');

$tabControl->BeginCustomField('CONTACTS', Loc::getMessage('ACRIT_PERFMON_ASK_CONTACTS_TITLE'));
?>
    <tr class="heading">
        <td colspan="2"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <fieldset title="<?= htmlspecialcharsbx($tabControl->GetCustomLabelHTML()); ?>">
                <legend><?= $tabControl->GetCustomLabelHTML(); ?></legend>
                <?= Loc::getMessage('ACRIT_PERFMON_ASK_CONTACTS_TEXT'); ?>
            </fieldset>
        </td>
    </tr>
<?php
$tabControl->EndCustomField('CONTACTS');

$tabControl->BeginNextFormTab();

$tabControl->BeginCustomField('IDEA', Loc::getMessage('ACRIT_PERFMON_IDEA'));
?>
    <tr class="heading">
        <td colspan="2"><?= $tabControl->GetCustomLabelHTML(); ?></td>
    </tr>
    <tr>
        <td colspan="2">
            <div><?= Loc::getMessage('ACRIT_PERFMON_IDEA_TEXT', ['#URL#' => $ideaUrl]); ?></div>
            <br/>
        </td>
    </tr>
<?php
$tabControl->EndCustomField('IDEA');

$tabControl->Show();
?></div>

<div hidden>
    <form action="https://www.acrit-studio.ru/support/?show_wizard=Y" method="post" id="form-ticket" target="_blank">
        <input type="hidden" name="send_ticket" value="Y"/>
        <input type="hidden" name="ticket_title" value="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_FORM_SUBJECT', ['#SITE_NAME#' => $siteDomain])); ?>"/>
        <input type="hidden" name="ticket_text" value=""/>
        <input type="hidden" name="module_id" value="<?= htmlspecialcharsbx($moduleId); ?>" data-ticket-meta="Y" data-label="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_MODULE_ID')); ?>"/>
        <input type="hidden" name="module_version" value="<?= htmlspecialcharsbx($moduleVersion); ?>" data-ticket-meta="Y" data-label="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_MODULE_VERSION')); ?>"/>
        <input type="hidden" name="bitrix_version" value="<?= htmlspecialcharsbx($bitrixVersion); ?>" data-ticket-meta="Y" data-label="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_BITRIX_VERSION')); ?>"/>
        <input type="hidden" name="site_encoding" value="<?= htmlspecialcharsbx($siteEncoding); ?>" data-ticket-meta="Y" data-label="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_SITE_ENCODING')); ?>"/>
        <input type="hidden" name="site_domain" value="<?= htmlspecialcharsbx($siteDomain); ?>" data-ticket-meta="Y" data-label="<?= htmlspecialcharsbx(Loc::getMessage('ACRIT_PERFMON_ASK_SITE_DOMAIN')); ?>"/>
    </form>
</div>
<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
