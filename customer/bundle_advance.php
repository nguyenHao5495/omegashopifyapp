<?php
ini_set('display_errors', TRUE);
error_reporting(E_ALL);
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
require '../vendor/autoload.php';

use sandeepshetty\shopify_api;

require '../conn-shopify.php';

if (isset($_GET["action"])) {
    $action = $_GET["action"];
    $shop = $_GET["shop"];
    $shopify = shopifyInit($db, $shop, $appId);
    $customerId = isset($_GET["customerId"]) ? $_GET["customerId"] : null;
    $customerTags = $customerId != null ? array_map('trim', explode(",", $shopify("GET",  APIVERSION."customers/".$customerId.".json?fields=tags")["tags"])) : array();
    if ($action == "checkExpiredAndGetSettings") {
        $expired                    = checkExpired($db, $shop, $appId, $trialTime);
        $settings                   = fetchDbObject("SELECT *  FROM bundle_advance_settings WHERE shop = '$shop'");
        $position                   = getPosition($settings['position'], $rootLink, $shopify);
        $settings["position"]       = $position;
        $moneyFormat                = isset($shopify("GET",  APIVERSION."shop.json?fields=money_format")['money_format']) ? $shopify("GET",  APIVERSION."shop.json?fields=money_format")['money_format']:'';
        $settings["money_format"]   = strip_tags($moneyFormat);
        $settings["v"]              = time();
        $response = array(
            "settings"  => $settings,
            "expired"   => $expired
        );
        echo json_encode($response);
    }
    if ($action == "getBundles") {
        $settings = fetchDbObject("SELECT max_bundles FROM bundle_advance_settings WHERE shop = '$shop'");
        $productId = $_GET["productId"];
        $limit = $settings["max_bundles"];
        $listBundles = fetchDbArray("SELECT bundle.*, product.product_id, product.shop "
        . "FROM bundle_advance_bundles as bundle "
        . "INNER JOIN bundle_advance_products as product ON product.bundle_id = bundle.id "
        . "WHERE product.product_id = $productId AND product.shop='$shop' AND bundle.enable_bundle = 1 "
        . "ORDER BY bundle.bundle_order ASC LIMIT 0,$limit");
        $listProducts = array();
        foreach ($listBundles as &$bundle) {
            $bundleId = $bundle["id"];
            $listBundleProducts = fetchDbArray("SELECT product_id,product_quantity FROM bundle_advance_products WHERE bundle_id = $bundleId AND shop ='$shop'");
            $bundle["products"] = array();
            $showProduct["product_quantity"] = '';
            foreach($listBundleProducts as $product) {
                $index = array_search($product["product_id"], array_column($listProducts, "id"));
                if ($index > -1) {
                    $showProduct = $listProducts[$index];
                    $showProduct["product_quantity"] = $product["product_quantity"];
                    $bundle["products"][] = $showProduct;
                } else {
                    $newProduct = $shopify("GET",  APIVERSION."products/". $product['product_id']. ".json?fields=id,variants,handle,title,image,options,images");
                    $showProduct = $newProduct;
                    $showProduct["product_quantity"] = isset($product["product_quantity"]) ? $product["product_quantity"] : '';
                    $bundle["products"][] = $showProduct;
                    $listProducts[] = $newProduct;
                }
                $bundle["rules"]    = fetchDbArray("SELECT discount_type, amount, quantity FROM bundle_advance_rules WHERE bundle_id = $bundleId AND shop ='$shop'");
            }
        }
        echo json_encode($listBundles);
    }
    if ($action == 'getCustomerTags') {
        $customerId = $_GET["customerId"];
        $result     = $shopify("GET",  APIVERSION."customers/".$customerId.".json?fields=tags");
        echo json_encode($result["tags"]);
    }
    if ($action == 'webhookCreateOrder') {
        $webhook = fopen('php://input', 'rb');
        $webhookContent = '';
        while (!feof($webhook)) {
            $webhookContent .= fread($webhook, 4096);
        }
        fclose($webhook);
        if ($webhookContent != "") {
            $settings = fetchDbObject("SELECT *  FROM bundle_advance_settings WHERE shop = '$shop'");
            $convertOrder = convert_object_to_array(json_decode($webhookContent));
            $orderId = $convertOrder["id"];
            $tags = array();
            if ($convertOrder['tags'] != '') {
                array_push($tags, $convertOrder['tags']);
            }
            foreach ($convertOrder['note_attributes'] as $v) {
                if ($v['name'] == "Source" && strtolower(trim($v['value'])) == strtolower(trim($settings['order_tag']))) {
                    array_push($tags, $v['value']);
                    $now = date("Y-m-d H:i:s");
                    $db->query("INSERT INTO bundle_advance_orders SET order_id='$orderId', shop='$shop', created_at = '$now'");
                }
            }
            $converTags = implode(',', $tags);
            $updatedTags = array(
                "order" => array(
                    "id" => $orderId,
                    "tags" => $converTags
                )
            );
            $put = $shopify("PUT",  APIVERSION."orders/$orderId.json", $updatedTags);
        }
    }
}

