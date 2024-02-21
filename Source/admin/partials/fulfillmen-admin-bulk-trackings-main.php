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
    echo "<h3>Sync Trackig Numbers for Shipped Orders</h3>"
        . "<h5>This feature works for tracking numbers downloaded from WMS. The template can be downloaded from <a target='_balnk' href='https://fulfillmen.com/plugin/tracking.csv'>This Link</a></h5>";
    if (!empty($_FILES)) {
        $enableimport = true;
        //echo "<p>File upload function is now running!</p>";

        $uploadDIR = wp_get_upload_dir();
        $uploadDIR = $uploadDIR["path"];
        $currentDir = getcwd();
        //var_dump($uploadDIR);
        //wp_die();
        $errors = []; // Store all foreseen and unforseen errors here
        $fileExtensions = ['csv']; // Get all the file extensions
        $fileName = $_FILES['trackingCSV']['name'];
        $fileSize = $_FILES['trackingCSV']['size'];
        $fileTmpName = $_FILES['trackingCSV']['tmp_name'];
        $fileType = $_FILES['trackingCSV']['type'];
        $tmp = explode('.', $fileName);
        $fileExtension = end($tmp);
        $uploadPath = $uploadDIR . basename($fileName);
        //var_dump($fileTmpName);
        //var_dump($uploadPath);

        if (!in_array($fileExtension, $fileExtensions)) {
            $errors[] = '<p>This file extension is not allowed. Please upload a CSV file</p>';
        }

        if ($fileSize > 8000000) {
            $errors[] = '<p>This file is more than 2MB. Sorry, it has to be less than or equal to 8MB</p>';
        }

        if (empty($errors)) {
            $didUpload = move_uploaded_file($fileTmpName, $uploadPath);

            if ($didUpload) {
                echo '<p>The file ' . basename($fileName) . ' has been uploaded</p>';
                $handle = fopen($uploadPath, "r") or die("Error opening file");
                $i = 0;
                while (($line = fgetcsv($handle)) !== false) {
                    if ($i == 0) {
                        $c = 0;
                        foreach ($line as $col) {
                            $cols[$c] = $col;
                            $c++;
                        }
                    } else if ($i > 0) {
                        $c = 0;
                        foreach ($line as $col) {
                            $tracking_data[$i][$cols[$c]] = $col;
                            $c++;
                        }
                    }
                    $i++;
                }
                fclose($handle);
                //echo "inital loop good<br>";
                //echo "<pre>";

                foreach ($tracking_data as $tracking_row) {
                    $orderNum = $tracking_row['orderid'];
                    $trackingNum = $tracking_row['trackingid'];
                    $orderNum = preg_replace('/\s+/', '', $orderNum);
                    $orders = wc_get_orders(array('meta_key' => '_order_number', 'meta_value' => $orderNum));
                    foreach ($orders as $neworderid) {
                        $order_id = method_exists($neworderid, 'get_id') ? $neworderid->get_id() : $neworderid->id;
                        echo $orderNum . "Tracking Num:" . $trackingNum . " Order Id -" . $order_id . "<br>";
                        if (!empty($trackingNum)) {
                            $orderNum = $order_id;
                            if ($ASTIntegration == "yes") {
                                $today = date("Y-m-d");
                                $wast = WC_Advanced_Shipment_Tracking_Actions::get_instance();
                                $args = array(
                                    'tracking_provider' => 'Fulfillmen',
                                    'tracking_number' => $trackingNum,
                                    'date_shipped' => $today,
                                    'status_shipped' => 1,
                                );
                                //$order_id = $orderNum;
                                $wast->insert_tracking_item($order_id, $args);
                            } else {
                                $WCorder = new WC_Order($order_id);
                                //$WCorder = wc_get_order($orderNum);
                                //echo  $WCorder->get_id();
                                $WCorder->update_status('wc-completed');
                                $tracking_details = array('carrier' => "Fulfillmen", 'tracking_number' => $trackingNum, 'ship_date' => "");
                                add_tracking_information_into_order($postId, $tracking_details);
                            }

                            $sql = "UPDATE $ordersTable SET FMOrderStatus = 'Fulfilled', FMOrderTracking =' $trackingNum'  WHERE OrderNumber = '$orderNum'";
                            $wpdb->query($sql);

                            update_post_meta($orderNum, '_postnord_field_data', sanitize_text_field($trackingNum));

                            if ($pushNotification == 'yes') {
                                $email_oc = new WC_Email_Customer_Completed_Order();
                                $email_oc->trigger($orderNum);
                            }
                        }
                    }
                }

            } else {
                echo '<p>An error occurred somewhere. Try again or contact the admin</p>';
            }
        } else {
            foreach ($errors as $error) {
                echo $error . '<p>These are the errors' . '\n' . '</p>';
            }
        }

        //here ends the for each loop
        echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";

    } else { //Printing the Form
        echo '<form class="syncOrders" enctype="multipart/form-data" action="" method="post">
                <div id="poststuff">
                <div id="post-body" class="metabox-holder">
                    <div id="post-body-content">
                        Please select the CSV file to upload
                            <p>Choose a file: <input type="file" name="trackingCSV" /></p>
                    </div>
                </div>
                <br class="clear">
                </div>';
        echo '<button type="submit" class="wf-sync-sku button-primary" >Upload Trackings</button>';
        echo '</form>';
        echo '<br>';

    }
} else {
    echo "<h3>Invalid Username and Or Password!</h3>";
}
