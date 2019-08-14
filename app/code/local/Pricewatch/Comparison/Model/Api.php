<?php

class Pricewatch_Comparison_Model_Api extends Mage_Api_Model_Resource_Abstract {

    public function getProducts() {
        $error = array();
        try {
            if(Mage::helper('comparison')->isEnabled()){
                $endpoint   = Mage::helper('comparison')->getEndPoint();
                $products   = Mage::getModel('comparison/comparison')->getProductJSON();
                $form_args  = Mage::getModel('comparison/comparison')->getFormArg();
                $form_args['products'] = $products;
                
                return json_encode($form_args);
            } else {
                $error['code'] = 01;
                $error['msg'] = 'pricewatch is not enabled';
                
                return json_encode($error);
            }
            
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage());
        } catch (Exception $e) {
            Mage::log($e->getMessage());
        }
        
        return json_encode($productArray);
    }
    
    public function getSalesStatistic(){
        $return = array();
        $salesDetails = Mage::getResourceModel('reports/order_collection')->calculateSales()->load()->getFirstItem();
        $orders = Mage::getModel('sales/order')->getCollection();

        $return['lifetime'] = (int)str_replace(',', '', $salesDetails['lifetime']);;
        //$return['average'] = Mage::helper('core')->currency($salesDetails['average'], true, false);
        $return['totalorders'] = $orders->count();
        
        return json_encode($return);
    }

    public function getPopularProducts(){
        
        $collection = Mage::getModel('comparison/comparison')->getYearlyBestSellerProducts();
        $productArray = array();
        
        if($collection->count()){
            foreach($collection as $prod){
                $product = Mage::getModel('catalog/product')->load($prod['entity_id']);
                if($product->getId()){
                    $prodData = array();
                    $prodData['sku']            = $product->getSku();
                    $prodData['soldqty']        = $prod['sold_quantity'];
                    $productArray[]             = $prodData;
                } else {
                    $prodData['sku']            = $prod['sku'];
                    $prodData['soldqty']        = $prod['sold_quantity'];
                    $productArray[]             = $prodData;
                }
            }
        }
        
        return json_encode($productArray);
    }
    
    public function getPaymentGateways(){
        $payments = Mage::getSingleton('payment/config')->getActiveMethods();
        $methods = array();

        foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            $methods[] = array(
                'title'   => $paymentTitle,
                'code' => $paymentCode,
            );
        }

        return json_encode($methods);
    }
}