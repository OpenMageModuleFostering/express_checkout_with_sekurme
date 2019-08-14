<?php


class Sekurme_Block_Shortcut extends Mage_Core_Block_Template
{
    protected $_shouldRender = true;
    protected $_checkoutpage = 'sekurme/checkout';
    protected $_addcart = 'sekurme/checkout/addcart';

     
    public function _construct()
    {
        Mage::log('Sekurme_Block_Shortcut');   
       
    }
    protected function _beforeToHtml()
    {  
       $this->setcheckoutexpress($this->getUrl($this->_checkoutpage))->setMpAddCart('sekurme/checkout/addcart')
        ->setimgcheckout($this->getSkinUrl('images/sekurme/sekurme_logo.png'));  
    }
  
    protected function _toHtml()
    {
         return parent::_toHtml();
    }
    
   

}

?>