<?php

class Pricewatch_Comparison_Model_Status extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('comparison/status');
    }

}