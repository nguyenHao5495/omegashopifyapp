<?php
ini_set('display_errors', TRUE);
error_reporting(E_ALL);
date_default_timezone_set("Asia/Bangkok");
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods:  GET, POST");
require '../vendor/autoload.php';

use sandeepshetty\shopify_api;

require '../conn-shopify.php';
$shop = "";
if (isset($_GET["action"])) {
    $action = $_GET["action"];
    $shop = $_GET["shop"];
    $shopify = shopifyInit($db, $shop, $appId);
    if ($action == "getSettings") {
        $settings = fetchDbObject("SELECT * FROM bundle_advance_settings WHERE shop = '$shop'");
        echo json_encode($settings);
    }
    if($action == "updateCacheData"){
        if (is_dir(CACHE_PATH."$shop")) {
            remove_dir(CACHE_PATH."$shop");
        }
        echo json_encode(true);
    }
    if ($action == "countProducts") {
        $count = $shopify("GET",  APIVERSION."products/count.json");
        echo json_encode($count);
    }
    if ($action == "getProducts") { 
        $since_id = $_GET["since_id"];
        $limit = $_GET["limit"];
        $products = $shopify("GET",  APIVERSION."products.json?since_id=$since_id&limit=$limit&fields=id,title,handle,image");
        echo json_encode($products);
    }

    if ($action == "getTotalBundle") {
        $bundle = fetchDbArray("SELECT * FROM bundle_advance_bundles WHERE shop = '$shop' ORDER BY bundle_order ");
        echo json_encode(count($bundle));
    }
    if ($action == "getBundles") {
        $page = $_GET['page']; 
        $limit = 10;
        $start = ($page - 1) *  $limit;
        $where = "";
        if(isset($_GET['search']) && $_GET['search'] != null){
            $search = $_GET['search'];
            $where = "AND bundle_name LIKE '%$search%'";
        }
        $bundle = fetchDbArray("SELECT * FROM bundle_advance_bundles WHERE shop = '$shop'  $where ORDER BY bundle_order LIMIT $start, $limit ");
        echo json_encode($bundle);
    }

    if ($action == "getSpecificProducts") {
        $bundle_id = $_GET["bundle_id"];
        $results = fetchDbArray("SELECT * FROM bundle_advance_products WHERE shop = '$shop' AND bundle_id='$bundle_id'");
        echo json_encode($results);
    }
    if ($action == "getRules") {
        $bundle_id = $_GET["bundle_id"];
        $results = fetchDbArray("SELECT * FROM bundle_advance_rules WHERE shop = '$shop' AND bundle_id='$bundle_id'");
        echo json_encode($results);
    }
    if ($action == "countTotalProducts") {
        $id = $_GET["id"];
        $result = $db->query("SELECT COUNT(*) FROM bundle_advance_products WHERE shop = '$shop' AND bundle_id = $id");
        $count = $result->fetch_array(MYSQLI_BOTH);
        echo $count['COUNT(*)'];
    }
    if ($action == "countRules") {
        $id = $_GET["id"];
        $result = $db->query("SELECT COUNT(*) FROM bundle_advance_rules WHERE shop = '$shop' AND bundle_id = $id");
        $count = $result->fetch_array(MYSQLI_BOTH);
        echo $count['COUNT(*)'];
    }
    if ($action == "deleteBundle") {
        $id = $_GET["id"];
        $query_bundle   = $db->query("DELETE FROM bundle_advance_bundles WHERE shop = '$shop' AND id = '$id'");
        $query_products = $db->query("DELETE FROM bundle_advance_products WHERE shop = '$shop' AND bundle_id = '$id'");
        $query_rules    = $db->query("DELETE FROM bundle_advance_rules WHERE shop = '$shop' AND bundle_id = '$id'");
        echo json_encode($query_bundle && $query_products && $query_rules);
    }
    if ($action == 'getOrders') {
        $orders = fetchDbArray("SELECT * FROM bundle_advance_orders WHERE shop = '$shop' ORDER BY created_at ");
        if (count($orders) > 0) {
            $listOrderIds = array();
            foreach ($orders as $order) {
                array_push($listOrderIds, $order["order_id"]);
            }
            $listOrderIds = implode(",", $listOrderIds);
            $orders = $shopify("GET",  APIVERSION."orders.json?ids=" .$listOrderIds ."&fields=created_at,discount_applications,total_price,order_number,id,customer,financial_status");
            usort($orders, function($a, $b) {
                return strtotime($a["created_at"]) - strtotime($b["created_at"]);
            });
            echo json_encode($orders);
        } else {
            echo json_encode(array());
        }
        
    }
    if ($action == 'getUrlViewBundle') {
        $bundle_id = $_GET["bundle_id"];
        $listProducts = fetchDbArray("SELECT * FROM bundle_advance_products WHERE shop = '$shop' AND bundle_id='$bundle_id'");;
        $firstProduct = $listProducts[0];
        if ($firstProduct['product_handle'] == '' || $firstProduct['product_handle'] == null ) {
            $firstProductId = $firstProduct["product_id"];
            $handle = $shopify("GET",  APIVERSION."products/".$firstProductId.".json?fields=handle")['handle'];
            $query = $db->query("UPDATE bundle_advance_products SET product_handle = '$handle' WHERE shop = '$shop' AND bundle_id = $bundle_id AND product_id = '$firstProductId'");
        }else{
            echo 1;
            $handle = $firstProduct['product_handle'];
        }
        echo json_encode($handle);
    }
}

