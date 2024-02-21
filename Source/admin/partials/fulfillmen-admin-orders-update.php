<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.0
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/admin/partials
 */

global $wpdb;
$prefix = 'fulfillmen_';
$GLOBALS['hide_save_button'] = true;
global $product;
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
$TrackResponse = "";
$orderMode = get_option($prefix . 'order_mode'); //1 warehouse 2 dropship
$processAllOrders = get_option($prefix . 'process_all_geos');

if($processAllOrders == 'yes') {
    $processAllFlag = 1;
} else {
    $processAllFlag = 0;
}


function wf_process_orders($orderid, string $apiurl, array $credentials, $storeSuffix, $orderMode)
{
    global $wpdb;
    $storeName = $storeSuffix;
    global $post;
    global $product;
    global $woocommerce;
    global $warehouse ;


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

        $productobj = $variation_id ? wc_get_product($variation_id) : wc_get_product($product_id);
        if ($productobj->is_type('variable')) {
            // Product is a variable product, use the variation image URL
            $product_image_url = $productobj->get_image('full');
        } else {
            // Product is a simple product, use the product image URL
            $product_image_url = $productobj->get_image('full');
        }
        $imgurl = preg_replace("~^(https?://)~", "", $product_image_url) ?: "";


        $product = $item->get_product();
        $product_sku = $product->get_sku();
        $product_price = $product->get_price();
        $stock_quantity = $product->get_stock_quantity();
        $pprice = number_format(floatval($product_price), 2);
        $priceSanitised = str_replace(',', '', $pprice);
        $product_weight = "0.100";
       
        
        if($orderMode == 2) {
            $product_info[] = [
                "SKU" => $product_sku,
                "EnName" => $product_name,
                "CnName" => "太阳镜",
                "Quantity" => $quantity,
                "Weight" => number_format(floatval($product_weight), 2),
                "Price" => $priceSanitised,
                "ProducingArea" => "CN",
                "HSCode" => "000000",
                "Currency" => "USD",
                "Material" => "",
                "Application" => "",
                "SalesAddress" => $imgurl
             ] ;
        } else {
            $product_info[] = [
                "SKU" => $product_sku,
                "EnName" => $product_name,
                "CnName" => "太阳镜",
                "Quantity" => $quantity,
                "Weight" => number_format(floatval($product_weight), 2),
                "Price" => $priceSanitised,
                "ProducingArea" => "CN",
                "HSCode" => "000000",
                "Currency" => "USD",
                "Material" => "",
                "Application" => "",
                "SalesAddress" => ""
             ] ;
        }
    }

    ## SHIPPING INFORMATION:
    $order_number = $order->get_order_number();
    $order_shipping_name = $order->get_formatted_shipping_full_name();
    $order_shipping_company = $order->get_shipping_company();
    $order_shipping_address_1 = $order->get_shipping_address_1();
    $order_shipping_address_2 = $order->get_shipping_address_2();
    $order_shipping_city = $order->get_shipping_city();
    $order_shipping_country = $order->get_shipping_country(); //WC()->countries->countries[$order->get_billing_country()];
    //$order_shipping_state = WC()->countries->get_states($order_shipping_country)[$order->get_shipping_state()]; //$order->get_shipping_state();;
    $order_shipping_state = $order->get_shipping_state();
    $order_shipping_postcode = $order->get_shipping_postcode();
    $order_billing_email = $order->get_billing_email();
    $order_customer_note = $order->get_customer_note();
    $order_billing_phone = $order->get_billing_phone();
    $customerRef = $order_number . " " . $storeName;

    $getConfiguredCountries =  $wpdb->get_results("SELECT CountryID from $shippingConfig");

    foreach ($getConfiguredCountries as $countries) {
        $configCountries[] = $countries->CountryID;
    }
    if (in_array($order_shipping_country, $configCountries) ||  $processAllFlag == 1) {


        $getChannel = $wpdb->get_results("SELECT ChannelID from $shippingConfig where CountryID='$order_shipping_country' ORDER BY id DESC LIMIT 1");
        foreach ($getChannel as $channel) {
            $shippingChannel = $channel->ChannelID;
        }
        if (empty($shippingChannel)) {
            $shippingChannel = 'CHINAPOST';
        }

        // $params = array(
        //     "Style" => 1,
        //     "CustomerID" => $credentials['custid'],
        //     "ChannelInfoID" => $shippingChannel, //add channel code var here
        //     "ShipToName" => $order_shipping_name,
        //     "ShipToPhoneNumber" => $order_billing_phone,
        //     "ShipToCountry" => $order_shipping_country,
        //     "ShipToState" => $order_shipping_state,
        //     "ShipToCity" => $order_shipping_city,
        //     "ShipToAdress1" => $order_shipping_address_1,
        //     "ShipToAdress2" => $order_shipping_address_2,
        //     "ShipToZipCode" => $order_shipping_postcode,
        //     "ShipToCompanyName" => $order_shipping_company,
        //     "RecipientEmail" => $order_billing_email,
        //     "OrderStatus" => 1,
        //     "TrackingNo" => "",
        //     "CusRemark" => $order_customer_note,
        //     "CODType" => 0,
        //     "CODMoney" => 0,
        //     "IDCardNo" => "",
        //     "CsRefNo" => $customerRef,
        //     "WarehouseId" => $warehouse ,  //536,
        //     "Products" => $product_info,
        // );

        $params = [
            "Style" => get_option($prefix . 'order_mode'),
            "CustomerID" => $credentials['custid'],
            "ShippingCode" => $shippingChannel, //add channel code var here
            "ShipToName" => $order_shipping_name,
            "ShipToPhoneNumber" => $order_billing_phone,
            "ShipToMobileNumber" => $order_billing_phone,
            "ShipToCountry" => $order_shipping_country,
            "ShipToState" => $order_shipping_state,
            "ShipToCity" => $order_shipping_city,
            "ShipToArea" => "",
            "ShipToAdress1" => $order_shipping_address_1,
            "ShipToAdress2" => $order_shipping_address_2,
            "ShipToHouseNo" => "",
            "ShipToZipCode" => $order_shipping_postcode,
            "ShipToCompanyName" => $order_shipping_company,
            "ShipToEmail" => $order_billing_email,
            "SendName" => "",
            "SendCountry" => "China",
            "SendState" => "Guangdong",
            "SendCity" => "Shenzhen",
            "SendAddress" => "Huizhou",
            "SendZipcode" => "518000",
            "SendContact" => "",
            "SendCompanyName" => "NA",
            "SendEmail" => "",
            "OrderStatus" => "1",
            "TrackingNo" => "",
            "CusRemark" => "",
            "CODType" => "",
            "CODMoney" => "",
            "IOSS" => "",
            "VATNo" => "",
            "IDCardNo" => "",
            "CsRefNo" =>  $customerRef,
            "WarehouseId" => "536",
            "CustWeight" => "0.100",
            "FLength" => "1",
            "FWidth" => "1",
            "FHeight" => "1",
            "Products" => $product_info,
        ];


        //changed to REST

        $body = null;
        $params = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $response  = wp_remote_post($apiurl, array(
            'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
            'body'        => $params,
            'method'      => 'POST',
            'data_format' => 'body',
        ));
        $body = wp_remote_retrieve_body($response);
        // //var_dump($body);
        // return $body;
        //var_dump($body);




        $responseArr = ["Body" => $body, "debug" => $apiurl . " req: " . $params];
        $data = json_encode($responseArr);
        //wp_mail('it@fulfillmen.com', 'Submitted', $data);
        return $responseArr;
    }
}

