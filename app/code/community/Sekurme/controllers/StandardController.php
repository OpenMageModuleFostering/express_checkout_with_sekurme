<?php
/**
 * SEKUR.me Payment Module
 * Created by SEKUR.me
 * http://www.sekur.me
**/
class Sekurme_StandardController extends Mage_Core_Controller_Front_Action
{
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }
    
     /**
     * @Clear shopping cart data
     */
    
     protected function ClearCart()
      {
        foreach( Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection() as $item ){
        Mage::getSingleton('checkout/cart')->removeItem( $item->getId() )->save();
        }
      }
    
    /**
     * @Clear customer CC data
     */  
    protected function removeCCInfo($etxnID)
    {
      
      $connection = Mage::getSingleton('core/resource')->getConnection('core_write');
      $connection->beginTransaction();

      $__condition = array($connection->quoteInto('eTxnID=?', $etxnID));
      $query = $connection->delete('customer_flat_quote_payment', $__condition);

      $connection->commit();
    
    }

    /**
     * @return Mage_Sekurme_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('sekurme/standard');
    }
    
    /**
     * When a customer fails payment from SEKUR.me.
    **/
    public function failedAction()
    {
	  //
		// Load layout
		//
		$this->loadLayout();
		$this->getLayout()->getBlock('content')->append($this->getLayout()->createBlock('sekurme/general_failed'));
		$this->renderLayout();
    }
    
    //
    // Changes the order status after payment is made
    //
    public function setOrderStatusAfterPayment()
    {
    
      //
  		// Load the payment object
  		//
  		
      $standard = Mage::getModel('sekurme/express');
        
  		//
  		// Load the order object from the get orderid parameter
  		//
  		
      $order = Mage::getModel('sales/order');
  		$order->loadByIncrementId($_GET['etxnId']);
        
  		//
  		// Set the status to the new payex status after payment
  		// and save to database
  		//
  		
      $order->addStatusToHistory($standard->getConfigData('order_status_after_payment'), '', true);
  		$order->setStatus($standard->getConfigData('order_status_after_payment'));
  		$order->save();
    
    }
    
    //
    // Remove from stock (if used)
    //
    public function removeFromStock()
    {
  		//
  		// Load the payment object
  		//
  		$standard = Mage::getModel('sekurme/express');
        
  		//
  		// Load the order object from the get orderid parameter
  		//
  		$order = Mage::getModel('sales/order');
  		$order->loadByIncrementId($_GET['etxnId']);
        
          $items = $order->getAllItems();
  	    if ($items) {
  			foreach($items as $item) {
  				$quantity = $item->getQtyOrdered();
  				$product_id = $item->getProductId();
  				$stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product_id);
  				$stock->setQty($stock->getQty()-$quantity);
  				$stock->save();
  				continue;                        
  			}
  		}            
    }
    
    public function confirmOrder($quoteId, $paymentData) {
        
          
          $_customer = Mage::getSingleton('customer/session')->getCustomer();   
          
          $cart = Mage::helper('checkout')->getQuote(); 
          
          //get default billing address from session
          $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();   
 
          //if we have a default billing addreess, try gathering its values into variables we need
          if ($customerAddressId){ 
              $address = Mage::getModel('customer/address')->load($customerAddressId);
              $street = $address->getStreet();
              $city = $address->getCity();
              $postcode = $address->getPostcode();
              $phoneNumber = $address->getTelephone();
              $countryId = $address->getCountryId();
              $regionId = $address->getRegionId();
          // otherwise, setup some custom entry values so we don't have a bunch of confusing un-descriptive orders in the backend
          }else{
              $address = 'No address';
              $street = 'No street';
              $city = 'No City';
              $postcode = 'No post code';
              $phoneNumber = 'No phone';
              $countryId = 'No country';
              $regionId = 'No region';        
          }
          
          //Start a new order quote and assign current customer to it.
          $quoteObj = Mage::getModel('sales/quote')->load($quoteId);  
          
          $quoteObj->assignCustomer($_customer);      
          
          $shippingMethod = $quoteObj->getShippingAddress();
          
          //Add address array to both billing AND shipping address objects.   
          $billingAddress = $quoteObj->getBillingAddress()->addData($addressData);
          $shippingAddress = $quoteObj->getShippingAddress()->addData($addressData);
          
          
          $items = $quoteObj->getAllItems();
          
          $itemArr = array();
          
          foreach($items as $itemVal)
          {
             $itemArr = $itemVal;
          }
         
          if(empty($itemArr))
          {
          
            ?>
            <script type="text/javascript">
            window.onload = function() {
                if(!window.location.hash) {
                    window.location = window.location + '#loaded';
                    window.location.reload();
                }
            }
            </script>
            
            <?php
            //Detect special conditions devices
            $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
            $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
            $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
            
            //do something with this information
            if( $iPod || $iPhone || $iPad ){
                $this->_redirectUrl(Mage::getBaseUrl());
            }else {
                exit;
            }
            
          }
          
          $shipping = $quoteObj->getShippingAddress()->getShippingAmount();

          $shippingAmount = $cart->getShippingAddress()->getData('shipping_amount');
          
          if(empty($shippingAmount))
          {
              $shippingAmount =  $shipping;
          }
          
          $taxAmount = $cart->getShippingAddress()->getData('tax_amount'); 
          
          $quoteObj->getShippingAddress()->setTaxAmount($taxAmount); //set the total of tax 
          $quoteObj->getShippingAddress()->setBaseTaxAmount($taxAmount); //set the total of tax 
          $quoteObj->getShippingAddress()->setShippingAmount($shippingAmount); //set the shipping price with taxes 
          $quoteObj->getShippingAddress()->setBaseShippingAmount($shippingAmount); //set the shipping price with taxes

          $tax_amount = $quoteObj->getShippingAddress()->getData('tax_amount');
          $shipping_amount = $quoteObj->getShippingAddress()->getData('shipping_amount');
          
          $quoteObj->reserveOrderId();
          $quoteObj->collectTotals(); 
            
          // set payment method
          $quotePaymentObj = $quoteObj->getPayment();
          $quotePaymentObj->setMethod($paymentData['method']);
          $quoteObj->setPayment($quotePaymentObj);
          
          // convert quote to order
          $convertQuoteObj = Mage::getSingleton('sales/convert_quote');
          $orderObj = $convertQuoteObj->addressToOrder($quoteObj->getShippingAddress());
          $orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);
          
          //Set the Shipping Amount To OrderObj
          $orderObj->setShippingAmount($shipping_amount);
          $orderObj->setBaseShippingAmount($shipping_amount); 
          
          //Set the Tax Amount To OrderObj 
          $orderObj->setTaxAmount($tax_amount);
          $orderObj->setBaseTaxAmount($tax_amount);
          
          // Assign Looged In Customer Address
          
          $orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getBillingAddress()));
          
          $orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getShippingAddress()));
        
          $orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
         
          if ($paymentData) {
          
          $orderObj->getPayment()->setCcNumber($paymentData['ccNumber']);
          $orderObj->getPayment()->setCcOwner($paymentData['ccOwner']);
          $orderObj->getPayment()->setCcType($paymentData['ccType']); 
          $orderObj->getPayment()->setCcExpMonth($paymentData['ccExpMonth']);
          $orderObj->getPayment()->setCcExpYear($paymentData['ccExpYear']);
          $orderObj->getPayment()->setCcLast4(substr($paymentData['ccNumber'],-4));
            
          }

          foreach ($items as $item) {
              //@var $item Mage_Sales_Model_Quote_Item
              $orderItem = $convertQuoteObj->itemToOrderItem($item);
              if ($item->getParentItem()) {
              
                  $orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
              
              }
              
              $orderObj->addItem($orderItem);
          
          }
          $orderObj->setCanShipPartiallyItem(false);
                
          $totalDue = $orderObj->getTotalDue();
      
          //$orderObj->sendNewOrderEmail();
          
          try {                                      
            
            $orderObj->place(); //calls _placePayment
            $orderObj->save();
            
          }catch (Exception $e){     
            
              Mage::register('payment_error', "bank");
              Mage::register('code', "Payment");
					    Mage::register('desc', "Order not places successfully, incorrect credit card details.");
              $this->failedAction();
  					  return;
          }
                                                   
          $orderObj->load(Mage::getSingleton('sales/order')->getLastOrderId());
          $lastOrderId = $orderObj->getIncrementId();
          
          $sessionQuote = Mage::getSingleton('checkout/session');
          
		  //prepare session to success page
	   		Mage::getSingleton("checkout/session")
	   			->setLastQuoteId($sessionQuote->getQuoteId())
                ->setLastSuccessQuoteId($sessionQuote->getQuoteId())
                ->setLastOrderId($orderObj->getId())
                ->setLastRealOrderId($orderObj->getIncrementId());

      
          /***************EMAIL*****************/
          $orderObj->loadByIncrementId($lastOrderId);
      
          try{
          
              //echo "Trying to send an  mail";
              //$emailed = $orderObj->sendNewOrderEmail();
              //$quote->delete();
          
          }catch (Exception $ex){
          
              //echo "Failed to send a confirmation mail";
          
          }
          /***************EMAIL*****************/
          
          return $lastOrderId;
      
      }
    
     public function createOrderAuthAction($quoteId, $paymentMethod, $paymentData) 
     {
        
        $_customer = Mage::getSingleton('customer/session')->getCustomer();   
          
        $cart = Mage::helper('checkout')->getQuote(); 
        
        //get default billing address from session
        $customerAddressId = Mage::getSingleton('customer/session')->getCustomer()->getDefaultBilling();   

        //if we have a default billing addreess, try gathering its values into variables we need
        if ($customerAddressId){ 
            $address = Mage::getModel('customer/address')->load($customerAddressId);
            $street = $address->getStreet();
            $city = $address->getCity();
            $postcode = $address->getPostcode();
            $phoneNumber = $address->getTelephone();
            $countryId = $address->getCountryId();
            $regionId = $address->getRegionId();
        // otherwise, setup some custom entry values so we don't have a bunch of confusing un-descriptive orders in the backend
        }else{
            $address = 'No address';
            $street = 'No street';
            $city = 'No City';
            $postcode = 'No post code';
            $phoneNumber = 'No phone';
            $countryId = 'No country';
            $regionId = 'No region';        
        }
        
        //Start a new order quote and assign current customer to it.
        $quoteObj = Mage::getModel('sales/quote')->load($quoteId);  
        
        $quoteObj->assignCustomer($_customer);      
        
        $shippingMethod = $quoteObj->getShippingAddress();
        
        //Add address array to both billing AND shipping address objects.   
        $billingAddress = $quoteObj->getBillingAddress()->addData($addressData);
        $shippingAddress = $quoteObj->getShippingAddress()->addData($addressData);
       
        $items = $quoteObj->getAllItems();
          
        $itemArr = array();
          
          foreach($items as $itemVal)
          {
             $itemArr = $itemVal;
          }
         
          if(empty($itemArr))
          {
          
            ?>
            <script type="text/javascript">
            window.onload = function() {
                if(!window.location.hash) {
                    window.location = window.location + '#loaded';
                    window.location.reload();
                }
            }
            </script>
            
            <?php
            //Detect special conditions devices
            $iPod    = stripos($_SERVER['HTTP_USER_AGENT'],"iPod");
            $iPhone  = stripos($_SERVER['HTTP_USER_AGENT'],"iPhone");
            $iPad    = stripos($_SERVER['HTTP_USER_AGENT'],"iPad");
            
            //do something with this information
            if( $iPod || $iPhone || $iPad ){
                $this->_redirectUrl(Mage::getBaseUrl());
            }else {
                exit;
            }
            
        }                  

        $quoteObj->reserveOrderId();

          // set payment method 
        $quotePaymentObj = $quoteObj->getPayment(); // Mage_Sales_Model_Quote_Payment
        $quotePaymentObj->setMethod($paymentMethod);
        $quoteObj->setPayment($quotePaymentObj);

        // convert quote to order
        $convertQuoteObj = Mage::getSingleton('sales/convert_quote');
        
        $orderObj = $convertQuoteObj->addressToOrder($quoteObj->getShippingAddress());
        
        $orderPaymentObj = $convertQuoteObj->paymentToOrderPayment($quotePaymentObj);

        // convert quote addresses
        $orderObj->setBillingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getBillingAddress()));
        $orderObj->setShippingAddress($convertQuoteObj->addressToOrderAddress($quoteObj->getShippingAddress()));

        // set payment options
        $orderObj->setPayment($convertQuoteObj->paymentToOrderPayment($quoteObj->getPayment()));
        if ($paymentData) {
          $orderObj->getPayment()->setCcNumber($paymentData['ccNumber']);
          //$orderObj->getPayment()->setCcOwner($paymentData['ccOwner']);
          $orderObj->getPayment()->setCcType($paymentData['ccType']); 
          $orderObj->getPayment()->setCcExpMonth($paymentData['ccExpMonth']);
          $orderObj->getPayment()->setCcExpYear($paymentData['ccExpYear']);
          $orderObj->getPayment()->setCcLast4(substr($paymentData['ccNumber'],-4)); 
        }
        // convert quote items
        foreach ($items as $item) {
            // @var $item Mage_Sales_Model_Quote_Item
            $orderItem = $convertQuoteObj->itemToOrderItem($item);

            $options = array();
        if ($productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct())) {

            $options = $productOptions;
        }
        if ($addOptions = $item->getOptionByCode('additional_options')) {
            $options['additional_options'] = unserialize($addOptions->getValue());
        }
        if ($options) {
            $orderItem->setProductOptions($options);
        }
            if ($item->getParentItem()) {
                $orderItem->setParentItem($orderObj->getItemByQuoteItemId($item->getParentItem()->getId()));
            }
            $orderObj->addItem($orderItem);
        }

        $orderObj->setCanShipPartiallyItem(false);
        
        try {
            $orderObj->place();
            $orderObj->save();
            
            $orderObj->load(Mage::getSingleton('sales/order')->getLastOrderId());
            $lastOrderId = $orderObj->getIncrementId();
              
            $sessionQuote = Mage::getSingleton('checkout/session');
              
    		  //prepare session to success page
    	   		Mage::getSingleton("checkout/session")
    	   			->setLastQuoteId($sessionQuote->getQuoteId())
                    ->setLastSuccessQuoteId($sessionQuote->getQuoteId())
                    ->setLastOrderId($orderObj->getId())
                    ->setLastRealOrderId($orderObj->getIncrementId());
    
          
              /***************EMAIL*****************/
              $orderObj->loadByIncrementId($lastOrderId);
          
              try{
              
                  //echo "Trying to send an  mail";
                  //$emailed = $orderObj->sendNewOrderEmail();
                  //$quote->delete();
              
              }catch (Exception $ex){
              
                  //echo "Failed to send a confirmation mail";
              
              }
            
        } catch (Exception $e){
            Mage::log($e->getMessage());
            Mage::log($e->getTraceAsString());
            $lastOrderId = $e->getMessage();
            $lastOrderId .='Payment_Error';
        }
        //$orderObj->save();
        //$orderObj->sendNewOrderEmail();
        //return $orderObj->getId();
        
        /***************EMAIL*****************/
        return $lastOrderId;  
      
     }
    
     // Decrypt string
     public function cc_decrypt($str)
      {
          $EncKey = "25c6c7dd";
          $str = mcrypt_decrypt(MCRYPT_DES, $EncKey, base64_decode($str), MCRYPT_MODE_ECB);
          # Strip padding out.
          $block = mcrypt_get_block_size('des', 'ecb');
          $pad = ord($str[($len = strlen($str)) - 1]);
          if ($pad && $pad < $block && preg_match(
          '/' . chr($pad) . '{' . $pad . '}$/', $str
          )
          ) {
          return substr($str, 0, strlen($str) - $pad);
          }
          return $str;
      }
      
     public function completeAction()
      
      {
        
      	$session = Mage::getSingleton('checkout/session');
    		
        $standard = Mage::getModel('sekurme/express');
    		
        $quoteId = $session->getQuoteId();
        
        $ETXNID = $_GET['eTxnID'];
        
        $selectquery = "SELECT * FROM sekurmeorderstatus WHERE eTxnID=".$ETXNID;
        
        $customerInfo = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($selectquery); 
        
        //Get customer data
        $customer = Mage::getModel('customer/customer'); 
       
        $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
        
        $customer->loadByEmail(trim($customerInfo['0']['email_id']));
        
        Mage::getSingleton('core/session', array('name' => 'frontend'));
        Mage::getSingleton('customer/session')->setCustomerAsLoggedIn($customer);

        
        $customAddress = Mage::getModel('customer/address');
        
        $customerId = $customer->getId();
        
        $ccquery = "SELECT * FROM customer_flat_quote_payment WHERE eTxnID=".$ETXNID;
    	  $ccValue = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($ccquery);
    	  
    	  $last4Digit = trim($this->cc_decrypt($ccValue['0']['cc_number_enc']));
    	  
    	  $last4Num = substr($last4Digit,-4,4);
    	  
    	  $expYear = trim($ccValue['0']['cc_exp_year']);
    	  
    	  $last2Num = substr($expYear,2);
    	  
    	  $paymentMethod = Mage::getStoreConfig('payment/sekurme/paymentmethod');
    	  
    	  $paymentData = array(
                'method' => $paymentMethod,
                'ccType' => trim($ccValue['0']['cc_type']),
                'ccOwner' => trim($ccValue['0']['cc_owner']),
                'ccNumber' => trim($this->cc_decrypt($ccValue['0']['cc_number_enc'])),
                'ccExpMonth' => trim($ccValue['0']['cc_exp_month']),
                'ccExpYear' => trim($ccValue['0']['cc_exp_year']),
                'CcLast4' => $last4Num
                
        );
        
        try{
            if($paymentMethod == 'authorizenet'){ 
               $getID = $this->createOrderAuthAction($quoteId, $paymentMethod, $paymentData);
            }else{
               $getID = $this->confirmOrder($quoteId, $paymentData); 
            }  
        
        }catch (Exception $e) {
        
            $this->failedAction();
  					return;
        
        }
       
        $this->removeCCInfo($ETXNID);

        $params = array('_query' => array('orderID'  => $getID));
        
        $this->_redirect('sekurme/standard/success/',$params);
      
      }
      
      public function successAction()
      {
       
       $getID = $_GET['orderID']; 
       $session = Mage::getSingleton('checkout/session');
       $session->setPayexStandardQuoteId($session->getQuoteId());
		   
       $order = Mage::getModel('sales/order');
       $order_id = Mage::getSingleton("checkout/session")->getLastRealOrderId();
	     $order->loadByIncrementId($order_id);  
      
       if (strpos($getID,'Payment_Error') !== false) {
         $msg = explode('Payment_Error',$getID);
       }

        //
        // Load the order number
        if (Mage::getSingleton('checkout/session')->getLastOrderId() && (isset($_GET["orderID"])) && Mage::getSingleton('checkout/session')->getLastOrderId() == $_GET["orderID"]) {
		  	$order->load(Mage::getSingleton('checkout/session')->getLastOrderId());
        
        } else {
		
      	 if (isset($_GET["orderID"])) {
  		
      		$order->loadByIncrementId((int)$_GET["orderID"]);
  		
      	 } 
         else 
         {
    				Mage::register('payment_error', "bank");
    				if(empty($msg[0]))
    				{
      		    Mage::register('desc', "Your purchase has not been completed using the selected card.
              <br/> Your bank declined our attempt to authorize your card.");
    				}else{
              Mage::register('desc', $msg[0]);
            }
            $this->failedAction();
    				return;
          }
        }
        
        //
        // Validate the order and send email confirmation if enabled
        if(!$order->getId()){
        
          Mage::register('payment_error', "order_error");
          if(empty($msg[0]))
          {
  		      Mage::register('desc', "There was a problem creating your order. Please try again, or contact customer service for more help.");
  				}else{
            Mage::register('desc', $msg[0]);
          }
          $this->failedAction();
  				return;
        }
        
    		// Send email order confirmation
    		// 
    		
    	  $order->load($order->getId());
    	  $order->setState(Mage_Sales_Model_Order::STATE_PROCESSING, true)->save();
		    //$order->sendOrderUpdateEmail();
		    
		    $order->save();
		    
        $this->ClearCart();
        
        $params = array('order_id'  => $order->getId());
        
        Mage::getSingleton('checkout/cart')->truncate();
       
        $this->_redirect('sales/order/view/',$params);
        
        //$this->_redirect('checkout/onepage/success');
       
    }
  
    function clean_up_response($response) {
		
      return simplexml_load_string(html_entity_decode($response));
	
    }
    
    
	
}