if (isset($_POST["action"])) {
    $action  = $_POST["action"];
    $shop    = $_POST["shop"];
    $shopify = shopifyInit($db, $shop, $appId);
    if ($action == "saveSettings") {
        $settings = $_POST["settings"];
        $result = saveSettings($db, $shop, $settings);
        echo json_encode($result);
    }
    if ($action == "createBundle") {
        $bundle = $_POST["bundle"];
        $result = createBundle($db, $shop, $bundle);
        echo json_encode($result);
    }
    if ($action == "duplicateBundle") {
        $bundle             = $_POST["bundle"];
        $newBundleId        = createBundle($db, $shop, $bundle);
        $bundle_id          = $bundle["id"];
        $listProducts       = fetchDbArray("SELECT * FROM bundle_advance_products WHERE shop = '$shop' AND bundle_id='$bundle_id'");
        $doneCreateProducts = createSpecificProducts($db, $shop, $newBundleId, $listProducts);
        $listRules          = fetchDbArray("SELECT * FROM bundle_advance_rules WHERE shop = '$shop' AND bundle_id='$bundle_id'");
        $doneCreateRules    = createRules($db, $shop, $newBundleId, $listRules);
        echo json_encode($doneCreateProducts && $doneCreateRules);
    }
    if ($action == "updateBundleOrder") {
        $id                 = $_POST["bundle_id"];
        $newOrder           = $_POST["bundle_order"];
        $query = $db->query("UPDATE bundle_advance_bundles SET bundle_order='$newOrder' WHERE shop = '$shop' AND id = '$id'");
    }
    if ($action == "updateBundleStatus") {
        $id                 = $_POST["bundle_id"];
        $enable_bundle      = $_POST["enable_bundle"];
        $query = $db->query("UPDATE bundle_advance_bundles SET enable_bundle='$enable_bundle' WHERE shop = '$shop' AND id = '$id'");
    }
    if ($action == "updateBundle") {
        $bundle             = $_POST["bundle"];
        $bundle_id          = $bundle["id"];
        $doneUpdateBundle   = updateBundle($db, $shop, $bundle);

        $listProducts       = $_POST["products"];
        $deleteProducts     = $db->query("DELETE FROM bundle_advance_products WHERE shop = '$shop' AND bundle_id = '$bundle_id'");
        $doneCreateProducts = createSpecificProducts($db, $shop, $bundle_id, $listProducts);

        $listRules          = $_POST["rules"];
        $deleteRules        = $db->query("DELETE FROM bundle_advance_rules WHERE shop = '$shop' AND bundle_id = '$bundle_id'");
        $doneCreateRules    = createRules($db, $shop, $bundle_id, $listRules);
        echo json_encode($doneUpdateBundle && $doneCreateProducts && $doneCreateRules);
    }
    if ($action == "createProducts") {
        $bundle_id = $_POST["bundle_id"]; 
        $products = $_POST["products"];
        $doneCreateProducts = createSpecificProducts($db, $shop, $bundle_id, $products);
        echo json_encode($doneCreateProducts);
    }
    if ($action == "createRules") {
        $bundle_id  = $_POST["bundle_id"];
        $rules      = $_POST["rules"];
        $doneCreateRules    = createRules($db, $shop, $bundle_id, $rules);
        echo json_encode($doneCreateRules);
    }
}

