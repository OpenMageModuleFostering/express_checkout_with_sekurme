<?php
/**
 * SEKUR.me Payment Module
 * Created by SEKUR.me
 * http://www.sekur.me
**/
require_once 'Mage/Checkout/controllers/CartController.php';
class Sekurme_Shopping_Checkout_CartController extends Mage_Checkout_CartController
{
    
    
    public function indexAction()
    {
        $cart = $this->_getCart();

        
        if ($cart->getQuote()->getItemsCount()) {
            $cart->init();
            $cart->save();

            if (!$this->_getQuote()->validateMinimumAmount()) {
                $warning = Mage::getStoreConfig('sales/minimum_order/description');
                $cart->getCheckoutSession()->addNotice($warning);
            }
        }

        // Compose array of messages to add
        $messages = array();
        foreach ($cart->getQuote()->getMessages() as $message) {
            if ($message) {
                $messages[] = $message;
            }
        }
        $cart->getCheckoutSession()->addUniqueMessages($messages);

        /**
         * if customer enteres shopping cart we should mark quote
         * as modified bc he can has checkout page in another window.
         */
        $this->_getSession()->setCartWasUpdated(true);

        Varien_Profiler::start(__METHOD__ . 'cart_display');
        $this
            ->loadLayout()
            ->_initLayoutMessages('checkout/session')
            ->_initLayoutMessages('catalog/session')
            ->getLayout()->getBlock('head')->setTitle($this->__('Shopping Cart'));
        $this->renderLayout();
        
        Varien_Profiler::stop(__METHOD__ . 'cart_display');
        
        
        
    }
    
    /* Initialize shipping information
     */
      public function estimatePostAction()
      {
          
          $country    = (string) $this->getRequest()->getParam('country_id');
          $postcode   = (string) $this->getRequest()->getParam('estimate_postcode');
          $regionId   = (string) $this->getRequest()->getParam('region_id');
          
          if(empty($regionId)){
          
            $regionInfo = "SELECT * FROM  tax_calculation_rate WHERE tax_postcode='".$postcode."*'";
      	    
            $regionVal = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($regionInfo);
            
            if(!empty($regionVal['0']['tax_region_id'])) 
            {                                 
              $regionId   = $regionVal['0']['tax_region_id'];
            }
          }
          
          $city  = (string) $this->getRequest()->getParam('estimate_city');
          
          $region = (string) $this->getRequest()->getParam('region');   
  
          $this->_getQuote()->getShippingAddress()
              ->setCountryId($country)
              ->setCity($city)
              ->setPostcode($postcode)
              ->setRegionId($regionId)
              ->setRegion($region)
              ->setCollectShippingRates(true);
          $this->_getQuote()->save();
          
          $this->estimateUpdatePostAction();
          
          $this->_goBack();
      }
     
    /*
    **true, display
    **false, don't display
    **/
    public function isDisplayMsg()
    {
        return false;
    }
    
    public function estimateUpdatePostAction()
    {
        $code = (string) $this->getRequest()->getParam('estimate_method');
        
        if (!empty($code)) {
            $this->_getQuote()->getShippingAddress()->setShippingMethod($code)/*->collectTotals()*/->save();
        }else{
        
        $shippingQuote = Mage::getSingleton('checkout/session')->getQuote();
            $shippingAddress = $shippingQuote->getShippingAddress();
            //Find if our shipping has been included.
            //$rates = $shippingAddress->collectShippingRates()->getGroupedAllShippingRates();
            $rates = $shippingAddress->getGroupedAllShippingRates();
            
            $qualifies = false;

                foreach ($rates as $carrier) {
                    foreach ($carrier as $rate) {
                         $shipDetail[] = array('code'=>$rate->getCode(), 'price'=>$rate->getPrice());
                         //print_r($rate->getData());
                    }

                }
            
            //$shippingInfo = min($shipDetail);
            
            $minVal = PHP_INT_MAX;
            $maxVal = 0;
            foreach ($shipDetail as $shipArray) {
                $minVal = min($minVal, $shipArray['price']);
                $maxVal = max($maxVal, $shipArray['price']);
            }
            
            $shippingPrice = $minVal; 
            
            foreach ($shipDetail as $inner) {
              if ($inner['price'] == $minVal) {
                $result = $inner['code'];
                // or to get the whole inner array:
                // $result = $inner;
                break;
              }
            }
            
            $shippingMethod = $result;
            
            $this->_getQuote()->getShippingAddress()->setShippingAmount($shippingPrice);
            $this->_getQuote()->getShippingAddress()->setBaseShippingAmount($shippingPrice);
            $this->_getQuote()->getShippingAddress()->setShippingMethod($shippingMethod)/*->collectTotals()*/->save();
           
            Mage::getSingleton('checkout/session')->resetCheckout();
         
        }
     $this->_goBack();
    }
}
?>