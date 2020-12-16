<?php
	header('Set-Cookie: cross-site-cookie=name; SameSite=None; Secure');
    ini_set('display_errors', TRUE);
    error_reporting(E_ALL);
    require '../vendor/autoload.php';
    use sandeepshetty\shopify_api;
    require '../conn-shopify.php';
    session_start();

    if (isset($_GET['shop'])) {
        $shop = $_GET['shop'];
    }
    $select_settings = $db->query("SELECT * FROM tbl_appsettings WHERE id = $appId");
    $app_settings = $select_settings->fetch_object();
    $shop_data = $db->query("select * from tbl_usersettings where store_name = '" . $shop . "' and app_id = $appId");
    $shop_data = $shop_data->fetch_object();
    $shopify = shopify_api\client(
        $shop, $shop_data->access_token, $app_settings->api_key, $app_settings->shared_secret
    );
    $shopInfo = $shopify("GET",  APIVERSION."shop.json");
	$money_format = strip_tags($shopInfo['money_format']);
	

	$confirmUrl = $shop_data->confirmation_url;
	$clientStatus = $shop_data->status;
	if ($clientStatus != 'active') {
		header('Location: ' . $rootLink . '/chargeRequire.php?shop=' . $shop);
	}	
	
?>
<!DOCTYPE html>
<head>
    <title>Bundlify - Cross selling boost Admin</title>
    <meta content="width=device-width,initial-scale=1,minimal-ui" name="viewport">
    <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Roboto:400,500,700,400italic,700italic|Material+Icons">
    <link rel="stylesheet" href="lib/vue-material/default.css">
    <link rel="stylesheet" href="lib/vue-material/vue-material.min.css">
    <link rel="stylesheet" href="lib/vue-multiselect/vue-multiselect.min.css">
    <link rel="stylesheet" href="lib/vue-flatpickr/flatpickr.min.css">
    <link rel="stylesheet" href="css/styles.css?v=<?php echo $v; ?>">
    <link rel="stylesheet" href="css/preview.css?v=<?php echo $v; ?>">