if (isset($_POST["action"])) {
    $action = $_POST["action"];
    $shop = $_POST["shop"];
    $shopify = shopifyInit($db, $shop, $appId);
    $customerId = isset($_POST["customerId"]) ? $_POST["customerId"] : null;
    $customerTags = $customerId != null ? array_map('trim', explode(",", $shopify("GET",  APIVERSION."customers/".$customerId.".json?fields=tags")["tags"])) : array();
    if ($action == 'createDiscountPrice') {
        deleteOldAndUnusedDiscountCode($shopify);
        $cart = $_POST["cart"];
        if (!empty($cart["items"])) {
            // Sort cart items by price
            usort($cart["items"], function ($a, $b) {
                return ($a["original_price"] > $b["original_price"]) ? -1 : 1;
            });
            // Get list of bundle available for each product
            $listBundles = [];
            foreach ($cart["items"] as $item) {
                $productId = $item["product_id"];
                $bundles = fetchDbArray("SELECT bundle.*, product.product_id "
                    . "FROM bundle_advance_bundles as bundle "
                    . "INNER JOIN bundle_advance_products as product ON product.bundle_id = bundle.id "
                    . "WHERE product.product_id = $productId AND bundle.shop='$shop' AND bundle.enable_bundle = 1 "
                    . "ORDER BY bundle.bundle_order ASC");
                
                foreach ($bundles as $bundle) {
                    $bundleId = $bundle["id"]; 
                    $key  = array_search($bundleId, array_column($listBundles, "id"));  
                    if (gettype($key) == 'boolean' && $key == false) {  
                        if ($bundle && checkBundleValid($bundle, $customerId, $customerTags)) { 
                            $bundle["products"] = fetchDbArray("SELECT product_id,product_quantity FROM bundle_advance_products WHERE bundle_id = '$bundleId' AND shop ='$shop'");
                            $bundle["rules"]    = fetchDbArray("SELECT discount_type, amount, quantity FROM bundle_advance_rules WHERE bundle_id = '$bundleId' AND shop ='$shop'");
                            $listBundles[]      = $bundle; 
                        }
                    } 
                }
            }

            // Sort bundle by bundle_order
            usort($listBundles, function($a, $b) {
                return ($a['bundle_order'] < $b['bundle_order']) ? -1 : 1;
            });

            // Calculate discount price 
            $discountPrice = calculateDiscountPrice($listBundles, $cart, 0);
            echo $discountPrice;
        } else {
            echo 0;
        }
    }
    if ($action == 'createDiscountCode') {
        $discountPrice = $_POST["discountPrice"];
        $newDiscountCode = createNewDiscountCode($shopify, $discountPrice);
        echo json_encode($newDiscountCode);
    }
}

