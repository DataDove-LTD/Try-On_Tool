<?php
/**
 * A WooCommerce plugin that allows users to virtually try on clothing and accessories.
 *
 * @package Try-On Tool
 * @copyright 2025 DataDove LTD
 * @license GPL-2.0-only
 *
 * This file is part of Try-On Tool.
 * 
 * Try-On Tool is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, version 2 only.
 * 
 * Try-On Tool is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */
/**
 * Plugin Name: Try-On Tool
 * Plugin URI: https://tryontool.com
 * Description: Connect WooCommerce with Try-On Tool for AI-generated virtual try-on previews
 * Version: 1.2.3
 * Author: DataDove
 * Author URI: https://tryontool.com
 * Text Domain: woo-fitroom-preview
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 * HPOS Compatible: true
 * Network: false
 * Update URI: https://tryontool.com/updates/
 */
// Modified by DataDove LTD on 9/24/2025

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin identifier for WordPress compatibility across folder name changes
if (!defined('WOO_FITROOM_PLUGIN_IDENTIFIER')) {
    define('WOO_FITROOM_PLUGIN_IDENTIFIER', 'try-on-tool-plugin');
}

// Prevent multiple plugin instances - check early to avoid conflicts
if (defined('WOO_FITROOM_PREVIEW_VERSION')) {
    // Plugin already loaded, prevent conflicts
    add_action('admin_notices', function() {
        $current_version = defined('WOO_FITROOM_PREVIEW_VERSION') ? WOO_FITROOM_PREVIEW_VERSION : 'unknown';
        echo '<div class="notice notice-error"><p><strong>Try-On Tool:</strong> Multiple versions detected. ';
        echo 'Current version: ' . esc_html($current_version) . '. ';
        echo 'Please deactivate the older version before activating the newer one.</p></div>';
    });
    return;
}

// Additional safety check - if any of our classes already exist, abort
if (class_exists('WooFITROOM_Wasabi') || 
    class_exists('WooFitroomPreview_Settings') || 
    class_exists('WooFitroomPreview_Product_Button') ||
    class_exists('WooFitroomPreview_Shortcode') ||
    class_exists('WooFitroomPreview_API_Handler')) {
    
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Try-On Tool:</strong> Plugin classes already loaded. ';
        echo 'Please deactivate the existing version before activating the new one.</p></div>';
    });
    return;
}

// Check if we're in the correct plugin directory (prevent loading from wrong location)
$current_plugin_file = plugin_basename(__FILE__);
$expected_plugin_file = 'woo-fitroom-preview.php';
if (basename($current_plugin_file) !== $expected_plugin_file) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p><strong>Try-On Tool:</strong> Plugin file location mismatch. ';
        echo 'Please ensure the plugin is installed in the correct directory.</p></div>';
    });
    return;
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'woo_fitroom_preview_activate');
register_deactivation_hook(__FILE__, 'woo_fitroom_preview_deactivate');

function woo_fitroom_preview_activate() {
    // Set a flag to indicate the plugin is activated
    update_option('woo_fitroom_preview_activated', true);
    update_option('woo_fitroom_preview_version', WOO_FITROOM_PREVIEW_VERSION);
    
    // Store plugin identifier for folder name compatibility
    update_option('woo_fitroom_preview_plugin_identifier', WOO_FITROOM_PLUGIN_IDENTIFIER);
    
    // Check if this is an update by comparing versions
    $previous_version = get_option('woo_fitroom_preview_previous_version', '');
    $is_update = false;
    
    if ($previous_version && version_compare($previous_version, WOO_FITROOM_PREVIEW_VERSION, '<')) {
        // This is an update
        $is_update = true;
        update_option('woo_fitroom_preview_updated_from', $previous_version);
        update_option('woo_fitroom_preview_updated_to', WOO_FITROOM_PREVIEW_VERSION);
        
        // Clean up any potential conflicts from old version
        woo_fitroom_cleanup_old_version();
        
        // Show update success notice
        add_action('admin_notices', function() use ($previous_version) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>Try-On Tool:</strong> Successfully updated from version ' . esc_html($previous_version) . ' to ' . esc_html(WOO_FITROOM_PREVIEW_VERSION) . '!</p>';
            echo '</div>';
        });
    }
    
    // Store current version as previous for next update
    update_option('woo_fitroom_preview_previous_version', WOO_FITROOM_PREVIEW_VERSION);
    
    // Add default options (moved from duplicate function)
    add_option('WOO_FITROOM_preview_enabled', false);
    add_option('WOO_FITROOM_daily_credits', 0);
    add_option('WOO_FITROOM_logged_in_only', false);
    add_option('WOO_FITROOM_allowed_roles', array());
    add_option('WOO_FITROOM_allowed_user_ids', '');
    add_option('WOO_FITROOM_required_user_tag', '');
    add_option('WOO_FITROOM_require_extra_consents', 1); // Default to checked
    add_option('WOO_FITROOM_consent_default_set', true); // Mark that default consent has been set
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

