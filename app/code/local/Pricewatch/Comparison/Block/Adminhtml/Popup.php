<?php
class Pricewatch_Comparison_Block_Adminhtml_Popup extends Mage_Core_Block_Template {
 
    /**
     * XML path of Severity icons url
     */
    const XML_SEVERITY_ICONS_URL_PATH  = 'system/adminnotification/severity_icons_url';

    /**
     * Severity icons url
     *
     * @var string
     */
    protected $_severityIconsUrl;

    public function canShow()
    {   
        $canShow = Mage::getSingleton('core/session')->getPricewatchNotification();

        if ($canShow != 'NO' && Mage::helper('comparison')->isEnabled()){
            $bulkProcess = Mage::getModel('comparison/status')->getCollection()
                        ->addFieldToFilter('name', 'bulkrequired')
                        ->getFirstItem();
            if($bulkProcess->getStatus()){
                Mage::getSingleton('core/session')->setPricewatchNotification('YES');
                return true;
            } else {
                Mage::getSingleton('core/session')->setPricewatchNotification('NO');
                return false;
            }
        }
        
        return false;
    }
    
    public function getSeverityText(){
        return $this->escapeHtml($this->__('MAJOR'));
    }
    
    public function getSeverityIconsUrl(){
        $this->_severityIconsUrl =
                (Mage::app()->getFrontController()->getRequest()->isSecure() ? 'https://' : 'http://')
                . sprintf(Mage::getStoreConfig(self::XML_SEVERITY_ICONS_URL_PATH), Mage::getVersion(),
                    'SEVERITY_MAJOR')
            ;
            
        return $this->_severityIconsUrl;
    }
}