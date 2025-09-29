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

// Enqueue admin styles
wp_enqueue_style('tryon-admin-settings', plugin_dir_url(__FILE__) . '../../assets/css/admin-settings.css', array(), '1.0.0');
?>
<div class="tryon-admin-wrap">
       <div class="tryon-admin-header">
       <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
       </div>
       
       <!-- Tab Navigation -->
       <nav class="tryon-tab-wrapper">
           <a href="#general" class="tryon-nav-tab tryon-nav-tab-active" data-tab="general"><?php _e('General', 'woo-fitroom-preview'); ?></a>
           <a href="#appearance" class="tryon-nav-tab" data-tab="appearance"><?php _e('Appearance', 'woo-fitroom-preview'); ?></a>
       </nav>
       
       <form method="post" action="options.php">
           <?php
           settings_fields('WOO_FITROOM_preview_options');
           do_settings_sections('WOO_FITROOM_preview_options');
           $license_status = get_option('WOO_FITROOM_license_status', 'unknown');
           $license_expires = get_option('WOO_FITROOM_license_expires', '');
           $license_credits = get_option('WOO_FITROOM_license_credits', '');
           $plan_product_id = get_option('WOO_FITROOM_plan_product_id', '');
           $show_on_demand_initially = ($license_status === 'valid' && $license_credits !== '' && (int)$license_credits <= 0);
           ?>
           
           <!-- General Tab -->
           <div id="general-tab" class="tab-content">
           <table class="tryon-form-table">
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_preview_enabled">
                           <?php _e('Enable Try-On Tool', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <div class="tryon-d-flex tryon-align-items-center tryon-gap-2">
                           <div class="tryon-toggle-wrapper">
                               <input type="checkbox" class="tryon-toggle-input" 
                                      id="WOO_FITROOM_preview_enabled_toggle" 
                              name="WOO_FITROOM_preview_enabled" 
                              value="1" 
                              <?php checked(get_option('WOO_FITROOM_preview_enabled'), 1); ?>>
                               <span class="tryon-toggle-slider"></span>
                           </div>
                           <span><?php _e('Master switch for the plugin functionality.', 'woo-fitroom-preview'); ?></span>
                       </div>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_license_key">
                           <?php _e('License Key', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <div class="tryon-d-flex tryon-align-items-center tryon-gap-2 tryon-mb-2">
                       <input type="text" id="WOO_FITROOM_license_key"
                              name="WOO_FITROOM_license_key"
                                  class="tryon-input regular-text"
                              value="<?php echo esc_attr(get_option('WOO_FITROOM_license_key')); ?>">
                           <button type="button" id="validate-license-key" class="tryon-btn tryon-btn-secondary">
                           <?php _e('Validate Key', 'woo-fitroom-preview'); ?>
                       </button>
                       </div>
                       <p class="description tryon-mb-2">
                           <?php _e('Enter the license key you received via email after purchase.', 'woo-fitroom-preview'); ?>
                       </p>
                       <div id="license-status" class="tryon-mb-2">
                           <?php if ($license_status === 'valid'): ?>
                               <div class="tryon-status tryon-status-success">
                                   <strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Active', 'woo-fitroom-preview'); ?>
                                   <?php if($license_expires) printf(__(' (Expires: %s)'), esc_html($license_expires)); ?>
                                   <?php if($license_credits !== '') printf(__(' | Credits: %s'), esc_html($license_credits)); ?>
                               </div>
                           <?php elseif ($license_status === 'invalid'): ?>
                                <div class="tryon-status tryon-status-error">
                                    <strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Invalid or Expired', 'woo-fitroom-preview'); ?>
                                </div>
                            <?php else: ?>
                                <div class="tryon-status tryon-status-warning">
                                    <strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Unknown (Please validate)', 'woo-fitroom-preview'); ?>
                                </div>
                            <?php endif; ?>
                       </div>
                        <div id="license-validation-result" class="tryon-hidden"></div>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <?php _e('Purchase Plans', 'woo-fitroom-preview'); ?>
                   </th>
                   <td>
                       <p class="tryon-mb-2">
                           <?php _e('Need to purchase a plan?', 'woo-fitroom-preview'); ?>
                       </p>
                       <a href="https://tryontool.com/plans" target="_blank" class="tryon-btn tryon-btn-primary">
                           <?php _e('Visit Try-On Tool Website', 'woo-fitroom-preview'); ?>
                       </a>
                       <p class="description tryon-mt-2">
                           <?php _e('Browse our plans and purchase additional credits for your Try-On Tool plugin.', 'woo-fitroom-preview'); ?>
                       </p>
                   </td>
               </tr>
               <!-- FREE PLAN TOPUP SECTION - appears when license key is empty -->
               <?php 
               $license_key = get_option('WOO_FITROOM_license_key');
               if (empty($license_key)): 
                   
                   // FREE PLAN TOPUP - Check if FREE plan has been used by this account
                   global $wpdb;
                   $table_name = $wpdb->prefix . 'user_plans';
                   $free_plan_used = false;
                   
                   if (defined('FITROOM_PLAN_FREE_PRODUCT_ID')) {
                       $current_user_id = get_current_user_id();
                       
                       // FREE PLAN TOPUP - Check the dedicated free_plan_used column
                       $free_plan_used_value = $wpdb->get_var($wpdb->prepare(
                           "SELECT free_plan_used FROM $table_name 
                            WHERE user_id = %d 
                            LIMIT 1",
                           $current_user_id
                       ));
                       
                       if ($free_plan_used_value == 1) {
                           $free_plan_used = true;
                           
                           // Get additional details for display
                           $free_plan_details = $wpdb->get_row($wpdb->prepare(
                               "SELECT start_date, status FROM $table_name 
                                WHERE user_id = %d 
                                AND plan_product_id = %d
                                LIMIT 1",
                               $current_user_id,
                               FITROOM_PLAN_FREE_PRODUCT_ID
                           ));
                           
                           if ($free_plan_details) {
                               $free_plan_date = $free_plan_details->start_date;
                               $free_plan_status = $free_plan_details->status;
                           }
                       }
                   }
               ?>
               <tr>
                   <th scope="row">
                       <?php _e('Try For Free', 'woo-fitroom-preview'); ?>
                   </th>
                   <td>
                       <p style="margin-bottom: 10px;">
                           <?php _e('Get started with our free trial!', 'woo-fitroom-preview'); ?>
                       </p>
                       <div style="background: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; margin-bottom: 15px;">
                           <h4 style="margin-top: 0;"><?php _e('Free Trial Plan', 'woo-fitroom-preview'); ?></h4>
                           <ul style="margin: 10px 0; padding-left: 20px;">
                               <li><?php _e('3 AI-generated previews', 'woo-fitroom-preview'); ?></li>
                               <li><?php _e('Valid for 30 days', 'woo-fitroom-preview'); ?></li>
                               <li><?php _e('One-time purchase per user', 'woo-fitroom-preview'); ?></li>
                               <li><?php _e('No credit card required', 'woo-fitroom-preview'); ?></li>
                           </ul>
                           <p style="margin-bottom: 10px; font-weight: bold; color: #0073aa;">
                               <?php _e('Price: Â£0.00', 'woo-fitroom-preview'); ?>
                           </p>
                       </div>
                       
                       <?php if ($free_plan_used): ?>
                           <!-- FREE PLAN TOPUP - Button disabled when already used -->
                           <div style="background: #f8f9fa; padding: 15px; border-left: 4px solid #6c757d; margin-bottom: 15px;">
                               <h4 style="margin-top: 0; color: #6c757d;"><?php _e('Free Trial Status', 'woo-fitroom-preview'); ?></h4>
                               <p style="margin: 10px 0; color: #6c757d;">
                                   <strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> 
                                   <span style="color: #dc3545;"><?php _e('Already Used', 'woo-fitroom-preview'); ?></span>
                                   <?php if (isset($free_plan_status)): ?>
                                       <br><strong><?php _e('Plan Status:', 'woo-fitroom-preview'); ?></strong> 
                                       <span style="color: <?php echo ($free_plan_status === 'active') ? '#28a745' : '#ffc107'; ?>;">
                                           <?php echo ucfirst($free_plan_status); ?>
                                       </span>
                                   <?php endif; ?>
                                   <?php if (isset($free_plan_date)): ?>
                                       <br><strong><?php _e('Used On:', 'woo-fitroom-preview'); ?></strong> 
                                       <span><?php echo date('F j, Y', strtotime($free_plan_date)); ?></span>
                                   <?php endif; ?>
                               </p>
                               <p style="margin: 10px 0; color: #6c757d; font-size: 13px;">
                                   <?php _e('You have already used your free trial plan. You can only purchase it once per account forever.', 'woo-fitroom-preview'); ?>
                               </p>
                           </div>
                           <button type="button" id="try-for-free-button" class="button" disabled>
                               <span style="text-decoration: line-through;"><?php _e('Try For Free', 'woo-fitroom-preview'); ?></span>
                           </button>
                           <p class="description" style="color: #6c757d; font-style: italic;">
                               <?php _e('Free trial plan has been used. Consider purchasing a paid plan for more credits.', 'woo-fitroom-preview'); ?>
                           </p>
                       <?php else: ?>
                           <!-- FREE PLAN TOPUP - Button enabled when not used -->
                           <button type="button" id="try-for-free-button" class="button button-primary">
                               <?php _e('Try For Free', 'woo-fitroom-preview'); ?>
                           </button>
                           <p class="description">
                               <?php _e('Click to get your free trial plan. You can only purchase the free plan once per user account forever.', 'woo-fitroom-preview'); ?>
                           </p>
                       <?php endif; ?>
                   </td>
               </tr>
               <?php endif; ?>
               <!-- END FREE PLAN TOPUP SECTION -->
               <tr id="on-demand-credits-row" style="<?php echo $show_on_demand_initially ? '' : 'display: none;'; ?>">
                    <th scope="row">
                        <?php _e('Buy Credits', 'woo-fitroom-preview'); ?>
                    </th>
                    <td>
                        <?php
                            // --- Build credit pack options (static pricing) ---
                            $credit_packs = array(
                                60  => array( 'id' => defined('FITROOM_CREDIT_PACK_100_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_100_PRODUCT_ID  : 3515, 'price' => 5.99  ),
                                120  => array( 'id' => defined('FITROOM_CREDIT_PACK_200_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_200_PRODUCT_ID  : 3516, 'price' => 11.99  ),
                                240  => array( 'id' => defined('FITROOM_CREDIT_PACK_300_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_300_PRODUCT_ID  : 3517, 'price' => 23.99 ),
                                /*
                                400  => array( 'id' => defined('FITROOM_CREDIT_PACK_400_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_400_PRODUCT_ID  : 3518, 'price' => 170 ),
                                500  => array( 'id' => defined('FITROOM_CREDIT_PACK_500_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_500_PRODUCT_ID  : 3519, 'price' => 210 ),
                                600  => array( 'id' => defined('FITROOM_CREDIT_PACK_600_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_600_PRODUCT_ID  : 3520, 'price' => 250 ),
                                700  => array( 'id' => defined('FITROOM_CREDIT_PACK_700_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_700_PRODUCT_ID  : 3521, 'price' => 290 ),
                                800  => array( 'id' => defined('FITROOM_CREDIT_PACK_800_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_800_PRODUCT_ID  : 3522, 'price' => 330 ),
                                900  => array( 'id' => defined('FITROOM_CREDIT_PACK_900_PRODUCT_ID')  ? FITROOM_CREDIT_PACK_900_PRODUCT_ID  : 3523, 'price' => 370 ),
                                1000 => array( 'id' => defined('FITROOM_CREDIT_PACK_1000_PRODUCT_ID') ? FITROOM_CREDIT_PACK_1000_PRODUCT_ID : 3524, 'price' => 410 ),
                                1100 => array( 'id' => defined('FITROOM_CREDIT_PACK_1100_PRODUCT_ID') ? FITROOM_CREDIT_PACK_1100_PRODUCT_ID : 3525, 'price' => 450 ),
                                1200 => array( 'id' => defined('FITROOM_CREDIT_PACK_1200_PRODUCT_ID') ? FITROOM_CREDIT_PACK_1200_PRODUCT_ID : 3526, 'price' => 490 ),
                                */
                            );
                        ?>

                        <div id="credit-pack-buttons" class="tryon-credit-packs">
                            <button type="button" class="tryon-credit-pack credit-pack-option" data-credits="60">60 Credits</button>
                            <button type="button" class="tryon-credit-pack credit-pack-option" data-credits="120">120 Credits</button>
                            <button type="button" class="tryon-credit-pack credit-pack-option" data-credits="240">240 Credits</button>
                        </div>

                        <!--
                        <div id="custom-credit-selector" style="display:flex; align-items:center; gap:5px; margin-bottom:10px;">
                            <button type="button" id="custom-credit-minus" class="button">&minus;</button>
                            <input type="text" id="custom-credit-value" value="100" readonly style="width:80px; text-align:center;" />
                            <button type="button" id="custom-credit-plus" class="button">+</button>
                            <span class="description" style="margin-left:8px;"><?php _e('Custom amount (multiples of 100)', 'woo-fitroom-preview'); ?></span>
                        </div>
                        -->

                        <div id="credit-pack-selected-display" class="tryon-credit-display">
                            <span id="selected-credits"></span> credits for <span id="selected-price"></span>
                        </div>
                        <button type="button" id="buy-on-demand-credits" class="tryon-btn tryon-btn-primary">
                            <?php _e('Buy On-Demand Credits', 'woo-fitroom-preview'); ?>
                        </button>
                        <p class="description tryon-mt-2">
                            <?php _e('Choose a credit bundle then click "Buy" to proceed to checkout.', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_daily_credits">
                           <?php _e('Daily Credits Per User (Visual Only)', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <input type="number" id="WOO_FITROOM_daily_credits"
                              name="WOO_FITROOM_daily_credits"
                              class="tryon-input small-text"
                              value="<?php echo esc_attr(get_option('WOO_FITROOM_daily_credits', 0)); ?>" min="0">
                       <p class="description tryon-mt-1">
                           <?php _e('Optional: Set a visual daily limit reminder for users. Actual credits are managed by the server.', 'woo-fitroom-preview'); ?>
                       </p>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_logged_in_only">
                           <?php _e('Restrict to Logged-in Users', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <div class="tryon-d-flex tryon-align-items-center tryon-gap-2">
                           <div class="tryon-toggle-wrapper">
                               <input type="checkbox" class="tryon-toggle-input" 
                                      id="WOO_FITROOM_logged_in_only_toggle"
                                      name="WOO_FITROOM_logged_in_only" 
                                      value="1"
                              <?php checked(get_option('WOO_FITROOM_logged_in_only'), 1); ?>>
                               <span class="tryon-toggle-slider"></span>
                           </div>
                           <span><?php _e('Enable this to show the Try-On button only to logged-in customers.', 'woo-fitroom-preview'); ?></span>
                       </div>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_allowed_roles">
                           <?php _e('Allowed User Roles', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <?php $all_roles = wp_roles()->roles; $selected_roles = (array) get_option('WOO_FITROOM_allowed_roles', array()); ?>
                       <select id="WOO_FITROOM_allowed_roles" name="WOO_FITROOM_allowed_roles[]" class="tryon-select" multiple size="4">
                           <?php foreach ($all_roles as $role_key => $role) : ?>
                               <option value="<?php echo esc_attr($role_key); ?>" <?php selected(in_array($role_key, $selected_roles), true); ?>>
                                   <?php echo esc_html($role['name']); ?>
                               </option>
                           <?php endforeach; ?>
                       </select>
                       <p class="description tryon-mt-1">
                           <?php _e('Leave empty to allow all roles (if logged-in restriction applies).', 'woo-fitroom-preview'); ?>
                       </p>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_allowed_user_ids">
                           <?php _e('Specific Allowed User IDs', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <textarea id="WOO_FITROOM_allowed_user_ids" name="WOO_FITROOM_allowed_user_ids" rows="3" cols="50" class="tryon-input large-text code"><?php echo esc_textarea(get_option('WOO_FITROOM_allowed_user_ids', '')); ?></textarea>
                       <p class="description tryon-mt-1">
                           <?php _e('Comma-separated list of WordPress user IDs that can access the Try-On feature (overrides role setting). Leave empty to disable.', 'woo-fitroom-preview'); ?>
                       </p>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_required_user_tag">
                           <?php _e('Required User Tag (Meta Key: woo_tryontool_user_tag)', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                        <input type="text" id="WOO_FITROOM_required_user_tag"
                            name="WOO_FITROOM_required_user_tag"
                            class="tryon-input regular-text"
                            value="<?php echo esc_attr(get_option('WOO_FITROOM_required_user_tag', '')); ?>">
                       <p class="description tryon-mt-1">
                           <?php _e('If set, only users with this exact value in their `woo_tryontool_user_tag` user meta field can use the feature.', 'woo-fitroom-preview'); ?>
                       </p>
                   </td>
               </tr>
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_require_extra_consents">
                           <?php _e('Require Terms/Refund Consent on First Use', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                                                                       <?php 
                        $consent_value = get_option('WOO_FITROOM_require_extra_consents');
                        // Only default to checked (1) if option doesn't exist at all
                        if ($consent_value === false) {
                            $consent_value = 1;
                        }
                        ?>
                       <div class="tryon-d-flex tryon-align-items-center tryon-gap-2">
                           <div class="tryon-toggle-wrapper">
                               <input type="checkbox" class="tryon-toggle-input" 
                                      id="WOO_FITROOM_require_extra_consents_toggle"
                                      name="WOO_FITROOM_require_extra_consents" 
                                      value="1"
                               <?php checked($consent_value, 1); ?>>
                               <span class="tryon-toggle-slider"></span>
                           </div>
                           <span><?php _e('If enabled, users must agree to Terms and Refund Policy on first use.', 'woo-fitroom-preview'); ?></span>
                       </div>
                   </td>
               </tr>
               <!-- Records of Consent -->
               <tr>
                    <th scope="row"><?php _e('Records of Consent', 'woo-fitroom-preview'); ?></th>
                    <td>
                        <div class="tryon-d-flex tryon-gap-2 tryon-mb-2">
                            <button type="button" id="view-consent-records" class="tryon-btn tryon-btn-secondary">
                            <?php _e('View Records', 'woo-fitroom-preview'); ?>
                        </button>
                            <button type="button" id="export-consent-records" class="tryon-btn tryon-btn-primary">
                                <?php _e('Export to Excel', 'woo-fitroom-preview'); ?>
                            </button>
                        </div>
                        <p class="description tryon-mt-1"><?php _e('View user consent records for image processing or export them to Excel format.', 'woo-fitroom-preview'); ?></p>
                    </td>
               </tr>
           </table>
           </div>
           
           <!-- Appearance Tab -->
           <div id="appearance-tab" class="tab-content tryon-hidden">
           <table class="tryon-form-table">
               <tr>
                   <th scope="row">
                       <label for="WOO_FITROOM_use_theme_colors">
                           <?php _e('Button Color Style', 'woo-fitroom-preview'); ?>
                       </label>
                   </th>
                   <td>
                       <div class="tryon-radio-group">
                           <div class="tryon-radio-item">
                               <input type="radio" name="WOO_FITROOM_use_theme_colors" value="1" 
                                      id="WOO_FITROOM_use_theme_colors_1"
                                      <?php checked(get_option('WOO_FITROOM_use_theme_colors', true), true); ?>>
                               <label for="WOO_FITROOM_use_theme_colors_1">
                               <?php _e('Inherit theme primary color', 'woo-fitroom-preview'); ?>
                               </label>
                           </div>
                           <div class="tryon-radio-item">
                               <input type="radio" name="WOO_FITROOM_use_theme_colors" value="0" 
                                      id="WOO_FITROOM_use_theme_colors_0"
                                      <?php checked(get_option('WOO_FITROOM_use_theme_colors', true), false); ?>>
                               <label for="WOO_FITROOM_use_theme_colors_0">
                               <?php _e('Use Try-On Tool defined color or your custom color', 'woo-fitroom-preview'); ?>
                           </label>
                           </div>
                       </div>
                       <p class="description tryon-mt-2">
                           <?php _e('Choose whether the Try-On button should inherit your theme\'s primary color or use Try-On Tool defined color.', 'woo-fitroom-preview'); ?>
                       </p>
                   </td>
               </tr>
                <tr id="custom-color-row" class="<?php echo get_option('WOO_FITROOM_use_theme_colors', true) ? 'tryon-hidden' : ''; ?>" style="<?php echo get_option('WOO_FITROOM_use_theme_colors', true) ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label for="WOO_FITROOM_custom_button_color">
                            <?php _e('Custom Button Color', 'woo-fitroom-preview'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="tryon-color-picker-wrapper tryon-mb-2">
                            <input type="color" id="WOO_FITROOM_custom_button_color" 
                                   name="WOO_FITROOM_custom_button_color" 
                                   class="tryon-color-picker"
                                   value="<?php echo esc_attr(get_option('WOO_FITROOM_custom_button_color', '#FF6E0E')); ?>">
                            <input type="text" id="WOO_FITROOM_custom_button_color_text" 
                                   placeholder="#FF6E0E" 
                                   class="tryon-color-text"
                                   value="<?php echo esc_attr(get_option('WOO_FITROOM_custom_button_color', '#FF6E0E')); ?>">
                            <span id="color-display" class="tryon-color-display">
                                <?php echo esc_html(get_option('WOO_FITROOM_custom_button_color', '#FF6E0E')); ?>
                            </span>
                        </div>
                        <p class="description">
                            <?php _e('Select a custom color for the Try-On button using the color picker or enter a hex code. Default: #FF6E0E (Orange).', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="WOO_FITROOM_use_theme_padding">
                            <?php _e('Button Padding Style', 'woo-fitroom-preview'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="tryon-radio-group">
                            <div class="tryon-radio-item">
                                <input type="radio" name="WOO_FITROOM_use_theme_padding" value="1" 
                                       id="WOO_FITROOM_use_theme_padding_1"
                                       <?php checked(get_option('WOO_FITROOM_use_theme_padding', true), true); ?>>
                               <label for="WOO_FITROOM_use_theme_padding_1">
                                <?php _e('Use Try-On Tool default padding', 'woo-fitroom-preview'); ?>
                               </label>
                           </div>
                           <div class="tryon-radio-item">
                                <input type="radio" name="WOO_FITROOM_use_theme_padding" value="0" 
                                       id="WOO_FITROOM_use_theme_padding_0"
                                       <?php checked(get_option('WOO_FITROOM_use_theme_padding', true), false); ?>>
                               <label for="WOO_FITROOM_use_theme_padding_0">
                                <?php _e('Use custom padding values', 'woo-fitroom-preview'); ?>
                            </label>
                            </div>
                        </div>
                        <p class="description tryon-mt-2">
                            <?php _e('Choose whether to use Try-On Tool default padding (12px top/bottom, 20px left/right) or set custom padding values.', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
                </tr>
                <tr id="custom-padding-row" class="<?php echo get_option('WOO_FITROOM_use_theme_padding', true) ? 'tryon-hidden' : ''; ?>" style="<?php echo get_option('WOO_FITROOM_use_theme_padding', true) ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label for="WOO_FITROOM_custom_button_padding">
                            <?php _e('Custom Button Padding', 'woo-fitroom-preview'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="tryon-spacing-grid">
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_padding_top" class="tryon-spacing-label">
                                    <?php _e('Top Padding (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_padding_top" 
                                       name="WOO_FITROOM_padding_top" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_padding_top', '12')); ?>"
                                       min="0" max="50" step="1">
                            </div>
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_padding_bottom" class="tryon-spacing-label">
                                    <?php _e('Bottom Padding (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_padding_bottom" 
                                       name="WOO_FITROOM_padding_bottom" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_padding_bottom', '12')); ?>"
                                       min="0" max="50" step="1">
                            </div>
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_padding_left" class="tryon-spacing-label">
                                    <?php _e('Left Padding (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_padding_left" 
                                       name="WOO_FITROOM_padding_left" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_padding_left', '20')); ?>"
                                       min="0" max="50" step="1">
                            </div>
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_padding_right" class="tryon-spacing-label">
                                    <?php _e('Right Padding (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_padding_right" 
                                       name="WOO_FITROOM_padding_right" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_padding_right', '20')); ?>"
                                       min="0" max="50" step="1">
                            </div>
                        </div>
                        <p class="description tryon-mt-2">
                            <?php _e('Set custom padding values for the Try-On button. Default: 12px top/bottom, 20px left/right.', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="WOO_FITROOM_use_theme_border_radius">
                            <?php _e('Button Border Radius Style', 'woo-fitroom-preview'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="tryon-radio-group">
                            <div class="tryon-radio-item">
                                <input type="radio" name="WOO_FITROOM_use_theme_border_radius" value="1"
                                       id="WOO_FITROOM_use_theme_border_radius_1"
                                       <?php checked(get_option('WOO_FITROOM_use_theme_border_radius', true), true); ?>>
                               <label for="WOO_FITROOM_use_theme_border_radius_1">
                                <?php _e('Inherit theme border radius', 'woo-fitroom-preview'); ?>
                               </label>
                           </div>
                           <div class="tryon-radio-item">
                                <input type="radio" name="WOO_FITROOM_use_theme_border_radius" value="0"
                                       id="WOO_FITROOM_use_theme_border_radius_0"
                                       <?php checked(get_option('WOO_FITROOM_use_theme_border_radius', true), false); ?>>
                               <label for="WOO_FITROOM_use_theme_border_radius_0">
                                <?php _e('Use Try-On Tool defined border radius (50px) or your own custom', 'woo-fitroom-preview'); ?>
                            </label>
                            </div>
                        </div>
                        <p class="description tryon-mt-2">
                            <?php _e('Choose whether to inherit theme border radius or use Try-On Tool defined border radius (50px for all corners).', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
                </tr>
                <tr id="custom-border-radius-row" class="<?php echo get_option('WOO_FITROOM_use_theme_border_radius', true) ? 'tryon-hidden' : ''; ?>" style="<?php echo get_option('WOO_FITROOM_use_theme_border_radius', true) ? 'display: none;' : ''; ?>">
                    <th scope="row">
                        <label for="WOO_FITROOM_custom_button_border_radius">
                            <?php _e('Custom Button Border Radius', 'woo-fitroom-preview'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="tryon-spacing-grid" style="max-width: 500px;">
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_border_radius_top_left" class="tryon-spacing-label">
                                    <?php _e('Top Left (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_border_radius_top_left" 
                                       name="WOO_FITROOM_border_radius_top_left" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_border_radius_top_left', '50')); ?>"
                                       min="0" max="100" step="1">
                            </div>
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_border_radius_top_right" class="tryon-spacing-label">
                                    <?php _e('Top Right (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_border_radius_top_right" 
                                       name="WOO_FITROOM_border_radius_top_right" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_border_radius_top_right', '50')); ?>"
                                       min="0" max="100" step="1">
                            </div>
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_border_radius_bottom_left" class="tryon-spacing-label">
                                    <?php _e('Bottom Left (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_border_radius_bottom_left" 
                                       name="WOO_FITROOM_border_radius_bottom_left" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_border_radius_bottom_left', '50')); ?>"
                                       min="0" max="100" step="1">
                            </div>
                            <div class="tryon-spacing-item">
                                <label for="WOO_FITROOM_border_radius_bottom_right" class="tryon-spacing-label">
                                    <?php _e('Bottom Right (px)', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="number" id="WOO_FITROOM_border_radius_bottom_right" 
                                       name="WOO_FITROOM_border_radius_bottom_right" 
                                       class="tryon-spacing-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_border_radius_bottom_right', '50')); ?>"
                                       min="0" max="100" step="1">
                            </div>
                        </div>
                        <p class="description tryon-mt-2">
                            <?php _e('Set custom border radius values for each corner of the Try-On button. Default: 50px for all corners.', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <?php _e('Important Note', 'woo-fitroom-preview'); ?>
                    </th>
                    <td>
                        <div class="tryon-notice tryon-notice-warning">
                            <p style="margin: 0;">
                                <strong><?php _e('Cache Notice:', 'woo-fitroom-preview'); ?></strong> 
                                <?php _e('If you have any caching plugin activated (WP Rocket, W3 Total Cache, WP Super Cache, etc.), make sure to clear the hard cache before checking if the changes are applied on your frontend.', 'woo-fitroom-preview'); ?>
                            </p>
                        </div>
                        <p class="description tryon-mt-1">
                            <?php _e('Changes will be visible on your product pages after clearing cache.', 'woo-fitroom-preview'); ?>
                        </p>
                    </td>
                </tr>
                <tr class="button-text-feature-row" style="display: none;">
                    <th scope="row">
                        <label for="WOO_FITROOM_button_text_mode">
                            <?php _e('Button Text', 'woo-fitroom-preview'); ?>
                        </label>
                    </th>
                    <td>
                        <div class="tryon-radio-group">
                            <div class="tryon-radio-item">
                                <input type="radio" name="WOO_FITROOM_button_text_mode" value="default" 
                                       id="WOO_FITROOM_button_text_mode_default"
                                       <?php checked(get_option('WOO_FITROOM_button_text_mode', 'default'), 'default'); ?>>
                                <label for="WOO_FITROOM_button_text_mode_default">
                                    <?php _e('Set to default "Try It On"', 'woo-fitroom-preview'); ?>
                                </label>
                            </div>
                            <div class="tryon-radio-item">
                                <input type="radio" name="WOO_FITROOM_button_text_mode" value="custom" 
                                       id="WOO_FITROOM_button_text_mode_custom"
                                       <?php checked(get_option('WOO_FITROOM_button_text_mode', 'default'), 'custom'); ?>>
                                <label for="WOO_FITROOM_button_text_mode_custom">
                                    <?php _e('Set your custom text', 'woo-fitroom-preview'); ?>
                                </label>
                            </div>
                        </div>
                        <p class="description tryon-mt-2">
                            <?php _e('Choose whether to use the default "Try It On" text or set your own custom text.', 'woo-fitroom-preview'); ?>
                        </p>
                        
                        <div id="custom-button-text-row" class="<?php echo get_option('WOO_FITROOM_button_text_mode', 'default') === 'custom' ? '' : 'tryon-hidden'; ?>" style="<?php echo get_option('WOO_FITROOM_button_text_mode', 'default') === 'custom' ? '' : 'display: none;'; ?>">
                            <div class="tryon-mt-3">
                                <label for="WOO_FITROOM_custom_button_text" class="tryon-block tryon-mb-2" style="font-weight: 500;">
                                    <?php _e('Custom Button Text', 'woo-fitroom-preview'); ?>
                                </label>
                                <input type="text" id="WOO_FITROOM_custom_button_text" 
                                       name="WOO_FITROOM_custom_button_text" 
                                       class="tryon-input"
                                       value="<?php echo esc_attr(get_option('WOO_FITROOM_custom_button_text', 'Try It On')); ?>"
                                       maxlength="15"
                                       placeholder="Try It On">
                                <div class="tryon-mt-1" style="display: flex; justify-content: space-between; align-items: center;">
                                    <p class="description" style="margin: 0;">
                                        <?php _e('Enter your custom text for the Try-On button (max 15 characters).', 'woo-fitroom-preview'); ?>
                                    </p>
                                    <span id="button-text-counter" class="description" style="margin: 0; font-weight: 500; color: #666;">
                                        <span id="current-length"><?php echo strlen(get_option('WOO_FITROOM_custom_button_text', 'Try It On')); ?></span>/15
                                    </span>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
           </table>
           </div>
           
           <div class="tryon-mt-4 tryon-text-center">
               <?php submit_button('Save Settings', 'primary', 'submit', false, array('class' => 'tryon-btn tryon-btn-primary tryon-btn-lg')); ?>
           </div>
       </form>

       <!-- Consent Records Modal -->
       <div id="consent-records-modal" class="tryon-modal tryon-hidden">
            <div class="tryon-modal-content">
                <div class="tryon-modal-header">
                <h2><?php _e('User Consent Records','woo-fitroom-preview'); ?></h2>
                    <span class="tryon-modal-close close-consent-modal">&times;</span>
                </div>
                <div class="tryon-modal-body"></div>
            </div>
       </div>

       <script type="text/javascript">
document.addEventListener('DOMContentLoaded', function () {

    /* ----------------------------------------------------------------
     *  CREDITâ€‘PACK UI
     * ---------------------------------------------------------------- */
    const creditPacks  = JSON.parse('<?php echo wp_json_encode($credit_packs); ?>');

    /* element refs */
    const quickButtons = document.querySelectorAll('.credit-pack-option');
    const plusBtn      = document.getElementById('custom-credit-plus');   // may be null
    const minusBtn     = document.getElementById('custom-credit-minus');  // may be null
    const customInput  = document.getElementById('custom-credit-value');  // may be null
    const creditsLbl   = document.getElementById('selected-credits');
    const priceLbl     = document.getElementById('selected-price');
    const buyBtn       = document.getElementById('buy-on-demand-credits');

    let selectedCredits = 60;     // default pack

    function updateDisplay () {
        if (!creditPacks[selectedCredits]) { return; }
        creditsLbl.textContent      = selectedCredits;
        priceLbl.textContent        = 'Â£' + creditPacks[selectedCredits].price;
        buyBtn.dataset.productId    = creditPacks[selectedCredits].id;
        if (customInput) { customInput.value = selectedCredits; }

        /* highlight quick buttons */
        quickButtons.forEach(btn => {
            btn.classList.toggle('tryon-credit-pack-selected',
                parseInt(btn.dataset.credits, 10) === selectedCredits);
        });
    }

    /* quickâ€‘pick buttons */
    quickButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            selectedCredits = parseInt(btn.dataset.credits, 10);
            updateDisplay();
        });
    });

    /* --------------------------------------------------------------
     *  PLUS / MINUS HANDLERS (disabled â€“ kept only for future use)
     * -------------------------------------------------------------- */
    /*
    if (plusBtn && minusBtn && customInput) {
        plusBtn.addEventListener('click', () => {
            if (selectedCredits < 240) {      // highest pack button
                selectedCredits += 60;
                updateDisplay();
            }
        });

        minusBtn.addEventListener('click', () => {
            if (selectedCredits > 60) {       // lowest pack button
                selectedCredits -= 60;
                updateDisplay();
            }
        });
    }
    */

    /* initialise */
    updateDisplay();

    /* buyâ€‘button click */
    buyBtn.addEventListener('click', e => {
        e.preventDefault();
        const pid = buyBtn.dataset.productId;
        if (!pid) {
            alert('<?php echo esc_js(__('Please select a credit pack first.', 'woo-fitroom-preview')); ?>');
            return;
        }
        window.location.href = 'https://tryontool.com/checkout/?add-to-cart=' + pid;
    });

    /* --------------------------------------------------------------
     *  UPGRADE / DOWNGRADE SWITCH FLOW (simplified redirect)
     * -------------------------------------------------------------- */
    // document.getElementById('FitRoom-change-plan').addEventListener('click', function(e){
    //     e.preventDefault();
    //     window.location.href = 'https://staging4.tryontool.com/my-account/subscriptions/';
    // });



    /* ----------------------------------------------------------------
     *  LICENSE VALIDATION  (unchanged)
     * ---------------------------------------------------------------- */
    const $ = jQuery;

    function validateLicense () {
        var licenseKey    = $('#WOO_FITROOM_license_key').val();
        var resultDiv     = $('#license-validation-result');
        var statusDiv     = $('#license-status');
        var validateBtn   = $('#validate-license-key');

        if (!licenseKey) { return; }

        validateBtn.prop('disabled', true)
                   .text('<?php _e('Validating...', 'woo-fitroom-preview'); ?>');
        resultDiv.removeClass('notice-success notice-error tryon-status-success tryon-status-error').addClass('tryon-hidden').empty();
        statusDiv.html('<div class="tryon-status tryon-status-info"><strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Checking...', 'woo-fitroom-preview'); ?></div>');
        $('#on-demand-credits-row').addClass('tryon-hidden');

        $.post(ajaxurl, {
            action      : 'woo_fitroom_validate_license',
            nonce       : '<?php echo wp_create_nonce('fitroom_validate_license_nonce'); ?>',
            license_key : licenseKey
        }, function (response) {

            if (response.success) {
                resultDiv.addClass('tryon-status tryon-status-success')
                         .html('<p>' + response.data.message + '</p>').removeClass('tryon-hidden');
                statusDiv.html(
                    '<div class="tryon-status tryon-status-success"><strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Active', 'woo-fitroom-preview'); ?>' +
                    (response.data.expires  ? ' (Expires: ' + response.data.expires  + ')' : '') +
                    (response.data.credits !== undefined ? ' | Credits: ' + response.data.credits : '') +
                    '</div>'
                );

                var buyRow = $('#on-demand-credits-row');
                if (parseInt(response.data.credits, 10) <= 0) { buyRow.removeClass('tryon-hidden'); } else { buyRow.addClass('tryon-hidden'); }

            } else {
                resultDiv.addClass('tryon-status tryon-status-error')
                         .html('<p>' + response.data.message + '</p>').removeClass('tryon-hidden');
                statusDiv.html(
                    '<div class="tryon-status tryon-status-error"><strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Invalid or Expired', 'woo-fitroom-preview'); ?></div>'
                );
                $('#on-demand-credits-row').addClass('tryon-hidden');
            }

        }).fail(function () {
            resultDiv.addClass('tryon-status tryon-status-error')
                     .html('<p><?php _e('AJAX error validating license.', 'woo-fitroom-preview'); ?></p>').removeClass('tryon-hidden');
            statusDiv.html(
                '<div class="tryon-status tryon-status-error"><strong><?php _e('Status:', 'woo-fitroom-preview'); ?></strong> <?php _e('Validation Error', 'woo-fitroom-preview'); ?></div>'
            );
            $('#on-demand-credits-row').addClass('tryon-hidden');

        }).always(function () {
            validateBtn.prop('disabled', false)
                       .text('<?php _e('Validate Key', 'woo-fitroom-preview'); ?>');
        });
    }

    validateLicense();
    $('#validate-license-key').on('click', e => { e.preventDefault(); validateLicense(); });

    /* ----------------------------------------------------------------
     *  FREE PLAN TOPUP FUNCTIONALITY
     * ---------------------------------------------------------------- */
    // FREE PLAN TOPUP - Try For Free button click handler
    $('#try-for-free-button').on('click', function(e) {
        e.preventDefault();
        
        // FREE PLAN TOPUP - Product ID for the free plan (3 credits, 3 days)
        const freePlanProductId = <?php echo defined('FITROOM_PLAN_FREE_PRODUCT_ID') ? FITROOM_PLAN_FREE_PRODUCT_ID : 5961; ?>;
        
        // FREE PLAN TOPUP - Redirect to checkout with free plan product
        window.location.href = 'https://tryontool.com/checkout/?add-to-cart=' + freePlanProductId;
    });

    /* ----------------------------------------------------------------
     *  CONSENT RECORDS MODAL  (unchanged)
     * ---------------------------------------------------------------- */
    var consentNonce = '<?php echo wp_create_nonce('FITROOM_get_consents'); ?>';
    var exportNonce = '<?php echo wp_create_nonce('FITROOM_export_consents'); ?>';
    


    $('#view-consent-records').on('click', function () {
        var modal = $('#consent-records-modal');
        modal.find('.tryon-modal-body').html('<p><?php _e('Loading...', 'woo-fitroom-preview'); ?></p>');
        modal.removeClass('tryon-hidden');

        $.post(ajaxurl, {
            action : 'WOO_FITROOM_get_consents',
            nonce  : consentNonce
        }, function (res) {
            if (res.success) {
                var html = '<table class="widefat fixed striped"><thead><tr><th>User&nbsp;ID</th><th>Email</th><th>Consent&nbsp;Given</th><th>Last&nbsp;Login</th></tr></thead><tbody>';
                if (res.data.length) {
                    res.data.forEach(function (r) {
                        var consentDate = r.consent_timestamp ? new Date(r.consent_timestamp).toLocaleString() : 'Not given';
                        var lastLogin = r.last_login ? new Date(r.last_login).toLocaleString() : 'Never';
                        
                        html += '<tr><td>' + r.user_id + '</td><td>' + r.email + '</td><td>' + consentDate + '</td><td>' + lastLogin + '</td></tr>';
                    });
                } else {
                    html += '<tr><td colspan="4"><?php _e('No records found.', 'woo-fitroom-preview'); ?></td></tr>';
                }
                html += '</tbody></table>';
                modal.find('.tryon-modal-body').html(html);
            } else {
                modal.find('.tryon-modal-body').html('<p>' + (res.data && res.data.message ? res.data.message : 'Error') + '</p>');
            }
        }).fail(function () {
            modal.find('.tryon-modal-body').html('<p><?php _e('Ajax error.', 'woo-fitroom-preview'); ?></p>');
        });
    });

    $(document).on('click', '.close-consent-modal', function () { $('#consent-records-modal').addClass('tryon-hidden'); });
    $(window).on('click', function (e) {
        if (e.target === document.getElementById('consent-records-modal')) {
            $('#consent-records-modal').addClass('tryon-hidden');
        }
         });

    /* ----------------------------------------------------------------
     *  EXPORT CONSENT RECORDS
     * ---------------------------------------------------------------- */
    $('#export-consent-records').on('click', function () {
        // Show loading state
        var $button = $(this);
        var originalText = $button.text();
        $button.text('<?php _e('Exporting...', 'woo-fitroom-preview'); ?>').prop('disabled', true);
        
        // Create a form to submit the export request
        var form = $('<form>', {
            'method': 'POST',
            'action': ajaxurl,
            'target': '_blank'
        });
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'WOO_FITROOM_export_consents'
        }));
        
        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': exportNonce
        }));
        
        // Add form to body and submit
        $('body').append(form);
        form.submit();
        form.remove();
        
        // Reset button state after a short delay
        setTimeout(function() {
            $button.text(originalText).prop('disabled', false);
        }, 2000);
         });

    /* ----------------------------------------------------------------
     *  TAB FUNCTIONALITY
     * ---------------------------------------------------------------- */
    $('.tryon-nav-tab').on('click', function(e) {
        e.preventDefault();
        
        // Remove active class from all tabs
        $('.tryon-nav-tab').removeClass('tryon-nav-tab-active');
        $('.tab-content').addClass('tryon-hidden');
        
        // Add active class to clicked tab
        $(this).addClass('tryon-nav-tab-active');
        
        // Show corresponding content
        const tab = $(this).data('tab');
        $('#' + tab + '-tab').removeClass('tryon-hidden');
    });

    /* ----------------------------------------------------------------
     *  TOGGLE SWITCH FUNCTIONALITY
     * ---------------------------------------------------------------- */
    // Handle toggle switch clicks
    $('.tryon-toggle-slider').on('click', function() {
        const toggleWrapper = $(this).closest('.tryon-toggle-wrapper');
        const toggleInput = toggleWrapper.find('.tryon-toggle-input');
        const isChecked = toggleInput.is(':checked');
        
        // Toggle the checkbox state
        toggleInput.prop('checked', !isChecked);
        
        // Trigger change event for any other listeners
        toggleInput.trigger('change');
        
        console.log('Toggle clicked:', toggleInput.attr('name'), 'New state:', !isChecked);
    });
    
    // Handle direct clicks on the toggle input (if somehow clicked)
    $('.tryon-toggle-input').on('change', function() {
        console.log('Toggle input changed:', $(this).attr('name'), 'State:', $(this).is(':checked'));
    });
    
    // Form submission handling - all values are properly submitted

     /* ----------------------------------------------------------------
      *  APPEARANCE SETTINGS FUNCTIONALITY
      * ---------------------------------------------------------------- */
     
     // Store the last custom color for memory functionality
     let lastCustomColor = '<?php echo esc_js(get_option('WOO_FITROOM_custom_button_color', '#FF6E0E')); ?>';
     let userHasChangedCustomColor = false;
     
     // Toggle custom color row based on radio selection
     $('input[name="WOO_FITROOM_use_theme_colors"]').on('change', function() {
         const useThemeColors = $(this).val() === '1';
         if (useThemeColors) {
             $('#custom-color-row').addClass('tryon-hidden').hide();
         } else {
             $('#custom-color-row').removeClass('tryon-hidden').show();
             
             // If user hasn't manually changed the custom color, restore the last custom color
             if (!userHasChangedCustomColor) {
                 $('#WOO_FITROOM_custom_button_color').val(lastCustomColor);
                 $('#WOO_FITROOM_custom_button_color_text').val(lastCustomColor);
                 $('#color-display').text(lastCustomColor);
             }
         }
     });

     // Sync color picker with text input
     $('#WOO_FITROOM_custom_button_color').on('input', function() {
         const color = $(this).val();
         $('#WOO_FITROOM_custom_button_color_text').val(color);
         $('#color-display').text(color);
         lastCustomColor = color;
         userHasChangedCustomColor = true;
     });

     // Sync text input with color picker
     $('#WOO_FITROOM_custom_button_color_text').on('input', function() {
         let color = $(this).val();
         
         // Add # if missing
         if (color && !color.startsWith('#')) {
             color = '#' + color;
         }
         
         // Validate hex color
         if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
             $('#WOO_FITROOM_custom_button_color').val(color);
             $('#color-display').text(color);
             lastCustomColor = color;
             userHasChangedCustomColor = true;
             $(this).css('border-color', '#ddd');
         } else if (color.length > 0) {
             // Show error for invalid hex
             $(this).css('border-color', '#dc3545');
         } else {
             $(this).css('border-color', '#ddd');
         }
     });

     // Handle text input on blur (when user finishes typing)
     $('#WOO_FITROOM_custom_button_color_text').on('blur', function() {
         let color = $(this).val();
         
         // Add # if missing
         if (color && !color.startsWith('#')) {
             color = '#' + color;
         }
         
         // If valid hex, update everything
         if (/^#[0-9A-Fa-f]{6}$/.test(color)) {
             $('#WOO_FITROOM_custom_button_color').val(color);
             $('#color-display').text(color);
             lastCustomColor = color;
             userHasChangedCustomColor = true;
         } else if (color.length > 0) {
             // Reset to last valid color if invalid
             $(this).val(lastCustomColor);
             $('#WOO_FITROOM_custom_button_color').val(lastCustomColor);
             $('#color-display').text(lastCustomColor);
         }
         
         $(this).css('border-color', '#ddd');
     });

     /* ----------------------------------------------------------------
      *  PADDING SETTINGS FUNCTIONALITY
      * ---------------------------------------------------------------- */
     
     // Toggle custom padding row based on radio selection
     $('input[name="WOO_FITROOM_use_theme_padding"]').on('change', function() {
         const useThemePadding = $(this).val() === '1';
         if (useThemePadding) {
             $('#custom-padding-row').addClass('tryon-hidden').hide();
         } else {
             $('#custom-padding-row').removeClass('tryon-hidden').show();
         }
     });

     // Toggle custom border radius row based on radio selection
     $('input[name="WOO_FITROOM_use_theme_border_radius"]').on('change', function() {
         const useThemeBorderRadius = $(this).val() === '1';
         if (useThemeBorderRadius) {
             $('#custom-border-radius-row').addClass('tryon-hidden').hide();
         } else {
             $('#custom-border-radius-row').removeClass('tryon-hidden').show();
         }
     });

     // Validate padding inputs (ensure they're within reasonable bounds)
     $('input[id^="WOO_FITROOM_padding_"]').on('input', function() {
         let value = parseInt($(this).val());
         if (isNaN(value) || value < 0) {
             $(this).val(0);
         } else if (value > 50) {
             $(this).val(50);
         }
     });

     // Validate border radius inputs (ensure they're within reasonable bounds)
     $('input[id^="WOO_FITROOM_border_radius_"]').on('input', function() {
         let value = parseInt($(this).val());
         if (isNaN(value) || value < 0) {
             $(this).val(0);
         } else if (value > 100) {
             $(this).val(100);
        }
         });

    /* ----------------------------------------------------------------
     *  BUTTON TEXT MODE AND CHARACTER COUNTER
     * ---------------------------------------------------------------- */
    const buttonTextModeDefault = document.getElementById('WOO_FITROOM_button_text_mode_default');
    const buttonTextModeCustom = document.getElementById('WOO_FITROOM_button_text_mode_custom');
    const customButtonTextRow = document.getElementById('custom-button-text-row');
    const buttonTextInput = document.getElementById('WOO_FITROOM_custom_button_text');
    const currentLengthSpan = document.getElementById('current-length');
    
    // Handle radio button switching
    if (buttonTextModeDefault && buttonTextModeCustom && customButtonTextRow) {
        function toggleCustomTextRow() {
            if (buttonTextModeCustom.checked) {
                customButtonTextRow.classList.remove('tryon-hidden');
                customButtonTextRow.style.display = '';
            } else {
                customButtonTextRow.classList.add('tryon-hidden');
                customButtonTextRow.style.display = 'none';
            }
        }
        
        buttonTextModeDefault.addEventListener('change', toggleCustomTextRow);
        buttonTextModeCustom.addEventListener('change', toggleCustomTextRow);
        
        // Initialize on page load
        toggleCustomTextRow();
    }
    
    // Character counter functionality
    if (buttonTextInput && currentLengthSpan) {
        function updateCharacterCounter() {
            const currentLength = buttonTextInput.value.length;
            currentLengthSpan.textContent = currentLength;
            
            // Change color based on character count
            if (currentLength > 12) {
                currentLengthSpan.style.color = '#dc2626'; // Red for close to limit
            } else if (currentLength > 10) {
                currentLengthSpan.style.color = '#f59e0b'; // Orange for warning
            } else {
                currentLengthSpan.style.color = '#666'; // Default gray
            }
        }
        
        // Update counter on input
        buttonTextInput.addEventListener('input', updateCharacterCounter);
        
        // Initialize counter
        updateCharacterCounter();
    }

 });

   </script>
   </div>

<p class="tryon-mt-4" style="font-size:smaller; color: #6c757d;">
       Try-On Tool is Free Software, licensed under the GNU GPL v2. NO WARRANTY. 
    <a href="<?php echo plugin_dir_url( dirname( dirname( __FILE__ ) ) ); ?>COPYING.txt" target="_blank" style="color: #667eea;">View License</a>
   </p>
   
