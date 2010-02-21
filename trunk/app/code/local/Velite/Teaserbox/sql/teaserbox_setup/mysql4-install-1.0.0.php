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



$this->startSetup();

$this->addAttribute('catalog_product', 'teaserboxadd', array(
                        'group'             => 'Teaserbox',
                        'label'             => 'Show in Teaserbox',
                        'type'              => 'int',
                        'input'             => 'select',
                        'default'           => '0',
                        'source'            => 'velite_teaserbox_model_product_attribute_source_images',
                        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                        'required'          => false
));

$this->addAttribute('catalog_product', 'teaserboxprio', array(
                        'group'             => 'Teaserbox',
                        'label'             => 'Order by priority',
                        'type'              => 'int',
                        'input'             => 'text',
                        'default'           => '5',
                        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                        'required'          => false
));

$this->addAttribute('catalog_product', 'teaserboxalttext', array(
                        'group'             => 'Teaserbox',
                        'label'             => 'Alternative Productname',
                        'type'              => 'text',
                        'input'             => 'text',
                        'global'            => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
                        'required'          => false
));


$this->endSetup();
