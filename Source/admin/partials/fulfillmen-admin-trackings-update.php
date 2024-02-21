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
$Automation = get_option($prefix . 'automation_fw');
$ASTIntegration = get_option($prefix . 'ff_ast_integration');
$APIuserID = get_option($prefix . 'fulfillmen_userID');
$apiKey = get_option($prefix . 'fulfillmen_apikey');
$TrackingUrl = get_option($prefix . 'customtrackingurl');
$iFautoMated = get_option($prefix . 'automation_fw');
$userID = get_option($prefix . 'fulfillmen_username');
$userPass = get_option($prefix . 'fulfillmen_password');
$warehouse = get_option($prefix . 'warehouse_ID');
$ordersTable = $wpdb->prefix . 'fmOrders';
$credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);
$TrackResponse = '';
function add_tracking_information_into_order($order, $tracking_details)
{

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
if ($Automation == "yes") {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => array('wc-processing'),
        'posts_per_page' => 500,
    );
    $loop = new WP_Query($args);
    if ($loop->have_posts()):
        while ($loop->have_posts()): $loop->the_post();

            $orderNUmberFromPost = $loop->post->ID;
            $orderNUmber = new WC_Order($orderNUmberFromPost);
            $orderNUmber = trim(str_replace('#', '', $orderNUmber->get_order_number()));
            $orderid = $orderNUmber;
            $postId = $orderNUmberFromPost;
            if (strpos($orderid, $storeName) == false) {
                $orderidforJSON = $orderid . "%20" . $storeName;
            } else {
                $orderidforJSON = $orderid;
            }
            $OrderInfo = file_get_contents('http://wms.fulfillmen.com/api-json/GetOrderList.aspx?Key=' . $apiKey . '&page=1&CsRefNo=' . $orderidforJSON);
            $jData = json_decode($OrderInfo, true);
            $dataArr = $jData['data'];
            if (!empty($dataArr[0]['TrackingNo'])) {
                $orderStatus = intval($dataArr[0]['OrderStatus']);
                if ($orderStatus == 6) {
                    $fmtracking = $dataArr[0]['TrackingNo'];

                    if ($ASTIntegration == "yes") {
                        $today = date("Y-m-d");

                        /*$key = get_option($prefix . 'ff_wooKey');
                        $secret = get_option($prefix . 'ff_wooSec');
                        $endpointforREST = '/wc/v1/orders/' . $orderid . '/shipment-trackings/';
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
                    update_post_meta($postId, '_postnord_field_data', sanitize_text_field($fmtracking));
                    //update_post_meta($postId, '_tracking_number', sanitize_text_field($fmtracking));

                    $TrackResponse .= $orderid . "Tracking: " . $fmtracking . "\n";

                    if ($pushNotification == 'yes') {
                        $email_oc = new WC_Email_Customer_Completed_Order();
                        $email_oc->trigger($orderid);
                    }
                }
            }

        endwhile;
        wp_reset_query();
    endif;
} else {

    $TrackResponse .= "\n Automation is disabled";
}
