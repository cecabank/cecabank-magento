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
    	$order = $this->_session->getLastRealOrder();
    	$order_id = $order->getId();
    	$not_processing=(!$this->_session->getData("Cecabank".$order_id) || $this->_session->getData("Cecabank".$order_id) < 10);     	
    	
    	if($order_id && $not_processing){ 
			$order_items = $order->getAllItems();
			$amount = floatval($order->getTotalDue());
			$client = array(
				"name" => $order->getCustomerFirstname()." ".$order->getCustomerLastname(),
				"email" => $order->getCustomerEmail()
			);
			$products = "";
			
	    	foreach($order_items as $item){
	    		if($item->getQtyOrdered()%1!=0)
	    			$count = $item->getQtyOrdered();
	    		else
	    			$count = intval($item->getQtyOrdered());
	    			
				$products .= $item->getName()."x".$count." / ";
	    	}

	    	$try = $this->_session->getData("Cecabank".$order_id);
	    	
	    	if($try == null) {
				$try = 0;
			}

    		$try++;    		
    			
	    	$this->_session->setData("Cecabank".$order_id, $try);
    		
    		if($try == 1){
    			$order->setState('new')->setStatus('pending_payment')->save();
    			$order->addStatusHistoryComment(__("Cecabank, redireccionado al pago."), false)
	    			->setIsCustomerNotified(false)
	    			->save();
    		}

    		$resultPage = $this->_resultPageFactory->create();
    		$resultPage->getConfig()->getTitle()->prepend(__("Redireccionando..."));
    		$resultPage->getLayout()->initMessages();
    		$resultPage->getLayout()->getBlock('cecabank_checkout_redirect')->setTry($try);
    		
    		if($try < 10){    			    	
    			$order_id = str_pad($order_id.$try, 12, "0", STR_PAD_LEFT);
					    	
		    	$cecabank_client = $this->_cecabankController->generateFields($order_id, $order, $products, $amount);
	    		$resultPage->getLayout()->getBlock('cecabank_checkout_redirect')->setCecabankClient($cecabank_client);
    		}
    		
    		return $resultPage;
    	
    	} else {
			$this->_redirect("checkout");
		}
    }
    
}
