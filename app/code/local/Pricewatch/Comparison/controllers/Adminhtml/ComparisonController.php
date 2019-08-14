<?php

class Pricewatch_Comparison_Adminhtml_ComparisonController extends Mage_Adminhtml_Controller_action
{

    public function uploadAction(){
        if(Mage::helper('comparison')->isEnabled()){
            $result = array();
            $isReady = Mage::getModel('comparison/comparison')->prepareProductsForUpload();
            if($isReady == 'TRUE'){
                $currentTime = date("Y-m-d h:i:s");
                $result = Mage::getModel('comparison/comparison')->processBulkUpload();
                if($result['success']){
                    $bulkProcess = Mage::getModel('comparison/status')->getCollection()
                            ->addFieldToFilter('name', 'bulkrequired')
                            ->getFirstItem();
                    $bulkProcess->setStatus(0);
                    $bulkProcess->setUpdatedTime($currentTime);
                    $bulkProcess->save();
                }
            } else {
                $result['success'] = false;
                $result['msg'] = $isReady;
            }
        } else {
            $result['success'] = false;
            $result['msg'] = Mage::helper('comparison')->isEnabledMsg();
        }
        
        Mage::app()->getResponse()->setBody(json_encode($result)); 
    }
    
}