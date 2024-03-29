<?php

class afterpay_pay
{
    var $id, $code, $title, $description, $sort_order, $enabled, $debug_mode, $log_to, $emspay;

    public $apiKey;
    public $zone;
    public $constPrefix;
    public $pluginPrefixLarge;
  // Class Constructor
    public function __construct()
    {
        $this->includeSettings();

        global $order;

        $this->id = $paymentId['afterpay'];
        $this->code = $paymentCode[$this->id];
        $this->title_selection = $paymentConstantTitle[$this->id];

        $this->title = $paymentTitle[$this->id].' '. $this->title_selection;


        $this->description = $paymentConstantDescription[$this->id];
        $this->sort_order = $paymentConstantSortOrder[$this->id];

        $this->test_apikey = $paymentConstantTestApiKey[$this->id];

        $this->enabled = $paymentConstantStatus[$this->id] == 'True' ? true : false;
        $this->debug_mode = (( $paymentConstantDebugMode == 'True' ) ? true : false );
        $this->log_to = $paymentConstantLogTo;


        $this->apiKey = $paymentConstantApiKey;
        $this->zone = $paymentConstantZone[$this->id];
        $this->constPrefix = $constPrefix[$this->id];
        $this->pluginPrefixLarge = $pluginPrefixLarge;

        if ((int)$paymentConstantOrderStatusId[$this->id] > 0)
        {
            $this->order_status = $paymentConstantOrderStatusId[$this->id];
            $payment = $this->code;
        }
        else if ( $payment==$this->code )
        {
            $payment = '';
        }

        if ( is_object( $order ) )
        {
          $this->update_status();
        }

        $this->emspay = null;
        if ( $this->enabled )
        {
          if ( file_exists( 'emspay/ems_lib.php' ) )
          {
            require_once 'emspay/ems_lib.php';
            $apiKey = !empty( $this->test_apikey  ) ? $this->test_apikey : $this->apiKey;
            $this->emspay = new Ems_Services_Lib( $apiKey, $this->log_to, $this->debug_mode );
          }
          else
          {
            // TODO: SHOULD GIVE WARNING
          }
        }
    }

    protected function includeSettings()
    {
        if ( file_exists(__DIR__ . '/../../../gpe_payment/Modules/PaymentSetings.php') )
        {
            require_once __DIR__ . '/../../../gpe_payment/Modules/PaymentSetings.php';
        }
    }

        // Class Methods
    function update_status()
    {
        global $order;

        if ( ( $this->enabled == true ) && ( (int)$this->zone > 0 ) )
        {
          $check_flag = false;
          $check_query = tep_db_query( "select zone_id from " . TABLE_ZONES_TO_GEO_ZONES . " where geo_zone_id = '" . intval( $this->zone ) . "' and zone_country_id = '" . intval( $order->billing['country']['id'] ) . "' order by zone_id" );
          while ( $check = tep_db_fetch_array( $check_query ) )
          {
            if ( $check['zone_id'] < 1 )
            {
              $check_flag = true;
              break;
            } elseif ( $check['zone_id'] == $order->billing['zone_id'] )
            {
              $check_flag = true;
              break;
            }
          }

          if ( $check_flag == false )
          {
            $this->enabled = false;
          }
        }

        if ($this->enabled)
        {
            $check_countries_query = tep_db_query("select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EMSPAY_COUNTRIES_ACCESS'");
            $check_countries = tep_db_fetch_array($check_countries_query);
            $countrylist = array_map("trim", explode(',', $check_countries['configuration_value']));
            if (empty($check_countries['configuration_value']) || in_array($order->billing['country']['iso_code_2'], $countrylist))
            {
                $this->enabled = true;
            }
            else
            {
                $this->enabled = false;
            }
        }

        if ( $order->info['currency'] != "EUR" )
        {
          $this->enabled = false;
        }

        // check that api key is not blank
        if ( !$this->apiKey or !strlen( $this->apiKey ) )
        {
          print 'no secret ' . $this->apiKey;
          $this->enabled = false;
        }
    }

