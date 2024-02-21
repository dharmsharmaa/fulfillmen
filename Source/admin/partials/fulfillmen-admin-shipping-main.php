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
$shippingConfiTable = $wpdb->prefix . 'fmSetShipping';
$fmCountryID = $wpdb->prefix . 'fmCountryIDS';
$ShippingChannelID = $wpdb->prefix . 'fmShippingChannels';

if (!empty($userID) || !empty($userPass)) {
    //echo 'Shipping Lines Configuration <br/>';
    if (!empty($_POST)) {
        //Saving to the DB
        $country = $_POST['countries'];
        $shippingChannel = $_POST['shippingChannel'];
        $sql = "INSERT INTO $shippingConfiTable (ChannelID,CountryID) values('$shippingChannel','$country')";
        $wpdb->query($sql);
        echo '<br>Configuration saved successfully.';
        echo "<br><a href='#' onclick='history.go(-1);' class='button-secondary'>Back</a>";

    } else {
        if (isset($_GET['action'])) {
            $action = $_GET['action'];
            if ($action == "deletechannel") {
                $cid = $_GET['cid'];
                $alterconfig = $wpdb->get_results("DELETE from $shippingConfiTable WHERE id = $cid");
                echo "Database Has been updated! <br>";
            }
        }

        echo '<table class="wp-list-table widefat fixed striped posts">';
        echo '<td>Country</td> <td>Channel</td> <td>Actions</td>';
        $resultMain = $wpdb->get_results("SELECT * from $shippingConfiTable");
        foreach ($resultMain as $row):
            echo '</tr>';
            echo '<td> ' . $row->CountryID . ' </td>';
            echo '<td> ' . $row->ChannelID . ' </td>';
            echo '<td><a href="admin.php?page=wc-settings&tab=fulfillmen&section=shipping&action=deletechannel&cid=' . $row->id . '" >Delete Entry</a> </td>';
            echo '</tr>';
        endforeach;

        //Printing the Form
        echo '<h2>Add a new channel</h2>';
        echo '<form class="syncASN" action="" method="post" style="max-width:450px;"> ';
        echo '<table class="form-table">';

        $channels = json_decode(file_get_contents('http://wms.fulfillmen.com/api-json/GetShippingList.aspx?Key=' . $apiKey), true);
        $jResponse1 = $channels['Code'];

        $countryList = json_decode(file_get_contents('http://wms.fulfillmen.com/api-json/GetCountryList.aspx?Key=' . $apiKey), true);
        $jResponse2 = $countryList['Code'];

        echo '<tr>';
        echo '<td>';
        echo '<label for="countries">Country</label>';
        echo '</td>';
        echo '<td>';
        echo '<select id="countries" name="countries" class="wc-enhanced-select enhanced">';
        if ($jResponse2 == "100") {
            $data = $countryList['data'];
            foreach ($data as $row) {
                $countryID = $row["ShortName"];
                $ShortName = $row["EnName"];
                echo '<option value="' . $countryID . '">' . $ShortName . '</option>';
            }
        } else {
            $result1 = $wpdb->get_results("SELECT * from $fmCountryID ORDER BY CountryCode ASC");
            foreach ($result1 as $row):
                echo '<option value="' . $row->CountryCode . '">' . $row->CountryCode . '</option>';
            endforeach;
        }

        echo '</select>';
        echo '</td>';
        echo '</tr>';

        echo '<tr>';
        echo '<td>';
        echo '<label for="shippingChannel">Shipping Channel</label>';
        echo '</td>';
        echo '<td>';
        echo '<select id="shippingChannel" name="shippingChannel" class="wc-enhanced-select enhanced">';
        if ($jResponse1 == "100") {
            $data = $channels['data'];
            foreach ($data as $row) {
                $channelID = $row["ShippingCode"];
                $ShortName = $row["EnName"];
                echo '<option value="' . $channelID . '">' . $ShortName . '</option>';
            }
        } else {
            //fallback
            $result2 = $wpdb->get_results("SELECT * from $ShippingChannelID ORDER BY ChannelCode ASC");
            foreach ($result2 as $row):
                echo '<option value="' . $row->ChannelCode . '">' . $row->ChannelCode . '</option>';
            endforeach;
        }

        echo '</select>';
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
