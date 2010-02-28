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

class AW_FBIntegrator_Model_Observer
{
    public function facebookPublishOrder($observer)
    {
        $isEnabled = Mage::getStoreConfigFlag('fbintegrator/facebook/enabled');
        $facebook = Mage::getSingleton('fbintegrator/facebook');
        if (!$isEnabled || !$facebook->getCurrentUid()){
            return;
        }
        $order = $observer->getEvent()->getOrder();
        if (Mage_Sales_Model_Order::STATE_NEW == $order->getState()){
            $facebook = Mage::getSingleton('fbintegrator/facebook');
            $template = Mage::helper('fbintegrator/template');
            list($message, $actionLinks) = $template->process($order);
            try {
                $facebook->streamPublish(
                    $facebook->getGrantedPermissions(),
                    $facebook->getSessionKey(),
                    $facebook->getCurrentUid(),
                    $message,
                    $actionLinks
                );
            } catch (Exception $e){}
        }
    }
}