  function javascript_validation()
  {
    $js = 'if (payment_value == "' . $this->code . '") {' . "\n" .
        '  var emspay_gender_id = document.checkout_payment.emspay_gender_id.value;' . "\n" .
        '  var emspay_day_id = document.checkout_payment.emspay_date_of_birth_day_id.value;' . "\n" .
        '  var emspay_month_id = document.checkout_payment.emspay_month_of_birth_day_id.value;' . "\n" .
        '  var emspay_year_id = document.checkout_payment.emspay_year_of_birth_day_id.value;' . "\n" .
        '  var emspay_terms_id = document.checkout_payment.emspay_terms_id;' . "\n" .
        '  if (emspay_gender_id == "" || emspay_day_id == "" || emspay_month_id == "" || emspay_year_id == "" || !emspay_terms_id.checked) {' . "\n" .
        '    if (emspay_gender_id == "") {error_message += "' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_GENDER . '\n"}' . "\n" .
        '    if (emspay_day_id == "") {error_message += "' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_DAY . '\n"}' . "\n" .
        '    if (emspay_month_id == "") {error_message += "' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_MONTH . '\n"}' . "\n" .
        '    if (emspay_year_id == "") {error_message += "' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_YEAR . '\n"}' . "\n" .
        '    if (!emspay_terms_id.checked) {error_message += "' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_TERMS . '\n"}' . "\n" .
        '    error = 1;' . "\n" .
        '  }' . "\n" .
        '}' . "\n";
    return $js;
  }

  function selection()
  {
    $selection['id'] = $this->code;
    $selection['module'] = $this->title_selection;

    $day_range = range( 1, 31 );
    $month_range = range( 1, 12 );
    $year_range = range( date( 'Y' ), date( 'Y' ) - 100 );

    $selection['fields'][0]['field'] = tep_draw_pull_down_menu( 'emspay_gender_id', [ [ 'id' => '', 'text' => MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_GENDER ], [ 'id' => 'male', 'text' => MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_MALE ], [ 'id' => 'female', 'text' => MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_FEMALE ] ], $_SESSION['emspay_gender_id'] );
    $selection['fields'][1]['field'] = tep_draw_pull_down_menu( 'emspay_date_of_birth_day_id', $this->getPeriodValues( MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_DAY, $day_range ), $_SESSION['emspay_date_of_birth_day_id'] );
    $selection['fields'][2]['field'] = tep_draw_pull_down_menu( 'emspay_month_of_birth_day_id', $this->getPeriodValues( MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_MONTH, $month_range ), $_SESSION['emspay_month_of_birth_day_id'] );
    $selection['fields'][3]['field'] = tep_draw_pull_down_menu( 'emspay_year_of_birth_day_id', $this->getPeriodValues( MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_YEAR, $year_range ), $_SESSION['emspay_year_of_birth_day_id'] );
    $selection['fields'][4]['field'] = tep_draw_checkbox_field( 'emspay_terms_id', '1', ( ( $_SESSION['emspay_terms_id'] == '1' ) ? true : false ) ) . '<strong>' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_ACCEPT . ' <a href="' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_TERMS_LINK . '" target="_blank" style="text-decoration: underline">' . MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_TERMS . '</a></strong>';
    return $selection;
  }

  function getPeriodValues( $placeholder, $values ) {
    if ( is_array( $values ) && count( $values ) ) {
      $modified_arr = [ [ 'id' => '', 'text' => $placeholder ] ];
      foreach ( $values as $value ) {
        $modified_arr[] = [ 'id' => $value, 'text' => $value ];
      }
      return $modified_arr;
    } else {
      return array();
    }
  }

  function pre_confirmation_check() {
    $_SESSION['emspay_gender_id'] = $_POST['emspay_gender_id'];
    $_SESSION['emspay_date_of_birth_day_id'] = $_POST['emspay_date_of_birth_day_id'];
    $_SESSION['emspay_month_of_birth_day_id'] = $_POST['emspay_month_of_birth_day_id'];
    $_SESSION['emspay_year_of_birth_day_id'] = $_POST['emspay_year_of_birth_day_id'];
    $_SESSION['emspay_terms_id'] = $_POST['emspay_terms_id'];
  }

  function confirmation() {
    return false;
  }

  function process_button() {
    return false;
  }

  function before_process() {
    return false;
  }

