<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category   Velite
 * @package    Velite_Teaserbox
 * @copyright  Copyright (c) 2009 velite (http://velite.de)
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Velite_Teaserbox_Block_Xml extends Velite_Teaserbox_Block_Xml_Abstract
{	
	
	protected $_extName = 'Teaserbox';
	protected $_version = '1.2.0';
	
	protected function _getConfigPaths()
	{
		$paths = array(
						'stageColor'  => array( 
								'path' 		=> 'stageColor',
								'prefix' 	=> '0x'),
						'enableTimer' 	=> 'enableTimer',
						'timer' 		=> 'timer',
						'direction' 	=> 'direction',
						'containerSpeed'=> 'containerSpeed',
						'showButtons' 	=> 'showButtons',
						'buttonTransparency' 	=> 'buttonTransparency' );
		return $paths;
	}
	
	protected function _toHtml() 
	{
		$storeId = $this->getRequest()->getParam('storeid', 1);
		$store = Mage::app()->getStore($storeId);
		
		$configXml = $this->getConfig(
								'teaserbox/general/',
								$this->_getConfigPaths(),
								array (
									'version' => $this->_extName .' '.$this->_version
								),
								$storeId );
	
		$out = "<teaserbox>\n";
		$out .= $configXml;

		$products = Mage::getResourceModel('catalog/product_collection')
		                  ->addFieldToFilter('teaserboxadd', array(array('from' => 1)))
		                  ->addAttributeToSelect('*')
		                  ->setStore($store)
		                  ->setOrder('teaserboxprio', 'asc');
		
		$out .= "<products>\n";
		
		foreach ($products AS $product) {
			$productFull = Mage::getModel('catalog/product');
			$productFull->load($product->getEntityId());
			$productFull->setStoreId($storeId);
			
			$imgPath = '';
			
		    if (is_array($productFull->getMediaGallery('images'))) {
        		foreach ($productFull->getMediaGallery('images') AS $image) {
                	$image['id'] = isset($image['value_id']) ? $image['value_id'] : null;
                	
                	if ($image['id'] != null && 
                		$image['id'] == $productFull->getTeaserboxadd()) {
   						$imgPath = $productFull->getMediaConfig()->getMediaUrl($image['file']);
                	}
        		}
        	}
		
		    if (empty($imgPath)) {
		    	continue;
		    }

			$productName = (string)$productFull->getTeaserboxalttext();
			  
			if (strlen($productName) == 0) {
				$productName = $productFull->name;
			}

			$out .= "<product>\n";
			$out .= $this->createXmlTag('productName', $productName);
			$out .= $this->createXmlTag('imagePath', $imgPath);
			$out .= $this->createXmlTag('targetURL', $productFull->getProductUrl());
		    $out .= "</product>\n";
		}
	
		$out .= "</products>\n";
		$out .= "</teaserbox>\n";		                  
		                  
		return $out;
	}

}
