<?php
/**
 * Created by PhpStorm.
 * User: nikit
 * Date: 01.10.2018
 * Time: 12:05
 */

namespace esas\cmsgate\hutkigrosh;

use esas\cmsgate\CmsConnectorJoomshopping;
use esas\cmsgate\descriptors\ModuleDescriptor;
use esas\cmsgate\descriptors\VendorDescriptor;
use esas\cmsgate\descriptors\VersionDescriptor;
use esas\cmsgate\hutkigrosh\utils\RequestParamsHutkigrosh;
use esas\cmsgate\hutkigrosh\view\client\CompletionPanelHutkigroshJoomshopping;
use esas\cmsgate\view\admin\AdminViewFields;
use esas\cmsgate\view\admin\ConfigFormJoomshopping;

class RegistryHutkigroshJoomshopping extends RegistryHutkigrosh
{
    public function __construct()
    {
        $this->cmsConnector = new CmsConnectorJoomshopping();
        $this->paysystemConnector = new PaysystemConnectorHutkigrosh();
    }


    /**
     * Переопделение для упрощения типизации
     * @return RegistryHutkigroshJoomshopping
     */
    public static function getRegistry()
    {
        return parent::getRegistry();
    }

    /**
     * @return ConfigFormJoomshopping
     * @throws \Exception
     */
    public function createConfigForm()
    {
        $managedFields = $this->getManagedFieldsFactory()->getManagedFieldsExcept(AdminViewFields::CONFIG_FORM_COMMON,
            [
                ConfigFieldsHutkigrosh::shopName(),
                ConfigFieldsHutkigrosh::paymentMethodName(),
                ConfigFieldsHutkigrosh::paymentMethodDetails(),
                ConfigFieldsHutkigrosh::paymentMethodNameWebpay(),
                ConfigFieldsHutkigrosh::paymentMethodDetailsWebpay(),
            ]);
        $configForm = new ConfigFormJoomshopping(
            $managedFields,
            AdminViewFields::CONFIG_FORM_COMMON,
            null,
            null);
        $configForm->addSubmitButton(AdminViewFields::CONFIG_FORM_BUTTON_DOWNLOAD_LOG);
        return $configForm;
    }


    function getUrlAlfaclick($orderWrapper)
    {
        return
            CmsConnectorJoomshopping::generatePaySystemControllerUrl("alfaclick");
    }

    function getUrlWebpay($orderWrapper)
    {
        return
            CmsConnectorJoomshopping::generatePaySystemControllerUrl("complete") .
            "&" . RequestParamsHutkigrosh::ORDER_NUMBER . "=" . $orderWrapper->getOrderNumber() .
            "&" . RequestParamsHutkigrosh::BILL_ID . "=" . $orderWrapper->getExtId();
    }

    public function getCompletionPanel($orderWrapper)
    {
        return new CompletionPanelHutkigroshJoomshopping($orderWrapper);
    }

    public function createModuleDescriptor()
    {
        return new ModuleDescriptor(
            "hutkigrosh",
            new VersionDescriptor("3.1.0", "2021-01-05"),
            "Прием платежей через ЕРИП (сервис Hutkirosh)",
            "https://bitbucket.esas.by/projects/CG/repos/cmsgate-joomshopping-hutkigrosh/browse",
            VendorDescriptor::esas(),
            "Выставление пользовательских счетов в ЕРИП"
        );
    }
}