</head>
<body style="overflow-x: hidden;">
	<span id="shop" style="display: none" class="shopName"><?php echo $shop; ?></span>
    <div id="combo-products-app">
        <md-tabs :md-active-tab="activeTab" md-alignment="centered" @md-changed="changeTab" :md-dynamic-height="true">
            <md-tab id="tab-orders" md-icon="shopping_cart" md-label="Orders">
                <orders :products="products" @change-tab="changeTab" ref="orders"></orders>
            </md-tab>
            <md-tab id="tab-bundle" md-icon="layers" md-label="Bundle">
                <bundle :products="products" @change-tab="changeTab" @reload-bundles="reloadBundle" ref="bundle"></bundle>
            </md-tab>
            <md-tab id="tab-settings" md-icon="settings" md-label="Settings">
                <settings ref="settings"></settings>
            </md-tab>
            <md-tab id="tab-documents" md-icon="description" md-label="Document">
                <document></document>
            </md-tab>
        </md-tabs>
        <md-snackbar :md-position="'center'" :md-duration="'infinity'" :md-active.sync="showSnackbar" md-persistent>
            <span>If you're using Custom Theme or after installed app and nothing appears, please contact <a href="mailto:contact@omegatheme.com">contact@omegatheme.com</a> and we will assist you in the shortest time.</span>
            <md-button class="md-primary" @click="showSnackbar = false">.
                <md-icon>close</md-icon>
            </md-button>
        </md-snackbar>		
    </div>

    <div class=" md-layout">
        <!--<div class="md-layout-item md-size-100" style="text-align: center;">
            <button class="btn-docs">
                <p>See our 
                    <a href="https://bundle-advance.myshopify.com/pages/document" target="_blank" style="color: #448aff;">
                        document
                    </a>
                </p>
            </button>
        </div> -->
        <div class="md-layout-item md-size-100" style="text-align: center; margin-top: 10px;">
            <span>Some other sweet <strong>Omega</strong> apps you might like!</span>
            <a target="_blank" href="https://apps.shopify.com/partners/omegaapps">
                (View all app)
            </a>
        </div>
        <div class="md-layout-item md-size-100">
            <div class="md-layout md-gutter md-alignment-top-space-around">
                <div class="md-layout-item md-size-40 md-small-size-100" style="text-align: center;">
                    <p><a href="https://apps.shopify.com/quantity-price-breaks-limit-purchase?utm_source=bundle-advance_admin&surface_type=bundle-advance" target="_blank"><img alt="Quantity Price Breaks by Omega" src="https://s3.amazonaws.com/shopify-app-store/shopify_applications/small_banners/5143/splash.png?1452220345"></a></p>
                </div>
                <div class="md-layout-item md-small-size-100" style="text-align: center;">
                    <p><a href="https://apps.shopify.com/facebook-reviews-1?utm_source=bundle-advance_admin&surface_type=bundle-advance" target="_blank"><img alt="Facebook Reviews by Omega" src="https://s3.amazonaws.com/shopify-app-store/shopify_applications/small_banners/13331/splash.png?1499916138"></a></p>
                </div>
                <div class="md-layout-item md-size-40 md-small-size-100" style="text-align: center;">
                    <p><a href="https://apps.shopify.com/order-tagger-by-omega?utm_source=bundle-advance_admin&surface_type=bundle-advance" target="_blank"><img alt="Order Tagger by Omega" src="https://s3.amazonaws.com/shopify-app-store/shopify_applications/small_banners/17108/splash.png?1510565540"></a></p>
                </div>
            </div>
        </div>
        <div class="md-layout-item md-size-100" style="text-align: center;">
            ©2017 <a href="https://www.omegatheme.com/" target="_blank">Omegatheme</a> All Rights Reserved.
        </div>
    </div>
	
        <?php require 'review/star.php'; ?>
        <?php include 'facebook-chat.html'; ?>
		
    <script src="https://cdn.shopify.com/s/assets/external/app.js"></script>
    <script>
        window.shop = "<?php echo $shop; ?>";
        window.rootlink = "<?php echo $rootLink; ?>";
        window.money_format = "<?php echo $money_format; ?>";
        window.v = "<?php echo $v; ?>";

        ShopifyApp.init({
            apiKey: '<?php echo $apiKey; ?>',
            shopOrigin: 'https://<?php echo $shop; ?>',
        });
        ShopifyApp.ready(function(){
            ShopifyApp.Bar.initialize({});
        });
    </script>
	
        <!-- Global site tag (gtag.js) - Google Analytics -->
		<script async src="https://www.googletagmanager.com/gtag/js?id=UA-126587266-1"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());

		  gtag('config', 'UA-126587266-1');
		</script>
		<?php include 'google_remarketing_tag.txt'; ?>	

    <script src="lib/vue-flatpickr/flatpickr.min.js"></script>

    <script src="lib/vue/vue.js"></script>
    <script src="lib/moment/moment.min.js"></script>
    <script src="lib/vue-flatpickr/vue-flatpickr.min.js"></script>
    <script src="lib/vue-material/vue-material.min.js"></script>
    <script src="lib/vue-loader/http-vue-loader.min.js"></script>
    <script src="lib/vue-resource/vue-resource.js"></script>
    <script src="lib/vue-multiselect/vue-multiselect.min.js"></script>

    <!-- CDNJS :: Vue.Draggable (https://cdnjs.com/) -->
    <script src="//cdn.jsdelivr.net/npm/sortablejs@1.7.0/Sortable.min.js"></script>
    <script src="//cdnjs.cloudflare.com/ajax/libs/Vue.Draggable/2.17.0/vuedraggable.min.js"></script>
    
    <script src="js/app.js?v=<?php echo $v; ?>"></script>
</body>
