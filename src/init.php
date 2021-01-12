<?php
require_once(dirname(__FILE__) . '/vendor/esas/cmsgate-core/src/esas/cmsgate/CmsPlugin.php');

use esas\cmsgate\hutkigrosh\RegistryHutkigroshJoomshopping;
use esas\cmsgate\CmsPlugin;


(new CmsPlugin(dirname(__FILE__) . '/vendor', dirname(__FILE__)))
    ->setRegistry(new RegistryHutkigroshJoomshopping())
    ->init();

