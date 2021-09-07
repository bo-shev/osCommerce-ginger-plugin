<?php

class OrderBuilder extends gpe_payment\PaymentLib
{
    public function createOrder($orders_id, $total, $description, $customer, $webhook_url, $payment_id, $return_url, $issuer_id = null, $order_lines = [])
    {
        $post = array_filter([
            "type"              => "payment",
            "currency"          => "EUR",
            "amount"            => 100 * round($total, 2),
            "merchant_order_id" => (string)$orders_id,
            'customer' 		  => $customer,
            "description"       => (string)$description,
            "return_url"        => (string)$return_url,
            "transactions"      => [
                [
                    "payment_method"         => $payment_id,
                ]
            ],
            'extra' => [
                'plugin' => $this->plugin_version,
            ],
        ]);

        if ($return_url != null)
            $post['return_url'] = $return_url;

        if ($webhook_url != null)
            $post['webhook_url'] = $webhook_url;

        if ($issuer_id != null)
            $post['transactions'][0]['payment_method_details'] = array("issuer_id" => $issuer_id);

        if (!empty($order_lines))
            $post['order_lines'] = $order_lines;

        $order = json_encode($post);

        return $this->performApiCall("orders/", $order);
    }

    public function getOrderStatus($order_id)
    {
        $order = $this->performApiCall("orders/" . $order_id . "/");

        if (!is_array($order) || array_key_exists('error', $order))
        {
            return 'error';
        }
        else
        {
            return $order['status'];
        }
    }

    public function getOrderDetails($order_id)
    {
        $order = $this->performApiCall("orders/" . $order_id . "/");

        if (!is_array($order) || array_key_exists('error', $order))
        {
            return 'error';
        }
        else
        {
            return $order;
        }
    }
}
