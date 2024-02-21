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
$ASTIntegration = get_option($prefix . 'ast_integration');

if (!empty($userID) || !empty($userPass)) {
    $userString = "UserName=" . $userID . "&CusPsw=" . $userPass;
    $accountInfo = json_decode(file_get_contents('http://wms.fulfillmen.com/api-json/GetCustomer.aspx?' . $userString), true);
    $table_name1 = $wpdb->prefix . 'fmCountryIDS';
    $table_name2 = $wpdb->prefix . 'fmShippingChannels';
    //var_dump($accountInfo['Code']);
    if ($accountInfo['Code'] == "100") {
        $Accountbalance = $accountInfo['AccountMoney'];
        echo "<h3>Hello $userID</h3>";
        echo "<p>Your Current Balance Is $Accountbalance CNY</p>";

        $result = $wpdb->get_results("SELECT * from $table_name1");
        $result2 = $wpdb->get_results("SELECT * from $table_name2");
        if (count($result) == 0) {
            echo "<h3>Updating Country database please wait</h3>";
            $countryList = $accountInfo = json_decode(file_get_contents('http://wms.fulfillmen.com/api-json/GetCountryList.aspx?Key=' . $apiKey), true);
            $jResponse1 = $countryList['Code'];
            if ($jResponse1 == "100") {
                echo "DB Update in progress, do not click back or refresh the page.";
                //var_dump($countryList['data']);
                $data = $countryList['data'];
                $sql = "INSERT INTO $table_name1 (CountryCode,CountryFMID) values";
                $valuesArr = array();
                foreach ($data as $row) {
                    $countryID = $row["CountryID"];
                    $ShortName = $row["ShortName"];
                    $valuesArr[] = "('$ShortName','$countryID')";
                }
                //var_dump($valuesArr);
                $sql .= implode(',', $valuesArr);
                $wpdb->query($sql);
                /*if($wpdb->last_error !== '') :
                $wpdb->print_error();
                endif;*/
                echo "<br>Update Success!";
            } else {
                echo "DB Update failed, please check the API details or contact support.";
            }
        } else {
            echo "Database is up to date!";
            //echo $warehouse;
        }
        if (count($result2) == 0) {
            echo "<h3>Updating Shipping database please wait</h3>";
            $shippingList = $accountInfo = json_decode(file_get_contents('http://wms.fulfillmen.com/api-json/GetShippingList.aspx?Key=' . $apiKey), true);
            $jResponse2 = $shippingList['Code'];
            if ($jResponse2 == "100") {
                echo "DB Update in progress, do not click back or refresh the page.";
                //var_dump($shippingList['data']);
                $data = $shippingList['data'];
                $sql2 = "INSERT INTO $table_name2 (ChannelName,ChannelCode,ChannelID) values";
                $valuesArr = array();
                foreach ($data as $row) {
                    $ShippingID = $row["ShippingID"];
                    $ShippingCode = $row["ShippingCode"];
                    $ShippingName = $row["EnName"];
                    $valuesArr[] = "('$ShippingName','$ShippingCode','$ShippingID')";
                }
                //var_dump($valuesArr);
                $sql2 .= implode(',', $valuesArr);
                $wpdb->query($sql2);
                if ($wpdb->last_error !== ''):
                    $wpdb->print_error();
                endif;
                echo "<br>Update Success!";
            } else {
                echo "DB Update failed, please check the API details or contact support.";
            }
        }
        
    } else {
        echo "<h3>Invalid Username and Or Password!</h3>";
    }
} else {
    echo "<h3>Invalid Username and Or Password!</h3>";
}
