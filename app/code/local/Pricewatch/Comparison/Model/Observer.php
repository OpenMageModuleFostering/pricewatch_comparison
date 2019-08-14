<?php

class Pricewatch_Comparison_Model_Observer {

    public function updateProduct(Varien_Event_Observer $observer) {
        $currentTimeStamp = time();
        $firstTime = Mage::getSingleton('core/session')->getFirstPricewatchProductUpdate();
        if(($currentTimeStamp-$firstTime) >= 0 && ($currentTimeStamp-$firstTime) <= 10){
            $bulkUpload = Mage::getModel('comparison/status')->getCollection()
                        ->addFieldToFilter('name', 'bulkrequired')
                        ->getFirstItem();
            $bulkUpload->setStatus(1)->save();
            $msg = 'The Pricewatch Comparison service requires that you initiate a manual sync. Please go <a href="'.Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/comparison").'">here</a> and click on the sync button.';
            Mage::getSingleton('adminhtml/session')->addNotice($msg);
        } else {
            $prod = $observer->getEvent()->getProduct();
            $catIds = $prod->getCategoryIds();
            $catId = '';
            if(!empty($catIds)){
                $catId = array_reverse($catIds)[0];
            }
            $cat = Mage::getModel('catalog/category')->load($catId);
            $brand = $prod->getAttributeText('manufacturer');
            
            $model = Mage::getModel('comparison/comparison')->load($prod->getId());
            $data['product_id'] = $prod->getId();
            $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $imgPath = $baseUrl.'catalog/product';
            $data['sku'] = $prod->getSku();
            $data['name'] = $prod->getName();
            $data['price'] = $prod->getPrice();
            $data['special_price'] = ($prod->getPrice() >$prod->getFinalPrice())?$prod->getFinalPrice():$prod->getPrice();
            $data['product_url'] = $prod->getProductUrl();
            $data['image_url'] = $imgPath.$prod->getImage();
            $data['category'] = $cat->getName();
            $data['brand'] = ($brand)?$brand:'';
            $data['description'] = $prod->getDescription();
            $data['status'] = 0;
            $data['api_time'] = $model->getApiTime();
            $data['created_time'] = $prod->getCreatedAt();
            $data['updated_time'] = $prod->getUpdatedAt();
            $model->setData($data);
            try{
                $model->save();
                $result = Mage::getModel('comparison/comparison')->processSingleUpload($prod->getId());
            } catch (Exception $e) {
                Mage::log($e->getMessage());
            }            
        }
        Mage::getSingleton('core/session')->setFirstPricewatchProductUpdate($currentTimeStamp);
    }
    
    public function setManualUpdate(Varien_Event_Observer $observer){
        $bulkUpload = Mage::getModel('comparison/status')->getCollection()
                    ->addFieldToFilter('name', 'bulkrequired')
                    ->getFirstItem();
        $bulkUpload->setStatus(1)->save();
        $msg = 'The Pricewatch Comparison service requires that you initiate a manual sync. Please go <a href="'.Mage::helper("adminhtml")->getUrl("adminhtml/system_config/edit/section/comparison").'">here</a> and click on the sync button.';
        Mage::getSingleton('adminhtml/session')->addNotice($msg);
    }
}