  function after_process() {
    global $insert_id, $order, $languages_id;

    for ( $i = 0, $n = sizeof( $order->products ); $i < $n; $i++ ) {
	  $product_id = strpos($order->products[$i]['id'], '{') ? substr($order->products[$i]['id'], 0, strpos($order->products[$i]['id'], '{')) : $order->products[$i]['id'];
	  $order_lines[] = array(
          'amount' => (int)round( ( $order->products[$i]['final_price'] + tep_calculate_tax( $order->products[$i]['final_price'], $order->products[$i]['tax'] ) ) * 100, 0 ),
          'currency' => 'EUR',
          'merchant_order_line_id' => $insert_id . "_" . $order->products[$i]['id'],
          'name' => $order->products[$i]['name'],
          'quantity' => (int)$order->products[$i]['qty'],
          'type' => 'physical',
          'url' => tep_href_link( FILENAME_PRODUCT_INFO, 'products_id=' . $product_id ),
          'vat_percentage' => (int)round( ( $order->products[$i]['tax'] * 100 ), 0 ),
      );
    }
    // check if there is shipping
    if ( $order->info['shipping_cost'] ) {
      $order_lines[] = array(
          'amount' => (int)round( $order->info['shipping_cost'] * 100, 0 ),
          'currency' => 'EUR',
          'merchant_order_line_id' => $insert_id . "_shipping",
          'name' => $order->info['shipping_method'],
          'quantity' => (int)1,
          'type' => 'shipping_fee',
          'vat_percentage' => (int)2100,
      );
    }

    $customer_gender = !empty( $_SESSION['emspay_gender_id'] ) ? (string)$_SESSION['emspay_gender_id'] : null;
    $customer_birthdate = (string)implode( '-', [ $_SESSION['emspay_date_of_birth_day_id'], $_SESSION['emspay_month_of_birth_day_id'], $_SESSION['emspay_year_of_birth_day_id'] ] );

    $webhook_url = tep_href_link( "ext/modules/payment/emspay/notify.php", '', 'SSL' );

    $customer = $this->emspay->getCustomerInfo( $customer_gender, $customer_birthdate );
    $emspay_order = $this->emspay->emsCreateOrder( $insert_id,
	    							   $order->info['total'],
	    							   STORE_NAME . " " . $insert_id,
	    							   $customer,
	    							   $webhook_url,
	    							   $this->id,
	    							   tep_href_link( "ext/modules/payment/emspay/redir.php", '', 'SSL' ),
	    							   null,
	    							   $order_lines
    								  );

    // change order status to value selected by merchant
    tep_db_query( "update " . TABLE_ORDERS . " set orders_status = " . intval( MODULE_PAYMENT_EMSPAY_NEW_STATUS_ID ) . ", emspay_order_id = '" . $emspay_order['id'] . "' where orders_id = " . intval( $insert_id ) );

    $this->emspay->emsLog( $emspay_order );

    if ( !is_array( $emspay_order ) or array_key_exists( 'error', $emspay_order ) or $emspay_order['status'] == 'error' or current($emspay_order['transactions'])['status'] == 'error' ) {
      // TODO: Remove this? I don't know if I like it removing orders, or make it optional
      $this->tep_remove_order( $insert_id, $restock = true );
        $reason = "Error placing AfterPay order ";
        $reason .= $emspay_order['error']['value'] ?? $emspay_order['transactions'][0]['reason'] ?? null;
        tep_redirect( tep_href_link( FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode( $reason ), 'SSL' ) );
    } else {
      tep_redirect( $emspay_order['transactions'][0]['payment_url'] );
    }
    return false;
  }

  function get_error() {
    return false;
  }

  function check() {
    if ( !isset( $this->_check ) ) {
      $check_query = tep_db_query( "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EMSPAY_AFTERPAY_STATUS'" );
      $this->_check = tep_db_num_rows( $check_query );
    }
    return $this->_check;
  }