function show_array($data) {
    if (is_array($data)) {
        echo "<pre>";
        print_r($data);
        echo "<pre>";
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

function getProducts($shopify, $page) {
    $count = $shopify("GET",  APIVERSION."products/count.json");
    $allProducts = array();
    if ($count > 0) {
        $pages = ceil($count / 50);
        for ($i = 0; $i < $pages; $i++) {
            $allProducts = array_merge($allProducts, $shopify("GET",  APIVERSION."products.json?limit=50&page=" . ($i + 1) . "&fields=id,title,handle"));
        }
    }
    return $allProducts;
}

function createBundle ($db, $shop, $bundle) {
    $bundle_name            = $bundle["bundle_name"];
    $bundle_msg             = $bundle["bundle_msg"];
    $bundle_layout          = $bundle["bundle_layout"];
    $success_msg            = $bundle["success_msg"];

    $enable_start_date      = isset($bundle["enable_start_date"]) && $bundle["enable_start_date"] == 1 ? 1 : 0;
    if ($enable_start_date && isset($bundle["start_date"])) {
        $start_date = $bundle["start_date"];
        $start_date = substr($start_date, 0, strpos($start_date, '('));
        $start_date = new DateTime("$start_date", new DateTimeZone('GMT'));
        $start_date = $start_date->format('Y-m-d H:i:s');
    } else {
        $start_date = null;
    }

    $enable_end_date = isset($bundle["enable_end_date"]) && $bundle["enable_end_date"] == 1 ? 1 : 0;
    if ($enable_end_date && isset($bundle["end_date"])) {
        $end_date = $bundle["end_date"];
        $end_date = substr($end_date, 0, strpos($end_date, '('));
        $end_date = new DateTime("$end_date", new DateTimeZone('GMT'));
        $end_date = $end_date->format('Y-m-d H:i:s');
    } else {
        $end_date = null;
    }

    $require_logged_in      = isset($bundle["require_logged_in"]) && $bundle["require_logged_in"] == 1 ? 1 : 0;
    $enable_customer_tags   = isset($bundle["enable_customer_tags"]) && $bundle["enable_customer_tags"] == 1 ? 1 : 0;
    $customer_tags          = $bundle["customer_tags"];

    // Set order
    $max                    = $db->query("SELECT MAX(bundle_order) FROM bundle_advance_bundles WHERE shop = '$shop'");
    $row                    = $max->fetch_array(MYSQLI_BOTH);
    $bundle_order           = $row["MAX(bundle_order)"] != null ? $row["MAX(bundle_order)"] + 1 : 0 ;

    $query = $db->query("INSERT INTO bundle_advance_bundles SET "
            . " bundle_order='$bundle_order',bundle_name='$bundle_name',bundle_msg='$bundle_msg',bundle_layout='$bundle_layout',success_msg='$success_msg',"
            . " enable_start_date='$enable_start_date',start_date='$start_date',"
            . " enable_end_date='$enable_end_date',end_date='$end_date',"
            . " require_logged_in='$require_logged_in',enable_customer_tags='$enable_customer_tags',customer_tags='$customer_tags',"
            . " shop = '$shop'");
    if ($query === TRUE) {
        $last_id = $db->insert_id;
        return $last_id;
    } else {
        return NULL;   
    }
}

function updateBundle ($db, $shop, $bundle) {
    $id                     = $bundle["id"];
    $bundle_name            = $bundle["bundle_name"];
    $bundle_msg             = $bundle["bundle_msg"];
    $success_msg            = $bundle["success_msg"];
    $bundle_layout          = $bundle["bundle_layout"];
    $enable_start_date      = isset($bundle["enable_start_date"]) && $bundle["enable_start_date"] == 1 ? 1 : 0;
    if ($enable_start_date && isset($bundle["start_date"])) {
        $start_date = $bundle["start_date"];
        $start_date = substr($start_date, 0, strpos($start_date, '('));
        $start_date = new DateTime("$start_date", new DateTimeZone('GMT'));
        $start_date = $start_date->format('Y-m-d H:i:s');
    } else {
        $start_date = null;
    }

    $enable_end_date = isset($bundle["enable_end_date"]) && $bundle["enable_end_date"] == 1 ? 1 : 0;
    if ($enable_end_date && isset($bundle["end_date"])) {
        $end_date = $bundle["end_date"];
        $end_date = substr($end_date, 0, strpos($end_date, '('));
        $end_date = new DateTime("$end_date", new DateTimeZone('GMT'));
        $end_date = $end_date->format('Y-m-d H:i:s');
    } else {
        $end_date = null;
    }
    $require_logged_in      = isset($bundle["require_logged_in"]) && $bundle["require_logged_in"] == 1 ? 1 : 0;
    $enable_customer_tags   = isset($bundle["enable_customer_tags"]) && $bundle["enable_customer_tags"] == 1 ? 1 : 0;
    $customer_tags          = $bundle["customer_tags"];

    $query = $db->query("UPDATE bundle_advance_bundles SET "
            . " bundle_name='$bundle_name',bundle_msg='$bundle_msg',bundle_layout='$bundle_layout',success_msg='$success_msg',"
            . " enable_start_date='$enable_start_date',start_date='$start_date',"
            . " enable_end_date='$enable_end_date',end_date='$end_date',"
            . " require_logged_in='$require_logged_in',enable_customer_tags='$enable_customer_tags',customer_tags='$customer_tags'"
            . " WHERE shop = '$shop' AND id ='$id'");
    return $query;
}

function createSpecificProducts($db, $shop, $bundleId, $listProducts) {
    $i = 0;
    foreach ($listProducts as $product) {
        $product_id = isset($product["product_id"]) ? $product["product_id"] : $product["id"];
        $product_handle = isset($product["handle"]) ? $product["handle"] : "";
        $product_quantity = $product["product_quantity"];
        $query = $db->query("INSERT INTO bundle_advance_products SET "
                . "product_id='$product_id',product_handle='$product_handle',bundle_id='$bundleId',product_quantity='$product_quantity', "
                . "shop = '$shop'");
        if ($query == TRUE) {
            $i ++;
        }
    };
    return $i == count($listProducts);
}

function createRules($db, $shop, $bundleId, $listRules) {
    $i = 0;
    foreach ($listRules as $rule) {
        $discount_type  = $rule["discount_type"];
        $quantity       = $rule["quantity"];
        $amount         = $rule["amount"];
        $query = $db->query("INSERT INTO bundle_advance_rules SET "
                . "bundle_id='$bundleId',discount_type='$discount_type',quantity='$quantity',amount='$amount',"
                . "shop = '$shop'");
        if ($query == TRUE) {
            $i ++;
        }
    };
    return $i == count($listRules);
}

function saveSettings ($db, $shop, $settings) {
    $max_bundles                = $settings["max_bundles"];

    $title_background_color     = $settings["title_background_color"];
    $title_text_color           = $settings["title_text_color"];
    $title_text_size            = $settings["title_text_size"];

    $button_text                = $settings["button_text"];
    $button_discount_text       = $settings["button_discount_text"];
    $button_text_color          = $settings["button_text_color"];
    $button_text_size           = $settings["button_text_size"];
    $button_background_color    = $settings["button_background_color"];

    $custom_position            = $settings["custom_position"];
    $position                   = $settings["position"];

    $enable_admin_mode          = $settings["enable_admin_mode"] ? $settings["enable_admin_mode"] : 0;
    $order_tag                  = $settings["order_tag"];
    $custom_css                 = $settings["custom_css"];
    $typeRule                 = $settings["typeRule"];
    
    $query = $db->query("UPDATE bundle_advance_settings SET "
            . " max_bundles='$max_bundles',"
            . " title_background_color='$title_background_color',title_text_color='$title_text_color',title_text_size='$title_text_size',"
            . " button_text='$button_text',button_discount_text='$button_discount_text',button_text_color='$button_text_color',button_text_size='$button_text_size',button_background_color='$button_background_color',"
            . " custom_position='$custom_position',position='$position',enable_admin_mode='$enable_admin_mode',order_tag='$order_tag',custom_css='$custom_css',typeRule='$typeRule'"
            . " WHERE shop = '$shop'");
    return $query;
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
function remove_dir($dir = null) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
  
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir."/".$object) == "dir") remove_dir($dir."/".$object);
          else unlink($dir."/".$object);
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }