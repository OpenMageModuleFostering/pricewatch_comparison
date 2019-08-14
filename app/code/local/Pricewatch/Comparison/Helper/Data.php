<?php

class Pricewatch_Comparison_Helper_Data extends Mage_Core_Helper_Abstract
{

    public function getConfigData($node) 
	{
        return Mage::getStoreConfig('comparison/' . $node);
    }

    public function isEnabled(){
        
        return $this->getConfigData('general/enabled');
    }

    public function getMerchantToken(){
        
        return $this->getConfigData('general/token');
    }

    public function getMacKey(){
        
        return $this->getConfigData('general/mackey');
    }
    
    public function getEndPoint(){
        
        // return 'http://pricewatch.com.ng/partner/api/loaddata.php';
        return 'http://pricewatch.com.ng/partner/api/dataloader.php';
    }

    public function isEnabledMsg(){
        
        return 'Please enable pricewatch comparison module';
    }
}