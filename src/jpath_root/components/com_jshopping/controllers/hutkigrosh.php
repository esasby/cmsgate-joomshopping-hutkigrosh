<?php
/*
* @info     Платёжный модуль Hutkigrosh для JoomShopping
* @package  hutkigrosh
* @author   esas.by
* @license  GNU/GPL
*/
defined('_JEXEC') or die();

use esas\cmsgate\hutkigrosh\controllers\ControllerHutkigroshAlfaclick;
use esas\cmsgate\hutkigrosh\controllers\ControllerHutkigroshNotify;
use esas\cmsgate\joomshopping\CmsgateControllerJoomshopping;
use esas\cmsgate\utils\Logger as HgLogger;

require_once(JPATH_SITE . '/plugins/jshopping/hutkigrosh/init.php');

class JshoppingControllerHutkigrosh extends CmsgateControllerJoomshopping
{
    /**
     * Выставляет счет в альфаклик
     */
    function alfaclick()
    {
        try {
            $controller = new ControllerHutkigroshAlfaclick();
            $controller->process();
        } catch (Throwable $e) {
            HgLogger::getLogger("alfaclick")->error("Exception: ", $e);
        }
    }


    /**
     * Callback, который вызывает сам ХуткиГрош для оповещение об оплате счета в ЕРИП
     * Тут выполняется дополнительная проверка статуса счета на шлюза и при необходимости изменение его статус заказа
     * в локальной БД
     */
    function notify()
    {
        try {
            $controller = new ControllerHutkigroshNotify();
            $controller->process();
        } catch (Throwable $e) {
            HgLogger::getLogger("callback")->error("Exception:", $e);
        }
    }
}