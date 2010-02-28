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

class AW_FBIntegrator_Model_Facebook_Request extends Zend_Http_Client
{
    public function request($method = parent::POST)
    {
        $response = parent::request($method);
        if ($response->isSuccessful())
        {
            $res = new AW_FBIntegrator_Model_Facebook_Response($response->getBody());
            return $res->getData();
        }
        else
        {
            throw new Exception('Facebook API request failed. Try again later.');
        }
    }

}

?>
