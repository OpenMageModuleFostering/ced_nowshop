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
 
class NowShop_Synchronize_Helper_Data extends Mage_Core_Helper_Abstract{
	
	const ALLOWED_TAGS_AT_NOWSHOP = '<strong><em><b><i><div><br><br/><p><ul><ol><li>';
	public function getStatuses(){
		return array(
            '1' => $this->__("Listed"),
			'-1' => $this->__("Not Listed")
        );
	}
	public function getAttribute($attr = '',$value = ''){
		$result = '';
		if($attr!='' && $value!=''){
			switch($attr){
				case 'type' : switch($value){
								case 'simple' : $result = 'simple';break;
								case 'configurable' : 
								case 'bundle' :
								case 'grouped' : 
								case 'virtual' :
								case 'downloadable' : $result = 'master';break;
								default : $result = $value;
							} break;
				case 'description' : $result = strip_tags($value, self::ALLOWED_TAGS_AT_NOWSHOP); 
									 if($result==''){ $result = "default description"; }
									 break;
			}
		}
		return $result;
	}
	
	public function getOrderStatus($status = ''){
		switch($status){
			case 'holded'			:
			case 'fraud'			: return 'ordered'; break;
			case 'pending' 			: 
			case 'pending_payment'	:
			case 'payment_review'	:
			case 'processing'		: return 'confirmed'; break;
			case 'complete' 		: return 'shipped'; break;
			case 'canceled' 		:  
			case 'closed' 			: return 'cancelled'; break;
			default: return 'ordered'; break;
		}
		
	}
	
	public function uploadViaFtp($file=''){
		if(!strlen(Mage::getStoreConfig('nowshop/ftp/host')) || !strlen(Mage::getStoreConfig('nowshop/ftp/username')) || !strlen(Mage::getStoreConfig('nowshop/ftp/password'))){
			Mage::log('Error: Ftp Credentials Not Found..!!',null,'nowshop_ftp.log');
			return false;
		}
		$ftp_server 	= trim(Mage::getStoreConfig('nowshop/ftp/host'));
		$ftp_user_name	= trim(Mage::getStoreConfig('nowshop/ftp/username'));
		$ftp_user_pass	= trim(Mage::getStoreConfig('nowshop/ftp/password'));
		$remote_file_name = basename($file);

		try {
			/* set up basic connection */
			$ftp = new NowShop_Synchronize_Helper_Ftp("http://$ftp_user_name:$ftp_user_pass@$ftp_server");
			
			/* upload a file */
		
			$ret = $ftp->nbput($remote_file_name, $file, FTP_ASCII, $ftp->size($remote_file_name));
			
			while ($ret == NowShop_Synchronize_Helper_Ftp::MOREDATA) {
			   // Continue uploading...
			   $ret = $ftp->nb_continue();
			}
			if ($ret != NowShop_Synchronize_Helper_Ftp::FINISHED) {
			   Mage::log('Error: There was an error uploading the file('.$file.')',null,'nowshop_ftp.log');
			   return false;
			}
			return true;
		} catch (FtpException $e) {
			Mage::log('Error: '.$e->getMessage(),null,'nowshop_ftp.log');
			return false;
		}
	}
}