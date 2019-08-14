<?php
/**
 * SEKUR.me Payment Module
 * Created by SEKUR.me
 * http://www.sekur.me
**/

class Sekurme_Model_Express extends Mage_Payment_Model_Method_Abstract
{   
    
    const PAYMENT_TYPE_AUTH = 'PHONE';
    const PAYMENT_TYPE_SALE = 'SALE';
    protected $_code = 'sekurme';
    protected $_formBlockType = 'sekurme/express_shortcutmid';
    
    protected $_isGateway                   = true;
    protected $_canOrder                    = true;
    protected $_canAuthorize                = true;
    protected $_canCapture                  = true;
    protected $_canCapturePartial           = true;
    protected $_canRefund                   = true;
    protected $_canRefundInvoicePartial     = true;
    protected $_canVoid                     = true;
    protected $_canUseInternal              = true;
    protected $_canUseCheckout              = true;
    protected $_canUseForMultishipping      = true;
    protected $_canFetchTransactionInfo     = true;
    protected $_canCreateBillingAgreement   = true;
    protected $_canReviewPayment            = true;
    
  
    
    protected function _construct()
    {
        $this->_init('sekurme/express');
    }
    
     /**
     * Get Sekurme session namespace
     *
     * @return Mage_Sekurme_Model_Session
     */
    public function getSession()
    {
        return Mage::getSingleton('sekurme/session');
    }

    /**
     * Get checkout session namespace
     *
     * @return Mage_Checkout_Model_Session
     */
    public function getCheckout()
    {
        return Mage::getSingleton('checkout/session');
    }
    
    /**
     * Get current quote
     *
     * @return Mage_Sales_Model_Quote
     */
    public function getQuote()
    {
        return $this->getCheckout()->getQuote();
    }
    
    public function getCheckoutCart()
    {
        return Mage::getSingleton('checkout/cart');
    }
    
    /**
     * Using internal pages for input payment data
     *
     * @return bool
     */
    public function canUseInternal()
    {
        return false;
    }

    /**
     * Using for multiple shipping address
     *
     * @return bool
     */
    public function canUseForMultishipping()
    {
        return false;
    }
    
    public function createFormBlock($name)
    {
        $block = $this->getLayout()->createBlock('sekurme/shortcut_form', $name)
            ->setMethod('sekurme_express')
            ->setPayment($this->getPayment())
            ->setTemplate('sekurme/express/form.phtml');

        return $block;
    }
    
    public function validate()
    {
        parent::validate();
        return $this;
    }

    public function onOrderValidate(Mage_Sales_Model_Order_Payment $payment)
    {
       return $this;
    }

    public function onInvoiceCreate(Mage_Sales_Model_Invoice_Payment $payment)
    {        
       
    }
    
    public function getOrderPlaceRedirectUrl() {
        
        return Mage::getUrl('sekurme/checkout/redirect', array('_secure' => true));
    }
    
    /**
     * Get country collection
     * @return array
     */
    
    public function getCountryCollection()
    {
        $countryCollection = Mage::getModel('directory/country_api')->items();
        return $countryCollection;
    }

    /**
     * Get region collection
     * @param string $countryCode
     * @return array
     */
    public function getRegionCollection($countryCode)
    {
        $regionCollection = Mage::getModel('directory/region_api')->items($countryCode);
        return $regionCollection;
    }
    
    
    public function getShippingEstimate($productId,$productQty,$countryId,$postcode ) {

          $quote = Mage::getModel('sales/quote')->setStoreId(Mage::app()->getStore('default')->getId());
          $_product = Mage::getModel('catalog/product')->load($productId);
        
          $_product->getStockItem()->setUseConfigManageStock(false);
          $_product->getStockItem()->setManageStock(false);
      
          $quote->addProduct($_product, $productQty);
          $quote->getShippingAddress()->setCountryId($countryId)->setPostcode($postcode); 
          $quote->getShippingAddress()->collectTotals();
          $quote->getShippingAddress()->setCollectShippingRates(true);
          $quote->getShippingAddress()->collectShippingRates();
      
          $_rates = $quote->getShippingAddress()->getShippingRatesCollection();
          
          $shippingRates = array();
          foreach ($_rates as $_rate):
                  if($_rate->getPrice() > 0) {
                      $shippingRates[] =  array("Title" => $_rate->getMethodTitle(), "Price" => $_rate->getPrice());
                  }
          endforeach;
      
          return $shippingRates;
      
      } 
      