if ($iFautoMated == 'yes') {
    $today = date('Y-m-d');


    $args = array(
        'status'         => 'processing',
        'limit'          => 25,
        'paged'          => $paged,
        'meta_query'     => array(
            array(
                'key'     => 'fmDraftSynced',
                'compare' => 'NOT EXISTS',
            ),
        ),
    );

    $order_query = new WC_Order_Query($args);
    $orders = $order_query->get_orders();

    if ($orders) {
        foreach ($orders as $order) {
            $orderNUmberFromPost = $order->get_id();
            $orderid = $orderNUmberFromPost;
            $order_number = trim(str_replace('#', '', $order->get_order_number()));
            $order_shipping_country = $order->get_shipping_country();
            if (in_array($order_shipping_country, $configCountries) || $processAllFlag == 1) {
                $credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);
                $response = wf_process_orders($orderNUmberFromPost, "https://wms.fulfillmen.com/api-json/CreateOrder.aspx?Key=$apiKey", $credentials, $storeName);
                if (is_wp_error($response)) {
                    // echo json_encode(array('success' => $response->get_error_message()));
                    $TrackResponse .= json_encode(array('success' => $response['body']));
                    //wp_mail('it@fulfillmen.com', 'Orders Are not processed', $TrackResponse);
                } else {
                    $res = json_decode($response['Body']);
                    // var_dump($response);
                    // var_dump($res);
                    if ($res->success == "success") {
                        //echo json_encode(array('code' => $res->success, 'status' => $res->success, 'order'=> $res->OrderNo, 'reference'=> $res->CsRefNo));

                        $sql = "INSERT INTO $ordersTable (OrderNumber,FulfillmenOrderNum,FMOrderStatus,FMOrderTracking,isSynced) values('$res->CsRefNo','$res->OrderNo','Draft','NA','0')";
                        $wpdb->query($sql);

                        /*$WCorder = new WC_Order($orderid);
                        $WCorder->update_status('wc-processing');*/
                        update_post_meta($orderNUmberFromPost, 'fmDraftSynced', 'yes');
                        update_post_meta($orderNUmberFromPost, 'fmDraftOrderNumber', $res->OrderNo);
                        $order = wc_get_order($orderNUmberFromPost);
                        if ($order) {
                            $meta_key = 'fmDraftSynced';
                            $new_value = 'yes';
                            $order->update_meta_data($meta_key, $new_value);
                            $order->save();

                            $meta_key = 'fmDraftOrderNumber';
                            $new_value = $res->OrderNo;
                            $order->update_meta_data($meta_key, $new_value);
                            $order->save();
                        
                            //echo "Order metadata updated successfully!";
                        }

                        $TrackResponse .= "Order Processed: " . $res->OrderNo;

                        //wp_mail('it@fulfillmen.com', 'Orders Are processed', $response['Body']);
                    } else {
                        $TrackResponse .= "Error Occured: " . $res->message . " " . $res->Enmessage;
                        //wp_mail('it@fulfillmen.com', 'Orders Are not processed',  $TrackResponse);
                        //echo "<br>Error Occured: " . $res->Info;
                    }
                }
            }
        }
    }

    // $today = date('Y-m-d');
    // $args = array(
    //     'post_type' => 'shop_order',
    //     'post_status' => 'wc-processing',
    //     'posts_per_page' => 500,
    //     'meta_query' => array(
    //         array(
    //             'key' => 'fmDraftSynced',
    //             'compare' => 'NOT EXISTS',
    //         ),
    //     ),
    //     // 'date_query' => array(
    //     //     'before' => date('Y-m-d', strtotime('today')),
    //     // ),
    // );
    // $loop = new WP_Query($args);
    // if ($loop->have_posts()):
    //     while ($loop->have_posts()): $loop->the_post();
    //         $orderNUmberFromPost = $loop->post->ID;
    //         $orderNUmber = new WC_Order($orderNUmberFromPost);
    //         $orderNUmber = trim(str_replace('#', '', $orderNUmber->get_order_number()));

    //         $credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);
    //         $postID = $orderNUmberFromPost;
    //         $orderid = $orderNUmber;
    //         //$response = wf_process_orders($orderid, 'http://wms.fulfillmen.com/webservice/APIWebService.asmx', $credentials, $storeName);
    //         $response = wf_process_orders($orderid, "https://wms.fulfillmen.com/api-json/CreateOrder.aspx?Key=$apiKey", $credentials, $storeName);

    //         if (is_wp_error($response)) {
    //             // echo json_encode(array('success' => $response->get_error_message()));
    //             $TrackResponse .= json_encode(array('success' => $response['body']));
    //             //wp_mail('it@fulfillmen.com', 'Orders Are not processed', $TrackResponse);
    //         } else {
    //             $res = json_decode($response['Body']);
    //             // var_dump($response);
    //             // var_dump($res);
    //             if ($res->success == "success") {
    //                 //echo json_encode(array('code' => $res->success, 'status' => $res->success, 'order'=> $res->OrderNo, 'reference'=> $res->CsRefNo));

    //                 $sql = "INSERT INTO $ordersTable (OrderNumber,FulfillmenOrderNum,FMOrderStatus,FMOrderTracking,isSynced) values('$res->CsRefNo','$res->OrderNo','Draft','NA','0')";
    //                 $wpdb->query($sql);

    //                 /*$WCorder = new WC_Order($orderid);
    //                 $WCorder->update_status('wc-processing');*/
    //                 update_post_meta($orderNUmberFromPost, 'fmDraftSynced', 'yes');
    //                 update_post_meta($orderNUmberFromPost, 'fmDraftOrderNumber', $res->OrderNo);
    //                 $TrackResponse .= "Order Processed: " . $res->OrderNo;

    //                 //wp_mail('it@fulfillmen.com', 'Orders Are processed', $response['Body']);
    //             } else {
    //                 $TrackResponse .= "Error Occured: " . $res->message . " " . $res->Enmessage;
    //                 //wp_mail('it@fulfillmen.com', 'Orders Are not processed',  $TrackResponse);
    //                 //echo "<br>Error Occured: " . $res->Info;
    //             }
    //         }

    //     endwhile;
    // wp_reset_query();
    // endif;
} else {
    $TrackResponse .= "<h3>Invalid Username and Or Password!</h3>";
}
