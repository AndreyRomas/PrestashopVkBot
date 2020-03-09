<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class VkNotifications extends Module
{
    protected $config_form = false;

	
    public function __construct()
    {
        $this->name = 'VkNotifications';
        $this->tab = 'administration';
        $this->version = '0.1.5';
        $this->author = 'Andrey Romas';
        $this->need_instance = 1;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Уведомления ВК');
        $this->description = $this->l('Уведомления о новых заказах');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }
	    
    public function hookNewOrder($params)
    {
		$id_order = $params['order']->id;
		$customer_message = $params['order']->getFirstMessage();
		$customer = new Customer((int)$params['order']->id_customer);
		$vkstring = "Новый заказ! " . "Номер: ". $id_order . "\n";
		$product_list = $params['order']->getProductsDetail();
		foreach ($product_list AS $p)
		{
			$vkstring .= $p['product_name']." ".number_format($p['product_price'], 2, '.', '')."р. ".$p['product_quantity'] . "шт. \n";
		}
		$address_delivery = new Address((int)$params['order']->id_address_delivery);
		$carrier = new Carrier((int)$params['order']->id_carrier);
		$delivery =  $carrier->name;
		$deliveryprice = $params['order']->total_shipping;
		$vkstring .= "Способ доставки: ".$delivery . " " . $deliveryprice. "р.". "\n";
		$totalprice = $params['order']->total_paid;
		$vkstring .= "Сумма: ".$totalprice . "р.\n";
		$name2 = $address_delivery->lastname;
		$name1 = $address_delivery->firstname;
		$vkstring .= $name1 . " " . $name2 . "\n";
		$phonenumber = $address_delivery->phone;
		$vkstring .= $phonenumber . "\n";
		$address1 = $address_delivery->address1;
		$address2 = $address_delivery->address2;
		$vkstring .= $address1." ".$address2."\n";
		$vkstring .= $customer_message . "\n";
		$pochta = $customer->email;
		$vkstring .= $pochta. "\n";
		$vkstring .= "Подробнее в админке магазина.";
		$chatid = ''; // ID чата с ботом вк <---- вписать сюда
		$this->vk_send_message($chatid, $vkstring);
	}
	function install()  
	{
	 if ( parent::install() == false OR
	 !$this->registerHook( 'newOrder' ) )
	  return false;
	 return true;
	}

    public function uninstall()
    {
        Configuration::deleteByName('VKNOTIFICATIONS');

        return parent::uninstall();
    }
		public function vk_send_message($peer_id, $message) {
		  $this->vk_api('messages.send', array(
			'peer_id' => $peer_id,
			'message' => $message,
		  ));
		}

		public function vk_api($method, $params) {
		  $vkkey = ''; // ключ API VK <---- вписать сюда
		  $params['access_token'] = $vkkey;
		  $params['v'] = '5.89';
		  $query = http_build_query($params);
		  $url = 'https://api.vk.com/method/' . $method . '?' . $query;
		  $curl = curl_init($url);
		  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		  $json = curl_exec($curl);
		  $error = curl_error($curl);
		  if ($error) {
			error_log($error);
			throw new Exception("Failed {$method} request");
		  }
		  curl_close($curl);
		  $response = json_decode($json, true);
		  if (!$response || !isset($response['response'])) {
			error_log($json);
			throw new Exception("Invalid response for {$method} request");
		  }
		  return $response['response'];
		}
}
		

