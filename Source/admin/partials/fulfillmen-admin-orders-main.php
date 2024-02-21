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
global $woocommerce;
global $post;
$storeName = get_option($prefix . 'fulfillmen_store');
$APIuserID = get_option($prefix . 'fulfillmen_userID');
$apiKey = get_option($prefix . 'fulfillmen_apikey');
$TrackingUrl = get_option($prefix . 'customtrackingurl');
$iFautoMated = get_option($prefix . 'automation_fw');
$userID = get_option($prefix . 'fulfillmen_username');
$userPass = get_option($prefix . 'fulfillmen_password');
$warehouse = get_option($prefix . 'warehouse_ID');
$ordersTable = $wpdb->prefix . 'fmOrders';
$shippingConfig = $wpdb->prefix . 'fmSetShipping';
$orderMode = get_option($prefix . 'order_mode'); //1 warehouse 2 dropship
$processAllOrders = get_option($prefix . 'process_all_geos');

if($processAllOrders == 'yes') {
    $processAllFlag = 1;
} else {
    $processAllFlag = 0;
}


function wf_process_orders($orderid, string $apiurl, array $credentials, $storeSuffix, $orderMode)
{
    global $prefix;
    global $wpdb;
    global $woocommerce;
    global $post;
    global $warehouse;
    
    global  $processAllFlag;
   
    $prefix = 'fulfillmen_';
    $shippingConfig = $wpdb->prefix . 'fmSetShipping';
    $ordersTable = $wpdb->prefix . 'fmOrders';
    $storeName = $storeSuffix;
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
        $image_id = $productobj->is_type('variable') ? $productobj->get_variation_image_id($variation_id) : $productobj->get_image_id();

       

        $productbyid = wc_get_product($product_id);
        $image_id = $productbyid->get_image_id();
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        $imgurl = preg_replace("~^(https?://)~", "", $image_url) ?: "";

      
        //$product = $item->get_product(); // Get the WC_Product object
        //$product_type = $product->get_type();
        //$product_sku = $product->get_sku();
        /*$product_variation_id = $variation_id;
        if ($product_variation_id) {
            $product = new WC_Product($variation_id);
        } else {
            $product = $item->get_product();
        }*/
        

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
    $csRef = $order_number . " " . $storeName;
    $getChannel = $wpdb->get_results("SELECT ChannelID from $shippingConfig where CountryID='$order_shipping_country' ORDER BY id DESC LIMIT 1");
    foreach ($getChannel as $channel) {
        $shippingChannel = $channel->ChannelID;
    }
    if (empty($shippingChannel)) {
        $shippingChannel = 'CHINAPOST';
    }

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
        "CsRefNo" => $csRef,
        "WarehouseId" => "536",
        "CustWeight" => "0.100",
        "FLength" => "1",
        "FWidth" => "1",
        "FHeight" => "1",
        "Products" => $product_info,
     ];
    // var_dump($params);
    //changed to REST

    $params = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $response  = wp_remote_post($apiurl, array(
        'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'        => $params,
        'method'      => 'POST',
        'data_format' => 'body',
    ));
    $body = wp_remote_retrieve_body($response);
    //var_dump($body);
    $responseArr = ["Body" => $body, "debug" => $apiurl . " req: " . $params];
    return $responseArr;
}