      //Calculate Sales Tax based on Zip Code
      public function getSalesTax()
      {
            $shippingAddress = $this->getCheckoutCart()->getQuote()->getShippingAddress();
            
            $shippingPostcode = $shippingAddress->getData("postcode");
            
            $shippingCountryID = $shippingAddress->getData('country_id');
            
            $zipcode = $shippingPostcode;
            
            $country = $shippingCountryID;
            
            // Update the cart's quote.
            
            
            $shippingAddress->setCountryId($country)->setPostcode($zipcode)->setCollectShippingrates(true);
            
            $this->getCheckoutCart()->save();
            
            // Find if our shipping has been included.
            $rates = $shippingAddress->collectShippingRates()->getGroupedAllShippingRates();
            
            $qualifies = false;
            foreach ($rates as $carrier) {
                foreach ($carrier as $rate) {
                    if ($rate->getMethod() === 'whiteglove') {
                        $qualifies = true;
                        break;
                    }
                }
            }

      }
    
      // Calculate Shipping Cost  
      public function getShippingCost()
        
        {
          
          // Total Object
          $totals = $this->getCheckout()->getQuote()->getTotals();
        
          // Calculate Shipping Cost
          $totalKeys = array('shipping');
  
          foreach ($totalKeys as $totalKey) {
              
              if (isset($totals[$totalKey])) 
              
               $shippingcost = $totals[$totalKey]->getData('value');
               
          }
          
          if(empty($shippingcost)){
  
            $shippingQuote = Mage::getSingleton('checkout/session')->getQuote();
            $shippingAddress = $shippingQuote->getShippingAddress();
            //Find if our shipping has been included.
            $rates = $shippingAddress->getGroupedAllShippingRates();
            
            $qualifies = false;

                foreach ($rates as $carrier) {
                    foreach ($carrier as $rate) {
                         $shipDetail[] = array('code'=>$rate->getCode(), 'price'=>$rate->getPrice());

                    }

                }
            
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

                break;
              }
            }
            
            $shippingMethod = $result;
            
            $shippingcost = $shippingPrice;
            
