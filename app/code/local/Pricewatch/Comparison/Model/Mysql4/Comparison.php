<?php

class Pricewatch_Comparison_Model_Mysql4_Comparison extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('comparison/comparison', 'product_id');
        $this->_isPkAutoIncrement = false;
    }
}