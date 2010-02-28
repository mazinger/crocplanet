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

class AW_FBIntegrator_Model_Customer extends Varien_Object
{
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    public function isLoggedIn()
    {
        return (bool) $this->_getSession()->isLoggedIn();
    }

    public function login($facebookId = null)
    {
		# if linked
        if ($facebookId && $this->isRegistered($facebookId)){
            $this->_getSession()->setCustomerAsLoggedIn($this->_customer);
            return true;
        }
		# if not linked
		elseif ($facebookId && !$this->isRegistered($facebookId))
		{
			return false;
		}
        Mage::throwException(Mage::helper('fbintegrator')->__('This facebook user is not registered with this store.'));
    }

	public function link($facebookId, $customerId)
	{
		if ($facebookId && $customerId)
		{
			try 
			{
				$customer = Mage::getModel('customer/customer')->load($customerId);
				if ($customer && !$this->isRegistered($facebookId))
				{
					$customer->setFacebookId($facebookId)->save();
				}
			}
			catch(Exception $e)
			{
				Mage::throwException($e->getMessage());
			}															
		}
		return $this;
	}

    public function register($facebookId, $email, $password, $userInfo, $isSubscribed = false)
    {
        if ($this->isRegistered($facebookId)){
            Mage::throwException(Mage::helper('fbintegrator')->__('This facebook user is already registered with this store. Please log in!'));
        }
        $customer = Mage::getModel('customer/customer')->setId(null);
        $customer->getGroupId();
        $customer->setPassword($password);
        $customer->setConfirmation($password);
        $customer->setData('facebook_id', $facebookId);
        $customer->setData('firstname', $userInfo['firstname']);
        $customer->setData('lastname', $userInfo['lastname']);
        $customer->setData('email', $email);
		if (isset($userInfo['dob']))
		{
			$customer->setData('dob', $userInfo['dob']);
		}		
        if ($isSubscribed) {
            $customer->setIsSubscribed(1);
        }

        $errors = $customer->validate();

        if (is_array($errors)){
            Mage::throwException(implode("\n", $errors));
        }
        $customer->save();
        $customer->setConfirmation(null);
        $customer->save();
        $customer->sendNewAccountEmail('confirmed');
        return $customer;
    }

    public function isRegistered($facebookId){
        $collection = Mage::getResourceModel('customer/customer_collection');
        $collection->addAttributeToFilter('facebook_id', array('eq' => $facebookId));
        $publishType = new Mage_Customer_Model_Config_Share();
        if ($publishType->isWebsiteScope()){
            $siteId = Mage::app()->getWebsite()->getId();
            $collection->addAttributeToFilter('website_id', array('eq' => $siteId));
        }

        $customer = $collection->getFirstItem();
        if ($customer->getId()){
            $this->_customer = $customer;
            return true;
        }
        return false;
    }
}