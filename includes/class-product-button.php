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
 * Handle the product page button display and functionality for TryOnTool
 */
if (!class_exists('WooFitroomPreview_Product_Button')) {
    class WooFitroomPreview_Product_Button {
    /**
     * Initialize the class
     */
    public function __construct() {
        // Add debug logging
        error_log('WooTryOnTool Plugin: Product Button class constructed');
        
        // Try different hooks to ensure button display
        add_action('woocommerce_after_add_to_cart_button', array($this, 'add_preview_button'), 10);
        
        // Add modal template to footer
        add_action('wp_footer', array($this, 'add_modal_template'));
        
        // Add top/bottom modal template to footer
        add_action('wp_footer', array($this, 'add_top_bottom_modal_template'));
        
        // Add bottom modal template to footer
        add_action('wp_footer', array($this, 'add_bottom_modal_template'));
        
        // Add debugging JavaScript to header
        add_action('wp_head', array($this, 'add_debug_script'));
        
        // Enqueue scripts and styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Add AJAX handlers
        add_action('wp_ajax_woo_fitroom_generate_preview', array($this, 'handle_preview_generation'));
        add_action('wp_ajax_nopriv_woo_fitroom_generate_preview', array($this, 'handle_preview_generation'));
    }

    /**
     * Add the preview button to product page
     */
    public function add_preview_button() {
        // Add debug logging
        error_log('WooTryOnTool Plugin: Attempting to add preview button');
        
        if (!is_product()) {
            error_log('WooTryOnTool Plugin: Not on product page');
            return;
        }

        if ( ! get_option( 'WOO_FITROOM_preview_enabled' ) ) {
            error_log('WooTryOnTool Plugin: Plugin not enabled');
            return;
        }

        if ( empty( get_option( 'WOO_FITROOM_license_key' ) ) ) {
            // error_log('WooTryOnTool Plugin: License key not configured.');
            // return;
        }

        global $product;
        if (!$product) {
            error_log('WooTryOnTool Plugin: No product found');
            return;
        }

        $product_image_id = $product->get_image_id();
        error_log('WooTryOnTool Plugin: Product image ID: ' . var_export($product_image_id, true));
        
        $product_image_url = '';
        
        if ($product_image_id) {
            $product_image_url = wp_get_attachment_url($product_image_id);
        } else {
            $gallery_image_ids = $product->get_gallery_image_ids();
            if (!empty($gallery_image_ids)) {
                $product_image_url = wp_get_attachment_url($gallery_image_ids[0]);
            }
        }
        
        if (empty($product_image_url)) {
            $product_image_url = wc_placeholder_img_src('woocommerce_single');
            error_log('WooTryOnTool Plugin: Using placeholder image');
        }

        error_log('WooTryOnTool Plugin: Product image URL: ' . $product_image_url);
        
        if (empty($product_image_url)) {
            error_log('WooTryOnTool Plugin: No product image found, even after fallbacks');
            return;
        }

        if ( ! $this->current_user_can_use_feature() ) {
            error_log('WooTryOnTool Plugin: Current user not permitted to use Try-On feature');
            return;
        }

        // Get product category assignment
        $assignment = $this->get_product_category_assignment($product);
        
        error_log('WooTryOnTool Plugin: Outputting buttons');
        error_log('WooTryOnTool Plugin: Category assignment: ' . var_export($assignment, true));
        ?>
        <script>console.log('Product Image URL in HTML:', <?php echo json_encode($product_image_url); ?>);</script>
        
        <div class="woo-fitroom-preview-buttons" style="display: inline-block; margin-left: 10px;">
            <?php if ($assignment === 'none'): ?>
                <!-- No button displayed - category set to none -->
            <?php elseif ($assignment === 'top'): ?>
                <!-- Show only Try Top Only button -->
                <button type="button" 
                        class="button alt woo-fitroom-preview-button-top-bottom" 
                        data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                        data-product-image="<?php echo esc_url($product_image_url); ?>"
                        data-preview-type="top-bottom"
                        data-theme-detection="enabled"
                        style="margin-right: 5px;">
                    <?php echo esc_html($this->get_top_bottom_button_text()); ?>
                </button>
            <?php elseif ($assignment === 'bottom'): ?>
                <!-- Show only Try Bottom Only button -->
                <button type="button" 
                        class="button alt woo-fitroom-preview-button-bottom" 
                        data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                        data-product-image="<?php echo esc_url($product_image_url); ?>"
                        data-preview-type="bottom"
                        data-theme-detection="enabled"
                        style="margin-right: 5px;">
                    <?php echo esc_html($this->get_bottom_button_text()); ?>
                </button>
            <?php else: ?>
                <!-- Show Try It On button (full outfit or no assignment) -->
                <button type="button" 
                        class="button alt woo-fitroom-preview-button" 
                        data-product-id="<?php echo esc_attr($product->get_id()); ?>"
                        data-product-image="<?php echo esc_url($product_image_url); ?>"
                        data-preview-type="full-outfit"
                        data-theme-detection="enabled"
                        style="margin-right: 5px;">
                    <?php echo esc_html($this->get_button_text()); ?>
                </button>
            <?php endif; ?>
        </div>
        
        <script>
        console.log('WooTryOnTool: Buttons rendered. Top/Bottom button count:', document.querySelectorAll('.woo-fitroom-preview-button-top-bottom').length);
        console.log('WooTryOnTool: Top/Bottom button element:', document.querySelector('.woo-fitroom-preview-button-top-bottom'));
        console.log('WooTryOnTool: Bottom button count:', document.querySelectorAll('.woo-fitroom-preview-button-bottom').length);
        console.log('WooTryOnTool: Bottom button element:', document.querySelector('.woo-fitroom-preview-button-bottom'));
        </script>
        <?php
    }

    /**
     * Get the button text based on the mode setting
     */
    private function get_button_text() {
        $button_text_mode = get_option('WOO_FITROOM_button_text_mode', 'default');
        
        if ($button_text_mode === 'custom') {
            $custom_text = get_option('WOO_FITROOM_custom_button_text', 'Try It On');
            return !empty($custom_text) ? $custom_text : 'Try It On';
        }
        
        return 'Try It On';
    }

    /**
     * Get the top/bottom button text
     */
    private function get_top_bottom_button_text() {
        $button_text_mode = get_option('WOO_FITROOM_button_text_mode', 'default');
        
        if ($button_text_mode === 'custom') {
            $custom_text = get_option('WOO_FITROOM_custom_top_bottom_button_text', 'Try It On');
            return !empty($custom_text) ? $custom_text : 'Try It On';
        }
        
        return 'Try It On';
    }

    /**
     * Get the bottom button text
     */
    private function get_bottom_button_text() {
        $button_text_mode = get_option('WOO_FITROOM_button_text_mode', 'default');
        
        if ($button_text_mode === 'custom') {
            $custom_text = get_option('WOO_FITROOM_custom_bottom_button_text', 'Try It On');
            return !empty($custom_text) ? $custom_text : 'Try It On';
        }
        
        return 'Try It On';
    }

    /**
     * Get product category assignment (top/bottom/full)
     */
    private function get_product_category_assignment($product) {
        // Get product categories
        $category_ids = $product->get_category_ids();
        if (empty($category_ids)) {
            return null;
        }
        
        // Get assignment for the first category
        $primary_cat_id = $category_ids[0];
        global $wpdb;
        $table = $wpdb->prefix . 'fitroom_category_assignments';
        
        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'") === $table;
        if (!$table_exists) {
            return null;
        }
        
        $assignment = $wpdb->get_var($wpdb->prepare(
            "SELECT assignment FROM $table WHERE category_id = %d",
            $primary_cat_id
        ));
        
        return $assignment ? $assignment : null;
    }

    private function current_user_can_use_feature() {
        $logged_in_only   = (bool) get_option( 'WOO_FITROOM_logged_in_only' );
        $allowed_roles    = (array) get_option( 'WOO_FITROOM_allowed_roles', array() );
        $allowed_user_ids = array_filter(array_map('absint', explode(',', (string) get_option('WOO_FITROOM_allowed_user_ids', ''))));
        $required_tag     = trim( (string) get_option( 'WOO_FITROOM_required_user_tag', '' ) );

        if ( ! is_user_logged_in() ) {
            if ( $logged_in_only ) {
                return false;
            }
            return empty( $allowed_roles ) && empty( $allowed_user_ids ) && $required_tag === '';
        }

        $user_id  = get_current_user_id();
        $user_obj = wp_get_current_user();

        if ( ! empty( $allowed_user_ids ) && ! in_array( $user_id, $allowed_user_ids, true ) ) {
            return false;
        }

        if ( ! empty( $allowed_roles ) ) {
            $user_roles = (array) $user_obj->roles;
            $matched    = array_intersect( $user_roles, $allowed_roles );
            if ( empty( $matched ) ) {
                return false;
            }
        }

        if ( $required_tag !== '' ) {
            $user_tag = get_user_meta( $user_id, 'woo_fitroom_user_tag', true );
            if ( $user_tag !== $required_tag ) {
                return false;
            }
        }

        return true;
    }

    /**
     * Add theme detection class to body
     */
    public function add_theme_detection_class($classes) {
        $classes[] = 'tryon-theme-detection';
        return $classes;
    }

    public function add_modal_template() {
        if (!is_product()) {
            return;
        }

        if ( ! $this->current_user_can_use_feature() ) {
            return;
        }

        if ( ! get_option( 'WOO_FITROOM_preview_enabled' ) ) {
            return;
        }

        global $product;

        /* ------------------------------------------------------------------
         *  USER CONSENT HANDLING
         * ------------------------------------------------------------------
         *  We only ask for explicit consent once.  After the user has ticked
         *  the checkbox we store a time-stamp in user_meta so the checkbox
         *  never shows again.  (Guest users fall back to the front-end
         *  required attribute – no server-side record is stored.)
         * ------------------------------------------------------------------ */

        $require_consent = true;
        if ( is_user_logged_in() ) {
            // Back-compat: mirror legacy fashnai meta into fitroom if present
            $uid_bc = get_current_user_id();
            $legacy = get_user_meta( $uid_bc, 'woo_fashnai_user_consent', true );
            if ( $legacy && ! get_user_meta( $uid_bc, 'woo_fitroom_user_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_user_consent', $legacy );
            }
            $legacy_t = get_user_meta( $uid_bc, 'woo_fashnai_terms_consent', true );
            if ( $legacy_t && ! get_user_meta( $uid_bc, 'woo_fitroom_terms_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_terms_consent', $legacy_t );
            }
            $legacy_r = get_user_meta( $uid_bc, 'woo_fashnai_refund_consent', true );
            if ( $legacy_r && ! get_user_meta( $uid_bc, 'woo_fitroom_refund_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_refund_consent', $legacy_r );
            }
            $require_consent = ! (bool) get_user_meta( $uid_bc, 'woo_fitroom_user_consent', true );
        }
        // Pre-compute extra consents visibility so we can expose flags to JS
        $require_extra_consents = get_option('WOO_FITROOM_require_extra_consents');
        $terms_consent = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'woo_fitroom_terms_consent', true) : false;
        $refund_consent = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'woo_fitroom_refund_consent', true) : false;
        $show_extra_consents = ($require_extra_consents && is_user_logged_in() && ( ! $terms_consent || ! $refund_consent ));
        $product_image_url = '';
        if ($product) {
            $product_image_id = $product->get_image_id();
            if ($product_image_id) {
                $product_image_url = wp_get_attachment_url($product_image_id);
            } else {
                $gallery_image_ids = $product->get_gallery_image_ids();
                if (!empty($gallery_image_ids)) {
                    $product_image_url = wp_get_attachment_url($gallery_image_ids[0]);
                }
            }
            
            if (empty($product_image_url)) {
                $product_image_url = wc_placeholder_img_src('woocommerce_single');
            }
        }

        ?>
        <script>console.log('Modal template rendering started');</script>
        <div id="woo-fitroom-preview-modal" class="woo-fitroom-preview-modal" style="display: none;" data-require-consent="<?php echo $require_consent ? '1' : '0'; ?>" data-show-extra-consents="<?php echo $show_extra_consents ? '1' : '0'; ?>">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?php echo esc_html($this->get_button_text()); ?></h2>

                <!-- Product image preview removed as per user request -->
                
                <?php if ( is_user_logged_in() ) : ?>
                <div id="my_uploads_strip" class="my-uploads-strip" style="display:none; margin-bottom:12px;">
                    <div class="strip-title" style="font-size:12px; color:#555; margin-bottom:8px; display: flex; justify-content: space-between; align-items: center;">
                        <span><?php _e('My Uploads', 'woo-fitroom-preview'); ?></span>
                        <button type="button" id="delete_all_images_btn" class="delete-all-btn" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; display: none;">
                            <?php _e('Delete All', 'woo-fitroom-preview'); ?>
                        </button>
                    </div>
                    <div id="my_uploads_list" class="strip-list"></div>
                </div>
                <?php endif; ?>
                
                <form id="woo-fitroom-preview-form" enctype="multipart/form-data">
                    <div class="form-field">
                        <div class="upload-dropzone" id="user_image_dropzone">
                        <input type="file" 
                               id="user_image" 
                               name="user_image" 
                               accept="image/*,.heic,.heif,.avif,.jfif,.jpe,.jpeg,.jpg,.bmp,.tif,.tiff,.png,.gif,.webp"
                                   aria-label="<?php esc_attr_e('Upload your photo', 'woo-fitroom-preview'); ?>"
                               required>
                            <div class="dz-inner">
                                <div class="dz-icon" aria-hidden="true"></div>
                                <img class="dz-thumb" alt="" />
                                <div class="dz-copy">
                                    <div class="dz-title">
                                        <?php _e('Drop your image, or', 'woo-fitroom-preview'); ?>
                                        <a href="#" class="dz-browse"><?php _e('Browse', 'woo-fitroom-preview'); ?></a>
                                    </div>
                                    <div class="dz-hint"><?php _e('Use clear, sharp, front-facing images (no blur, text, or side views).', 'woo-fitroom-preview'); ?></div>
                                    <div class="dz-hint"><?php _e('Fix issues: Refresh, re-upload, or click "Try Again"', 'woo-fitroom-preview'); ?></div>
                                </div>
                            </div>
                        </div>
                        <p id="selected-photo-name" class="selected-photo-name" style="margin-top:5px; font-style:italic; color:#555;"></p>

                        <?php if ( $require_consent ) : ?>
                        <div class="form-field" style="margin-top:15px; margin-bottom:-15px !important;">
                            <label>
                                <input type="checkbox" id="user_consent" style="border: 1px solid;" name="user_consent" required>
                                <?php _e('I consent to the processing of my uploaded images for generating previews.', 'woo-fitroom-preview'); ?>
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php // variables already computed above: $show_extra_consents ?>
                    <?php if ($show_extra_consents) : ?>
                        <div class="form-field" style="margin-top:7px;">
                            <label>
                                <input type="checkbox" id="terms_consent" style="border: 1px solid;" name="terms_consent" required>
                                <?php _e('I agree to the Terms and Privacy Policy.', 'woo-fitroom-preview'); ?>
                            </label>
                            <label>
                                <input type="checkbox" id="refund_consent" style="border: 1px solid;" name="refund_consent" required>
                                <?php _e('I understand previews may be inaccurate and agree to the Refund Policy.', 'woo-fitroom-preview'); ?>
                            </label>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" id="product_image_url" name="product_image_url" value="<?php echo esc_attr($product_image_url); ?>">
                    <input type="hidden" id="product_id" name="product_id" value="<?php echo $product ? esc_attr($product->get_id()) : ''; ?>">
                    <input type="hidden" id="saved_user_image_url" name="saved_user_image_url" value="">
                    
                    <div class="form-submit">
                        <button type="submit" class="button alt">
                            <?php _e('Generate Preview', 'woo-fitroom-preview'); ?>
                        </button>
                    </div>
                </form>

                <div class="preview-result" style="display: none;">
                    <h3 class="preview-title"><?php _e('Your Try On Preview', 'woo-fitroom-preview'); ?></h3>
                    <div class="preview-image"></div>
                    <div class="preview-footer">
                        <div class="left-actions">
                        <button class="button download-preview">
                            <?php _e('Download Preview', 'woo-fitroom-preview'); ?>
                        </button>
                        </div>
                        <div class="right-actions preview-toolbar">
                            <button type="button" class="icon-btn share-preview" title="<?php esc_attr_e('Share', 'woo-fitroom-preview'); ?>" aria-label="<?php esc_attr_e('Share', 'woo-fitroom-preview'); ?>">
                                <span class="icon-svg share-icon" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="icon-btn regenerate-preview" title="<?php esc_attr_e('Regenerate', 'woo-fitroom-preview'); ?>" aria-label="<?php esc_attr_e('Regenerate', 'woo-fitroom-preview'); ?>">
                                <span class="icon-svg regenerate-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="preview-error" style="display: none;">
                    <p class="error-message"></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add the top/bottom modal template to footer
     */
    public function add_top_bottom_modal_template() {
        if (!is_product()) {
            return;
        }

        if ( ! $this->current_user_can_use_feature() ) {
            return;
        }

        if ( ! get_option( 'WOO_FITROOM_preview_enabled' ) ) {
            return;
        }

        global $product;

        /* ------------------------------------------------------------------
         *  USER CONSENT HANDLING (same as full outfit modal)
         * ------------------------------------------------------------------ */
        $require_consent = true;
        if ( is_user_logged_in() ) {
            $uid_bc = get_current_user_id();
            $legacy = get_user_meta( $uid_bc, 'woo_fashnai_user_consent', true );
            if ( $legacy && ! get_user_meta( $uid_bc, 'woo_fitroom_user_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_user_consent', $legacy );
            }
            $legacy_t = get_user_meta( $uid_bc, 'woo_fashnai_terms_consent', true );
            if ( $legacy_t && ! get_user_meta( $uid_bc, 'woo_fitroom_terms_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_terms_consent', $legacy_t );
            }
            $legacy_r = get_user_meta( $uid_bc, 'woo_fashnai_refund_consent', true );
            if ( $legacy_r && ! get_user_meta( $uid_bc, 'woo_fitroom_refund_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_refund_consent', $legacy_r );
            }
            $require_consent = ! (bool) get_user_meta( $uid_bc, 'woo_fitroom_user_consent', true );
        }
        
        $require_extra_consents = get_option('WOO_FITROOM_require_extra_consents');
        $terms_consent = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'woo_fitroom_terms_consent', true) : false;
        $refund_consent = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'woo_fitroom_refund_consent', true) : false;
        $show_extra_consents = ($require_extra_consents && is_user_logged_in() && ( ! $terms_consent || ! $refund_consent ));
        
        $product_image_url = '';
        if ($product) {
            $product_image_id = $product->get_image_id();
            if ($product_image_id) {
                $product_image_url = wp_get_attachment_url($product_image_id);
            } else {
                $gallery_image_ids = $product->get_gallery_image_ids();
                if (!empty($gallery_image_ids)) {
                    $product_image_url = wp_get_attachment_url($gallery_image_ids[0]);
                }
            }
            
            if (empty($product_image_url)) {
                $product_image_url = wc_placeholder_img_src('woocommerce_single');
            }
        }

        ?>
        <script>console.log('Top/Bottom Modal template rendering started');</script>
        <div id="woo-fitroom-preview-modal-top-bottom" class="woo-fitroom-preview-modal" style="display: none;" data-require-consent="<?php echo $require_consent ? '1' : '0'; ?>" data-show-extra-consents="<?php echo $show_extra_consents ? '1' : '0'; ?>">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?php echo esc_html($this->get_top_bottom_button_text()); ?></h2>


                <?php if ( is_user_logged_in() ) : ?>
                <div id="my_uploads_strip_top_bottom" class="my-uploads-strip" style="display:none; margin-bottom:12px;">
                    <div class="strip-title" style="font-size:12px; color:#555; margin-bottom:8px; display: flex; justify-content: space-between; align-items: center;">
                        <span><?php _e('My Uploads', 'woo-fitroom-preview'); ?></span>
                        <button type="button" id="delete_all_images_btn_top_bottom" class="delete-all-btn" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; display: none;">
                            <?php _e('Delete All', 'woo-fitroom-preview'); ?>
                        </button>
                    </div>
                    <div id="my_uploads_list_top_bottom" class="strip-list"></div>
                </div>
                <?php endif; ?>
                
                <form id="woo-fitroom-preview-form-top-bottom" enctype="multipart/form-data">
                    <div class="form-field">
                        <div class="upload-dropzone" id="user_image_dropzone_top_bottom">
                        <input type="file" 
                               id="user_image_top_bottom" 
                               name="user_image" 
                               accept="image/*,.heic,.heif,.avif,.jfif,.jpe,.jpeg,.jpg,.bmp,.tif,.tiff,.png,.gif,.webp"
                                   aria-label="<?php esc_attr_e('Upload your photo', 'woo-fitroom-preview'); ?>"
                               required>
                            <div class="dz-inner">
                                <div class="dz-icon" aria-hidden="true"></div>
                                <img class="dz-thumb" alt="" />
                                <div class="dz-copy">
                                    <div class="dz-title">
                                        <?php _e('Drop your image, or', 'woo-fitroom-preview'); ?>
                                        <a href="#" class="dz-browse"><?php _e('Browse', 'woo-fitroom-preview'); ?></a>
                                    </div>
                                    <div class="dz-hint"><?php _e('Use clear, sharp, front-facing images (no blur, text, or side views).', 'woo-fitroom-preview'); ?></div>
                                    <div class="dz-hint"><?php _e('Fix issues: Refresh, re-upload, or click "Try Again"', 'woo-fitroom-preview'); ?></div>
                                </div>
                            </div>
                        </div>
                        <p id="selected-photo-name-top-bottom" class="selected-photo-name" style="margin-top:5px; font-style:italic; color:#555;"></p>

                        <?php if ( $require_consent ) : ?>
                        <div class="form-field" style="margin-top:15px; margin-bottom:-15px !important;">
                            <label>
                                <input type="checkbox" id="user_consent_top_bottom" style="border: 1px solid;" name="user_consent" required>
                                <?php _e('I consent to the processing of my uploaded images for generating previews.', 'woo-fitroom-preview'); ?>
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($show_extra_consents) : ?>
                        <div class="form-field" style="margin-top:7px;">
                            <label>
                                <input type="checkbox" id="terms_consent_top_bottom" style="border: 1px solid;" name="terms_consent" required>
                                <?php _e('I agree to the Terms and Privacy Policy.', 'woo-fitroom-preview'); ?>
                            </label>
                            <label>
                                <input type="checkbox" id="refund_consent_top_bottom" style="border: 1px solid;" name="refund_consent" required>
                                <?php _e('I understand previews may be inaccurate and agree to the Refund Policy.', 'woo-fitroom-preview'); ?>
                            </label>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" id="product_image_url_top_bottom" name="product_image_url" value="<?php echo esc_attr($product_image_url); ?>">
                    <input type="hidden" id="product_id_top_bottom" name="product_id" value="<?php echo $product ? esc_attr($product->get_id()) : ''; ?>">
                    <input type="hidden" id="saved_user_image_url_top_bottom" name="saved_user_image_url" value="">
                    <input type="hidden" id="preview_type_top_bottom" name="preview_type" value="top-bottom">
                    
                    <div class="form-submit">
                        <button type="submit" class="button alt">
                                <?php _e('Generate Preview', 'woo-fitroom-preview'); ?>
                        </button>
                    </div>
                </form>

                <div class="preview-result" style="display: none;">
                    <h3 class="preview-title"><?php _e('Your Try On Preview', 'woo-fitroom-preview'); ?></h3>
                    <div class="preview-image"></div>
                    <div class="preview-footer">
                        <div class="left-actions">
                        <button class="button download-preview">
                            <?php _e('Download Preview', 'woo-fitroom-preview'); ?>
                        </button>
                        <button type="button" class="try-full-outfit-btn button" style="margin-left: 10px;">
                            <?php _e('Try Full Outfit', 'woo-fitroom-preview'); ?>
                        </button>
                        </div>
                        <div class="right-actions preview-toolbar">
                            <button type="button" class="icon-btn share-preview" title="<?php esc_attr_e('Share', 'woo-fitroom-preview'); ?>" aria-label="<?php esc_attr_e('Share', 'woo-fitroom-preview'); ?>">
                                <span class="icon-svg share-icon" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="icon-btn regenerate-preview" title="<?php esc_attr_e('Regenerate', 'woo-fitroom-preview'); ?>" aria-label="<?php esc_attr_e('Regenerate', 'woo-fitroom-preview'); ?>">
                                <span class="icon-svg regenerate-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="preview-error" style="display: none;">
                    <p class="error-message"></p>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Add the bottom modal template to footer
     */
    public function add_bottom_modal_template() {
        if (!is_product()) {
            return;
        }

        if ( ! $this->current_user_can_use_feature() ) {
            return;
        }

        if ( ! get_option( 'WOO_FITROOM_preview_enabled' ) ) {
            return;
        }

        global $product;

        /* ------------------------------------------------------------------
         *  USER CONSENT HANDLING (same as full outfit modal)
         * ------------------------------------------------------------------ */
        $require_consent = true;
        if ( is_user_logged_in() ) {
            $uid_bc = get_current_user_id();
            $legacy = get_user_meta( $uid_bc, 'woo_fashnai_user_consent', true );
            if ( $legacy && ! get_user_meta( $uid_bc, 'woo_fitroom_user_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_user_consent', $legacy );
            }
            $legacy_t = get_user_meta( $uid_bc, 'woo_fashnai_terms_consent', true );
            if ( $legacy_t && ! get_user_meta( $uid_bc, 'woo_fitroom_terms_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_terms_consent', $legacy_t );
            }
            $legacy_r = get_user_meta( $uid_bc, 'woo_fashnai_refund_consent', true );
            if ( $legacy_r && ! get_user_meta( $uid_bc, 'woo_fitroom_refund_consent', true ) ) {
                update_user_meta( $uid_bc, 'woo_fitroom_refund_consent', $legacy_r );
            }
            $require_consent = ! (bool) get_user_meta( $uid_bc, 'woo_fitroom_user_consent', true );
        }
        
        $require_extra_consents = get_option('WOO_FITROOM_require_extra_consents');
        $terms_consent = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'woo_fitroom_terms_consent', true) : false;
        $refund_consent = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'woo_fitroom_refund_consent', true) : false;
        $show_extra_consents = ($require_extra_consents && is_user_logged_in() && ( ! $terms_consent || ! $refund_consent ));
        
        $product_image_url = '';
        if ($product) {
            $product_image_id = $product->get_image_id();
            if ($product_image_id) {
                $product_image_url = wp_get_attachment_url($product_image_id);
            } else {
                $gallery_image_ids = $product->get_gallery_image_ids();
                if (!empty($gallery_image_ids)) {
                    $product_image_url = wp_get_attachment_url($gallery_image_ids[0]);
                }
            }
            
            if (empty($product_image_url)) {
                $product_image_url = wc_placeholder_img_src('woocommerce_single');
            }
        }

        ?>
        <script>console.log('Bottom Modal template rendering started');</script>
        <div id="woo-fitroom-preview-modal-bottom" class="woo-fitroom-preview-modal" style="display: none;" data-require-consent="<?php echo $require_consent ? '1' : '0'; ?>" data-show-extra-consents="<?php echo $show_extra_consents ? '1' : '0'; ?>">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h2><?php echo esc_html($this->get_bottom_button_text()); ?></h2>


                <?php if ( is_user_logged_in() ) : ?>
                <div id="my_uploads_strip_bottom" class="my-uploads-strip" style="display:none; margin-bottom:12px;">
                    <div class="strip-title" style="font-size:12px; color:#555; margin-bottom:8px; display: flex; justify-content: space-between; align-items: center;">
                        <span><?php _e('My Uploads', 'woo-fitroom-preview'); ?></span>
                        <button type="button" id="delete_all_images_btn_bottom" class="delete-all-btn" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 11px; cursor: pointer; display: none;">
                            <?php _e('Delete All', 'woo-fitroom-preview'); ?>
                        </button>
                    </div>
                    <div id="my_uploads_list_bottom" class="strip-list"></div>
                </div>
                <?php endif; ?>
                
                <form id="woo-fitroom-preview-form-bottom" enctype="multipart/form-data">
                    <div class="form-field">
                        <div class="upload-dropzone" id="user_image_dropzone_bottom">
                        <input type="file" 
                               id="user_image_bottom" 
                               name="user_image" 
                               accept="image/*,.heic,.heif,.avif,.jfif,.jpe,.jpeg,.jpg,.bmp,.tif,.tiff,.png,.gif,.webp"
                                   aria-label="<?php esc_attr_e('Upload your photo', 'woo-fitroom-preview'); ?>"
                               required>
                            <div class="dz-inner">
                                <div class="dz-icon" aria-hidden="true"></div>
                                <img class="dz-thumb" alt="" />
                                <div class="dz-copy">
                                    <div class="dz-title">
                                        <?php _e('Drop your image, or', 'woo-fitroom-preview'); ?>
                                        <a href="#" class="dz-browse"><?php _e('Browse', 'woo-fitroom-preview'); ?></a>
                                    </div>
                                    <div class="dz-hint"><?php _e('Use clear, sharp, front-facing images (no blur, text, or side views).', 'woo-fitroom-preview'); ?></div>
                                    <div class="dz-hint"><?php _e('Fix issues: Refresh, re-upload, or click "Try Again"', 'woo-fitroom-preview'); ?></div>
                                </div>
                            </div>
                        </div>
                        <p id="selected-photo-name-bottom" class="selected-photo-name" style="margin-top:5px; font-style:italic; color:#555;"></p>

                        <?php if ( $require_consent ) : ?>
                        <div class="form-field" style="margin-top:15px; margin-bottom:-15px !important;">
                            <label>
                                <input type="checkbox" id="user_consent_bottom" style="border: 1px solid;" name="user_consent" required>
                                <?php _e('I consent to the processing of my uploaded images for generating previews.', 'woo-fitroom-preview'); ?>
                            </label>
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php if ($show_extra_consents) : ?>
                        <div class="form-field" style="margin-top:7px;">
                            <label>
                                <input type="checkbox" id="terms_consent_bottom" style="border: 1px solid;" name="terms_consent" required>
                                <?php _e('I agree to the Terms and Privacy Policy.', 'woo-fitroom-preview'); ?>
                            </label>
                            <label>
                                <input type="checkbox" id="refund_consent_bottom" style="border: 1px solid;" name="refund_consent" required>
                                <?php _e('I understand previews may be inaccurate and agree to the Refund Policy.', 'woo-fitroom-preview'); ?>
                            </label>
                        </div>
                    <?php endif; ?>

                    <input type="hidden" id="product_image_url_bottom" name="product_image_url" value="<?php echo esc_attr($product_image_url); ?>">
                    <input type="hidden" id="product_id_bottom" name="product_id" value="<?php echo $product ? esc_attr($product->get_id()) : ''; ?>">
                    <input type="hidden" id="saved_user_image_url_bottom" name="saved_user_image_url" value="">
                    <input type="hidden" id="preview_type_bottom" name="preview_type" value="bottom">
                    
                    <div class="form-submit">
                        <button type="submit" class="button alt">
                            <?php _e('Generate Preview', 'woo-fitroom-preview'); ?>
                        </button>
                    </div>
                </form>

                <div class="preview-result" style="display: none;">
                    <h3 class="preview-title"><?php _e('Your Try On Preview', 'woo-fitroom-preview'); ?></h3>
                    <div class="preview-image"></div>
                    <div class="preview-footer">
                        <div class="left-actions">
                        <button class="button download-preview">
                            <?php _e('Download Preview', 'woo-fitroom-preview'); ?>
                        </button>
                        <button type="button" class="button try-full-outfit-btn" style="margin-left: 10px;">
                            <?php _e('Try Full Outfit', 'woo-fitroom-preview'); ?>
                        </button>
                        </div>
                        <div class="right-actions preview-toolbar">
                            <button type="button" class="icon-btn share-preview" title="<?php esc_attr_e('Share', 'woo-fitroom-preview'); ?>" aria-label="<?php esc_attr_e('Share', 'woo-fitroom-preview'); ?>">
                                <span class="icon-svg share-icon" aria-hidden="true"></span>
                            </button>
                            <button type="button" class="icon-btn regenerate-preview" title="<?php esc_attr_e('Regenerate', 'woo-fitroom-preview'); ?>" aria-label="<?php esc_attr_e('Regenerate', 'woo-fitroom-preview'); ?>">
                                <span class="icon-svg regenerate-icon" aria-hidden="true"></span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="preview-error" style="display: none;">
                    <p class="error-message"></p>
                </div>
            </div>
        </div>
        <?php
    }

    public function enqueue_assets() {
        // Add theme detection class to body
        add_filter('body_class', array($this, 'add_theme_detection_class'));
        if (!is_product()) {
            return;
        }

        if ( ! get_option( 'WOO_FITROOM_preview_enabled' ) ) {
            return;
        }

        wp_enqueue_style(
            'woo-fitroom-preview-product',
            WOO_FITROOM_PREVIEW_PLUGIN_URL . 'assets/css/product-preview.css',
            array(),
            WOO_FITROOM_PREVIEW_VERSION
        );

        wp_enqueue_script(
            'woo-fitroom-preview-product',
            WOO_FITROOM_PREVIEW_PLUGIN_URL . 'assets/js/product-preview.js',
            array( 'jquery', 'wp-i18n' ),
            WOO_FITROOM_PREVIEW_VERSION,
            true
        );

        wp_localize_script(
            'woo-fitroom-preview-product',
            'WooFitroomPreview',
            array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('woo_fitroom_preview_nonce'),
                'credits' => get_user_credits(),
                'user_id' => get_current_user_id(),
                'use_custom_color' => get_option('WOO_FITROOM_use_theme_colors', true) ? '1' : '0',
                'custom_color' => get_option('WOO_FITROOM_custom_button_color', '#FF6E0E'),
                'use_custom_padding' => get_option('WOO_FITROOM_use_theme_padding', true) ? '1' : '0',
                'custom_padding' => array(
                    'top' => get_option('WOO_FITROOM_padding_top', 12),
                    'right' => get_option('WOO_FITROOM_padding_right', 20),
                    'bottom' => get_option('WOO_FITROOM_padding_bottom', 12),
                    'left' => get_option('WOO_FITROOM_padding_left', 20)
                ),
                'use_custom_border_radius' => get_option('WOO_FITROOM_use_theme_border_radius', true) ? '1' : '0',
                'custom_border_radius' => array(
                    'top_left' => get_option('WOO_FITROOM_border_radius_top_left', 50),
                    'top_right' => get_option('WOO_FITROOM_border_radius_top_right', 50),
                    'bottom_left' => get_option('WOO_FITROOM_border_radius_bottom_left', 50),
                    'bottom_right' => get_option('WOO_FITROOM_border_radius_bottom_right', 50)
                ),
                'custom_button_text' => $this->get_button_text(),
                'i18n' => array(
                    'processing' => __('Generating preview...', 'woo-fitroom-preview'),
                    'error' => __('Error generating preview', 'woo-fitroom-preview'),
                    'success' => __('Generate Preview', 'woo-fitroom-preview'),
                    'out_of_credits' => __('You are out of credits. Please purchase more to continue.', 'woo-fitroom-preview')
                )
            )
        );
    }

    public function handle_preview_generation() {
        error_log('WooTryOnTool Plugin: Preview generation request received');
        
        error_log('WooTryOnTool Plugin: POST data: ' . print_r($_POST, true));
        error_log('WooTryOnTool Plugin: FILES data: ' . print_r($_FILES, true));
        
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'woo_fitroom_preview_nonce')) {
            error_log('WooTryOnTool Plugin: Nonce verification failed');
            wp_send_json_error(array('message' => __('Security check failed', 'woo-tryontool-preview')));
        }

        /* ─────────────────────────────────────────────────────────
         *  CONSENT ENFORCEMENT
         * ---------------------------------------------------------
         *  We require each logged-in user to provide explicit
         *  consent before the first preview generation.  The time-
         *  stamp is stored in user_meta and updated on subsequent
         *  generations so we can display the "last consent" date in
         *  the admin back-end if needed.
         * ──────────────────────────────────────────────────────── */

        if ( is_user_logged_in() ) {
            $uid          = get_current_user_id();
            $existing_ts  = get_user_meta( $uid, 'woo_fitroom_user_consent', true );

            if ( ! $existing_ts ) {
                // Ensure checkbox ticked for very first preview.
                if ( empty( $_POST['user_consent'] ) ) {
                    wp_send_json_error( array( 'message' => __( 'Please provide consent before generating a preview.', 'woo-fitroom-preview' ) ) );
                }

                // Record consent *once* – do NOT overwrite later.
                $now_mysql = current_time( 'mysql' );
                update_user_meta( $uid, 'woo_fitroom_user_consent', $now_mysql );

                // Site-wide consent registry
                $consents   = get_option( 'WOO_FITROOM_consents', array() );
                $user_obj   = wp_get_current_user();
                
                // Get additional consent data
                $terms_consent = get_user_meta($uid, 'woo_fitroom_terms_consent', true);
                $refund_consent = get_user_meta($uid, 'woo_fitroom_refund_consent', true);
                
                // Determine consolidated consent status
                $require_extra_consents = get_option('WOO_FITROOM_require_extra_consents');
                $consolidated_consent_date = $now_mysql; // Main consent is always given at this point
                
                // If extra consents are required, use the latest consent date
                if ($require_extra_consents) {
                    $consent_dates = array_filter([$now_mysql, $terms_consent, $refund_consent]);
                    if (!empty($consent_dates)) {
                        $consolidated_consent_date = max($consent_dates);
                    }
                }
                
                $consents[ $uid ] = array(
                    'user_id'          => $uid,
                    'email'            => $user_obj ? $user_obj->user_email : '',
                    'consent_timestamp'=> $consolidated_consent_date,
                    'terms_consent'    => $terms_consent ? $terms_consent : '',
                    'refund_consent'   => $refund_consent ? $refund_consent : '',
                    'last_login'       => get_user_meta($uid, 'last_login', true) ?: '',
                    'site_url'         => parse_url(home_url(), PHP_URL_HOST), // Ensure site URL is set for analytics
                    // last_login added via the wp_login hook
                );
                update_option( 'WOO_FITROOM_consents', $consents, false );
                // Clear cache after updating
                wp_cache_delete('WOO_FITROOM_consents', 'options');
            }
        }

        $require_extra_consents = get_option('WOO_FITROOM_require_extra_consents');
        if ($require_extra_consents && is_user_logged_in()) {
            $uid = get_current_user_id();
            $has_terms   = (bool) get_user_meta($uid, 'woo_fitroom_terms_consent', true);
            $has_refund  = (bool) get_user_meta($uid, 'woo_fitroom_refund_consent', true);

            // Only require checkboxes if not previously recorded
            if (!$has_terms) {
                if (empty($_POST['terms_consent'])) {
                    wp_send_json_error(array('message' => __('You must agree to the Terms of Use and Privacy Policy.', 'woo-fitroom-preview')));
                }
                update_user_meta($uid, 'woo_fitroom_terms_consent', current_time('mysql'));
            }
            if (!$has_refund) {
                if (empty($_POST['refund_consent'])) {
                    wp_send_json_error(array('message' => __('You must agree to the Refund Policy.', 'woo-fitroom-preview')));
                }
                update_user_meta($uid, 'woo_fitroom_refund_consent', current_time('mysql'));
            }
            
            // Update consent registry with latest terms and refund consent data
            $consents = get_option('WOO_FITROOM_consents', array());
            if (isset($consents[$uid])) {
                $consents[$uid]['terms_consent'] = get_user_meta($uid, 'woo_fitroom_terms_consent', true) ?: '';
                $consents[$uid]['refund_consent'] = get_user_meta($uid, 'woo_fitroom_refund_consent', true) ?: '';
                $consents[$uid]['site_url'] = parse_url(home_url(), PHP_URL_HOST); // Ensure site URL is set
                
                // Update consolidated consent date to the latest consent given
                $consent_dates = array_filter([
                    $consents[$uid]['consent_timestamp'],
                    $consents[$uid]['terms_consent'],
                    $consents[$uid]['refund_consent']
                ]);
                if (!empty($consent_dates)) {
                    $consents[$uid]['consent_timestamp'] = max($consent_dates);
                }
                
                update_option('WOO_FITROOM_consents', $consents, false);
                // Clear cache after updating
                wp_cache_delete('WOO_FITROOM_consents', 'options');
            }
        }

        // Determine source of user image: fresh upload or previously saved URL
        $using_saved_image = false;

        if (!isset($_FILES['user_image']) || $_FILES['user_image']['error'] !== UPLOAD_ERR_OK) {
            // No fresh upload – try saved image URL
            if (!empty($_POST['saved_user_image_url'])) {
                $incoming = trim((string) $_POST['saved_user_image_url']);
                $remote_url = '';
                // If it's an absolute URL, use as-is; if it's a path, prefix with home_url
                if (preg_match('#^https?://#i', $incoming)) {
                    $remote_url = esc_url_raw($incoming);
                } else {
                    $remote_url = esc_url_raw(home_url($incoming));
                }

                // If the URL points to Wasabi, route through our public proxy endpoint for reliability
                if (strpos($remote_url, 'wasabisys.com') !== false) {
                    // ensure client initialised to know bucket name
                    if (!class_exists('WooFITROOM_Wasabi')) {
                        require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-wasabi-client.php';
                    }
                    $bucket = method_exists('WooFITROOM_Wasabi','bucket') ? WooFITROOM_Wasabi::bucket() : '';
                    $pattern = '#https?://[^/]+/' . preg_quote($bucket, '#') . '/#';
                    $key = preg_replace($pattern, '', $remote_url);
                    if ($key === $remote_url) {
                        // fallback: strip host and first segment (bucket)
                        $parsed = parse_url($remote_url);
                        $path = isset($parsed['path']) ? ltrim($parsed['path'],'/') : '';
                        $parts = explode('/', $path, 2);
                        $key = isset($parts[1]) ? $parts[1] : $path;
                    }
                    $remote_url = home_url('/wp-json/woo-tryontool/v1/wasabi-image?key=' . rawurlencode($key));
                }

                error_log('Attempting to download image from URL: ' . $remote_url);
                
                if (!function_exists('download_url')) {
                    require_once ABSPATH . 'wp-admin/includes/file.php';
                }
                
                $tmp_file = download_url($remote_url, 60); // Increase timeout if needed

                if (is_wp_error($tmp_file)) {
                    error_log('Error downloading image: ' . $tmp_file->get_error_message());
                    wp_send_json_error(array('message' => __('Could not retrieve saved image. Please upload again.', 'woo-tryontool-preview')));
                }

                // Craft a pseudo $_FILES entry so later code works the same
                $_FILES['user_image'] = array(
                    'name' => wp_basename($remote_url),
                    'full_path' => wp_basename($remote_url),
                    'type' => 'image/jpeg',
                    'tmp_name' => $tmp_file,
                    'error' => 0,
                    'size' => filesize($tmp_file),
                );

                $using_saved_image = true;
            } else {
                wp_send_json_error(array('message' => __('Please upload an image', 'woo-tryontool-preview')));
            }
        }

        if (empty($_POST['product_image_url'])) {
            error_log('WooTryOnTool Plugin: Product image URL is missing');
            wp_send_json_error(array('message' => __('Product image URL is missing', 'woo-tryontool-preview')));
        }

        if ( ! $this->current_user_can_use_feature() ) {
            error_log('WooTryOnTool Plugin: User not permitted – access control');
            wp_send_json_error(array( 'message' => __( 'You are not allowed to use this feature.', 'woo-tryontool-preview' ) ) );
        }

        $daily_limit = absint(get_option('WOO_FITROOM_daily_credits', 0));
        if ($daily_limit > 0 && is_user_logged_in()) {
            $user_id       = get_current_user_id();
            $today         = date('Y-m-d');
            $meta_key_date = 'woo_fitroom_daily_date';
            $meta_key_used = 'woo_fitroom_daily_used';

            $stored_date = get_user_meta($user_id, $meta_key_date, true);
            $used        = intval(get_user_meta($user_id, $meta_key_used, true));

            if ($stored_date !== $today) {
                update_user_meta($user_id, $meta_key_date, $today);
                $used = 0;
                update_user_meta($user_id, $meta_key_used, 0);
            }

            if ($used >= $daily_limit) {
                error_log('WooTryOnTool Plugin: Daily limit reached for user ' . $user_id);
                wp_send_json_error(array('message' => __('Daily quota exceeded, please try again tomorrow.', 'woo-tryontool-preview')));
            }
        }

        $original_file_path = $_FILES['user_image']['tmp_name'];
        
        // Always convert to JPEG for backend consistency
        $converted_file_path = $this->convert_to_jpeg($original_file_path);
        $uploaded_file_path = $converted_file_path;

        // Save permanent copy for logged-in users (only when fresh upload)
        $uploaded_photo_final_url = null;
        $temp_files_to_cleanup = array(); // Track temp files for cleanup
        
        // If conversion created a new file (not the original), mark it for cleanup
        if ($converted_file_path !== $original_file_path && file_exists($converted_file_path)) {
            $temp_files_to_cleanup[] = $converted_file_path;
        }
        
        if (is_user_logged_in() && !$using_saved_image) {
            $user_id = get_current_user_id();
            
            // Check if file exists before uploading
            if (!file_exists($uploaded_file_path)) {
                error_log('Wasabi Upload Error: Converted file not found at: ' . $uploaded_file_path);
                wp_send_json_error(array('message' => __('Failed to convert image. Please try a different format.', 'woo-tryontool-preview')));
            }
            
            error_log('Wasabi Upload: Calling WooFITROOM_Wasabi::upload()');
            $url = WooFITROOM_Wasabi::upload( $user_id, $uploaded_file_path );
            
            if ( $url ) {
                $uploaded_photo_final_url = $url;
                error_log('Wasabi Upload: Success - URL returned: ' . $url);
            } else {
                error_log('Wasabi Upload: FAILED - No URL returned. Upload did not succeed but continuing with preview generation.');
                // Don't block preview generation if Wasabi upload fails
            }
        } else {
            error_log('Wasabi Upload: Skipped - User not logged in OR using saved image');
        }

        try {
            $api_handler = new WooFitroomPreview_API_Handler();
            
            $product_category = '';
            $product_id = isset($_POST['product_id']) ? absint($_POST['product_id']) : 0;
            if ($product_id > 0) {
                $product = wc_get_product($product_id);
                if ($product) {
                    $category_ids = $product->get_category_ids();
                    if (!empty($category_ids)) {
                        $primary_cat_id = $category_ids[0];
                        $term = get_term($primary_cat_id, 'product_cat');
                        if ($term && !is_wp_error($term)) {
                            $product_category = strtolower($term->name);
                        }
                    }
                    if (empty($product_category)) {
                        $product_category = $product->get_type(); 
                    }
                }
            }
            error_log('WooTryOnTool Plugin: Determined product category/type: ' . $product_category);
            
            // Determine preview type and map category accordingly
            $preview_type = isset($_POST['preview_type']) ? sanitize_text_field($_POST['preview_type']) : 'full-outfit';
            $mapped_category = 'auto';
            
            // Get category assignment from database if available
            $category_assignment = $this->get_category_assignment_from_db($product_category);
            
            error_log('WooTryOnTool Plugin: Product category: ' . $product_category);
            error_log('WooTryOnTool Plugin: Category assignment from DB: ' . ($category_assignment ? $category_assignment : 'NULL'));
            
            if ($preview_type === 'top-bottom') {
                // SMART TOP/BOTTOM LOGIC:
                // - If product is TOP → send same user image as BOTTOM to API (replace bottom, keep top)
                // - If product is BOTTOM → send same user image as TOP to API (replace top, keep bottom)
                
                $product_is_top = false;
                $product_is_bottom = false;
                
                if ($category_assignment) {
                    // Use database assignment if available
                    if ($category_assignment === 'top') {
                        $product_is_top = true;
                        // If product is TOP, send user image as BOTTOM to API
                        $mapped_category = 'Bottom';
                    } elseif ($category_assignment === 'bottom') {
                        $product_is_bottom = true;
                        // If product is BOTTOM, send user image as TOP to API
                        $mapped_category = 'Top';
                    } else {
                        // For full outfit categories in top-bottom mode, default to tops
                        $product_is_top = true;
                        $mapped_category = 'Bottom';
                    }
                } else {
                    // Fallback to keyword matching
                if ($product_category) {
                    $cat_lc = strtolower($product_category);
                    $tops_keywords = array('top', 'shirt', 't-shirt', 'blouse', 'sweater', 'hoodie', 'jacket', 'coat');
                    $bottoms_keywords = array('pant', 'trouser', 'jean', 'skirt', 'short', 'bottom');

                    foreach ($tops_keywords as $kw) {
                        if (strpos($cat_lc, $kw) !== false) {
                                $product_is_top = true;
                                // If product is TOP, send user image as BOTTOM to API
                                $mapped_category = 'Bottom';
                            break;
                        }
                    }
                    if ($mapped_category === 'auto') {
                        foreach ($bottoms_keywords as $kw) {
                            if (strpos($cat_lc, $kw) !== false) {
                                    $product_is_bottom = true;
                                    // If product is BOTTOM, send user image as TOP to API
                                    $mapped_category = 'Top';
                                break;
                            }
                        }
                    }
                }
                    // If we can't determine from category, default to 'Bottom' for top/bottom preview
                if ($mapped_category === 'auto') {
                        $product_is_top = true;
                        $mapped_category = 'Bottom';
                    }
                }
                
                error_log('WooTryOnTool Plugin: Top/Bottom mode - Product is ' . ($product_is_top ? 'TOP' : ($product_is_bottom ? 'BOTTOM' : 'UNKNOWN')) . ', mapped to: ' . $mapped_category);
                error_log('WooTryOnTool Plugin: Will send to API - user_category: ' . ($mapped_category === 'Top' ? 'Bottom' : 'Top') . ', garment_category: ' . $mapped_category);
            } elseif ($preview_type === 'bottom') {
                // BOTTOM-ONLY LOGIC:
                // Always send user image as TOP and product image as BOTTOM
                // This will replace only the bottom, keep user's top
                
                $mapped_category = 'Bottom';
                error_log('WooTryOnTool Plugin: Bottom-only mode - Will replace BOTTOM only (keep user top)');
                error_log('WooTryOnTool Plugin: Will send to API - user_category: Top, garment_category: Bottom');
            } else {
                // For full outfit preview, use database assignment or fallback logic
                if ($category_assignment) {
                    // Use database assignment if available
                    if ($category_assignment === 'top') {
                        $mapped_category = 'Top';
                    } elseif ($category_assignment === 'bottom') {
                        $mapped_category = 'Bottom';
                    } elseif ($category_assignment === 'full') {
                        $mapped_category = 'Full Outfit';
                    }
                } else {
                    // Fallback to keyword matching
                if ($product_category) {
                    $cat_lc = strtolower($product_category);
                    $tops_keywords = array('top', 'shirt', 't-shirt', 'blouse', 'sweater', 'hoodie', 'jacket', 'coat');
                    $bottoms_keywords = array('pant', 'trouser', 'jean', 'skirt', 'short', 'bottom');
                    $onepiece_keywords = array('dress', 'jumpsuit', 'overall', 'onesie', 'one-piece');

                    foreach ($tops_keywords as $kw) {
                        if (strpos($cat_lc, $kw) !== false) {
                                $mapped_category = 'Top';
                            break;
                        }
                    }
                    if ($mapped_category === 'auto') {
                        foreach ($bottoms_keywords as $kw) {
                            if (strpos($cat_lc, $kw) !== false) {
                                    $mapped_category = 'Bottom';
                                break;
                            }
                        }
                    }
                    if ($mapped_category === 'auto') {
                        foreach ($onepiece_keywords as $kw) {
                            if (strpos($cat_lc, $kw) !== false) {
                                    $mapped_category = 'Full Outfit';
                                break;
                                }
                            }
                        }
                    }
                }
            }
            error_log('WooTryOnTool Plugin: Mapped category to TryOnTool value: ' . $mapped_category);
            error_log('WooTryOnTool Plugin: Preview type: ' . $preview_type);
            if ($preview_type === 'top-bottom') {
                error_log('WooTryOnTool Plugin: Top/Bottom mode - This will ' . ($mapped_category === 'Bottom' ? 'REPLACE TOP only (keep user bottom)' : 'REPLACE BOTTOM only (keep user top)'));
            } elseif ($preview_type === 'bottom') {
                error_log('WooTryOnTool Plugin: Bottom-only mode - This will REPLACE BOTTOM only (keep user top)');
            }
            
            error_log('WooTryOnTool Plugin: Calling API with product image: ' . $_POST['product_image_url']);
            
            // For top-bottom preview, we need to modify the API call based on product category
            if ($preview_type === 'top-bottom') {
                if ($mapped_category === 'Bottom') {
                    // Product is TOP category → send user image as BOTTOM, product as TOP
                    // This will replace only the top, keep user's bottom
                    error_log('WooTryOnTool Plugin: Top/Bottom mode - Product is TOP, sending user image as BOTTOM to API');
                    $response = $api_handler->generate_preview_top_bottom(
                        $uploaded_file_path, // user image as bottom
                        $_POST['product_image_url'], // product image as top
                        'Bottom', // category for user image (bottom)
                        'Top', // category for product image (top)
                        array()
                    );
                } else {
                    // Product is BOTTOM category → send user image as TOP, product as BOTTOM
                    // This will replace only the bottom, keep user's top
                    error_log('WooTryOnTool Plugin: Top/Bottom mode - Product is BOTTOM, sending user image as TOP to API');
                    $response = $api_handler->generate_preview_top_bottom(
                        $uploaded_file_path, // user image as top
                        $_POST['product_image_url'], // product image as bottom
                        'Top', // category for user image (top)
                        'Bottom', // category for product image (bottom)
                        array()
                    );
                }
            } elseif ($preview_type === 'bottom') {
                // Bottom-only preview: Always send user image as TOP, product as BOTTOM
                // This will replace only the bottom, keep user's top
                error_log('WooTryOnTool Plugin: Bottom-only mode - Sending user image as TOP, product as BOTTOM to API');
                $response = $api_handler->generate_preview_top_bottom(
                    $uploaded_file_path, // user image as top
                    $_POST['product_image_url'], // product image as bottom
                    'Top', // category for user image (top)
                    'Bottom', // category for product image (bottom)
                    array()
                );
            } else {
                // Regular full outfit preview
            $response = $api_handler->generate_preview(
                $uploaded_file_path,
                $_POST['product_image_url'],
                $mapped_category,
                array()
            );
            }

            error_log('WooTryOnTool Plugin: API response: ' . print_r($response, true));

            if (is_wp_error($response)) {
                error_log('WooTryOnTool Plugin: API error: ' . $response->get_error_message());
                
                $upload_dir = wp_upload_dir();
                $debug_dir = $upload_dir['basedir'] . '/woo-fitroom-debug';
                if (!file_exists($debug_dir)) {
                    wp_mkdir_p($debug_dir);
                }
                
                $error_data = array(
                    'message' => $response->get_error_message(),
                    'code' => $response->get_error_code(),
                    'data' => $response->get_error_data()
                );
                
                file_put_contents(
                    $debug_dir . '/error-' . time() . '.json',
                    json_encode($error_data, JSON_PRETTY_PRINT)
                );
                
                $original_error_message = $response->get_error_message();
                if (strpos(strtolower($original_error_message), 'segmentation failed') !== false || strpos(strtolower($original_error_message), 'try-on failed') !== false) {
                    $user_friendly_message = __('Could not generate the try-on preview. The user or product image might not be suitable. Try a different photo.', 'woo-tryontool-preview');
                } else if (strpos(strtolower($original_error_message), 'timeout') !== false) {
                     $user_friendly_message = __('The request timed out. Please try again later.', 'woo-tryontool-preview');
                } else { 
                    $user_friendly_message = $original_error_message; 
                }
                
                wp_send_json_error(array(
                    'message' => $user_friendly_message,
                    'debug_code' => $response->get_error_code()
                ));
            }
            
            if (isset($response['image_url']) && !empty($response['image_url'])) {
                 error_log('WooTryOnTool Plugin: Found image URL in response: ' . $response['image_url']);

                $daily_limit = absint(get_option('WOO_FITROOM_daily_credits', 0));
                if ($daily_limit > 0 && is_user_logged_in()) {
                    $user_id       = get_current_user_id();
                    $meta_key_used = 'woo_fitroom_daily_used';
                    $used          = intval(get_user_meta($user_id, $meta_key_used, true));
                    update_user_meta($user_id, $meta_key_used, $used + 1);
                }

                // Clean up temporary files
                foreach ($temp_files_to_cleanup as $temp_file) {
                    if (file_exists($temp_file)) {
                        @unlink($temp_file);
                        error_log('WooTryOnTool Plugin: Cleaned up temp file: ' . $temp_file);
                    }
                }

                // Track successful preview generation (counts gallery reuses too)
                $current_user_id_for_tracking = is_user_logged_in() ? get_current_user_id() : 0;
                if ($current_user_id_for_tracking > 0) {
                    // Update per-user counters
                    update_user_meta($current_user_id_for_tracking, 'woo_fitroom_last_preview_date', current_time('mysql'));
                    update_user_meta($current_user_id_for_tracking, 'woo_fitroom_total_previews', intval(get_user_meta($current_user_id_for_tracking, 'woo_fitroom_total_previews', true)) + 1);
                }
                // Fire analytics hook for events table consumers
                $tracking_payload = array(
                    'success' => true,
                    'product_id' => isset($_POST['product_id']) ? absint($_POST['product_id']) : ($data['product_id'] ?? 0),
                    'category' => isset($mapped_category) ? $mapped_category : '',
                    'timestamp' => current_time('mysql')
                );
                do_action('woo_fitroom_preview_generated', $current_user_id_for_tracking, $tracking_payload);

                $payload = array(
                    'image_url' => $response['image_url'],
                    'message' => __('Preview generated successfully', 'woo-tryontool-preview')
                );
                if ( $uploaded_photo_final_url ) {
                    $payload['user_image_saved_url'] = $uploaded_photo_final_url;
                }
                wp_send_json_success( $payload );
            } else {
                // Clean up temporary files on error too
                foreach ($temp_files_to_cleanup as $temp_file) {
                    if (file_exists($temp_file)) {
                        @unlink($temp_file);
                    }
                }
                
                error_log('WooTryOnTool Plugin: Success response received, but image_url missing or empty. Response: ' . print_r($response, true));
                wp_send_json_error(array(
                     'message' => __('AI preview generated, but the result could not be retrieved. Please try again.', 'woo-tryontool-preview')
                ));
            }

        } catch (Exception $e) {
            // Clean up temporary files on exception
            foreach ($temp_files_to_cleanup as $temp_file) {
                if (file_exists($temp_file)) {
                    @unlink($temp_file);
                }
            }
            
            error_log('WooTryOnTool Plugin: Exception: ' . $e->getMessage());
            wp_send_json_error(array(
                'message' => __('An unexpected error occurred: ', 'woo-tryontool-preview') . $e->getMessage()
            ));
        }
    }

    private function convert_to_jpeg($file_path) {
        $image_type = exif_imagetype($file_path);
        $src_img = false;

        switch ($image_type) {
            case IMAGETYPE_GIF:
                $src_img = imagecreatefromgif($file_path);
                break;
            case IMAGETYPE_PNG:
                $src_img = imagecreatefrompng($file_path);
                break;
            case IMAGETYPE_WEBP:
                if (function_exists('imagecreatefromwebp')) {
                    $src_img = imagecreatefromwebp($file_path);
                }
                break;
            case false:
                // Types like HEIC/HEIF may return false. Try Imagick if available.
                if (class_exists('Imagick')) {
                    try {
                        $imagick = new Imagick($file_path);
                        $imagick->setImageFormat('jpg');
                        $src_img = imagecreatefromstring($imagick->getImageBlob());
                        $imagick->clear();
                        $imagick->destroy();
                    } catch (Exception $e) {
                        $src_img = false;
                    }
                }
                break;
            default:
                $src_img = imagecreatefromstring(file_get_contents($file_path));
                break;
        }

        if ($src_img) {
            $upload_dir = wp_upload_dir();
            $jpeg_file = $upload_dir['basedir'] . '/woo-fitroom-temp/' . uniqid('converted_') . '.jpg';

            // Ensure temp dir exists
            if (!file_exists(dirname($jpeg_file))) {
                wp_mkdir_p(dirname($jpeg_file));
            }

            // Convert to JPEG
            imagejpeg($src_img, $jpeg_file, 90);
            imagedestroy($src_img);

            return $jpeg_file;
        }

        return $file_path; // Return original if conversion fails
    }

    public function add_debug_script() {
        if (!is_product()) {
            return;
        }
        
        ?>
        <script type="text/javascript">
            console.log('WooTryOnTool Debug: Script loaded in head');
            
            function checkJQuery() {
                if (window.jQuery) {
                    console.log('WooTryOnTool Debug: jQuery is available');
                    jQuery(document).ready(function($) {
                        console.log('WooTryOnTool Debug: Document ready fired');
                        console.log('WooTryOnTool Debug: Button elements found:', $('.woo-fitroom-preview-button').length);
                        
                        window.checkModalImage = function() {
                            var imgElement = document.getElementById('preview-product-image');
                            console.log('Debug Image Element:', imgElement);
                            console.log('Image src:', imgElement ? imgElement.src : 'No image element');
                            console.log('Image displayed:', imgElement ? window.getComputedStyle(imgElement).display : 'No image element');
                            console.log('Image width:', imgElement ? imgElement.offsetWidth : 'No image element');
                            console.log('Image complete:', imgElement ? imgElement.complete : 'No image element');
                            
                            if (imgElement && !imgElement.complete) {
                                var currentSrc = imgElement.src;
                                if (currentSrc && currentSrc.indexOf('?') === -1) {
                                    imgElement.src = currentSrc + '?t=' + new Date().getTime();
                                    console.log('Forced image reload with:', imgElement.src);
                                }
                            }
                        };
                        
                        $('.woo-fitroom-preview-button').on('click', function() {
                            console.log('Button Data Product ID:', $(this).data('product-id'));
                            console.log('Button Data Product Image:', $(this).data('product-image'));
                            
                            setTimeout(window.checkModalImage, 500);
                        });

                        if (WooFitroomPreview.credits <= 0) {
                            $('.woo-fitroom-preview-button').prop('disabled', true);
                            $('#woo-fitroom-preview-modal .preview-error .error-message').text(WooFitroomPreview.i18n.out_of_credits).show();
                        } else {
                            $('.woo-fitroom-preview-button').prop('disabled', false);
                        }

                        $('#woo-fitroom-preview-modal .preview-error .error-message').hide();
                    });
                } else {
                    setTimeout(checkJQuery, 100);
                }
            }
            checkJQuery();
            
            window.addEventListener('load', function() {
                console.log('WooTryOnTool Debug: Window load event fired');
                
                if (document.querySelector('.woo-fitroom-preview-button')) {
                    console.log('WooTryOnTool Debug: Button found after page load');
                } else {
                    console.log('WooTryOnTool Debug: Button NOT found after page load');
                }
                
                if (document.getElementById('woo-fitroom-preview-modal')) {
                    console.log('WooTryOnTool Debug: Modal found after page load');
                } else {
                    console.log('WooTryOnTool Debug: Modal NOT found after page load');
                }
            });
        </script>
        <?php
    }

    // Add a function to store image URL and timestamp in a transient
    private function store_image_for_deletion($user_id, $image_url) {
        // Register a transient for this specific image (allow multiple per user)
        $transient_key = 'woo_fitroom_image_deletion_' . md5($image_url);
        $data = array('user_id' => $user_id, 'image_url' => $image_url, 'timestamp' => time());
        set_transient($transient_key, $data, 0);
        error_log('WooTryOnTool: Transient set for image deletion -> ' . $transient_key);

        // Handle cases where the timestamp might be missing
        if ($data) {
            $ts = $data['timestamp'] ?? ($data['upload_time'] ?? false);
            if ($ts && (time() - $ts) > YEAR_IN_SECONDS) {
                // Delete the image
                self::delete_image($data['user_id'], $data['image_url'] ?? '');
                // Delete the transient
                delete_transient($transient_key);
            }
        }
    }

    // Make the check_and_delete_images method static
    public static function check_and_delete_images() {
        if ( ! defined( 'WOO_FITROOM_INACTIVITY_WINDOW' ) ) {
            return; // safety guard – constant should be defined in main plugin file
        }

        global $wpdb;
        $prefix = '_transient_WOO_FITROOM_pending_delete_';
        $rows   = $wpdb->get_results( $wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $prefix . '%' ) );

        foreach ( $rows as $row ) {
            $transient_key = str_replace( '_transient_', '', $row->option_name );
            $logout_time   = get_transient( $transient_key );

            // Extract the user-ID from the transient name
            $user_id = intval( str_replace( 'WOO_FITROOM_pending_delete_', '', $transient_key ) );
            if ( ! $user_id ) {
                continue;
            }

            // If the transient has vanished (false) *or* exceeded the inactivity window
            if ( ! $logout_time || ( time() - intval( $logout_time ) ) > WOO_FITROOM_INACTIVITY_WINDOW ) {
                // Initialise Wasabi client so bucket is set
                WooFITROOM_Wasabi::client();

                // Fetch & delete every object that belongs to this user
                $images = WooFITROOM_Wasabi::list_user_images( $user_id );
                foreach ( $images as $url ) {
                    self::delete_image( $user_id, $url );
                }

                // Finally, forget the last-activity transient
                delete_transient( $transient_key );
            }
        }
    }

    // Make the delete_image method static
    private static function delete_image($user_id, $image_url) {
        if ( empty( $image_url ) ) {
            return;
        }

        // Ensure Wasabi client is initialised so that ::bucket() has a value
        WooFITROOM_Wasabi::client();

        $bucket = WooFITROOM_Wasabi::bucket();

        // Extract the S3 object key from the full URL.  We support both
        // …/bucket-name/key  and any custom Wasabi region endpoints.
        $pattern = '#https?://[^/]+/' . preg_quote( $bucket, '#' ) . '/#';
        $key     = preg_replace( $pattern, '', $image_url );

        // Fallback: if the regex failed, fall back to the legacy str_replace.
        if ( $key === $image_url ) {
            $key = str_replace( 'https://s3.eu-west-1.wasabisys.com/' . $bucket . '/', '', $image_url );
        }

        if ( $key && $key !== $image_url ) {
            WooFITROOM_Wasabi::delete( $key );
            error_log( 'WooTryOnTool: deleted ' . $key . ' for user ' . $user_id );
        } else {
            error_log( 'WooTryOnTool: could not parse Wasabi key from URL ' . $image_url );
        }

        // No user-meta to update – list() pulls directly from Wasabi.
    }

    /**
     * Get category assignment from database
     */
    private function get_category_assignment_from_db($category_name) {
        if (empty($category_name)) {
            return null;
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . 'fitroom_category_assignments';
        
        // Check if table exists before querying
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
        if (!$table_exists) {
            error_log('WooTryOnTool Plugin: Category assignments table does not exist yet');
            return null;
        }
        
        $assignment = $wpdb->get_var($wpdb->prepare(
            "SELECT assignment FROM $table_name WHERE category_name = %s",
            $category_name
        ));
        
        return $assignment ? $assignment : null;
    }
}

