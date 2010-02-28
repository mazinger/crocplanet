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

class Velite_Teaserbox_Block_Index extends Mage_Core_Block_Template
{
	public function getTbWidth()
	{
		$storeId = Mage::app()->getStore()->getId();
		return Mage::getStoreConfig('teaserbox/general/tbWidth', $storeId);		
	}

	public function getTbHeight()
	{
		$storeId = Mage::app()->getStore()->getId();
		return Mage::getStoreConfig('teaserbox/general/tbHeight', $storeId);
	}
	
	public function getTeaserboxXmlUrl() 
	{
		return $this->getUrl('teaserbox/xml', $this->getTeaserboxXmlParams());
	}

	public function getJsBaseUrl()
	{
		return Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_JS);
	}	
	
	public function getTeaserboxXmlParams()
	{
		$storeId = Mage::app()->getStore()->getId();
		$params = array('storeid' => $storeId);
		return $params;
	}

	public function getTeaserboxDiv()
	{
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		return "<div id='teaserboxBox'><a href='http://velite.de'>Magento ".(strlen($baseUrl)%2?'Templates':'Extensions')."</a> by velite&trade;</div>";
	}
	
}