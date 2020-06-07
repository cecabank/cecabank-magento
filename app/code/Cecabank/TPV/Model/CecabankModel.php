<?php

namespace Cecabank\TPV\Model;

use Cecabank\TPV\Controller\CecabankController;
use Cecabank\TPV\lib\CecabankClient;


/**
 * Gateway payment method model
 */
class CecabankModel extends \Magento\Payment\Model\Method\AbstractMethod
{

    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'cecabank';

    /**
     * Availability option
     *
     * @var bool
     */
    protected $_isOffline = false;
    
    protected $_isGateway = true;

    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;protected $_resultPageFactory;

    protected $_cecabankController;

    public function setController(CecabankController $cecabankController)
    {
    	$this->_cecabankController = $cecabankController;
    }
    
    public function getConfigData($field, $storeId=null){
    	return parent::getConfigData($field, $storeId);
    }

	public function getClientConfig() {
		$environment = 'test';
		if ($this->getConfigData('sandbox') != '1') {
			$environment = 'real';
		}
		return array(
			'Environment' => $environment,
			'MerchantID' => $this->getConfigData('merchant'),
			'AcquirerBIN' => $this->getConfigData('acquirer'),
			'TerminalID' => $this->getConfigData('terminal'),
			'ClaveCifrado' => $this->getConfigData('secretkey'),
			'TipoMoneda' => $this->getConfigData('currency'),
			'Exponente' => '2',
			'Cifrado' => 'SHA2',
			'Idioma' => '1',
			'Pago_soportado' => 'SSL'
		);
	}

    function getTitle(){
    	return $this->getConfigData('titlepay');
    }

    function getDescription(){
    	return $this->getConfigData('description');
    }

    function getImage(){
    	return "https://pgw.ceca.es/TPVvirtual/images/logo".$this->getConfigData('acquirer').".gif";
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $order = $payment->getOrder();
	    $transactionId = explode("-", $payment->getTransactionId())[0];
        if (!$transactionId) {
            $order->addStatusHistoryComment(__("Cecabank, fallo al intentar realizar devolución."), false);
			$order->save();
        }
        try {
            $refund_data = array(
                'Num_operacion' => $order->getId(),
                'Referencia' => $transactionId,
		        'Importe' => $amount,
		        'TIPO_ANU' => 'P'
            );

            $config = $this->getClientConfig();
            $cecabank_client = new CecabankClient($config);
	        $cecabank_client->refund($refund_data);
            return $this;
        } catch ( Exception $e ) {
			$order->addStatusHistoryComment('Cecabank: Exception: '.$e->getMessage(), false);
			$order->save();
        }
        return $this;
    }
}