function get_user_credits() {
    $credits = get_option('WOO_FITROOM_license_credits', 0);
    return $credits;
}

// Add helper functions for storing images in user meta
function WOO_FITROOM_get_user_uploaded_images($user_id) {
    return WooFITROOM_Wasabi::list_user_images($user_id);
}

function WOO_FITROOM_save_uploaded_image_url($user_id, $url) {
    // No longer needed – images live in Wasabi only
}

add_action('wp_ajax_get_user_uploaded_images', function() {
    check_ajax_referer('woo_fitroom_preview_nonce', 'nonce');
    $uid = absint($_POST['user_id'] ?? 0);
    if ( ! $uid ) {
        wp_send_json_error();
    }
    $imgs = WOO_FITROOM_get_user_uploaded_images($uid);
    error_log('WooTryOnTool: Returning '.count($imgs).' saved images for user '.$uid);
    wp_send_json_success(array('images' => $imgs));
});

// Allow non-logged-in visitors to fetch their uploads (they'll just get an empty list).
add_action('wp_ajax_nopriv_get_user_uploaded_images', function() {
    check_ajax_referer('woo_fitroom_preview_nonce', 'nonce');
    wp_send_json_success(array('images' => array()));
});

// Add AJAX handler for deleting uploaded images
add_action('wp_ajax_delete_user_uploaded_image', function() {
    check_ajax_referer('woo_fitroom_preview_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to delete images.', 'woo-fitroom-preview')));
    }
    
    $user_id = absint($_POST['user_id'] ?? 0);
    $image_url = sanitize_url($_POST['image_url'] ?? '');
    
    if (!$user_id || !$image_url) {
        wp_send_json_error(array('message' => __('Invalid parameters.', 'woo-fitroom-preview')));
    }
    
    // Verify the image belongs to the current user
    $user_images = WOO_FITROOM_get_user_uploaded_images($user_id);
    if (!in_array($image_url, $user_images)) {
        wp_send_json_error(array('message' => __('Image not found or access denied.', 'woo-fitroom-preview')));
    }
    
    // Delete the image from Wasabi
    try {
        // Ensure Wasabi client is initialized
        if (!class_exists('WooFITROOM_Wasabi')) {
            require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-wasabi-client.php';
        }
        
        // The client now uses server API, no need to check client() or bucket()
        // Extract the S3 object key from the full URL
        // Try different URL patterns to extract the key
        $key = '';
        
        // Pattern 1: Direct Wasabi URL
        if (preg_match('#https?://[^/]+/.*?/(.*)$#', $image_url, $matches)) {
            $key = $matches[1];
            error_log('Wasabi Delete Debug: Extracted key using pattern 1: ' . $key);
        }
        
        // Fallback: try to extract just the path after the domain
        if (empty($key) || $key === $image_url) {
            $parsed_url = parse_url($image_url);
            if (isset($parsed_url['path'])) {
                $key = ltrim($parsed_url['path'], '/');
                error_log('Wasabi Delete Debug: Extracted key using path: ' . $key);
            }
        }
        
        if ($key && $key !== $image_url) {
            error_log('Wasabi Delete Debug: Attempting to delete key: ' . $key);
            $result = WooFITROOM_Wasabi::delete($key);
            error_log('Wasabi Delete Debug: Delete result: ' . ($result ? 'success' : 'failed'));
            
            if ($result) {
                // Also clean up the transient for this image
                $transient_key = 'WOO_FITROOM_image_deletion_' . md5($key);
                delete_transient($transient_key);
                error_log('Wasabi Delete Debug: Cleaned up transient for key: ' . $key);
                wp_send_json_success(array('message' => __('Image deleted successfully.', 'woo-fitroom-preview')));
            } else {
                error_log('Wasabi Delete Debug: Wasabi delete() returned false for key: ' . $key);
                wp_send_json_error(array('message' => __('Failed to delete image from storage. The image may have already been deleted or there may be a connection issue. Please try again.', 'woo-fitroom-preview')));
            }
        } else {
            error_log('Wasabi Delete Debug: Could not parse key from URL: ' . $image_url);
            wp_send_json_error(array('message' => __('Invalid image URL format. Please refresh the page and try again.', 'woo-fitroom-preview')));
        }
    } catch (Exception $e) {
        error_log('Error deleting image: ' . $e->getMessage());
        $error_message = __('An error occurred while deleting the image. Please check your internet connection and try again.', 'woo-fitroom-preview');
        
        // Provide more specific error messages based on the exception
        if (strpos($e->getMessage(), 'connection') !== false || strpos($e->getMessage(), 'timeout') !== false) {
            $error_message = __('Connection error. Please check your internet connection and try again.', 'woo-fitroom-preview');
        } elseif (strpos($e->getMessage(), 'credentials') !== false || strpos($e->getMessage(), 'authentication') !== false) {
            $error_message = __('Authentication error. Please contact support.', 'woo-fitroom-preview');
        } elseif (strpos($e->getMessage(), 'permission') !== false || strpos($e->getMessage(), 'access') !== false) {
            $error_message = __('Permission denied. Please contact support.', 'woo-fitroom-preview');
        }
        
        wp_send_json_error(array('message' => $error_message));
    }
});

