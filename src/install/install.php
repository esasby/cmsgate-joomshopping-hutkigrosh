<?php
/*
* @info     Платёжный модуль Hutkigrosh для JoomShopping
* @package  hutkigrosh
* @author   esas.by
* @license  GNU/GPL
*/
define('PATH_JSHOPPING', JPATH_SITE . '/components/com_jshopping/');

use esas\cmsgate\hutkigrosh\ConfigFieldsHutkigrosh;
use esas\cmsgate\joomshopping\InstallUtilsJoomshopping;
use esas\cmsgate\Registry;

defined('_JEXEC') or die();
jimport('joomla.filesystem.folder');

class PlgjshoppinghutkigroshInstallerScript
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
            //вручную копируем файлы из временной папки, в папку components, иначе не сработают require_once
//            $pmPath = JPATH_SITE . '/plugins/jshopping/hutkigrosh/components';
//            $newPath = JPATH_SITE . '/components';
//            if (!JFolder::copy($pmPath, $newPath, "", true)) {
//                $this->success = false;
//                echo JText::sprintf('COM_PFMIGRATOR_FOLDER_RENAME_FAILED', $newPath);
//                return false;
//            }
//            $this->req();
            self::preInstall('hutkigrosh');
            self::req('hutkigrosh');
            InstallUtilsJoomshopping::dbAddPaymentMethod();
            $this->dbAddCompletionText();
            InstallUtilsJoomshopping::dbActivatePlugin();
        } catch (Exception $e) {
            echo JText::sprintf($e->getMessage());
            return false;
        }
    }

    public function uninstall($parent)
    {
        $ret = true;
        self::req('hutkigrosh');
//        $this->req();
        $ret = $ret && InstallUtilsJoomshopping::dbDeletePaymentMethod();
        $ret = $ret && $this->dbDeleteCompletionText();
        $ret = $ret && InstallUtilsJoomshopping::deleteFiles();
        return $ret;
    }

//    private function req()
//    {
//        require_once(PATH_JSHOPPING . 'lib/factory.php');
//        require_once(PATH_JSHOPPING . 'payments/pm_hutkigrosh/init.php');
//    }

    public static function preInstall($paySystemName) {
        //вручную копируем файлы из временной папки, в папку components, иначе не сработают require_once
        $pmPath = JPATH_SITE . '/plugins/jshopping/' . $paySystemName . '/components';
        $newPath = JPATH_SITE . '/components';
        if (!JFolder::copy($pmPath, $newPath, "", true)) {
            throw new Exception('Can not copy folder from[' . $pmPath . '] to [' . $newPath . ']');
        }

    }

    public static function req($paySystemName)
    {
        require_once(PATH_JSHOPPING . 'lib/factory.php');
        require_once(PATH_JSHOPPING . 'payments/pm_' . $paySystemName . '/init.php');
    }

    private function dbAddCompletionText()
    {
        $staticText = new stdClass();
        $staticText->alias = ConfigFieldsHutkigrosh::completionText();
        $staticText->use_for_return_policy = 0;
        $jshoppingLanguages = JSFactory::getTable('language', 'jshop');
        foreach ($jshoppingLanguages::getAllLanguages() as $lang) {
            $i18nField = 'text_' . $lang->language;
            $staticText->$i18nField = Registry::getRegistry()->getTranslator()->getConfigFieldDefault(ConfigFieldsHutkigrosh::completionText(), $lang->language);
        }
        return JFactory::getDbo()->insertObject('#__jshopping_config_statictext', $staticText);
    }

    private function dbDeleteCompletionText()
    {
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);
        $conditions = array(
            $db->quoteName('alias') . ' = ' . $db->quote(ConfigFieldsHutkigrosh::completionText())
        );
        $query->delete($db->quoteName('#__jshopping_config_statictext'));
        $query->where($conditions);

        $db->setQuery($query);
        return $db->execute();
    }
}