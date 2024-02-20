<?php
/**
 * Extends the WC_Settings_Page class
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.0
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/admin
 *
 */
if (!defined('ABSPATH')) {
    exit;
}
// Exit if accessed directly
if (!class_exists('Fulfillmen_WC_Settings')) {
    /**
     * Settings class
     *
     * @since 1.0.0
     */
    class Fulfillmen_WC_Settings extends WC_Settings_Page
    {
        /**
         * Constructor
         * @since  1.0
         */
        public function __construct()
        {

            $this->id = 'fulfillmen';
            $this->label = __('Fulfillmen - WooCommerce', 'fulfillmen');
            // Define all hooks instead of inheriting from parent
            add_filter('woocommerce_settings_tabs_array', array($this, 'add_settings_page'), 20);
            add_action('woocommerce_sections_' . $this->id, array($this, 'output_sections'));
            add_action('woocommerce_settings_' . $this->id, array($this, 'output'));
            add_action('woocommerce_settings_save_' . $this->id, array($this, 'save'));

        }
        /**
         * Get sections.
         *
         * @return array
         */
        public function get_sections()
        {
            $sections = array(
            '' => __('Settings', 'fulfillmen'),
            'orders' => __('Orders Management', 'fulfillmen'),
            'inventory' => __('Inventory Management', 'fulfillmen'),
            'asn' => __('ASN Management', 'fulfillmen'),
            'trackings' => __('Tracking Numbers', 'fulfillmen'),
            'shipping' => __('Shipping Configuration', 'fulfillmen'),
            'account' => __('Account Info', 'fulfillmen'),
            'bulktracking' => __('Bulk Tracking Update', 'fulfillmen'),
            'pluginupdate'=> __('Plugin Update', 'fulfillmen')
            );
            return apply_filters('woocommerce_get_sections_' . $this->id, $sections);
        }
        /**
         * Get settings array
         *
         * @return array
         */
        public function get_settings()
        {
            global $current_section;
            $prefix = 'fulfillmen_';
            $settings = array();
            switch ($current_section) {
                case 'log':
                    $settings = array(
                        array(),
                    );
                    break;
                default:
                    /*$settings = array(
                    array()
                    );    */
                    include 'partials/fulfillmen-admin-settings-main.php';
            }
            return apply_filters('woocommerce_get_settings_' . $this->id, $settings, $current_section);
        }
        /**
         * Output the settings
         */
        public function output()
        {
            global $current_section;

            switch ($current_section) {
                case 'orders':
                    include 'partials/fulfillmen-admin-orders-main.php';
                    break;
                case 'account':
                    include 'partials/fulfillmen-admin-account-main.php';
                    break;
                case 'inventory':
                    include 'partials/fulfillmen-admin-inventory-main.php';
                    break;
                case 'asn':
                    include 'partials/fulfillmen-admin-asn-main.php';
                    break;
                case 'trackings':
                    include 'partials/fulfillmen-admin-tracking-main.php';
                    break;
                case 'shipping':
                    include 'partials/fulfillmen-admin-shipping-main.php';
                    break;
                case 'bulktracking':
                    include 'partials/fulfillmen-admin-bulk-trackings-main.php';
                    break;
                case 'pluginupdate':
                    include 'partials/fulfillmen-admin-update-plugin.php';
                    break;
                default:
                    $settings = $this->get_settings();
                    WC_Admin_Settings::output_fields($settings);
            }

            /*$settings = $this->get_settings();
        WC_Admin_Settings::output_fields( $settings );*/
        }
        /**
         * Save settings
         *
         * @since 1.0
         */
        public function save()
        {
            $settings = $this->get_settings();
            WC_Admin_Settings::save_fields($settings);
        }
    }
}
return new Fulfillmen_WC_Settings();
