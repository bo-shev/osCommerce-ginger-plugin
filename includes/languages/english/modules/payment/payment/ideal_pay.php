<?php

class ideal_pay
{
  var $code, $title, $description, $sort_order, $enabled, $debug_mode, $log_to, $emspay, $id;

  public $apiKey;
  public $zone;
  public $constPrefix;
  public $selectBank;

  // Class Constructor
  public function __construct()
  {
    //  xdebug_get_code_coverage();

    //  xdebug_info();
    $this->includeSettings();

    global $order;

      $this->id = $paymentId['ideal'];
      $this->code = $paymentCode[$this->id];
      $this->title_selection = $paymentConstantTitle[$this->id];


      $this->title = $paymentTitle[$this->id].' '. $this->title_selection;

      $this->description = $paymentConstantDescription[$this->id];
      $this->sort_order = $paymentConstantSortOrder[$this->id];
      $this->enabled = $paymentConstantStatus[$this->id] == 'True' ? true : false;

      $this->debug_mode = (( $paymentConstantDebugMode == 'True' ) ? true : false );
      $this->log_to = $paymentConstantLogTo;


      $this->apiKey = $paymentConstantApiKey;
      $this->zone = $paymentConstantZone[$this->id];
      $this->constPrefix = $constPrefix[$this->id];
      $this->selectBank = $paymentConstantSelectBank[$this->id];

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
    if ($this->enabled)
    {
      if ( file_exists( 'emspay/ems_lib.php' ) )
      {
        require_once 'emspay/ems_lib.php';
        $this->emspay = new Ems_Services_Lib( $this->apiKey , $this->log_to, $this->debug_mode );
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
        }
        elseif ( $check['zone_id'] == $order->billing['zone_id'] )
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

    if ( $order->info['currency'] != "EUR" )
    {
      $this->enabled = false;
    }

    // check that api key is not blank
    if ( !$this->apiKey or !strlen( $this->apiKey ) )
    {
      print 'no secret '.$this->apiKey ;
      $this->enabled = false;
    }
  }

  function javascript_validation()
  {
    $js = 'if (payment_value == "' . $this->code . '") {' . "\n" .
      '  var emspay_issuer_id = document.checkout_payment.emspay_issuer_id.value;' . "\n" .
      '  if (emspay_issuer_id == "") {' . "\n" .
      '    error_message = error_message + "' . $this->selectBank . '";' . "\n" .
      '    error = 1;' . "\n" .
      '  }' . "\n" .
      '}' . "\n";
    return $js;

  }

  function selection()
  {
    // TODO: When selecting a bank; this method should be in focus
    //    $onFocus = ' onfocus="selectRowEffect(\'selectRowEffect(this, 2)\')"';

      $selection['id'] = $this->code;
      $selection['module'] = $this->title_selection;

      $selection['fields'][0]['title'] = '';
      $selection['fields'][0]['field'] = tep_draw_pull_down_menu( 'emspay_issuer_id', $this->get_issuers(), $_SESSION['emspay_issuer_id'], $onFocus );

      return $selection;
  }

  function get_issuers()
  {
    $issuers_tmp = $this->emspay->emsGetIssuers();

    if ( is_array( $issuers_tmp ) && !isset( $issuers_tmp['error'] ) ) {
      $issuers_array = array();

      $i = 0;
      $issuers_array[$i]['id'] = '';
      $issuers_array[$i]['text'] = $this->selectBank;

      $i++;

      foreach ( $issuers_tmp as $issuer )
      {
        $issuers_array[$i]['id'] = $issuer['id'];
        $issuers_array[$i]['text'] = $issuer['name'];

        $i++;
      }

      return $issuers_array;
    }
    else
    {
      return array();
    }
  }

  function pre_confirmation_check()
  {
    $_SESSION['emspay_issuer_id'] = $_POST['emspay_issuer_id'];
  }

  function confirmation()
  {
    return false;
  }

  function process_button()
  {
    return false;
  }

  function before_process()
  {
    return false;
  }

  function after_process()
  {
    global $insert_id, $order;

    $webhook_url =  tep_href_link( "ext/modules/payment/emspay/notify.php", '', 'SSL' );

    $customer = $this->emspay->getCustomerInfo();
    $emspay_order = $this->emspay->emsCreateOrder( $insert_id,
                                                   $order->info['total'],
                                                   STORE_NAME . " " . $insert_id,
                                                   $customer,
                                                   $webhook_url,
	    							   $this->id,
                                                   tep_href_link( "ext/modules/payment/emspay/redir.php", '', 'SSL' ),
                                                   $_SESSION['emspay_issuer_id']
    								 );

    // change order status to value selected by merchant
    tep_db_query( "update ". TABLE_ORDERS. " set orders_status = " . intval( MODULE_PAYMENT_EMSPAY_NEW_STATUS_ID ) . ", emspay_order_id = '" . $emspay_order['id']  . "' where orders_id = ". intval( $insert_id ) );

    $this->emspay->emsLog( $emspay_order );

    if ( !is_array( $emspay_order ) or array_key_exists( 'error', $emspay_order) or $emspay_order['status'] == 'error' )
    {
      // TODO: Remove this? I don't know if I like it removing orders, or make it optional
      $this->tep_remove_order( $insert_id, $restock = true );
        $reason = "Error placing ".$this->title_selection." order ";
        $reason.= $emspay_order['error']['value'] ?? $emspay_order['transactions'][0]['reason'] ?? null;
        tep_redirect( tep_href_link( FILENAME_CHECKOUT_PAYMENT, 'error_message=' . urlencode( $reason ), 'SSL' ) );
    }
    else
    {
      tep_redirect( $emspay_order['transactions'][0]['payment_url'] );
    }
    return false;
  }

  function get_error()
  {
    return false;
  }

  function check()
  {
    if ( !isset( $this->_check ) )
    {
      $check_query = tep_db_query( "select configuration_value from " . TABLE_CONFIGURATION . " where configuration_key = 'MODULE_PAYMENT_EMSPAY_IDEAL_STATUS'" );
      $this->_check = tep_db_num_rows( $check_query );
    }
    return $this->_check;
  }

  function tableColumnExists($table_name, $column_name)
  {
    $check_q = tep_db_query("SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = '" . DB_DATABASE . "' AND TABLE_NAME = '" . $table_name . "' AND COLUMN_NAME = '" . $column_name ."'");
    return tep_db_num_rows($check_q);
  }

  function install()
  {
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

  function remove()
  {
    tep_db_query( "delete from " . TABLE_CONFIGURATION . " where configuration_key in ('" . implode( "', '", $this->keys() ) . "')" );
  }
    function keys()
    {
        return array(
            'MODULE_PAYMENT_'.$this->constPrefix.'_STATUS',
            'MODULE_PAYMENT_'.$this->constPrefix.'_ZONE',
            'MODULE_PAYMENT_'.$this->constPrefix.'_SORT_ORDER',
        );
    }

  function tep_remove_order( $order_id, $restock = false )
  {
    if ( $restock == 'on' )
    {
      $order_query = tep_db_query( "select products_id, products_quantity from " . TABLE_ORDERS_PRODUCTS . " where orders_id = '" . (int)$order_id . "'" );
      while ( $order = tep_db_fetch_array( $order_query ) )
      {
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