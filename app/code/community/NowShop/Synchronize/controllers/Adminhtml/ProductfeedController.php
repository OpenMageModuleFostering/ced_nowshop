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
 
class NowShop_Synchronize_Adminhtml_ProductfeedController extends Mage_Adminhtml_Controller_Action {
	
	const EXCULDED_ATTRIBUTES = 'name,description,price,short_description,sku,special_price,fixed_special_price,image,small_image,thumbnail,media_gallery,options_container';
	protected $_ndebug = null;
	protected $_storeId = null;
	protected $_colors = null;
	protected $_sizes = null;
	
	/**
	 * get allowed color attributes
	 */
	public function getColors() {
		if(empty($this->_colors))
			$this->_colors = explode(',',Mage::getStoreConfig('nowshop/productfeed/color',$this->_storeId));
		return $this->_colors;
	}
	
	/**
	 * get allowed size attributes
	 */
	public function getSizes() {
		if(empty($this->_sizes))
			$this->_sizes = explode(',', Mage::getStoreConfig('nowshop/productfeed/size',$this->_storeId));
		return $this->_sizes;
	}
	
	/**
     * Initialize titles, navigation
     * @return NowShop_Synchronize_Adminhtml_ProductFeedController
     */
    protected function _initAction()
    {
        $this->_title($this->__('Sell On NowShop'));
        $this->loadLayout()
            ->_setActiveMenu('nowshop')
            ->_addBreadcrumb($this->__('Sell On NowShop'),$this->__('Sell On NowShop'));
        return $this;
    }
	
	public function indexAction(){
		$this->_initAction()
            ->renderLayout();
	}
	
