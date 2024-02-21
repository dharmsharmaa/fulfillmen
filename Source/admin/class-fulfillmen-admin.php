<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.0
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/admin
 * @author     Dhairya Sharma <hello@dhairyasharma.com>
 */
class Fulfillmen_Admin
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;

    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fulfillmen_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fulfillmen_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/fulfillmen-admin.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in Fulfillmen_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The Fulfillmen_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/fulfillmen-admin.js', array('jquery'), $this->version, false);
    }
    /**
     * Load dependencies for additional WooCommerce settings
     *
     * @since    1.0.0
     * @access   private
     */

    public function fulfillmen_add_settings($settings)
    {
        $settings[] = include plugin_dir_path(dirname(__FILE__)) . 'admin/class-fulfillmen-wc-settings.php';
        return $settings;
    }

    //Adding custom Status

    public function register_my_new_order_statuses()
    {
        register_post_status('wc-fulfilled', array(
            'label' => _x('Fulfilled', 'Order status', 'woocommerce'),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop('Fulfilled <span class="count">(%s)</span>', 'Fulfilled<span class="count">(%s)</span>', 'woocommerce'),
        ));
    }

    public function my_new_wc_order_statuses($order_statuses)
    {
        $order_statuses['wc-fulfilled'] = _x('Fulfilled', 'Order status', 'woocommerce');
        return $order_statuses;
    }

    public function my_new_wc_order_statuses2($order_statuses1)
    {
        $order_statuses1['wc-fminprocess'] = _x('Processing at Warehouse', 'Order status', 'woocommerce');
        return $order_statuses1;
    }
    //Adding custom Field to capture Tracking Number
    // Add a the metabox to Order edit pages
    public function add_postnord_meta_box()
    {
        function add_postnord_meta_box_content()
        {
            global $post;

            $value = get_post_meta($post->ID, '_postnord_field_data', true);

            echo '<input type="hidden" name="postnord_meta_field_nonce" value="' . wp_create_nonce() . '">
                <p style="border-bottom:solid 1px #eee;padding-bottom:13px;">
                <input type="text" style="width:250px;";" name="postnord_data_name" value="' . $value . '"></p>';
        }

        add_meta_box('postnord_field', __('Parcel ID', 'woocommerce'), 'add_postnord_meta_box_content', 'shop_order', 'side', 'core');

    }

// Save the field value from metabox content
    public function save_postnord_meta_box_field_value($post_id)
    {

        if (!isset($_POST['postnord_meta_field_nonce'])) {
            return $post_id;
        }

        $nonce = $_REQUEST['postnord_meta_field_nonce'];

        if (!wp_verify_nonce($nonce)) {
            return $post_id;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }

        if (!(current_user_can('edit_shop_order', $post_id) || current_user_can('edit_shop_order', $post_id))) {
            return $post_id;
        }

        update_post_meta($post_id, '_postnord_field_data', sanitize_text_field($_POST['postnord_data_name']));
    }

// Display post north tracking info under shipping address
    public function postnord_custom_field_display_admin_order_meta($order)
    {
        $postnord_value = $order->get_meta('_postnord_field_data');
        if (!empty($postnord_value)) {
            echo '<p><strong>' . __("Tracking ID", "woocommerce") . ':</strong> ' . $postnord_value . '</p>';
        }
    }

// Display post north tracking info under shipping address
 public function postnord_custom_field_display_customer_order_meta($order)
    {
        $prefix = 'fulfillmen_';
        $tracking_data = $order->get_meta('_postnord_field_data');
        $TrackingURl = get_option($prefix . 'customtrackingurl').$tracking_data;
        if (!empty($tracking_data)) {
            // Display the tracking information
            $tracking_info = '<address><span style="display: inline-block;font-style: normal;">Tracking Number: '.$tracking_data.' <a style="font-size: 10px;display: inline-block;vertical-align: middle;margin-left: 5px;" href="'.$TrackingURl.'" class="button" target="_blank">Track Your Order</a></span></address>';
            echo '<div style="border: 1px solid #cbd5e0;padding: 5px;border-radius: 5px;margin-bottom: 15px;"><h6>' . __('Tracking Information', 'woocommerce') . '</h6> ' . $tracking_info . '</div>';
        }
    }


