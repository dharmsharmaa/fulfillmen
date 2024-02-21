<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       www.dhairyasharma.com
 * @since      1.0.0
 *
 * @package    Fulfillmen
 * @subpackage Fulfillmen/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Fulfillmen
 * @subpackage Fulfillmen/includes
 * @author     Dhairya Sharma <hello@dhairyasharma.com>
 */
class Fulfillmen
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Fulfillmen_Loader    $loader    Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version    The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('FULFILLMEN_VERSION')) {
            $this->version = FULFILLMEN_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'fulfillmen';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Fulfillmen_Loader. Orchestrates the hooks of the plugin.
     * - Fulfillmen_i18n. Defines internationalization functionality.
     * - Fulfillmen_Admin. Defines all hooks for the admin area.
     * - Fulfillmen_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fulfillmen-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-fulfillmen-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-fulfillmen-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-fulfillmen-public.php';

        $this->loader = new Fulfillmen_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Fulfillmen_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Fulfillmen_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Fulfillmen_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        // Adding Custom Status To Woocoom
        $this->loader->add_action('init', $plugin_admin, 'register_my_new_order_statuses');
        // Add plugin settings to WooCommerce
        $this->loader->add_filter('woocommerce_get_settings_pages', $plugin_admin, 'fulfillmen_add_settings');

        // Adding Custom Status To Woocoom
        $this->loader->add_filter('wc_order_statuses', $plugin_admin, 'my_new_wc_order_statuses');

        //Tracking number Meta Field
        $this->loader->add_action('add_meta_boxes', $plugin_admin, 'add_postnord_meta_box');
        $this->loader->add_action('save_post_shop_order', $plugin_admin, 'save_postnord_meta_box_field_value', 10, 1);
        $this->loader->add_action('woocommerce_admin_order_data_after_shipping_address', $plugin_admin, 'postnord_custom_field_display_admin_order_meta', 10, 1);
        //$this->loader->add_action('woocommerce_email_after_order_table', $plugin_admin, 'add_postnord_tracking_to_customer_complete_order_email', 0, 1); //20, 4);
        $this->loader->add_action('woocommerce_email_order_meta', $plugin_admin, 'add_postnord_tracking_to_customer_complete_order_email', 10, 1); //20, 4);
        $this->loader->add_action('woocommerce_order_details_after_order_table', $plugin_admin, 'postnord_custom_field_display_customer_order_meta', 10, 1);

        //Scheduling CronJob for Tracking Update
        $this->loader->add_action('wp', $plugin_admin, 'fulfillmen_schedule_cron');
        $this->loader->add_action('fulfillmen_cron', $plugin_admin, 'fulfillmen_cron_function');

        //schedung CronJob for Orders Processing
        $this->loader->add_action('wp', $plugin_admin, 'fulfillmen_schedule_cron_for_orders');
        $this->loader->add_action('fulfillmen_orders_cron', $plugin_admin, 'fulfillmen_cron_function_orders');

        //Custom Cron Schedule for Tracking Update
        $this->loader->add_filter('cron_schedules', $plugin_admin, 'fulfillmen_check_every_3_hours');

        //webhook based order collection
        //$this->loader->add_action( 'woocommerce_payment_complete', $plugin_admin, 'fulfillmen_webhook_order_collection', 10, 1);        

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Fulfillmen_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @since     1.0.0
     * @return    Fulfillmen_Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

}