function calculateDiscountPrice($listBundles, $cart, $discountPrice) {
    // Set discount price
    // if discount price still has value, recursion is being called
    // else if discount price is undefined, function is being called the first time
    $discountPrice = isset($discountPrice) ? $discountPrice : 0;

    // Assign matching bundle to $currentBundle
    // if currentBundle still has value, listBundles still matching
    $currentBundle;
    $currentBundleIndex;
    // Check from list Bundles
    // we have two case:
    // Case 1: cart still have items match with bundle product conditional => break loop, assign that bundle to $currentBundle
    // Case 2: cart have no item match with bundle => remove that bundle from list, check next bundle
    foreach ($listBundles as $k=>$bundle) {
        $count = 0;
        foreach ($cart["items"] as $item) {
            $index = array_search($item["product_id"], array_column($bundle["products"], "product_id"));
            if (gettype($index) == 'integer') {
                $count ++;
            }
        }
        if (1 <= $count) {
            $currentBundle = $bundle;
            $currentBundleIndex = $k;
            break;
        } else {
            unset($listBundles[$k]);
        }
    }


    // Get all products from cart which match with list product_id in bundle
    // This will be push into variable: $listWillBeDiscounted
    if (isset($currentBundle)) {
        $listWillBeDiscounted = [];
        $totalQuantity = 0;
        foreach ($cart["items"] as $k=>$item) {
            // Check if product is in bundle, we will apply discount
            $index = array_search($item["product_id"], array_column($currentBundle["products"], "product_id"));
            if (gettype($index) == 'integer') {
                $listWillBeDiscounted[] = $item;
                $totalQuantity += $item["quantity"];
            }
        }

        // After haved list will be discounted
        // We will find exactly rule having quantity match with totalQuantity
        // We will use recursion to find that, so if totalQuantity not match, find totalQuantity - 1
        // return value into $discountedQuantity
        // If quantity is equal zero, rules of currentBundle dont have any quantity match with
        if (count($listWillBeDiscounted) > 0) {
            $discountedQuantity = findQuantityWillBeDiscounted($totalQuantity, $currentBundle["rules"]);
            if ($discountedQuantity > 0) {
                $totalPrice = 0;
                $count = 0;
                foreach ($listWillBeDiscounted as $key => $item) {
                    $index = array_search($item["product_id"], array_column($cart["items"], "product_id"));
                    if ($count < $discountedQuantity) {
                        $restQuantity = $discountedQuantity - $count;
                        if ($restQuantity < $item["quantity"]) {
                            $totalPrice += ($item["original_price"])*$restQuantity;
                            $count = $count + $restQuantity;
                            $cart["items"][$index]["quantity"] = $item["quantity"] - $restQuantity;
                        } else {
                            $totalPrice += ($item["original_price"])*$item["quantity"];
                            $count = $count + $item["quantity"];
                            unset($cart["items"][$index]);
                            $cart["items"] = array_values($cart["items"]);
                        }
                    } else {
                        break;
                    }
                }
                // Find rule having discounted quantity
                $index = array_search($discountedQuantity, array_column($currentBundle["rules"], "quantity"));
                $rule = $currentBundle["rules"][$index];

                switch ($rule["discount_type"]) {
                    case 'percent_off':
                        $discountPrice += $totalPrice*$rule["amount"]/100;
                        break;
                    case 'fixed_price_off':
                        if ($rule["amount"]*100 < $totalPrice) {
                            $discountPrice += $rule["amount"]*100;
                        } else {
                            $discountPrice += $totalPrice;
                        }
                        break;
                    case 'fixed_last_price':
                        if ($rule["amount"]*100 < $totalPrice) {
                            $discountPrice += $totalPrice - $rule["amount"]*100;
                        } else {
                            $discountPrice += $totalPrice;
                        }
                        break;
                    default:
                        break;
                }
            } else {
                unset($listBundles[$currentBundleIndex]);
            }
        }
    }
    
    if ((isset($cart["items"]) && count($cart["items"]) > 0) && (isset($listBundles) && count($listBundles) > 0)) {
        return calculateDiscountPrice($listBundles, $cart, $discountPrice);
    } else {
        return $discountPrice;
    }
}

