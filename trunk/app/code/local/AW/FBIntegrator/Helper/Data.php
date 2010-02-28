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

class AW_FBIntegrator_Helper_Data extends Mage_Core_Helper_Abstract
{
	public function setLoginError($value)
	{
        $session = Mage::getSingleton('core/session', array('name'=>'frontend'))->start();
        $session->setAwLoginError( $value );		
		return $this;
	}

	public function getLoginError()
	{
        $session = Mage::getSingleton('core/session', array('name'=>'frontend'))->start();
        $error = $session->getAwLoginError();
        $session->setAwLoginError( null );
        return $error;
	}
}