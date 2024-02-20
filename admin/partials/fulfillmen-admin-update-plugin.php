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


function download_and_install_plugin_update() {
    $plugin_slug = 'fulfillmen'; 
    $github_repo = 'fulfillmen/woocommerce'; 

    // Download latest release zip file
    $url = "https://api.github.com/repos/{$github_repo}/releases/latest";
    $response = wp_remote_get($url);

    if (!is_wp_error($response) && $response['response']['code'] === 200) {
        $release_data = json_decode($response['body'], true);
        $zip_url = $release_data['zipball_url'];
        $zip_file = download_url($zip_url);

        if (!is_wp_error($zip_file)) {
            // Extract zip file
            $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
            $extracted_dir = unzip_file($zip_file, $plugin_dir); // Provide destination directory

            if (!is_wp_error($extracted_dir)) {
                // Replace existing plugin files
                $source_dir = $extracted_dir . '/' . $plugin_slug . '-' . $release_data['tag_name'];
                $result = copy_dir($source_dir, $plugin_dir, true);

                if ($result) {
                    // Update version number
                    update_option("{$plugin_slug}_version", $release_data['tag_name']);

                    // Success message
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-success"><p>Plugin updated successfully!</p></div>';
                    });
                } else {
                    // Error message
                    add_action('admin_notices', function() {
                        echo '<div class="notice notice-error"><p>Failed to update plugin.</p></div>';
                    });
                }
            }else{
                add_action('admin_notices', function() {
                    echo '<div class="notice notice-error"><p>Failed to update plugin.</p></div>';
                });
            }
        }else{
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>Failed to fetch the plugin file from url '.$zip_url.' </p></div>';
            });
        }
    }else{
        add_action('admin_notices', function() {
            echo '<div class="notice notice-error"><p>Failed to fetch the repository from '.$url.'.</p></div>';
        });
    }
}



    if (isset($_POST['update_plugin'])) {
        download_and_install_plugin_update();
    }
    ?>
    <div class="wrap">
        <h1>Update Your Plugin</h1>
        <form method="post">
            <p>Click the button below to update your plugin.</p>
            <input type="submit" name="update_plugin" class="button button-primary" value="Update Plugin">
        </form>
    </div>