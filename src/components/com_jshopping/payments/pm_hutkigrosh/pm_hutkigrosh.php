<?php
/*
* @info     Платёжный модуль BGPB для JoomShopping
* @package  bgpb
* @author   esas.by
* @license  GNU/GPL
*/


use esas\cmsgate\hutkigrosh\controllers\ControllerHutkigroshAddBill;
use esas\cmsgate\hutkigrosh\controllers\ControllerHutkigroshWebpayForm;
use esas\cmsgate\hutkigrosh\protocol\HutkigroshBillNewRs;
use esas\cmsgate\hutkigrosh\RegistryHutkigroshJoomshopping;
use esas\cmsgate\hutkigrosh\utils\RequestParamsHutkigrosh;
use esas\cmsgate\hutkigrosh\view\client\CompletionPanelHutkigrosh;
use esas\cmsgate\hutkigrosh\view\client\CompletionPanelHutkigroshJoomshopping;
use esas\cmsgate\messenger\Messages;
use esas\cmsgate\Registry;
use esas\cmsgate\utils\Logger;
use esas\cmsgate\view\admin\ConfigForm;
use esas\cmsgate\view\admin\ConfigPageJoomshopping;
use esas\cmsgate\view\ViewUtils;
use esas\cmsgate\wrappers\SystemSettingsWrapperJoomshopping;
use JFactory;

defined('_JEXEC') or die('Restricted access');
require_once(dirname(__FILE__) . '/init.php');

class pm_hutkigrosh extends PaymentRoot
{
    /**
     * Отображение формы с настройками платежного шлюза (админка)
     * Для отображения ошибок в форме, сами формы должны быть сохранены в сессии
     * @param $params
     */
    function showAdminFormParams($params)
    {
        try {
            $configForms = new ConfigPageJoomshopping();
            $configFormCommon = RegistryHutkigroshJoomshopping::getRegistry()->getConfigForm();
            $this->validateFields($configFormCommon);
            $configForms->addForm($configFormCommon);
            echo $configForms->generate();
        } catch (Exception $e) {
            JFactory::getApplication()->enqueueMessage(ViewUtils::logAndGetMsg("admin", $e), 'error');
        } catch (Throwable $e) {
            JFactory::getApplication()->enqueueMessage(ViewUtils::logAndGetMsg("admin", $e), 'error');
        }
    }

    /**
     * @param ConfigForm $configForm
     */
    private function validateFields($configForm)
    {
        if (!$configForm->isValid()) {
            JFactory::getApplication()->enqueueMessage(RegistryHutkigroshJoomshopping::getRegistry()->getTranslator()->translate(Messages::INCORRECT_INPUT), 'error');
        }
    }

    const RESP_CODE_OK = '0';
    const RESP_CODE_CANCELED = '2018';


    function checkTransaction($pmconfigs, $order, $act)
    {
        $request_params = JFactory::getApplication()->input->request->getArray();
        // все переменные передаются в запросе, можно передевать через сессию
        $hgStatusCode = $request_params[RequestParamsHutkigrosh::HUTKIGROSH_STATUS];
        $billId = $request_params[RequestParamsHutkigrosh::BILL_ID];
        if ($hgStatusCode != '0') {
            // в hutkigrosh большое кол-во кодов неуспешного выставления счета, поэтому для упрощения сводим их все к одному
            $respCode = self::RESP_CODE_CANCELED;
            $message = "Ошибка выставления счета";
        } else {
            $respCode = self::RESP_CODE_OK;
            $message = 'Order[' . $order->order_id . '] was successfully added to Hutkigrosh with billid[' . $billId . ']';
        }
        //пока счет не будет оплачен в ЕРИП у заказа будет статус Pending
        return array($respCode, $message, $billId);
    }

