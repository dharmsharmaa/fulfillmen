<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.8
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/admin/partials
 */

global $wpdb;
$prefix = 'fulfillmen_';
//$GLOBALS['hide_save_button'] = true;
global $woocommerce;
global $post;
$storeName = get_option($prefix . 'fulfillmen_store');
$APIuserID = get_option($prefix . 'fulfillmen_userID');
$apiKey = get_option($prefix . 'fulfillmen_apikey');
$TrackingUrl = get_option($prefix . 'customtrackingurl');
$iFautoMated = get_option($prefix . 'automation_fw_orders');
$userID = get_option($prefix . 'fulfillmen_username');
$userPass = get_option($prefix . 'fulfillmen_password');
$warehouse = get_option($prefix . 'warehouse_ID');
$ordersTable = $wpdb->prefix . 'fmOrders';
$shippingConfig = $wpdb->prefix . 'fmSetShipping';
$TrackResponse ="";
function wf_process_orders($orderid, string $apiurl, array $credentials, $storeSuffix)
{
    global $wpdb;
    $storeName = $storeSuffix;
    global $post;
    global $woocommerce;
    $prefix = 'fulfillmen_';
    $shippingConfig = $wpdb->prefix . 'fmSetShipping';
    $ordersTable = $wpdb->prefix . 'fmOrders';
    $order = wc_get_order($orderid);
    foreach ($order->get_items() as $item_key => $item) {
        $item_id = $item->get_id();
        $item_data = $item->get_data();
        $product_name = $item_data['name'];
        $product_id = $item_data['product_id'];
        $variation_id = $item_data['variation_id'];
        $quantity = $item_data['quantity'];
        $tax_class = $item_data['tax_class'];
        $line_subtotal = $item_data['subtotal'];
        $line_subtotal_tax = $item_data['subtotal_tax'];
        $line_total = $item_data['total'];
        $line_total_tax = $item_data['total_tax'];
        $product = $item->get_product(); // Get the WC_Product object
        //$product_type = $product->get_type();     
        //$product_variation_id = $item['variation_id'];
        // Check if product has variation.
        /*if ($product_variation_id) { 
            $product = new WC_Product($item['variation_id']);
        } else {
            $product = $item->get_product();
        }*/
        $product_sku = $product->get_sku();   
        $product_price = $product->get_price();
        $stock_quantity = $product->get_stock_quantity();
        $product_weight = "0.100";
        

        $product_info[] = array(
            "SKU" => $product_sku,
            "EnName" => $product_name,
            "CnName" => $product_name,
            "MaterialQuantity" => $quantity,
            "Weight" => floatval($product_weight),
            "Price" => floatval($product_price),
            "ProducingArea" => "CN",
            "HSCode" => "000000",
        );
    }

    ## SHIPPING INFORMATION:
    $order_number = $order->get_order_number();
    $order_shipping_name = $order->get_formatted_shipping_full_name();
    $order_shipping_company = $order->get_shipping_company();
    $order_shipping_address_1 = $order->get_shipping_address_1();
    $order_shipping_address_2 = $order->get_shipping_address_2();
    $order_shipping_city = $order->get_shipping_city();
    $order_shipping_country = $order->get_shipping_country(); //WC()->countries->countries[$order->get_billing_country()];
    $order_shipping_state = WC()->countries->get_states($order_shipping_country)[$order->get_shipping_state()]; //$order->get_shipping_state();;
    $order_shipping_postcode = $order->get_shipping_postcode();
    $order_billing_email = $order->get_billing_email();    
    $order_customer_note = $order->get_customer_note();
    $order_billing_phone = $order->get_billing_phone();  
    $customerRef = $order_number . " " . $storeName;
    $getChannel = $wpdb->get_results("SELECT ChannelID from $shippingConfig where CountryID='$order_shipping_country' ORDER BY id DESC LIMIT 1");
    foreach ($getChannel as $channel) {
        $shippingChannel = $channel->ChannelID;
    }
    if (empty($shippingChannel)) {
        $shippingChannel = 'CHINAPOST';
    }

    $params = array(
        "Style" => 1,
        "CustomerID" => $credentials['custid'],
        "ChannelInfoID" => $shippingChannel, //add channel code var here
        "ShipToName" => $order_shipping_name,
        "ShipToPhoneNumber" => $order_billing_phone,
        "ShipToCountry" => $order_shipping_country,
        "ShipToState" => $order_shipping_state,
        "ShipToCity" => $order_shipping_city,
        "ShipToAdress1" => $order_shipping_address_1,
        "ShipToAdress2" => $order_shipping_address_2,
        "ShipToZipCode" => $order_shipping_postcode,
        "ShipToCompanyName" => $order_shipping_company,
        "RecipientEmail" => $order_billing_email,
        "OrderStatus" => 1,
        "TrackingNo" => "",
        "CusRemark" => $order_customer_note,
        "CODType" => 0,
        "CODMoney" => 0,
        "IDCardNo" => "",
        "CsRefNo" => $customerRef,
        "WarehouseId" => 536,
        "Products" => $product_info,
    );

    //changed to SOAP

    $parsedres = null;
    $params = json_encode($params);

    //$params = ' { "Style": 1, "CustomerID": "10002", "ChannelInfoID": "hkdhl", "ShipToName": "Dawei Liu", "ShipToPhoneNumber": "8919266766", "ShipToCountry": "US", "ShipToState": "NewYork oblast", "ShipToCity": "Livenskiy rayon", "ShipToAdress1": "Dubki ， rabochaya 13/3", "ShipToAdress2": "", "ShipToZipCode": "12345", "ShipToCompanyName": "Express", "RecipientEmail": "", "OrderStatus": 1, "TrackingNo": "", "CusRemark": "", "CODType": 0, "CODMoney": 0, "IDCardNo": "", "CsRefNo": "15551X1010101", "WarehouseId": "2B", "Products": [{ "SKU": "HOODIE-PATIENT-NINJA-TEST14", "EnName": "Other", "CnName": "手机壳", "MaterialQuantity": 1, "Weight": 0.1, "Price": 5.00, "ProducingArea": "CN", "HSCode": "000000" }] }';

    // Create soap xml request parameters
    $soap_request = "<?xml version=\"1.0\"?>\n";
    $soap_request .= "<soap:Envelope xmlns:soap=\"http://www.w3.org/2003/05/soap-envelope\" xmlns:tem=\"http://tempuri.org/\">\n";
    $soap_request .= "  <soap:Body>\n";
    $soap_request .= "  	<tem:AddorUpdateOrders>\n";
    $soap_request .= "		<tem:strorderinfo>$params</tem:strorderinfo>\n";
    $soap_request .= "		<tem:secretkey>" . $credentials['apikey'] . "</tem:secretkey>\n";
    $soap_request .= "		</tem:AddorUpdateOrders>\n";
    $soap_request .= "  </soap:Body>\n";
    $soap_request .= "</soap:Envelope>";

    // Create curl headers
    $header = array(
        "Content-type: text/xml;charset=\"utf-8\"",
        "Accept: text/xml",
        "Cache-Control: no-cache",
        "Pragma: no-cache",
        "Content-length: " . strlen($soap_request),
    );

    // Initialize curl
    $soap_do = curl_init();
    curl_setopt($soap_do, CURLOPT_URL, esc_url($apiurl));
    curl_setopt($soap_do, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($soap_do, CURLOPT_TIMEOUT, 10);
    curl_setopt($soap_do, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($soap_do, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($soap_do, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($soap_do, CURLOPT_POST, true);
    curl_setopt($soap_do, CURLOPT_POSTFIELDS, $soap_request);
    curl_setopt($soap_do, CURLOPT_HTTPHEADER, $header);

    $response = curl_exec($soap_do);
    if (curl_error($soap_do)) {
        $error_msg = curl_error($soap_do);
        $error = json_encode(array("flag" => "failure", "message" => $error_msg));
        return $error;
        wp_die();
    }

    curl_close($soap_do);

    $p = xml_parser_create();
    xml_parse_into_struct($p, $response, $vals, $index);
    xml_parser_free($p);

    if (!$vals) {
        $error = json_encode(array("flag" => "failure", "message" => "An error occured while creaeting Order."));
        return $error;
        wp_die();
    }

    //var_dump($vals);
    //echo $params;
    // wp_die();

    foreach ($vals as $val) {
        if ('ADDORUPDATEORDERSRESULT' == $val['tag']) {
            $parsedres = json_decode($val['value']);
            $parsedres = json_encode($parsedres);
        }
    }

    return $parsedres;
}

