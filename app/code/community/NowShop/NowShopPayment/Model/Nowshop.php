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
 * @package     NowShop_NowShopPayment
 * @author 		Asheesh Singh<asheeshsingh@cedcoss.com>
 * @copyright   Copyright NowShop 2012 - 2014 (http://nowshop.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class NowShop_NowShopPayment_Model_Nowshop extends Mage_Payment_Model_Method_Abstract {

	protected $_code = 'nowshop';
	protected $_canAuthorize = true;
	protected $_canCancelInvoice = false;
	protected $_canCapture = false;
	protected $_canCapturePartial = false;
	protected $_canCreateBillingAgreement = false;
	protected $_canFetchTransactionInfo = false;
	protected $_canManageRecurringProfiles = false;
	protected $_canOrder = false;
	protected $_canRefund = false;
	protected $_canRefundInvoicePartial = false;
	protected $_canReviewPayment = false;
	/* Setting for disable from front-end. */
	/* START */
	protected $_canUseCheckout = false;
	protected $_canUseForMultishipping = false;
	protected $_canUseInternal = true; 
	protected $_canVoid = false;
	protected $_isGateway = false;
	protected $_isInitializeNeeded = false;
	
	/* END */

	/**
	 * Check whether payment method can be used
	 * @param Mage_Sales_Model_Quote
	 * @return bool
	 */
	public function isAvailable($quote = null) {
		return true;
	}
	
	public function getCode(){
		return $this->_code;
	}

}