<?php
class Pricewatch_Comparison_Block_Adminhtml_System_Config_Form_Help extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element) {
        
        return  'If you do not have a unique token and website key, you should go to <a href="http://www.pricewatch.com.ng" target="_blank">www.pricewatch.com.ng</a> to register for an account';
    }

}