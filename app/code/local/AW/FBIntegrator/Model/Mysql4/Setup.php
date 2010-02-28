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

class AW_FBIntegrator_Model_Mysql4_Setup extends Mage_Eav_Model_Entity_Setup
{
    public function getDefaultEntities()
    {
        return array(
            'customer' => array(
                'entity_model' 		  => 'customer/customer' ,
                'attribute_model'	  => '' ,
                'table'	              => 'customer/entity' ,
                'increment_model'	  => 'eav/entity_increment_numeric' ,
                'increment_per_store' => 0,
                'attributes'        => array(
                    'facebook_id' => array(
                        'type'              => 'varchar',
                        'backend'           => '',
                        'frontend'          => '',
                        'label'             => '',
                        'input'             => '',
                        'class'             => '',
                        'source'            => '',
                        'global'            => 1,
                        'visible'           => false,
                        'required'          => false,
                        'user_defined'      => false,
                        'default'           => '',
                        'searchable'        => true,
                        'filterable'        => true,
                        'comparable'        => false,
                        'visible_on_front'  => false,
                        'unique'            => true,
                    ),
                    'facebook_permissions' => array(
                        'type'              => 'text',
                        'backend'           => '',
                        'frontend'          => '',
                        'label'             => '',
                        'input'             => '',
                        'class'             => '',
                        'source'            => '',
                        'global'            => 1,
                        'visible'           => false,
                        'required'          => false,
                        'user_defined'      => false,
                        'default'           => '',
                        'searchable'        => true,
                        'filterable'        => true,
                        'comparable'        => false,
                        'visible_on_front'  => false,
                        'unique'            => false,
                    ),
                )
            ),
        );
    }

}

?>