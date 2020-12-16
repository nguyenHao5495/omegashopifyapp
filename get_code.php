<?php
date_default_timezone_set('UTC');

require 'vendor/autoload.php'; 

use sandeepshetty\shopify_api; 

require 'conn-shopify.php';

$select_settings = $db->query("SELECT * FROM tbl_appsettings WHERE id = $appId");
$app_settings = $select_settings->fetch_object();
if (!empty($_GET['shop']) && !empty($_GET['code'])) {
    $shop = $_GET['shop']; //shop name
    
    //get permanent access token
    $access_token = shopify_api\oauth_access_token(
        $shop, $app_settings->api_key, $app_settings->shared_secret, $_GET['code']
    );
    $installed = checkInstalled($db, $shop, $appId);
    if ($installed["installed"]) {
        $date_installed = $installed["installed_date"];
        $db->query("
            INSERT INTO tbl_usersettings 
            SET access_token = '$access_token',
            store_name = '$shop', app_id = $appId, installed_date = '$date_installed', confirmation_url = ''
        ");
        $date1 = new DateTime($installed["installed_date"]);
        $date2 = new DateTime("now");
        $interval = date_diff($date1, $date2);
        $diff = (int)$interval->format('%R%a');
        $trialTime = $trialTime - $diff;
        if($trialTime < 0) {
            $trialTime = 0;
        }
    } else {
        $db->query("
            INSERT INTO tbl_usersettings 
            SET access_token = '$access_token',
            store_name = '$shop', app_id = $appId, installed_date = NOW(), confirmation_url = ''
        ");
        $db->query("
            INSERT INTO shop_installed 
            SET shop = '$shop', app_id = $appId, date_installed = NOW()
        ");
    }

    //insert shop setting for app
    $settings = getShopSettings($db, $shop);
    if (count($settings) < 1) {
        $db->query("INSERT INTO bundle_advance_settings(shop) values('$shop')");
    }
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

    if ($chargeType == "one-time") {
        $recu = $shopify("POST",  APIVERSION."application_charges.json", $charge);
		if(is_array($recu) && isset($recu["confirmation_url"])) {
			$confirmation_url = $recu["confirmation_url"];
		} else {
			$confirmation_url = "";
		}
    } else {
        $recu = $shopify("POST",  APIVERSION."recurring_application_charges.json", $charge);
		if(is_array($recu) && isset($recu["confirmation_url"])) {
			$confirmation_url = $recu["confirmation_url"];
		} else {
			$confirmation_url = "";
		}
    }
    $db->query("update tbl_usersettings set confirmation_url = '$confirmation_url' where store_name = '$shop' and app_id = $appId");

    // Gui email cho customer khi cai dat
	require 'email/install_email.php';
	
    // add js to shop
    $check = true;
    $putjs1 = $shopify('GET',  APIVERSION.'script_tags.json');
    if ($putjs1) {
        foreach ($putjs1 as $value) {
            if ($value["src"] == $rootLink . '/customer/bundle_advance.js') {
                $check = false;
            }
        }
    }
    if ($check) {
        $putjs = $shopify('POST',  APIVERSION.'script_tags.json', array('script_tag' => array('event' => 'onload', 'src' => $rootLink . '/customer/bundle_advance.js')));
    }
    
    //hook when user remove app
    $shopify('POST',  APIVERSION.'webhooks.json', 
        array('webhook' =>
            array(
                'topic' => 'app/uninstalled',
                'address' => $rootLink . '/uninstall.php',
                'format' => 'json'
            )
        )
    );
    $shopify('POST',  APIVERSION.'webhooks.json', 
        array('webhook' =>
            array(
                'topic' => 'orders/create',
                'address' => $rootLink . '/customer/bundle_advance.php?shop=' .$shop .'&action=webhookCreateOrder',
                'format' => 'json'
            )
        )
    );

    if($chargeType == "free"){
        $db->query("update tbl_usersettings set status = 'active' where store_name = '$shop' and app_id = $appId");
        header('Location: https://'.$shop.'/admin/apps/'.$apiKey.'');
    } else {
        header('Location: ' . $confirmation_url);
    }
	
}

function checkInstalled($db, $shop, $appId) {
    $sql = "select * from shop_installed where shop = '$shop' and app_id = $appId";
    $query = $db->query($sql);
    if ($query->num_rows > 0) {
        while ($row = $query->fetch_assoc()) {
            $date_instaled = $row["date_installed"];
            $result = array(
                "installed_date" => $date_instaled,
                "installed" => true
            );
            return $result;
        }
    } else {
        $result = array(
            "installed" => false
        );
        return $result;
    }
}

function getShopSettings($db, $shop) {
    $sql = "SELECT * FROM bundle_advance_settings WHERE shop = '$shop'";
    $query = $db->query($sql);
    $settings = array();
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $settings = $row;
        }
    }
    return $settings;
}