    /**
     * На основе кода ответа от платежного шлюза задаем статус заказу
     * @param int $rescode
     * @param array $pmconfigs
     * @return mixed
     */
    function getStatusFromResCode($rescode, $pmconfigs)
    {
        if ($rescode != self::RESP_CODE_OK) {
            $status = Registry::getRegistry()->getConfigWrapper()->getBillStatusCanceled();
        } else {
            $status = Registry::getRegistry()->getConfigWrapper()->getBillStatusPayed();
        }
        return $status;
    }

    /**
     * При каких кодах ответов от платежного шлюза считать оплату неуспешной.
     * @return array
     */
    function getNoBuyResCode()
    {
        // в bgpb большое кол-во кодов неуспешного выставления счета, поэтому для упрощения сводим их все к одному
        return array(self::RESP_CODE_CANCELED);
    }

    /**
     * Форма отображаемая клиенту на step7.
     * @param $pmconfigs
     * @param $order
     * @throws Throwable
     */
    function showEndForm($pmconfigs, $order)
    {
        try {
            $orderWrapper = Registry::getRegistry()->getOrderWrapper($order->order_id);
            $controller = new ControllerHutkigroshAddBill();
            /**
             * @var HutkigroshBillNewRs
             */
            $addBillRs = $controller->process($orderWrapper);
            /**
             * На этом этапе мы только выполняем запрос к HG для добавления счета. Мы не показываем итоговый экран
             * (с кнопками webpay и alfaclick), а выполняем автоматический редирект на step7
             **/
            $redirectParams = array(
                "js_paymentclass" => SystemSettingsWrapperJoomshopping::getPaymentCode(),
                RequestParamsHutkigrosh::HUTKIGROSH_STATUS => $addBillRs->getResponseCode(),
                RequestParamsHutkigrosh::ORDER_ID => $order->order_id);
            if ($addBillRs->getBillId())
                $redirectParams[RequestParamsHutkigrosh::BILL_ID] = $addBillRs->getBillId();

            JFactory::getApplication()->redirect(SystemSettingsWrapperJoomshopping::generateControllerPath("checkout", "step7") . '&' . http_build_query($redirectParams));
        } catch (Throwable $e) {
            $this->redirectError($e->getMessage());
        } catch (Exception $e) { // для совместимости с php 5
            $this->redirectError($e->getMessage());
        }

    }

    function redirectError($message)
    {
        JFactory::getApplication()->redirect(JRoute::_('index.php?option=com_jshopping&controller=cart&task=view', FALSE), stripslashes($message), 'error');
    }

    // возможно, уже не надо
    function getUrlParams($pmconfigs)
    {
        $reqest_params = JFactory::getApplication()->input->request->getArray();
        $params = array();
        $params['order_id'] = $reqest_params[RequestParamsHutkigrosh::ORDER_ID];
        $params['hash'] = '';
        $params['checkHash'] = false;
        $params['checkReturnParams'] = false;
        return $params;
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
            $orderWrapper = Registry::getRegistry()->getOrderWrapper($order->order_id);
            $completionPanel = new CompletionPanelHutkigroshJoomshopping($orderWrapper);
            if (RegistryHutkigroshJoomshopping::getRegistry()->getConfigWrapper()->isAlfaclickSectionEnabled()) {
                $completionPanel->setAlfaclickUrl(SystemSettingsWrapperJoomshopping::generatePaySystemControllerPath("alfaclick"));
            }
            if (RegistryHutkigroshJoomshopping::getRegistry()->getConfigWrapper()->isWebpaySectionEnabled()) {
                $controller = new ControllerHutkigroshWebpayForm();
                $webpayResp = $controller->process($orderWrapper);
                $completionPanel->setWebpayForm($webpayResp->getHtmlForm());
                if (array_key_exists(RequestParamsHutkigrosh::WEBPAY_STATUS, $_REQUEST))
                    $completionPanel->setWebpayStatus($_REQUEST[RequestParamsHutkigrosh::WEBPAY_STATUS]);
            }
            $completionPanel->render();
        } catch (Throwable $e) {
            Logger::getLogger("payment")->error("Exception:", $e);
            JFactory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }
    }
}

?>