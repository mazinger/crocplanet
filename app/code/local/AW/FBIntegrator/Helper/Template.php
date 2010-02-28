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

class AW_FBIntegrator_Helper_Template extends Mage_Core_Helper_Abstract
{
    public function process($order)
    {
        $message = Mage::getStoreConfig('fbintegrator/facebook/post_message');
        $linkText = Mage::getStoreConfig('fbintegrator/facebook/post_link_text');
        $link = Mage::getStoreConfig('fbintegrator/facebook/post_link');
        $itemsCount = Mage::getStoreConfig('fbintegrator/facebook/items_count');
        $orderItems = $order->getAllVisibleItems();
        $data['items_count'] = count($orderItems);
        if (Mage::app()->isSingleStoreMode()){
            $data['store_link'] = Mage::getUrl();
        } else {
            $data['store_link'] = Mage::getUrl('', array('_store_to_url' => true, '_store' => Mage::app()->getStore()->getId()));
        }
        $message = $this->compile($message, $data);
        $product = Mage::getModel('catalog/product');
        $store = Mage::app()->getStore();
        $i = 0;
        foreach ($orderItems as $orderItem){
            $i++;
            $item = $product->load($orderItem->getProductId());
            $data['item_name'] =  $item->getName();
            $data['item_price'] = $store->convertPrice($item->getPrice(), true, false);
            $data['item_link'] =  $item->getProductUrl(false);
            $actionLinks[] = array(
                'text' => $this->compile($linkText, $data),
                'href' => $this->compile($link, $data),
            );
            if ($itemsCount > 0 && $i >= $itemsCount){
                break;
            }
        }
        return array($message, $actionLinks);
    }

    protected function _parse($template)
    {
        $vars = array();
        preg_match_all('~(\{(.*?)\})~', $template, $matches, PREG_SET_ORDER);
        foreach ($matches as $match){
            $vars[$match[1]] = array($match[2]);
        }
        return $vars;
    }

    public function compile($template, $data)
    {
        $vars = $this->_parse($template);
        foreach ($vars as $key => $value){
            $template = str_replace($key, $data[$value[0]], $template);
        }
        return $template;
    }
}