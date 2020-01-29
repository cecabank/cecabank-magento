<?php

namespace Cecabank\TPV\Controller\Checkout;

use Magento\Catalog\Model\ProductRepository;
use Magento\Checkout\Model\Cart;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Data\Form\FormKey;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

use Cecabank\TPV\Controller\CecabankController;
use Cecabank\TPV\lib\CecabankClient;

class Notify extends \Magento\Framework\App\Action\Action
{
    protected $_resultPageFactory;
    protected $_cecabankController;
    protected $_session;
    protected $_invoiceService;
    protected $_invoiceSender;
    protected $_cart;
    protected $_formKey;
    protected $_productRepository;

    public function __construct(Context $context, Session $session, PageFactory $resultPageFactory, StoreManagerInterface $storeManager, CecabankController $cecabankController, InvoiceService $invoiceService, InvoiceSender $invoiceSender, Cart $cart, ProductRepository $productRepository, FormKey $formKey)
    {
    	$this->_session = $session;
    	$this->_invoiceSender = $invoiceSender;
    	$this->_invoiceService = $invoiceService;
    	$this->_cecabankController = $cecabankController;
    	$this->_resultPageFactory = $resultPageFactory;
    	$this->_cart = $cart;
    	$this->_formKey = $formKey;
    	$this->_productRepository = $productRepository;
    	parent::__construct($context);
    }
    
    public function execute()
    {
    	$resultPage = $this->_resultPageFactory->create();
    	$resultPage->getConfig()->getTitle()->append(__("Notificacion")." - Cecabank");
    	$resultPage->getLayout()->initMessages();
    	
    	$data = null;
    	$is_post = false;
    	
        if (!empty($_POST)) //URL RESP. ONLINE
        { 
			$data = $_POST;
			$is_post = true;
        }
        else if (!empty($_GET)) //URL RESP. ONLINE
        { 
			$data = $_GET;
			$is_post = false;
        }

        if($data != null && $is_post){
        	$resultValidation = $this->validateOrder($data);
			$order = $resultValidation[1];
			$order_id = null;
			if ($order) {
				$order_id = $order->getId();
			}
        	
        	switch ($resultValidation[0]) {
        		case 1:
					echo 'Error';
					break;
				default: 
					$this->confirmOrder($order, $data);
					echo $resultValidation[2];
					break;
			}
			die();
        } else if (!$is_post && $data != null && $data['cancel']) {
			$resultPage->getLayout()->getBlock('cecabank_checkout_notify')->setExito(1);
			$resultPage->getLayout()->getBlock('cecabank_checkout_notify')->setURL($this->_cecabankController->get_baseURL());
		} else{
			$resultPage->getLayout()->getBlock('cecabank_checkout_notify')->setExito(0);
			$resultPage->getLayout()->getBlock('cecabank_checkout_notify')->setURL($this->_cecabankController->get_baseURL());
        }
    	return $resultPage;
    }
    
    private function validateOrder($data){
		$config = $this->_cecabankController->get_client_config();
		$cecabank_client = new CecabankClient($config);
		try {
			$cecabank_client->checkTransaction($data);
		} catch (\Exception $e) {
			return array(1, null, 'Ha ocurrido un error con el pago');
		}

		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$order = $objectManager->create('\Magento\Sales\Model\Order')->load($data['Num_operacion']);
		return array(0, $order, $cecabank_client->successCode());
    }

	private function confirmOrder($order, $data){
		try {			
			$invoice = $this->_invoiceService->prepareInvoice($order);
			$invoice->register();
			$invoice->pay();
			$invoice->setTransactionId($data['Referencia']);
			$invoice->save();

			$payment = $order->getPayment();
			$payment->setLastTransId($data['Referencia']);
			$payment->setTransactionId($data['Referencia']);
			$payment->setParentTransactionId($payment->getTransactionId());
			$transaction = $payment->addTransaction(Transaction::TYPE_CAPTURE, null, true, "");
			$payment->save();
			$transaction->save();

			if(!@$this->_invoiceSender->send($invoice)) {
				$order->addStatusHistoryComment(__("Cecabank, fallo al enviar la factura."), false);
			}
			$order->addStatusHistoryComment(__("Cecabank, factura generada."), false)->save();
			
			$status = $this->_cecabankController->get_status();
    		$order->setState('new')->setStatus($status)->save();
    		$order->addStatusHistoryComment(__("Cecabank, pago existoso."), false)
	    		->setIsCustomerNotified(false)
				->save();
		} catch (Exception $e) {
			$order->addStatusHistoryComment('Cecabank: Exception: '.$e->getMessage(), false);
			$order->save();
		}
	}

	public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }
	
}