if (!empty($userID && $userPass)) {
    echo "<h3>Sync Pending Orders with Fulfillmen</h3>";
    if (!empty($_POST)) {
        //Sending the data to FUlfillmen
        //var_dump($_POST);
        foreach ($_POST['data'] as $row) {
            // If the checkbox is on
            if (isset($row['select'])) {
                $orderid = $row['orderID'];
                $postID = $row['postID'];
                $credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);

                $response = wf_process_orders($orderid, "https://wms.fulfillmen.com/api-json/CreateOrder.aspx?Key=$apiKey", $credentials, $storeName, $orderMode);

                if (is_wp_error($response)) {
                    echo json_encode(array('success' => $response->get_error_message()));
                } else {
                    $res = json_decode($response['Body']);
                    // var_dump($response);
                    // var_dump($res);
                    if ($res->success == "success") {
                        //echo json_encode(array('code' => $res->success, 'status' => $res->success, 'order'=> $res->OrderNo, 'reference'=> $res->CsRefNo));
                        echo "<div class='apiresponse'> <br>Order Processed: " . $res->OrderNo . " " . $res->message . "</div>";
                        $sql = "INSERT INTO $ordersTable (OrderNumber,FulfillmenOrderNum,FMOrderStatus,FMOrderTracking,isSynced) values('$res->CsRefNo','$res->OrderNo','Draft','NA','0')";
                        $wpdb->query($sql);
                        //$WCorder = new WC_Order($orderid);
                        //$WCorder->update_status('wc-processing');
                        $metaUpdate1 = update_post_meta($postID, 'fmDraftSynced', 'yes');
                        $metaUpdate2 = update_post_meta($postID, 'fmDraftOrderNumber', $res->OrderNo);
                        if( $metaUpdate1 == false ||  $metaUpdate2 == false){
                            echo "<div class='apiresponse'> Can't update the post meta Post ID: $postID</div>";
                        }
                        $order = wc_get_order($orderid);
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
                        } else {
                            echo "<div class='apiresponse'> Can't update the post meta Post ID: $postID</div>";
                        }
                    } else {
                        $debugresp = $response['debug'];
                        echo "<div class='apiresponse'> Error Occured: (Order Mode: " . get_option($prefix . 'order_mode') . ")" . $res->message . " <br> Payload: $debugresp</div>";
                    }
                    //echo json_encode(array('code' => $res, 'status' => $res, 'order'=>$res->OrderNo, 'message' => __($msg)));
                }

                //wp_die();
            } /*else {
        echo 'Error: Please select at least one order to Sync! <br>';

        }*/
        }
        //here ends the for each loop
        echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";
    } else {
        $getConfiguredCountries =  $wpdb->get_results("SELECT CountryID from $shippingConfig");

        foreach ($getConfiguredCountries as $countries) {
            $configCountries[] = $countries->CountryID;
        }

        //Printing the Form
        echo '<form class="syncOrders" action="" method="post"> ';
        echo '<table class="wp-list-table widefat fixed striped posts">';
        echo '<td class="manage-column column-cb check-column" style="padding:8px 10px;"><input type="checkbox" id="allcb"></td> <td>Order Number</td> <td>Order Date</td> <td>Status</td>';

        if (isset($_GET['paged'])) {
            $paged = ($_GET['paged']);
        } else {
            $paged = 1;
        }

        // $args = array(
        //    'post_type' => 'shop_order',
        //    'post_status' => 'wc-processing',
        //    'posts_per_page' => 25,
        //    'paged' => $paged,
        //    'meta_query' => array(
        //        array(
        //            'key' => 'fmDraftSynced',
        //            'compare' => 'NOT EXISTS',
        //        ),
        //    ),
        // );

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
                $order_number = trim(str_replace('#', '', $order->get_order_number()));
                $order_shipping_country = $order->get_shipping_country();

                if (in_array($order_shipping_country, $configCountries) || $processAllFlag == 1) {
                    echo '<tr>';
                    echo '<td><input type="checkbox" name="data[' . $order_number . '][select]" class="wf-sku-input-checkbox" id="' . $order_number . '"></td>';
                    echo '<td>' . $order_number . '</td>';
                    echo '<td>' . $order->get_date_created()->format('Y-m-d H:i:s') . '</td>';
                    echo '<td class="order_status column-order_status"><span class="order-status status-processing" style="padding:2px 5px;">Payment Completed</span></td>';
                    echo '<input type="hidden" name="data[' . $order_number . '][orderID]" value="' . $order_number . '">';
                    echo '<input type="hidden" name="data[' . $order_number . '][postID]" value="' . $orderNUmberFromPost . '">';
                    echo '</tr>';
                }
            }
            echo '</table>';

            echo '<button type="submit" class="wf-sync-sku button-primary" >Sync Orders</button>';
            echo '</form>';

            echo '<br>';
            $max_pages = $order_query->max_num_pages;
            $nextpage  = $paged + 1;
            $prevpage  = max(($paged - 1), 0);

            if ($prevpage !== 0) {
                echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=orders&paged=' . $prevpage . '">Previous page</a>';
            }

            if ($max_pages > $paged) {
                echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=orders&paged=' . $nextpage . '">Next Page</a>';
            }
        }

        // $loop = new WP_Query($args);
        //
        // if ($loop->have_posts()):
        //     while ($loop->have_posts()): $loop->the_post();

        //         //var_dump($loop->post);
        //         $orderNUmberFromPost = $loop->post->ID;
        //         $orderNUmber = new WC_Order($orderNUmberFromPost);
        //         $orderNUmber = trim(str_replace('#', '', $orderNUmber->get_order_number()));
        //         $order = wc_get_order($orderNUmber);
        //         $order_shipping_country = $order->get_shipping_country(); //WC()->countries->countries[$order->get_billing_country()];

        //         if (in_array($order_shipping_country, $configCountries) || $processAllFlag == 1) {
        //             //var_dump$ifOrder = $wpdb->get_results("SELECT * from $ordersTable where OrderNumber='$orderNUmber' AND isSynced=1 ");
        //             //if (count($ifOrder) == 0) {
        //             echo '<tr>';
        //             echo '<td><input type="checkbox" name="data[' . $orderNUmber . '][select]" class="wf-sku-input-checkbox" id="' . $orderNUmber . '"></td>';
        //             echo '<td>' . $orderNUmber . '</td>';
        //             echo '<td>' . $loop->post->post_date . '</td>';
        //             echo '<td class="order_status column-order_status"><span class="order-status status-processing" style="padding:2px 5px;">Payment Completed</span></td>';
        //             echo '<input type="hidden" name="data[' . $orderNUmberFromPost . '][orderID]" value="' . $orderNUmberFromPost . '">';
        //             echo '<input type="hidden" name="data[' . $orderNUmberFromPost . '][postID]" value="' . $loop->post->ID . '">';
        //             //}
        //             //$ifSKU = $wpdb->get_results("SELECT * from $ordersTable where OrderNumber='$sku' AND isSynced=1 ");

        //             /*if (count($ifSKU) == 0) {
        //             echo '<td><input type="checkbox" name="data[' . $sku . '][select]" class="wf-sku-input-checkbox" id="' . $sku . '"></td>';
        //             } else {
        //             echo '<td><input type="radio"  class="" disabled></td>';
        //             }*/
        //             echo '</tr>';
        //         }

        //     endwhile;

        //     echo '</table>';
        //     echo '<button type="submit" class="wf-sync-sku button-primary" >Sync Orders</button>';
        //     echo '</form>';
        //     echo '<br>';
        //     $max_pages = $loop->max_num_pages;
        //     $nextpage = $paged + 1;
        //     $prevpage = max(($paged - 1), 0); //max() will discard any negative value
        //     if ($prevpage !== 0) {
        //         echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=orders&paged=' . $prevpage . '">Previous page</a>';
        //     }

        //     if ($max_pages > $paged) {
        //         echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=orders&paged=' . $nextpage . '">Next Page</a>';
        //     }

        //     wp_reset_query();
        // endif;
    }
} else {
    echo "<h3>Invalid Username and Or Password!</h3>";
}
