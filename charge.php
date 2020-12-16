<?php
require 'conn-shopify.php';
require 'vendor/autoload.php';

use sandeepshetty\shopify_api;

session_start();

if (isset($_GET['charge_id'])) {
    $charge_id = $_GET['charge_id'];
    $shop = $_GET['shop'];
    $shopify = shopifyInit($db, $shop, $appId);
    $theCharge = $shopify("GET",  APIVERSION."recurring_application_charges/$charge_id.json");

    if ($theCharge['status'] == 'accepted') {
        activeClient($appId, $shop, $db, $shopify, $charge_id, $apiKey);
    } else {
        deactiveClient($rootLink, $shop);
    }
}

function shopifyInit($db, $shop, $appId) {
    $select_settings = $db->query("SELECT * FROM tbl_appsettings WHERE id = $appId");
    $app_settings = $select_settings->fetch_object();
    $shop_data = $db->query("select * from tbl_usersettings where store_name = '" . $shop . "' and app_id = $appId");
    $shop_data = $shop_data->fetch_object();
    $shopify = shopify_api\client(
            $shop, $shop_data->access_token, $app_settings->api_key, $app_settings->shared_secret
    );
    return $shopify;
}

function activeClient($appId, $shop, $db, $shopify, $charge_id, $apiKey) {
    $recu = $shopify("POST",  APIVERSION."recurring_application_charges/$charge_id/activate.json");
    $db->query("update tbl_usersettings set status = 'active' where app_id = $appId and store_name = '$shop'");
    header('Location: https://'.$shop.'/admin/apps/'.$apiKey.'');
}

function deactiveClient($rootLink, $shop) {
    header('Location: '.$rootLink.'/decline_charge.php?shop='.$shop);
}