function findQuantityWillBeDiscounted($totalQuantity, $listRules) {
    $maxQuantity = $listRules[count($listRules) - 1]["quantity"];
    if ($totalQuantity >= $maxQuantity) {
        return $maxQuantity;
    } else {
        if ($totalQuantity >= 1) {
            $index = array_search($totalQuantity, array_column($listRules, "quantity"));
            if (gettype($index) == "integer") {
                return $listRules[$index]["quantity"];
            } else {
                $totalQuantity = $totalQuantity - 1;
                return findQuantityWillBeDiscounted($totalQuantity, $listRules);
            }
        } else {
            return 0;
        }
    }
}

function fetchDbObject ($sql) {
    global $db;
    global $shop;
    $query = $db->query($sql);
    $object = array();
    if ($query) {
        while ($row = $query->fetch_assoc()) {
            $object = $row;
        }
    }
    return $object;
}

function fetchDbArray ($sql) {
    global $db;
    global $shop;
    $result = [];
    $query = $db->query($sql);
    while ($row = mysqli_fetch_assoc($query)) {
        $result[] = $row;
    }
    return $result;
}

function createNewDiscountCode ($shopify, $discountPrice) {
    $code = 'OT_' ;
    $code .= substr(md5(uniqid(rand(1,6))), 0, 16);
	$date = strtotime("- 1 day");
    $newDiscountRule = array (
        'price_rule' => array (
            'title' => $code,
            'target_type' => 'line_item',
            'target_selection' => 'all',
            'allocation_method' => 'across',
            'value_type' => 'fixed_amount',
            'value' => -$discountPrice,
            'customer_selection' => 'all',
            'starts_at' => date('Y-m-d H:i:s',$date),
            'usage_limit' => 1
        )
    );
    $newDiscountRule = $shopify("POST",  APIVERSION."price_rules.json", $newDiscountRule);
    $newDiscountCode = array (
        'discount_code' => array(
            'code' => $code
        )
    );
    $newDiscountCode = $shopify("POST",  APIVERSION."price_rules/". $newDiscountRule['id'] ."/discount_codes.json", $newDiscountCode);
    return $newDiscountCode;
}

function deleteOldAndUnusedDiscountCode ($shopify) {
    $listPriceRules = $shopify("GET",  APIVERSION."price_rules.json?times_used=0");
    foreach($listPriceRules as $priceRule) {
        if (substr( $priceRule["title"], 0, 3 ) === "OT_") {
            $listDiscountCodes = $shopify("GET",  APIVERSION."price_rules/". $priceRule["id"] ."/discount_codes.json");
            if ($listDiscountCodes && !isset($listDiscountCodes["errors"]) && count($listDiscountCodes) > 0) {
                foreach($listDiscountCodes as $discountCode) {
                    $shopify("DELETE",  APIVERSION."price_rules/". $priceRule["id"] ."/discount_codes/". $discountCode["id"] .".json");
                };
            }
            $shopify("DELETE",  APIVERSION."price_rules/".$priceRule["id"].".json");
        }
    }
}

function checkBundleValid ($bundle, $customerId, $customerTags) {
    $checkDate = checkDateBundle($bundle); 
    $checkCustomer = checkCustomerBundle($bundle, $customerId, $customerTags); ;
    return json_decode($checkDate == 1 && $checkCustomer == 1);
}

