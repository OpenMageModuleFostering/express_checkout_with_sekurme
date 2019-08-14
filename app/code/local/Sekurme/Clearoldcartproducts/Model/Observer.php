<?php
class Sekurme_Clearoldcartproducts_Model_Observer extends Mage_Checkout_Model_Session
{
	public function loadCustomerQuote() {
	
		$customerQuote = Mage:: getModel('sales/quote')
	                  ->setStoreId(Mage:: app()->getStore()->getId())
	                  ->loadByCustomer(Mage:: getSingleton('customer/session')->getCustomerId());
	if ($customerQuote->getId() && $this->getQuoteId() != $customerQuote->getId()) {
	    foreach ($customerQuote->getAllItems() as $item) {
	    	$item->isDeleted(true);
	    if ($item->getHasChildren()) {
	    foreach ($item->getChildren() as $child) {
	    	$child->isDeleted(true);
	    }
	    }
	    }
	    	$customerQuote->collectTotals()->save();
	}
	else {
		$this->getQuote()->getBillingAddress();
		$this->getQuote()->getShippingAddress();
		$this->getQuote()->setCustomer(Mage:: getSingleton('customer/session')->getCustomer()) 
	                     ->setTotalsCollectedFlag(false)->collectTotals()->save();
	}
		return $this;
}

}
