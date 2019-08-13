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
 
$installer = $this;
$installer->startSetup();
$installer->run("
		CREATE TABLE IF NOT EXISTS `".$this->getTable('nowshop/cfeed')."` (
		`id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID',
		`category_id` text NOT NULL COMMENT 'CATEGORY ID',
		`parent_id` text NOT NULL COMMENT 'PARENT CATEGORY ID',
		`status` tinyint(4) NOT NULL DEFAULT '1' COMMENT 'STATUS',
		`updated_at` datetime NOT NULL COMMENT 'UPDATED At',
		PRIMARY KEY (`id`)
	) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
");
$installer->endSetup();