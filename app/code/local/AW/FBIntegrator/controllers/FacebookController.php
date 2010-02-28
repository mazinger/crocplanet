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

class AW_FBIntegrator_FacebookController extends Mage_Core_Controller_Front_Action
{
    protected function _getSession()
    {
        return Mage::getSingleton('customer/session');
    }

    protected function _ajaxResponse($result = array())
    {
        $this->getResponse()->setBody(Zend_Json::encode($result));
        return;
    }

    protected function _ajaxRedirect()
    {
        $result['error'] = false;
        $result['redirect'] = $this->_getRedirectUrl();
        return $this->_ajaxResponse($result);
    }

    protected function _getRedirectUrl()
    {
        $url = $this->_getSession()->getBeforeAuthUrl(true);
        if (!$url){
            $url = Mage::getUrl('customer/account/');
        }
        return $url;
    }

    public function loginAction()
    {
        if ($this->_getSession()->isLoggedIn()) {
            $this->_ajaxRedirect();
            return;
        }

        $facebook = Mage::getSingleton('fbintegrator/facebook');
        if ($facebook->getCurrentUid()){
            $customer = Mage::getSingleton('fbintegrator/customer');
            try {				
				if ( $customer->login($facebook->getCurrentUid()) )
				{
					$result['error'] = false;
					$result['redirect'] = $this->_getRedirectUrl();
					return $this->_ajaxResponse($result);
				}
				else
				{
					$result['needlogin'] = true;
					return $this->_ajaxResponse($result);
				}
            } catch (Mage_Core_Exception $e) {
                $result['error'] = true;
                $result['message'] = $e->getMessage();
                return $this->_ajaxResponse($result);
            } catch (Exception $e) {
                $result['error'] = true;
                $result['message'] = $e;
                return $this->_ajaxResponse($result);
            }
        }
        $result['error'] = true;
        $result['message'] = Mage::helper('fbintegrator')->__('Facebook session does not exist.');
        return $this->_ajaxResponse($result);
    }

    public function registerAction()
    {
        if ($this->_getSession()->isLoggedIn()){
            $this->_ajaxRedirect();
            return;
        }

        $facebook = Mage::getSingleton('fbintegrator/facebook');
        if ($facebook->getCurrentUid()){
            $facebookUser = $facebook->usersGetStandardInfo($facebook->getCurrentUid());					
            $userInfo = array(
                'firstname' => $facebookUser['first_name'],
                'lastname'  => $facebookUser['last_name'],
                'sex'		=> $facebookUser['sex'],
				'dob'		=> $facebookUser['birthday']
            );		
            $customer = Mage::getSingleton('fbintegrator/customer');
            //$redirectReferer = (bool) $this->getRequest()->getParam('referer', false);
            $data = $this->getRequest()->getPost('login');
            $email = $data['username'];
            $password = $data['password'];
            $isSubscribed = $this->getRequest()->getPost('is_subscribed');
            try {
                $newCustomer = $customer->register($facebook->getCurrentUid(), $email, $password, $userInfo, $isSubscribed);
            } catch (Mage_Core_Exception $e) {
                $result['error'] = true;
                $result['message'] = $e->getMessage();
                return $this->_ajaxResponse($result);
            } catch (Exception $e) {
                $result['error'] = true;
                $result['message'] = $e;
                return $this->_ajaxResponse($result);
            }
            if ($newCustomer){
                $this->_getSession()->setCustomerAsLoggedIn($newCustomer);
                $result['error'] = false;
                //if ($redirectReferer){
                $result['redirect'] = $this->_getRedirectUrl();
                /*} else {
                    $result['redirect'] = Mage::getUrl('customer/account/');
                }*/
                return $this->_ajaxResponse($result);
            }
        }
    }

	public function linkAction()
	{
        if ($this->_getSession()->isLoggedIn())
		{
			$facebook = Mage::getSingleton('fbintegrator/facebook');
			$customer = Mage::getSingleton('fbintegrator/customer');
			$customerId = $this->_getSession()->getCustomer()->getId();
			$facebookId = $facebook->getCurrentUid();
			if ($facebookId && $customerId)
			{
				try
				{
					$customer->link($facebookId, $customerId );
				}
				catch (Mage_Core_Exception $e)
				{
					$result['error'] = true;
					$result['message'] = $e->getMessage();
					return $this->_ajaxResponse($result);
				}
				catch (Exception $e)
				{
					$result['error'] = true;
					$result['message'] = $e;
					return $this->_ajaxResponse($result);
				}
			}
            $this->_ajaxRedirect();
            return;
        }
	}

    public function savePermissionsAction()
    {
        $customer = $this->_getSession()->getCustomer();
        $customer->setFacebookPermissions($this->getRequest()->getPost('permissions'));
        $customer->save();
        $result['error'] = false;
        return $this->_ajaxResponse($result);
    }

	/*
	 * Fast login section Actions
	 */


	public function loginFormAction()
	{
        $this->getResponse()->setBody(
			$this->getLayout()->createBlock('fbintegrator/login','fbintegrator.store.login', array('template'=>'fbintegrator/store_login.phtml'))->toHtml()
        );
	}

	public function fastLoginAction()
	{
		$username = '';
		$password = '';
		$data = $this->getRequest()->getPost('login');
		if ( isset($data['username']) && isset($data['password']) )
		{
			$username = $data['username'];
			$password = $data['password'];
		}
		if ($this->_fastLogin($username, $password))
		{
			$result['success'] = true;
			return $this->_ajaxResponse($result);
		}
		else
		{
			$content = $this->getLayout()->createBlock('fbintegrator/login','fbintegrator.store.login', array('template'=>'fbintegrator/store_login.phtml'))->toHtml();
			$this->getResponse()->setBody($content);
		}
	}

	protected function _fastLogin($username, $password)
	{
		$session = $this->_getSession();

        if ($session->isLoggedIn())
		{
            return true;
        }

		$session = $this->_getSession();
		
		if ($username && $password)
		{
			try
			{
				$session->login($username, $password);
			}
			catch (Exception $e) {
				switch ($e->getCode()) {
					case Mage_Customer_Model_Customer::EXCEPTION_EMAIL_NOT_CONFIRMED:
						$message = Mage::helper('customer')->__('This account is not confirmed. <a href="%s">Click here</a> to resend confirmation email.',
							Mage::helper('customer')->getEmailConfirmationUrl($username)
						);
						break;
					case Mage_Customer_Model_Customer::EXCEPTION_INVALID_EMAIL_OR_PASSWORD:
						$message = $e->getMessage();
						break;
					default:
						$message = $e->getMessage();
				}
				Mage::helper('fbintegrator')->setLoginError($message);
				$session->setUsername($username);
			}
		} 
		else
		{
			Mage::helper('fbintegrator')->setLoginError($this->__('Login and password are required'));
		}

        if ($session->isLoggedIn())
		{
            return true;
        }
		else
		{
			return false;
		}
	}

}