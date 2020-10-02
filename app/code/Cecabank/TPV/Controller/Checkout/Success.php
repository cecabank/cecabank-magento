<?php


namespace Cecabank\TPV\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface; 
use Cecabank\TPV\Controller\CecabankController;

class Success extends \Magento\Framework\App\Action\Action
{
  	protected $_session;
  	protected $_resultPageFactory;
  	protected $_storeManager;
  	protected $_cecabankController;

    public function __construct(Context $context, PageFactory $resultPageFactory, Session $session, StoreManagerInterface $storeManager, CecabankController $cecabankController){
	    $this->_session = $session;
	    $this->_resultPageFactory = $resultPageFactory;
    	$this->_storeManager = $storeManager;
    	$this->_cecabankController = $cecabankController;
    	
	    return parent::__construct($context);
    }
    
    public function execute()
    {
		$quote = $this->_session->getQuote();		
		$quote->setIsActive(0)->save();
		// $this->_session->clear();
		$this->_redirect("checkout/onepage/success/");
    }
    
}
