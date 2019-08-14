<?php

class Pricewatch_Comparison_Model_Review extends Mage_Core_Model_Abstract
{
    public function getProductReview($productId){
        $response = array();
        $storeId = Mage::app()->getStore()->getId();
        if($productId){
            $summaryData = Mage::getModel('review/review_summary')
                        ->setStoreId($storeId)
                        ->load($productId);
                        
            //$response['reviews_count'] = $summaryData['reviews_count'];
            $response['rating_summary'] = $summaryData['rating_summary'];
            
            $allratings = $this->_getAllRattings();
            
            $reviews = Mage::getModel('review/review')
                     ->getResourceCollection()
                     ->addEntityFilter('product', $productId)
                     ->addStatusFilter(Mage_Review_Model_Review::STATUS_APPROVED)
                     ->setDateOrder()
                     ->addRateVotes();
            foreach($reviews as $review){
                
                // $votesCollection = Mage::getModel('rating/rating_option_vote')
                    // ->getResourceCollection()
                    // ->setReviewFilter($review['review_id'])
                    // ->load();
                    
                $data = array();
                $data['title'] = $review['title'];
                $data['detail'] = $review['detail'];
                $data['nickname'] = $review['nickname'];
                $data['rating'] = @$allratings[$review['review_id']];
                $data['created_at'] = $review['created_at'];
                $response['reviews'][] = $data;
            }
        }
        return $response;
    }
    
    private function _getAllRattings(){
        $resource = Mage::getSingleton('core/resource');
        $readConnection = $resource->getConnection('core_read');
        $query = 'SELECT review_id, avg(percent) as avg FROM ' .$resource->getTableName("rating_option_vote"). ' group by review_id';
        $results = $readConnection->fetchAll($query);
        $allrating = array();
        foreach($results as $result){
            $allrating[$result['review_id']] = $result['avg'];
        }
        
        return $allrating;
    }
}