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
		$line2 = '';
		$line3 = '';
		$postal_code = $billing_address->getPostcode();
		$state = $billing_address->getRegion();
		$phone = $billing_address->getTelephone();
		$shipping_address = $quote->getShippingAddress();
		$ship_name = $shipping_address->getFirstname().' '.$shipping_address->getLastname();
		$ship_city = $shipping_address->getCity();
		$ship_country = $shipping_address->getCountryId();
		$ship_line1 = $shipping_address->getStreet();
		$ship_line2 = '';
		$ship_line3 = '';
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
		$acs = array(
			'CARDHOLDER'        => array(
				'NAME'          => $name,
				'EMAIL'         => $email,
				'BILL_ADDRESS'  => array(
					'CITY'      => $city,
					'COUNTRY'   => $country,
					'LINE1'     => $line1,
					'LINE2'     => $line2,
					'LINE3'     => $line3,
					'POST_CODE' => $postal_code,
					'STATE'     => $state
				),
			),
			'PURCHASE'          => array(
				'SHIP_ADDRESS'  => array(
					'CITY'      => $ship_city,
					'COUNTRY'   => $ship_country,
					'LINE1'     => $ship_line1,
					'LINE2'     => $ship_line2,
					'LINE3'     => $ship_line3,
					'POST_CODE' => $ship_postal_code,
					'STATE'     => $ship_state
				),
				'MOBILE_PHONE'  => array(
					'CC'        => '',
					'SUBSCRIBER'=> $phone
				),
				'WORK_PHONE'    => array(
					'CC'        => '',
					'SUBSCRIBER'=> ''
				),
				'HOME_PHONE'    => array(
					'CC'        => '',
					'SUBSCRIBER'=> ''
				),
			),
			'MERCHANT_RISK_IND' => array(
				'SHIP_INDICATOR'=> $ship_indicator,
				'DELIVERY_TIMEFRAME' => $delivery_time_frame,
				'DELIVERY_EMAIL_ADDRESS' => $delivery_email,
				'REORDER_ITEMS_IND' => $reorder_items,
				'PRE_ORDER_PURCHASE_IND' => 'AVAILABLE',
				'PRE_ORDER_DATE'=> '',
			),
			'ACCOUNT_INFO'      => array(
				'CH_ACC_AGE_IND'=> $user_age,
				'CH_ACC_CHANGE_IND' => $user_info_age,
				'CH_ACC_CHANGE' => $registered,
				'CH_ACC_DATE'   => $registered,
				'TXN_ACTIVITY_DAY' => $txn_activity_today,
				'TXN_ACTIVITY_YEAR' => $txn_activity_year,
				'NB_PURCHASE_ACCOUNT' => $txn_purchase_6,
				'SUSPICIOUS_ACC_ACTIVITY' => 'NO_SUSPICIOUS',
				'SHIP_NAME_INDICATOR' => $ship_name_indicator,
				'PAYMENT_ACC_IND' => $user_age,
				'PAYMENT_ACC_AGE' => $registered
			)
		);
		
		// Create transaction
		$cecabank_client->setFormHiddens(array(
			'Num_operacion' => $quote->getId(),
			'Descripcion' => 'Pago del pedido '.$quote_id,
			'Importe' => $amount,
			'URL_OK' => $url,
			'URL_NOK' => $this->get_baseURL(),
			'datos_acs_20' => urlencode( json_encode( $acs ) )
		));
	}
	
	public function generateFields($quote_id, $quote, $products, $amount){
		$url = $this->_baseURL."index.php/checkout/onepage/success/";

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
