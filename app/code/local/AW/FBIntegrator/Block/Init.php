<?php
/**
 * aheadWorks Co.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://ecommerce.aheadworks.com/LICENSE-M1.txt
 *
 * @category   AW
 * @package    AW_FBIntegrator
 * @copyright  Copyright (c) 2003-2009 aheadWorks Co. (http://www.aheadworks.com)
 * @license    http://ecommerce.aheadworks.com/LICENSE-M1.txt
 */
class AW_FBIntegrator_Block_Init extends Mage_Core_Block_Template
{
	const XML_CONFIG_LOCALE = 'general/locale/code';
	const XML_CONFIG_FRONTEND_SECTURE = 'web/secure/use_in_frontend';

	public function isSecure()
	{
		return Mage::getStoreConfig(self::XML_CONFIG_FRONTEND_SECTURE);
	}

	public function getLocale()
	{
		return Mage::getStoreConfig(self::XML_CONFIG_LOCALE);
	}
}