// Display post north tracking info and urls on customer email
    //public function add_postnord_tracking_to_customer_complete_order_email($order, $sent_to_admin, $plain_text, $email)
    public function add_postnord_tracking_to_customer_complete_order_email($order)
    {

        $prefix = 'fulfillmen_';
        $TrackingURl = get_option($prefix . 'customtrackingurl');
        /*if ($sent_to_admin) {
        return;
        }*/
        // Exit

        $postnord_value = $order->get_meta('_postnord_field_data');

        if (!empty($postnord_value)) {
            $postnord_url = 'http://track.fulfillmen.com/';
            $tracking_url = $TrackingURl . $postnord_value;
            $title = __("Track Your Order", "woocommerce");
            $message = '<p><strong>' . __("Tracking ID", "woocommerce") . ':</strong> ' . $postnord_value . '</p>
            <p>' . sprintf(__("You can track your parcel %s ", "woocommerce"),
                // '<a href="' . $postnord_url . '" target="_blank">' . __("Tracking website", "woocommerce") . '</a>',
                '<a href="' . $tracking_url . '" target="_blank">' . __("here", "woocommerce") . '</a>.</p>');

            echo '<style>
        .tracking table {width: 100%; font-family: \'Helvetica Neue\', Helvetica, Roboto, Arial, sans-serif;
            color: #737373; border: 1px solid #e4e4e4; margin-bottom:8px;}
        .tracking table td{text-align: left; border-top-width: 4px; color: #737373; border: 1px solid #e4e4e4;
            padding: 12px; padding-bottom: 4px;}
        </style>
        <div class="tracking">
        <h2>' . $title . '</h2>
        <table cellspacing="0" cellpadding="6">
            <tr><td>' . $message . '</td></tr>
        </table></div><br>';
        } 
    }

    //Setup Cron for Tracking Update

    public function fulfillmen_check_every_3_hours($schedules)
    {
        $schedules['every_three_hours'] = array(
            'interval' => 10800,
            'display' => __('Every 3 hours'),
        );
        return $schedules;
    }

    public function fulfillmen_schedule_cron()
    {
        if (!wp_next_scheduled('fulfillmen_cron')) {
            wp_schedule_event(time(), 'every_three_hours', 'fulfillmen_cron');
        }

    }

    public function fulfillmen_schedule_cron_for_orders()
    {
        if (!wp_next_scheduled('fulfillmen_orders_cron')) {
            wp_schedule_event(time(), 'every_three_hours', 'fulfillmen_orders_cron');
        }

    }

    public function fulfillmen_cron_function()
    {
        //Trigger Tracking updates
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/fulfillmen-admin-trackings-update.php';
        /*$CronBody = "Cron Ran at:".date('r')."\n".$TrackResponse;
    wp_mail('it@fulfillmen.com', 'Cron Worked', $CronBody);*/
    }

    public function fulfillmen_cron_function_orders()
    {
        //Trigger Tracking updates
        include plugin_dir_path(dirname(__FILE__)) . 'admin/partials/fulfillmen-admin-orders-update.php';
    }

    public function fulfillmen_webhook_order_collection($order)
    {
        $ifWebhook = get_option($prefix . 'webhook_fw_orders');
        if ($ifWebhook == 'yes') {
            $Body = "First Sequence completed at at:" . date('r') . "\n";
            //wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);

            include_once plugin_dir_path(dirname(__FILE__)) . 'admin/partials/fulfillmen-admin-orders-webhook.php';

            $Body = "Secnond Sequence completed at at:" . date('r') . "\n";
            //wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);

            $orderid = wc_get_order($order);
            $response = wf_process_orders($orderid, 'http://wms.fulfillmen.com/webservice/APIWebService.asmx', $credentials, $storeName);
            if (is_wp_error($response)) {
                // echo json_encode(array('success' => $response->get_error_message()));
                $TrackResponse .= json_encode(array('success' => $response->get_error_message()));

                $Body = "Third Sequence failed at at:" . date('r') . "\n" . $TrackResponse;
               // wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);
            } else {
                $Body = "Third Sequence Completed at at:" . date('r') . "\n";
                //wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);

                $res = json_decode($response);
                // var_dump($response);
                // var_dump($res);
                if ($res->success == "success") {

                    $Body = "Fourth Sequence completed at at:" . date('r') . "\n";
                    //wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);

                    //echo json_encode(array('code' => $res->success, 'status' => $res->success, 'order'=> $res->OrderNo, 'reference'=> $res->CsRefNo));

                    $sql = "INSERT INTO $ordersTable (OrderNumber,FulfillmenOrderNum,FMOrderStatus,FMOrderTracking,isSynced) values('$res->CsRefNo','$res->OrderNo','Draft','NA','0')";
                    $wpdb->query($sql);

                    /*$WCorder = new WC_Order($orderid);
                    $WCorder->update_status('wc-processing');*/
                    update_post_meta($order, 'fmDraftSynced', 'yes');
                    update_post_meta($order, 'fmDraftOrderNumber', $res->OrderNo);
                    $TrackResponse .= "Order Processed: " . $res->OrderNo;
                    $Body = "Final Sequence completed at at:" . date('r') . "\n" . $TrackResponse;
                    //wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);

                } else {
                    $TrackResponse .= "Error Occured: " . $res->Info;
                    //echo "<br>Error Occured: " . $res->Info;
                    $Body = "Final Sequence failed at at:" . date('r') . "\n" . $TrackResponse;
                    //wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);

                }
            }
        } else {
            $Body = "First Sequence completed at at:" . date('r') . "\n";
           // wp_mail('hello@cruze.pw', 'Orders Are Processed', $Body);
            return;
        }

    }

}
