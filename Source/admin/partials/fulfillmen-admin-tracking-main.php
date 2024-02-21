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
global $woocommerce;
global $post;
$prefix = 'fulfillmen_';
$GLOBALS['hide_save_button'] = true;

$storeName = get_option($prefix . 'fulfillmen_store');
$pushNotification = get_option($prefix . 'push_mailnotification');
$ASTIntegration = get_option($prefix . 'ff_ast_integration');
$Automation = get_option($prefix . 'automation_fw');
$APIuserID = get_option($prefix . 'fulfillmen_userID');
$apiKey = get_option($prefix . 'fulfillmen_apikey');
$TrackingUrl = get_option($prefix . 'customtrackingurl');
$iFautoMated = get_option($prefix . 'automation_fw');
$userID = get_option($prefix . 'fulfillmen_username');
$userPass = get_option($prefix . 'fulfillmen_password');
$warehouse = get_option($prefix . 'warehouse_ID');
$ordersTable = $wpdb->prefix . 'fmOrders';

function add_tracking_information_into_order($order, $tracking_details)
{
    //$order_id = $order->get_id();
    $args = array(
        'tracking_provider' => $tracking_details['carrier'],
        'tracking_number' => wc_clean($tracking_details['tracking_number']),
        'date_shipped' => wc_clean($tracking_details['ship_date']),
        'status_shipped' => '',
    );
    update_post_meta($order, '_tracking_provider', $args['tracking_provider']);
    update_post_meta($order, '_tracking_number', $args['tracking_number']);
    update_post_meta($order, '_date_shipped', $args['date_shipped']);
}

