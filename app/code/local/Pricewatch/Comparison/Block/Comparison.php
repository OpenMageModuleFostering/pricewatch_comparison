<?php
class Pricewatch_Comparison_Block_Comparison extends Mage_Core_Block_Template
{
	public function _prepareLayout()
    {
		return parent::_prepareLayout();
    }
    
     public function getComparison()     
     { 
        if (!$this->hasData('comparison')) {
            $this->setData('comparison', Mage::registry('comparison'));
        }
        return $this->getData('comparison');
        
    }
}