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
 * Settings class for WooCommerce TryOnTool Preview
 */
if (!class_exists('WooFitroomPreview_Settings')) {
    class WooFitroomPreview_Settings {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Set default consent value only once when plugin is first activated
        add_action('init', array($this, 'set_consent_default_once'));
        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        // Register AJAX handlers for license validation
        add_action('wp_ajax_woo_fitroom_validate_license', array($this, 'ajax_validate_license_key'));
        // FREE PLAN TOPUP - AJAX handler for marking free plan as used
        add_action('wp_ajax_WOO_FITROOM_mark_free_plan_used', array($this, 'ajax_mark_free_plan_used'));
        // FREE PLAN TOPUP - AJAX handler for resetting free plan status
        add_action('wp_ajax_WOO_FITROOM_reset_free_plan', array($this, 'ajax_reset_free_plan'));
        // Consent records fetch (admin)
        add_action('wp_ajax_WOO_FITROOM_get_consents', array($this, 'ajax_get_consents'));
        // Consent export (admin)
        add_action('wp_ajax_WOO_FITROOM_export_consents', array($this, 'ajax_export_consents'));
        

        

    }

    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_preview_enabled',
            array(
                'type' => 'boolean',
                'default' => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            )
        );

        // Add License Key Setting
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_license_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );

        // API key option removed â€“ key is managed server-side on relay

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_daily_credits', // Keep if you want a *client-side* visual limit (doesn't enforce server-side)
            array(
                'type'              => 'integer',
                'default'           => 0,
                'sanitize_callback' => 'absint',
            )
        );
         // Keep other settings like logged_in_only, allowed_roles etc. if needed
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_logged_in_only',
            array(
                'type'              => 'boolean',
                'default'           => false,
                'sanitize_callback' => 'rest_sanitize_boolean',
            )
        );
        // ... other existing settings registrations ...
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_allowed_roles',
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_array_of_strings' ),
                'default'           => array(),
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_allowed_user_ids',
            array(
                'type'              => 'string', // comma-separated list stored as string
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_required_user_tag',
            array(
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => '',
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_require_extra_consents',
            array(
                'type' => 'boolean',
                'sanitize_callback' => array($this, 'sanitize_consent_setting'),
                'default' => 1,
            )
        );

        // FREE PLAN TOPUP - Track if free plan has been used
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_free_plan_used',
            array(
                'type' => 'boolean',
                'sanitize_callback' => function($val) { return $val ? 1 : 0; },
                'default' => 0,
            )
        );

        // APPEARANCE SETTINGS - Button color options
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_use_theme_colors',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_custom_button_color',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_hex_color',
                'default' => '#FF6E0E',
            )
        );

        // APPEARANCE SETTINGS - Button padding options
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_use_theme_padding',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_padding_top',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 12,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_padding_bottom',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 12,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_padding_left',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 20,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_padding_right',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 20,
            )
        );

        // APPEARANCE SETTINGS - Button border radius options
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_use_theme_border_radius',
            array(
                'type' => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default' => true,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_border_radius_top_left',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 50,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_border_radius_top_right',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 50,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_border_radius_bottom_left',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 50,
            )
        );

        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_border_radius_bottom_right',
            array(
                'type' => 'integer',
                'sanitize_callback' => 'absint',
                'default' => 50,
            )
        );

        // APPEARANCE SETTINGS - Button text mode
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_button_text_mode',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_button_text_mode'),
                'default' => 'default',
            )
        );

        // APPEARANCE SETTINGS - Custom button text
        register_setting(
            'WOO_FITROOM_preview_options',
            'WOO_FITROOM_custom_button_text',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_button_text'),
                'default' => 'Try It On',
            )
        );
    }

    /**
     * Add settings page to admin menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Try-On Tool Settings', 'woo-fitroom-preview'),
            __('Try-On Tool', 'woo-fitroom-preview'),
            'manage_options',
            'woo-fitroom-preview',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render the settings page
     */
    public function render_settings_page() {
        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'templates/admin/settings-page.php';
    }

    // Function to call license validation endpoint
    public function ajax_validate_license_key() {
        check_ajax_referer( 'fitroom_validate_license_nonce', 'nonce' );

        $license_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        $site_url = home_url(); // Get current site URL

        if ( empty($license_key) ) {
            wp_send_json_error( array( 'message' => __('Please enter a license key.', 'woo-fitroom-preview') ) );
        }

        $request_args = array(
            'method' => 'POST',
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body' => wp_json_encode( array(
                'license_key' => $license_key,
                'site_url'    => $site_url,
             ) ),
            'timeout' => 30,
        );

        $response = wp_remote_post( defined('FITROOM_VALIDATE_ENDPOINT') ? FITROOM_VALIDATE_ENDPOINT : FITROOM_VALIDATE_ENDPOINT, $request_args );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => __('Error contacting validation server: ', 'woo-fitroom-preview') . $response->get_error_message() ) );
        }

        $response_code = wp_remote_retrieve_response_code( $response );
        $response_body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $response_code === 200 && isset($response_body['success']) && $response_body['success'] ) {
             // Optionally store validation status/info
             update_option('WOO_FITROOM_license_status', 'valid');
             update_option('WOO_FITROOM_license_expires', isset($response_body['expires']) ? $response_body['expires'] : '');
             update_option('WOO_FITROOM_license_credits', isset($response_body['credits']) ? $response_body['credits'] : '');
             update_option('WOO_FITROOM_plan_product_id', isset($response_body['plan_product_id']) ? $response_body['plan_product_id'] : '');

             wp_send_json_success( array(
                 'message' => __('License key is valid and active!', 'woo-fitroom-preview'),
                 'credits' => isset($response_body['credits']) ? $response_body['credits'] : 'N/A',
                 'expires' => isset($response_body['expires']) ? $response_body['expires'] : 'N/A',
                 'plan_product_id' => isset($response_body['plan_product_id']) ? $response_body['plan_product_id'] : null,
             ) );
        } else {
             update_option('WOO_FITROOM_license_status', 'invalid');
             update_option('WOO_FITROOM_license_expires', '');
             update_option('WOO_FITROOM_license_credits', '');
             update_option('WOO_FITROOM_plan_product_id', '');

             $error_message = __('License validation failed.', 'woo-fitroom-preview');
             if ( isset($response_body['message']) ) {
                 $error_message = $response_body['message'];
             } elseif( isset($response_body['code']) ) { // Use code from WP_Error response
                 $error_message .= ' (' . $response_body['code'] . ')';
             }
            wp_send_json_error( array( 'message' => $error_message ) );
        }
    }

    public function verify_settings() {
        error_log('WooTryOnTool Plugin Settings:');
        error_log('Enabled: ' . (get_option('WOO_FITROOM_preview_enabled') ? 'Yes' : 'No'));
    }

    public function sanitize_array_of_strings( $input ) {
        if ( is_array( $input ) ) {
            return array_map( 'sanitize_text_field', $input );
        }
        return array();
    }
    
    /**
     * Sanitize consent setting
     */
    public function sanitize_consent_setting( $input ) {
        // If the input is explicitly set to 0 or false, return 0
        if ( $input === 0 || $input === '0' || $input === false || $input === 'false' ) {
            return 0;
        }
        
        // If the input is truthy, return 1
        if ( $input ) {
            return 1;
        }
        
        // Default to 0 for any other case
        return 0;
    }

    /**
     * Sanitize button text mode setting
     */
    public function sanitize_button_text_mode( $input ) {
        $allowed_values = array( 'default', 'custom' );
        $sanitized = sanitize_text_field( $input );
        
        if ( in_array( $sanitized, $allowed_values ) ) {
            return $sanitized;
        }
        
        return 'default';
    }

    /**
     * Sanitize button text setting
     */
    public function sanitize_button_text( $input ) {
        // Sanitize the text
        $sanitized = sanitize_text_field( $input );
        
        // Limit to 15 characters
        if ( strlen( $sanitized ) > 15 ) {
            $sanitized = substr( $sanitized, 0, 15 );
        }
        
        // If empty after sanitization, return default
        if ( empty( $sanitized ) ) {
            return 'Try It On';
        }
        
        return $sanitized;
    }

    /**
     * Set default consent value only once when plugin is first activated
     */
    public function set_consent_default_once() {
        // Check if we've already set the default value
        if (get_option('WOO_FITROOM_consent_default_initialized') === true) {
            return; // Already initialized, don't run again
        }
        
        // Get the current value of the consent option
        $current_value = get_option('WOO_FITROOM_require_extra_consents');
        
        // Only set default if the option doesn't exist at all
        if ($current_value === false) {
            update_option('WOO_FITROOM_require_extra_consents', 1);
            error_log('Try-On Tool: Consent setting initialized to checked by default.');
        }
        
        // Mark that we've initialized the default value
        update_option('WOO_FITROOM_consent_default_initialized', true);
        
        // Migrate existing consent records to include new fields
        $this->migrate_consent_records();
    }

    /**
     * Migrate existing consent records to include new fields
     */
    public function migrate_consent_records() {
        $consents = get_option('WOO_FITROOM_consents', array());
        $updated = false;
        
        foreach ($consents as $user_id => &$consent_data) {
            // Add missing fields if they don't exist
            if (!isset($consent_data['terms_consent'])) {
                $consent_data['terms_consent'] = get_user_meta($user_id, 'woo_fitroom_terms_consent', true) ?: '';
                $updated = true;
            }
            if (!isset($consent_data['refund_consent'])) {
                $consent_data['refund_consent'] = get_user_meta($user_id, 'woo_fitroom_refund_consent', true) ?: '';
                $updated = true;
            }
            if (!isset($consent_data['last_login'])) {
                $consent_data['last_login'] = get_user_meta($user_id, 'last_login', true) ?: '';
                $updated = true;
            }
            
            // Update consolidated consent date if needed
            if (isset($consent_data['consent_timestamp'])) {
                $consent_dates = array_filter([
                    $consent_data['consent_timestamp'],
                    $consent_data['terms_consent'],
                    $consent_data['refund_consent']
                ]);
                if (!empty($consent_dates)) {
                    $new_consolidated_date = max($consent_dates);
                    if ($consent_data['consent_timestamp'] !== $new_consolidated_date) {
                        $consent_data['consent_timestamp'] = $new_consolidated_date;
                        $updated = true;
                    }
                }
            }
        }
        
        if ($updated) {
            update_option('WOO_FITROOM_consents', $consents, false);
        }
    }
    
    /**
     * AJAX: Return consent records for admin table
     */
    public function ajax_get_consents() {
        check_ajax_referer( 'FITROOM_get_consents', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-fitroom-preview' ) ), 403 );
        }

        $consents = get_option( 'WOO_FITROOM_consents', array() );

        // Return as numerically-indexed array for easier JS loop
        $out = array_values( $consents );
        wp_send_json_success( $out );
    }

    /**
     * AJAX: Export consent records as Excel/CSV file
     */
    public function ajax_export_consents() {
        check_ajax_referer( 'FITROOM_export_consents', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Permission denied.', 'woo-fitroom-preview' ) );
        }

        $consents = get_option( 'WOO_FITROOM_consents', array() );
        
        // Set headers for file download
        $filename = 'tryon-consent-records-' . date('Y-m-d-H-i-s') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Create output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel compatibility
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // CSV headers
        $headers = array(
            'User ID',
            'Email',
            'Consent Given',
            'Last Login'
        );
        fputcsv($output, $headers);

        // Process each consent record
        foreach ($consents as $user_id => $consent_data) {
            $user_id = intval($user_id);
            $email = isset($consent_data['email']) ? $consent_data['email'] : '';
            $consent_date = isset($consent_data['consent_timestamp']) ? $consent_data['consent_timestamp'] : '';
            $last_login = isset($consent_data['last_login']) ? $consent_data['last_login'] : '';

            // Format dates for better readability
            $consent_formatted = $consent_date ? date('Y-m-d H:i:s', strtotime($consent_date)) : 'Not Given';
            $last_login_formatted = $last_login ? date('Y-m-d H:i:s', strtotime($last_login)) : 'Never';

            $row = array(
                $user_id,
                $email,
                $consent_formatted,
                $last_login_formatted
            );
            
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * FREE PLAN TOPUP - AJAX: Mark free plan as used
     */
    public function ajax_mark_free_plan_used() {
        check_ajax_referer( 'FITROOM_mark_free_plan_used', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-fitroom-preview' ) ), 403 );
        }

        // FREE PLAN TOPUP - Mark free plan as used
        update_option( 'WOO_FITROOM_free_plan_used', true );

        wp_send_json_success( array( 
            'message' => __( 'Free plan marked as used.', 'woo-fitroom-preview' ),
            'redirect_url' => 'https://tryontool.com/checkout/?add-to-cart=' . (defined('FITROOM_PLAN_FREE_PRODUCT_ID') ? FITROOM_PLAN_FREE_PRODUCT_ID : 5961)
        ) );
    }

    /**
     * FREE PLAN TOPUP - AJAX: Reset free plan status
     */
    public function ajax_reset_free_plan() {
        check_ajax_referer( 'FITROOM_reset_free_plan', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'woo-fitroom-preview' ) ), 403 );
        }

        // FREE PLAN TOPUP - Reset free plan status
        delete_option( 'WOO_FITROOM_free_plan_used' );
        wp_send_json_success( array( 'message' => __( 'Free plan status reset.', 'woo-fitroom-preview' ) ) );
    }
    }
}

