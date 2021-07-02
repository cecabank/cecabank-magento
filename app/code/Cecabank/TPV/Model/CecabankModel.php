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
			'Exponente' => '2',
			'Cifrado' => 'SHA2',
			'Idioma' => '1',
			'Pago_soportado' => 'SSL',
            'versionMod' => 'M-1.0.2'
		);
	}

    function getTitle(){
    	return $this->getConfigData('titlepay');
    }

    function getDescription(){
    	return $this->getConfigData('description');
    }

    function getImage(){
        $acquirer = $this->getConfigData('acquirer');
        $icon = $this->getConfigData('icon');
        if ($acquirer && $acquirer !== '0000554000' && $icon === "https://pgw.ceca.es/TPVvirtual/images/logo0000554000.gif") {
            $icon = "https://pgw.ceca.es/TPVvirtual/images/logo".$acquirer.".gif";
        }
    	return $icon;
    }

    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount) {
        $order = $payment->getOrder();
	    $transactionId = explode("-", $payment->getTransactionId())[0];
        if (!$transactionId) {
            $order->addStatusHistoryComment(__("Cecabank, fallo al intentar realizar devolución."), false);
			$order->save();
        }
        try {
            $config = $this->getClientConfig();
            $cecabank_client = new CecabankClient($config);
            $refund_data = array(
                'Num_operacion' => $order->getId(),
                'Referencia' => $transactionId,
		        'Importe' => $amount,
		        'TIPO_ANU' => 'P',
                'TipoMoneda' => $cecabank_client->getCurrencyCode($order->getOrderCurrencyCode())
            );
	        $cecabank_client->refund($refund_data);
            return $this;
        } catch ( Exception $e ) {
			$order->addStatusHistoryComment('Cecabank: Exception: '.$e->getMessage(), false);
			$order->save();
        }
        return $this;
    }
}
