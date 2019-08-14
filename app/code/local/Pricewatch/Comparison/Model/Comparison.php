<?php

class Pricewatch_Comparison_Model_Comparison extends Mage_Core_Model_Abstract
{
    private $_logfile = '';
    
    public function _construct()
    {
        parent::_construct();
        $this->_init('comparison/comparison');
    }
    
    public function getFormArg(){
        
        if(Mage::helper('comparison')->isEnabled()){
            $WEBSITE_URL    = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
            $MERCHANT_TOKEN = Mage::helper('comparison')->getMerchantToken();
            $MAC_KEY_WEB    = Mage::helper('comparison')->getMacKey();
            $updateTime     = time();
            $transactionID  = rand(10000,99999).$updateTime;
            $notifyURL      = $WEBSITE_URL.'comparison/notify/index';
            // $hash           = $transactionID.$WEBSITE_URL.$MAC_KEY_WEB.$notifyURL.$MERCHANT_TOKEN.$updateTime;
            $hash           = $transactionID.$WEBSITE_URL.$MAC_KEY_WEB.$MERCHANT_TOKEN.$updateTime;
            $hash           = hash("sha512", $hash);

            $form_args = array(
                    'website' 		=> $WEBSITE_URL,
                    'transaction_id'=> $transactionID,
                    'reference_key' => $MERCHANT_TOKEN,
                    // 'notify_url' 	=> $notifyURL,
                    'request_time' 	=> $updateTime,
                    'hash'          => $hash
            );
            return $form_args;
        }
    }
    
    public function getProductJSON(){
        $productArray = array();
        $_productCollection = $this->getProductCollection();
        $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
        $imgPath = $baseUrl.'catalog/product';
        if($_productCollection->count()){
            foreach($_productCollection as $k=>$product){
                $prodData = array();
                $prod = Mage::getModel('catalog/product')->load($product->getId());
                $catIds = $prod->getCategoryIds();
                $catId = '';
                if(!empty($catIds)){
                    $catId = array_reverse($catIds)[0];
                }
                $cat = Mage::getModel('catalog/category')->load($catId);
                $brand = $product->getAttributeText('manufacturer');

                $prodData['sku']            = $product->getSku();
                $prodData['name']           = $product->getName();
                $prodData['price']          = $prod->getPrice();
                if($prod->getPrice() > $prod->getFinalPrice()){
                    $prodData['special_price']  = $prod->getFinalPrice();
                }
                $prodData['url']            = $prod->getProductUrl();
                $prodData['image']          = $imgPath.$prod->getImage();
                $prodData['brand']          = ($brand)?$brand:'';
                $data['description']        = $prod->getDescription();
                $prodData['category']       = $cat->getName();
                $prodData['message']        = '';
                
                $productArray[]             = $prodData;
            }
        }
        return json_encode($productArray);
    }
    
    public function getProductCollection(){
        $_productCollection = Mage::getModel('catalog/product')->getCollection();
        $_productCollection->addAttributeToSelect('*')
                ->joinField('qty',
                            'cataloginventory/stock_item',
                            'qty',
                            'product_id=entity_id',
                            '{{table}}.stock_id=1',
                            'left')
                ->addAttributeToFilter('qty', array("gt" => 0))
                ->addFieldToFilter('visibility', array(
                           Mage_Catalog_Model_Product_Visibility::VISIBILITY_BOTH,
                           Mage_Catalog_Model_Product_Visibility::VISIBILITY_IN_CATALOG
                ))
                ->getSelect()
                ;        
       
        return $_productCollection;
    }
    