  function tableColumnExists( $table_name, $column_name ) {
    $check_q = tep_db_query( "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . $table_name . "' AND COLUMN_NAME = '" . $column_name . "'" );
    return tep_db_num_rows( $check_q );
  }

  function install() {
    // ADD EMSPAY ORDER ID TO THE ORDERS TABLE
    if ( !$this->tableColumnExists( "orders", "emspay_order_id" ) ) {
      if ( !tep_db_query( "ALTER TABLE orders ADD emspay_order_id VARCHAR( 36 ) NULL DEFAULT NULL ;" ) ) {
        die( "To be able to work; please add the column emspay_order_id (VARCHAR 36, DEFAULT NULL) to your order table" );
      }
    }

    $sort_order = 0;
    $add_array = array(
        "configuration_title" => 'Enable Online '.$this->title_selection.' Module',
        "configuration_key" => 'MODULE_PAYMENT_'.$this->constPrefix.'_STATUS',
        "configuration_value" => 'False',
        "configuration_description" => 'Do you want to accept '.$this->title_selection.' payments using '.$this->title.'?',
        "configuration_group_id " => '6',
        "sort_order" => $sort_order,
        "set_function" => "tep_cfg_select_option(array('True', 'False'), ",
        "date_added " => 'now()',
    );
    tep_db_perform( TABLE_CONFIGURATION, $add_array );
    $sort_order++;

    $add_array = array(
        "configuration_title" => 'Test API key',
        "configuration_key" => 'MODULE_PAYMENT_'.$this->constPrefix.'_TEST_APIKEY',
        "configuration_value" => '',
        "configuration_description" => 'Enter here the API Key of the test webshop for testing '.$this->title_selection.'. If you do not offer AfterPay you can leave this empty.',
        "configuration_group_id " => '6',
        "sort_order" => $sort_order,
        "date_added " => 'now()',
    );
    tep_db_perform( TABLE_CONFIGURATION, $add_array );
    $sort_order++;

    $add_array = array(
          "configuration_title" => 'Countries available for '.$this->title_selection.'',
          "configuration_key" => 'MODULE_PAYMENT_'.$this->pluginPrefixLarge.'_COUNTRIES_ACCESS',
          "configuration_value" => 'NL, BE',
          "configuration_description" => 'To allow '.$this->title_selection.' to be used for any other country just add its country code (in ISO 2 standard) to the "Countries available for '.$this->title_selection.'" field.<br> Example: BE, NL, FR <br> If field is empty then '.$this->title_selection.' will be available for all countries.',
          "configuration_group_id " => '6',
          "sort_order" => $sort_order,
          "date_added " => 'now()',
      );
    tep_db_perform( TABLE_CONFIGURATION, $add_array );
    $sort_order++;

    $add_array = array(
        "configuration_title" => 'Payment Zone',
        "configuration_key" => 'MODULE_PAYMENT_'.$this->constPrefix.'_ZONE',
        "configuration_value" => 0,
        "configuration_description" => 'If a zone is selected, only enable this payment method for that zone.',
        "configuration_group_id " => '6',
        "sort_order" => $sort_order,
        "set_function" => "tep_cfg_pull_down_zone_classes(",
        "use_function" => "tep_get_zone_class_title",
        "date_added " => 'now()',
    );
    tep_db_perform( TABLE_CONFIGURATION, $add_array );
    $sort_order++;

    $add_array = array(
        "configuration_title" => 'Sort Order of Display',
        "configuration_key" => 'MODULE_PAYMENT_'.$this->constPrefix.'_SORT_ORDER',
        "configuration_value" => 0,
        "configuration_description" => 'Sort order of display. Lowest is displayed first.',
        "configuration_group_id " => '6',
        "sort_order" => $sort_order,
        "date_added " => 'now()',
    );
    tep_db_perform( TABLE_CONFIGURATION, $add_array );
    $sort_order++;
  }

  function remove() {
    tep_db_query( "delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode( "', '", $this->keys() ) . "')" );
  }

//  function keys() {
//    return array(
//        'MODULE_PAYMENT_EMSPAY_AFTERPAY_STATUS',
//        'MODULE_PAYMENT_EMSPAY_AFTERPAY_TEST_APIKEY',
//        'MODULE_PAYMENT_EMSPAY_AFTERPAY_ZONE',
//        'MODULE_PAYMENT_EMSPAY_AFTERPAY_SORT_ORDER',
//        'MODULE_PAYMENT_EMSPAY_COUNTRIES_ACCESS',
//    );
//  }
  function keys()
   {
        return array(
            'MODULE_PAYMENT_'.$this->constPrefix.'_STATUS',
            'MODULE_PAYMENT_'.$this->constPrefix.'_TEST_APIKEY',
            'MODULE_PAYMENT_'.$this->constPrefix.'_ZONE',
            'MODULE_PAYMENT_'.$this->constPrefix.'_SORT_ORDER',
            'MODULE_PAYMENT_'.$this->pluginPrefixLarge.'_COUNTRIES_ACCESS',
        );
   }


  function tep_remove_order( $order_id, $restock = false ) {
    if ( $restock == 'on' ) {
      $order_query = tep_db_query( "select products_id, products_quantity from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'" );
      while ( $order = tep_db_fetch_array( $order_query ) ) {
        tep_db_query( "update " . TABLE_PRODUCTS . " set products_quantity = products_quantity + " . $order['products_quantity'] . ", products_ordered = products_ordered - " . $order['products_quantity'] . " where products_id = '" . (int)$order['products_id'] . "'" );
      }
    }

    tep_db_query( "delete from " . TABLE_ORDERS . " where orders_id = '" . (int)$order_id . "'" );
    tep_db_query( "delete from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'" );
    tep_db_query( "delete from " . TABLE_ORDERS_PRODUCTS_ATTRIBUTES . " where orders_id = '" . (int)$order_id . "'" );
    tep_db_query( "delete from " . TABLE_ORDERS_STATUS_HISTORY . " where orders_id = '" . (int)$order_id . "'" );
    tep_db_query( "delete from " . TABLE_ORDERS_TOTAL . " where orders_id = '" . (int)$order_id . "'" );
  }
}