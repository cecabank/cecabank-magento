<?php
namespace Cecabank\TPV\Controller;

use Cecabank\TPV\Model\CecabankModel;
use Magento\Store\Model\StoreManagerInterface;
use Cecabank\TPV\lib\CecabankClient;

class CecabankController extends \Magento\Framework\App\Action\Action
{
	protected $_baseURL;
    protected $_enabled;
    protected $_sandbox;
    protected $_merchant;
    protected $_acquirer;
    protected $_terminal;
    protected $_secretkey;
    protected $_currency;
    protected $_icon;
    protected $_status;
   

    public function __construct(CecabankModel $model, StoreManagerInterface $storeManager) {
    	$this->_baseURL = $storeManager->getStore()->getBaseUrl();
    	
    	$this->_enabled = $model->getConfigData('enabled');
    	$this->_sandbox = $model->getConfigData('sandbox');
    	$this->_merchant = $model->getConfigData('merchant');
    	$this->_acquirer = $model->getConfigData('acquirer');
    	$this->_terminal = $model->getConfigData('terminal');
    	$this->_secretkey = $model->getConfigData('secretkey');
    	$this->_currency = $model->getConfigData('currency');
    	$this->_status = $model->getConfigData('status');
    	$this->_icon = $model->getConfigData('icon');
    }

	/**
	 * _enabled
	 * @return unkown
	 */
	public function get_enabled(){
		return $this->_enabled;
	}

	/**
	 * _sandbox
	 * @return unkown
	 */
	public function get_sandbox(){
		return $this->_sandbox;
	}

	/**
	 * _merchant
	 * @return unkown
	 */
	public function get_merchant(){
		return $this->_merchant;
	}

	/**
	 * _acquirer
	 * @return unkown
	 */
	public function get_acquirer(){
		return $this->_acquirer;
	}

	/**
	 * _terminal
	 * @return unkown
	 */
	public function get_terminal(){
		return $this->_terminal;
	}

	/**
	 * _secretkey
	 * @return unkown
	 */
	public function get_secretkey(){
		return $this->_secretkey;
	}

	/**
	 * _currency
	 * @return unkown
	 */
	public function get_currency(){
		return $this->_currency;
	}

	/**
	 * _icon
	 * @return unkown
	 */
	public function get_icon(){
		return $this->_icon;
	}

	/**
	 * _status
	 * @return unkown
	 */
	public function get_status(){
		return $this->_status;
	}
	
	/**
	 * _baseURL
	 * @return unkown
	 */
	public function get_baseURL(){
		return $this->_baseURL;
	}

	public function get_storeLanguage(){
		/** @var \Magento\Framework\ObjectManagerInterface $om */
		$om = \Magento\Framework\App\ObjectManager::getInstance();
		/** @var \Magento\Framework\Locale\Resolver $resolver */
		$resolver = $om->get('Magento\Framework\Locale\Resolver');
		return $resolver->getLocale();
	}

	public function get_client_config() {
		$environment = 'test';
		if ($this->get_sandbox() != '1') {
			$environment = 'real';
		}
		return array(
			'Environment' => $environment,
			'MerchantID' => $this->get_merchant(),
			'AcquirerBIN' => $this->get_acquirer(),
			'TerminalID' => $this->get_terminal(),
			'ClaveCifrado' => $this->get_secretkey(),
			'TipoMoneda' => $this->get_currency(),
			'Exponente' => '2',
			'Cifrado' => 'SHA2',
			'Idioma' => '1',
			'Pago_soportado' => 'SSL'
		);
	}

