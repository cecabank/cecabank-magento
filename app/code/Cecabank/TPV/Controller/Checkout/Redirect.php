<?php


namespace Cecabank\TPV\Controller\Checkout;

use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Checkout\Model\Session;
use Magento\Store\Model\StoreManagerInterface; 
use Cecabank\TPV\Controller\CecabankController;

class Redirect extends \Magento\Framework\App\Action\Action
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
		$quote_id = $quote->getId(); 		
		
		$not_processing=(!$this->_session->getData("Cecabank".$quote_id) || $this->_session->getData("Cecabank".$quote_id) < 10);

		if (!$quote->getCustomerId()) {
			$quote->setCustomerIsGuest(1);
			$quote->setCustomerEmail($quote->getBillingAddress()->getEmail());
			$quote->save();
		}  	
		
    	if($quote_id && $not_processing){ 
			$quote_items = $quote->getAllItems();
			$amount = floatval($quote->getGrandTotal());
			$client = array(
				"name" => $quote->getCustomerFirstname()." ".$quote->getCustomerLastname(),
				"email" => $quote->getCustomerEmail()
			);
			$products = "";
			
			foreach($quote_items as $item){
	    		if($item->getQtyOrdered()%1!=0)
	    			$count = $item->getQtyOrdered();
	    		else
	    			$count = intval($item->getQtyOrdered());
	    			
				$products .= $item->getName()."x".$count." / ";
	    	}

	    	$try = $this->_session->getData("Cecabank".$quote_id);
	    	
	    	if($try == null) {
				$try = 0;
			}

    		$try++;    		
    			
	    	$this->_session->setData("Cecabank".$quote_id, $try);

    		$resultPage = $this->_resultPageFactory->create();
    		$resultPage->getConfig()->getTitle()->prepend(__("Redireccionando..."));
    		$resultPage->getLayout()->initMessages();
    		$resultPage->getLayout()->getBlock('cecabank_checkout_redirect')->setTry($try);
    		
    		if($try < 10){    			    	
    			$quote_id = str_pad($quote_id.$try, 12, "0", STR_PAD_LEFT);
					    	
				$cecabank_client = $this->_cecabankController->generateFields($quote_id, $quote, $products, $amount);
	    		$resultPage->getLayout()->getBlock('cecabank_checkout_redirect')->setCecabankClient($cecabank_client);
    		}
    		
    		return $resultPage;
    	
    	} else {
			$this->_redirect("checkout");
		}
    }
    
}
