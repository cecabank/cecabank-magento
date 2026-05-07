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
		$numOperacion = $this->getRequest()->getParam('Num_operacion');
		if (empty($numOperacion) || !ctype_digit((string) $numOperacion)) {
			$this->_redirect('checkout/cart');
			return;
		}

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$quote = $objectManager->create('\Magento\Quote\Model\Quote')->load($numOperacion);

		if (!$quote->getId()) {
			$this->_redirect('checkout/cart');
			return;
		}

		if (!$this->isQuoteOwnedByCurrentUser($quote, $objectManager)) {
			$this->_redirect('checkout/cart');
			return;
		}

		$order = $objectManager->create('\Magento\Sales\Model\Order')->load($quote->getReservedOrderId(), 'increment_id');
		$this->_session->setLastSuccessQuoteId($quote->getId());
		$this->_session->setLastQuoteId($quote->getId());
		$this->_session->setLastOrderId($order->getId());
		$this->_session->setLastRealOrderId($order->getIncrementId());
		$this->_redirect("checkout/onepage/success/");
    }

    private function isQuoteOwnedByCurrentUser($quote, $objectManager)
    {
		$sessionQuoteId = $this->_session->getQuoteId();
		if ($sessionQuoteId && (int) $sessionQuoteId === (int) $quote->getId()) {
			return true;
		}

		$customerSession = $objectManager->get('\Magento\Customer\Model\Session');
		if ($customerSession->isLoggedIn()) {
			$customerId = $customerSession->getCustomerId();
			$quoteCustomerId = $quote->getCustomerId();
			if ($customerId && $quoteCustomerId && (int) $customerId === (int) $quoteCustomerId) {
				return true;
			}
		}

		return false;
    }
    
}