	function process_regular_payment($cecabank_client, $quote_id, $quote, $products, $amount, $url) {
		$objectManager = \Magento\Framework\App\ObjectManager::getInstance();
		$user = $objectManager->create('\Magento\Customer\Model\Customer')->load($quote->getCustomerId());
		$user_id = $quote->getCustomerId();
		$user_age = 'NO_ACCOUNT';
		$user_info_age = '';
		$registered = '';
		$txn_activity_today = '';
		$txn_activity_year = '';
		$txn_purchase_6 = '';
		$ship_name_indicator = 'DIFFERENT';
		$billing_address = $quote->getBillingAddress();
		$name = $billing_address->getFirstname().' '.$billing_address->getLastname();
		$email = $quote->getCustomerEmail();
		$ip = $quote->getRemoteIp();
		$city = $billing_address->getCity();
		$country = $billing_address->getCountryId();
		$line1 = $billing_address->getStreet();
		$postal_code = $billing_address->getPostcode();
		$state = $billing_address->getRegion();
		$phone = $billing_address->getTelephone();
		$shipping_address = $quote->getShippingAddress();
		$ship_name = $shipping_address->getFirstname().' '.$shipping_address->getLastname();
		$ship_city = $shipping_address->getCity();
		$ship_country = $shipping_address->getCountryId();
		$ship_line1 = $shipping_address->getStreet();
		$ship_postal_code = $shipping_address->getPostcode();
		$ship_state = $shipping_address->getRegion();
		if ( $user ) {
			$registered = $user->getCreatedAt();
			$diff = strtotime('now') - strtotime($registered);
			$days = (int)date('d', $diff);
			if ( $days === 0 ) {
				$user_age = 'JUST_CHANGED';
				$user_info_age = 'JUST_CHANGED';
			}  elseif ( $days < 31 ) {
				$user_age = 'LESS_30';
				$user_info_age = 'LESS_30';
			}  elseif ( $days < 61 ) {
				$user_age = 'BETWEEN_30_60';
				$user_info_age = 'BETWEEN_30_60';
			}  else {
				$user_age = 'MORE_60';
				$user_info_age = 'MORE_60';
			}
			$txn_activity_today = 0;
			$txn_activity_year = 0;
			$txn_purchase_6 = 0;
			if ( $name === $ship_name ) {
				$ship_name_indicator = 'IDENTICAL';
			}
		}
		$ship_indicator = 'CH_BILLING_ADDRESS';
		$delivery_time_frame = 'TWO_MORE_DAYS';
		$delivery_email = '';
		$reorder_items = 'FIRST_TIME_ORDERED';
		if ( false ) {
			$ship_indicator = 'DIGITAL_GOODS';
			$delivery_time_frame = 'ELECTRONIC_DELIVERY';
			$delivery_email = $email;
		} elseif ($line1 !== $ship_line1) {
			$ship_indicator = 'CH_NOT_BILLING_ADDRESS';
		}

		// ACS
		$acs = array();

		// Cardholder
		$cardholder = array();
		$add_cardholder = false;

		// Cardholder bill address
		$bill_address = array();
		$add_bill_address = false;
		if ($city) {
			$bill_address['CITY'] = $city;
			$add_bill_address = true;
		}                
		if ($country) {
			$bill_address['COUNTRY'] = $country;
			$add_bill_address = true;
		}
		if ($line1) {
			$bill_address['LINE1'] = $line1;
			$add_bill_address = true;
		}
		if ($postal_code) {
			$bill_address['POST_CODE'] = $postal_code;
			$add_bill_address = true;
		}                
		if ($state) {
			$bill_address['STATE'] = $state;
			$add_bill_address = true;
		}
		if ($add_bill_address) {
			$cardholder['BILL_ADDRESS'] = $bill_address;
			$add_cardholder = true;
		}

		// Cardholder name
		if ($name) {
			$cardholder['NAME'] = $name;
			$add_cardholder = true;
		}

		// Cardholder email
		if ($email) {
			$cardholder['EMAIL'] = $email;
			$add_cardholder = true;
		}

		if ($add_cardholder) {
			$acs['CARDHOLDER'] = $cardholder;
		}

		// Purchase
		$purchase = array();
		$add_purchase = true;

		// Purchase ship address
		$ship_address = array();
		$add_ship_address = false;
		if ($ship_city) {
			$ship_address['CITY'] = $ship_city;
			$add_ship_address = true;
		}                
		if ($ship_country) {
			$ship_address['COUNTRY'] = $ship_country;
			$add_ship_address = true;
		}
		if ($ship_line1) {
			$ship_address['LINE1'] = $ship_line1;
			$add_ship_address = true;
		}
		if ($ship_postal_code) {
			$ship_address['POST_CODE'] = $ship_postal_code;
			$add_ship_address = true;
		}                
		if ($ship_state) {
			$ship_address['STATE'] = $ship_state;
			$add_ship_address = true;
		}
		if ($add_ship_address) {
			$purchase['SHIP_ADDRESS'] = $ship_address;
			$add_purchase = true;
		}

		// Purchase mobile phone
		if ($phone) {
			$purchase['MOBILE_PHONE'] = array(
				'SUBSCRIBER' => $phone
			);
			$add_purchase = true;
		}

		if ($add_purchase) {
			$acs['PURCHASE'] = $purchase;
		}

		// Merchant risk
		$merchant_risk = array(
			'PRE_ORDER_PURCHASE_IND' => 'AVAILABLE'
		);
		if ($ship_indicator) {
			$merchant_risk['SHIP_INDICATOR'] = $ship_indicator;
		}
		if ($delivery_time_frame) {
			$merchant_risk['DELIVERY_TIMEFRAME'] = $delivery_time_frame;
		}
		if ($delivery_email) {
			$merchant_risk['DELIVERY_EMAIL_ADDRESS'] = $delivery_email;
		}
		if ($reorder_items) {
			$merchant_risk['REORDER_ITEMS_IND'] = $reorder_items;
		}
		$acs['MERCHANT_RISK_IND'] = $merchant_risk;

		// Account info
		$account_info = array(
			'SUSPICIOUS_ACC_ACTIVITY' => 'NO_SUSPICIOUS'
		);
		if ($user_age) {
			$account_info['CH_ACC_AGE_IND'] = $user_age;
			$account_info['PAYMENT_ACC_IND'] = $user_age;
		}
		if ($user_info_age) {
			$account_info['CH_ACC_CHANGE_IND'] = $user_info_age;
		}
		if ($registered) {
			$account_info['CH_ACC_CHANGE'] = $registered;
			$account_info['CH_ACC_DATE'] = $registered;
			$account_info['PAYMENT_ACC_AGE'] = $registered;
		}
		if ($txn_activity_today) {
			$account_info['TXN_ACTIVITY_DAY'] = $txn_activity_today;
		}
		if ($txn_activity_year) {
			$account_info['TXN_ACTIVITY_YEAR'] = $txn_activity_year;
		}
		if ($txn_purchase_6) {
			$account_info['NB_PURCHASE_ACCOUNT'] = $txn_purchase_6;
		}
		if ($ship_name_indicator) {
			$account_info['SHIP_NAME_INDICATOR'] = $ship_name_indicator;
		}
		$acs['ACCOUNT_INFO'] = $account_info;
		
		// Create transaction
		$cecabank_client->setFormHiddens(array(
			'Num_operacion' => $quote->getId(),
			'Descripcion' => 'Pago del pedido '.$quote_id,
			'Importe' => $amount,
			'URL_OK' => $url.'?Num_operacion='.$quote->getId(),
			'URL_NOK' => $this->get_baseURL(),
			'datos_acs_20' => urlencode( json_encode( $acs ) )
		));
	}
	
	public function generateFields($quote_id, $quote, $products, $amount){
		$url = $this->_baseURL."cecabank/checkout/success/";

		$config = $this-> get_client_config();

		$cecabank_client = new CecabankClient($config);

		$this->process_regular_payment( $cecabank_client, strval($quote_id), $quote, $products, $amount, $url );
		
		return $cecabank_client;
	}
    
    public function execute()
    {
    	die(0);
    }

}
