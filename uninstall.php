<?php
session_start();
require 'conn-shopify.php';

unset($_SESSION['shop']);
$webhookContent = "";

$webhook = fopen('php://input', 'rb');
while (!feof($webhook)) {
    $webhookContent .= fread($webhook, 4096);
}

fclose($webhook);

$webhookContent = json_decode($webhookContent);
if (isset($webhookContent->myshopify_domain)) {
    $shop = $webhookContent->myshopify_domain;
    $sql = 'delete from tbl_usersettings where store_name = "' . $shop . '" and app_id = ' . $appId;
    $db->query($sql);
    // Gui email cho customer khi uninstalled
	 require 'email/uninstall_email.php';	
} else if (isset($webhookContent->domain)) {
    $shop = $webhookContent->domain;
    $sql = 'delete from tbl_usersettings where store_name = "' . $shop . '" and app_id = ' . $appId;
    $db->query($sql);
	// Gui email cho customer khi uninstalled
	 require 'email/uninstall_email.php';	
}