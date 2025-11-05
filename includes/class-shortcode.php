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
 * Test shortcode for TryOnTool API integration
 */
if (!class_exists('WooFitroomPreview_Shortcode')) {
    class WooFitroomPreview_Shortcode {
    /**
     * Initialize the class
     */
    public function __construct() {
        add_shortcode('WOO_FITROOM_test', array($this, 'render_test_form'));
        add_action('wp_ajax_WOO_FITROOM_test_api', array($this, 'handle_test_submission'));
        add_action('wp_ajax_nopriv_WOO_FITROOM_test_api', array($this, 'handle_test_submission'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Enqueue necessary scripts and styles for the shortcode
     */
    public function enqueue_scripts() {
        // Check if the shortcode is active before enqueueing?
        wp_enqueue_style(
            'woo-fitroom-preview-style',
            WOO_FITROOM_PREVIEW_PLUGIN_URL . 'assets/css/woo-fitroom-preview.css',
            array(),
            WOO_FITROOM_PREVIEW_VERSION
        );

        wp_enqueue_script(
            'woo-fitroom-preview-script',
            WOO_FITROOM_PREVIEW_PLUGIN_URL . 'assets/js/woo-fitroom-preview.js',
            array( 'jquery', 'wp-i18n' ),
            WOO_FITROOM_PREVIEW_VERSION,
            true
        );

        if ( function_exists( 'wp_set_script_translations' ) ) {
            wp_set_script_translations( 'woo-fitroom-preview-script', WOO_FITROOM_TEXTDOMAIN, WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'languages' );
        }

        wp_localize_script(
            'woo-fitroom-preview-script',
            'WooFitroomPreview',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('WOO_FITROOM_preview_nonce'),
            )
        );
    }

    /**
     * Render the test form shortcode
     */
    public function render_test_form() {
        // Check if plugin is enabled
        if (!get_option('WOO_FITROOM_preview_enabled')) {
            return '<p>' . __('Try-On Tool Preview is not available.', 'woo-fitroom-preview') . '</p>';
        }

        ob_start();
        ?>
        <div class="woo-fitroom-preview-test-form">
            <h3><?php _e('Test Try-On Tool API Integration', 'woo-fitroom-preview'); ?></h3>

            <form id="woo-fitroom-test-form" enctype="multipart/form-data">
                <div class="form-field">
                    <label for="user_image"><?php _e('Upload Your Image:', 'woo-fitroom-preview'); ?></label>
                    <input type="file" id="user_image" name="user_image" accept="image/jpeg,image/png" required>
                </div>

                <div class="form-field">
                    <label for="product_image_url"><?php _e('Product Image URL:', 'woo-fitroom-preview'); ?></label>
                    <input type="url" id="product_image_url" name="product_image_url" required>
                </div>

                <div class="form-submit">
                    <button type="submit" class="button"><?php _e('Generate Preview', 'woo-fitroom-preview'); ?></button>
                </div>
            </form>

            <div id="woo-fitroom-result" style="display: none;">
                <h4><?php _e('Result:', 'woo-fitroom-preview'); ?></h4>
                <div class="result-content"></div>
            </div>

            <div id="woo-fitroom-error" style="display: none;">
                <h4><?php _e('Error:', 'woo-fitroom-preview'); ?></h4>
                <div class="error-content"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle form submission
     */
    public function handle_test_submission() {
        // Check nonce for security
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'WOO_FITROOM_preview_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'woo-fitroom-preview')));
        }

        // Check if plugin is enabled
        if (!get_option('WOO_FITROOM_preview_enabled')) {
            wp_send_json_error(array('message' => __('Try-On Tool Preview is currently disabled', 'woo-fitroom-preview')));
        }

        // Check for file upload
        if (!isset($_FILES['user_image']) || $_FILES['user_image']['error'] !== UPLOAD_ERR_OK) {
            wp_send_json_error(array('message' => __('User image upload failed', 'woo-fitroom-preview')));
        }

        // Check for product image URL
        if (empty($_POST['product_image_url'])) {
            wp_send_json_error(array('message' => __('Product image URL is required', 'woo-fitroom-preview')));
        }

        // Validate and store uploaded file
        $uploaded_file = $_FILES['user_image'];
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/woo-fitroom-temp';

        // Create temp directory if not exists
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }

        // Generate unique filename
        $filename = wp_unique_filename($temp_dir, $uploaded_file['name']);
        $file_path = $temp_dir . '/' . $filename;

        // Move uploaded file to temp directory
        if (!move_uploaded_file($uploaded_file['tmp_name'], $file_path)) {
            wp_send_json_error(array('message' => __('Failed to save uploaded file', 'woo-tryontool-preview')));
        }

        // Always convert the uploaded file to JPEG and use the JPEG path
        $jpeg_path = $temp_dir . '/' . wp_unique_filename($temp_dir, pathinfo($filename, PATHINFO_FILENAME) . '.jpg');
        $img_type = @exif_imagetype($file_path);
        $src = false;
        switch ($img_type) {
            case IMAGETYPE_JPEG:
                // Already JPEG – just copy to ensure .jpg extension
                @copy($file_path, $jpeg_path);
                break;
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($file_path);
                break;
            case IMAGETYPE_GIF:
                $src = @imagecreatefromgif($file_path);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($file_path);
                }
                break;
            default:
                // Fallback: try to create from raw
                $raw = @file_get_contents($file_path);
                if ($raw !== false) { $src = @imagecreatefromstring($raw); }
                break;
        }
        if ($src) {
            // Preserve appearance for formats with alpha by compositing on white
            $w = imagesx($src); $h = imagesy($src);
            $canvas = imagecreatetruecolor($w, $h);
            $white = imagecolorallocate($canvas, 255, 255, 255);
            imagefilledrectangle($canvas, 0, 0, $w, $h, $white);
            imagecopy($canvas, $src, 0, 0, 0, 0, $w, $h);
            imagejpeg($canvas, $jpeg_path, 90);
            imagedestroy($canvas);
            imagedestroy($src);
        }
        // Prefer the JPEG path if it exists; otherwise stick with original
        $final_path = (file_exists($jpeg_path)) ? $jpeg_path : $file_path;

        // Get product image URL
        $product_image_url = esc_url_raw($_POST['product_image_url']);

        // Call the API handler
        $api_handler = new WooFitroomPreview_API_Handler(); // Updated class name
        $response = $api_handler->generate_preview($final_path, $product_image_url, '', array()); // Pass empty array for options

        // Delete temp file regardless of result
        @unlink($file_path);
        if (isset($jpeg_path) && $jpeg_path !== $file_path) { @unlink($jpeg_path); }

        // Handle response
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('Try On Tool API Error: ', 'woo-fitroom-preview') . $response->get_error_message(),
                'details' => $response->get_error_data(),
            ));
        }

        // Process successful response
        wp_send_json_success(array(
            'message' => __('Successfully generated preview', 'woo-tryontool-preview'),
            'data' => $response,
        ));
    }
    }
}

