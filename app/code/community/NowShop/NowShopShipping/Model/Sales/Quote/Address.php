<?php
class NowShop_NowShopShipping_Model_Sales_Quote_Address extends Mage_Sales_Model_Quote_Address
{
    public function getShippingRatesCollection()
    {
        parent::getShippingRatesCollection();
        $nowshopCode = Mage::getModel('nowshopshipping/carrier_nowshop')->getCode();
        $removeRates = array();
        foreach ($this->_rates as $key => $rate) {
            if ($rate->getCarrier() == $nowshopCode) {
                $removeRates[] = $key;
            }
        }
        foreach ($removeRates as $key) {
            $this->_rates->removeItemByKey($key);
        }
        return $this->_rates;
    }
}