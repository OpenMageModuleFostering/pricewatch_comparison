<?php
class Pricewatch_Comparison_Block_Adminhtml_System_Config_Upload extends Mage_Adminhtml_Block_System_Config_Form_Field  implements Varien_Data_Form_Element_Renderer_Interface
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $buttonBlock = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');

        $params = array(
            'website' => $buttonBlock->getRequest()->getParam('website')
        );

        $data = array(
            'label'     => Mage::helper('adminhtml')->__('Sync Now'),
            'onclick'   => 'setLocation(\''.Mage::helper('adminhtml')->getUrl("comparison/adminhtml_comparison/upload", $params).'\')',
            'class'     => '',
        );

        $html = $buttonBlock->setData($data)->toHtml();

        return $html;
    }
}
