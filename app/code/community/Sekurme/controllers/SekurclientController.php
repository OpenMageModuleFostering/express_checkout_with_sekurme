<?php
/**
 * SEKUR.me Payment Module
 * Created by SEKUR.me
 * http://www.sekur.me
**/

class Sekurme_SekurclientController extends Mage_Core_Controller_Front_Action
{
    
    
    protected function _expireAjax()
    {
        if (!Mage::getSingleton('checkout/session')->getQuote()->hasItems()) {
            $this->getResponse()->setHeader('HTTP/1.1','403 Session Expired');
            exit;
        }
    }

    /**
     * @return Mage_Sekurme_Model_Standard
     */
    public function getStandard()
    {
        return Mage::getSingleton('sekurme/sekurclient');
    }
    /**
     * Get region collection
     * @param string $countryCode
     * @return array
     */
    public function getRegionCollection($regionCode, $countryCode)
    {
        $regionModel = Mage::getModel('directory/region')->loadByCode($regionCode, $countryCode);
        $regionId = $regionModel->getId();
        return $regionId;
    }
    
    public function cc_encrypt($str)
    {
        $EncKey = "25c6c7dd"; //For security
        $block = mcrypt_get_block_size('des', 'ecb');
        if (($pad = $block - (strlen($str) % $block)) < $block) {
        $str .= str_repeat(chr($pad), $pad);
        }
        return base64_encode(mcrypt_encrypt(MCRYPT_DES, $EncKey, $str, MCRYPT_MODE_ECB));
    }
    
    public function loginUser( $email, $password )
    {
        /** @var $session Mage_Customer_Model_Session */
        $session = Mage::getSingleton( 'customer/session' );
    
        try
        {
            $session->login( $email, $password );
            $session->setCustomerAsLoggedIn( $session->getCustomer() );
            return true;
        }
        catch( Exception $e )
        {
            return false;
        }
    }
    
    //sekurClient_ProcessPayment
    public function sekurClient_processPaymentAction()
    {
      
      // Get the response     
      $paymentbody = file_get_contents('php://input');
      
      // parse response data
      $xml = simplexml_load_string($paymentbody);
      
      // CC Data
      $AUTH_KEY_1 = $xml->AUTH_KEY_1;
      $AUTH_KEY_2 = $xml->AUTH_KEY_2;
      $eTxnID = $xml->eTxnID;
      $AMOUNT = $xml->AMOUNT;
      $CURRENCY = $xml->CURRENCY;
      $ACCOUNT_NUMBER = $this->cc_encrypt(trim($xml->ACCOUNT_NUMBER));
      $LAST_4DIGIT = "XXXX-XXXX-XXXX-".substr($xml->ACCOUNT_NUMBER,-4,4);
      $EXP_MONTH = substr($xml->EXP_MONTH,0,-4);
      $EXP_YEAR = substr($xml->EXP_MONTH,2);
      $CC_CID_ENC = $this->cc_encrypt(trim($xml->CVC));
      $NAME_ON_CARD = urldecode($xml->NAME_ON_CARD);
      $BILLING_NAME = $xml->BILLING_NAME;
      $BILLING_STREET = $xml->BILLING_STREET;
      $BILLING_CITY = $xml->BILLING_CITY;
      $BILLING_STATE = $xml->BILLING_STATE;
      $BILLING_ZIP = $xml->BILLING_ZIP;
      $BILLING_COUNTRY = $xml->BILLING_COUNTRY;
      
      $dbquery = "SELECT eTxnID FROM customer_flat_quote_payment WHERE eTxnID=".$eTxnID;
    	$processValue = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($dbquery);
      
      //Check the credit card type info
      $creditNum = trim($xml->ACCOUNT_NUMBER);
      
      switch ($creditNum[0]) {
                  case 3:
                      $type = "AE";
                      break;
                  case 4:
                      $type = "VI";
                      break;
                  case 5:
                      $type = "MC";
                      break;
                  case 6:
                      $type = "DI";
                      break;
                  default:
                      $type = "Others";
                      break; 
            }
      

      if($processValue['0']['eTxnID'] != $eTxnID){
        
        $write = Mage::getSingleton('core/resource')->getConnection('core_write');
    	  $write->query("INSERT INTO customer_flat_quote_payment(cc_flat_id,eTxnID,created_at,updated_at,amount,currency,billing_name,billing_street,
                        billing_city,billing_state,billing_zip,billing_country,
                        cc_type,cc_number_enc,cc_last4,cc_cid_enc,cc_owner,cc_exp_month,cc_exp_year)
                        VALUES('','$eTxnID','NOW()','','$AMOUNT','$CURRENCY','$BILLING_NAME','$BILLING_STREET',
                        '$BILLING_CITY','$BILLING_STATE','$BILLING_ZIP','$BILLING_COUNTRY',
                        '$type','$ACCOUNT_NUMBER','$LAST_4DIGIT','$CC_CID_ENC','$NAME_ON_CARD','$EXP_MONTH','$EXP_YEAR')");
        
        $selectquery = "SELECT cc_flat_id FROM customer_flat_quote_payment WHERE eTxnID=".$eTxnID;
    	  $ccValue = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($selectquery);
        
        $update = Mage::getSingleton('core/resource')->getConnection('core_write');
    	  $update->query('UPDATE sekurmeorderstatus set cc_flat_id = "'.$ccValue['0']['cc_flat_id']. '" where eTxnID = "'.$eTxnID .'"');
        
      }
      
        $fp = fopen('/var/www/html/furniture_store/processpayment.txt', 'w+');
        fwrite($fp, "Account Number=>".$ACCOUNT_NUMBER."\n");
        fwrite($fp, "Last Digit=>".$LAST_4DIGIT."\n");
        fwrite($fp, "Exp Month=>".$EXP_MONTH."\n");
        fwrite($fp, "Exp Year=>".$EXP_YEAR."\n");
        fwrite($fp, "Name On Card=>".$NAME_ON_CARD."\n");
        fwrite($fp, "Amount=>".$AMOUNT."\n");
        fclose($fp);
      
        // Send The Response
        $response = '<'.'?xml version="1.0" encoding="utf-8"?'.'>';
        $response.= '<SekurProcessVerificationResponse><ErrorCode>0</ErrorCode>';
        $response = $response.'<StatusMessage>Success</StatusMessage></SekurProcessVerificationResponse>';
        header("Content-type: text/xml; charset=utf-8");
        echo $response;
       
    }

