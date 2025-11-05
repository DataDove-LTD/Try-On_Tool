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
        
        // Analytics dashboard AJAX handlers
        add_action('wp_ajax_WOO_FITROOM_get_analytics_data', array($this, 'ajax_get_analytics_data'));
        add_action('wp_ajax_WOO_FITROOM_refresh_analytics', array($this, 'ajax_refresh_analytics'));
        add_action('wp_ajax_WOO_FITROOM_test_tracking', array($this, 'ajax_test_tracking'));
        add_action('wp_ajax_WOO_FITROOM_check_database', array($this, 'ajax_check_database'));
        add_action('wp_ajax_WOO_FITROOM_check_consents', array($this, 'ajax_check_consents'));
        add_action('wp_ajax_WOO_FITROOM_check_active_users', array($this, 'ajax_check_active_users'));
        
        // Categories fetch (admin)
        add_action('wp_ajax_WOO_FITROOM_get_categories', array($this, 'ajax_get_categories'));
        
        // Categories save (admin)
        add_action('wp_ajax_WOO_FITROOM_save_categories', array($this, 'ajax_save_categories'));
        
        // Register REST API endpoints for category management
        add_action('rest_api_init', array($this, 'register_category_endpoints'));
        
        // Auto-assign categories automatically in various scenarios
        add_action('admin_init', array($this, 'maybe_auto_assign_categories'));
        add_action('woocommerce_init', array($this, 'maybe_auto_assign_categories'));
        add_action('wp_loaded', array($this, 'maybe_auto_assign_categories'));
        
        // Auto-assign new categories when they are created
        add_action('created_product_cat', array($this, 'auto_assign_new_category'));
        
        // Auto-assign when categories are accessed in admin
        add_action('admin_head', array($this, 'maybe_auto_assign_on_admin_access'));
        
        // Initialize analytics tracking
        add_action('init', array($this, 'init_analytics_tracking'));
    }

    // (OpenAI key registration is handled in the main register_settings below)

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

        // OpenAI API key for server-side AI suggestions
        register_setting(
            'WOO_FITROOM_preview_options',
            'fitroom_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => '',
            )
        );

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

    /**
     * Register REST API endpoints for category management
     */
    public function register_category_endpoints() {
        // Get categories with assignments
        register_rest_route( 'fitroom/v1', '/categories', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_categories' ),
            'permission_callback' => '__return_true',
        ));
        register_rest_route( 'FitRoom/v1', '/categories', array(
            'methods'             => 'GET',
            'callback'            => array( $this, 'rest_get_categories' ),
            'permission_callback' => '__return_true',
        ));

        // Save category assignment
        register_rest_route( 'fitroom/v1', '/category-assignment', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_save_assignment' ),
            'permission_callback' => '__return_true',
        ));
        register_rest_route( 'FitRoom/v1', '/category-assignment', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_save_assignment' ),
            'permission_callback' => '__return_true',
        ));

        // Auto-assign categories
        register_rest_route( 'fitroom/v1', '/categories/auto-assign', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_auto_assign' ),
            'permission_callback' => '__return_true',
        ));
        register_rest_route( 'FitRoom/v1', '/categories/auto-assign', array(
            'methods'             => 'POST',
            'callback'            => array( $this, 'rest_auto_assign' ),
            'permission_callback' => '__return_true',
        ));
    }

    /**
     * REST: Get all WooCommerce categories with current assignments
     */
    public function rest_get_categories( WP_REST_Request $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'permission_denied', 'Permission denied', array( 'status' => 403 ) );
        }
        
        if ( ! class_exists( 'WooCommerce' ) ) {
            return new WP_Error( 'wc_missing', 'WooCommerce not active', array( 'status' => 500 ) );
        }

        // Auto-assign any unassigned categories before returning the list
        $this->run_auto_assignment();
        
        $this->create_assignments_table();
        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        
        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        $out = array();
        
        foreach ( $terms as $term ) {
            $assignment = $wpdb->get_var( $wpdb->prepare(
                "SELECT assignment FROM {$table} WHERE category_id = %d",
                $term->term_id
            ) );
            
            $out[] = array(
                'id'                 => (int) $term->term_id,
                'name'               => $term->name,
                'slug'               => $term->slug,
                'product_count'      => (int) $term->count,
                'current_assignment' => $assignment ? $assignment : null,
            );
        }

        return new WP_REST_Response( $out, 200 );
    }

    /**
     * REST: Save a single category assignment
     */
    public function rest_save_assignment( WP_REST_Request $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'permission_denied', 'Permission denied', array( 'status' => 403 ) );
        }
        
        global $wpdb;
        $this->create_assignments_table();
        
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        $params = $request->get_json_params();
        
        $category_id   = isset( $params['category_id'] ) ? intval( $params['category_id'] ) : 0;
        $category_name = isset( $params['category_name'] ) ? sanitize_text_field( $params['category_name'] ) : '';
        $assignment    = isset( $params['assignment'] ) ? sanitize_text_field( $params['assignment'] ) : '';

        if ( ! in_array( $assignment, array( 'top', 'bottom', 'full', 'none' ), true ) ) {
            return new WP_Error( 'invalid_assignment', 'Assignment must be top, bottom, full, or none', array( 'status' => 400 ) );
        }

        if ( $category_id <= 0 || empty( $category_name ) ) {
            return new WP_Error( 'missing_params', 'category_id and category_name required', array( 'status' => 400 ) );
        }

        $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE category_id = %d", $category_id ) );
        
        if ( $existing ) {
            $wpdb->update(
                $table,
                array( 'category_name' => $category_name, 'assignment' => $assignment, 'updated_at' => current_time( 'mysql' ) ),
                array( 'id' => $existing ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
        } else {
            $wpdb->insert(
                $table,
                array( 'category_id' => $category_id, 'category_name' => $category_name, 'assignment' => $assignment, 'updated_at' => current_time( 'mysql' ) ),
                array( '%d', '%s', '%s', '%s' )
            );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * REST: Auto-assign all categories using OpenAI or keyword fallback
     */
    public function rest_auto_assign( WP_REST_Request $request ) {
        if ( ! current_user_can( 'manage_options' ) ) {
            return new WP_Error( 'permission_denied', 'Permission denied', array( 'status' => 403 ) );
        }
        
        $this->create_assignments_table();
        
        $openai_key = get_option( 'fitroom_openai_api_key', '' );
        $use_openai = ! empty( $openai_key );

        $terms = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
        
        if ( is_wp_error( $terms ) ) {
            return $terms;
        }

        $results = array();
        
        foreach ( $terms as $term ) {
            $suggestion = null;
            
            // Try OpenAI if key is configured
            if ( $use_openai ) {
                $suggestion = $this->get_openai_suggestion( $term->name, $openai_key );
            }
            
            // Fallback to keyword-based
            if ( ! $suggestion ) {
                $suggestion = $this->get_keyword_suggestion( $term->name );
            }

            if ( $suggestion && in_array( $suggestion, array( 'top', 'bottom', 'full', 'none' ), true ) ) {
                global $wpdb;
                $table = $wpdb->prefix . 'fitroom_category_assignments';
                
                $existing = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE category_id = %d", $term->term_id ) );
                
                if ( $existing ) {
                    $wpdb->update(
                        $table,
                        array( 'category_name' => $term->name, 'assignment' => $suggestion, 'updated_at' => current_time( 'mysql' ) ),
                        array( 'id' => $existing ),
                        array( '%s', '%s', '%s' ),
                        array( '%d' )
                    );
                } else {
                    $wpdb->insert(
                        $table,
                        array( 'category_id' => $term->term_id, 'category_name' => $term->name, 'assignment' => $suggestion, 'updated_at' => current_time( 'mysql' ) ),
                        array( '%d', '%s', '%s', '%s' )
                    );
                }
                
                $results[] = array( 'id' => (int) $term->term_id, 'name' => $term->name, 'assignment' => $suggestion );
            }
        }

        return new WP_REST_Response( array( 'success' => true, 'assigned' => $results ), 200 );
    }

    /**
     * Get OpenAI suggestion for category
     */
    private function get_openai_suggestion( $category_name, $api_key ) {
        $prompt = 'Classify this product category into one of: top, bottom, full. Return only one word. Category: ' . $category_name;
        
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'model'       => 'gpt-3.5-turbo',
                'messages'    => array( array( 'role' => 'user', 'content' => $prompt ) ),
                'max_tokens'  => 5,
                'temperature' => 0.1,
            ) ),
            'timeout' => 30,
        ) );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $code = wp_remote_retrieve_response_code( $response );
        $data = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $code === 200 && isset( $data['choices'][0]['message']['content'] ) ) {
            $answer = strtolower( trim( $data['choices'][0]['message']['content'] ) );
            $answer = preg_replace( '/[^a-z]/', '', $answer );
            
            if ( in_array( $answer, array( 'top', 'bottom', 'full', 'none' ), true ) ) {
                return $answer;
            }
        }

        return null;
    }

    /**
     * Get keyword-based suggestion for category
     */
    private function get_keyword_suggestion( $category_name ) {
        $name_lower = strtolower( trim( $category_name ) );
        
        // PRIORITY 1: Check FULL OUTFIT items FIRST (multi-word combinations)
        // This must come first to catch "shalwar kameez" before "shalwar" alone
        $full_multiword_keywords = array(
            'shalwar kameez', 'salwar kameez', 'shalwar qameez', 'salwar qameez',
            'kurta pajama', 'kurta pyjama', 'kurta set',
            'sherwani set',
            'three piece', 'three-piece', '3 piece', '3-piece',
            'two piece', 'two-piece', '2 piece', '2-piece',
            'one piece', 'one-piece', '1 piece', '1-piece',
            'co-ord set', 'coord set', 'co ord set',
            'full outfit', 'full-outfit', 'full body', 'full-body',
            'complete outfit', 'complete set',
            'suit set', 'tracksuit', 'track suit'
        );
        
        foreach ( $full_multiword_keywords as $keyword ) {
            if ( stripos( $name_lower, $keyword ) !== false ) {
                return 'full';
            }
        }
        
        // PRIORITY 2: Check single-word FULL OUTFIT items
        $full_keywords = array(
            // Dresses and Gowns
            'dress', 'dresses', 'gown', 'frock',
            // One-piece items
            'jumpsuit', 'romper', 'overall', 'overalls', 'dungaree', 'onesie', 'playsuit', 'coverall', 'boilersuit',
            // Traditional full outfits
            'abaya', 'kaftan', 'kimono', 'sarong', 'poncho',
            'sherwani', 'thobe', 'dishdasha', 'jalabiya', 'djellaba',
            'saree', 'sari', 'lehenga', 'ghagra', 'anarkali',
            'hanbok', 'cheongsam', 'qipao', 'ao dai',
            'burqa', 'burka', 'niqab', 'chador',
            // Nightwear full
            'negligee', 'nightgown', 'nightdress',
            // Suits (unless it's "suit jacket" or "pant suit")
            'tuxedo', 'gown'
        );
        
        foreach ( $full_keywords as $keyword ) {
            if ( stripos( $name_lower, $keyword ) !== false ) {
                return 'full';
            }
        }
        
        // PRIORITY 3: TOP items - upper body clothing
        $top_keywords = array(
            // Jackets and Coats
            'jacket', 'coat', 'blazer', 'parka', 'anorak', 'windbreaker', 'bomber', 'denim jacket',
            // Cardigans and Sweaters
            'cardigan', 'sweater', 'pullover', 'sweatshirt', 'fleece', 'jumper',
            // Shirts and Tops
            'shirt', 'blouse', 'tunic',
            'polo', 'henley',
            // T-shirts and Tanks
            'tee', 't-shirt', 'tank top', 'tank', 'cami', 'camisole', 'singlet',
            // Hoodies
            'hoodie', 'hoody',
            // Vests
            'vest', 'waistcoat', 'gilet',
            // Crop and special
            'crop top', 'crop', 'halter', 'tube top',
            'bralette', 'bustier', 'corset', 'bodice',
            // Traditional upper wear (standalone)
            'kurti', 'choli',
            // Wraps and Shawls
            'shrug', 'bolero', 'cape', 'stole', 'shawl', 'wrap', 'pashmina'
        );
        
        foreach ( $top_keywords as $keyword ) {
            if ( stripos( $name_lower, $keyword ) !== false ) {
                return 'top';
            }
        }
        
        // PRIORITY 4: BOTTOM items - lower body clothing
        $bottom_keywords = array(
            // Jeans and Denim
            'jeans', 'denim pant', 'denim trouser',
            // Pants and Trousers
            'pant', 'pants', 'trouser', 'trousers', 'slacks', 'chinos', 'khaki',
            // Shorts
            'shorts', 'bermuda', 'cargo short',
            // Skirts
            'skirt', 'mini skirt', 'midi skirt', 'pencil skirt', 'pleated skirt', 'a-line skirt',
            // Leggings and Tights
            'legging', 'leggings', 'tights', 'jegging', 'tregging',
            // Bottom category
            'bottom', 'bottoms', 'lower',
            // Casual and Athletic
            'jogger', 'joggers', 'sweatpants', 'trackpants', 'athletic pant',
            // Traditional lower wear (standalone only, not part of full outfit)
            'churidar', 'dhoti', 'lungi',
            // Other bottoms
            'capri', 'culottes', 'palazzo', 'harem pant'
        );
        
        foreach ( $bottom_keywords as $keyword ) {
            if ( stripos( $name_lower, $keyword ) !== false ) {
                return 'bottom';
            }
        }
        
        // PRIORITY 5: ACCESSORIES - items that don't fit try-on
        $accessory_keywords = array(
            // Footwear
            'shoe', 'shoes', 'boot', 'boots', 'sandal', 'sandals', 'sneaker', 'sneakers',
            'heel', 'heels', 'pump', 'pumps', 'loafer', 'loafers', 'slipper', 'slippers',
            'footwear', 'flip-flop', 'moccasin', 'clog', 'espadrille',
            // Bags
            'bag', 'bags', 'purse', 'handbag', 'backpack', 'clutch', 'tote', 'satchel', 'messenger bag',
            // Small accessories
            'belt', 'tie', 'bow tie', 'necktie', 'bowtie',
            'scarf', 'hat', 'cap', 'beanie', 'beret', 'fedora', 'visor',
            'glove', 'gloves', 'mitten', 'mittens',
            'sock', 'socks', 'stocking', 'stockings', 'hosiery',
            // Jewelry
            'watch', 'watches', 'jewelry', 'jewellery', 'necklace', 'bracelet', 'ring', 'earring', 'pendant',
            // Eyewear
            'sunglasses', 'glasses', 'eyewear', 'spectacles',
            // Other accessories
            'wallet', 'keychain', 'umbrella', 'handkerchief',
            'accessory', 'accessories', 'misc', 'miscellaneous'
        );
        
        foreach ( $accessory_keywords as $keyword ) {
            if ( stripos( $name_lower, $keyword ) !== false ) {
                // Accessories don't fit the try-on model, return 'full' as default
                return 'full';
            }
        }

        // Default to 'full' if no match
        return 'full';
    }

    /**
     * Create category assignments table
     */
    private function create_assignments_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'fitroom_category_assignments';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            category_id BIGINT(20) UNSIGNED NOT NULL,
            category_name VARCHAR(255) NOT NULL,
            assignment VARCHAR(20) NOT NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_category (category_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Auto-assign categories automatically
     */
    public function maybe_auto_assign_categories() {
        // Only run if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Run auto-assignment to check for unassigned categories
        $this->run_auto_assignment();
    }

    /**
     * Run automatic category assignment
     */
    private function run_auto_assignment() {
        $this->create_assignments_table();
        
        $openai_key = get_option('fitroom_openai_api_key', '');
        $use_openai = !empty($openai_key);

        $terms = get_terms(array('taxonomy' => 'product_cat', 'hide_empty' => false));
        
        if (is_wp_error($terms)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        
        foreach ($terms as $term) {
            // Check if assignment already exists
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT assignment FROM {$table} WHERE category_id = %d",
                $term->term_id
            ));
            
            // Skip if already assigned
            if ($existing) {
                continue;
            }
            
            $suggestion = null;
            
            // Try OpenAI if key is configured
            if ($use_openai) {
                $suggestion = $this->get_openai_suggestion($term->name, $openai_key);
            }
            
            // Fallback to keyword-based
            if (!$suggestion) {
                $suggestion = $this->get_keyword_suggestion($term->name);
            }

            if ($suggestion && in_array($suggestion, array('top', 'bottom', 'full', 'none'), true)) {
                $wpdb->insert(
                    $table,
                    array(
                        'category_id' => $term->term_id, 
                        'category_name' => $term->name, 
                        'assignment' => $suggestion, 
                        'updated_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s')
                );
            }
        }
    }

    /**
     * Auto-assign a newly created category
     */
    public function auto_assign_new_category($term_id) {
        $this->create_assignments_table();
        
        $term = get_term($term_id, 'product_cat');
        if (!$term || is_wp_error($term)) {
            return;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        
        // Check if assignment already exists
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT assignment FROM {$table} WHERE category_id = %d",
            $term_id
        ));
        
        // Skip if already assigned
        if ($existing) {
            return;
        }
        
        $openai_key = get_option('fitroom_openai_api_key', '');
        $use_openai = !empty($openai_key);
        
        $suggestion = null;
        
        // Try OpenAI if key is configured
        if ($use_openai) {
            $suggestion = $this->get_openai_suggestion($term->name, $openai_key);
        }
        
        // Fallback to keyword-based
        if (!$suggestion) {
            $suggestion = $this->get_keyword_suggestion($term->name);
        }

        if ($suggestion && in_array($suggestion, array('top', 'bottom', 'full', 'none'), true)) {
            $wpdb->insert(
                $table,
                array(
                    'category_id' => $term_id, 
                    'category_name' => $term->name, 
                    'assignment' => $suggestion, 
                    'updated_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%s')
            );
        }
    }

    /**
     * Auto-assign categories when admin pages are accessed
     */
    public function maybe_auto_assign_on_admin_access() {
        // Only run on admin pages
        if (!is_admin()) {
            return;
        }

        // Only run if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Run auto-assignment to check for unassigned categories
        $this->run_auto_assignment();
    }
    
    /**
     * AJAX handler to get categories
     */
    public function ajax_get_categories() {
        check_ajax_referer('fitroom_categories_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'woo-fitroom-preview')));
        }
        
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ));
        
        if (is_wp_error($categories)) {
            wp_send_json_error(array('message' => __('Failed to retrieve categories.', 'woo-fitroom-preview')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        
        $category_data = array();
        foreach ($categories as $category) {
            // Get assignment from database table
            $assignment = $wpdb->get_var($wpdb->prepare(
                "SELECT assignment FROM {$table} WHERE category_id = %d",
                $category->term_id
            ));
            
            $category_data[] = array(
                'id' => $category->term_id,
                'name' => $category->name,
                'count' => $category->count,
                'assignment' => $assignment ?: '',
            );
        }
        
        wp_send_json_success(array('categories' => $category_data));
    }
    
    /**
     * AJAX handler to save category assignments
     */
    public function ajax_save_categories() {
        check_ajax_referer('fitroom_categories_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions.', 'woo-fitroom-preview')));
        }
        
        $assignments = isset($_POST['assignments']) ? $_POST['assignments'] : array();
        
        if (!is_array($assignments)) {
            wp_send_json_error(array('message' => __('Invalid data format.', 'woo-fitroom-preview')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        
        $this->create_assignments_table();
        
        $saved_count = 0;
        foreach ($assignments as $category_id => $assignment) {
            $category_id = intval($category_id);
            $assignment = sanitize_text_field($assignment);
            
            if (!in_array($assignment, array('top', 'bottom', 'full', 'none'), true)) {
                continue;
            }
            
            $term = get_term($category_id, 'product_cat');
            if (!$term || is_wp_error($term)) {
                continue;
            }
            
            // Update or insert assignment
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$table} WHERE category_id = %d",
                $category_id
            ));
            
            if ($existing) {
                $wpdb->update(
                    $table,
                    array(
                        'assignment' => $assignment,
                        'updated_at' => current_time('mysql')
                    ),
                    array('category_id' => $category_id),
                    array('%s', '%s'),
                    array('%d')
                );
            } else {
                $wpdb->insert(
                    $table,
                    array(
                        'category_id' => $category_id,
                        'category_name' => $term->name,
                        'assignment' => $assignment,
                        'updated_at' => current_time('mysql')
                    ),
                    array('%d', '%s', '%s', '%s')
                );
            }
            
            $saved_count++;
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('%d category assignments saved successfully.', 'woo-fitroom-preview'), $saved_count),
            'saved_count' => $saved_count
        ));
    }
    
    /**
     * Initialize analytics tracking
     */
    public function init_analytics_tracking() {
        // Create analytics tables on plugin activation
        $this->create_analytics_tables();
        
        // Add tracking hooks
        add_action('woo_fitroom_preview_generated', array($this, 'track_preview_generation'), 10, 2);
        add_action('woo_fitroom_image_uploaded', array($this, 'track_image_upload'), 10, 2);
        add_action('woo_fitroom_image_deleted', array($this, 'track_image_deletion'), 10, 2);
        add_action('woo_fitroom_error_occurred', array($this, 'track_error'), 10, 2);
        
        error_log('TryOnTool Analytics: Tracking hooks registered successfully');
    }

    /**
     * Create analytics database tables
     */
    private function create_analytics_tables() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tryon_analytics_events';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            event_type varchar(50) NOT NULL,
            user_id bigint(20) DEFAULT NULL,
            product_id bigint(20) DEFAULT NULL,
            event_data text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_type (event_type),
            KEY user_id (user_id),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        $daily_table = $wpdb->prefix . 'tryon_analytics_daily';
        $daily_sql = "CREATE TABLE $daily_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            date date NOT NULL,
            total_previews int(11) DEFAULT 0,
            successful_previews int(11) DEFAULT 0,
            failed_previews int(11) DEFAULT 0,
            unique_users int(11) DEFAULT 0,
            images_uploaded int(11) DEFAULT 0,
            images_deleted int(11) DEFAULT 0,
            storage_used_mb decimal(10,2) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY date (date)
        ) $charset_collate;";

        dbDelta($daily_sql);
    }

    /**
     * Track preview generation
     */
    public function track_preview_generation($user_id, $data) {
        error_log('TryOnTool Analytics: track_preview_generation called for user ' . $user_id . ' with data: ' . print_r($data, true));
        $this->log_analytics_event('preview_generated', $user_id, $data['product_id'] ?? null, $data);
        $this->update_daily_stats('preview_generated', $user_id, $data);
    }

    /**
     * Track image upload
     */
    public function track_image_upload($user_id, $data) {
        $this->log_analytics_event('image_uploaded', $user_id, null, $data);
        $this->update_daily_stats('image_uploaded', $user_id, $data);
    }

    /**
     * Track image deletion
     */
    public function track_image_deletion($user_id, $data) {
        $this->log_analytics_event('image_deleted', $user_id, null, $data);
        $this->update_daily_stats('image_deleted', $user_id, $data);
    }

    /**
     * Track errors
     */
    public function track_error($user_id, $data) {
        $this->log_analytics_event('error_occurred', $user_id, $data['product_id'] ?? null, $data);
        $this->update_daily_stats('error_occurred', $user_id, $data);
    }

    /**
     * Log analytics event
     */
    private function log_analytics_event($event_type, $user_id, $product_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tryon_analytics_events';
        
        $result = $wpdb->insert(
            $table_name,
            array(
                'event_type' => $event_type,
                'user_id' => $user_id,
                'product_id' => $product_id,
                'event_data' => json_encode($data),
                'created_at' => current_time('mysql')
            ),
            array('%s', '%d', '%d', '%s', '%s')
        );
        
        if ($result === false) {
            error_log('TryOnTool Analytics: Failed to insert event - ' . $wpdb->last_error);
        } else {
            error_log('TryOnTool Analytics: Successfully logged event ' . $event_type . ' for user ' . $user_id . ' (ID: ' . $wpdb->insert_id . ')');
        }
    }

    /**
     * Update daily statistics
     */
    private function update_daily_stats($event_type, $user_id, $data) {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tryon_analytics_daily';
        $today = current_time('Y-m-d');
        
        // Get or create today's record
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE date = %s",
            $today
        ));
        
        if (!$existing) {
            $wpdb->insert(
                $table_name,
                array('date' => $today),
                array('%s')
            );
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT * FROM $table_name WHERE date = %s",
                $today
            ));
        }
        
        // Update based on event type
        $updates = array();
        switch ($event_type) {
            case 'preview_generated':
                $updates['total_previews'] = $existing->total_previews + 1;
                if (isset($data['success']) && $data['success']) {
                    $updates['successful_previews'] = $existing->successful_previews + 1;
                } else {
                    $updates['failed_previews'] = $existing->failed_previews + 1;
                }
                break;
            case 'image_uploaded':
                $updates['images_uploaded'] = $existing->images_uploaded + 1;
                break;
            case 'image_deleted':
                $updates['images_deleted'] = $existing->images_deleted + 1;
                break;
        }
        
        if (!empty($updates)) {
            $wpdb->update(
                $table_name,
                $updates,
                array('date' => $today),
                array_fill(0, count($updates), '%d'),
                array('%s')
            );
        }
    }

    /**
     * AJAX: Get analytics data
     */
    public function ajax_get_analytics_data() {
        check_ajax_referer('WOO_FITROOM_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-fitroom-preview')));
        }
        
        try {
            $analytics_data = $this->get_analytics_overview();
            
            wp_send_json_success($analytics_data);
        } catch (Exception $e) {
            error_log('TryOnTool Analytics: Error in ajax_get_analytics_data - ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('Error retrieving analytics data.', 'woo-fitroom-preview'),
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * AJAX: Refresh analytics data
     */
    public function ajax_refresh_analytics() {
        check_ajax_referer('WOO_FITROOM_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-fitroom-preview')));
        }
        
        try {
            // Force refresh by clearing cache
            delete_transient('tryon_analytics_overview');
            
            // Also clear any object cache if using caching plugins
            wp_cache_delete('tryon_analytics_overview', 'tryon_analytics');
            
            // Get fresh data (bypass cache by passing false or calling directly)
            $analytics_data = $this->get_analytics_overview(true);
            
            wp_send_json_success($analytics_data);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Error refreshing analytics data.', 'woo-fitroom-preview'),
                'error' => $e->getMessage()
            ));
        }
    }

    /**
     * Get analytics overview data
     * 
     * @param bool $force_refresh Whether to force refresh and bypass cache
     */
    public function get_analytics_overview($force_refresh = false) {
        // Check cache first (unless forcing refresh)
        if (!$force_refresh) {
            $cached_data = get_transient('tryon_analytics_overview');
            if ($cached_data !== false) {
                return $cached_data;
            }
        }
        
        // Get local data - ensure all values are properly formatted
        // Force fresh database reads by cleaning object cache
        if ($force_refresh) {
            wp_cache_delete('alloptions', 'options');
            wp_cache_flush_group('options');
        }
        
        $local_data = array(
            'total_users' => intval($this->get_total_users_with_consent($force_refresh)),
            'total_images' => intval($this->get_total_images_stored($force_refresh)),
            'storage_used' => $this->get_storage_usage($force_refresh) ? $this->get_storage_usage($force_refresh) : '0 MB',
            'active_users_10d' => intval($this->get_active_users_10d($force_refresh)),
            'total_previews' => intval($this->get_total_previews($force_refresh)),
            'success_rate' => floatval($this->get_success_rate()),
            'preview_trend' => $this->get_preview_trend_30d($force_refresh),
            'top_categories' => $this->get_top_categories(),
            'last_updated' => current_time('Y-m-d H:i:s'),
            'site_info' => $this->get_site_information()
        );
        
        // Ensure preview_trend is always an array
        if (!is_array($local_data['preview_trend'])) {
            $local_data['preview_trend'] = array();
        }
        
        // Ensure top_categories is always an array
        if (!is_array($local_data['top_categories'])) {
            $local_data['top_categories'] = array();
        }
        
        // Get server-side data from tryontool.com
        $server_data = $this->get_server_analytics_data();
        
        // Merge server data with local data (server data takes precedence)
        $data = array_merge($local_data, $server_data);
        
        // Cache for 5 minutes
        set_transient('tryon_analytics_overview', $data, 300);
        
        return $data;
    }

    /**
     * Get analytics data from tryontool.com server
     */
    private function get_server_analytics_data() {
        $license_key = get_option('WOO_FITROOM_license_key');
        $site_url = home_url();
        
        if (empty($license_key)) {
            error_log('TryOnTool Analytics: No license key found, skipping server data');
            return array();
        }
        
        $request_args = array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'license_key' => $license_key,
                'site_url' => $site_url,
                'action' => 'get_analytics_data'
            )),
            'timeout' => 30,
        );
        
        $response = wp_remote_post('https://tryontool.com/wp-json/tryontool/v1/analytics', $request_args);
        
        if (is_wp_error($response)) {
            error_log('TryOnTool Analytics: Server request failed - ' . $response->get_error_message());
            return array();
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = json_decode(wp_remote_retrieve_body($response), true);
        
        if ($response_code === 200 && isset($response_body['success']) && $response_body['success']) {
            error_log('TryOnTool Analytics: Successfully retrieved server data');
            return $response_body['data'] ?? array();
        } else {
            error_log('TryOnTool Analytics: Server returned error - ' . print_r($response_body, true));
            return array();
        }
    }

    /**
     * Get site-specific consent records (filtered by current site URL)
     * 
     * @param bool $force_refresh Whether to bypass cache and get fresh data
     */
    private function get_site_specific_consents($force_refresh = false) {
        // If forcing refresh, clear option cache to get latest data
        if ($force_refresh) {
            wp_cache_delete('WOO_FITROOM_consents', 'options');
        }
        
        $all_consents = get_option('WOO_FITROOM_consents', array());
        $current_site_url = parse_url(home_url(), PHP_URL_HOST);
        $site_specific_consents = array();
        $skipped_legacy = 0;
        $skipped_other_sites = 0;
        
        foreach ($all_consents as $user_id => $consent_data) {
            // Check if this consent record belongs to the current site
            $consent_host = isset($consent_data['site_url']) ? $consent_data['site_url'] : null;
            
            if ($consent_host === $current_site_url) {
                // Explicit match - include this record
                $site_specific_consents[$user_id] = $consent_data;
            } elseif (!isset($consent_data['site_url'])) {
                // Legacy records without site_url - DO NOT include them
                // They could belong to any site, so we can't safely assume they belong to current site
                $skipped_legacy++;
                error_log('TryOnTool Analytics: Skipping legacy consent record for user_id=' . $user_id . ' (no site_url - cannot verify site ownership)');
            } else {
                // Record has site_url but it doesn't match current site - skip it
                $skipped_other_sites++;
                error_log('TryOnTool Analytics: Skipping consent record for user_id=' . $user_id . ' (site_url=' . $consent_host . ' does not match current site=' . $current_site_url . ')');
            }
        }
        
        error_log('TryOnTool Analytics: Found ' . count($site_specific_consents) . ' site-specific consents out of ' . count($all_consents) . ' total consents for site: ' . $current_site_url . ' (skipped ' . $skipped_legacy . ' legacy, ' . $skipped_other_sites . ' other sites)');
        return $site_specific_consents;
    }

    /**
     * Get total users who have actually generated previews (have images in Wasabi)
     * Now properly filtered by current site URL
     * 
     * @param bool $force_refresh Whether to force refresh and bypass cache
     */
    private function get_total_users_with_consent($force_refresh = false) {
        // Get real data from Wasabi - users who have actually generated previews for THIS site only
        if (class_exists('WooFITROOM_Wasabi')) {
            try {
                $total_users = WooFITROOM_Wasabi::get_total_users_with_previews_for_site($force_refresh);
                error_log('TryOnTool Analytics: Total users from Wasabi for current site: ' . $total_users);
                return $total_users;
            } catch (Exception $e) {
                error_log('TryOnTool Analytics: Error fetching user count from Wasabi: ' . $e->getMessage());
                
                // Fallback to site-specific consent count if Wasabi fails
                $consents = $this->get_site_specific_consents($force_refresh);
                $consent_count = 0;
                foreach ($consents as $user_id => $consent_data) {
                    if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                        $consent_count++;
                    }
                }
                return $consent_count;
            }
        } else {
            // Fallback to site-specific consent count if Wasabi class not available
            $consents = $this->get_site_specific_consents($force_refresh);
            $consent_count = 0;
            foreach ($consents as $user_id => $consent_data) {
                if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                    $consent_count++;
                }
            }
            return $consent_count;
        }
    }

    /**
     * Get total images stored from Wasabi (site-specific)
     * 
     * @param bool $force_refresh Whether to force refresh (passed through for consistency)
     */
    private function get_total_images_stored($force_refresh = false) {
        $total_images = 0;
        
        if (class_exists('WooFITROOM_Wasabi')) {
            try {
                $summary = WooFITROOM_Wasabi::get_site_images_summary($force_refresh);
                $total_images = isset($summary['count']) ? intval($summary['count']) : 0;
                error_log('TryOnTool Analytics: Total images (site-wide) from Wasabi: ' . $total_images);
            } catch (Exception $e) {
                error_log('TryOnTool Analytics: Error fetching site image summary: ' . $e->getMessage());
                $total_images = 0;
            }
        }
        
        return $total_images;
    }

    /**
     * Get storage usage from Wasabi data (site-specific)
     * 
     * @param bool $force_refresh Whether to force refresh (passed through for consistency)
     */
    private function get_storage_usage($force_refresh = false) {
        $total_bytes = 0;
        
        if (class_exists('WooFITROOM_Wasabi')) {
            try {
                $summary = WooFITROOM_Wasabi::get_site_images_summary($force_refresh);
                $total_bytes = isset($summary['total_bytes']) ? intval($summary['total_bytes']) : 0;
                error_log('TryOnTool Analytics: Storage used (site-wide) total bytes: ' . $total_bytes);
                
                // If server did not return sizes, fall back to simple estimate based on image count
                if ($total_bytes <= 0 && isset($summary['count'])) {
                    $total_bytes = intval($summary['count']) * 2 * 1024 * 1024; // estimate 2MB each
                    error_log('TryOnTool Analytics: Estimated storage used at 2MB per image: ' . $total_bytes . ' bytes');
                }
            } catch (Exception $e) {
                error_log('TryOnTool Analytics: Error fetching site storage summary: ' . $e->getMessage());
                $total_images = $this->get_total_images_stored($force_refresh);
                $total_bytes = $total_images * 2 * 1024 * 1024; // 2MB per image
            }
        } else {
            // Fallback to estimation
            $total_images = $this->get_total_images_stored($force_refresh);
            $total_bytes = $total_images * 2 * 1024 * 1024; // 2MB per image
        }
        
        $total_mb = $total_bytes / (1024 * 1024);
        
        if ($total_mb >= 1024) {
            return round($total_mb / 1024, 2) . ' GB';
        } else {
            return round($total_mb, 2) . ' MB';
        }
    }

    /**
     * Get active users in last 10 days (users with consent who generated previews) - site-specific
     * 
     * @param bool $force_refresh Whether to force refresh and bypass cache
     */
    private function get_active_users_10d($force_refresh = false) {
        $active_count = 0;
        $cutoff_timestamp = strtotime('-10 days');
        $cutoff_date = date('Y-m-d', $cutoff_timestamp);
        $current_site_url = parse_url(home_url(), PHP_URL_HOST);
        $active_users = array();
        
        // Get real data from Wasabi for current site only - check actual image upload dates
        if (class_exists('WooFITROOM_Wasabi')) {
            try {
                $all_images_data = WooFITROOM_Wasabi::get_all_users_images_data_for_site($force_refresh);
                
                foreach ($all_images_data as $user_id => $user_images) {
                    $user_is_active = false;
                    
                    // Check if user has any images uploaded in the last 10 days
                    foreach ($user_images as $image) {
                        // Check created_date or last_modified from Wasabi metadata
                        $image_date_str = '';
                        if (isset($image['created_date']) && !empty($image['created_date'])) {
                            $image_date_str = $image['created_date'];
                        } elseif (isset($image['last_modified']) && !empty($image['last_modified'])) {
                            // Extract date from last_modified (format: Y-m-d H:i:s)
                            $image_date_str = date('Y-m-d', strtotime($image['last_modified']));
                        }
                        
                        if (!empty($image_date_str)) {
                            // Compare dates directly (Y-m-d format)
                            if ($image_date_str >= $cutoff_date) {
                                $user_is_active = true;
                                break; // Found at least one recent image, user is active
                            }
                        }
                    }
                    
                    if ($user_is_active) {
                        $active_users[] = $user_id;
                        $active_count++;
                    }
                }
                
                error_log('TryOnTool Analytics: Found ' . $active_count . ' active users in last 10 days from Wasabi for current site (cutoff: ' . $cutoff_date . ', user IDs: ' . implode(', ', $active_users) . ')');
            } catch (Exception $e) {
                error_log('TryOnTool Analytics: Error fetching Wasabi data for active users: ' . $e->getMessage());
                
                // Fallback to user meta if Wasabi fails
                $consents = $this->get_site_specific_consents($force_refresh);
                foreach ($consents as $user_id => $consent_data) {
                    if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                        $last_preview_date = get_user_meta($user_id, 'woo_fitroom_last_preview_date', true);
                        
                        if (!empty($last_preview_date)) {
                            $preview_timestamp = strtotime($last_preview_date);
                            if ($preview_timestamp && $preview_timestamp >= $cutoff_timestamp) {
                                $active_count++;
                            }
                        }
                    }
                }
                error_log('TryOnTool Analytics: Using fallback method - found ' . $active_count . ' active users in last 10 days');
            }
        } else {
            // Fallback to user meta if Wasabi class not available
            $consents = $this->get_site_specific_consents($force_refresh);
            foreach ($consents as $user_id => $consent_data) {
                if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                    $last_preview_date = get_user_meta($user_id, 'woo_fitroom_last_preview_date', true);
                    
                    if (!empty($last_preview_date)) {
                        $preview_timestamp = strtotime($last_preview_date);
                        if ($preview_timestamp && $preview_timestamp >= $cutoff_timestamp) {
                            $active_count++;
                        }
                    }
                }
            }
            error_log('TryOnTool Analytics: Wasabi class not available - found ' . $active_count . ' active users in last 10 days using user meta');
        }
        
        return $active_count;
    }

    /**
     * Get total previews generated from Wasabi data (site-specific)
     * 
     * @param bool $force_refresh Whether to force refresh (passed through for consistency)
     */
    private function get_total_previews($force_refresh = false) {
        $total_previews = 0;
        
        // Primary source: aggregate per-user preview counters (counts previews from uploads AND gallery reuses)
        $consents = $this->get_site_specific_consents($force_refresh);
        foreach ($consents as $user_id => $consent_data) {
            if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                $user_previews = intval(get_user_meta($user_id, 'woo_fitroom_total_previews', true));
                $total_previews += $user_previews;
            }
        }
        
        // Fallback: if counters are missing, estimate using site-wide stored images count
        if ($total_previews === 0 && class_exists('WooFITROOM_Wasabi')) {
            try {
                $summary = WooFITROOM_Wasabi::get_site_images_summary($force_refresh);
                $total_previews = isset($summary['count']) ? intval($summary['count']) : 0;
            } catch (Exception $e) {
                // leave as 0
            }
        }
        
        return $total_previews;
    }

    /**
     * Get success rate
     */
    private function get_success_rate() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tryon_analytics_events';
        $total = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table_name 
            WHERE event_type = 'preview_generated'
        ");
        
        $successful = $wpdb->get_var("
            SELECT COUNT(*) 
            FROM $table_name 
            WHERE event_type = 'preview_generated'
            AND JSON_EXTRACT(event_data, '$.success') = true
        ");
        
        if ($total > 0) {
            return round(($successful / $total) * 100, 1);
        }
        
        return 0;
    }

    /**
     * Get preview generation trend for last 30 days (site-specific)
     * 
     * @param bool $force_refresh Whether to force refresh (passed through for consistency)
     */
    private function get_preview_trend_30d($force_refresh = false) {
        // Generate last 30 days array (including today)
        $trend = array();
        $today = strtotime('today'); // Today at 00:00:00
        $start_date = $today - (29 * 24 * 60 * 60); // 29 days ago to include today
        
        // Initialize all days with 0 previews
        for ($i = 0; $i < 30; $i++) {
            $date = date('Y-m-d', $start_date + ($i * 24 * 60 * 60));
            $trend[] = array(
                'date' => $date,
                'previews' => 0
            );
        }
        
        error_log('TryOnTool Analytics: Chart date range from ' . date('Y-m-d', $start_date) . ' to ' . date('Y-m-d', $today) . ' for current site');
        
        // Get real data from Wasabi for current site only
        if (class_exists('WooFITROOM_Wasabi')) {
            try {
                $all_images_data = WooFITROOM_Wasabi::get_all_users_images_data_for_site($force_refresh);
                
                foreach ($all_images_data as $user_id => $user_images) {
                    foreach ($user_images as $image) {
                        $image_date = $image['created_date'];
                        $image_timestamp = strtotime($image_date);
                        
                        // Check if image is within our 30-day range
                        if ($image_timestamp >= $start_date && $image_timestamp <= $today) {
                            $days_ago = floor(($image_timestamp - $start_date) / (24 * 60 * 60));
                            
                            if ($days_ago >= 0 && $days_ago < 30) {
                                $trend[$days_ago]['previews']++;
                                error_log('TryOnTool Analytics: Added preview for date ' . $image_date . ' (day ' . $days_ago . ') for current site');
                            }
                        }
                    }
                }
                
                error_log('TryOnTool Analytics: Processed ' . count($all_images_data) . ' users with images from Wasabi for current site');
            } catch (Exception $e) {
                error_log('TryOnTool Analytics: Error fetching Wasabi data: ' . $e->getMessage());
            }
        }
        
        // If no real data, initialize with empty data (no sample data for fresh installs)
        $has_real_data = false;
        foreach ($trend as $day) {
            if ($day['previews'] > 0) {
                $has_real_data = true;
                break;
            }
        }
        
        if (!$has_real_data) {
            error_log('TryOnTool Analytics: No preview data found for current site - showing empty chart');
        }
        
        error_log('TryOnTool Analytics: Generated 30-day trend with ' . count($trend) . ' days for current site');
        
        return $trend;
    }

    /**
     * Get top product categories
     */
    private function get_top_categories() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . 'tryon_analytics_events';
        
        // Fallback approach for broader DB compatibility (no JSON_EXTRACT requirement)
        // Pull recent preview_generated events and aggregate categories in PHP
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT event_data FROM $table_name WHERE event_type = %s ORDER BY id DESC LIMIT %d",
                'preview_generated',
                2000 // cap to a reasonable recent sample
            )
        );
        
        if (!is_array($rows) || empty($rows)) {
            return array();
        }
        
        $category_to_count = array();
        foreach ($rows as $row) {
            if (empty($row->event_data)) { continue; }
            $data = json_decode($row->event_data, true);
            if (!is_array($data)) { continue; }
            if (!isset($data['category']) || $data['category'] === null || $data['category'] === '') { continue; }
            $cat = strtolower(trim((string) $data['category']));
            if ($cat === '') { continue; }
            if (!isset($category_to_count[$cat])) {
                $category_to_count[$cat] = 0;
            }
            $category_to_count[$cat]++;
        }
        
        if (empty($category_to_count)) {
            return array();
        }
        
        // Sort by count desc and take top 5
        arsort($category_to_count);
        $top = array_slice($category_to_count, 0, 5, true);
        
        $out = array();
        foreach ($top as $cat => $count) {
            $out[] = array(
                'name' => ucfirst($cat),
                'count' => intval($count),
            );
        }
        return $out;
    }

    /**
     * Get site information
     */
    private function get_site_information() {
        return array(
            'site_url' => home_url(),
            'site_name' => get_bloginfo('name'),
            'wp_version' => get_bloginfo('version'),
            'wc_version' => defined('WC_VERSION') ? WC_VERSION : null,
            'plugin_version' => defined('WOO_FITROOM_PREVIEW_VERSION') ? WOO_FITROOM_PREVIEW_VERSION : '1.3.0',
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'timezone' => wp_timezone_string(),
            'language' => get_locale(),
            'multisite' => is_multisite(),
            'license_key_set' => !empty(get_option('WOO_FITROOM_license_key')),
            'analytics_tables_created' => $this->check_analytics_tables_exist()
        );
    }

    /**
     * Check if analytics tables exist
     */
    private function check_analytics_tables_exist() {
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'tryon_analytics_events';
        $daily_table = $wpdb->prefix . 'tryon_analytics_daily';
        
        $events_exists = $wpdb->get_var("SHOW TABLES LIKE '$events_table'") == $events_table;
        $daily_exists = $wpdb->get_var("SHOW TABLES LIKE '$daily_table'") == $daily_table;
        
        return $events_exists && $daily_exists;
    }

    /**
     * AJAX: Test tracking functionality
     */
    public function ajax_test_tracking() {
        check_ajax_referer('WOO_FITROOM_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-fitroom-preview')));
        }
        
        // Simulate a test preview generation
        $user_id = get_current_user_id();
        $test_data = array(
            'success' => true,
            'product_id' => 999,
            'category' => 'test',
            'timestamp' => current_time('mysql'),
            'test' => true
        );
        
        error_log('TryOnTool Analytics: Running test tracking for user ' . $user_id);
        
        // Update user meta directly for testing
        if ($user_id > 0) {
            update_user_meta($user_id, 'woo_fitroom_last_preview_date', current_time('mysql'));
            update_user_meta($user_id, 'woo_fitroom_total_previews', intval(get_user_meta($user_id, 'woo_fitroom_total_previews', true)) + 1);
            error_log('TryOnTool Analytics: Updated user meta for test user ' . $user_id);
        }
        
        // Trigger the tracking
        do_action('woo_fitroom_preview_generated', $user_id, $test_data);
        
        wp_send_json_success(array(
            'message' => 'Test tracking completed',
            'user_id' => $user_id,
            'data' => $test_data
        ));
    }

    /**
     * AJAX: Check database status
     */
    public function ajax_check_database() {
        check_ajax_referer('WOO_FITROOM_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-fitroom-preview')));
        }
        
        global $wpdb;
        
        $events_table = $wpdb->prefix . 'tryon_analytics_events';
        $daily_table = $wpdb->prefix . 'tryon_analytics_daily';
        
        $events_exists = $wpdb->get_var("SHOW TABLES LIKE '$events_table'") == $events_table;
        $daily_exists = $wpdb->get_var("SHOW TABLES LIKE '$daily_table'") == $daily_table;
        
        $events_count = $events_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $events_table") : 0;
        $daily_count = $daily_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $daily_table") : 0;
        
        wp_send_json_success(array(
            'events_table_exists' => $events_exists,
            'daily_table_exists' => $daily_exists,
            'events_count' => intval($events_count),
            'daily_count' => intval($daily_count),
            'tables' => array(
                'events' => $events_table,
                'daily' => $daily_table
            )
        ));
    }

    /**
     * AJAX: Check consent records
     */
    public function ajax_check_consents() {
        check_ajax_referer('WOO_FITROOM_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-fitroom-preview')));
        }
        
        $consents = get_option('WOO_FITROOM_consents', array());
        
        $consent_count = 0;
        $consent_details = array();
        
        foreach ($consents as $user_id => $consent_data) {
            if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                $consent_count++;
                $consent_details[] = array(
                    'user_id' => $user_id,
                    'email' => $consent_data['email'] ?? 'Unknown',
                    'consent_date' => $consent_data['consent_timestamp'],
                    'terms_consent' => $consent_data['terms_consent'] ?? '',
                    'refund_consent' => $consent_data['refund_consent'] ?? ''
                );
            }
        }
        
        // Get active users in last 10 days
        $active_users_10d = $this->get_active_users_10d();
        
        wp_send_json_success(array(
            'total_consents' => count($consents),
            'valid_consents' => $consent_count,
            'active_users_10d' => $active_users_10d,
            'consent_details' => $consent_details,
            'raw_consents' => $consents
        ));
    }

    /**
     * AJAX: Check active users in last 10 days
     */
    public function ajax_check_active_users() {
        check_ajax_referer('WOO_FITROOM_analytics_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'woo-fitroom-preview')));
        }
        
        global $wpdb;
        
        // Get users who have given consent
        $consents = get_option('WOO_FITROOM_consents', array());
        $consent_user_ids = array();
        
        foreach ($consents as $user_id => $consent_data) {
            if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                $consent_user_ids[] = intval($user_id);
            }
        }
        
        if (empty($consent_user_ids)) {
            wp_send_json_success(array(
                'active_users_count' => 0,
                'active_users_details' => array(),
                'consent_users_count' => 0,
                'message' => 'No users with consent found'
            ));
        }
        
        // Get users who generated previews in last 10 days using user meta
        $active_users_details = array();
        $cutoff_date = strtotime('-10 days');
        
        foreach ($consent_user_ids as $user_id) {
            $last_preview_date = get_user_meta($user_id, 'woo_fitroom_last_preview_date', true);
            $total_previews = intval(get_user_meta($user_id, 'woo_fitroom_total_previews', true));
            
            if (!empty($last_preview_date)) {
                $preview_timestamp = strtotime($last_preview_date);
                if ($preview_timestamp && $preview_timestamp >= $cutoff_date) {
                    $user_info = get_userdata($user_id);
                    $active_users_details[] = array(
                        'user_id' => $user_id,
                        'email' => $user_info ? $user_info->user_email : 'Unknown',
                        'display_name' => $user_info ? $user_info->display_name : 'Unknown',
                        'last_preview_date' => $last_preview_date,
                        'total_previews' => $total_previews
                    );
                }
            }
        }
        
        wp_send_json_success(array(
            'active_users_count' => count($active_users_details),
            'active_users_details' => $active_users_details,
            'consent_users_count' => count($consent_user_ids),
            'date_range' => 'Last 10 days',
            'cutoff_date' => date('Y-m-d H:i:s', strtotime('-10 days'))
        ));
    }
}
}