if (!empty($userID && $userPass)) {
    echo "<h3>Sync Trackig Numbers for Shipped Orders</h3>";
    if (!empty($_POST)) {
        foreach ($_POST['data'] as $row) {
            // If the checkbox is on
            if (isset($row['select'])) {
                $orderid = $row['orderID'];
                $postId = $row['orderPost'];
                if (strpos($orderid, $storeName) == false) {
                    $orderidforJSON = $orderid . "%20" . $storeName;
                } else {
                    $orderidforJSON = $orderid;
                }
                $credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);

                $OrderInfo = file_get_contents('http://wms.fulfillmen.com/api-json/GetOrderList.aspx?Key=' . $apiKey . '&page=1&CsRefNo=' . $orderidforJSON);
                $jData = json_decode($OrderInfo, true);
                $dataArr = $jData['data'];
                //var_dump($jData['data']);
                //echo $orderid = $row['orderID'];
                if (!empty($dataArr[0]['TrackingNo'])) {
                    $orderStatus = intval($dataArr[0]['OrderStatus']);
                    if ($orderStatus == 6) {
                        $fmtracking = $dataArr[0]['TrackingNo'];
                        echo "<br>Tracking Number for order $orderid is: " . $fmtracking;
                        update_post_meta($postId, '_postnord_field_data', sanitize_text_field($fmtracking));
                        if ($ASTIntegration == "yes") {
                            $today = date("Y-m-d");
                            /*$key = get_option($prefix . 'ff_wooKey');
                            $secret = get_option($prefix . 'ff_wooSec');
                            $endpointforREST = '/wc/v1/orders/' . $orderid . '/shipment-trackings/';
                            echo $endpointforREST ;
                            $request = new WP_REST_Request('POST', $endpointforREST);
                            $request->add_header('Content-Type', 'application/json');
                            $request->set_body(json_encode(array(
                            'tracking_number' => $fmtracking,
                            'date_shipped' => $today,
                            "tracking_provider" => "Fulfillmen"
                            )));
                            $response = rest_do_request($request);
                            $data = $response->get_data();
                            var_dump($data);*/

                            $wast = WC_Advanced_Shipment_Tracking_Actions::get_instance();
                            $args = array(
                                'tracking_provider' => 'Fulfillmen',
                                'tracking_number' => $fmtracking,
                                'date_shipped' => $today,
                                'status_shipped' => 1,
                            );
                            $order_id = $postId;
                            $wast->insert_tracking_item($order_id, $args);
                        } else {
                            $WCorder = new WC_Order($postId);
                            $WCorder->update_status('wc-completed');
                            $tracking_details = array('carrier' => "Fulfillmen", 'tracking_number' => $fmtracking, 'ship_date' => "");
                            add_tracking_information_into_order($postId, $tracking_details);
                        }

                        $sql = "UPDATE $ordersTable SET FMOrderStatus = 'Fulfilled', FMOrderTracking =' $fmtracking'  WHERE OrderNumber = '$orderid'";
                        $wpdb->query($sql);

                        //update_post_meta($postId, '_postnord_field_data', sanitize_text_field($fmtracking));
                        //update_post_meta($postId, '_tracking_number', sanitize_text_field($fmtracking));

                        if ($pushNotification == 'yes') {
                            $email_oc = new WC_Email_Customer_Completed_Order();
                            $email_oc->trigger($orderid);
                        }

                        echo "<br>Database has been updated";
                    } else {
                        echo "<br>Status is not updated for order: $orderid";
                    }
                }

            }
        }
        //here ends the for each loop
        echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";

    } else { //Printing the Form
        echo '<form class="syncOrders" action="" method="post"> ';
        echo '<table class="wp-list-table widefat fixed striped posts">';
        echo '<td class="manage-column column-cb check-column" style="padding:8px 10px;"><input type="checkbox" id="allcb"></td> <td>Order Number</td> <td>Order Date</td> <td>Status</td> <td>WMS Order Number</td>';

        if (isset($_GET['paged'])) {
            $paged = ($_GET['paged']);
        } else {
            $paged = 1;
        }
        $args = array(
            'post_type' => 'shop_order',
            'post_status' => 'wc-processing',
            'posts_per_page' => 50,
            'paged' => $paged,
        );
        $loop = new WP_Query($args);
        if ($loop->have_posts()):
            while ($loop->have_posts()): $loop->the_post();
                echo '<tr>';
                //var_dump($loop->post);
                $orderNUmberFromPost = $loop->post->ID;
                $orderNUmber = new WC_Order($orderNUmberFromPost);
                $orderNUmber = trim(str_replace('#', '', $orderNUmber->get_order_number()));
                //$orderNUmber = $loop->post->ID;
                $ifOrder = $wpdb->get_results("SELECT * from $ordersTable where OrderNumber='$orderNUmber' AND isSynced=0 ");
                foreach ($ifOrder as $WMSorder) {
                    $wmsorderNumber = $WMSorder->FulfillmenOrderNum;
                }
                if (empty($wmsorderNumber)) {
                    $wmsorderNumber = "NA";
                }

                //if (count($ifOrder) == 0) {
                echo '<td><input type="checkbox" name="data[' . $orderNUmber . '][select]" class="wf-sku-input-checkbox" id="' . $orderNUmber . '"></td>';
                echo '<td>' . $orderNUmber . '</td>';
                echo '<td>' . $loop->post->post_date . '</td>';
                echo '<td class="order_status column-order_status"><span class="order-status status-processing" style="padding:2px 5px;">Order Confirmed</span></td>';
                echo '<td>' . $wmsorderNumber . '</td>';
                echo '<input type="hidden" name="data[' . $orderNUmber . '][orderID]" value="' . $orderNUmber . '">';
                echo '<input type="hidden" name="data[' . $orderNUmber . '][orderPost]" value="' . $orderNUmberFromPost . '">';
                //}
                //$ifOrder = $wpdb->get_results("SELECT * from $ordersTable where OrderNumber='$orderNUmber' AND isSynced=1 ");
                /*if (count($ifSKU) == 0) {
                echo '<td><input type="checkbox" name="data[' . $sku . '][select]" class="wf-sku-input-checkbox" id="' . $sku . '"></td>';
                } else {
                echo '<td><input type="radio"  class="" disabled></td>';
                }*/

                echo '</tr>';
            endwhile;

            echo '</table>';
            echo '<button type="submit" class="wf-sync-sku button-primary" >Sync Trackings</button>';
            echo '</form>';
            echo '<br>';
            $max_pages = $loop->max_num_pages;
            $nextpage = $paged + 1;
            $prevpage = max(($paged - 1), 0); //max() will discard any negative value
            if ($prevpage !== 0) {
                echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=trackings&paged=' . $prevpage . '">Previous page</a>';
            }

            if ($max_pages > $paged) {
                echo '<a class="pagiantion" style="margin-right:5px;" href="admin.php?page=wc-settings&tab=fulfillmen&section=trackings&paged=' . $nextpage . '">Next Page</a>';
            }

            wp_reset_query();
        endif;

    }
} else {
    echo "<h3>Invalid Username and Or Password!</h3>";
}
