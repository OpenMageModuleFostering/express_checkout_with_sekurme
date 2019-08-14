<?php

class Sekurme_Block_General_Shortcut extends Mage_Core_Block_Template
{
    protected $_shouldRender = true;
    protected $_checkoutpage = 'sekurme/checkout/zipcode';
     
 
    protected function _beforeToHtml()
    {  
       $country = Mage::getModel('sekurme/express')->getConfigData('acc_origin');
       $cart = Mage::getModel('sekurme/express')->getConfigData('express_button_checkout');
       $side = Mage::getModel('sekurme/express')->getConfigData('express_button_checkout_sidebar');
       $prod = Mage::getModel('sekurme/express')->getConfigData('express_button_product');   
       $this->setcheckoutexpress($this->getUrl($this->_checkoutpage))->setCountry($country)
       ->setAllowedProduct($prod)->setAllowedCart($cart)->setAllowedSidebar($side)        
       ->setimgcheckoutBr($this->getSkinUrl('images/sekurme/sekurme_logo.png'))->setimgcheckoutAr($this->getSkinUrl('images/sekurme/sekurme_logo.png'));  
    }
  
    protected function _toHtml()
    {
         return parent::_toHtml();
    }
    
   

}

?>