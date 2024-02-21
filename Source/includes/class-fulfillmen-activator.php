<?php

/**
 * Fired during plugin activation
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.0
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Fulfillmen
 * @subpackage Fulfillmen/includes
 * @author     Dhairya Sharma <hello@dhairyasharma.com>
 */
class Fulfillmen_Activator
{

    /**
     * Short Description. (use period)
     *
     * Long Description.
     *
     * @since    1.0.0
     */
    public static function activate()
    {
        

            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            $table_name1 = $wpdb->prefix . 'fmCountryIDS';
            $table_name2 = $wpdb->prefix . 'fmShippingChannels';
			$table_name3 = $wpdb->prefix . 'fmSetShipping';
			$table_name4 = $wpdb->prefix . 'fmSKU';
			$table_name5 = $wpdb->prefix . 'fmOrders';
			$table_name6 = $wpdb->prefix . 'fmASN';

            if ($wpdb->get_var("show tables like '$table_name1'") != $table_name1) {
                $sql[] = "CREATE TABLE $table_name1 (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				CountryCode varchar(50) NOT NULL,
				CountryFMID smallint(5) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			}
			
            if ($wpdb->get_var("show tables like '$table_name2'") != $table_name2) {
                $sql[] = "CREATE TABLE $table_name2 (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				ChannelName varchar(50) NOT NULL,
				ChannelCode varchar(50) NOT NULL,
				ChannelID  smallint(5) NOT NULL,
				UNIQUE KEY id (id)
			) $charset_collate;";
			}
			
			if ($wpdb->get_var("show tables like '$table_name3'") != $table_name3) {
                $sql[] = "CREATE TABLE $table_name3 (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				ChannelID varchar(50) NOT NULL,
				CountryID varchar(50) NOT NULL,				
				UNIQUE KEY id (id)
			) $charset_collate;";
			}
			
			if ($wpdb->get_var("show tables like '$table_name4'") != $table_name4) {
                $sql[] = "CREATE TABLE $table_name4 (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				StoreSKU varchar(50) NOT NULL,
				isSynced tinyint(1) NOT NULL,				
				UNIQUE KEY id (id)
			) $charset_collate;";
			}

			if ($wpdb->get_var("show tables like '$table_name5'") != $table_name5) {
                $sql[] = "CREATE TABLE $table_name5 (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				OrderNumber varchar(50) NOT NULL,
				FulfillmenOrderNum varchar(50) NOT NULL,	
				FMOrderStatus varchar(50) NOT NULL,	
				FMOrderTracking varchar(50) NOT NULL,	
				isSynced tinyint(1) NOT NULL,			
				UNIQUE KEY id (id)
			) $charset_collate;";
			}

			if ($wpdb->get_var("show tables like '$table_name6'") != $table_name6) {
                $sql[] = "CREATE TABLE $table_name6 (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				time datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
				ASNInfo varchar(255) NOT NULL,
				SKU varchar(50) NOT NULL,	
				warehouse varchar(50) NOT NULL,				
				isSynced tinyint(1) NOT NULL,			
				UNIQUE KEY id (id)
			) $charset_collate;";
			}
			
            if (!empty($sql)) {
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                dbDelta($sql);
            }
        }		
    
}
