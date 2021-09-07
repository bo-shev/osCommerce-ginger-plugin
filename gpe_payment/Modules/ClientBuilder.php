<?php

class ClientBuilder
{
    public function getCustomerInfo($gender = '', $birthdate = '')
    {
        global $order, $languages_id, $customer_id;

        if (empty($gender)||empty($birthdate))
        {
            // check if it's not english
            $language_row = tep_db_fetch_array(tep_db_query("SELECT * FROM languages WHERE languages_id = '" . $languages_id . "'"));
            $customer_data_not_in_customer_object = tep_db_fetch_array(tep_db_query("SELECT customers_dob, customers_gender FROM customers WHERE customers_id = '" . (int)$customer_id . "'"));

            if (empty($gender))
            {
                $gender = $customer_data_not_in_customer_object['customers_gender'] == "f" ? 'female' : 'male';
            }

            if (empty($birthdate))
            {
                $birthdate = date("Y-m-d", strtotime($customer_data_not_in_customer_object['customers_dob']));
            }
        }

        return array(
            'email_address' => !empty($order->customer['email_address']) ? (string)$order->customer['email_address'] : null,
            'first_name' => !empty($order->customer['firstname']) ? (string)$order->customer['firstname'] : null,
            'last_name' => !empty($order->customer['lastname']) ? (string)$order->customer['lastname'] : null,
            'address_type' => 'customer',
            'address' => !empty($order->customer['street_address'] . "\n" . $order->customer['postcode'] . ' ' . $order->customer['city']) ? (string)($order->customer['street_address'] . "\n" . $order->customer['postcode'] . ' ' . $order->customer['city']) : null,
            'postal_code' => !empty($order->customer['postcode']) ? (string)$order->customer['postcode'] : null,
            'country' => !empty($order->customer['country']['iso_code_2']) ? (string)$order->customer['country']['iso_code_2'] : null,
            'locale' => $language_row['code'] == 'en' ? 'en_GB' : 'nl_NL',
            'phone_numbers' => !empty($order->customer['telephone']) ? [(string)$order->customer['telephone']] : null,
            'ip_address' => !empty(filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) ? (string)filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP) : null,
            'gender' => $gender,
            'birthdate' => $birthdate,
            'additional_addresses' => $this->getAdditionalAddresses()
        );
    }

    public function getAdditionalAddresses()
    {
        global $order;

        return [array_filter([
            'address_type' => 'billing',
            'address' => !empty($order->customer['street_address'] . "\n" . $order->customer['postcode'] . ' ' . $order->customer['city']) ? (string)($order->customer['street_address'] . "\n" . $order->customer['postcode'] . ' ' . $order->customer['city']) : null,
            'postal_code' => !empty($order->customer['postcode']) ? (string)$order->customer['postcode'] : null,
            'country' => !empty($order->customer['country']['iso_code_2']) ? (string)$order->customer['country']['iso_code_2'] : null,
        ])];
    }
}