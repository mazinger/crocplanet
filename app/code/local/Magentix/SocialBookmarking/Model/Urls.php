<?php
/** http://www.magentix.fr **/

class Magentix_SocialBookmarking_Model_Urls extends Mage_Core_Model_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('socialbookmarking/urls');
    }

}