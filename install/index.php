<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;

Loc::loadMessages(__FILE__);

class acrit_perfmon extends CModule
{
    public const MODULE_ID = 'acrit.perfmon';

    public $MODULE_ID = 'acrit.perfmon';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME;
    public $MODULE_DESCRIPTION;
    public $PARTNER_NAME;
    public $PARTNER_URI;

    protected string $localModulePath;
    protected string $documentRoot;

    public function __construct()
    {
        $this->documentRoot = Application::getDocumentRoot();
        $this->localModulePath = $this->documentRoot . '/local/modules/' . self::MODULE_ID;

        include __DIR__ . '/version.php';
        if (is_array($arModuleVersion)) {
            $this->MODULE_VERSION = $arModuleVersion['VERSION'];
            $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        }

        $this->MODULE_NAME = Loc::getMessage('ACRIT_PERFMON_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('ACRIT_PERFMON_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('ACRIT_PERFMON_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('ACRIT_PERFMON_PARTNER_URI');
    }

    public function DoInstall(): bool
    {
        global $APPLICATION;

        if (!$this->hasRights()) {
            $APPLICATION->AuthForm(Loc::getMessage('ACRIT_PERFMON_ACCESS_DENIED'));
            return false;
        }

        ModuleManager::registerModule(self::MODULE_ID);
        $this->installFiles();
        $this->installDB();

        return true;
    }

    public function DoUninstall(): bool
    {
        global $APPLICATION;

        if (!$this->hasRights()) {
            $APPLICATION->AuthForm(Loc::getMessage('ACRIT_PERFMON_ACCESS_DENIED'));
            return false;
        }

        $this->unInstallFiles();
        $this->unInstallDB();
        Option::delete(self::MODULE_ID);
        ModuleManager::unRegisterModule(self::MODULE_ID);

        return true;
    }

    public function installFiles(): void
    {
        $adminSourceDir = $this->localModulePath . '/admin';
        $adminTargetDir = $this->documentRoot . '/bitrix/admin';
        if (is_dir($adminSourceDir) && ($dir = opendir($adminSourceDir))) {
            while (($file = readdir($dir)) !== false) {
                if (in_array($file, ['.', '..', 'menu.php'], true)) {
                    continue;
                }
                $stubName = 'acrit_perfmon_' . $file;
                $stubPath = $adminTargetDir . '/' . $stubName;
                $stubCode = '<?php require($_SERVER["DOCUMENT_ROOT"] . "/local/modules/' . self::MODULE_ID . '/admin/' . $file . '");';
                file_put_contents($stubPath, $stubCode);
            }
            closedir($dir);
        }

        $obsoleteTestsStub = $adminTargetDir . '/acrit_perfmon_tests.php';
        if (is_file($obsoleteTestsStub)) {
            unlink($obsoleteTestsStub);
        }

        DeleteDirFilesEx('/bitrix/js/acrit.perfmon/');
        @unlink($this->documentRoot . '/bitrix/themes/.default/acrit.perfmon.css');

        if (is_dir($this->localModulePath . '/install/tools')) {
            CopyDirFiles(
                $this->localModulePath . '/install/tools',
                $this->documentRoot . '/bitrix/tools',
                true,
                true
            );
        }
    }

    public function unInstallFiles(): void
    {
        foreach (['tests.php', 'support.php'] as $file) {
            $stubPath = $this->documentRoot . '/bitrix/admin/acrit_perfmon_' . $file;
            if (is_file($stubPath)) {
                unlink($stubPath);
            }
        }

        DeleteDirFilesEx('/bitrix/js/acrit.perfmon/');
        DeleteDirFilesEx('/bitrix/tools/acrit.perfmon/');
        @unlink($this->documentRoot . '/bitrix/themes/.default/acrit.perfmon.css');
    }

    public function installDB(): void
    {
        global $DB, $APPLICATION;

        $sqlFile = $this->localModulePath . '/install/db/mysql/install.sql';
        if (is_file($sqlFile)) {
            $errors = $DB->RunSQLBatch($sqlFile);
            if ($errors !== false) {
                $APPLICATION->ThrowException(implode('<br>', $errors));
            }
        }
    }

    public function unInstallDB(): void
    {
        global $DB, $APPLICATION;

        $sqlFile = $this->localModulePath . '/install/db/mysql/uninstall.sql';
        if (is_file($sqlFile)) {
            $errors = $DB->RunSQLBatch($sqlFile);
            if ($errors !== false) {
                $APPLICATION->ThrowException(implode('<br>', $errors));
            }
        }
    }


    protected function hasRights(): bool
    {
        global $APPLICATION;
        return $APPLICATION->GetGroupRight(self::MODULE_ID) >= 'W';
    }
}
