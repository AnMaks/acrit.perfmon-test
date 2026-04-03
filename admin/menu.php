<?php

use Acrit\Perfmon\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);
Loader::includeModule('acrit.perfmon');

if ($GLOBALS['APPLICATION']->GetGroupRight(Main::getModuleId()) < 'R') {
    return;
}

$aMenu[] = [
    'parent_menu' => 'global_menu_acrit',
    'section' => 'acrit_perfmon',
    'sort' => 160,
    'text' => Loc::getMessage('ACRIT_PERFMON_MENU_TITLE'),
    'title' => Loc::getMessage('ACRIT_PERFMON_MENU_TITLE'),
    'icon' => 'fileman_menu_icon',
    'page_icon' => 'fileman_menu_icon',
    'module_id' => Main::getModuleId(),
    'items_id' => 'menu_acrit_perfmon',
    'items' => [
        [
            'text' => Loc::getMessage('ACRIT_PERFMON_MENU_TESTS'),
            'title' => Loc::getMessage('ACRIT_PERFMON_MENU_TESTS'),
            'url' => 'settings.php?lang=' . LANGUAGE_ID . '&mid=' . urlencode(Main::getModuleId()),
            'more_url' => ['settings.php?lang=' . LANGUAGE_ID . '&mid=' . urlencode(Main::getModuleId()), 'settings.php?lang=' . LANGUAGE_ID . '&mid=' . urlencode(Main::getModuleId()) . '&tabControl_active_tab=tests'],
        ],
        [
            'text' => Loc::getMessage('ACRIT_PERFMON_MENU_SUPPORT'),
            'title' => Loc::getMessage('ACRIT_PERFMON_MENU_SUPPORT'),
            'url' => 'acrit_perfmon_support.php?lang=' . LANGUAGE_ID,
            'more_url' => ['acrit_perfmon_support.php?lang=' . LANGUAGE_ID],
        ],
    ],
];

return $aMenu;
