<?php 

//get customer email when installed app
$customer = $shopify('GET', '/admin/shop.json');
$email = $customer['email'];
$name = $customer['name'];
	$xml = '<xmlrequest>
		<username>madmin</username>
		<usertoken>a3538fd0f3e37560114ede32c2c0886b387cc667</usertoken>
		<requesttype>subscribers</requesttype>
		<requestmethod>AddSubscriberToList</requestmethod>
		<details>
			<emailaddress>'.$email.'</emailaddress>
			<mailinglist>'.$iemid.'</mailinglist>
			<format>html</format>
			<confirmed>yes</confirmed>
			<customfields>
				<item>
					<fieldid>1</fieldid>
					<value>'.$customer['name'].'</value>
				</item>
				<item>
					<fieldid>3</fieldid>
					<value>'.$customer['phone'].'</value>
				</item>
				<item>
					<fieldid>4</fieldid>
					<value>'.$customer['city'].'</value>
				</item>
				<item>
					<fieldid>6</fieldid>
					<value>'.$customer['zip'].'</value>
				</item>
				<item>
					<fieldid>7</fieldid>
					<value>'.$customer['country'].'</value>
				</item>				
			</customfields>
		</details>
	</xmlrequest>
	';
	$ch = curl_init('https://www.omegatheme.com/iem/xml.php');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
	$result = @curl_exec($ch);
	if($result === false) {
		$db->query("insert into test_data set test = 'Error performing request'");
	}else {
		$xml_doc = simplexml_load_string($result);
		if ($xml_doc->status == 'SUCCESS') {
			$db->query("update shop_installed set email_shop = '$email', name_shop = '$name' where shop = '$shop' and app_id = $appId"); 
		} else {
			$db->query("insert into test_data set test = 'Error is: $xml_doc->errormessage");
			$db->query("update shop_installed set email_shop = '$email', name_shop = '$name' where shop = '$shop' and app_id = $appId"); 
		}
	}	