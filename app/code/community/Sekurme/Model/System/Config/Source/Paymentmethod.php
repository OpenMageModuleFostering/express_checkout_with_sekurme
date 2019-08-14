<?php
/**
 * SEKUR.me Payment Module
 * Created by SEKUR.me
 * http://www.sekur.me
**/

class Sekurme_Model_System_Config_Source_Paymentmethod 
{

    
    public function getActivPaymentMethods()
    {
       $payments = Mage::getSingleton('payment/config')->getActiveMethods();
       $methods = array(array('value'=>'', 'label'=>Mage::helper('adminhtml')->__('--Select Payment Mode--')));
       foreach ($payments as $paymentCode=>$paymentModel) {
            $paymentTitle = Mage::getStoreConfig('payment/'.$paymentCode.'/title');
            if(!preg_match("/(?:paypal)/", $paymentCode, $matches)){
            $methods[$paymentCode] = array(
                'label'   => $paymentTitle,
                'value' => $paymentCode,
            );
           }
        }
        return $methods;
    }
    
    public function toOptionArray()
    {
        return $this->getActivPaymentMethods();
    }

}
