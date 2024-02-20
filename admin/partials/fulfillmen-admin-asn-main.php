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

$APIuserID = get_option($prefix . 'fulfillmen_userID');
$apiKey = get_option($prefix . 'fulfillmen_apikey');
$TrackingUrl = get_option($prefix . 'customtrackingurl');
$iFautoMated = get_option($prefix . 'automation_fw');
$userID = get_option($prefix . 'fulfillmen_username');
$userPass = get_option($prefix . 'fulfillmen_password');
$warehouse = get_option($prefix . 'warehouse_ID');
$ordersTable = $wpdb->prefix . 'fmASN';

function wf_post_asn_to_ff(array $params, string $apiurl, array $credentials)
{
    $response = null;
    $params = json_encode($params,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $response  = wp_remote_post($apiurl, array(
        'headers'     => array('Content-Type' => 'application/json; charset=utf-8'),
        'body'        => json_encode($params),
        'method'      => 'POST',
        'data_format' => 'body',
    ));
    $body = wp_remote_retrieve_body( $response );
    //var_dump($body);
    return $body;

}

if (!empty($userID) || !empty($userPass)) {
    if (!empty($_POST)) {
        //Sending the data to FUlfillmen
        $product = $_POST['SKU'];
        $qty = $_POST['quantity'];
        $Cartons = $_POST['Cartons'];
        //$params[] = ["GFF_GoodsID" => $product, "ShouldQuantity" => $qty, "Carton_Number" =>  $Cartons, "Field11" => "ZP"];
        $refno = $APIuserID . date('Ymd');

       

        // $params = array(
        //     "ASNMain" => array(
        //         "ASNDetail" => $params,
        //         "DeliveryStyle" => "2",
        //         "WaybillNumber" => "",
        //         "warehouseCode" => $warehouse,
        //         "EnchaseNum" => 1,
        //         "ReachStartTime" => "",
        //         "ReachEndTime" => "",
        //         "Field2" => $APIuserID,
        //         "Style" => "CGRK",
        //         "ReferenceNo" => $refno,
        //         "ODR_OrderMainID" => "",
        //     ),
        // );

        $params[] = ["SKU" => $product,
        "Quantity" => $qty,
        "Box no" => $Cartons,
        "WaybillNo"=> $refno];

        $paramsArr = array(

                "ASNDetail"  => $params,
                "CustomerID" => $APIuserID,
                "Style" => "CGRK",
                "ASNStatus" => "0",
                "Type" => "0",
                "WarehouseCode" => $warehouse,
                "DeliveryStyle" => "2",
                "WaybillNumber" => "",
                "ReachStartTime" => date('Y-m-d'),
                "ReferenceNo" => $refno ,
                "OrderNo" => ""

        );


        $credentials = array('custid' => $APIuserID, 'apikey' => $apiKey);
        $response = wf_post_asn_to_ff($params, "http://wms.fulfillmen.com/api-json/CreateASN.aspx?Key=$apiKey", $credentials);
        if ('success' == $response->success) {
            //var_dump($response);
            //wp_die();
            // $to = 'billy@fulfillmen.com';
            // $subject = 'ASN '. $refno;
            // $body = 'ASN is created for customer id ' . $APIuserID . ' ASNNumber ' . $response->ASNNumber;
            // $headers = array('Content-Type: text/html; charset=UTF-8');
            // wp_mail( $to, $subject, $body, $headers );

            //echo json_encode( array( 'code' => 200, 'status' => 'success', 'message' => 'ASN successfully created', 'response' => $response, 'ASN' => $response->ASNNumber) );
            echo 'ASN Created Successfully '. $response->ASNNumber;
            echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";
        } else {
            echo json_encode(array('code' => 401, 'status' => 'failed', 'message' => 'Error creating ASN.', 'response' => $response));
            echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";
        }

        wp_die();
    } else {
        //Printing the Form
        echo '<form class="syncASN" action="" method="post" style="max-width:450px;"> ';
        echo '<table class="form-table">';
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
        );
        $loop = new WP_Query($args);
        echo '<tr>';
        echo '<td>';
        echo '<label for="SKU">SKU</label>';
        echo '</td>';
        echo '<td>';
        echo '<select id="SKU" name="SKU">';
        while ($loop->have_posts()): $loop->the_post();
            global $product;
            $sku = get_post_meta(get_the_ID(), '_sku', true);
            echo '<option value="' . $sku . '">' . $sku . '</option>';
        endwhile;

        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo '<label for="quantity">Quantity</label>';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" id="quantity" name="quantity">';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo '<label for="Cartons">Cartons</label>';
        echo '</td>';
        echo '<td>';
        echo '<input type="text" id="Cartons" value="1" name="Cartons">';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo '<label>Submit Data</label>';
        echo '</td>';
        echo '<td>';
        echo '<button type="reset" class="button-secondary"> Reset</button>';
        echo '<button class="button-primary" style="margin-left: 10px;" type="submit">Submit</button>';
        echo '</td>';
        echo '</tr>';
        echo '</table>';
        echo '</form>';
    }
} else {
    echo "<h3>Invalid Username and Or Password!</h3>";
}
