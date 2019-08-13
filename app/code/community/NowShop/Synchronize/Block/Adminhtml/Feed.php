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
/**
 * Block for NowShop Feed
 */
class NowShop_Synchronize_Block_Adminhtml_Feed extends Mage_Adminhtml_Block_Widget_Container{
	/**     * Set template     */    public function __construct()    {        parent::__construct();        $this->setTemplate('nowshop/feed.phtml');    }    /**     * Prepare button and grid     *     * @return Mage_Adminhtml_Block_Catalog_Product     */    protected function _prepareLayout()    {        $this->setChild('ngrid', $this->getLayout()->createBlock('nowshop/adminhtml_feed_grid', 'product.grid'));        return parent::_prepareLayout();    }    /**     * Deprecated since 1.3.2     *     * @return string     */    public function getAddNewButtonHtml()    {        return $this->getChildHtml('add_new_button');    }    /**     * Render grid     *     * @return string     */    public function getGridHtml()    {        return $this->getChildHtml('ngrid');    }    /**     * Check whether it is single store mode     *     * @return bool     */    public function isSingleStoreMode()    {        if (!Mage::app()->isSingleStoreMode()) {               return false;        }        return true;    }
}