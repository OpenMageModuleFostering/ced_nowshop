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
 
class NowShop_Synchronize_Helper_Arraytoxml {
 
    private static $xml = null;
	private static $encoding = 'UTF-8';
 
    public function getSchema(){
		$schema = Mage::getBaseDir().DS.'app'.DS.'code'.DS.'community'.DS.'NowShop'.DS.'Synchronize'.DS.'etc'.DS.'xsd'.DS.'product_feed.xsd';
		return $schema;
	}
	
	public function formatedFileName($type='product',$diff = false){
		$filename  = Mage::getBaseDir('media').DS.'nowshop'.DS.$type.'feed'.DS;
		$isocode = Mage::getStoreConfig('nowshop/setting/isocode');
		if($type == 'category'){
			$filename .= 'categories.';
		}
		$filename .= date('Ymdhis').'.'.$isocode; 
		if($diff)
			$filename .= '.diff'; 
		$filename .= '.xml';
		return $filename;
	}
	
	/**
     * Initialize the root XML node [optional]
     * @param $version
     * @param $encoding
     * @param $format_output
     */
    public static function init($version = '1.0', $encoding = 'UTF-8', $format_output = true) {
        self::$xml = new DomDocument($version, $encoding);
        self::$xml->formatOutput = $format_output;
		self::$encoding = $encoding;
    }
 
    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DomDocument
     */
    public static function &createXML($node_name, $arr=array()) {
        $xml = self::getXMLRoot();
        $xml->appendChild(self::convert($node_name, $arr));
 
        self::$xml = null;    /* clear the xml node in the class for 2nd time use. */
        return $xml;
    }
 
    /**
     * Convert an Array to XML
     * @param string $node_name - name of the root node to be converted
     * @param array $arr - aray to be converterd
     * @return DOMNode
     */
    private static function &convert($node_name, $arr=array()) {
 
        /* print_arr($node_name); */
        $xml = self::getXMLRoot();
        $node = $xml->createElement($node_name);
 
        if(is_array($arr)){
            /* get the attributes first.; */
            if(isset($arr['@attributes'])) {
                foreach($arr['@attributes'] as $key => $value) {
                    if(!self::isValidTagName($key)) {
                        throw new Exception('[NowShop_Synchronize_Helper_Arraytoxml] Illegal character in attribute name. attribute: '.$key.' in node: '.$node_name);
                    }
                    $node->setAttribute($key, self::bool2str($value));
                }
                unset($arr['@attributes']); /* remove the key from the array once done. */
            }
 
            /* check if it has a value stored in @value, if yes store the value and return */
            /* else check if its directly stored as string */
            if(isset($arr['@value'])) {
                $node->appendChild($xml->createTextNode(self::bool2str($arr['@value'])));
                unset($arr['@value']);    /* remove the key from the array once done. */
                /* return from recursion, as a note with value cannot have child nodes. */
                return $node;
            } else if(isset($arr['@cdata'])) {
                $node->appendChild($xml->createCDATASection(self::bool2str($arr['@cdata'])));
                unset($arr['@cdata']);    /* remove the key from the array once done. */
                /*return from recursion, as a note with cdata cannot have child nodes. */
                return $node;
            }
        }
 
        /* create subnodes using recursion */
        if(is_array($arr)){
            /* recurse to get the node for that key */
            foreach($arr as $key=>$value){
                if(!self::isValidTagName($key)) {
                    throw new Exception('[NowShop_Synchronize_Helper_Arraytoxml] Illegal character in tag name. tag: '.$key.' in node: '.$node_name);
                }
                if(is_array($value) && is_numeric(key($value))) {
                    /* MORE THAN ONE NODE OF ITS KIND;
                       if the new array is numeric index, means it is array of nodes of the same kind
                       it should follow the parent key name 
					*/
                    foreach($value as $k=>$v){
                        $node->appendChild(self::convert($key, $v));
                    }
                } else {
                    /* ONLY ONE NODE OF ITS KIND */
                    $node->appendChild(self::convert($key, $value));
                }
                unset($arr[$key]); /* remove the key from the array once done. */
            }
        }
 
        /* after we are done with all the keys in the array (if it is one) */
        /* we check if it has any text value, if yes, append it. */
        if(!is_array($arr)) {
            $node->appendChild($xml->createTextNode(self::bool2str($arr)));
        }
 
        return $node;
    }
 
    /*
     * Get the root XML node, if there isn't one, create it.
     */
    private static function getXMLRoot(){
        if(empty(self::$xml)) {
            self::init();
        }
        return self::$xml;
    }
 
    /*
     * Get string representation of boolean value
     */
    private static function bool2str($v){
        /* convert boolean to text value. */
        $v = $v === true ? 'true' : $v;
        $v = $v === false ? 'false' : $v;
        return $v;
    }
 
    /*
     * Check if the tag name or attribute name contains illegal characters
     * Ref: http://www.w3.org/TR/xml/#sec-common-syn
     */
    private static function isValidTagName($tag){
        $pattern = '/^[a-z_]+[a-z0-9\:\-\.\_]*[^:]*$/i';
        return preg_match($pattern, $tag, $matches) && $matches[0] == $tag;
    }
	
}