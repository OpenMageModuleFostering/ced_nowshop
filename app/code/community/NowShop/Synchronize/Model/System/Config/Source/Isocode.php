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
 
class NowShop_Synchronize_Model_System_Config_Source_Isocode
{
	const EZ = 'EZ';
	const DK = 'DK';
	const GB = 'GB';
	const NO = 'NO';
	const SE = 'SE';
	
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray($empty = false)
    {
		return array(
						array('value' => self::EZ, 'label' => Mage::helper('nowshop')->__('Eurozone')),
						array('value' => self::DK, 'label' => Mage::helper('nowshop')->__('Denmark')),
						array('value' => self::NO, 'label' => Mage::helper('nowshop')->__('Nowrway')),
						array('value' => self::SE, 'label' => Mage::helper('nowshop')->__('Sweden')),
						array('value' => self::GB, 'label' => Mage::helper('nowshop')->__('United Kingdom')),
					);
    }
}