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

class Velite_Teaserbox_Block_Xml_Abstract extends Mage_Core_Block_Template
{
	public function getConfig($namespace, $paths, $data = null, $storeId = null)
	{
		$out = "<config>\n";
		foreach($paths AS $tagName => $path) {
			if (is_array($path)) {
				$val = Mage::getStoreConfig($namespace . $path['path'], $storeId);
				$out .= $this->createXmlTag($tagName, $path['prefix'] . $val);
			} else {
				$val = Mage::getStoreConfig($namespace . $path, $storeId);
				$out .= $this->createXmlTag($tagName, $val);
			}
		}
		
		if (is_array($data)) {
			foreach($data AS $tagName => $val) {
				$out .= $this->createXmlTag($tagName, $val);
			}
		}
		$out .= "</config>\n";
		return $out;
	}

	public function createXmlTag ($tagName, $tagValue)
	{
		return "<{$tagName}><![CDATA[{$tagValue}]]></{$tagName}>\n";
	}
}