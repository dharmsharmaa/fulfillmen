<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              www.dhairyasharma.com
 * @since             1.0.0
 * @package           Fulfillmen
 *
 * @wordpress-plugin
 * Plugin Name:       Fulfillmen
 * Plugin URI:        http://www.fulfillmen.com
 * Description:       WooCommerce Order Fulfilment by Fulfillmen. Update notes: Bugfixes, added compatibility for the latest version of WooCommerce. 
 * Version:           1.1.7
 * GitHub Plugin URI: https://github.com/fulfillmen/woocommerce
 * Author:            Dhairya Sharma
 * Author URI:        www.dhairyasharma.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       fulfillmen
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}



define('FULFILLMEN_VERSION', '1.1.7');

if (!function_exists('is_plugin_active')) {
    include_once ABSPATH . '/wp-admin/includes/plugin.php';
}
 
function check_plugin_updates() {
    $plugin_slug = 'fulfillmen'; 
    $github_repo = 'fulfillmen/woocommerce'; 

    $url = "https://api.github.com/repos/{$github_repo}/releases/latest";
    $response = wp_remote_get($url);

    if (!is_wp_error($response) && $response['response']['code'] === 200) {
        $release_data = json_decode($response['body'], true);
        $latest_version = $release_data['tag_name'];
        $installed_version = get_option("{$plugin_slug}_version");
        if (version_compare($installed_version, $latest_version, '<')) {
            // New version available, prompting user to update
            add_action('admin_notices', function() {
                echo '<div class="notice notice-warning"><p>A new version of Fulfillmen Woocommerce Plugin is available. <a href="admin.php?page=wc-settings&tab=fulfillmen&section=pluginupdate">Update now</a></p></div>';
            });
        }
    }
}




function custom_update_checker($result, $action, $args) {
    if ($action === 'plugin_information' && isset($args->slug) && $args->slug === 'fulfillmen') {
        $github_repo = 'fulfillmen/woocommerce'; 
        $url = "https://api.github.com/repos/{$github_repo}/releases/latest";
        $response = wp_remote_get($url);

        if (!is_wp_error($response) && $response['response']['code'] === 200) {
            $release_data = json_decode($response['body'], true);
            $update = new stdClass();
            $update->slug = 'fulfillmen';
            $update->new_version = $release_data['tag_name'];
            $update->url = $release_data['html_url'];
            $update->package = $release_data['zipball_url'];
            $result = new WP_Error(false, '', $update);
        }
    }
    return $result;
}



// function fulfillmen_displayNews(){
//     global $pagenow;
//     if (( $pagenow == 'admin.php' ) && ($_GET['page'] == 'wc-settings'))  {       
//         add_action('admin_notices', 'fulfillmen_news_wc_notice');
//     }
// }

// function fulfillmen_news_wc_notice()
// {
//     $str = file_get_contents('https://fulfillmen.com/plugin/woocom.php');
//     $json = json_decode($str, true);
//     $jversion = $json['Version'];
//     $releasedOn = $json['ReleaseDate'];

//     if($jversion !== constant('FULFILLMEN_VERSION')){
//         $mdata = "You are not using the latest version of the official Fulfillmen Plugin, Please update to the latest version $jversion released on $releasedOn , The plugin can be downloaded from the following link: <a href='https://fulfillmen.com/plugin/fulfillmen.zip'> Fulfillmen Official Build </a> ";
//     }
//     else{
//         $mdata = "You are running the latest version of the official Fulfillmen Plugin version $jversion, released on $releasedOn ";
//     }
//     $class = 'notice notice-success';
    
//     $message = __($mdata, 'fulfillmen');
//     printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
// }


function fulfillmen_check_requirements()
{
    if (is_plugin_active('woocommerce/woocommerce.php')) {
        return true;
    } else {
        add_action('admin_notices', 'fulfillmen_missing_wc_notice');
        return false;
    }
}

function fulfillmen_missing_wc_notice()
{
    $class = 'notice notice-error';
    $message = __('Fulfillmen WooCommerce Integration requires WooCommerce to be installed and active.', 'fulfillmen');

    printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-fulfillmen-activator.php
 */
function activate_fulfillmen()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fulfillmen-activator.php';    
    Fulfillmen_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-fulfillmen-deactivator.php
 */
function deactivate_fulfillmen()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-fulfillmen-deactivator.php';
    Fulfillmen_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_fulfillmen');
//register_activation_hook(__FILE__, 'createDBTables');
register_deactivation_hook(__FILE__, 'deactivate_fulfillmen');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-fulfillmen.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_fulfillmen()
{
    if (fulfillmen_check_requirements()) {
        $plugin = new Fulfillmen();
        $plugin->run();
        add_action('admin_init', 'check_plugin_updates');
        add_filter('plugins_api', 'custom_update_checker', 20, 3);
        //fulfillmen_displayNews();
    }
}
run_fulfillmen();
