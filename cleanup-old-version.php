<?php
/**
 * Cleanup script for Try-On Tool Plugin
 * 
 * This script helps clean up old version files that might cause conflicts
 * when updating to version 1.2.2
 * 
 * Usage: Upload this file to your WordPress root directory and run it once
 * Then delete this file for security
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    // Try to load WordPress
    $wp_load_paths = [
        './wp-load.php',
        '../wp-load.php',
        '../../wp-load.php',
        '../../../wp-load.php'
    ];
    
    $wp_loaded = false;
    foreach ($wp_load_paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if (!$wp_loaded) {
        die('WordPress not found. Please place this file in your WordPress root directory.');
    }
}

// Check if user has admin privileges
if (!current_user_can('manage_options')) {
    die('Access denied. You must be an administrator to run this script.');
}

echo "<h1>Try-On Tool Plugin Cleanup Script</h1>";
echo "<p>This script will clean up old version files that might cause conflicts.</p>";

// Get all plugins
$plugins = get_plugins();
$tryon_plugins = [];

foreach ($plugins as $plugin_file => $plugin_data) {
    if (strpos($plugin_file, 'woo-fitroom-preview') !== false || 
        strpos($plugin_data['Name'], 'Try-On Tool') !== false) {
        $tryon_plugins[$plugin_file] = $plugin_data;
    }
}

echo "<h2>Found Try-On Tool Plugin(s):</h2>";
foreach ($tryon_plugins as $plugin_file => $plugin_data) {
    $status = is_plugin_active($plugin_file) ? 'Active' : 'Inactive';
    echo "<p><strong>{$plugin_data['Name']}</strong> - Version: {$plugin_data['Version']} - Status: {$status}</p>";
}

// Check for multiple versions
if (count($tryon_plugins) > 1) {
    echo "<h2>Multiple Versions Detected!</h2>";
    echo "<p>This is likely causing the fatal error. Here's what to do:</p>";
    echo "<ol>";
    echo "<li>Deactivate ALL Try-On Tool plugins</li>";
    echo "<li>Delete ALL Try-On Tool plugins</li>";
    echo "<li>Upload and activate only version 1.2.2</li>";
    echo "</ol>";
    
    echo "<h3>Plugin Files to Check:</h3>";
    foreach ($tryon_plugins as $plugin_file => $plugin_data) {
        $plugin_path = WP_PLUGIN_DIR . '/' . dirname($plugin_file);
        echo "<p><strong>{$plugin_data['Name']} v{$plugin_data['Version']}:</strong> {$plugin_path}</p>";
    }
} else {
    echo "<h2>Single Version Found</h2>";
    $plugin_file = array_keys($tryon_plugins)[0];
    $plugin_data = $tryon_plugins[$plugin_file];
    
    if ($plugin_data['Version'] === '1.2.2') {
        echo "<p>✅ You have the correct version (1.2.2) installed.</p>";
    } else {
        echo "<p>⚠️ You have version {$plugin_data['Version']} installed. Please update to version 1.2.2.</p>";
    }
}

// Check for conflicting constants
echo "<h2>Constant Check:</h2>";
$constants_to_check = [
    'WOO_FITROOM_PREVIEW_VERSION',
    'WOO_FITROOM_PREVIEW_PLUGIN_DIR',
    'WOO_FITROOM_PREVIEW_PLUGIN_URL',
    'FITROOM_RELAY_ENDPOINT',
    'FITROOM_VALIDATE_ENDPOINT'
];

foreach ($constants_to_check as $constant) {
    if (defined($constant)) {
        $value = constant($constant);
        echo "<p>✅ {$constant}: " . esc_html($value) . "</p>";
    } else {
        echo "<p>❌ {$constant}: Not defined</p>";
    }
}

// Check for conflicting classes
echo "<h2>Class Check:</h2>";
$classes_to_check = [
    'WooFITROOM_Wasabi',
    'WooFitroomPreview_Settings',
    'WooFitroomPreview_Product_Button',
    'WooFitroomPreview_Shortcode',
    'WooFitroomPreview_API_Handler'
];

foreach ($classes_to_check as $class) {
    if (class_exists($class)) {
        echo "<p>✅ {$class}: Exists</p>";
    } else {
        echo "<p>❌ {$class}: Not found</p>";
    }
}

// Cleanup options
echo "<h2>Cleanup Options:</h2>";
echo "<p>If you're still experiencing issues, try these cleanup options:</p>";

if (isset($_POST['cleanup_cache'])) {
    wp_cache_flush();
    echo "<p>✅ Cache cleared</p>";
}

if (isset($_POST['cleanup_transients'])) {
    global $wpdb;
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_woo_fitroom_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_woo_fitroom_%'");
    echo "<p>✅ Transients cleared</p>";
}

if (isset($_POST['cleanup_options'])) {
    $options_to_clean = [
        'woo_fitroom_preview_activated',
        'woo_fitroom_preview_version',
        'woo_fitroom_preview_previous_version',
        'woo_fitroom_preview_updated_from',
        'woo_fitroom_preview_updated_to'
    ];
    
    foreach ($options_to_clean as $option) {
        delete_option($option);
    }
    echo "<p>✅ Plugin options cleared</p>";
}

echo "<form method='post'>";
echo "<p><input type='submit' name='cleanup_cache' value='Clear Cache' /></p>";
echo "<p><input type='submit' name='cleanup_transients' value='Clear Transients' /></p>";
echo "<p><input type='submit' name='cleanup_options' value='Clear Plugin Options' /></p>";
echo "</form>";

echo "<h2>Manual Cleanup Instructions:</h2>";
echo "<ol>";
echo "<li>Go to WordPress Admin → Plugins</li>";
echo "<li>Deactivate ALL Try-On Tool plugins</li>";
echo "<li>Delete ALL Try-On Tool plugins</li>";
echo "<li>Upload version 1.2.2 zip file</li>";
echo "<li>Activate the new version</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> Delete this cleanup script after use for security.</p>";
?>
