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
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$quote = $objectManager->create('\Magento\Quote\Model\Quote')->load($_GET['Num_operacion']);
		$order = $objectManager->create('\Magento\Sales\Model\Order')->load($quote->getReservedOrderId(), 'increment_id');
		$this->_session->setLastSuccessQuoteId($quote->getId());
		$this->_session->setLastQuoteId($quote->getId());
		$this->_session->setLastOrderId($order->getId());
		$this->_session->setLastRealOrderId($order->getIncrementId());
		$this->_redirect("checkout/onepage/success/");
    }
    
}
