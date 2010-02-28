<?php

/**
 * Magento Owebia Shipping Module
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
 * @category   Owebia
 * @package    Owebia_Shipping_2
 * @copyright  Copyright (c) 2008-09 Owebia (http://www.owebia.com/)
 * @author     Antoine Lemoine
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

include_once dirname(__FILE__).'/OwebiaShippingHelper.php';

class Magento_Product implements OS_Product {
	private $parent_cart_item;
	private $cart_item;
	private $cart_product;
	private $loaded_product;
	private $quantity;
	
	public function Magento_Product($cart_item, $parent_cart_item)
	{
		$this->cart_item = $cart_item;
		$this->cart_product = $cart_item->getProduct();
		$this->parent_cart_item = $parent_cart_item;
		$this->quantity = isset($parent_cart_item) ? $parent_cart_item->getQty() : $cart_item->getQty();
	}
	
	public function getOption($option_name, $get_by_id)
	{
		$value = null;
		$product = $this->cart_product;
		foreach ($product->getOptions() as $option)
		{
			if ($option->getTitle()==$option_name) {
				$custom_option = $product->getCustomOption('option_'.$option->getId());
				if ($custom_option) {
					$value = $custom_option->getValue();
					if ($option->getType()=='drop_down' && !$get_by_id) {
						$option_value = $option->getValueById($value);
						if ($option_value) $value = $option_value->getTitle();
					}
				}
				break;
			}
		}
		return $value;
	}
	
	public function getAttribute($attribute_name, $get_by_id)
	{
		if (!isset($this->loaded_product)) $this->loaded_product = Mage::getModel('catalog/product')->load($this->cart_product->getId());

		$value = null;
		$product = $this->loaded_product;
		$attribute = $product->getResource()->getAttribute($attribute_name);
		if ($attribute) {
			$input_type = $attribute->getFrontend()->getInputType();
			switch ($input_type)
			{
				case 'select' :
					$value = $get_by_id ? $product->getData($attribute_name) : $product->getAttributeText($attribute_name);
					break;
				default :
					$value = $product->getData($attribute_name);
					break;
			}
		}
		return $value;
	}

	public function getQuantity()
	{
		return $this->quantity;
	}

	public function getName()
	{
		return $this->cart_product->getName();
	}

	public function getSku()
	{
		return $this->cart_product->getSku();
	}
}

abstract class Owebia_Shipping_2_Model_Carrier_AbstractOwebiaShipping
	extends Mage_Shipping_Model_Carrier_Abstract
{
	protected $_result;
	protected $_config;
	protected $_countries;
	protected $_helper;
	protected $_messages;

	/**
	 * Collect rates for this shipping method based on information in $request
	 *
	 * @param Mage_Shipping_Model_Rate_Request $data
	 * @return Mage_Shipping_Model_Rate_Result
	 */
	public function collectRates(Mage_Shipping_Model_Rate_Request $request)
	{
		// skip if not enabled
		if (!$this->__getConfigData('active')) return false;

		$customer_group_id = Mage::getSingleton('customer/session')->getCustomerGroupId();
		$customer_group_code = Mage::getSingleton('customer/group')->load($customer_group_id)->getData('customer_group_code');

		$process = array(
			'result' => Mage::getModel('shipping/rate_result'),
			'price_excluding_tax' => $request->_data['package_value_with_discount'],
			'destination_country_code' => $request->_data['dest_country_id'],
			'destination_region_code' => $request->_data['dest_region_code'],
			'destination_postcode' => $request->_data['dest_postcode'],
			'destination_country_name' => $this->__getCountryName($request->_data['dest_country_id']),
			'origin_country_code' => $request->_data['country_id'],
			'origin_region_code' => $request->_data['region_id'],
			'origin_postcode' => $request->_data['postcode'],
			'origin_country_name' => $this->__getCountryName($request->_data['country_id']),
			'free_shipping' => $request->getFreeShipping(),
			'customer_group_id' => $customer_group_id,
			'customer_group_code' => $customer_group_code,
			'package_weight' => $request->_data['package_weight'],
			'weight_unit' => Mage::getStoreConfig('owebia/shipping/2/weight_unit'),
			'products_quantity' => $request->_data['package_qty'],
			'items' => array(),
			'products' => array(),
		);

		$items = $request->getAllItems();
		for ($i=0, $n=count($items); $i<$n; $i++)
		{
			$item = $items[$i];
			if ($item->getProduct() instanceof Mage_Catalog_Model_Product) $process['items'][$item->getId()] = $item;
		}

		foreach ($process['items'] as $id => $item)
		{
			if ($item->getProduct()->getTypeId()!='configurable')
			{
				$parent_item_id = $item->getParentItemId();
				$process['products'][] = new Magento_Product($item, isset($process['items'][$parent_item_id]) ? $process['items'][$parent_item_id] : null);
			}
		}

		if (!$process['free_shipping']) {
			foreach ($process['items'] as $item) {
				if ($item->getProduct() instanceof Mage_Catalog_Model_Product) {
					if ($item->getFreeShipping()) $process['free_shipping'] = true;
					else {
						$process['free_shipping'] = false;
						break;
					}
				}
			}
		}

		$process['price_including_tax'] = $this->__getCartTaxAmount($process)+$process['price_excluding_tax'];

		$this->_process($process);

		return $process['result'];
	}


	public function getAllowedMethods()
	{
		$process = array();

		$config = $this->_getConfig();

		$allowed_methods = array();
		if (count($config)>0)
		{
			foreach ($config as $row) $allowed_methods[$row['*code']] = $row['label'];
		}
		return $allowed_methods;
	}

	public function isTrackingAvailable()
	{
		return true;
	}

	public function getTrackingInfo($tracking_number)
	{
		$tracking_url = $this->__getConfigData('tracking_view_url');
		$parts = explode(':',$tracking_number);
		if (count($parts)>=2)
		{
			$tracking_number = $parts[1];

			$process = array();
			$config = $this->_getConfig();
			
			if (isset($config[$parts[0]]['tracking_url'])) {
				$tmp_tracking_url = $this->_helper->getRowProperty($config,$row,'tracking_url');
				if (isset($tmp_tracking_url)) $tracking_url = $tmp_tracking_url;
			}
		}

		$tracking_status = Mage::getModel('shipping/tracking_result_status')
			->setCarrier($this->_code)
			->setCarrierTitle($this->__getConfigData('title'))
			->setTracking($tracking_number)
			->addData(
				array(
					'status'=>'<a target="_blank" href="'.str_replace('{tracking_number}',$tracking_number,$tracking_url).'">'.__('track the package').'</a>'
				)
			)
		;
		$tracking_result = Mage::getModel('shipping/tracking_result')
			->append($tracking_status)
		;

		if ($trackings = $tracking_result->getAllTrackings()) return $trackings[0];
		return false;
	}

	/***************************************************************************************************************************/

	protected function _process(&$process)
	{
		$process['stop_to_first_match'] = $this->__getConfigData('stop_to_first_match');
		$process['full_weight'] = $process['package_weight'];
		$process['config'] = $this->_getConfig();

		$value_found = false;
		foreach ($process['config'] as $row)
		{
			$result = $this->_helper->processRow($process,$row);
			$this->_addMessages($this->_helper->getMessages());
			if ($result->success)
			{
				$value_found = true;
				$this->__appendMethod($process,$row,$result->result);
				if ($process['stop_to_first_match']) break;
			}
		}

		$this->_appendErrors($process,$this->_messages);
		if (!$value_found) $this->__appendError($process,$this->__('No match found'));
	}

	protected function _getConfig()
	{
		if (!isset($this->_config))
		{
			$this->_helper = new OwebiaShippingHelper();
			$this->_config = $this->_helper->parseConfig($this->__getConfigData('config'));
			$this->_addMessages($this->_helper->getMessages());
		}
		return $this->_config;
	}

	protected function _getMethodText($process, $row, $property)
	{
		if (!isset($row[$property])) return '';

		return str_replace(
			array('{weight}','{country}','{products_quantity}','{price_including_tax}','{price_excluding_tax}'),
			array(
				$process['full_weight'].$process['weight_unit'],
				$process['destination_country_name'],
				$process['products_quantity'],
				$this->__formatPrice($process['price_including_tax']),
				$this->__formatPrice($process['price_excluding_tax'])
			),
			$this->_helper->getRowProperty($process['config'],$row,$property)
		);
	}

	protected function _addMessages($messages)
	{
		if (!is_array($messages)) $messages = array($messages);
		if (!is_array($this->_messages)) $this->_messages = $messages;
		else $this->_messages = array_merge($this->_messages,$messages);
	}

	protected function _appendErrors(&$process, $messages)
	{
		if (is_array($messages))
		{
			foreach ($messages as $message)
			{
				$this->__appendError($process,$this->__($message));
			}
		}
	}
	
	/***************************************************************************************************************************/

	protected function __getConfigData($key)
	{
		return $this->getConfigData($key);
	}

	protected function __appendMethod(&$process, $row, $fees)
	{
		$method = Mage::getModel('shipping/rate_result_method')
			->setCarrier($this->_code)
			->setCarrierTitle($this->__getConfigData('title'))
			->setMethod($row['*code'])
			->setMethodTitle($this->_getMethodText($process,$row,'label'))
			->setMethodDescription($this->_getMethodText($process,$row,'description'))
			->setPrice($fees)
			->setCost($fees)
		;

		$process['result']->append($method);
	}

	protected function __appendError(&$process, $message)
	{
		if (isset($process['result']) && $this->__getConfigData('display_when_unavailable'))
		{
			$error = Mage::getModel('shipping/rate_result_error')
				->setCarrier($this->_code)
				->setCarrierTitle($this->__getConfigData('title'))
				->setErrorMessage($message)
			;
			$process['result']->append($error);
		}
	}
	
	protected function __formatPrice($price)
	{
		if (!isset($this->_core_helper)) $this->_core_helper = Mage::helper('core');
		return $this->_core_helper->currency($price);
	}

	protected function __($message)
	{
		$args = func_get_args();
		$message = array_shift($args);
		if ($message instanceof OS_Message)
		{
			$args = $message->args;
			$message = $message->message;
		}
		$output = Mage::helper('shipping')->__($message);
		return count($args)==0 ? $output : vsprintf($output,$args);
	}

	protected function __getCountryName($country_code)
	{
		if (!isset($this->_countries)) {
			// deprecated
			//$this->_countries = Mage::getModel('core/locale')->getLocale()->getCountryTranslationList();
			$this->_countries = Mage::getModel('core/locale')->getLocale()->getTranslationList('territory',null,2);
		}
		return isset($this->_countries[$country_code]) ? $this->_countries[$country_code] : $country_code;
	}

	protected function __getCartTaxAmount($process)
	{
		$tax_amount = 0;
		foreach ($process['items'] as $item) {
			$calc = Mage::getSingleton('tax/calculation');
			$rates = $calc->getRatesForAllProductTaxClasses($calc->getRateRequest());
			$tax_class_id = $item->getProduct()->getTaxClassId();
			$vat_rate = isset($rates[$tax_class_id]) ? $rates[$tax_class_id] : 0;

			if ($vat_rate > 0){
				$vat_to_add = $item->getData('row_total_with_discount')*$vat_rate/100;
			} else {
				$vat_to_add = $item->getData('tax_amount');
			}
			//echo $item->getProduct()->getName().', '.$item->getData('row_total_with_discount').', '.$vat_rate.', '.$vat_to_add.'<br />';
			$tax_amount += $vat_to_add;
		}
		//echo 'tax:'.$tax_amount.'<br />';
		return $tax_amount;
	}

	//				

}

?>