    //sekurClient_ProcessPaymentVerificationAndShipping 
    public function sekurClient_processPaymentVerificationAndShippingAction()
    {
      
     // Get the response     
      $shippingbody = file_get_contents('php://input');
      
      // parse response data
      $shippingxml = simplexml_load_string($shippingbody);
      
      $session = Mage::getSingleton('core/session', array('name' => 'frontend'));
      
      // User Data
      $CompanyID = $shippingxml->CompanyID;
      $StoreID = $shippingxml->StoreID;
      $TSSID = $shippingxml->TSSID;
      $ETXNID = $shippingxml->ETXNID;                                  
      $First = $shippingxml->First;
      $Last = $shippingxml->Last;
      $eMail = $shippingxml->eMail;
      $AuthorizationStatus = $shippingxml->AuthorizationStatus;
      $PayStatus = $shippingxml->PayStatus;
      $PGReturnCode = $shippingxml->PGReturnCode;
      $PGReturnMsg = $shippingxml->PGReturnMsg;
      $PGAuthCode = $shippingxml->PGAuthCode;
      $PGTransactionID = $shippingxml->PGTransactionID;
      $PaymentAmount  = $shippingxml->PaymentAmount;
      $Phone = $shippingxml->Phone;
      $SekurID = $shippingxml->SekurID;
      
      // Billing Address Details
      $Bill_First = $shippingxml->Bill_First;
      $Bill_Last = $shippingxml->Bill_Last;
      $Bill_Address = $shippingxml->Bill_Address;
      $Bill_City  = $shippingxml->Bill_City;
      $Bill_State = $shippingxml->Bill_State;
      $Bill_Zip = $shippingxml->Bill_Zip;
      $Bill_Country = $shippingxml->Bill_Country;
      
      if($Bill_Country == 'USA')
    	{
        $Bill_Country_Id = 'US';
        
      }elseif($Bill_Country == 'CAN')
      {
        $Bill_Country_Id = 'CA';
      }
      
      // Shipping Address Details
      $Ship_First = $shippingxml->Ship_First;
      $Ship_Last = $shippingxml->Ship_Last;
      $Ship_Address  = $shippingxml->Ship_Address;
      $Ship_City = $shippingxml->Ship_City;
      $Ship_State = $shippingxml->Ship_State;
      $Ship_Zip = $shippingxml->Ship_Zip;
      $Ship_Country = $shippingxml->Ship_Country;
      
      
      if($Ship_Country == 'USA')
    	{
        $Ship_Country_Id = 'US';
        
      }elseif($Ship_Country == 'CAN')
      {
        $Ship_Country_Id = 'CA';
      }
      
      // Bill Pay Details
      $BILL_PAY_1 = $shippingxml->BILL_PAY_1;
      $BILL_PAY_2  = $shippingxml->BILL_PAY_2;
      
      $Bill_Region_Id = $this->getRegionCollection($Bill_State, $Bill_Country_Id);
      $Ship_Region_Id = $this->getRegionCollection($Ship_State, $Ship_Country_Id);

      // Create customer if not exit
      $customer_email = $eMail; 
      $customer_fname = $First; 
      $customer_lname = $Last;
      $passwordLength = 10;  // the lenght of autogenerated password
      
      $customer = Mage::getModel('customer/customer');
      $customer->setWebsiteId(Mage::app()->getWebsite()->getId());
      $customer->loadByEmail($customer_email);
      $customer_id = $customer->getId();
      
     
      /*
      * Check if the email exist on the system.
      * If YES,  it will not create a user account. 
      */
      
      
      if(!$customer->getId()) {
      
         //setting data such as email, firstname, lastname, and password 
      
        $email = $customer->setEmail($customer_email); 
        $customer->setFirstname($customer_fname);
        $customer->setLastname($customer_lname);
        $password = $customer->setPassword($customer->generatePassword($passwordLength));
      
        try{
            //the save the data and send the new account email.
            $customer->save();
            $customer->setConfirmation(null);
            $customer->save();
            $customer->setStatus(1); 
            
            //Get the current customer ID & set the billing address
            $billaddress = Mage::getModel("customer/address");
            $billaddress->setCustomerId($customer->getId());
            $billaddress->setFirstname($Bill_First);
            $billaddress->setLastname($Bill_Last);
            $billaddress->setCountryId($Bill_Country_Id); //Country code here
            $billaddress->setStreet($Bill_Address);
            $billaddress->setPostcode($Bill_Zip);
            $billaddress->setCity($Bill_City);
            $billaddress->setState($Bill_Region_Id);
            $billaddress->setTelephone($Phone);
            $billaddress->setIsDefaultBilling('1');
            $billaddress->save();
            
            //Get the current customer ID & set the billing address
            $shipaddress = Mage::getModel("customer/address");
            $shipaddress->setCustomerId($customer->getId());
            $shipaddress->setFirstname($Ship_First);
            $shipaddress->setLastname($Ship_Last);
            $shipaddress->setCountryId($Ship_Country_Id); //Country code here
            $shipaddress->setStreet($Ship_Address);
            $shipaddress->setPostcode($Ship_Zip);
            $shipaddress->setCity($Ship_City);
            $shipaddress->setState($Ship_Region_Id);
            $shipaddress->setTelephone($Phone);
            $shipaddress->setIsDefaultShipping('1');
            $shipaddress->save();
            
        }
        
        catch(Exception $ex){
        
            Mage::log($e->getMessage());
        }
        
      }else{
      
        $selectquery = "SELECT * FROM customer_flat_quote_payment WHERE eTxnID=".$ETXNID;
    	  $billValue = Mage::getSingleton('core/resource')->getConnection('core_read')->fetchAll($selectquery);
      
        if($billValue['0']['billing_country'] =='USA')
        {
           $billValue['0']['billing_countryId'] = 'US';
        }
        
        $customer = Mage::getModel('customer/customer')->load($customer->getId());
        
        $dataBilling = array(
            'firstname'  => $Bill_First,
            'lastname'   => $Bill_Last,
            'street'     => array($Bill_Address),
            'city'       => $Bill_City,
            'region'     => $Bill_State,
            'region_id'  => $Bill_Region_Id,
            'postcode'   => $Bill_Zip,
            'country_id' => $Bill_Country_Id,
            'telephone'  => $Phone,
        );
        
        $dataShipping = array(
            'firstname'  => $Ship_First,
            'lastname'   => $Ship_Last,
            'street'     => array($Ship_Address),
            'city'       => $Ship_City,
            'region'     => $Ship_State,
            'region_id'  => $Ship_Region_Id,
            'postcode'   => $Ship_Zip,
            'country_id' => $Ship_Country_Id,
            'telephone'  => $Phone,
        );
        $customerAddress = Mage::getModel('customer/address'); 
        // Check for the shipping address
        if ($defaultShippingId = $customer->getDefaultShipping()){
            $customerAddress->load($defaultShippingId); 
        } else {   
             $customerAddress
                ->setCustomerId($customer->getId())
                ->setIsDefaultShipping('1')
                ->setSaveInAddressBook('1');   
        
             $customer->addAddress($customerAddress);
        }            
        
        $customerAddress1 = Mage::getModel('customer/address');
        // check for the bliing address
        if ($defaultBillingId = $customer->getDefaultBilling()){
            $customerAddress1->load($defaultBillingId); 
        } else {   
             $customerAddress1
                ->setCustomerId($customer->getId())
                ->setIsDefaultBilling('1')
                ->setSaveInAddressBook('1');   
        
             $customer->addAddress($customerAddress);
        }
        
        
        try {
             $customerAddress->addData($dataShipping)->save();
             $customerAddress1->addData($dataBilling)->save();
                           
        } catch(Exception $e){

        } 
        
      }
      
      $update = Mage::getSingleton('core/resource')->getConnection('core_write');
    	$update->query('UPDATE sekurmeorderstatus set companyID = "'.$CompanyID. '",'.'storeID = "'.$StoreID.'",'.'customer_id = "'.$customer_id.'",'.'email_id ="'.$eMail.'",
                     '.'sekurID = "'.$SekurID.'",'.'payStatus = "'.$PayStatus.'",'.'transactionID = "'.$PGTransactionID.'" where eTxnID = "'. $ETXNID . '"');
        
         
         
        // Send The Response
        $shipresponse = '<'.'?xml version="1.0" encoding="utf-8"?'.'>';
        $shipresponse.= '<SekurProcessVerificationResponse><ErrorCode>0</ErrorCode>';
        $shipresponse = $shipresponse.'<StatusMessage>Success</StatusMessage></SekurProcessVerificationResponse>';
        header("Content-type: text/xml; charset=utf-8");
        
        echo $shipresponse;
  
      
    }

    
    function clean_up_response($response) {
		return simplexml_load_string(html_entity_decode($response));
	}
}
