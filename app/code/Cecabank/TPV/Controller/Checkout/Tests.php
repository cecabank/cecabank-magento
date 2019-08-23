<?php

namespace Cecabank\TPV\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Cecabank;

class Tests extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;

    public function __construct(Context $context)
    {
    	parent::__construct($context);
    }
    
    public function execute()
    {
    	die(0);
    }
}