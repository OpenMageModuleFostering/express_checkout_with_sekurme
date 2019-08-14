<?php
/**
 * SEKUR.me Payment Module
 * Created by Span Infotech, ravindra.singh@spanservices.com
 * http://www.spansystems.com
**/

class Sekurme_Block_General_Failed extends Mage_Core_Block_Template
{
	public function __construct()
    {
        parent::__construct();
        
        $this->setTemplate('sekurme/shortcut/failed.phtml');
	}
}
