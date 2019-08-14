<?php
/**
 * SEKUR.me Payment Module
 * Created by Span Infotech, ravindra.singh@spanservices.com
 * http://www.spansystems.com
**/

$installer = $this;

$installer->startSetup();

$installer->run("

		delete from {$installer->getTable('core_resource')} where code = 'sekurme_setup';
		
		CREATE TABLE IF NOT EXISTS `customer_flat_quote_payment` (
    `cc_flat_id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'CC Flat Id',
    `eTxnID` int(11) DEFAULT NULL COMMENT 'eTxnID',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Created At',
    `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' COMMENT 'Updated At',
    `amount` float DEFAULT NULL COMMENT 'Amount',
    `currency` varchar(10) DEFAULT NULL COMMENT 'Currency',
    `billing_name` varchar(255) DEFAULT NULL COMMENT 'Billing Name',
    `billing_street` varchar(255) DEFAULT NULL COMMENT 'Billing Street',
    `billing_city` varchar(30) DEFAULT NULL COMMENT 'Billing City',
    `billing_state` varchar(30) DEFAULT NULL COMMENT 'Billing State',
    `billing_zip` int(10) DEFAULT NULL COMMENT 'Billing Zip',
    `billing_country` varchar(30) DEFAULT NULL COMMENT 'Billing Country',
    `cc_type` varchar(255) DEFAULT NULL COMMENT 'Cc Type',
    `cc_number_enc` varchar(255) DEFAULT NULL COMMENT 'Cc Number Enc',
    `cc_last4` varchar(255) DEFAULT NULL COMMENT 'Cc Last4',
    `cc_cid_enc` varchar(255) DEFAULT NULL COMMENT 'Cc Cid Enc',
    `cc_owner` varchar(255) DEFAULT NULL COMMENT 'Cc Owner',
    `cc_exp_month` smallint(5) unsigned DEFAULT '0' COMMENT 'Cc Exp Month',
    `cc_exp_year` smallint(5) unsigned DEFAULT '0' COMMENT 'Cc Exp Year',
    PRIMARY KEY (`cc_flat_id`)
   )ENGINE=InnoDB  DEFAULT CHARSET=utf8 COMMENT='Customer Flat Quote Payment' AUTO_INCREMENT=1 ;
	
	 CREATE TABLE IF NOT EXISTS `sekurmeorderstatus` (
    `Id` int(11) NOT NULL AUTO_INCREMENT,
    `eTxnID` varchar(30) NOT NULL,
    `tssID` varchar(30) NOT NULL,
    `companyID` varchar(50) NOT NULL,
    `storeID` varchar(50) NOT NULL,
    `customer_id` int(11) NOT NULL,
    `email_id` varchar(80) NOT NULL,
    `sekurID` varchar(50) NOT NULL,
    `cc_flat_id` int(11) NOT NULL,
    `paymentAction` varchar(30) NOT NULL,
    `payStatus` int(5) NOT NULL,
    `transactionID` int(10) NOT NULL,
    `status` int(5) NOT NULL COMMENT '0 = unpaid, 1 = paid',
    `statusMessage` varchar(20) NOT NULL,
    `errorCode` int(5) NOT NULL,
    `qr_URL` text NOT NULL,
    `date` datetime NOT NULL,
    PRIMARY KEY (`Id`)
  )ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
		
    ");


$installer->endSetup();