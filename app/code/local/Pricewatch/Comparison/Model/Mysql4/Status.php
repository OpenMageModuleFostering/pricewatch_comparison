<?php

class Pricewatch_Comparison_Model_Mysql4_Status extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct()
    {    
        $this->_init('comparison/status', 'id');
        $this->_isPkAutoIncrement = false;
    }
}