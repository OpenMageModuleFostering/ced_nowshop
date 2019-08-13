<?php 

/**
 * NowShop
 *
 * NOTICE OF LICENS
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to support@nowshop.com so we can send you a copy immediately.
 *
 * @category    NowShop
 * @package     NowShop_Synchronize
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 * @copyright   Copyright NowShop 2012 - 2014 (http://nowshop.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
class NowShop_Synchronize_Model_System_Config_Source_Configattr
{
    const BY_DEFAULT_BRAND = 'retailer_store_name';
	/**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray($empty = false)
    {
		$option = array();
		$attributes = Mage::getResourceModel('catalog/product_attribute_collection')
							->addVisibleFilter()
							->addFieldToFilter('is_configurable',array('eq'=>1))
							->addFieldToFilter('is_global',array('eq'=>1))
							->addFieldToFilter('frontend_input',array('eq'=>'select'));
	
		if(count($attributes)>0){
			foreach ($attributes as $attribute) {
				/* 	print_r($attribute->getData());die; */
				if(strlen($attribute->getData('frontend_label')))
					$option[] = array('value'=>$attribute->getData('attribute_code'),'label'=>Mage::helper('catalog')->__('%s',$attribute->getData('frontend_label')));
			}
		}
		return $option;
		/* return array(
						array('value' => self::EZ, 'label' => Mage::helper('nowshop')->__('Eurozone')),
						array('value' => self::DK, 'label' => Mage::helper('nowshop')->__('Denmark')),
						array('value' => self::NO, 'label' => Mage::helper('nowshop')->__('Nowrway')),
						array('value' => self::SE, 'label' => Mage::helper('nowshop')->__('Sweden')),
						array('value' => self::GB, 'label' => Mage::helper('nowshop')->__('United Kingdom')),
					); */
    }
}