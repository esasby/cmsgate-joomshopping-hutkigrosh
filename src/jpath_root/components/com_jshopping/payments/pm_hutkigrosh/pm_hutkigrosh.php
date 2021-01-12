<?php
/*
* @info     Платёжный модуль BGPB для JoomShopping
* @package  bgpb
* @author   esas.by
* @license  GNU/GPL
*/
defined('_JEXEC') or die('Restricted access');
require_once(JPATH_SITE . '/plugins/jshopping/hutkigrosh/init.php');

use esas\cmsgate\hutkigrosh\controllers\ControllerHutkigroshAddBill;
use esas\cmsgate\hutkigrosh\controllers\ControllerHutkigroshCompletionPage;
use esas\cmsgate\joomshopping\CmsgatePaymentRootJoomshopping;
use esas\cmsgate\utils\Logger;
use Joomla\CMS\Factory;

class pm_hutkigrosh extends CmsgatePaymentRootJoomshopping
{
    /**
     * Форма отображаемая клиенту на step7.
     * @param $pmconfigs
     * @param $order
     * @throws Throwable
     */
    function addInvoice($orderWrapper)
    {
        $controller = new ControllerHutkigroshAddBill();
        $controller->process($orderWrapper);
    }

    /**
     * В теории, тут должно отправлятся уведомление на шлюз об успешном оформлении заказа.
     * В случае с ХуткиГрош мы тут отображаем итоговый экран с доп. кнопками.
     * @param $pmconfigs
     * @param $order
     * @param $payment
     * @throws Throwable
     */
    function complete($pmconfigs, $order, $payment)
    {
        try {
            $controller = new ControllerHutkigroshCompletionPage();
            $completionPanel = $controller->process($order->order_id);
            $completionPanel->render();
        } catch (Throwable $e) {
            Logger::getLogger("payment")->error("Exception:", $e);
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
    }
}

?>