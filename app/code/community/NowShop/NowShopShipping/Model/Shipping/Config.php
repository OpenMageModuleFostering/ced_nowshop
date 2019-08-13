<?php

class NowShop_NowShopShipping_Model_Shipping_Config extends Mage_Shipping_Model_Config
{
    public function getActiveCarriers($store = null)
    {
        $carriers = parent::getActiveCarriers($store);
        if (Mage::getDesign()->getArea() === Mage_Core_Model_App_Area::AREA_FRONTEND) {
            $carriersCodes = array_keys($carriers);
            $nowshopCode = Mage::getModel('nowshopshipping/carrier_nowshop')->getCode();
            foreach ($carriersCodes as $carriersCode) {
                if ($carriersCode == $nowshopCode) {
                    unset($carriers[$carriersCode]);
                }
            }
        }
        return $carriers;
    }
}