    public function uploadProducts($products){
        if(Mage::helper('comparison')->isEnabled()){
            if(!empty(json_decode($products))){
                $currentTime = date("Y-m-d h:i:s");
                $endpoint   = Mage::helper('comparison')->getEndPoint();
                $form_args  = $this->getFormArg();
                $form_args['products'] = $products;
                $form_args['popular_products'] = $this->_getPopularProducts();
                $form_args['sales_statistics'] = $this->_getSalesStatistics();
                $form_args['payment_gateways'] = $this->_getPaymentGateways();
                
                $this->_logfile = 'pricewatch_upload_'.$form_args['transaction_id'].'.txt';
                Mage::log('Uploaded Data from Pricewatch Plugin', null, $this->_logfile);
                Mage::log($form_args, null, $this->_logfile);
                
                $ch = curl_init($endpoint);
                curl_setopt( $ch, CURLOPT_POST, 1);
                curl_setopt( $ch, CURLOPT_POSTFIELDS, $form_args);
                curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 1);
                curl_setopt( $ch, CURLOPT_HEADER, 0);
                curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1);

                $response = curl_exec($ch);
                Mage::log('Response from Pricewatch Server', null, $this->_logfile);
                Mage::log($response, null, $this->_logfile);
                $response = json_decode($response, true);
                Mage::log($response, null, $this->_logfile);
                $prods = json_decode($products, true);
                if($response['tranid'] == $form_args['transaction_id']){
                    if($response['code']==00){
                        foreach($prods as $prod){
                            $comp = $this->loadProductbySku($prod['sku']);
                            if($comp->getProductId()){
                                $comp->setTransactionId($form_args['transaction_id']);
                                $comp->setApiTime($currentTime);
                                $comp->setStatus(1);
                                $comp->save();
                            }
                        }
                        $status = Mage::getModel('comparison/status')->getCollection()
                                    ->addFieldToFilter('name', 'bulkupload')
                                    ->getFirstItem();
                        $status->setUpdatedTime($currentTime)->save();

                        if(isset($response['msg'])){
                            return $this->uploadResponse('00', $response['msg']);
                        } else {
                            return $this->uploadResponse('00');
                        }
                    } else {
                        foreach($prods as $prod){
                            $comp = $this->loadProductbySku($prod['sku']);
                            if($comp->getProductId()){
                                $comp->setTransactionId($form_args['transaction_id']);
                                $comp->setApiTime($currentTime);
                                $comp->setStatus(2);
                                $comp->save();
                            }
                        }
                        if(isset($response['msg'])){
                            return $this->uploadResponse('04', $response['msg']);
                        } else {
                            return $this->uploadResponse('04');
                        }
                    }
                } else {
                    if(isset($response['msg'])){
                        return $this->uploadResponse('03', $response['msg']);
                    } else {
                        return $this->uploadResponse('03');
                    }
                }
                
            } else {
                if(isset($response['msg'])){
                    return $this->uploadResponse('02', $response['msg']);
                } else {
                    return $this->uploadResponse('02');
                }
            }
        } else {
            if(isset($response['msg'])){
                return $this->uploadResponse('01', $response['msg']);
            } else {
                return $this->uploadResponse('01');
            }
        }
    }
    
    public function prepareProductsForUpload(){
       try {
            $_productCollection = $this->getProductCollection();
            $baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);
            $imgPath = $baseUrl.'catalog/product';
            if($_productCollection->count()){
                foreach($_productCollection as $k=>$product){
                    $data = array();
                    $prod = Mage::getModel('catalog/product')->load($product->getId());
                    $catIds = $prod->getCategoryIds();
                    $catId = '';
                    if(!empty($catIds)){
                        $catId = array_reverse($catIds)[0];
                    }
                    $cat = Mage::getModel('catalog/category')->load($catId);
                    
                    $model = Mage::getModel('comparison/comparison');
                    $currentTime = time();
                    $brand = $prod->getAttributeText('manufacturer');
                    
                    $data['product_id'] = $product->getId();
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
                    $data['created_time'] = $prod->getCreatedAt();
                    $data['updated_time'] = $prod->getUpdatedAt();
                    $model->setData($data);
                    $model->save();
                }
            }
        } catch (Exception $e) {            
            return $e->getMessage();
        }
            
        return 'TRUE';
    }
    
    public function getDailyBestSellerProducts(){
        $storeId = (int) Mage::app()->getStore()->getId();
        $date = Mage::getModel('core/date')->date('Y-m-d');
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setPageSize(10);
 
        $collection->getSelect()
            ->join(
                array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_daily')),
                "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId}  AND aggregation.period = '{$date}'",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )
            ->group('e.entity_id')
            ->order(array('sold_quantity DESC', 'e.created_at'));
        
       return $collection;
    }

    public function getMonthlyBestSellerProducts(){
        $storeId = (int) Mage::app()->getStore()->getId();
        $date = new Zend_Date();
        $toDate = $date->setDay(1)->getDate()->get('Y-MM-dd');
        $fromDate = $date->subMonth(1)->getDate()->get('Y-MM-dd');
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setPageSize(10);
 
        $collection->getSelect()
            ->join(
                array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_monthly')),
                "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId}  AND aggregation.period BETWEEN '{$fromDate}' AND '{$toDate}'",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )
            ->group('e.entity_id')
            ->order(array('sold_quantity DESC', 'e.created_at'));
        
        return $collection;
    }

    public function getYearlyBestSellerProducts(){
        $storeId = (int) Mage::app()->getStore()->getId();
        $date = new Zend_Date();
        $year = $date->getDate()->get('Y-01-01');
        $collection = Mage::getResourceModel('catalog/product_collection')
            ->setPageSize(10);
 
        $collection->getSelect()
            ->join(
                array('aggregation' => $collection->getResource()->getTable('sales/bestsellers_aggregated_yearly')),
                "e.entity_id = aggregation.product_id AND aggregation.store_id={$storeId}  AND aggregation.period = '{$year}'",
                array('SUM(aggregation.qty_ordered) AS sold_quantity')
            )
            ->group('e.entity_id')
            ->order(array('sold_quantity DESC', 'e.created_at'));
        
        return $collection;
    }
    
    public function uploadResponse($code, $msg=''){
        $codes = $this->getResponseCodes();
        $response = array();
        if($code == '00'){
            $response['success'] = true;
            $response['msg'] = ($msg=='')?@$codes[$code]:$msg;
        } else {
            $response['success'] = false;
            $response['error'] = true;
            $response['msg'] = ($msg=='')?@$codes[$code]:$msg;
        }
        
        return $response;
        
    }
	public function getResponseCodes() {
        return array(
            '00'=>'Your products have been uploaded to Pricewatch'
            ,'01'=>'Pricewatch is not enabled/configured'
            ,'02'=>'No product details'
            ,'03'=>'Your products have been uploaded to Pricewatch but not Confirmed'
            ,'04'=>'Pricewatch product upload error, please try again'
            ,'05'=>'No product for upload'
        );
    }
    
    public function loadProductbySku($sku){
        if(trim($sku) != ''){
            $product = $this->getCollection()
                    ->addFieldToFilter('sku', trim($sku))
                    ->getFirstItem();
            return $product;
       }
    }
    
    public function processBulkUpload(){
        $status = Mage::getModel('comparison/status')->getCollection()
                    ->addFieldToFilter('name', 'bulkupload')
                    ->getFirstItem();
        $lastUpdateTime = $status->getUpdatedTime();
        $collection = $this->getCollection();
                    
        return $this->_processUpload($collection);
    }

    public function processSingleUpload($productId){
        $status = Mage::getModel('comparison/status')->getCollection()
                    ->addFieldToFilter('name', 'bulkupload')
                    ->getFirstItem();
        $lastUpdateTime = $status->getUpdatedTime();
        $collection = $this->getCollection()
                    ->addFieldToFilter('updated_time', array('gt' => $lastUpdateTime))
                    ->addFieldToFilter('product_id', $productId);
        return $this->_processUpload($collection);
    }
    
    private function _processUpload($collection){
        $productArray = array();
        $WEBSITE_URL    = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
        if($collection->count()){
            foreach ($collection as $product){
                $prodData = array();
                $prodData['sku']            = $product->getSku();
                $prodData['name']           = $product->getName();
                $prodData['price']          = $product->getPrice();
                if($product->getPrice() > $product->getFinalPrice()){
                $prodData['special_price']  = $product->getFinalPrice();
                }
                $prodData['url']            = $product->getProductUrl();
                $prodData['image']          = $product->getImageUrl();
                $prodData['brand']          = $product->getBrand();
                $prodData['description']    = $product->getdescription();
                $prodData['category']       = $product->getCategory();
                $prodData['review_url']     = $WEBSITE_URL.'index.php/review/product/list/id/'.$product->getProductId();
                $prodData['product_review'] = $this->_getProductReviews($product->getProductId());
                $prodData['message']        = $product->getMessage();
                
                $productArray[]             = $prodData;           
            }
            return $this->uploadProducts(json_encode($productArray));
        } else {
            return $this->uploadResponse('05');
        }
    }
    
    private function _getProductReviews($productId){

        return Mage::getModel('comparison/review')->getProductReview($productId);
    }
    private function _getPopularProducts(){

        return Mage::getModel('comparison/api')->getPopularProducts(false);
    }
    private function _getSalesStatistics(){
        
        return Mage::getModel('comparison/api')->getSalesStatistic(false);        
    }
    private function _getPaymentGateways(){

        return Mage::getModel('comparison/api')->getPaymentGateways(false);
    }
}