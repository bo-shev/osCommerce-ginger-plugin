<?php

$prefix = "emspay";
$prefixLarge = "EMS";

$pluginPrefixLarge = "EMSPAY";

constant('MODULE_PAYMENT_EMSPAY_'..'_TEXT_TITLE');

$paymentId = array(
    "ideal" => 'ideal',
    "afterpay" => 'afterpay',
);

$paymentCode = array(
    "ideal" => 'ideal_pay',
    "afterpay" => 'afterpay_pay',
);

$paymentName = array(
    "ideal" => 'iDEAL',
    "afterpay" => 'AfterPay',
);

$paymentTitle = array(
    "ideal" => $prefixLarge.' Online',
    "afterpay" => $prefixLarge.' Online',
);

$paymentConstantTitle = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_IDEAL_TEXT_TITLE,
    "afterpay" => MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_TITLE,
);

$paymentConstantDescription = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_IDEAL_TEXT_DESCRIPTION,
    "afterpay" => MODULE_PAYMENT_EMSPAY_AFTERPAY_TEXT_DESCRIPTION,
);

$paymentConstantSortOrder = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_IDEAL_SORT_ORDER,
    "afterpay" =>MODULE_PAYMENT_EMSPAY_AFTERPAY_SORT_ORDER,
);

$paymentConstantStatus = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_IDEAL_STATUS,
    "afterpay" =>MODULE_PAYMENT_EMSPAY_AFTERPAY_STATUS,
);

$paymentConstantDebugMode = MODULE_PAYMENT_EMSPAY_DEBUG_MODE;
//    array(
//    "ideal" => MODULE_PAYMENT_EMSPAY_DEBUG_MODE,
//    "afterpay" => MODULE_PAYMENT_EMSPAY_DEBUG_MODE,
//);

$paymentConstantLogTo = MODULE_PAYMENT_EMSPAY_LOG_TO;
//    array(
//    "ideal" => MODULE_PAYMENT_EMSPAY_LOG_TO,
//);

$paymentConstantOrderStatusId = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_ORDER_STATUS_ID,
);

$paymentConstantApiKey = MODULE_PAYMENT_EMSPAY_APIKEY;
//    array(
//    "ideal" => MODULE_PAYMENT_EMSPAY_APIKEY,
//);

$paymentConstantTestApiKey = array(
    "afterpay" => MODULE_PAYMENT_EMSPAY_AFTERPAY_TEST_APIKEY,
);

$paymentConstantZone = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_IDEAL_ZONE,
    "afterpay" => MODULE_PAYMENT_EMSPAY_AFTERPAY_ZONE,
);

$paymentConstantSelectBank = array(
    "ideal" => MODULE_PAYMENT_EMSPAY_IDEAL_TEXT_SELECT_BANK,
);

$constPrefix = array(
    "ideal" => 'EMSPAY_IDEAL',
    "afterpay" => 'EMSPAY_AFTERPAY',
);