<?php
require 'vendor/autoload.php';

use sandeepshetty\shopify_api;

require 'conn-shopify.php';

$shop = $_GET['shop']; //shop name
$select_settings = $db->query("SELECT * FROM tbl_appsettings WHERE id = $appId");
$app_settings = $select_settings->fetch_object();
$shop_data = $db->query("select * from tbl_usersettings where store_name = '" . $shop . "' and app_id = $appId");
$shop_data = $shop_data->fetch_object();
$shopify = shopify_api\client(
    $shop, $shop_data->access_token, $app_settings->api_key, $app_settings->shared_secret
);

//charge fee
$charge = array(
    "recurring_application_charge" => array(
        "name" => $chargeTitle,
        "price" => $price,
        "return_url" => "$rootLink/charge.php?shop=$shop",
        "test" => $testMode,
        "trial_days" => $trialTime
    )
);
if($chargeType == "one-time"){
    $recu = $shopify("POST", APIVERSION."application_charges.json", $charge);
    $confirmation_url = $recu["confirmation_url"];
} else {
    $recu = $shopify("POST", APIVERSION."recurring_application_charges.json", $charge);
    $confirmation_url = $recu["confirmation_url"];
}
$db->query("update tbl_usersettings set confirmation_url = '$confirmation_url' where store_name = '$shop' and app_id = $appId");
echo $confirmation_url;

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
