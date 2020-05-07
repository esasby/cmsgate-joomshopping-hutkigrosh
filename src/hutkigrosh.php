<?php
/*
* @info     Платёжный модуль Hutkigrosh для JoomShopping
* @package  hutkigrosh
* @author   esas.by
* @license  GNU/GPL
*/

require_once(JPATH_SITE . '/components/com_jshopping/payments/pm_hutkigrosh/init.php');

use esas\cmsgate\hutkigrosh\ConfigFieldsHutkigrosh;
use esas\cmsgate\hutkigrosh\RegistryHutkigroshJoomshopping;
use esas\cmsgate\joomshopping\CmsgatePlugin;
use esas\cmsgate\utils\FileUtils;
use esas\cmsgate\utils\Logger;
use esas\cmsgate\view\admin\AdminViewFields;

defined('_JEXEC') or die;

class plgJShoppingHutkigrosh extends CmsgatePlugin
{
//    public function onAfterSavePayment(&$payment)
//    {
//        try {
//            if ($payment->payment_class == SystemSettingsWrapperJoomshopping::getPaymentCode()) { //проверяем, что выполнится только для нужно платежной системы
//                if (isset($_REQUEST[AdminViewFields::CONFIG_FORM_BUTTON_SAVE])) {
//                    $configForm = RegistryHutkigroshJoomshopping::getRegistry()->getConfigForm();
//                    $this->saveOrRedirect($configForm);
//                } elseif (isset($_REQUEST[AdminViewFields::CONFIG_FORM_BUTTON_DOWNLOAD_LOG])) {
//                    FileUtils::downloadByPath(Logger::getLogFilePath());
//                } else {
//                    //если ни одна из внутренних кнопок не была нажата, очищаем сессию,
//                    //чтобы корректно отработала основная кнопка "save"
//                    SessionUtils::removeAllForms();
//                }
//            }
//        } catch (Exception $e) {
//            Logger::getLogger("admin")->error("Exception: ", $e);
//        } catch (Throwable $e) {
//            Logger::getLogger("admin")->error("Exception: ", $e);
//        }
//    }

    function onBeforeSavePayment(&$post)
    {
        try {
            if (isset($_REQUEST[AdminViewFields::CONFIG_FORM_BUTTON_DOWNLOAD_LOG])) {
                FileUtils::downloadByPath(Logger::getLogFilePath());
            } else {
                $configForm = RegistryHutkigroshJoomshopping::getRegistry()->getConfigForm();
                $this->saveOrRedirect($configForm);
                //если ни одна из внутренних кнопок не была нажата, очищаем сессию,
                //чтобы корректно отработала основная кнопка "save"
//                SessionUtils::removeAllForms();
            }


        } catch (Throwable $e) {
            Logger::getLogger("admin")->error("Exception: ", $e);
        }
    }

}