            $shippingQuote->getShippingAddress()->setShippingAmount($shippingPrice);
            $shippingQuote->getShippingAddress()->setBaseShippingAmount($shippingPrice);
            $shippingQuote->getShippingAddress()->setShippingMethod($shippingMethod)/*->collectTotals()*/->save();
            
          }
  
          return $shippingcost;
        
        }
      
      //Calculate Amount
      
      public function getTotalAmount()
      {
        
        // Caculate total items in cart
        $totalItemsInCart = $this->getCheckoutCart()->getItemsCount(); 
        
        //Total Object
        $totals = $this->getCheckout()->getQuote()->getTotals();

        //Subtotal value 
        $subtotal = $totals["subtotal"]->getValue();
        
        // Grandtotal value
        $grandtotal = $totals["grand_total"]->getValue();
        
        //Discount value if applied
        if(isset($totals['discount']) && $totals['discount']->getValue()) {
        
          $discount = $totals['discount']->getValue();
         
        } else {
        
          $discount = "";
        
        }
        
        //Tax value if present
        if(isset($totals['tax']) && $totals['tax']->getValue()) {
        
          $tax = $totals['tax']->getValue(); 
        
        } else {
        
          $tax = "";
        }
        
        $shippingcost = $this->getShippingCost();

        
        $totalAmount = $grandtotal;
        
        return $totalAmount;
      
      }
    
    
      //Get the order increment id
      public function getLastIncrementedOrderId()
      {
      
          $orders = Mage::getModel('sales/order')->getCollection()->setOrder('created_at','DESC')->setPageSize(1)->setCurPage(1);
          
          $orderId = $orders->getFirstItem()->getEntityId();
          
          $inc_order_id = Mage::getModel('sales/order')->load($orderId);
         
          $last_order_id = $inc_order_id['increment_id']+'1';
         
          return $last_order_id;
      }
      
    
      // Initialize StartTransaction Request
      public function initalize() {
    
       error_reporting('0');
       
       //Total Amount
       $totalAmount = $this->getTotalAmount();
       
       //Get the config data 
       $sekurmeurl = htmlspecialchars($this->getConfigData('sekurmeurl'))."MT/SekurServer_StartTransaction";
       $companyID =  htmlspecialchars($this->getConfigData('merchantnumber'));
       $storeAuth =  htmlspecialchars($this->getConfigData('md5key'));
       $storeID =    htmlspecialchars($this->getConfigData('storeid'));

       $quote_id = time();
       
       // Check for HTTP & HTTPS protocol
       
       $phoneURL = Mage::getUrl('sekurme/standard/complete');
       
       //echo $sekurmeurl; 
       
       $input_xml = '<SekurStartTransactionRequest>
                      <SekurAction>10</SekurAction>
                      <CompanyID>'.$companyID.'</CompanyID>
                      <StoreID>'.$storeID.'</StoreID> 
                      <StoreAuth>'.$storeAuth.'</StoreAuth>
                      <Amount>'.$totalAmount.'</Amount>
                      <UserID/>
                      <EtxnId>'.$quote_id.'</EtxnId>
                      <BILL_PAY_1/>
                      <BILL_PAY_2/>
                      <BILL_PAY_3/>
                      <BILL_PAY_4/>
                      <BILL_PAY_5/>
                      <BILL_PAY_6/>
                      <BILL_PAY_7/>
                      <BILL_PAY_8/>
                      <BILL_PAY_9/>
                      <APP_DATA_DESTINATION>MOBILE</APP_DATA_DESTINATION>
                      <APP_DATA_CONTROL>1CLICK</APP_DATA_CONTROL>
                      <APP_DATA>'.$phoneURL.'</APP_DATA>
                      </SekurStartTransactionRequest>
                      ';
       
       $service_url = $sekurmeurl;

       $curl = curl_init($service_url);

        //echo '<pre>'; print_r($curl_post_data);    
     
        curl_setopt($curl, CURLOPT_URL,$service_url);
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); 
        curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));
        curl_setopt($curl, CURLOPT_POSTFIELDS, $input_xml);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);

        $curl_response = curl_exec($curl);
        
        if ($curl_response === false) {

            $info = curl_getinfo($curl);

            curl_close($curl);

            //echo 'Error occured during connection with SEKUR.me server.';
        }

        curl_close($curl);
        
        $resvalues = explode(" ", strip_tags($curl_response));
        
        $errorCode = trim(strip_tags($resvalues["2"]));
        $statusMsg = trim(strip_tags($resvalues["10"]));
        $eTxnID =  trim(strip_tags($resvalues["8"]));
        $tssID =  trim(strip_tags($resvalues["6"]));
        $qr_URL = trim(strip_tags($resvalues["4"]));
        
        $data = array(
            'id' => '',
            'eTxnID'=> "'".trim(strip_tags($resvalues["8"]))."'",
            'tssID' => "'".trim(strip_tags($resvalues["6"]))."'",
            'paymentAction' => "'".$this->getConfigData('paymentaction')."'",
            'status' =>  '0',
            'statusMessage' => "'".trim(strip_tags($resvalues["10"]))."'",
            'errorCode' => "'".trim(strip_tags($resvalues["2"]))."'",
            'qr_URL' => "'".trim(strip_tags($resvalues["4"]))."'",
            'date' => 'NOW()',
        );
        
        
        $query = "SELECT eTxnID FROM sekurmeorderstatus WHERE eTxnID=".$data[eTxnID];
    	  $dataValue = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($query);
    	  
        
        if($dataValue['0']['eTxnID'] != $eTxnID){
        
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
    	  $write->query("INSERT INTO sekurmeorderstatus (Id,eTxnID,tssID,companyID,storeID,customer_id,email_id,sekurID,cc_flat_id,
                        paymentAction,payStatus,transactionID,status,statusMessage,errorCode,qr_URL,date)
                        VALUES('',$data[eTxnID],$data[tssID],'NULL','NULL','NULL','NULL','NULL','NULL',
                        $data[paymentAction],'NULL','NULL',$data[status],$data[statusMessage],$data[errorCode],$data[qr_URL],$data[date])");
        
        }else{
        
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
    	  $write->query('UPDATE sekurmeorderstatus set tssID = "'.$tssID. '",'.'qr_URL = "'.$qr_URL.'" where eTxnID = "'. $eTxnID . '"');
        
        }
        
        return $curl_response;
        
      }
      
                  
}


?>