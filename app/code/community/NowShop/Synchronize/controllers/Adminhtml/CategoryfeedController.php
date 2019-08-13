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
 
class NowShop_Synchronize_Adminhtml_CategoryfeedController extends Mage_Adminhtml_Controller_Action {
	
	protected $_ndebug = null;
	protected $_tmpCatg = array();
	protected $_cfeedData = array();
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
	
		$this->_initAction();
		$this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
        $this->renderLayout();
	}
	
	public function saveAction(){

		$this->_ndebug = Mage::getStoreConfig('nowshop/setting/debug');
		$categoryIds = array_unique(explode(',',trim($this->getRequest()->getParam('category_ids'),',')));
		$this->_tmpCatg = $categoryIds;
		if(!is_array($categoryIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select category(s)'));
        } else {
            try {
				if(count($categoryIds)){
					$data = array();
					$this->_cfeedData = array();
					$category = Mage::getModel('catalog/category');
					$tree = $category->getTreeModel();
					$tree->load();
					$ids = $tree->getCollection()->getAllIds();
					foreach($ids as $categoryId){
						if(count($this->_tmpCatg)>0 && in_array($categoryId,$this->_tmpCatg)){
							$this->_tmpCatg = array_diff($this->_tmpCatg,array($categoryId));
							$this->_cfeedData[] = array('category_id'=>$categoryId,'status'=>1, 'updated_at'=>date('Y-m-d h:i:s',time()));
							$category = Mage::getModel('catalog/category')->load($categoryId);
							$info = $this->generateChildren($category,$categoryIds);
							$data['category'][] = $info;	
						}
					}

					$xml = NowShop_Synchronize_Helper_Arraytoxml::createXML('categories', $data);
					
					$arraytoxml = Mage::helper('nowshop/arraytoxml');
					
					$diff = 0;
					$fileName = $arraytoxml->formatedFileName('category',$diff);
					if($xml->save($fileName) && $this->setListed($fileName,$this->_cfeedData)){
						$this->_getSession()->addSuccess(
							$this->__('Total of %d category(s) were successfully queued for listing on NowShop.', count($categoryIds))
						);
						$this->_getSession()->addSuccess(
							$this->__("It will take atmost one day to list.").'To See Feed Click <a target="_blank" href="'.Mage::getBaseUrl('media',array('_secure'=>true)).'nowshop/categoryfeed/'.basename($fileName).'">Here</a>'
						);
					}else{
						$this->_getSession()->addError(
							$this->__('Total of %d category(s) were failed to queued.Please try again later', count($categoryIds))
						);
					}
					
				}else{
					Mage::getSingleton('adminhtml/session')->addError($this->__('Selected item(s) are already in feed.Please try different categorys.'));
				}
					
            } catch (Exception $e) {
				if($this->_ndebug){
					$this->_getSession()->addError($e->getMessage());
				}else{
					$this->_getSession()->addError('Error in processing.');
				}
            }
        }
		$this->_redirect('*/*/index',array('_secure'=>true));
	}
	
	public function categoriesJsonAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('nowshop/adminhtml_feed_categories')
                ->getCategoryChildrenJson($this->getRequest()->getParam('category'))
        );
    }
	
	public function gridAction()
    {
        $this->loadLayout();
        $this->renderLayout();
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
                return Mage::getSingleton('admin/session')->isAllowed('nowshop/categoryfeed/view');
                break;
            case 'save':
                return Mage::getSingleton('admin/session')->isAllowed('nowshop/categoryfeed/list');
                break;
            default:
                return Mage::getSingleton('admin/session')->isAllowed('nowshop/categoryfeed');
                break;
        }
    }
	
	protected function setListed($fileName,$cfeedData = array()){
		if(Mage::helper('nowshop')->uploadViaFtp($fileName)){
			$coreResource   = Mage::getSingleton('core/resource');
			$cfeedTable      = $coreResource->getTableName('nowshop/cfeed');
			$conn = $coreResource->getConnection('write');
			if($conn->query('TRUNCATE TABLE `'.$cfeedTable.'`') && $conn->insertMultiple($cfeedTable, $cfeedData))
				return true;
		}
		return false;
	}
	
	
	protected function generateChildren($category,$categoryIds = array()){
		$info = array(
					'id'   => $category->getId(),
					'name'  => $category->getName()
				);
		if($category->hasChildren()) {
			$children = array();
			$children = explode(',',$category->getChildren());
			if(count($children)>0){
				foreach($children as $child){
					$child = Mage::getModel('catalog/category')->load($child);
					if(is_object($child) && $child->getId() && in_array($child->getId(),$categoryIds)){
						$this->_tmpCatg = array_diff($this->_tmpCatg,array($child->getId()));
						$this->_cfeedData[] = array('category_id'=>$child->getId(),'status'=>1, 'updated_at'=>date('Y-m-d h:i:s',time()));
						$cinfo[] = $this->generateChildren($child,$categoryIds);
					}
				}
				if(count($cinfo))
					$info['children']['category'] = $cinfo;	
			}
		}
		return $info;
	}
}
?>