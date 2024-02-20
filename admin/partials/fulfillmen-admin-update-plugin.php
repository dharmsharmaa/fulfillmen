<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
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

if ( ! function_exists( 'WP_Filesystem' ) ) {
    require_once ABSPATH . 'wp-admin/includes/file.php';
}
$wp_filesystem = WP_Filesystem();

function copy_directory($src, $dst) {
    if (!is_readable($src)) {
        echo "Source directory '{$src}' is not readable.";
        return false;
    }

    if (!is_dir($dst)) {
        echo "Destination directory '{$dst}' does not exist or is not a directory.";
        return false;
    }

    $dir = opendir($src);
    if (!$dir) {
        echo "Failed to open source directory '{$src}'.";
        return false;
    }

    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;

            if (is_dir($srcFile)) {
                // Recursively copy subdirectories
                if (!copy_directory($srcFile, $dstFile)) {
                    echo "Failed to copy directory '{$srcFile}' to '{$dstFile}'.";
                    return false;
                }
            } else {
                // Copy files, overwriting existing ones
                if (!copy($srcFile, $dstFile)) {
                    echo "Failed to copy file '{$srcFile}' to '{$dstFile}'.";
                    return false;
                }
            }
        }
    }

    closedir($dir);
    return true;
}



function download_and_install_plugin_update() {
    $plugin_slug = 'fulfillmen'; 
    $github_repo = 'fulfillmen/woocommerce'; 

    // Check if cached data exists
    $cache_key = 'fulfillmen_plugin_update';
    $cached_data = get_transient($cache_key);

    if ($cached_data === false) {
        // Cached data doesn't exist or has expired, fetch fresh data from GitHub API
        $url = "https://api.github.com/repos/{$github_repo}/releases/latest";
        $response = wp_remote_get($url);

        if (!is_wp_error($response) && $response['response']['code'] === 200) {
            $release_data = json_decode($response['body'], true);
            
            // Cache the fetched data for 1 hour
            set_transient($cache_key, $release_data, HOUR_IN_SECONDS);
        }
    } else {
        // Use cached data
        $release_data = $cached_data;
    }

    if (isset($release_data) && is_array($release_data)) {
        $latest_version = $release_data['tag_name'];
        $installed_version = get_option("{$plugin_slug}_version");
        
        if (version_compare($installed_version, $latest_version, '<')) {
            // New version available, proceed with update
            $zip_url = $release_data['zipball_url'];
            $zip_file = download_url($zip_url);
            
            if (!is_wp_error($zip_file)) {
                // Extract zip file
                $plugin_dir = WP_PLUGIN_DIR . '/' . $plugin_slug;
                $extracted_dir = unzip_file($zip_file, $plugin_dir);
                
                if (!is_wp_error($extracted_dir)) {
                    // Replace existing plugin files
                    $source_dir = $extracted_dir . '/' . $plugin_slug . '-' . $release_data['tag_name'];
                    copy_directory($source_dir, $plugin_dir); 

                    // Update version number
                    update_option("{$plugin_slug}_version", $latest_version);

                    // Success message
                    echo '<div class="notice notice-success"><p>Plugin updated successfully!</p></div>';
                } else {
                    // Error message
                    echo '<div class="notice notice-error"><p>Failed to extract zip file: ' . $extracted_dir->get_error_message() . '</p></div>';
                }
            } else {
                // Error message
                echo '<div class="notice notice-error"><p>Failed to fetch the plugin file from url '.$zip_url.' </p></div>';
            }
        } else {
            // Plugin is up to date, no action needed
            echo '<div class="notice notice-info"><p>Plugin is already up to date.</p></div>';
        }
    } else {
        // Error message
        echo '<div class="notice notice-error"><p>Failed to fetch plugin update information.</p></div>';
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