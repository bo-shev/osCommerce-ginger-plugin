<?php

namespace gpe_payment;

class Payment
{
    protected $id;
    protected $code;
    protected $title;
    protected $description;
    protected $sort_order;
    protected $enablet;
    protected $debug_mode;
    protected $log_to;
    protected $emspay;

    public function __construct()
    {
    }

    function update_status()
    {
        return false;
    }

    function javascript_validation()
    {
        return false;
    }

    function selection()
    {
        return false;
    }

    function pre_confirmation_check()
    {
        return false;
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
        return false;
    }

    function get_error()
    {
        return false;
    }

    function check()
    {
        return false;
    }

    function remove()
    {
        return false;
    }

    function keys()
    {
        return false;
    }

    function tep_remove_order( $order_id, $restock = false )
    {
        return false;
    }
}