function woo_fitroom_cleanup_old_version() {
    // Clear any cached data that might cause conflicts
    wp_cache_flush();
    
    // Clear any transients that might be from old version
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_woo_fitroom_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_woo_fitroom_%'");
    
    // Clear any object cache
    if (function_exists('wp_cache_delete_group')) {
        wp_cache_delete_group('woo_fitroom');
    }
}

function woo_fitroom_preview_deactivate() {
    // Clear the activation flag
    delete_option('woo_fitroom_preview_activated');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Show update notice if plugin was just updated
add_action('admin_notices', function() {
    $updated_from = get_option('woo_fitroom_preview_updated_from', '');
    $updated_to = get_option('woo_fitroom_preview_updated_to', '');
    
    if ($updated_from && $updated_to) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>Try-On Tool:</strong> Successfully updated from version ' . esc_html($updated_from) . ' to ' . esc_html($updated_to) . '! ';
        echo 'New features include enhanced theme compatibility for Astra, OceanWP, GeneratePress, and Storefront themes.</p>';
        echo '</div>';
        
        // Clear the update flags after showing the notice
        delete_option('woo_fitroom_preview_updated_from');
        delete_option('woo_fitroom_preview_updated_to');
    }
});

// Declare HPOS compatibility
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
    }
});

// HPOS compatibility helper functions
if (!function_exists('fitroom_get_order_meta')) {
    function fitroom_get_order_meta( $order_id, $key, $single = true ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return false;
    }
    return $order->get_meta( $key, $single );
}

    function fitroom_update_order_meta( $order_id, $key, $value ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return false;
    }
    $order->update_meta_data( $key, $value );
    $order->save();
    return true;
}

    function fitroom_delete_order_meta( $order_id, $key ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return false;
    }
    $order->delete_meta_data( $key );
    $order->save();
    return true;
    }
}

// BEGIN: Buffer output early to prevent BOM/header issues
if ( ! ob_get_level() ) {
    ob_start();
    // Ensure buffer flushes cleanly at shutdown
    add_action( 'shutdown', function () {
        if ( ob_get_level() ) {
            @ob_end_flush();
        }
    }, 0 );
}
// END buffer setup

// Define plugin constants only if not already defined
if (!defined('WOO_FITROOM_PREVIEW_VERSION')) {
    define('WOO_FITROOM_PREVIEW_VERSION', '1.2.3');
}
if (!defined('WOO_FITROOM_PREVIEW_PLUGIN_DIR')) {
    define('WOO_FITROOM_PREVIEW_PLUGIN_DIR', plugin_dir_path(__FILE__));
}
if (!defined('WOO_FITROOM_PREVIEW_PLUGIN_URL')) {
    define('WOO_FITROOM_PREVIEW_PLUGIN_URL', plugin_dir_url(__FILE__));
}
// FitRoom relay endpoints
if ( ! defined( 'FITROOM_RELAY_ENDPOINT' ) ) {
    define( 'FITROOM_RELAY_ENDPOINT', 'https://tryontool.com/wp-json/fitroom/v1/preview' );
}
if ( ! defined( 'FITROOM_VALIDATE_ENDPOINT' ) ) {
    define( 'FITROOM_VALIDATE_ENDPOINT', 'https://tryontool.com/wp-json/fitroom/v1/validate-license' );
}
if (!defined('WOO_FITROOM_INACTIVITY_WINDOW')) {
    define('WOO_FITROOM_INACTIVITY_WINDOW', YEAR_IN_SECONDS);
}
// Primary text-domain constant (used throughout PHP & JS)
if (!defined('WOO_FITROOM_TEXTDOMAIN')) {
    define('WOO_FITROOM_TEXTDOMAIN', 'woo-fitroom-preview');
}