	public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
    }
	
	public function massStatusAction()
    {
		$this->_ndebug = Mage::getStoreConfig('nowshop/setting/debug');
		$productIds = $this->getRequest()->getParam('product');
		if(!is_array($productIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select item(s)'));
        } else {
            try {
			
				$feed = Mage::getModel('nowshop/feed')->getCollection()
												  ->addFieldToFilter('product_id',array('in'=>$productIds));
				$feed->getSelect()->columns(
													array('product_ids' => new Zend_Db_Expr(
														"IFNULL(GROUP_CONCAT(DISTINCT main_table.product_id SEPARATOR ','), '')"
												)));
				$feed = $feed->getFirstItem()->load()->getData('product_ids');
				$feed = explode(',',$feed);
				
				$relistedIds = array_intersect($productIds,$feed);
				/* print_r($relistedIds);die; */
				/* $productIds = array_diff($productIds,$feed); */
				
				$allowedCategoryIds = Mage::getModel('nowshop/cfeed')->getCollection()->addFieldToFilter('status',1);
				$allowedCategoryIds->getSelect()->columns(
											array('category_ids' => new Zend_Db_Expr(
												"IFNULL(GROUP_CONCAT(DISTINCT main_table.category_id SEPARATOR ','), '')"
												)));
				$allowedCategoryIds = $allowedCategoryIds->getFirstItem()->load()->getData('category_ids');
				$allowedCategoryIds = explode(',',$allowedCategoryIds);

				if($this->getRequest()->getParam('onnowshop') == 'add' || $this->getRequest()->getParam('onnowshop') == 'adds'){					
					
					$loadedcollection = $this->getLayout()->getBlockSingleton("nowshop/adminhtml_feed_grid")->manualInit();
					$store = $loadedcollection->getStore();
					$this->_storeId = $store->getId();
					$currency = $store->getBaseCurrency()->getCode();
					$taxHelper = Mage::helper('tax');
					$isvatincluded = (int)$taxHelper->priceIncludesTax();
					$baseurl = Mage::getBaseUrl();
					$helper = Mage::helper('nowshop');
					$brand = '';
					$brandAttr = Mage::getStoreConfig('nowshop/productfeed/brand',$store);
					if($brandAttr == NowShop_Synchronize_Model_System_Config_Source_Brand::BY_DEFAULT_BRAND){
						$brand = Mage::getStoreConfig('general/store_information/name',$store);
					}
					$minDay = Mage::getStoreConfig('nowshop/productfeed/min',$store->getId());
					$maxDay = Mage::getStoreConfig('nowshop/productfeed/max',$store->getId());
					if(count($productIds)){
						$data = array();
						$infoData = array();
						$feedData = array();
						$sortOrder = array();
						$existProductCollection = Mage::getModel('nowshop/feed')->getCollection()
																  ->addFieldToSelect('product_xml_data')
																  ->addFieldToFilter('product_id',array('nin'=>$relistedIds))
																  ->addFieldToFilter('status',array('eq'=>1));
						foreach($existProductCollection as $existProduct){
							$temData = array();
							$tempData = json_decode($existProduct->getData('product_xml_data'),true);
							if($tempData && is_array($tempData) && count($tempData) > 0 && isset($tempData['sku']) && $tempData['sku']) {
								/* $data['product'][] = $tempData; */
								if(isset($tempData['parentsku'])) {
									if(!isset($sortOrder[$tempData['parentsku']][0]))
										$sortOrder[$tempData['parentsku']][0] = array();
									$sortOrder[$tempData['parentsku']][] = $tempData;
								} elseif(!isset($tempData['parentsku']) && $tempData['type']=='master') {
									$sortOrder[$tempData['sku']][0] = $tempData;
								} elseif($tempData && is_array($tempData) && count($tempData) > 0 && isset($tempData['sku']) && $tempData['sku']) {
									$data['product'][] = $tempData;
								}
							}
						}
						
						foreach($productIds as $productId){
							
							$product = Mage::getModel('catalog/product')->load($productId);
							$brandValue = '';
							if($product->getStatus()==1) {	
								$stockItem = $product->getStockItem();
								$allowedQty = Mage::getStoreConfig('nowshop/productfeed/qty',$store->getId());
								if($allowedQty >= 0){
									$qty = (int)$allowedQty;
								}else{
									$qty = (int)$stockItem->getQty();
								}
								$info = array(
											'sku'   => $product->getSku(),
											'type'  => $helper->getAttribute('type',$product->getTypeId()),
											'title' => $product->getName(),
											'description' => array('@cdata'=>$helper->getAttribute('description',$product->getDescription())),
											'url'	  => $baseurl.$product->getUrlPath(),
										);
								if(strlen($brand)){
									$info['brand'] = $brand;
								}elseif(strlen($brandAttr) && $brandValue = $product->getData($brandAttr)){
									$info['brand'] = $brandValue;
								}else{
									$info['brand'] = Mage::getStoreConfig('general/store_information/name',$store);
								}
								
								if($product->getWeight())
									$info['weight']  = $product->getWeight();
								
							
								$info['shipping'] = array(
														'@attributes' => array(
																			'min' => (int)$minDay,
																			'max' => (int)$maxDay,
																		)
													);
								
								if($product->getData('ean'))
									$info['ean'] = $product->getData('ean');
								if($product->getData('isbn10'))
									$info['isbn10'] = $product->getData('isbn10');
								if($product->getData('isbn13'))
									$info['isbn13'] = $product->getData('isbn13');
								$info['pricing'] = array(
														'@attributes' => array(
																			'currency' => $currency,
																			'isvatincluded' => $isvatincluded,
																		),
														'price' => $product->getFinalPrice(),
														'originalprice' => $product->getPrice(),
													);
								if($info['type']!='master'){
									$info['stock'] = array('quantity'=> $qty * $stockItem->getIsInStock());
									$info['stock']['enablestocklimitation'] = $stockItem->getManageStock();
								}else{
									$info['stock'] = array('quantity'=> '1');
								}
								if($images = $product->getMediaGalleryImages()){
									foreach($images as $image){
										$info['images']['image'][] =  array('@attributes' => 
																				array('url' => $image->getUrl())
																	  );
									}
								}
								$parents = array();
								$variant = array();
								$variants = array();
								if($info['type']=='simple'){
									$parents = $this->getParent($product->getId());
									if(count($parents)>0){
										$info['type'] = 'variant';
										$parentsku = array();
										$parentsku = $this->getParent($product->getId(),'sku');
										$info['parentsku'] = $parentsku['sku'];
										$variants = $this->getOptions($product, $parents);
									}
								}elseif($info['type']=='master'){
									$variants = $this->getOptions($product);
								}
								if(count($variants)>0){
									
									foreach($variants as $name=>$value){
										if($value!=''){
											$variant[] = array('@attributes' => array(
																			'name' => $name,
																),
																'@value' => $value,
														);
										}else{
											$variant[] = array('@attributes' => array(
																			'name' => $name,
																),
														);
										}
									}
									$info['variants']['variant'] = $variant;
								}									
								$attributes = $this->attributes($product->getAttributeSetId());
								$EXCULDED_ATTRIBUTES = explode(',',self::EXCULDED_ATTRIBUTES);
								foreach($attributes as $attribute){
									if(!in_array($attribute->getData('attribute_code'),$EXCULDED_ATTRIBUTES) && ($value = $product->getData($attribute->getData('attribute_code'))) && $attribute->getData('frontend_label')){
										if($attribute->usesSource()) {
											$value = $product->getAttributeText($attribute->getData('attribute_code'));
										}
										$info['attributes']['attribute'][] = array('@attributes' => array(
																								'name' => $attribute->getData('frontend_label'),
																					),
																				'@value'=> $helper->getAttribute('description',$value),
																				);
									}
								}
								if($categoryIds = $product->getCategoryIds()) {
									foreach($categoryIds as $id){
										if(in_array($id,$allowedCategoryIds)){
											$info['categories']['category'][] = array('@attributes' => array(
																									'id' => $id,
																						),
																					);
										}
									}
								}
								if(!in_array($productId,$relistedIds)){
									$feedData[] = array('product_id'=>$productId,'status'=>1, 'updated_at'=>date('Y-m-d H:i:s',time()), 'product_xml_data'=>json_encode($info));
								} else {
									$infoData[$product->getId()] = json_encode($info);
								}
								
								if(isset($info['parentsku'])) {
									if(!isset($sortOrder[$info['parentsku']][0]))
										$sortOrder[$info['parentsku']][0] = array();
									$sortOrder[$info['parentsku']][] = $info;
								} elseif(!isset($info['parentsku']) && $info['type']=='master') {
									$sortOrder[$info['sku']][0] = $info;
								} elseif($info && is_array($info) && count($info) > 0 && isset($info['sku']) && $info['sku']) {
									$data['product'][] = $info;
								}
							} else {
								$relistedIds = array_values(array_diff($relistedIds,array($product->getId())));
								$productIds = array_values(array_diff($productIds,array($product->getId())));
							}
						}
						if(count($sortOrder)) {
							foreach($sortOrder as $parentSku=>$sortedValue){
								if(is_array($sortedValue) && count($sortedValue) >1) {
									foreach($sortedValue as $key=>$value) {
										if(is_array($value) && count($value) > 0) {
											$data['product'][] = $value;
										} else {
											break;
										}
									}
								}
							}
						}
						
						/* print_r($data['product']);die; */
						$xml = NowShop_Synchronize_Helper_Arraytoxml::createXML('products', $data);
						/* header('Content-type: application/xml'); */
						/* echo $xml->saveXML();die; */
						$arraytoxml = Mage::helper('nowshop/arraytoxml');
						$schema = $arraytoxml->getSchema();
						if($xml->schemaValidate($schema)){
							$diff = 0;
							$fileName = $arraytoxml->formatedFileName('product',$diff);
							if($xml->save($fileName) && $this->setListed($fileName,$feedData) && $this->setReListed($relistedIds,$infoData)){
								if($this->getRequest()->getParam('onnowshop') == 'adds'){
									echo $this->__('Total of %d product(s) were successfully queued for listing on NowShop.', count($productIds)).'<br/>';
									echo $this->__("It will take atmost one day to list.");
									echo '<a href="'.Mage::helper('adminhtml')->getUrl('*/*/index',array('_secure'=>true)).'">Return to Admin</a><br/>';
									echo '<iframe src="'.Mage::getBaseUrl('media',array('_secure'=>true)).'nowshop/productfeed/'.basename($fileName).'"></iframe>';
								}else{
									$this->_getSession()->addSuccess(
										$this->__('Total of %d product(s) were successfully queued for listing on NowShop.', count($productIds))
									);
									$this->_getSession()->addSuccess(
										$this->__("It will take atmost one day to list.").'To See Feed Click <a target="_blank" href="'.Mage::getBaseUrl('media',array('_secure'=>true)).'nowshop/productfeed/'.basename($fileName).'">Here</a>'
									);
								}
							}else{
								$this->_getSession()->addError(
									$this->__('Total of %d product(s) were failed to queued.Please try again later', count($productIds))
								);
							}
						}else{
							$this->_getSession()->addError(
								$this->__('Total of %d product(s) were failed to queued.', count($productIds))
							);
							$this->_getSession()->addError(
								$this->__('The product feed content must according to <a target="_blank" href="https://nowshop.com/retailer/setup/products">NowShop Product Feed specification</a>')
							);
							$this->_getSession()->addError(
								$this->__('An XML file you export for NowShop must be valid against this <a href="https://nowshop.com/product_feed.xsd">XML Schema</a>')
							);
							
						}
					}else{
						Mage::getSingleton('adminhtml/session')->addError($this->__('Selected item(s) are already in feed.Please try different products.'));
					}	
				} elseif($this->getRequest()->getParam('onnowshop') == 'remove') {
					/* foreach ($productIds as $id) {
						$product = Mage::getSingleton('nowshop/feed')
										->loadByProductId($id)
										->save();
			 
					} */
					$this->_getSession()->addSuccess(
						$this->__('Total of %d product(s) were successfully removed form listing queue.', count($productIds))
					);
				} else {
					$this->_getSession()->addError(
						$this->__('No action specified.')
					);
				}
            } catch (Exception $e) {
				if($this->_ndebug){
					$this->_getSession()->addError($e->getMessage());
				}else{
					$this->_getSession()->addError('Error in processing.');
				}
            }
        }
		if($this->getRequest()->getParam('onnowshop') != 'adds'){
			$this->_redirect('*/*/index',array('_secure'=>true));
		}
    }

    /**
     * ACL check
     * @return bool
     */
    protected function _isAllowed()
    {
        switch ($this->getRequest()->getActionName()) {
            case 'index':
            case 'grid':
                return Mage::getSingleton('admin/session')->isAllowed('nowshop/productfeed/view');
                break;
            case 'massStatus':
                return Mage::getSingleton('admin/session')->isAllowed('nowshop/productfeed/list');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('nowshop/productfeed');
                break;
        }
    }
	
	protected function getParent($productId = 0,$return = 'ids'){
		$parentIds = array();
		/* Check configurable */
		$parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);
		
		/* Check bundle */
		/* if(count($parentIds)==0){
			$parentIds = Mage::getModel('bundle/product_type')->getParentIdsByChild($productId);
		} */

		/* Check grouped */
		/* if(count($parentIds)==0){
			$parentIds = Mage::getResourceSingleton('catalog/product_type_grouped')->getParentIdsByChild($productId);
		} */
		if($return == 'sku'){
			$collection = Mage::getModel('catalog/product')->getCollection()
														   ->addAttributeToSelect('sku')
														   ->addAttributeToFilter('entity_id',$parentIds[0]);
			$parentIds = $collection->getFirstItem()->getData();
		}
		return $parentIds;
	}
	
	protected function getOptions($product, $parentIds = array()){
		$attributeValues = array();
		$noColorAttr = true;
		$noSizeAttr = true;
		if($product->getTypeId()=='simple'){
			$productType = '';
			$productId = 0;
			$parent = array();
			foreach($parentIds as $id){
				$parentProduct = Mage::getModel('catalog/product')->load($id);
				if(1 || $parentProduct->isSaleable()){
					$productType = $parentProduct->getTypeId();
					$productId = $parentProduct->getId();
					$parent = $parentProduct;
					break;
				}
			}
		}elseif($product->getTypeId()=='configurable'){
			$productType = 'configurable';
			$productId = $product->getId();
			$parent = $product;
		}
		/* print_r($this->getColors());
		echo "<hr>";
		print_r($this->getSizes());
		die; */
		if($productType!=''){
			switch($productType){
				case 'configurable' : foreach ($parent->getTypeInstance()->getConfigurableAttributes() as $attribute){
										$attrCode = $attribute->getProductAttribute()->getAttributeCode();
										if(in_array($attribute->getProductAttribute()->getAttributeCode(),$this->getColors())) {
											$attrCode ='Color';
											$noColorAttr = false;
										} elseif(in_array($attribute->getProductAttribute()->getAttributeCode(),$this->getSizes())) {
										 	$attrCode ='Size';
											$noSizeAttr = false;
										}
										
										if(count($parentIds)>0) {
											$attributeValues[$attrCode] = $product->getAttributeText($attribute->getProductAttribute()->getAttributeCode());
										} else {
											$attributeValues[$attrCode] = '';
										}
									  }
									  break;
			}
		}
		if ($noColorAttr) {
			if(count($parentIds)>0){
				$attributeValues['Color'] = 'One Color';
			} else {
				$attributeValues['Color'] = '';
			}
		}
		if ($noSizeAttr) {
			if(count($parentIds)>0){
				$attributeValues['Size'] = 'One Size';
			} else {
				$attributeValues['Size'] = '';
			}
		}
		return $attributeValues;
	}
	
	protected function setListed($fileName,$feedData = array()) {
		if(Mage::helper('nowshop')->uploadViaFtp($fileName)) {
			$coreResource   = Mage::getSingleton('core/resource');
			$feedTable      = $coreResource->getTableName('nowshop/feed');
			$conn = $coreResource->getConnection('write');
			if($conn->insertMultiple($feedTable, $feedData))
				return true;
		}
		return false;
	}
	
	protected function setReListed($feedData = array(), $infoData = array()){
		if(count($feedData)>0 && count($infoData)>0){
			try {
				foreach($feedData as $productId){
					
					Mage::getModel('nowshop/feed')->loadByProductId($productId)
												  ->setData('product_xml_data',$infoData[$productId])
												  ->setUpdatedAt(date('Y-m-d H:i:s'))
												  ->save();
				}
				return true;
			} catch (Exception $e) {
				return false;
			}
		}
		return true;
	}
	
	protected function attributes($setId) {
		$attributeCollection = Mage::getResourceModel('eav/entity_attribute_collection');
		$attributeCollection
		->setAttributeSetFilter($setId)
		->addSetInfo()
		->getData();
		return $attributeCollection;
	}
}