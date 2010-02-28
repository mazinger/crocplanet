<?php

class AW_FBIntegrator_Block_Login extends Mage_Core_Block_Template
{
	protected $_username = -1;
	protected $_error;

	public function hasError()
	{
		return $this->_error = Mage::helper('fbintegrator')->getLoginError();
	}

	public function getError()
	{
		return $this->_error;
	}

    public function getUsername()
    {
        if (-1 === $this->_username) {
            $this->_username = Mage::getSingleton('customer/session')->getUsername(true);
        }
        return $this->_username;
    }

    /**
     * Retrieve form posting url
     *
     * @return string
     */
    public function getPostActionUrl()
    {
        return $this->getUrl('fbintegrator/facebook/fastLogin', array('_secure' => true) );
    }

    /**
     * Retrieve password forgotten url
     *
     * @return string
     */
    public function getForgotPasswordUrl()
    {
        return $this->helper('customer')->getForgotPasswordUrl();
    }

}
