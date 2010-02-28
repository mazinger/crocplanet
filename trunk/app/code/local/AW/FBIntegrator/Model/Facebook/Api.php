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

class AW_FBIntegrator_Model_Facebook_Api extends Varien_Object
{
    protected $_apiUrl = 'http://api.new.facebook.com/restserver.php';
    protected $_params;

    protected function _initParams(){
        $this->_params = array(
            'api_key' => $this->getApiKey(),
        	'v' => '1.0',
            'format' => 'XML',
            'call_id' => microtime(true),
        );
    }

    protected function _signParams(){
        $str = '';
        ksort($this->_params);
        foreach ($this->_params as $key => $value) {
            $str .= $key . '=' . $value;
        }
        $this->_params['sig'] = md5($str . $this->getSecret());
    }

    protected function _addParams(array $params)
    {
        $params = array_filter($params);
        $this->_params += $params;
    }

    public function usersGetStandardInfo($uid)
    {
        $fields = array(
            'uid',
            'first_name',
            'last_name',
            'username',
            'name',
            'locale',
            'timezone',
            'birthday',
            'sex',
            'current_location',
            'proxied_email',
        );

        $this->_initParams();
        $this->_addParams(array(
            'method' => 'users.getStandardInfo',
            'uids' => $uid,
            'fields' => implode(',', $fields),
        ));
        $this->_signParams();
        $xml = $this->_send();
        foreach ($xml->user as $n => $user){
            foreach ($user as $key => $value){
                if (count($xml->user) > 1){
                    $users[$n][$key] = (string) $value;
                } else {
                    $users[$key] = (string) $value;
                }
            }
        }
        return $users;
    }

    public function streamPublish($permissions, $sessionKey, $uid, $message, $actionLinks = array(), $targetId = null)
    {
        if (!in_array('publish_stream', $permissions)){
            return null;
        }
        $this->_initParams();
        $this->_addParams(array(
            'method' => 'stream.publish',
            'session_key' => $sessionKey,
            'uid' => $uid,
            'target_id' => $targetId,
            'message' => $message,
            'attachment' => '',
        	'action_links' => Zend_Json::encode($actionLinks),
        ));
        $this->_signParams();
        /*$this->_addParams(array(
            'method' => 'stream.publish',
            'session_key' => $sessionKey,
            'uid' => $uid,
            //'target_id' => $targetId,
            'message' => $message,
            'attachment' => '',
            'action_links' => Zend_Json::encode($actionLinks),
        ));*/
        $data = $this->_send();
        return $data;
    }

    protected function _send()
    {
        $request = new AW_FBIntegrator_Model_Facebook_Request($this->_apiUrl);
        $request->setParameterPost($this->_params);
        return $request->request();
    }
}