/**
 * Check if WooCommerce is active
 */
function WOO_FITROOM_preview_check_woocommerce() {
    if (!in_array(
        'woocommerce/woocommerce.php',
        apply_filters('active_plugins', get_option('active_plugins'))
    )) {
        add_action('admin_notices', 'WOO_FITROOM_preview_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * WooCommerce missing notice
 */
function WOO_FITROOM_preview_woocommerce_missing_notice() {
    ?>
    <div class="error">
        <p><?php _e('WooCommerce Try-On Tool Preview requires WooCommerce to be installed and activated!', 'woo-fitroom-preview'); ?></p>
    </div>
    <?php
}

// Add debug logging
add_action('init', function() {
    static $already_run = false;
    if ($already_run) {
        return;
    }
    $already_run = true;
    error_log('WooTryOnTool Plugin: Initializing');
});

class WooFitroomPreview {
    private static $instance = null;

    private function __construct() {
        // Add debug logging
        error_log('WooTryOnTool Plugin: Inside init function');
        
        // Check if WooCommerce is active
        if (!WOO_FITROOM_preview_check_woocommerce()) {
            error_log('WooTryOnTool Plugin: WooCommerce not active');
            return;
        }

        // Load text domain for translation
        load_plugin_textdomain(
            'woo-fitroom-preview',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages/'
        );

        // Load classes
        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-wasabi-client.php';
        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-api-handler.php';
        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-settings.php';
        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-product-button.php';

        // Initialize classes
        $settings = new WooFitroomPreview_Settings();
        $shortcode = new WooFitroomPreview_Shortcode();
        $product_button = new WooFitroomPreview_Product_Button();
        
        // Add debug logging
        error_log('WooTryOnTool Plugin: Classes initialized');
    }

    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}

// Initialize the plugin
add_action('plugins_loaded', ['WooFitroomPreview', 'get_instance']);

// Plugin activation function moved to top of file to avoid duplication

add_action('rest_api_init', function () {
    register_rest_route('woo-tryontool/v1', '/wasabi-image', array(
        'methods' => 'GET',
        'callback' => function ($request) {
            // Decode the incoming object key to handle characters like %40 for '@'
            $key = $request->get_param('key');
            if (is_string($key)) {
                $key = rawurldecode($key);
            }
            if (!$key) {
                return new WP_Error('no_key', 'Missing key', array('status' => 400));
            }

            require_once __DIR__ . '/includes/class-wasabi-client.php';
            $s3 = WooFITROOM_Wasabi::client();
            $bucket = WooFITROOM_Wasabi::bucket();

            try {
                $result = $s3->getObject([
                    'Bucket' => $bucket,
                    'Key'    => $key,
                ]);
                $imageData = is_object($result['Body']) && method_exists($result['Body'], 'getContents')
                    ? $result['Body']->getContents()
                    : (string) $result['Body'];
                $remoteContentType = isset($result['ContentType']) ? (string) $result['ContentType'] : '';
                // Normalise non-standard or truncated content-types
                if ($remoteContentType && !preg_match('/jpe?g$/i', $remoteContentType)) {
                    $remoteContentType = 'image/jpeg';
                }
            } catch (Exception $e) {
                return new WP_Error('not_found', 'Image not found', array('status' => 404));
            }

            // First, try to convert directly from bytes to JPEG to keep it simple
            $srcFromString = @imagecreatefromstring($imageData);
            if ($srcFromString) {
                $w = imagesx($srcFromString);
                $h = imagesy($srcFromString);
                $canvas = imagecreatetruecolor($w, $h);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
                imagecopy($canvas, $srcFromString, 0, 0, 0, 0, $w, $h);
                while (ob_get_level()) { @ob_end_clean(); }
                header('Content-Type: image/jpeg');
                header('Content-Disposition: inline; filename="' . basename($key) . '"');
                header('Cache-Control: public, max-age=31536000, immutable');
                imagejpeg($canvas, null, 90);
                imagedestroy($canvas);
                imagedestroy($srcFromString);
                exit;
            }
            
            // Fallback: try Imagick for exotic formats (HEIC/AVIF/etc.)
            if (class_exists('Imagick')) {
                try {
                    $imagick = new Imagick();
                    $imagick->readImageBlob($imageData);
                    $imagick->setImageColorspace(Imagick::COLORSPACE_RGB);
                    $imagick->setImageFormat('jpeg');
                    while (ob_get_level()) { @ob_end_clean(); }
                    header('Content-Type: image/jpeg');
                    header('Content-Disposition: inline; filename="' . basename($key) . '"');
                    header('Cache-Control: public, max-age=31536000, immutable');
                    echo $imagick->getImagesBlob();
                    $imagick->clear();
                    $imagick->destroy();
                    exit;
                } catch (Exception $e) {
                    // Imagick failed – continue to file-based fallback below
                }
            }
            
            // Save to temp file for type detection/conversion
            $tmpFile = tempnam(sys_get_temp_dir(), 'wasabiimg');
            file_put_contents($tmpFile, $imageData);

            $imageType = @exif_imagetype($tmpFile);
            if ($imageType !== IMAGETYPE_JPEG) {
                // Convert to JPEG
                switch ($imageType) {
                    case IMAGETYPE_PNG:
                        $src = imagecreatefrompng($tmpFile);
                        break;
                    case IMAGETYPE_GIF:
                        $src = imagecreatefromgif($tmpFile);
                        break;
                    case IMAGETYPE_WEBP:
                        $src = ( function_exists('imagecreatefromwebp') ) ? imagecreatefromwebp($tmpFile) : false;
                        break;
                    default:
                        $src = imagecreatefromstring(file_get_contents($tmpFile));
                }

                // If conversion succeeded, output JPEG
                if ($src) {
                    while (ob_get_level()) { @ob_end_clean(); }
                    header('Content-Type: image/jpeg');
                    header('Content-Disposition: inline; filename="' . basename($key) . '"');
                    header('Cache-Control: public, max-age=31536000, immutable');
                    imagejpeg($src, null, 90);
                    imagedestroy($src);
                    @unlink($tmpFile);
                    exit;
                }

                // Conversion failed (e.g., server lacks WEBP support) — return original bytes with original content-type when available
                $fallbackMime = $remoteContentType;
                if (!$fallbackMime) {
                    switch ($imageType) {
                        case IMAGETYPE_PNG:  $fallbackMime = 'image/png';  break;
                        case IMAGETYPE_GIF:  $fallbackMime = 'image/gif';  break;
                        case IMAGETYPE_WEBP: $fallbackMime = 'image/webp'; break;
                        default:             $fallbackMime = 'application/octet-stream';
                    }
                }
                while (ob_get_level()) { @ob_end_clean(); }
                header('Content-Type: ' . $fallbackMime);
                header('Content-Disposition: inline; filename="' . basename($key) . '"');
                header('Cache-Control: public, max-age=31536000, immutable');
                readfile($tmpFile);
                @unlink($tmpFile);
                exit;
                
            }
            // If already JPEG, just output
            // Prefer the upstream content-type when provided
            while (ob_get_level()) { @ob_end_clean(); }
            header('Content-Type: ' . ( $remoteContentType ?: 'image/jpeg' ) );
            header('Content-Disposition: inline; filename="' . basename($key) . '"');
            header('Cache-Control: public, max-age=31536000, immutable');
            readfile($tmpFile);
            @unlink($tmpFile);
            exit;
        },
        'permission_callback' => '__return_true', // Public access
    ));
});

// Update Wasabi client calls to use server API
function WOO_FITROOM_upload_image_to_wasabi($image_path) {
    $response = wp_remote_post('http://yourserver.com/wp-json/tryontool/v1/wasabi/upload', array(
        'body' => array('image_path' => $image_path),
    ));
    return $response;
}

function WOO_FITROOM_delete_image_from_wasabi($image_key) {
    $response = wp_remote_post('http://yourserver.com/wp-json/tryontool/v1/wasabi/delete', array(
        'body' => array('image_key' => $image_key),
    ));
    return $response;
}

function WOO_FITROOM_list_images_from_wasabi($user_id) {
    $response = wp_remote_post('http://yourserver.com/wp-json/tryontool/v1/wasabi/list', array(
        'body' => array('user_id' => $user_id),
    ));

    if (is_wp_error($response)) {
        error_log('Error fetching images: ' . $response->get_error_message());
        return new WP_Error('request_failed', 'Error fetching images', array('status' => 500));
    }

    $response_body = wp_remote_retrieve_body($response);
    $data = json_decode($response_body, true);

    if (isset($data['success']) && $data['success']) {
        return $data['images'];
    } else {
        error_log('Error fetching images: ' . $response_body);
        return new WP_Error('request_failed', 'Error fetching images', array('status' => 500));
    }
}

// Add custom cron schedule for every 10 minutes
add_filter('cron_schedules', function($schedules){
    if(!isset($schedules['ten_minutes'])){
        $schedules['ten_minutes'] = array(
            'interval' => 10 * MINUTE_IN_SECONDS,
            'display'  => 'Every 10 Minutes'
        );
    }
    return $schedules;
});

// Schedule the cron job for deleting expired images
add_action('init', function() {
    if (!wp_next_scheduled('WOO_FITROOM_delete_expired_images')) {
        wp_schedule_event(time(), 'ten_minutes', 'WOO_FITROOM_delete_expired_images');
    }
});

// Hook into the cron job
add_action('WOO_FITROOM_delete_expired_images', function() {
    global $wpdb;
    error_log('Checking for expired image transients...');
    $transients = $wpdb->get_results("SELECT option_name FROM $wpdb->options WHERE option_name LIKE '_transient_WOO_FITROOM_image_deletion_%'");
    foreach ($transients as $transient) {
        $transient_key = str_replace('_transient_', '', $transient->option_name);
        $data = get_transient($transient_key);
        if ($data) {
            $ts = $data['timestamp'] ?? ($data['upload_time'] ?? false);
            if ($ts && (time() - $ts) > YEAR_IN_SECONDS) {
                error_log('Deleting image with key: ' . ($data['key'] ?? ''));
                WooFITROOM_Wasabi::delete($data['key'] ?? '');
                delete_transient($transient_key);
            }
        } else {
            // Transient expired, nothing to clean
            delete_option($transient->option_name);
        }
    }
});

/* -------------------------------------------------------------------------
 * LOG-OUT BASED IMAGE PURGE FLOW
 * -------------------------------------------------------------------------
 * 1. When a user logs OUT we create a   WOO_FITROOM_pending_delete_{ID}
 *    transient with the current timestamp.
 * 2. If they log back in **before** the inactivity window we delete that
 *    transient â€“ their images are safe.
 * 3. A cron job (see below) checks these transients every 10 min and, for
 *    any older than the window, deletes **all** images for that account.
 */

add_action( 'wp_logout', 'WOO_FITROOM_mark_user_logout' );
function WOO_FITROOM_mark_user_logout() {
    $uid = get_current_user_id();
    if ( $uid ) {
        set_transient( 'WOO_FITROOM_pending_delete_' . $uid, time(), WOO_FITROOM_INACTIVITY_WINDOW + ( 10 * MINUTE_IN_SECONDS ) );
    }
}

add_action( 'wp_login', 'WOO_FITROOM_clear_pending_deletion', 10, 2 );
function WOO_FITROOM_clear_pending_deletion( $user_login, $wp_user ) {
    $uid = $wp_user->ID;
    delete_transient( 'WOO_FITROOM_pending_delete_' . $uid );

    // Record last-login timestamp
    $now_mysql = current_time( 'mysql' );
    update_user_meta( $uid, 'WOO_FITROOM_last_login', $now_mysql );

    // Update central consent registry (if any)
    $consents = get_option( 'WOO_FITROOM_consents', array() );
    if ( isset( $consents[ $uid ] ) ) {
        $consents[ $uid ]['last_login'] = $now_mysql;
    } else {
        $consents[ $uid ] = array(
            'user_id'          => $uid,
            'email'            => $wp_user->user_email,
            'consent_timestamp'=> '',
            'last_login'       => $now_mysql,
        );
    }
    update_option( 'WOO_FITROOM_consents', $consents, false );
}

// -------------------------------------------------------------------------
//  CRON: CLEAN UP IMAGES FOR INACTIVE USERS
// -------------------------------------------------------------------------

add_action( 'init', function () {
    if ( ! wp_next_scheduled( 'WOO_FITROOM_cleanup_inactive_users' ) ) {
        wp_schedule_event( time(), 'ten_minutes', 'WOO_FITROOM_cleanup_inactive_users' );
    }
} );

add_action( 'WOO_FITROOM_cleanup_inactive_users', function () {
    // Delegate to the static helper inside the Product Button class so that
    // the deletion logic lives in a single place.
    if ( class_exists( 'WooFitroomPreview_Product_Button' ) ) {
        WooFitroomPreview_Product_Button::check_and_delete_images();
    }
} );

/* -------------------------------------------------------------------------
 *  I18N: Load translations as early as possible and alias legacy domain
 * ------------------------------------------------------------------------- */

add_action( 'plugins_loaded', 'WOO_FITROOM_preview_load_textdomain', 0 );
function WOO_FITROOM_preview_load_textdomain() {
    load_plugin_textdomain(
        WOO_FITROOM_TEXTDOMAIN,
        false,
        dirname( plugin_basename( __FILE__ ) ) . '/languages/'
    );

    // Legacy compatibility: mirror strings to the old text-domain so that
    // any calls still using "woo-tryontool-preview" resolve correctly.
    global $l10n;
    if ( isset( $l10n[ WOO_FITROOM_TEXTDOMAIN ] ) ) {
        $l10n['woo-tryontool-preview'] = $l10n[ WOO_FITROOM_TEXTDOMAIN ];
    }
} 

/**
 * Maintenance: Admin endpoint to strip UTF-8 BOM from PHP files in this plugin
 * Usage: Visit /wp-admin/admin-post.php?action=fitroom_strip_bom while logged in as admin
 */
add_action( 'admin_post_fitroom_strip_bom', 'FITROOM_admin_strip_bom' );
function FITROOM_admin_strip_bom() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Forbidden', 403 );
    }

    $root     = plugin_dir_path( __FILE__ );
    $excluded = array( 'vendor' . DIRECTORY_SEPARATOR );
    $checked  = 0;
    $updated  = 0;
    $invalidated = 0;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ( $iterator as $file ) {
        $path = $file->getPathname();
        if ( pathinfo( $path, PATHINFO_EXTENSION ) !== 'php' ) { continue; }
        foreach ( $excluded as $ex ) {
            if ( strpos( $path, $root . $ex ) === 0 ) { continue 2; }
        }
        $checked++;

        $fh = @fopen( $path, 'rb' );
        if ( ! $fh ) { continue; }
        $bom = fread( $fh, 3 );
        $has_bom = ($bom === "\xEF\xBB\xBF");
        if ( $has_bom ) {
            $rest = stream_get_contents( $fh );
            fclose( $fh );
            if ( @file_put_contents( $path, $rest, LOCK_EX ) !== false ) {
                $updated++;
                // Invalidate PHP OPcache for this file so changes take effect immediately
                if ( function_exists( 'opcache_invalidate' ) ) {
                    try { if ( @opcache_invalidate( $path, true ) ) { $invalidated++; } } catch ( \Throwable $e ) {}
                }
                clearstatcache(true, $path);
            }
        } else {
            fclose( $fh );
        }
    }

    wp_die( sprintf( 'Checked: %d, Stripped BOM: %d, OPcache invalidated: %d', $checked, $updated, $invalidated ) );
}
