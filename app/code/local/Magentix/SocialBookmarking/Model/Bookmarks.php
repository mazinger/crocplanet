<?php
/** http://www.magentix.fr **/

class Magentix_SocialBookmarking_Model_Bookmarks extends Mage_Core_Model_Abstract {

    public function _construct() {
        parent::_construct();
        $this->_init('socialbookmarking/bookmarks');
    }

	/* Get bookmark URL - return #erreur if wrong parameter - array('url'=>'value','bitly'=>'value','title'=>'value') */
	public function getBookmarkUrl($infos=array()) {
		if(!$this->checkInfos($infos)) return '#erreur';
		return preg_replace(array('/<url>/','/<bitly>/','/<title>/'),array($infos['url'],$infos['bitly'],$infos['title']),$this->getUrl());
	}

	/* Get bookmark icon - return bookmark name if no image */
	public function getBookmarkImage() {
		$img = $this->getName();
		if($this->getImage() != '') {
			$img = '<img src="'.Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).$this->getImage().'" alt="'.$this->getName().'" />';
		}
		return $img;
	}

	/* Get link target - return empty if target is null */
	public function getBookmarkTarget() {
		$target = '';
		if($this->getTarget() == 1) $target = 'target="_blank"';
		return $target;
	}

	/* Return array with page informations : URL, bit.ly URL and Meta title */
	public function getPageInfos() {
		return array('url'=>$this->getPUrl(),'bitly'=>$this->getPBitly(),'title'=>$this->getPTitle());
	}

	/* Check array */
	private function checkinfos($infos) {
		return is_array($infos) && array_key_exists('url',$infos) && array_key_exists('bitly',$infos) && array_key_exists('title',$infos);
	}

	/* Return page Meta title */
	private function getPTitle() {
		return Mage::getSingleton('core/layout')->getBlock('head')->getTitle();
	}

	/* Return page URL */
	private function getPUrl() {
		return preg_replace(array('/\?___SID=U/'),array(''),Mage::helper('core/url')->getCurrentUrl());
	}

	/* Return page bit.ly URL */
	private function getPBitly() {

		$urls = Mage::getModel('socialbookmarking/urls')->getCollection()->addFieldToFilter('url',$this->getPUrl());
		if(count($urls)) {
			foreach($urls as $u) return $u->getBitly();
		} else {
			$config = Mage::getStoreConfig('socialbookmarking/bitly');

			foreach($config as $c => $i) if(empty($i)) return $this->getPUrl();

			$bitly = 'http://api.bit.ly/shorten?version='.$config['version'].'&longUrl='.urlencode($this->getPUrl()).'&login='.$config['login'].'&apiKey='.$config['key'];
			if($response = @file_get_contents($bitly)) {
				$json = json_decode($response,true);
				if(isset($json['results']) && isset($json['results'][$this->getPUrl()]['shortUrl'])) {

					$model = Mage::getModel('socialbookmarking/urls');
					$model->setData('url',$this->getPUrl());
					$model->setData('bitly',$json['results'][$this->getPUrl()]['shortUrl']);
					$model->save();

					return $json['results'][$this->getPUrl()]['shortUrl'];
				} else {
					return $this->getPUrl();
				}
			} else {
				return $this->getPUrl();
			}
		}
	}
}