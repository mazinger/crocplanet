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

class Velite_Teaserbox_Model_Product_Attribute_Source_Images extends Mage_Eav_Model_Entity_Attribute_Source_Abstract
{
    public function getAllOptions()
    {
        $product = Mage::registry('product');
        $options = array();
        
        if (empty($product)) {
        	return $options;
        }

        $options = array();        
        $options[] = array('value'=>'0','label'=>'Deactivated');        
        
        if (is_array($product->getMediaGallery('images'))) {
        	foreach ($product->getMediaGallery('images') AS $image) {
                $image['id'] = isset($image['value_id']) ? $image['value_id'] : null;
                $image['path'] = $product->getMediaConfig()->getMediaPath($image['file']);        		
        		
                if ($image['id'] != null) {
                	if ($image['label'] != '') {
		        		$label = $image['label'] . ' ('. basename($image['file']) .')';
        			} else {
        				$label = basename($image['file']);
        			}
        			
                    $options[] = array(
                		'value'=>$image['id'],
                		'label'=>$label 
            		);   
                }
        	}
        }
        
    	$this->_options = $options;
        return $this->_options;
    }
    
    public function getOptionText($value)
    {
        $product = Mage::registry('product');
        
        if (empty($product)) {
        	return 'Deactivated';
        }
        
    	return parent::getOptionText($value);
    }

    public function getOptionId($value)
    {
    	if ($value == 'Deactivated') {
    		return '0';
    	}
    	
        $product = Mage::registry('product');
         
        if (empty($product)) {
        	return '0';
        }
    	
    	return parent::getOptionId($value);
    }    
}