<?php
/**
 * SEKUR.me Payment Module
 * Created by Span Infotech, ravindra.singh@spanservices.com
 * http://www.spansystems.com
**/

class Sekurme_Model_System_Config_Source_Paymentaction
{

    public function toOptionArray()
    {
        return array( 
            array('value'=>1, 'label'=>Mage::helper('adminhtml')->__('Phone')),
            array('value'=>2, 'label'=>Mage::helper('adminhtml')->__('Authorize')),
            array('value'=>3, 'label'=>Mage::helper('adminhtml')->__('AuhorizeAndCapture')),
        );
    }

}
