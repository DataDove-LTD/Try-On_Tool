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
wp_enqueue_style('tryon-admin-analytics', plugin_dir_url(__FILE__) . '../../assets/css/admin-analytics.css', array(), '1.0.0');
// Ensure jQuery is available for admin page interactions and AJAX
wp_enqueue_script('jquery');
?>
<div class="tryon-admin-wrap">
       <div class="tryon-admin-header">
           <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
       </div>
       
       <!-- Tab Navigation -->
       <nav class="tryon-tab-wrapper">
           <a href="#general" class="tryon-nav-tab tryon-nav-tab-active" data-tab="general"><?php _e('General', 'woo-fitroom-preview'); ?></a>
           <a href="#appearance" class="tryon-nav-tab" data-tab="appearance"><?php _e('Appearance', 'woo-fitroom-preview'); ?></a>
          <a href="#ai-categories" class="tryon-nav-tab" data-tab="ai-categories"><?php _e('Try-On Tool Categories', 'woo-fitroom-preview'); ?></a>
          <a href="#analytics" class="tryon-nav-tab" data-tab="analytics"><?php _e('Analytics', 'woo-fitroom-preview'); ?></a>
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
                          <?php _e('Price: £0.00', 'woo-fitroom-preview'); ?>
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
           </table>
           </div>
           
           <!-- AI Categories Tab -->
           <div id="ai-categories-tab" class="tab-content tryon-hidden">
            <div class="tryon-notice tryon-notice-info tryon-mb-2">
                <p style="margin: 0;">
                    <strong><?php _e('Automatic Assignment:', 'woo-fitroom-preview'); ?></strong> 
                    <?php _e('Category assignments are now handled automatically. New categories will be assigned automatically when created, and existing categories are assigned on first access.', 'woo-fitroom-preview'); ?>
                </p>
            </div>
               <div id="tryon-category-assignments-table" class="tryon-category-table-container">
                   <table class="tryon-category-table" id="tryon-category-assignments-content" style="width: 100%; text-align: center;">
                       <thead>
                           <tr>
                               <th class="tryon-category-id"><?php _e('ID', 'woo-fitroom-preview'); ?></th>
                               <th class="tryon-category-name"><?php _e('Category', 'woo-fitroom-preview'); ?></th>
                               <th class="tryon-category-count"><?php _e('Products', 'woo-fitroom-preview'); ?></th>
                               <th class="tryon-category-assignment"><?php _e('Assignment', 'woo-fitroom-preview'); ?></th>
                           </tr>
                       </thead>
                       <tbody id="tryon-category-assignments-tbody"></tbody>
                   </table>
               </div>
               <div id="tryon-ai-status" class="tryon-mt-2"></div>
           </div>
           
           <!-- Analytics Tab -->
           <div id="analytics-tab" class="tab-content tryon-hidden">
               <div class="tryon-analytics-dashboard">
                   <div class="tryon-analytics-header">
                       <h2><?php _e('Analytics Dashboard', 'woo-fitroom-preview'); ?></h2>
                       <div class="tryon-analytics-controls">
                           <button type="button" id="refresh-analytics" class="tryon-btn tryon-btn-secondary">
                               <span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'woo-fitroom-preview'); ?>
                           </button>
                           <span class="tryon-last-updated" id="last-updated"></span>
                       </div>
                   </div>
                   
                   <!-- Site Information Section -->
                   <div class="tryon-site-info-section">
                       <h3><?php _e('Site Information', 'woo-fitroom-preview'); ?></h3>
                       <div class="tryon-site-info-grid">
                           <div class="tryon-site-info-item">
                               <strong><?php _e('Site URL:', 'woo-fitroom-preview'); ?></strong>
                               <span id="site-url"><?php echo esc_url(home_url()); ?></span>
                           </div>
                           <div class="tryon-site-info-item">
                               <strong><?php _e('WordPress Version:', 'woo-fitroom-preview'); ?></strong>
                               <span id="wp-version"><?php echo get_bloginfo('version'); ?></span>
                           </div>
                           <div class="tryon-site-info-item">
                               <strong><?php _e('WooCommerce Version:', 'woo-fitroom-preview'); ?></strong>
                               <span id="wc-version"><?php echo defined('WC_VERSION') ? WC_VERSION : __('Not Active', 'woo-fitroom-preview'); ?></span>
                           </div>
                           <div class="tryon-site-info-item">
                               <strong><?php _e('Plugin Version:', 'woo-fitroom-preview'); ?></strong>
                               <span id="plugin-version"><?php echo defined('WOO_FITROOM_PREVIEW_VERSION') ? WOO_FITROOM_PREVIEW_VERSION : '1.3.0'; ?></span>
                           </div>
                           <div class="tryon-site-info-item">
                               <strong><?php _e('Server Data Status:', 'woo-fitroom-preview'); ?></strong>
                               <span id="server-data-status"><?php _e('Checking...', 'woo-fitroom-preview'); ?></span>
                           </div>
                       </div>
                   </div>
                   
                   <!-- Key Metrics Cards -->
                   <div class="tryon-metrics-grid">
                       <div class="tryon-metric-card">
                           <div class="tryon-metric-icon">👥</div>
                           <div class="tryon-metric-content">
                               <div class="tryon-metric-value" id="total-users">-</div>
                               <div class="tryon-metric-label"><?php _e('Total Users', 'woo-fitroom-preview'); ?></div>
                           </div>
                       </div>
                       
                       <div class="tryon-metric-card">
                           <div class="tryon-metric-icon">🖼️</div>
                           <div class="tryon-metric-content">
                               <div class="tryon-metric-value" id="total-images">-</div>
                               <div class="tryon-metric-label"><?php _e('Images Stored', 'woo-fitroom-preview'); ?></div>
                           </div>
                       </div>
                       
                       <div class="tryon-metric-card">
                           <div class="tryon-metric-icon">💾</div>
                           <div class="tryon-metric-content">
                               <div class="tryon-metric-value" id="storage-used">-</div>
                               <div class="tryon-metric-label"><?php _e('Storage Used', 'woo-fitroom-preview'); ?></div>
                           </div>
                       </div>
                       
                       <div class="tryon-metric-card">
                           <div class="tryon-metric-icon">⚡</div>
                           <div class="tryon-metric-content">
                               <div class="tryon-metric-value" id="active-users">-</div>
                               <div class="tryon-metric-label"><?php _e('Active Users (10d)', 'woo-fitroom-preview'); ?></div>
                           </div>
                       </div>
                   </div>
                   
                   <!-- Charts Row -->
                   <div class="tryon-charts-row">
                       <div class="tryon-chart-container">
                           <h3><?php _e('Preview Generation Trend (Last 30 Days)', 'woo-fitroom-preview'); ?></h3>
                           <div class="tryon-chart-wrapper">
                               <canvas id="preview-trend-chart" width="400" height="200"></canvas>
                           </div>
                           <div class="tryon-chart-info">
                               <small><?php _e('Shows daily preview generation activity from users with consent', 'woo-fitroom-preview'); ?></small>
                               <br><small><strong><?php _e('Current Date:', 'woo-fitroom-preview'); ?> <?php echo date('F j, Y'); ?></strong></small>
                           </div>
                       </div>
                       
                       <div class="tryon-stats-container">
                           <div class="tryon-stat-box">
                               <h4><?php _e('Success Rate', 'woo-fitroom-preview'); ?></h4>
                               <div class="tryon-stat-subtext"><?php _e('Coming Soon', 'woo-fitroom-preview'); ?></div>
                               <div class="tryon-stat-value" id="success-rate">-</div>
                           </div>
                           
                           <div class="tryon-stat-box">
                               <h4><?php _e('Total Previews', 'woo-fitroom-preview'); ?></h4>
                               <div class="tryon-stat-value" id="total-previews">-</div>
                           </div>
                           
                           <div class="tryon-stat-box">
                               <h4><?php _e('Top Categories', 'woo-fitroom-preview'); ?></h4>
                               <div class="tryon-stat-subtext"><?php _e('Coming Soon', 'woo-fitroom-preview'); ?></div>
                               <div class="tryon-categories-list" id="top-categories">
                                   <div class="tryon-loading"><?php _e('Loading...', 'woo-fitroom-preview'); ?></div>
                               </div>
                           </div>
                       </div>
                   </div>
                   
                   <!-- Data Verification Section -->
                   <div class="tryon-verification-section">
                       <h3><?php _e('Data Verification & Debug', 'woo-fitroom-preview'); ?></h3>
                       <div class="tryon-verification-grid">
                           <div class="tryon-verification-item">
                               <strong><?php _e('Database Tables:', 'woo-fitroom-preview'); ?></strong>
                               <span id="db-tables-status"><?php _e('Checking...', 'woo-fitroom-preview'); ?></span>
                           </div>
                           <div class="tryon-verification-item">
                               <strong><?php _e('Tracking Hooks:', 'woo-fitroom-preview'); ?></strong>
                               <span id="hooks-status"><?php _e('Checking...', 'woo-fitroom-preview'); ?></span>
                           </div>
                           <div class="tryon-verification-item">
                               <strong><?php _e('Cache Status:', 'woo-fitroom-preview'); ?></strong>
                               <span id="cache-status"><?php _e('Checking...', 'woo-fitroom-preview'); ?></span>
                           </div>
                           <div class="tryon-verification-item">
                               <strong><?php _e('License Key:', 'woo-fitroom-preview'); ?></strong>
                               <span id="license-status"><?php echo get_option('WOO_FITROOM_license_key') ? __('Set', 'woo-fitroom-preview') : __('Not Set', 'woo-fitroom-preview'); ?></span>
                           </div>
                       </div>
                       
                       <div class="tryon-debug-section" style="margin-top: 20px;">
                           <h4><?php _e('Debug Actions', 'woo-fitroom-preview'); ?></h4>
                           <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                               <button type="button" id="test-tracking" class="tryon-btn tryon-btn-secondary">
                                   <?php _e('Test Tracking', 'woo-fitroom-preview'); ?>
                               </button>
                               <button type="button" id="clear-cache" class="tryon-btn tryon-btn-secondary">
                                   <?php _e('Clear Cache', 'woo-fitroom-preview'); ?>
                               </button>
                               <button type="button" id="check-db" class="tryon-btn tryon-btn-secondary">
                                   <?php _e('Check Database', 'woo-fitroom-preview'); ?>
                               </button>
                               <button type="button" id="check-consents" class="tryon-btn tryon-btn-secondary">
                                   <?php _e('Check Consents', 'woo-fitroom-preview'); ?>
                               </button>
                               <button type="button" id="check-active-users" class="tryon-btn tryon-btn-secondary">
                                   <?php _e('Check Active Users', 'woo-fitroom-preview'); ?>
                               </button>
                               <button type="button" id="clear-logs" class="tryon-btn tryon-btn-warning">
                                   <?php _e('Clear Logs', 'woo-fitroom-preview'); ?>
                               </button>
                           </div>
                           <div id="debug-output" style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 4px; font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; display: none;">
                               <div id="debug-content"></div>
                           </div>
                       </div>
                   </div>
               </div>
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
// Ensure jQuery $ alias is available globally for the handlers below
if (typeof window.jQuery !== 'undefined' && typeof window.$ === 'undefined') { window.$ = window.jQuery; }
document.addEventListener('DOMContentLoaded', function () {

    // Ensure ajaxurl is available for admin-ajax requests
    if (typeof window.ajaxurl === 'undefined') {
        window.ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    }

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
        priceLbl.textContent        = '£' + creditPacks[selectedCredits].price;
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
    //     window.location.href = 'https://tryontool.com/my-account/subscriptions/';
    // });



    /* ----------------------------------------------------------------
     *  LICENSE VALIDATION  (unchanged)
     * ---------------------------------------------------------------- */
    const $ = jQuery;
    // Try-On Tool Categories: client handlers
    function tryonRenderRows(categories){
        const $tbody = $('#tryon-category-assignments-tbody');
        $tbody.empty();
        (categories||[]).forEach(function(cat){
            var current = cat.current_assignment || '';
            var row = '<tr>'+
                '<td class="tryon-category-id">'+cat.id+'</td>'+
                '<td class="tryon-category-name">'+cat.name+'</td>'+
                '<td class="tryon-category-count">'+cat.product_count+'</td>'+
                '<td class="tryon-category-assignment">'+
                    '<div class="tryon-radio-group-grid">'+
                        '<div class="tryon-radio-row">'+
                            '<label class="tryon-radio-item'+(current==='top'?' checked':'')+'"><input type="radio" name="assign_'+cat.id+'" value="top"'+(current==='top'?' checked':'')+'><?php echo esc_js(__('Top','woo-fitroom-preview')); ?></label>'+
                            '<label class="tryon-radio-item'+(current==='bottom'?' checked':'')+'"><input type="radio" name="assign_'+cat.id+'" value="bottom"'+(current==='bottom'?' checked':'')+'><?php echo esc_js(__('Bottom','woo-fitroom-preview')); ?></label>'+
                            '<label class="tryon-radio-item'+(current==='full'?' checked':'')+'"><input type="radio" name="assign_'+cat.id+'" value="full"'+(current==='full'?' checked':'')+'><?php echo esc_js(__('Full Outfit','woo-fitroom-preview')); ?></label>'+
                            '<label class="tryon-radio-item'+(current==='none'?' checked':'')+'"><input type="radio" name="assign_'+cat.id+'" value="none"'+(current==='none'?' checked':'')+'><?php echo esc_js(__('None','woo-fitroom-preview')); ?></label>'+
                        '</div>'+
                    '</div>'+
                '</td>'+
            '</tr>';
            $tbody.append(row);
        });
        
        // Add event listeners for radio button changes
        $tbody.find('input[type="radio"]').on('change', function() {
            var $item = $(this).closest('.tryon-radio-item');
            var $row = $item.closest('.tryon-radio-row');
            
            // Remove checked class from all items in this row
            $row.find('.tryon-radio-item').removeClass('checked');
            
            // Add checked class to the selected item
            $item.addClass('checked');
            
            // Mark that there are unsaved changes
            $('#tryon-ai-status').html('<span style="color: #ff6b35;"><?php echo esc_js(__('You have unsaved changes. Click "Save Settings" to save all changes.','woo-fitroom-preview')); ?></span>');
        });
    }
    // Function to save all category assignments
    function saveAllCategoryAssignments() {
        var assignments = [];
        var hasChanges = false;
        
        // Collect all assignments
        $('#tryon-category-assignments-tbody tr').each(function() {
            var $row = $(this);
            var id = $row.find('.tryon-category-id').text();
            var name = $row.find('.tryon-category-name').text();
            var val = $row.find('input[type="radio"]:checked').val();
            
            if (id && name && val) {
                assignments.push({
                    category_id: parseInt(id, 10),
                    category_name: name,
                    assignment: val
                });
                hasChanges = true;
            }
        });
        
        if (!hasChanges) {
            return Promise.resolve();
        }
        
        $('#tryon-ai-status').text('<?php echo esc_js(__('Saving all assignments...','woo-fitroom-preview')); ?>');
        
        return new Promise(function(resolve, reject) {
            (function tryNext(i) {
                const bases = tryonApiBases();
                if (i >= bases.length) {
                    $('#tryon-ai-status').text('<?php echo esc_js(__('Save failed: ','woo-fitroom-preview')); ?>' + 'No route');
                    reject(new Error('No API route available'));
                    return;
                }
                
                // Save assignments one by one
                var savePromises = assignments.map(function(assignment) {
                    return $.ajax({
                        url: bases[i] + '/category-assignment',
                        method: 'POST',
                        contentType: 'application/json',
                        data: JSON.stringify(assignment),
                        beforeSend: function(xhr) {
                            xhr.setRequestHeader('X-WP-Nonce', '<?php echo wp_create_nonce('wp_rest'); ?>');
                        }
                    });
                });
                
                Promise.all(savePromises)
                    .then(function() {
                        $('#tryon-ai-status').text('<?php echo esc_js(__('All assignments saved successfully.','woo-fitroom-preview')); ?>');
                        resolve();
                    })
                    .catch(function(xhr) {
                        console.log('Save failed:', xhr);
                        tryNext(i + 1);
                    });
            })(0);
        });
    }
    
    // Function to load categories
    function tryonLoadCategories() {
        $('#tryon-ai-status').html('<div class="tryon-status tryon-status-info"><?php _e('Loading categories...', 'woo-fitroom-preview'); ?></div>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'WOO_FITROOM_get_categories',
                nonce: '<?php echo wp_create_nonce('fitroom_categories_nonce'); ?>'
            },
            success: function(response) {
                if (response.success && response.data.categories) {
                    const tbody = $('#tryon-category-assignments-tbody');
                    tbody.empty();
                    
                    response.data.categories.forEach(function(category) {
                        const assignment = category.assignment || '';
                        const row = `
                            <tr>
                                <td class="tryon-category-id"><strong>${category.id}</strong></td>
                                <td class="tryon-category-name"><strong>${category.name}</strong></td>
                                <td class="tryon-category-count">${category.count}</td>
                                <td class="tryon-category-assignment">
                                    <div class="tryon-radio-group-grid">
                                        <div class="tryon-radio-row">
                                            <label class="tryon-radio-item ${assignment === 'top' ? 'checked' : ''}">
                                                <input type="radio" name="assignment_${category.id}" value="top" ${assignment === 'top' ? 'checked' : ''}>
                                                <span><?php _e('Top', 'woo-fitroom-preview'); ?></span>
                                            </label>
                                            <label class="tryon-radio-item ${assignment === 'bottom' ? 'checked' : ''}">
                                                <input type="radio" name="assignment_${category.id}" value="bottom" ${assignment === 'bottom' ? 'checked' : ''}>
                                                <span><?php _e('Bottom', 'woo-fitroom-preview'); ?></span>
                                            </label>
                                            <label class="tryon-radio-item ${assignment === 'full' ? 'checked' : ''}">
                                                <input type="radio" name="assignment_${category.id}" value="full" ${assignment === 'full' ? 'checked' : ''}>
                                                <span><?php _e('Full Outfit', 'woo-fitroom-preview'); ?></span>
                                            </label>
                                            <label class="tryon-radio-item ${assignment === 'none' ? 'checked' : ''}">
                                                <input type="radio" name="assignment_${category.id}" value="none" ${assignment === 'none' ? 'checked' : ''}>
                                                <span><?php _e('None', 'woo-fitroom-preview'); ?></span>
                                            </label>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        `;
                        tbody.append(row);
                    });
                    
                    // Add event listeners to radio buttons to update checked state
                    $('input[type="radio"][name^="assignment_"]').on('change', function() {
                        const row = $(this).closest('tr');
                        row.find('.tryon-radio-item').removeClass('checked');
                        $(this).closest('.tryon-radio-item').addClass('checked');
                    });
                    
                    $('#tryon-ai-status').html('<div class="tryon-status tryon-status-success"><?php _e('Categories loaded successfully.', 'woo-fitroom-preview'); ?></div>');
                } else {
                    $('#tryon-ai-status').html('<div class="tryon-status tryon-status-error"><?php _e('Failed to load categories.', 'woo-fitroom-preview'); ?></div>');
                }
            },
            error: function() {
                $('#tryon-ai-status').html('<div class="tryon-status tryon-status-error"><?php _e('Error loading categories.', 'woo-fitroom-preview'); ?></div>');
            }
        });
    }
    
    // Auto-load when tab opened and if hash is for this tab
    $('.tryon-nav-tab[data-tab="ai-categories"]').on('click', function(){ if (!$('#tryon-category-assignments-tbody').children().length) { tryonLoadCategories(); } });
    if (window.location.hash === '#ai-categories') { tryonLoadCategories(); }

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
                        var consent = r.consent_timestamp || r.timestamp || '';
                        html += '<tr><td>' + r.user_id + '</td><td>' + r.email + '</td><td>' + consent + '</td><td>' + (r.last_login || '') + '</td></tr>';
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
        
        // If analytics tab is clicked, initialize analytics
        if (tab === 'analytics') {
            // Small delay to ensure tab is visible before loading chart
            setTimeout(function() {
                if (typeof Chart === 'undefined') {
                    loadChartJS().then(function() {
                        // Wait a bit more for Chart.js to fully initialize
                        setTimeout(function() {
                            loadAnalyticsData();
                        }, 50);
                    }).catch(function() {
                        showAnalyticsError('Failed to load Chart.js');
                    });
                } else {
                    loadAnalyticsData();
                }
            }, 150);
        }
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
    
    // Save category assignments on form submit
    $('form').on('submit', function(e) {
        console.log('Form submit event triggered');
        console.log('Category assignment radio buttons found:', $('input[name^="assignment_"]:checked').length);
        
        // Check if there are any category assignment radio buttons
        if ($('input[name^="assignment_"]:checked').length > 0) {
            console.log('Preventing default form submission to save category assignments');
            e.preventDefault();
            var $form = $(this);
            var formElement = this;
            
            // Save category assignments before form submission
            tryonSaveCategoryAssignments().then(function() {
                console.log('Category assignments saved successfully, submitting form');
                // Show success message
                jQuery('#tryon-ai-status').html('<div class="tryon-status tryon-status-success"><?php _e('Category assignments saved successfully! Submitting form...', 'woo-fitroom-preview'); ?></div>');
                // After category assignments are saved, submit the form normally using native method
                setTimeout(function() {
                    formElement.submit();
                }, 1000); // Small delay to show the message
            }).catch(function(error) {
                console.error('Failed to save category assignments:', error);
                // Still submit the form even if category assignments fail
                formElement.submit();
            });
        } else {
            console.log('No category assignments to save, allowing normal form submission');
            // Show a general saving message
            jQuery('#tryon-ai-status').html('<div class="tryon-status tryon-status-info"><?php _e('Saving settings...', 'woo-fitroom-preview'); ?></div>');
        }
        // If no category assignments, let the form submit normally
    });
    
    // Show success message if settings were saved
    if (window.location.search.includes('settings-updated=true')) {
        jQuery('#tryon-ai-status').html('<div class="tryon-status tryon-status-success"><?php _e('Settings saved successfully!', 'woo-fitroom-preview'); ?></div>');
    }

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

    });
    
    // Save category assignments
    function tryonSaveCategoryAssignments() {
        return new Promise(function(resolve, reject) {
            const assignments = {};
            jQuery('input[name^="assignment_"]:checked').each(function() {
                const categoryId = jQuery(this).attr('name').replace('assignment_', '');
                assignments[categoryId] = jQuery(this).val();
            });
            
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'WOO_FITROOM_save_categories',
                    assignments: assignments,
                    nonce: '<?php echo wp_create_nonce('fitroom_categories_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        jQuery('#tryon-ai-status').html('<div class="tryon-status tryon-status-success">' + response.data.message + '</div>');
                        resolve(response);
                    } else {
                        jQuery('#tryon-ai-status').html('<div class="tryon-status tryon-status-error">' + response.data.message + '</div>');
                        reject(new Error(response.data.message));
                    }
                },
                error: function() {
                    jQuery('#tryon-ai-status').html('<div class="tryon-status tryon-status-error"><?php _e('Error saving assignments.', 'woo-fitroom-preview'); ?></div>');
                    reject(new Error('AJAX error'));
                }
            });
        });
    }
    
    /* ----------------------------------------------------------------
     *  ANALYTICS DASHBOARD
     * ---------------------------------------------------------------- */
    let analyticsChart = null;
    
    // Load Chart.js if not already loaded
    function loadChartJS() {
        return new Promise((resolve, reject) => {
            if (typeof Chart !== 'undefined') {
                resolve();
                return;
            }
            
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }
    
    // Load analytics data
    function loadAnalyticsData() {
        const refreshBtn = document.getElementById('refresh-analytics');
        if (refreshBtn) {
            refreshBtn.disabled = true;
            refreshBtn.innerHTML = '<span class="dashicons dashicons-update"></span> <?php _e('Loading...', 'woo-fitroom-preview'); ?>';
        }
        
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_get_analytics_data',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            if (response && response.success) {
                if (!response.data) {
                    showAnalyticsError('No data received from server');
                    return;
                }
                
                updateAnalyticsDisplay(response.data);
                verifyAnalyticsData();
            } else {
                const errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Unknown error';
                showAnalyticsError('Failed to load analytics data: ' + errorMsg);
                
                // Show error in UI
                const errorDiv = document.createElement('div');
                errorDiv.className = 'tryon-analytics-error';
                errorDiv.textContent = 'Error: ' + errorMsg;
                const dashboard = document.querySelector('.tryon-analytics-dashboard');
                if (dashboard) {
                    dashboard.insertBefore(errorDiv, dashboard.firstChild);
                }
            }
        }).fail(function(xhr, status, error) {
            showAnalyticsError('Network error loading analytics data: ' + error);
            
            // Show error in UI
            const errorDiv = document.createElement('div');
            errorDiv.className = 'tryon-analytics-error';
            errorDiv.textContent = 'Network Error: ' + error + ' (Status: ' + status + ')';
            const dashboard = document.querySelector('.tryon-analytics-dashboard');
            if (dashboard) {
                dashboard.insertBefore(errorDiv, dashboard.firstChild);
            }
        }).always(function() {
            if (refreshBtn) {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = '<span class="dashicons dashicons-update"></span> <?php _e('Refresh', 'woo-fitroom-preview'); ?>';
            }
        });
    }
    
    // Update analytics display
    function updateAnalyticsDisplay(data) {
        if (!data) {
            return;
        }
        
        // Update metric cards with null checks
        const totalUsersEl = document.getElementById('total-users');
        const totalImagesEl = document.getElementById('total-images');
        const storageUsedEl = document.getElementById('storage-used');
        const activeUsersEl = document.getElementById('active-users');
        const successRateEl = document.getElementById('success-rate');
        const totalPreviewsEl = document.getElementById('total-previews');
        const lastUpdatedEl = document.getElementById('last-updated');
        
        if (totalUsersEl) {
            totalUsersEl.textContent = data.total_users !== undefined ? data.total_users : 0;
        }
        
        if (totalImagesEl) {
            totalImagesEl.textContent = data.total_images !== undefined ? data.total_images : 0;
        }
        
        if (storageUsedEl) {
            storageUsedEl.textContent = data.storage_used || '0 MB';
        }
        
        if (activeUsersEl) {
            activeUsersEl.textContent = data.active_users_10d !== undefined ? data.active_users_10d : 0;
        }
        
        if (successRateEl) {
            successRateEl.textContent = (data.success_rate !== undefined ? data.success_rate : 0) + '%';
        }
        
        if (totalPreviewsEl) {
            totalPreviewsEl.textContent = data.total_previews !== undefined ? data.total_previews : 0;
        }
        
        if (lastUpdatedEl) {
            lastUpdatedEl.textContent = 'Last updated: ' + (data.last_updated || 'Unknown');
        }
        
        // Update site information if available
        if (data.site_info) {
            // Update site info fields if they exist
            const siteUrlEl = document.getElementById('site-url');
            const wpVersionEl = document.getElementById('wp-version');
            const wcVersionEl = document.getElementById('wc-version');
            const pluginVersionEl = document.getElementById('plugin-version');
            const serverDataStatusEl = document.getElementById('server-data-status');
            
            if (data.site_info.site_url && siteUrlEl) {
                siteUrlEl.textContent = data.site_info.site_url;
            }
            if (data.site_info.wp_version && wpVersionEl) {
                wpVersionEl.textContent = data.site_info.wp_version;
            }
            if (data.site_info.wc_version !== null && wcVersionEl) {
                wcVersionEl.textContent = data.site_info.wc_version || '<?php _e('Not Active', 'woo-fitroom-preview'); ?>';
            }
            if (data.site_info.plugin_version && pluginVersionEl) {
                pluginVersionEl.textContent = data.site_info.plugin_version;
            }
            if (serverDataStatusEl) {
                serverDataStatusEl.textContent = data.site_info.license_key_set ? '<?php _e('Connected', 'woo-fitroom-preview'); ?>' : '<?php _e('No License Key', 'woo-fitroom-preview'); ?>';
            }
            
            showDebugOutput('Site Information loaded: ' + JSON.stringify(data.site_info, null, 2));
        }
        
        // Update top categories (intentionally left blank to show "Coming Soon" only)
        const categoriesContainer = document.getElementById('top-categories');
        if (categoriesContainer) {
            categoriesContainer.innerHTML = '';
        }
        
        // Update chart
        if (data.preview_trend && Array.isArray(data.preview_trend)) {
            updatePreviewTrendChart(data.preview_trend);
        } else {
            updatePreviewTrendChart([]);
        }
    }
    
    // Update preview trend chart
    function updatePreviewTrendChart(trendData) {
        const canvas = document.getElementById('preview-trend-chart');
        if (!canvas) {
            return;
        }
        
        // Check if canvas is visible (chart won't render if hidden)
        const canvasParent = canvas.closest('.tab-content');
        if (canvasParent && canvasParent.classList.contains('tryon-hidden')) {
            // Wait for tab to become visible
            setTimeout(function() {
                updatePreviewTrendChart(trendData);
            }, 200);
            return;
        }
        
        if (!Array.isArray(trendData) || trendData.length === 0) {
            // Still create chart with empty data so it's visible
            trendData = [];
            for (let i = 0; i < 30; i++) {
                const date = new Date();
                date.setDate(date.getDate() - (29 - i));
                trendData.push({
                    date: date.toISOString().split('T')[0],
                    previews: 0
                });
            }
        }
        
        // Create labels for last 30 days
        const labels = trendData.map(item => {
            try {
                const date = new Date(item.date + 'T00:00:00');
                return date.toLocaleDateString('en-US', { 
                    month: 'short', 
                    day: 'numeric' 
                });
            } catch (e) {
                return item.date;
            }
        });
        
        const data = trendData.map(item => parseInt(item.previews) || 0);
        const maxValue = Math.max(...data, 1); // Ensure at least 1 for proper scaling
        
        if (analyticsChart) {
            analyticsChart.destroy();
            analyticsChart = null;
        }
        
        // Chart.js v3+ accepts canvas element directly
        try {
            analyticsChart = new Chart(canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: '<?php _e('Previews Generated', 'woo-fitroom-preview'); ?>',
                    data: data,
                    borderColor: '#FF6E0E',
                    backgroundColor: 'rgba(255, 110, 14, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#FF6E0E',
                    pointBorderColor: '#FFFFFF',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                animation: {
                    duration: 750
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#FFFFFF',
                        bodyColor: '#FFFFFF',
                        borderColor: '#FF6E0E',
                        borderWidth: 1,
                        callbacks: {
                            title: function(context) {
                                const date = new Date(trendData[context[0].dataIndex].date);
                                return date.toLocaleDateString('en-US', { 
                                    weekday: 'long',
                                    year: 'numeric',
                                    month: 'long',
                                    day: 'numeric'
                                });
                            },
                            label: function(context) {
                                const value = context.parsed.y;
                                return value === 1 ? '1 Preview' : value + ' Previews';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        title: {
                            display: true,
                            text: '<?php _e('Days', 'woo-fitroom-preview'); ?>',
                            color: '#666',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 10,
                            color: '#666'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '<?php _e('Number of Previews', 'woo-fitroom-preview'); ?>',
                            color: '#666',
                            font: {
                                size: 12,
                                weight: 'bold'
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)',
                            drawBorder: false
                        },
                        ticks: {
                            stepSize: Math.ceil(maxValue / 5),
                            color: '#666',
                            callback: function(value) {
                                return value === 0 ? '0' : value;
                            }
                        }
                    }
                }
            }
        });
        
        // Force chart resize after creation to ensure proper display
        setTimeout(function() {
            if (analyticsChart) {
                if (typeof analyticsChart.resize === 'function') {
                    analyticsChart.resize();
                }
                // Also try update to ensure it renders
                analyticsChart.update();
            }
        }, 200);
        
        } catch (error) {
            showAnalyticsError('Failed to create chart: ' + error.message);
        }
    }
    
    // Verify analytics data
    function verifyAnalyticsData() {
        // Check database tables
        const dbStatus = document.getElementById('db-tables-status');
        if (dbStatus) {
            dbStatus.textContent = '<?php _e('Active', 'woo-fitroom-preview'); ?>';
            dbStatus.style.color = '#28a745';
        }
        
        // Check tracking hooks
        const hooksStatus = document.getElementById('hooks-status');
        if (hooksStatus) {
            hooksStatus.textContent = '<?php _e('Active', 'woo-fitroom-preview'); ?>';
            hooksStatus.style.color = '#28a745';
        }
        
        // Check cache status
        const cacheStatus = document.getElementById('cache-status');
        if (cacheStatus) {
            const cacheData = '<?php echo get_transient("tryon_analytics_overview") ? "Cached" : "Not cached"; ?>';
            cacheStatus.textContent = cacheData;
            cacheStatus.style.color = cacheData === 'Cached' ? '#28a745' : '#ffc107';
        }
        
        // Check server data status
        const serverStatus = document.getElementById('server-data-status');
        if (serverStatus) {
            const licenseKey = '<?php echo get_option("WOO_FITROOM_license_key") ? "Set" : "Not Set"; ?>';
            if (licenseKey === 'Set') {
                serverStatus.textContent = '<?php _e('Connected', 'woo-fitroom-preview'); ?>';
                serverStatus.style.color = '#28a745';
            } else {
                serverStatus.textContent = '<?php _e('No License Key', 'woo-fitroom-preview'); ?>';
                serverStatus.style.color = '#dc3545';
            }
        }
    }
    
    // Show analytics error
    function showAnalyticsError(message) {
        // Show error message in the analytics dashboard
        const dashboard = document.querySelector('.tryon-analytics-dashboard');
        if (dashboard) {
            // Remove any existing error messages
            const existingErrors = dashboard.querySelectorAll('.tryon-analytics-error');
            existingErrors.forEach(el => el.remove());
            
            // Create new error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'tryon-analytics-error';
            errorDiv.style.cssText = 'background: #f8d7da; color: #721c24; padding: 15px; border-radius: 6px; border: 1px solid #f5c6cb; margin: 20px 0;';
            errorDiv.innerHTML = '<strong><?php _e('Error:', 'woo-fitroom-preview'); ?></strong> ' + message;
            
            // Insert at the top of the dashboard
            const header = dashboard.querySelector('.tryon-analytics-header');
            if (header && header.nextSibling) {
                dashboard.insertBefore(errorDiv, header.nextSibling);
            } else {
                dashboard.insertBefore(errorDiv, dashboard.firstChild);
            }
        }
    }
    
    // Show debug output
    function showDebugOutput(message) {
        const debugOutput = document.getElementById('debug-output');
        const debugContent = document.getElementById('debug-content');
        if (debugOutput && debugContent) {
            debugOutput.style.display = 'block';
            debugContent.innerHTML += '<div>' + message + '</div>';
            debugOutput.scrollTop = debugOutput.scrollHeight;
        }
    }
    
    // Note: Analytics initialization is now handled in the general tab click handler above
    // to avoid conflicts and ensure proper timing
    
    // Auto-load analytics data when page loads if analytics tab is active
    $(document).ready(function() {
        if ($('.tryon-nav-tab[data-tab="analytics"]').hasClass('tryon-nav-tab-active')) {
            if (typeof Chart === 'undefined') {
                loadChartJS().then(function() {
                    loadAnalyticsData();
                }).catch(function() {
                    showAnalyticsError('Failed to load Chart.js');
                });
            } else {
                loadAnalyticsData();
            }
        }
    });
    
    // Refresh analytics button
    $('#refresh-analytics').on('click', function() {
        const refreshBtn = $(this);
        const originalHtml = refreshBtn.html();
        
        // Show loading state
        refreshBtn.prop('disabled', true);
        refreshBtn.html('<span class="dashicons dashicons-update"></span> <?php _e('Refreshing...', 'woo-fitroom-preview'); ?>');
        
        // Clear cache and reload
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_refresh_analytics',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            if (response && response.success) {
                if (response.data) {
                    updateAnalyticsDisplay(response.data);
                    verifyAnalyticsData();
                } else {
                    showAnalyticsError('No data received from server');
                }
            } else {
                const errorMsg = (response && response.data && response.data.message) ? response.data.message : 'Unknown error';
                showAnalyticsError('Failed to refresh analytics data: ' + errorMsg);
            }
        }).fail(function(xhr, status, error) {
            showAnalyticsError('Network error refreshing analytics data: ' + error);
        }).always(function() {
            // Restore button state
            refreshBtn.prop('disabled', false);
            refreshBtn.html(originalHtml);
        });
    });
    
    // Debug functions
    $('#test-tracking').on('click', function() {
        showDebugOutput('Testing tracking hooks...');
        
        // Simulate a preview generation event
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_test_tracking',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                showDebugOutput('Tracking test successful: ' + JSON.stringify(response.data));
                loadAnalyticsData(); // Refresh data
            } else {
                showDebugOutput('Tracking test failed: ' + (response.data.message || 'Unknown error'));
            }
        }).fail(function() {
            showDebugOutput('Tracking test AJAX failed');
        });
    });
    
    $('#clear-cache').on('click', function() {
        showDebugOutput('Clearing analytics cache...');
        
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_refresh_analytics',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            showDebugOutput('Cache cleared successfully');
            loadAnalyticsData(); // Refresh data
        }).fail(function() {
            showDebugOutput('Cache clear failed');
        });
    });
    
    $('#check-db').on('click', function() {
        showDebugOutput('Checking database tables...');
        
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_check_database',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                showDebugOutput('Database check successful: ' + JSON.stringify(response.data, null, 2));
            } else {
                showDebugOutput('Database check failed: ' + (response.data.message || 'Unknown error'));
            }
        });
    });
    
    $('#check-consents').on('click', function() {
        showDebugOutput('Checking consent records...');
        
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_check_consents',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                showDebugOutput('Consents check successful: ' + JSON.stringify(response.data, null, 2));
            } else {
                showDebugOutput('Consents check failed: ' + (response.data.message || 'Unknown error'));
            }
        });
    });
    
    $('#check-active-users').on('click', function() {
        showDebugOutput('Checking active users...');
        
        jQuery.post(ajaxurl, {
            action: 'WOO_FITROOM_check_active_users',
            nonce: '<?php echo wp_create_nonce('WOO_FITROOM_analytics_nonce'); ?>'
        }, function(response) {
            if (response.success) {
                showDebugOutput('Active users check successful: ' + JSON.stringify(response.data, null, 2));
            } else {
                showDebugOutput('Active users check failed: ' + (response.data.message || 'Unknown error'));
            }
        });
    });
    
    $('#clear-logs').on('click', function() {
        const debugContent = document.getElementById('debug-content');
        if (debugContent) {
            debugContent.innerHTML = '';
            showDebugOutput('Logs cleared');
        }
    });
    
    </script>
</div>

<p class="tryon-mt-4" style="font-size:smaller; color: #6c757d;">
    Try-On Tool is Free Software, licensed under the GNU GPL v2. NO WARRANTY. 
    <a href="<?php echo plugin_dir_url( dirname( dirname( __FILE__ ) ) ); ?>COPYING.txt" target="_blank" style="color: #667eea;">View License</a>
</p>
   
