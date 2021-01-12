<?php
/*
* @info     Платёжный модуль Hutkigrosh для JoomShopping
* @package  hutkigrosh
* @author   esas.by
* @license  GNU/GPL
*/
define('PATH_JSHOPPING', JPATH_SITE . '/components/com_jshopping/');
define('PATH_JSHOPPING_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_jshopping/');

use esas\cmsgate\hutkigrosh\ConfigFieldsHutkigrosh;
use esas\cmsgate\joomshopping\InstallHelperJoomshopping;
use Joomla\CMS\Filesystem\Folder;

defined('_JEXEC') or die();
jimport('joomla.filesystem.folder');

class plgjshoppingHutkigroshInstallerScript
{
    public function update()
    {
    }

    public function install($parent)
    {
    }

    public function postflight($type, $parent)
    {
        try {
            self::preInstall();
            InstallHelperJoomshopping::dbPaymentMethodAdd();
            InstallHelperJoomshopping::dbCompletionTextAdd(ConfigFieldsHutkigrosh::completionText());
            InstallHelperJoomshopping::dbActivateExtension();
        } catch (Exception $e) {
            echo JText::sprintf($e->getMessage());
            return false;
        }
    }

    public function uninstall($parent)
    {
        self::req();
        $ret1 = InstallHelperJoomshopping::dbPaymentMethodDelete();
        $ret2 = InstallHelperJoomshopping::dbCompletionTextDelete(ConfigFieldsHutkigrosh::completionText());
        $ret3 = InstallHelperJoomshopping::deleteFiles();
        return $ret1 && $ret2 && $ret3;
    }

    public static function preInstall() {
        //вручную копируем файлы из временной папки, в папку components, иначе не сработают require_once
        $installTmpPath = dirname(dirname(__FILE__)) . '/jpath_root';
        $newPath = JPATH_ROOT;
        if (!Folder::copy($installTmpPath, $newPath, "", true)) {
            throw new Exception('Can not copy folder from[' . $installTmpPath . '] to [' . $newPath . ']');
        }
        self::req();
    }

    public static function req()
    {
        require_once(dirname(dirname(__FILE__)) . '/init.php');
    }


}