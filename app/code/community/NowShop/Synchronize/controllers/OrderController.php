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
 * @author 		Cedcoss core team.
 * @copyright   Copyright NowShop 2012 - 2014 (http://nowshop.com/)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
 
class NowShop_Synchronize_OrderController extends Mage_Core_Controller_Front_Action {
	
	protected $_storeId = null;
	protected $_data = null;
	protected $_priceTotal = null;
	protected $_rowTotal = null;
	protected $_shipPrice = null;
	protected $_currencyCode = null;
	protected $_currencyRates = null;
	protected $_subTotal = 0.00;
	protected $_nresponse = null;
	protected $_debug = null;
	protected $_rawdata = null;
	
	private function getDigest($body = ''){
		$relatedUri = isset($this->_data['server']['REQUEST_URI'])?$this->_data['server']['REQUEST_URI']:'';
		$sharedKey = Mage::getStoreConfig('nowshop/order_api/key');
		$string = $relatedUri.$body;
		return Mage::getStoreConfig('nowshop/order_api/email').":".hash_hmac('sha256', $string, $sharedKey);
	}
	public function predispatch(){
		$fp = fopen('php://input', 'r');
		$this->_rawdata = stream_get_contents($fp);
		$request = json_decode($this->_rawdata,true); 
		$this->_data = $request;
		$this->_data['server'] = $_SERVER;
		if(!isset($this->_data['id'])){
			$this->_data['id'] = isset($_REQUEST['id'])?$_REQUEST['id']:'';
			$this->_data['externalid'] = isset($_REQUEST['externalid'])?$_REQUEST['externalid']:'';
			$this->_data['status'] = isset($_REQUEST['status'])?$_REQUEST['status']:'';
		}
		$this->_debug = Mage::getStoreConfig('nowshop/setting/debug');
		if($this->_debug){
			Mage::log('Request structure{{'.print_r($this->_data,true).'}}', null, 'nowshop.log');
		}
		if(isset($this->_data['server']['HTTP_X_NOWSHOP_AUTHENTICATION'])){
			if($this->_data['server']['HTTP_X_NOWSHOP_AUTHENTICATION'] != $this->getDigest($this->_rawdata)){
				header('HTTP/1.1 401 Unauthorized');
				if($this->_debug){
					$this->_nresponse['id'] 		 = $this->_data['id'];
					$this->_nresponse['externalid']  = isset($this->_data['externalid'])?$this->_data['externalid']:'';
					$this->_nresponse['status'] 	 = $this->__('HTTP_X_NOWSHOP_AUTHENTICATION not matched!');	
				}
				echo $this->prepareResponse();die;
			}
			header('Content-Type: application/json; charset=UTF-8');
			if($this->getRequest()->getActionName() == 'create'){
				if(isset($this->_data['pricing']['shipping']['total'])){
					$this->_shipPrice = $this->_data['pricing']['shipping']['total'];
					if(isset($this->_data['pricing']['shipping']['vat']))
						$this->_shipPrice += $this->_data['pricing']['shipping']['vat'];		
				}else{
					$this->_shipPrice = 0.00;
				}
				$this->_currencyCode = isset($this->_data['pricing']['currency'])?(string)$this->_data['pricing']['currency']:(string)Mage::app()->getStore()->getCurrentCurrencyCode();
			}

				
		}else{
			header("HTTP/1.1 403 Forbidden");
			if($this->_debug){
				$this->_nresponse['id'] 		 = $this->_data['id'];
				$this->_nresponse['externalid']  = isset($this->_data['externalid'])?$this->_data['externalid']:'';
				$this->_nresponse['status'] 	 = $this->__('HTTP_X_NOWSHOP_AUTHENTICATION not found!');	
			}
			echo $this->prepareResponse();die;
		}
	}
	
	public function getStoreId(){
		if(empty($this->_storeId))
			$this->_storeId = Mage::app()->getStore()->getId();
		return $this->_storeId;
	}
	
	protected function _initProduct($item = array()){
		$productSku = isset($item['sku'])?$item['sku']:'';
		$product = Mage::getModel('catalog/product');
		if(strlen($productSku))
			$product = $product->loadByAttribute('sku',$productSku,'*');
		return $product;
	}
	
	protected function _initOrder(){
		$storeId = $this->getStoreId();
		$reservedOrderId = Mage::getSingleton('eav/config')->getEntityType('order')->fetchNewIncrementId($storeId);    
		$baseCurrencyCode = Mage::app()->getBaseCurrencyCode();
		$allowedCurrencies = Mage::getModel('directory/currency')->getConfigAllowCurrencies(); 
		$rates = Mage::getModel('directory/currency')->getCurrencyRates($baseCurrencyCode, array_values($allowedCurrencies));

		$this->_currencyRates = $rates;
		$order = Mage::getModel('sales/order')
				->setIncrementId($reservedOrderId)
				->setStoreId($storeId)
				->setQuoteId(0)
				->setIsVirtual(0)
				->setRemoteIp($_SERVER['REMOTE_ADDR'])
				->setGlobalCurrencyCode($baseCurrencyCode)
				->setBaseCurrencyCode($baseCurrencyCode)
				->setStoreCurrencyCode($baseCurrencyCode)
				->setOrderCurrencyCode($this->_currencyCode)
				->setBaseToGlobalRate(isset($rates[$baseCurrencyCode])?$rates[$baseCurrencyCode]:1)
				->setBaseToOrderRate(isset($rates[$this->_currencyCode])?$rates[$this->_currencyCode]:1)
				->setStoreToBaseRate(isset($rates[$baseCurrencyCode])?$rates[$baseCurrencyCode]:1)
				->setStoreToOrderRate(isset($rates[$this->_currencyCode])?$rates[$this->_currencyCode]:1);
		if(isset($this->_data['created']) && $this->_data['created']){
			$date = '';
			$date = date('Y-m-d h:i:s',strtotime($this->_data['created']));
			$order->setCreatedAt($date);
		}
		return $order;
	}
	
	protected function _addToCart($item = array()){
		$storeId = $this->getStoreId();
		$product = $this->_initProduct($item);
		if(!is_object($product))
			return false;
		$this->_priceTotal = 0.00;
		$this->_rowTotal   = 0.00;
		$vat			   = 0;
		if(isset($item['unitprice'])){
			$this->_priceTotal = isset($item['unitprice']['total'])?$item['unitprice']['total']:$product->getFinalPrice();
			if(isset($item['unitprice']['vat']) && $item['unitprice']['vat']){
				$this->_priceTotal -= $item['unitprice']['vat'];
			}
		}else{
			$this->_priceTotal = $product->getFinalPrice();
		}
		
		$qty = $item['quantity']?$item['quantity']:1;
		
		if(isset($item['totalprice'])){
			$this->_rowTotal = isset($item['totalprice']['total'])?$item['totalprice']['total']:($product->getFinalPrice() * $qty);
			if(isset($item['totalprice']['vat']) && $item['totalprice']['vat']){
				$this->_rowTotal -= $item['totalprice']['vat'];
			} 
		}else{
			$this->_rowTotal = $product->getFinalPrice() * $qty;
		}
		
		$orderItem = Mage::getModel('sales/order_item')
					->setStoreId($storeId)
					->setQuoteItemId(0)
					->setQuoteParentItemId(NULL)
					->setProductId($product->getId())
					->setProductType($product->getTypeId())
					->setWeight($product->getWeight())
					->setIsVirtual(0)
					->setIsQtyDecimal(0)
					->setQtyBackordered(NULL)
					->setTotalQtyOrdered($qty)
					->setQtyOrdered($qty)
					->setName($product->getName())
					->setSku($product->getSku())
					->setPriceInclTax($this->_priceTotal)
					->setBasePriceInclTax($this->_priceTotal/$this->_currencyRates[$this->_currencyCode])
					->setPrice($this->_priceTotal)
					->setBasePrice($this->_priceTotal/$this->_currencyRates[$this->_currencyCode])
					->setOriginalPrice($this->_priceTotal)
					->setBaseOriginalPrice($this->_priceTotal/$this->_currencyRates[$this->_currencyCode])
					->setRowTotalInclTax($this->_rowTotal)
					->setBaseRowTotalInclTax($this->_rowTotal/$this->_currencyRates[$this->_currencyCode])
					->setRowTotal($this->_rowTotal)
					->setBaseRowTotal($this->_rowTotal/$this->_currencyRates[$this->_currencyCode]);
		$this->_subTotal += $this->_rowTotal;
		
		if(isset($item['totalprice']['vat']) && $item['totalprice']['vat']){
			$orderItem->setTaxAmount($item['totalprice']['vat'])
					  ->setBaseTaxAmount($item['totalprice']['vat']/$this->_currencyRates[$this->_currencyCode]);
			$this->_subTotal += $item['totalprice']['vat'];
		}
		
		return $orderItem;
	}
	
	protected function _billingAddress(){
		$storeId = $this->getStoreId();
		$billingAddress = Mage::getModel('sales/order_address')
							->setStoreId($storeId)
							->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_BILLING)
							->setFirstname(isset($this->_data['customer']['billing']['firstname'])?$this->_data['customer']['billing']['firstname']:'')
							->setLastname(isset($this->_data['customer']['billing']['lastname'])?$this->_data['customer']['billing']['lastname']:'')
							->setEmail(isset($this->_data['customer']['billing']['email'])?$this->_data['customer']['billing']['email']:'')
							->setStreet(isset($this->_data['customer']['billing']['address'])?$this->_data['customer']['billing']['address']:'')
							->setCity(isset($this->_data['customer']['billing']['city'])?$this->_data['customer']['billing']['city']:'')
							->setCountryId(isset($this->_data['customer']['billing']['country']['iso'])?$this->_data['customer']['billing']['country']['iso']:Mage::getStoreConfig('general/country/default',$storeId))
							/* ->setRegion($billing->getRegion()) */
							->setTelephone(isset($this->_data['customer']['billing']['telephone'])?$this->_data['customer']['billing']['telephone']:'')
							/* ->setRegion_id($this->_data['region_id']) */
							->setPostcode(isset($this->_data['customer']['billing']['postalcode'])?$this->_data['customer']['billing']['postalcode']:'');
		return $billingAddress;
	}
	
	protected function _shippingAddress(){
		$storeId = $this->getStoreId();
		$shippingAddress = Mage::getModel('sales/order_address')
							->setStoreId($storeId)
							->setAddressType(Mage_Sales_Model_Quote_Address::TYPE_SHIPPING)
							->setFirstname(isset($this->_data['customer']['shipping']['name'])?$this->_data['customer']['shipping']['name']:'')
							->setLastname('')
							->setEmail(isset($this->_data['customer']['billing']['email'])?$this->_data['customer']['billing']['email']:'')
							->setStreet(isset($this->_data['customer']['shipping']['address'])?$this->_data['customer']['shipping']['address']:'')
							->setCity(isset($this->_data['customer']['shipping']['city'])?$this->_data['customer']['shipping']['city']:'')
							->setCountryId(isset($this->_data['customer']['shipping']['country']['iso'])?$this->_data['customer']['shipping']['country']['iso']:Mage::getStoreConfig('general/country/default',$storeId))
							/* ->setRegion($billing->getRegion()) */
							->setTelephone(isset($this->_data['customer']['billing']['telephone'])?$this->_data['customer']['billing']['telephone']:'')
							/* ->setRegion_id($this->_data['region_id']) */
							->setPostcode(isset($this->_data['customer']['shipping']['postalcode'])?$this->_data['customer']['shipping']['postalcode']:'');
		return $shippingAddress;
	}
	
	protected function _setPayment(){
		$code = Mage::getModel('nowshoppayment/nowshop')->getCode();
		$orderPayment = Mage::getModel('sales/order_payment')
						->setStoreId($storeId)
						->setMethod($code);
		return $orderPayment;
	}
	
	/**
     * Check if customer email exists
     *
     * @param string $email
     * @param int $websiteId
     * @return false|Mage_Customer_Model_Customer
     */
    protected function _customerEmailExists($email)
    {
		$websiteId = Mage::app()->getWebsite()->getId();
        $customer = Mage::getModel('customer/customer');
 
		if ($websiteId) {
            $customer->setWebsiteId($websiteId);
        }
        $customer->loadByEmail($email);
		if ($customer->getId()) {
            return $customer;
        }
        return false;
    }
	
	public function createAction(){
		if($this->_debug){
			Mage::log('Create Request structure{{'.print_r($this->_data,true).'}}', null, 'nowshop.log');
		}
		try{
			$order = $this->_initOrder();
			$storeId = $this->getStoreId();
			$transaction = Mage::getModel('core/resource_transaction');		
			
			/* Step 1: */
				/* Add To Cart */
				$totalQty = 0;
				if(isset($this->_data['items']) && count($this->_data['items'])>0){
					foreach($this->_data['items'] as $item){
						$orderItem = array();
						$orderItem = $this->_addToCart($item);
						if($orderItem === false)
							continue;
						if($orderItem){
							$totalQty += $orderItem->getTotalQtyOrdered();
							$order->addItem($orderItem);
						}
					}
				}
				/* Validate order's items */
				if(!count($order->getAllItems())){
					if($this->_debug){
						echo $this->__('Products SKU not matched!');die;
					}else{
						echo $this->prepareResponse();die;
					}
				}else{
					$order->setTotalQtyOrdered($totalQty);
				}
			
			/* Step 2: */
				/* set Customer data */
				if($customer = $this->_customerEmailExists($this->_data['customer']['billing']['email'])){
					/* print_r($customer->getData());die; */
					$order->setCustomerIsGuest(0);
					$order->setCustomerId($customer->getId());
					$order->setCustomerGroupId($customer->getGroupId());
					$order->setCustomerEmail($customer->getEmail());
					$order->setCustomerFirstname($customer->getFirstname());
					$order->setCustomerLastname($customer->getLastname());
				}else{
					$order->setCustomerIsGuest(1);
					$order->setCustomerEmail(isset($this->_data['customer']['billing']['email'])?$this->_data['customer']['billing']['email']:'');
					$order->setCustomerFirstname(isset($this->_data['customer']['billing']['firstname'])?$this->_data['customer']['billing']['firstname']:'');
					$order->setCustomerLastname(isset($this->_data['customer']['billing']['lastname'])?$this->_data['customer']['billing']['lastname']:'');
				}
				
				/* echo $order->getCustomerIsGuest()."==".$order->getCustomerGroupId();die; */
			/* Step 3: */
				/* set Billing Address*/
				$billingAddress = $this->_billingAddress();
				$order->setBillingAddress($billingAddress);
				
			/* Step 4: */
				/* set shipping address */
				$shippingAddress = $this->_shippingAddress();
				$order->setShippingAddress($shippingAddress);
				
			/* Step 5: */
				/* Set Shipping information */
				$code = Mage::getModel('nowshopshipping/carrier_nowshop')->getCode();
				$order->setShippingAmount($this->_shipPrice)
					  ->setBaseShippingAmount($this->_shipPrice/$this->_currencyRates[$this->_currencyCode])
					  ->setBaseShippingTaxAmount(0.0000)
					  ->setBaseShippingDiscountAmount(0.0000)
					  ->setShippingMethod($code)
					  ->setShippingDescription(Mage::getStoreConfig('carriers/nowshop/title',$storeId));

			/* Step 6: */
				/* set payment information */
				$orderPayment = $this->_setPayment();
				$order->setPayment($orderPayment);
				
			/* Step 7: */
				/* set Grand total*/
				
				$grandtotal = $this->_subTotal+$this->_shipPrice;
				$order->setBaseDiscountAmount(0.0000)
					  ->setBaseTaxAmount(0.0000)
					  ->setTaxAmount(0.0000)
					  ->setBaseSubtotal($this->_subTotal/$this->_currencyRates[$this->_currencyCode])
					  ->setBaseSubtotalInclTax($this->_subTotal/$this->_currencyRates[$this->_currencyCode])
					  ->setSubtotalInclTax($this->_subTotal)
					  ->setSubtotal($this->_subTotal)
					  ->setBaseGrandTotal($grandtotal/$this->_currencyRates[$this->_currencyCode])
					  ->setGrandTotal($grandtotal);

			/* Step 8: */
				/* Order Place start */
					$transaction->addObject($order);
					$transaction->addCommitCallback(array($order, 'place'));
					$transaction->addCommitCallback(array($order, 'save'));
					$transaction->save();
			/* Step 9: */	
				/* Generate Invoice depend upon a setting from admin */
				/* Start */
				if(Mage::getStoreConfig('nowshop/order_api/can_invoice',$order->getStoreId())){
					if($order->canInvoice()){
						$invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice();
						if ($invoice->getTotalQty()) {
							$invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
							$invoice->register();
							$invoice->getOrder()->setIsInProcess(true);
							$transactionSave = Mage::getModel('core/resource_transaction')
													->addObject($invoice)
													->addObject($invoice->getOrder());
							$transactionSave->save();
							if($order->getStatus() == 'pending'){
								$order->setStatus('processing')
									  ->setState('processing');
							}
						}
					}
					$order = $invoice->getOrder();
				}
				/* End */
				
			/* Step 10 */
				/* Send Order confirmation mail */
					if(Mage::getStoreConfig('nowshop/order_api/can_email',$order->getStoreId())){
						$order->sendNewOrderEmail();
						$order->setEmailSent(true);	
					}
					$order->save();
			
			/* Step 11 */
				/* Prepare and send response */
					$this->_nresponse['id'] 			= $this->_data['id'];
					$this->_nresponse['externalid']  = $order->getIncrementId();
					$this->_nresponse['status'] 		= Mage::helper('nowshop')->getOrderStatus($order->getStatus());
					$body = $this->prepareResponse();
					$digest = $this->getDigest($body);
					header('HTTP/1.1 201 OK');
					header('X-NowShop-Authentication:'.$digest);
					echo $body;
					if($this->_debug){
						Mage::log('Response of Create Order structure{{'.$body.'}}', null, 'nowshop.log');
					}
		}catch(Exception $e){
			header('HTTP/1.1 500 Internal Server Error');
			if($this->_debug){
				$this->_nresponse['id'] 		 = $this->_data['id'];
				$this->_nresponse['externalid']  = isset($this->_data['externalid'])?$this->_data['externalid']:'';
				$this->_nresponse['status'] 	 = $e->getMessage();	
			}
			echo $this->prepareResponse();die;
		}
	}
	
	public function infoAction(){
		try{
			$response = array();
			if($this->_debug){
				Mage::log('Info Request structure{{'.print_r($this->_data,true).'}}', null, 'nowshop.log');
			}
			$id = isset($this->_data['id'])?$this->_data['id']:0;
			$orderId = isset($this->_data['externalid'])?$this->_data['externalid']:0;

			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			if($order && $order->getId()){
				$response['id'] = $id;
				$response['status'] = $order->getStatus();
				$response['items']  = array();
				foreach($order->getAllItems() as $item){
					if($order->getStatus() == 'pending'){
						$response['items'][] = array('sku'=>$item->getData('sku'),'quantity'=>(int)$item->getData('qty_ordered'));	
					}elseif($response['status'] == 'processing'){
						$response['items'][] = array('sku'=>$item->getData('sku'),'quantity'=>(int)$item->getData('qty_invoiced'));
					}elseif($response['status'] == 'complete'){
						$response['items'][] = array('sku'=>$item->getData('sku'),'quantity'=>(int)$item->getData('qty_shipped'));
					}elseif($response['status'] == 'canceled'){
						$response['items'][] = array('sku'=>$item->getData('sku'),'quantity'=>(int)$item->getData('qty_canceled'));
					}elseif($response['status'] == 'closed'){
						$response['items'][] = array('sku'=>$item->getData('sku'),'quantity'=>(int)$item->getData('qty_refunded'));
					}else{
						$response['items'][] = array('sku'=>$item->getData('sku'),'quantity'=>(int)$item->getData('qty_ordered'));
					}
				}
				$response['status'] = Mage::helper('nowshop')->getOrderStatus($response['status']);
			}
			$body = json_encode($response);
			$digest = $this->getDigest($body);
			header('HTTP/1.1 200 OK');
			header('X-NowShop-Authentication:'.$digest);
			echo $body;
			if($this->_debug){
				Mage::log('Response of Info Order structure{{'.$body.'}}', null, 'nowshop.log');
			}
		}catch(Exception $e){
			header('HTTP/1.1 500 Internal Server Error');
			if($this->_debug){
				$this->_nresponse['id'] 		 = $this->_data['id'];
				$this->_nresponse['externalid']  = isset($this->_data['externalid'])?$this->_data['externalid']:'';
				$this->_nresponse['status'] 	 = $e->getMessage();	
			}
			echo $this->prepareResponse();die;
		}
	}
	
	public function cancelAction(){
		try{
			$response = array();
			if($this->_debug){
				Mage::log('Cancel Request structure{{'.print_r($this->_data,true).'}}', null, 'nowshop.log');
			}
			$id = isset($this->_data['id'])?$this->_data['id']:0;
			$orderId = isset($this->_data['externalid'])?$this->_data['externalid']:0;
			$status = isset($this->_data['status'])?$this->_data['status']:'';
			$order = Mage::getModel('sales/order')->loadByIncrementId($orderId);
			if($order && $order->getId() && $status == 'cancelled'){	
				$response['id'] = $id;
				if($order->canCancel()){
					if($order->cancel()->save()){
						$response['status'] = $status;
					}else{
						$response['status'] = Mage::helper('nowshop')->getOrderStatus($order->getStatus());
					}
				}elseif($order->getStatus() == 'canceled'){
					$response['status'] = $status;
				}else{
					$response['status'] = Mage::helper('nowshop')->getOrderStatus($order->getStatus());
				}
			}
			$body = json_encode($response);
			$digest = $this->getDigest($body);
			header('HTTP/1.1 200 OK');
			header('X-NowShop-Authentication:'.$digest);
			echo $body;
			if($this->_debug){
				Mage::log('Response of Cancel Order structure{{'.$body.'}}', null, 'nowshop.log');
			}
		}catch(Exception $e){
			header('HTTP/1.1 500 Internal Server Error');
			if($this->_debug){
				$this->_nresponse['id'] 		 = $this->_data['id'];
				$this->_nresponse['externalid']  = isset($this->_data['externalid'])?$this->_data['externalid']:'';
				$this->_nresponse['status'] 	 = $e->getMessage();	
			}
			echo $this->prepareResponse();die;
		}
	}
	
	protected function prepareResponse(){
		$result = '';
		if(!empty($this->_nresponse)){
			$result = json_encode($this->_nresponse);
		}else{
			$this->_nresponse['id'] 		 = $this->_data['id'];
			$this->_nresponse['externalid']  = isset($this->_data['externalid'])?$this->_data['externalid']:'';;
			$this->_nresponse['status'] 	 = 'failure';
			$result = json_encode($this->_nresponse);
		}
		return $result;
	}
}