// Add AJAX handler for deleting all user images
add_action('wp_ajax_delete_all_user_images', function() {
    check_ajax_referer('woo_fitroom_preview_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('You must be logged in to delete images.', 'woo-fitroom-preview')));
    }
    
    $user_id = absint($_POST['user_id'] ?? 0);
    
    if (!$user_id) {
        wp_send_json_error(array('message' => __('Invalid user ID.', 'woo-fitroom-preview')));
    }
    
    // Get all user images
    $user_images = WOO_FITROOM_get_user_uploaded_images($user_id);
    
    if (empty($user_images)) {
        wp_send_json_success(array('message' => __('No images to delete.', 'woo-fitroom-preview'), 'deleted_count' => 0));
    }
    
    // Delete all images from Wasabi
    try {
        // Ensure Wasabi client is initialized
        if (!class_exists('WooFITROOM_Wasabi')) {
            require_once WOO_FITROOM_PREVIEW_PLUGIN_DIR . 'includes/class-wasabi-client.php';
        }
        
        // The client now uses server API, no need to check client() or bucket()
        
        $deleted_count = 0;
        $errors = array();
        
        foreach ($user_images as $image_url) {
            // Extract the S3 object key from the full URL
            $key = '';
            
            // Pattern 1: Direct Wasabi URL
            if (preg_match('#https?://[^/]+/.*?/(.*)$#', $image_url, $matches)) {
                $key = $matches[1];
                error_log('Wasabi Delete All Debug: Extracted key using pattern 1: ' . $key);
            }
            
            // Fallback: try to extract just the path after the domain
            if (empty($key) || $key === $image_url) {
                $parsed_url = parse_url($image_url);
                if (isset($parsed_url['path'])) {
                    $key = ltrim($parsed_url['path'], '/');
                    error_log('Wasabi Delete All Debug: Extracted key using path: ' . $key);
                }
            }
            
            if ($key && $key !== $image_url) {
                error_log('Wasabi Delete All Debug: Attempting to delete key: ' . $key);
                $result = WooFITROOM_Wasabi::delete($key);
                
                if ($result) {
                    $deleted_count++;
                    // Clean up the transient for this image
                    $transient_key = 'WOO_FITROOM_image_deletion_' . md5($key);
                    delete_transient($transient_key);
                    error_log('Wasabi Delete All Debug: Successfully deleted key: ' . $key);
                } else {
                    $errors[] = $key;
                    error_log('Wasabi Delete All Debug: Failed to delete key: ' . $key);
                }
            } else {
                $errors[] = $image_url;
                error_log('Wasabi Delete All Debug: Could not parse key from URL: ' . $image_url);
            }
        }
        
        if ($deleted_count > 0) {
            $message = sprintf(__('%d images deleted successfully.', 'woo-fitroom-preview'), $deleted_count);
            if (!empty($errors)) {
                $message .= ' ' . sprintf(__('%d images could not be deleted due to connection issues. Please try again.', 'woo-fitroom-preview'), count($errors));
            }
            wp_send_json_success(array('message' => $message, 'deleted_count' => $deleted_count, 'errors' => $errors));
        } else {
            wp_send_json_error(array('message' => __('Failed to delete any images. Please check your internet connection and try again.', 'woo-fitroom-preview')));
        }
        
    } catch (Exception $e) {
        error_log('Error deleting all images: ' . $e->getMessage());
        $error_message = __('An error occurred while deleting images. Please check your internet connection and try again.', 'woo-fitroom-preview');
        
        // Provide more specific error messages based on the exception
        if (strpos($e->getMessage(), 'connection') !== false || strpos($e->getMessage(), 'timeout') !== false) {
            $error_message = __('Connection error. Please check your internet connection and try again.', 'woo-fitroom-preview');
        } elseif (strpos($e->getMessage(), 'credentials') !== false || strpos($e->getMessage(), 'authentication') !== false) {
            $error_message = __('Authentication error. Please contact support.', 'woo-fitroom-preview');
        } elseif (strpos($e->getMessage(), 'permission') !== false || strpos($e->getMessage(), 'access') !== false) {
            $error_message = __('Permission denied. Please contact support.', 'woo-fitroom-preview');
        }
        
        wp_send_json_error(array('message' => $error_message));
    }
});

// Add the action hook for deleting the image
add_action('woo_fitroom_delete_user_image', function($user_id, $image_url) {
    error_log('Attempting to delete image for user ' . $user_id . ' and image ' . $image_url);
    // Get the user's uploaded images
    $images = WOO_FITROOM_get_user_uploaded_images($user_id);
    
    // Remove the image URL from the user's meta data
    $images = array_filter($images, function($url) use ($image_url) {
        return $url !== $image_url;
    });
    // Reindex array to maintain sequential keys so JSON encoding gives JS array
    $images = array_values($images);
    // No longer storing in user meta; list() pulls directly from Wasabi
    
    // Delete the image file from the server
    $upload_dir = wp_upload_dir();
    $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $image_url);
    if (file_exists($file_path)) {
        unlink($file_path);
        error_log('Deleted image file at ' . $file_path);
    } else {
        error_log('Image file not found at ' . $file_path);
    }
});

// Adjust the hook to call the static method
add_action('init', array('WooFitroomPreview_Product_Button', 'check_and_delete_images'));
}