function checkDateBundle($bundle) {
    $checkDate = 1;
    $today = time();
    $rawStartDate = DateTime::createFromFormat('Y-m-d H:i:s',$bundle['start_date']);
    $startDate = strtotime($rawStartDate->format('Y-m-d H:i:s'));
    $rawEndDate = DateTime::createFromFormat('Y-m-d H:i:s', $bundle['end_date']);
    $endDate   = strtotime($rawEndDate->format('Y-m-d H:i:s'));
    if ($bundle['enable_start_date'] == 1 && $bundle['enable_end_date'] == 1) { 
        $checkDate = (($today >= $startDate && $today <= $endDate) ? 1 : 0);  
    } else if ($bundle['enable_start_date'] == 1) {
        $checkDate = (($today >= $startDate) ? 1 : 0); 
    } else if ($bundle['enable_end_date'] == 1) {
        $checkDate = (($today <= $endDate) ? 1 : 0);
    }
    return $checkDate;
}

function checkCustomerBundle($bundle, $customerId, $customerTags) {
    $checkCustomer = 1;
   
    if ($bundle['require_logged_in'] == 1) {
        $checkCustomer = $customerId != null ? 1 : 0;
        if ($bundle['enable_customer_tags'] == 1 && $checkCustomer == 1) {
            $bundleTags    = array_map('trim', explode(',', $bundle["customer_tags"]));
            $check         = array_intersect($bundleTags, $customerTags);
            $checkCustomer = count($check) > 0 ? 1 : 0;
        }
    } 
   
    return $checkCustomer;
}


function getPosition($type, $rootLink, $shopify) {
    $theme = strtolower(getMainTheme($shopify));
    $themeLists = file_get_contents(dirname(__FILE__)."/themes.json");
    $themeLists = json_decode($themeLists);
    $position = "";
    foreach ($themeLists as $value) {
        if(strpos($theme, $value->name) !== false){
            if ($type == 'under_product_addcart'){
                $position = "form[action^='/cart/add']:first";
            } else if ($type == 'under_product_title'){
                $position = $value->product_title;
            } else if ($type == 'under_product_price'){
                $position = $value->product_price;
            } else if ($type == 'under_product_description') {
                $position = $value->product_description;
            }
        }
    }
    return $position;
}

function convert_object_to_array($data) {
    if (is_object($data)) {
        $data = get_object_vars($data);
    }
    if (is_array($data)) {
        return array_map(__FUNCTION__, $data);
    } else {
        return $data;
    }
}

function show_array($data) {
    if (is_array($data)) {
        echo "<pre>";
        print_r($data);
        echo "<pre>";
    }
}

function checkExpired($db, $shop, $appId, $trialTime) {
    $shop_data = $db->query("select * from tbl_usersettings where store_name = '" . $shop . "' and app_id = $appId");
    $shop_data = $shop_data->fetch_object();
    $installedDate = $shop_data->installed_date;
    $confirmation_url = $shop_data->confirmation_url;
    $clientStatus = $shop_data->status;

    $date1 = new DateTime($installedDate);
    $date2 = new DateTime("now");
    $interval = date_diff($date1, $date2);
    $diff = (int)$interval->format('%R%a');
    if(($diff > $trialTime && $clientStatus != 'active') || $clientStatus == 'trial'){
        return true;
    } else {
        return false;
    }
}

function shopifyInit($db, $shop, $appId) {
    $select_settings = $db->query("SELECT * FROM tbl_appsettings WHERE id = $appId");
    $app_settings = $select_settings->fetch_object();
    $shop_data = $db->query("select * from tbl_usersettings where store_name = '" . $shop . "' and app_id = $appId");
    $shop_data = $shop_data->fetch_object();
    if(isset($shop_data->access_token)){
        $shopify = shopify_api\client(
            $shop, $shop_data->access_token, $app_settings->api_key, $app_settings->shared_secret
        );
        return $shopify;
    } else {
        die();
    }
}

function getMainTheme($shopify) {
	$result = "";
	$themes = $shopify('GET',  APIVERSION.'themes.json');
	if(!isset($themes) && !is_array($themes)) return "";
	foreach ($themes as $theme){
		if($theme["role"] == 'main') $result = $theme["name"];
	}
	return $result;
}
