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
(function($) {
    'use strict';

    console.log('Script loaded and jQuery is available:', !!window.jQuery);
    console.info("Try-On Tool — GPL-2.0-only — NO WARRANTY. See COPYING file for license.");

    /* --------------------------------------------------------------
     *  I18N helper – pull wp.i18n.__ so we can wrap UI strings.
     *  Falls back to identity to stay compatible on very old sites.
     * ------------------------------------------------------------ */
    const { __ } = ( window.wp && wp.i18n ) ? wp.i18n : { __: ( s ) => s };

    /* --------------------------------------------------------------
     *  Enhanced Theme Color Detection Function
     * ------------------------------------------------------------ */
    function detectAndApplyThemeColors() {
        console.log('Try-On Tool: Starting theme color detection...');
        
        // Get all try-on buttons (both full outfit and top/bottom)
        const buttons = document.querySelectorAll('.woo-fitroom-preview-button, .woo-fitroom-preview-button-top-bottom, .woo-fitroom-preview-button-bottom');
        if (buttons.length === 0) return;

        // Check if custom color is enabled
        const useCustomColor = WooFitroomPreview && WooFitroomPreview.use_custom_color === '0';
        console.log('Try-On Tool: use_custom_color value:', WooFitroomPreview ? WooFitroomPreview.use_custom_color : 'undefined');
        console.log('Try-On Tool: useCustomColor result:', useCustomColor);
        console.log('Try-On Tool: custom_color value:', WooFitroomPreview ? WooFitroomPreview.custom_color : 'undefined');
        
        if (useCustomColor && WooFitroomPreview.custom_color) {
            // Apply custom color
            const customColor = WooFitroomPreview.custom_color;
            const customColors = {
                primary: customColor,
                primaryHover: darkenColor(customColor, 20),
                text: '#ffffff',
                textHover: '#ffffff',
                border: customColor,
                borderHover: darkenColor(customColor, 20),
                fontSize: '14px',
                fontWeight: '600'
            };
            
            buttons.forEach(button => {
                applyCustomColorsToButton(button, customColors);
                console.log('Try-On Tool: Button after custom color application:', button);
                console.log('Try-On Tool: Button computed styles:', window.getComputedStyle(button));
            });
            
            // Apply custom colors to all modal containers (full outfit, top-bottom, and bottom)
            const modalRoot = document.getElementById('woo-fitroom-preview-modal');
            const modalTopBottom = document.getElementById('woo-fitroom-preview-modal-top-bottom');
            const modalBottom = document.getElementById('woo-fitroom-preview-modal-bottom');
            
            if (modalRoot) {
                applyCustomColorsToModal(modalRoot, customColors);
            }
            if (modalTopBottom) {
                applyCustomColorsToModal(modalTopBottom, customColors);
            }
            if (modalBottom) {
                applyCustomColorsToModal(modalBottom, customColors);
            }
            
            console.log('Try-On Tool: Custom colors applied:', customColors);
        } else {
            // For OceanWP, wait a bit for the Add to Cart button to be available
            const isOceanWP = document.body.classList.contains('oceanwp') || 
                             document.documentElement.classList.contains('oceanwp') ||
                             document.body.classList.contains('oceanwp-theme') ||
                             document.querySelector('link[href*="oceanwp"]') ||
                             document.querySelector('script[src*="oceanwp"]');
            
            if (isOceanWP && !document.querySelector('.single_add_to_cart_button')) {
                console.log('Try-On Tool: OceanWP detected but Add to Cart button not ready, retrying in 500ms...');
                setTimeout(() => {
                    detectAndApplyThemeColors();
                }, 500);
                return;
            }
            
            // Detect theme and get primary color
            const themeColors = detectThemeColors();
            
            // Apply colors to all buttons
            buttons.forEach(button => {
                applyThemeColorsToButton(button, themeColors);
            });

            // Apply colors to all modals (full outfit, top-bottom, and bottom)
            const modalRoot = document.getElementById('woo-fitroom-preview-modal');
            const modalTopBottom = document.getElementById('woo-fitroom-preview-modal-top-bottom');
            const modalBottom = document.getElementById('woo-fitroom-preview-modal-bottom');
            
            if (modalRoot) {
                applyThemeColorsToModal(modalRoot, themeColors);
            }
            if (modalTopBottom) {
                applyThemeColorsToModal(modalTopBottom, themeColors);
            }
            if (modalBottom) {
                applyThemeColorsToModal(modalBottom, themeColors);
            }

            console.log('Try-On Tool: Theme colors applied:', themeColors);
        }
    }

    function detectThemeColors() {
        const colors = {
            primary: null,
            primaryHover: null,
            text: '#ffffff',
            textHover: '#ffffff',
            border: null,
            borderHover: null,
            borderRadius: '50px', // Try-On Tool default border radius
            fontSize: '14px',
            fontWeight: '600'
        };

        // Method 0: OceanWP specific detection - get comprehensive styling from Add to Cart button
        const isOceanWP = document.body.classList.contains('oceanwp') || 
                         document.documentElement.classList.contains('oceanwp') ||
                         document.body.classList.contains('oceanwp-theme') ||
                         document.querySelector('link[href*="oceanwp"]') ||
                         document.querySelector('script[src*="oceanwp"]');
        
        if (isOceanWP) {
            console.log('Try-On Tool: OceanWP theme detected, getting comprehensive styling from Add to Cart button...');
            
            // Find the Add to Cart button and get its comprehensive styling
            const addToCartButton = document.querySelector('.single_add_to_cart_button');
            if (addToCartButton) {
                const computedStyle = getComputedStyle(addToCartButton);
                
                // Get background color
                const bgColor = computedStyle.backgroundColor;
                if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                    colors.primary = bgColor;
                    colors.primaryHover = darkenColor(bgColor, 20);
                    colors.border = bgColor;
                    colors.borderHover = darkenColor(bgColor, 20);
                    
                    console.log('Try-On Tool: OceanWP primary color detected:', bgColor);
                }
                
                // Get text color
                const textColor = computedStyle.color;
                if (textColor && textColor !== 'rgba(0, 0, 0, 0)') {
                    colors.text = textColor;
                    colors.textHover = textColor;
                    console.log('Try-On Tool: OceanWP text color detected:', textColor);
                }
                
                // Get border radius - always get value even if 0
                const borderRadius = computedStyle.borderRadius;
                colors.borderRadius = borderRadius; // Always set, even if 0px
                console.log('Try-On Tool: OceanWP border radius detected:', borderRadius);
                
                // Get font size
                const fontSize = computedStyle.fontSize;
                if (fontSize && fontSize !== '0px') {
                    colors.fontSize = fontSize;
                    console.log('Try-On Tool: OceanWP font size detected:', fontSize);
                }
                
                // Get font weight
                const fontWeight = computedStyle.fontWeight;
                if (fontWeight && fontWeight !== 'normal') {
                    colors.fontWeight = fontWeight;
                    console.log('Try-On Tool: OceanWP font weight detected:', fontWeight);
                }
                
                // Get padding (comprehensive detection) - always get values even if 0
                const paddingTop = computedStyle.paddingTop;
                const paddingRight = computedStyle.paddingRight;
                const paddingBottom = computedStyle.paddingBottom;
                const paddingLeft = computedStyle.paddingLeft;
                
                // Always set padding values from OceanWP Add to Cart button (even if 0)
                colors.padding = `${paddingTop} ${paddingRight} ${paddingBottom} ${paddingLeft}`;
                colors.paddingTop = paddingTop;
                colors.paddingRight = paddingRight;
                colors.paddingBottom = paddingBottom;
                colors.paddingLeft = paddingLeft;
                console.log('Try-On Tool: OceanWP padding detected:', colors.padding);
                
                // Get border width
                const borderWidth = computedStyle.borderWidth;
                if (borderWidth && borderWidth !== '0px') {
                    colors.borderWidth = borderWidth;
                    console.log('Try-On Tool: OceanWP border width detected:', borderWidth);
                }
                
                // Get border style
                const borderStyle = computedStyle.borderStyle;
                if (borderStyle && borderStyle !== 'none') {
                    colors.borderStyle = borderStyle;
                    console.log('Try-On Tool: OceanWP border style detected:', borderStyle);
                }
                
                // Get text transform
                const textTransform = computedStyle.textTransform;
                if (textTransform && textTransform !== 'none') {
                    colors.textTransform = textTransform;
                    console.log('Try-On Tool: OceanWP text transform detected:', textTransform);
                }
                
                // Get letter spacing
                const letterSpacing = computedStyle.letterSpacing;
                if (letterSpacing && letterSpacing !== 'normal') {
                    colors.letterSpacing = letterSpacing;
                    console.log('Try-On Tool: OceanWP letter spacing detected:', letterSpacing);
                }
                
                // Get line height
                const lineHeight = computedStyle.lineHeight;
                if (lineHeight && lineHeight !== 'normal') {
                    colors.lineHeight = lineHeight;
                    console.log('Try-On Tool: OceanWP line height detected:', lineHeight);
                }
                
                // Get box shadow
                const boxShadow = computedStyle.boxShadow;
                if (boxShadow && boxShadow !== 'none') {
                    colors.boxShadow = boxShadow;
                    console.log('Try-On Tool: OceanWP box shadow detected:', boxShadow);
                }
                
                console.log('Try-On Tool: OceanWP comprehensive styling applied:', colors);
            } else {
                console.log('Try-On Tool: Add to Cart button not found for OceanWP, falling back to CSS variables');
            }
        }

        // Method 1: WordPress theme.json colors
        const wpPrimary = getComputedStyle(document.body).getPropertyValue('--wp--preset--color--primary').trim();
        const wpSecondary = getComputedStyle(document.body).getPropertyValue('--wp--preset--color--secondary').trim();
        
        if (wpPrimary && /rgb|#/.test(wpPrimary)) {
            colors.primary = wpPrimary;
            colors.primaryHover = darkenColor(wpPrimary, 20);
            colors.border = wpPrimary;
            colors.borderHover = darkenColor(wpPrimary, 20);
        } else if (wpSecondary && /rgb|#/.test(wpSecondary)) {
            colors.primary = wpSecondary;
            colors.primaryHover = darkenColor(wpSecondary, 20);
            colors.border = wpSecondary;
            colors.borderHover = darkenColor(wpSecondary, 20);
        }

        // Method 2: GeneratePress specific detection - force customizer color over WooCommerce
        const isGeneratePress = document.body.classList.contains('generatepress') || 
                               document.documentElement.classList.contains('generatepress') ||
                               document.body.classList.contains('gp-theme') ||
                               document.querySelector('link[href*="generatepress"]') ||
                               document.querySelector('script[src*="generatepress"]');
        
        if (isGeneratePress) {
            console.log('Try-On Tool: GeneratePress theme detected, forcing customizer primary color...');
            
            // Force GeneratePress customizer primary color
            const gpPrimary = getComputedStyle(document.body).getPropertyValue('--gp-theme-primary').trim();
            if (gpPrimary && /rgb|#/.test(gpPrimary)) {
                colors.primary = gpPrimary;
                colors.primaryHover = darkenColor(gpPrimary, 20);
                colors.border = gpPrimary;
                colors.borderHover = darkenColor(gpPrimary, 20);
                console.log('Try-On Tool: GeneratePress customizer primary color forced:', gpPrimary);
            }
        }

        // Method 3: Theme-specific CSS variables (fallback)
        if (!colors.primary) {
            const themeVars = [
                '--ast-global-color-0', // Astra
                '--gp-theme-primary', // GeneratePress
                '--ocean-primary-color', // OceanWP (primary variable)
                '--ocean-primary', // OceanWP (alternative)
                '--ocean-color-primary', // OceanWP (alternative)
                '--storefront-primary' // Storefront
            ];

            for (const varName of themeVars) {
                const color = getComputedStyle(document.body).getPropertyValue(varName).trim();
                if (color && /rgb|#/.test(color)) {
                    colors.primary = color;
                    colors.primaryHover = darkenColor(color, 20);
                    colors.border = color;
                    colors.borderHover = darkenColor(color, 20);
                    console.log('Try-On Tool: Detected color from CSS variable', varName, ':', color);
                    break;
                }
            }
        }

        // Method 4: Detect from existing buttons (prioritize WooCommerce buttons)
        if (!colors.primary) {
            // First try WooCommerce specific buttons
            const wooButtons = document.querySelectorAll('.single_add_to_cart_button, .add_to_cart_button, .woocommerce-button');
            for (const btn of wooButtons) {
                const bgColor = getComputedStyle(btn).backgroundColor;
                if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                    colors.primary = bgColor;
                    colors.primaryHover = darkenColor(bgColor, 20);
                    colors.border = bgColor;
                    colors.borderHover = darkenColor(bgColor, 20);
                    
                    console.log('Try-On Tool: Detected color from WooCommerce button:', bgColor);
                    
                    // Get text color
                    const textColor = getComputedStyle(btn).color;
                    if (textColor && textColor !== 'rgba(0, 0, 0, 0)') {
                        colors.text = textColor;
                        colors.textHover = textColor;
                    }
                    
                    // Get border radius
                    const borderRadius = getComputedStyle(btn).borderRadius;
                    if (borderRadius && borderRadius !== '0px') {
                        colors.borderRadius = borderRadius;
                    }
                    
                    // Get font size
                    const fontSize = getComputedStyle(btn).fontSize;
                    if (fontSize && fontSize !== '0px') {
                        colors.fontSize = fontSize;
                    }
                    
                    // Get font weight
                    const fontWeight = getComputedStyle(btn).fontWeight;
                    if (fontWeight && fontWeight !== 'normal') {
                        colors.fontWeight = fontWeight;
                    }
                    
                    break;
                }
            }
            
            // If no WooCommerce button found, try generic buttons
            if (!colors.primary) {
                const genericButtons = document.querySelectorAll('.button, .btn');
                for (const btn of genericButtons) {
                    const bgColor = getComputedStyle(btn).backgroundColor;
                    if (bgColor && bgColor !== 'rgba(0, 0, 0, 0)' && bgColor !== 'transparent') {
                        colors.primary = bgColor;
                        colors.primaryHover = darkenColor(bgColor, 20);
                        colors.border = bgColor;
                        colors.borderHover = darkenColor(bgColor, 20);
                        
                        console.log('Try-On Tool: Detected color from generic button:', bgColor);
                        
                        // Get text color
                        const textColor = getComputedStyle(btn).color;
                        if (textColor && textColor !== 'rgba(0, 0, 0, 0)') {
                            colors.text = textColor;
                            colors.textHover = textColor;
                        }
                        
                        // Get border radius
                        const borderRadius = getComputedStyle(btn).borderRadius;
                        if (borderRadius && borderRadius !== '0px') {
                            colors.borderRadius = borderRadius;
                        }
                        
                        // Get font size
                        const fontSize = getComputedStyle(btn).fontSize;
                        if (fontSize && fontSize !== '0px') {
                            colors.fontSize = fontSize;
                        }
                        
                        // Get font weight
                        const fontWeight = getComputedStyle(btn).fontWeight;
                        if (fontWeight && fontWeight !== 'normal') {
                            colors.fontWeight = fontWeight;
                        }
                        
                        break;
                    }
                }
            }
        }

        // Method 5: Detect from links
        if (!colors.primary) {
            const links = document.querySelectorAll('a');
            for (const link of links) {
                const linkColor = getComputedStyle(link).color;
                if (linkColor && /rgb|#/.test(linkColor) && linkColor !== 'rgb(0, 0, 0)') {
                    colors.primary = linkColor;
                    colors.primaryHover = darkenColor(linkColor, 20);
                    colors.border = linkColor;
                    colors.borderHover = darkenColor(linkColor, 20);
                    break;
                }
            }
        }

        // Method 6: Create test element
        if (!colors.primary) {
            const testEl = document.createElement('a');
            testEl.href = '#';
            testEl.style.position = 'absolute';
            testEl.style.visibility = 'hidden';
            testEl.style.top = '-9999px';
            document.body.appendChild(testEl);
            
            const linkColor = getComputedStyle(testEl).color;
            if (linkColor && /rgb|#/.test(linkColor)) {
                colors.primary = linkColor;
                colors.primaryHover = darkenColor(linkColor, 20);
                colors.border = linkColor;
                colors.borderHover = darkenColor(linkColor, 20);
            }
            
            document.body.removeChild(testEl);
        }

        // Fallback
        if (!colors.primary) {
            colors.primary = '#007cba';
            colors.primaryHover = '#005a87';
            colors.border = '#007cba';
            colors.borderHover = '#005a87';
        }

        return colors;
    }

    function applyThemeColorsToButton(button, colors) {
        // Set CSS custom properties
        button.style.setProperty('--tryon-theme-bg', colors.primary);
        button.style.setProperty('--tryon-theme-bg-hover', colors.primaryHover);
        button.style.setProperty('--tryon-theme-text', colors.text);
        button.style.setProperty('--tryon-theme-text-hover', colors.textHover);
        button.style.setProperty('--tryon-theme-border', colors.border);
        button.style.setProperty('--tryon-theme-border-hover', colors.borderHover);
        button.style.setProperty('--tryon-theme-radius', colors.borderRadius);
        button.style.setProperty('--tryon-theme-font-size', colors.fontSize);
        button.style.setProperty('--tryon-theme-font-weight', colors.fontWeight);
        button.style.setProperty('--tryon-theme-focus', colors.primary);
        
        // Remove custom color variables when using theme colors
        button.style.removeProperty('--tryon-custom-color');
        button.style.removeProperty('--tryon-custom-color-hover');
        
        // Apply comprehensive OceanWP styling if available
        if (colors.padding !== undefined) {
            button.style.setProperty('--tryon-theme-padding', colors.padding);
            button.style.setProperty('--tryon-theme-padding-top', colors.paddingTop);
            button.style.setProperty('--tryon-theme-padding-right', colors.paddingRight);
            button.style.setProperty('--tryon-theme-padding-bottom', colors.paddingBottom);
            button.style.setProperty('--tryon-theme-padding-left', colors.paddingLeft);
        }
        
        if (colors.borderWidth) {
            button.style.setProperty('--tryon-theme-border-width', colors.borderWidth);
        }
        
        if (colors.borderStyle) {
            button.style.setProperty('--tryon-theme-border-style', colors.borderStyle);
        }
        
        if (colors.textTransform) {
            button.style.setProperty('--tryon-theme-text-transform', colors.textTransform);
        }
        
        if (colors.letterSpacing) {
            button.style.setProperty('--tryon-theme-letter-spacing', colors.letterSpacing);
        }
        
        if (colors.lineHeight) {
            button.style.setProperty('--tryon-theme-line-height', colors.lineHeight);
        }
        
        if (colors.boxShadow) {
            button.style.setProperty('--tryon-theme-box-shadow', colors.boxShadow);
        }
        
        // Apply padding and border radius based on admin settings
        applyButtonPadding(button);
        applyButtonBorderRadius(button);
    }

    function applyCustomColorsToButton(button, colors) {
        console.log('Try-On Tool: Applying custom colors to button:', button, colors);
        
        // First, detect theme colors to get theme border radius and padding
        const themeColors = detectThemeColors();
        if (themeColors.borderRadius) {
            button.style.setProperty('--tryon-theme-radius', themeColors.borderRadius);
        }
        if (themeColors.padding) {
            button.style.setProperty('--tryon-theme-padding', themeColors.padding);
        }
        
        // Only apply color-related custom properties
        button.style.setProperty('--tryon-custom-color', colors.primary);
        button.style.setProperty('--tryon-custom-color-hover', colors.primaryHover);
        button.style.setProperty('--tryon-theme-bg', colors.primary);
        button.style.setProperty('--tryon-theme-bg-hover', colors.primaryHover);
        button.style.setProperty('--tryon-theme-text', colors.text);
        button.style.setProperty('--tryon-theme-text-hover', colors.textHover);
        button.style.setProperty('--tryon-theme-border', colors.border);
        button.style.setProperty('--tryon-theme-border-hover', colors.borderHover);
        button.style.setProperty('--tryon-theme-focus', colors.primary);
        
        // Only apply font properties if they're not inherited from theme
        if (WooFitroomPreview && WooFitroomPreview.use_custom_font !== '1') {
        button.style.setProperty('--tryon-theme-font-size', colors.fontSize);
        button.style.setProperty('--tryon-theme-font-weight', colors.fontWeight);
        }
        
        console.log('Try-On Tool: CSS custom properties set:', {
            '--tryon-custom-color': button.style.getPropertyValue('--tryon-custom-color'),
            '--tryon-theme-bg': button.style.getPropertyValue('--tryon-theme-bg'),
            '--tryon-theme-text': button.style.getPropertyValue('--tryon-theme-text')
        });
        
        // Apply padding and border radius based on admin settings (these functions check the settings)
        applyButtonPadding(button);
        applyButtonBorderRadius(button);
    }

    function applyCustomColorsToModal(modal, colors) {
        modal.style.setProperty('--tryon-theme-bg', colors.primary);
        modal.style.setProperty('--tryon-theme-bg-hover', colors.primaryHover);
        modal.style.setProperty('--tryon-custom-color', colors.primary);
        modal.style.setProperty('--tryon-custom-color-hover', colors.primaryHover);
        modal.style.setProperty('--tryon-theme-border', colors.border);
        modal.style.setProperty('--tryon-theme-border-hover', colors.borderHover);
        modal.style.setProperty('--tryon-primary', colors.primary);
    }

    function applyThemeColorsToModal(modal, colors) {
        modal.style.setProperty('--tryon-primary', colors.primary);
        modal.style.setProperty('--tryon-primary-20', colors.primary + '20');
        modal.style.setProperty('--tryon-primary-25', colors.primary + '25');
        modal.style.setProperty('--tryon-theme-bg', colors.primary);
        modal.style.setProperty('--tryon-theme-bg-hover', colors.primaryHover);
        // Don't set custom color variables when using theme colors
        modal.style.removeProperty('--tryon-custom-color');
        modal.style.removeProperty('--tryon-custom-color-hover');
    }

    function darkenColor(color, percent) {
        // Convert hex to RGB
        let r, g, b;
        if (color.startsWith('#')) {
            const hex = color.slice(1);
            r = parseInt(hex.substr(0, 2), 16);
            g = parseInt(hex.substr(2, 2), 16);
            b = parseInt(hex.substr(4, 2), 16);
        } else if (color.startsWith('rgb')) {
            const matches = color.match(/\d+/g);
            r = parseInt(matches[0]);
            g = parseInt(matches[1]);
            b = parseInt(matches[2]);
        } else {
            return color;
        }

        // Darken by percent
        r = Math.max(0, Math.floor(r * (1 - percent / 100)));
        g = Math.max(0, Math.floor(g * (1 - percent / 100)));
        b = Math.max(0, Math.floor(b * (1 - percent / 100)));

        return `rgb(${r}, ${g}, ${b})`;
    }

    // Custom Popup System
    function showCustomPopup(options) {
        const {
            title = 'Notice',
            message = '',
            type = 'info', // info, success, error, warning
            showCancel = false,
            confirmText = 'OK',
            cancelText = 'Cancel',
            onConfirm = null,
            onCancel = null
        } = options;

        // Remove any existing popup
        $('.tryon-custom-popup').remove();

        const popup = $(`
            <div class="tryon-custom-popup">
                <div class="tryon-popup-overlay"></div>
                <div class="tryon-popup-content">
                    <div class="tryon-popup-header">
                        <h3 class="tryon-popup-title">${title}</h3>
                        <button class="tryon-popup-close">&times;</button>
                    </div>
                    <div class="tryon-popup-body">
                        <div class="tryon-popup-icon tryon-popup-icon-${type}"></div>
                        <p class="tryon-popup-message">${message}</p>
                    </div>
                    <div class="tryon-popup-footer">
                        ${showCancel ? `<button class="tryon-popup-btn tryon-popup-cancel">${cancelText}</button>` : ''}
                        <button class="tryon-popup-btn tryon-popup-confirm tryon-popup-btn-${type}">${confirmText}</button>
                    </div>
                </div>
            </div>
        `);

        $('body').append(popup);

        // Handle close button
        popup.find('.tryon-popup-close, .tryon-popup-cancel').on('click', function() {
            popup.remove();
            if (onCancel) onCancel();
        });

        // Handle confirm button
        popup.find('.tryon-popup-confirm').on('click', function() {
            popup.remove();
            if (onConfirm) onConfirm();
        });

        // Handle overlay click
        popup.find('.tryon-popup-overlay').on('click', function() {
            popup.remove();
            if (onCancel) onCancel();
        });

        // Auto-close for info/success messages after 3 seconds
        if (type === 'info' || type === 'success') {
            setTimeout(() => {
                if (popup.length) {
                    popup.remove();
                }
            }, 3000);
        }
    }

    // Convenience functions for different popup types
    function showInfoPopup(message, title = 'Information') {
        showCustomPopup({ title, message, type: 'info' });
    }

    function showSuccessPopup(message, title = 'Success') {
        showCustomPopup({ title, message, type: 'success' });
    }

    function showErrorPopup(message, title = 'Error') {
        showCustomPopup({ title, message, type: 'error' });
    }

    function showConfirmPopup(message, onConfirm, title = 'Confirm') {
        showCustomPopup({
            title,
            message,
            type: 'warning',
            showCancel: true,
            confirmText: 'Yes',
            cancelText: 'No',
            onConfirm,
            onCancel: () => {}
        });
    }

    function applyButtonPadding(button) {
        // Check if we should use custom padding or theme-detected padding
        const useCustomPadding = WooFitroomPreview && WooFitroomPreview.use_custom_padding === '0';
        
        if (useCustomPadding && WooFitroomPreview.custom_padding) {
            // Apply custom padding values from admin settings
            const padding = WooFitroomPreview.custom_padding;
            button.style.setProperty('--tryon-custom-padding', `${padding.top}px ${padding.right}px ${padding.bottom}px ${padding.left}px`);
        } else {
            // Check if theme-detected padding is available (OceanWP, etc.)
            const themePadding = button.style.getPropertyValue('--tryon-theme-padding');
            if (themePadding) {
                // Use theme-detected padding (OceanWP Add to Cart button padding)
                button.style.setProperty('--tryon-custom-padding', themePadding);
                console.log('Try-On Tool: Using theme-detected padding:', themePadding);
            } else {
                // Use Try-On Tool default padding (12px top/bottom, 20px left/right)
                button.style.setProperty('--tryon-custom-padding', '12px 20px');
                console.log('Try-On Tool: Using default padding: 12px 20px');
            }
        }
    }

    function applyButtonBorderRadius(button) {
        // Check if we should use Try-On Tool defined border radius or theme border radius
        const useCustomBorderRadius = WooFitroomPreview && WooFitroomPreview.use_custom_border_radius === '0';
        
        if (useCustomBorderRadius) {
            if (WooFitroomPreview.custom_border_radius) {
                // Apply custom border radius values from admin settings
                const radius = WooFitroomPreview.custom_border_radius;
                button.style.setProperty('--tryon-custom-border-radius', `${radius.top_left}px ${radius.top_right}px ${radius.bottom_right}px ${radius.bottom_left}px`);
                console.log('Try-On Tool: Using custom border radius from admin settings');
            } else {
                // Apply Try-On Tool default border radius (50px)
                button.style.setProperty('--tryon-custom-border-radius', '50px');
                console.log('Try-On Tool: Using Try-On Tool defined border radius (50px)');
            }
        } else {
            // Check if theme-detected border radius is available (OceanWP, etc.)
            const themeBorderRadius = button.style.getPropertyValue('--tryon-theme-radius');
            if (themeBorderRadius) {
                // Use theme-detected border radius (OceanWP Add to Cart button border radius)
                button.style.setProperty('--tryon-custom-border-radius', themeBorderRadius);
                console.log('Try-On Tool: Using theme-detected border radius:', themeBorderRadius);
            } else {
                // Clear custom border radius to use CSS defaults
                button.style.setProperty('--tryon-custom-border-radius', '');
                console.log('Try-On Tool: Using CSS default border radius');
            }
        }
    }

    $(document).ready(function() {
        // Declare variable in outer scope to avoid ReferenceError in later debug logs
        let imgElement = null;
        
        // Apply theme colors on page load
        setTimeout(() => {
            detectAndApplyThemeColors();
        }, 100);
        
        // Additional OceanWP detection after page is fully loaded
        window.addEventListener('load', function() {
            const isOceanWP = document.body.classList.contains('oceanwp') || 
                             document.documentElement.classList.contains('oceanwp') ||
                             document.body.classList.contains('oceanwp-theme') ||
                             document.querySelector('link[href*="oceanwp"]') ||
                             document.querySelector('script[src*="oceanwp"]');
            
            if (isOceanWP) {
                console.log('Try-On Tool: OceanWP detected on window load, re-checking colors...');
                setTimeout(() => {
                    detectAndApplyThemeColors();
                }, 200);
            }
        });

        console.log('WooTryOnTool Preview: JavaScript initialized');
        console.log('WooTryOnTool Preview Buttons found:', $('.woo-fitroom-preview-button').length);
        console.log('WooTryOnTool Preview Top/Bottom Buttons found:', $('.woo-fitroom-preview-button-top-bottom').length);
        
        // Handle full outfit preview button click using event delegation
        $(document).on('click', '.woo-fitroom-preview-button', function(e) {
            console.log('WooTryOnTool Preview: Full outfit button clicked');
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const productImage = $(this).data('product-image');
            
            console.log('Product Image URL:', productImage);
            
            $('#product_id').val(productId);
            $('#product_image_url').val(productImage);
            
            $('#woo-fitroom-preview-modal').addClass('is-open').show();

            // Enhanced theme color detection and button styling
            try {
                detectAndApplyThemeColors();
            } catch (error) {
                console.warn('Theme color detection failed:', error);
            }
            
            // Hide consent blocks up-front if the server says they are not needed for this user
            const modal = document.getElementById('woo-fitroom-preview-modal');
            if (modal) {
                const needConsent = modal.getAttribute('data-require-consent') === '1';
                const showExtra = modal.getAttribute('data-show-extra-consents') === '1';
                if (!needConsent) { $('#user_consent').closest('.form-field').hide(); }
                if (!showExtra) { $('#terms_consent, #refund_consent').closest('.form-field').hide(); }
            }
            
            /* Auto-fetch previously uploaded images and render inline strip */
            if (WooFitroomPreview.user_id && WooFitroomPreview.user_id !== 0) {
                $.ajax({
                    url: WooFitroomPreview.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_user_uploaded_images',
                        user_id: WooFitroomPreview.user_id,
                        nonce: WooFitroomPreview.nonce
                    },
                    success: function(res){
                        if(res.success && res.data.images.length){
                            const list = $('#my_uploads_list');
                            const strip = $('#my_uploads_strip');
                            strip.show();
                            list.empty();
                            const maxThumbs = 3;
                            const images = res.data.images;
                            const visible = images.slice(0, maxThumbs);
                            visible.forEach((u, idx) => {
                                const isLastAndMore = (idx === maxThumbs - 1) && (images.length > maxThumbs);
                                if (isLastAndMore) {
                                    list.append(`<div class="thumb more" id="my_uploads_more"><span>${__('View More','woo-fitroom-preview')}</span></div>`);
                                } else {
                                    const prox = getProxyImageUrl(u);
                                    list.append(`<div class="thumb" data-url="${u}"><img src="${prox}" data-url="${u}" alt="uploaded"/><button class="delete-btn tryon-delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`);
                                }
                            });
                            // Pre-select first image
                            $('#saved_user_image_url').val(images[0]);
                            list.find('.thumb').first().addClass('selected');
                            // Show delete all button if there are images
                            $('#delete_all_images_btn').show();
                        } else {
                            $('#my_uploads_strip').hide();
                            $('#delete_all_images_btn').hide();
                        }
                    }
                });
            }
            
            // Assuming the image element ID hasn't changed (preview-product-image was not FashnAI specific)
            console.log('Setting image src to:', productImage);
            imgElement = document.getElementById('preview-product-image'); 
            console.log('Image element found:', imgElement !== null);
            
            if (imgElement && productImage) {
                imgElement.src = getProxyImageUrl(productImage);
                imgElement.style.display = 'block';
                console.log('Image src set:', imgElement.src);
                
                imgElement.onload = function() {
                    console.log('Product image loaded successfully');
                };
                
                imgElement.onerror = function() {
                    console.error('Failed to load product image');
                    imgElement.style.display = 'none';
                    $('#woo-fitroom-preview-modal .product-image-preview').append('<p class="error">' + __( 'Failed to load product image', 'woo-fitroom-preview' ) + '</p>');
                };
            } else {
                console.error('Image element #preview-product-image or product image URL not found');
                if (imgElement) imgElement.style.display = 'none';
                 $('#woo-fitroom-preview-modal .product-image-preview .error').remove();
                 $('#woo-fitroom-preview-modal .product-image-preview').append('<p class="error">' + __( 'No product image available', 'woo-fitroom-preview' ) + '</p>');
            }
        });

        // Handle top/bottom preview button click
        $(document).on('click', '.woo-fitroom-preview-button-top-bottom', function(e) {
            console.log('WooTryOnTool Preview: Top/Bottom button clicked');
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const productImage = $(this).data('product-image');
            
            console.log('Top/Bottom Button - Product ID:', productId);
            console.log('Top/Bottom Button - Product Image URL:', productImage);
            console.log('Top/Bottom Button - Data attributes:', $(this).data());
            
            $('#product_id_top_bottom').val(productId);
            $('#product_image_url_top_bottom').val(productImage);
            
            console.log('Top/Bottom Modal element found:', $('#woo-fitroom-preview-modal-top-bottom').length > 0);
            console.log('Top/Bottom Modal element:', $('#woo-fitroom-preview-modal-top-bottom')[0]);
            $('#woo-fitroom-preview-modal-top-bottom').addClass('is-open').show();
            
            // Display product image in top/bottom modal
            console.log('Setting top/bottom modal image src to:', productImage);
            console.log('Product image type:', typeof productImage);
            console.log('Product image length:', productImage ? productImage.length : 'undefined');
            const imgElementTopBottom = document.getElementById('preview-product-image-top-bottom'); 
            console.log('Top/Bottom Image element found:', imgElementTopBottom !== null);
            
            if (imgElementTopBottom && productImage && productImage.trim() !== '') {
                imgElementTopBottom.src = getProxyImageUrl(productImage);
                imgElementTopBottom.style.display = 'block';
                console.log('Top/Bottom Image src set:', imgElementTopBottom.src);
                
                imgElementTopBottom.onload = function() {
                    console.log('Top/Bottom Product image loaded successfully');
                };
                
                imgElementTopBottom.onerror = function() {
                    console.error('Failed to load top/bottom product image');
                    imgElementTopBottom.style.display = 'none';
                    $('#woo-fitroom-preview-modal-top-bottom .product-image-preview').append('<p class="error">' + __( 'Failed to load product image', 'woo-fitroom-preview' ) + '</p>');
                };
            } else {
                console.error('Top/Bottom Image element #preview-product-image-top-bottom or product image URL not found');
                console.error('imgElementTopBottom:', imgElementTopBottom);
                console.error('productImage:', productImage);
                if (imgElementTopBottom) imgElementTopBottom.style.display = 'none';
                $('#woo-fitroom-preview-modal-top-bottom .product-image-preview .error').remove();
                $('#woo-fitroom-preview-modal-top-bottom .product-image-preview').append('<p class="error">' + __( 'No product image available', 'woo-fitroom-preview' ) + '</p>');
            }
            
            // Load user's uploaded images for the top-bottom modal
            loadTopBottomModalImages();

            // Enhanced theme color detection and button styling
            try {
                detectAndApplyThemeColors();
            } catch (error) {
                console.warn('Theme color detection failed:', error);
            }
            
            // Hide consent blocks up-front if the server says they are not needed for this user
            const modal = document.getElementById('woo-fitroom-preview-modal-top-bottom');
            if (modal) {
                const needConsent = modal.getAttribute('data-require-consent') === '1';
                const showExtra = modal.getAttribute('data-show-extra-consents') === '1';
                if (!needConsent) { $('#user_consent_top_bottom').closest('.form-field').hide(); }
                if (!showExtra) { $('#terms_consent_top_bottom, #refund_consent_top_bottom').closest('.form-field').hide(); }
            }
        });

        // Handle bottom preview button click
        $(document).on('click', '.woo-fitroom-preview-button-bottom', function(e) {
            console.log('WooTryOnTool Preview: Bottom button clicked');
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const productImage = $(this).data('product-image');
            
            console.log('Bottom Button - Product ID:', productId);
            console.log('Bottom Button - Product Image URL:', productImage);
            console.log('Bottom Button - Data attributes:', $(this).data());
            
            $('#product_id_bottom').val(productId);
            $('#product_image_url_bottom').val(productImage);
            
            console.log('Bottom Modal element found:', $('#woo-fitroom-preview-modal-bottom').length > 0);
            console.log('Bottom Modal element:', $('#woo-fitroom-preview-modal-bottom')[0]);
            $('#woo-fitroom-preview-modal-bottom').addClass('is-open').show();
            
            // Display product image in bottom modal
            console.log('Setting bottom modal image src to:', productImage);
            console.log('Product image type:', typeof productImage);
            console.log('Product image length:', productImage ? productImage.length : 'undefined');
            const imgElementBottom = document.getElementById('preview-product-image-bottom'); 
            console.log('Bottom Image element found:', imgElementBottom !== null);
            
            if (imgElementBottom && productImage && productImage.trim() !== '') {
                imgElementBottom.src = getProxyImageUrl(productImage);
                imgElementBottom.style.display = 'block';
                console.log('Bottom Image src set:', imgElementBottom.src);
                
                imgElementBottom.onload = function() {
                    console.log('Bottom Product image loaded successfully');
                };
                
                imgElementBottom.onerror = function() {
                    console.error('Failed to load bottom product image');
                    imgElementBottom.style.display = 'none';
                    $('#woo-fitroom-preview-modal-bottom .product-image-preview').append('<p class="error">' + __( 'Failed to load product image', 'woo-fitroom-preview' ) + '</p>');
                };
                                } else {
                console.error('Bottom Image element #preview-product-image-bottom or product image URL not found');
                console.error('imgElementBottom:', imgElementBottom);
                console.error('productImage:', productImage);
                if (imgElementBottom) imgElementBottom.style.display = 'none';
                $('#woo-fitroom-preview-modal-bottom .product-image-preview .error').remove();
                $('#woo-fitroom-preview-modal-bottom .product-image-preview').append('<p class="error">' + __( 'No product image available', 'woo-fitroom-preview' ) + '</p>');
            }
            
            // Load user's uploaded images for the bottom modal
            loadBottomModalImages();

            // Enhanced theme color detection and button styling
            try {
                detectAndApplyThemeColors();
            } catch (error) {
                console.warn('Theme color detection failed:', error);
            }
            
            // Hide consent blocks up-front if the server says they are not needed for this user
            const modal = document.getElementById('woo-fitroom-preview-modal-bottom');
            if (modal) {
                const needConsent = modal.getAttribute('data-require-consent') === '1';
                const showExtra = modal.getAttribute('data-show-extra-consents') === '1';
                if (!needConsent) { $('#user_consent_bottom').closest('.form-field').hide(); }
                if (!showExtra) { $('#terms_consent_bottom, #refund_consent_bottom').closest('.form-field').hide(); }
            }
        });

        // Handle modal close
        $('.woo-fitroom-preview-modal .close').on('click', function() {
            $('.woo-fitroom-preview-modal').removeClass('is-open').hide();
            $('.product-image-preview .error').remove(); 
            // Clear errors for all modals
            $('#woo-fitroom-preview-modal .product-image-preview .error').remove();
            $('#woo-fitroom-preview-modal-top-bottom .product-image-preview .error').remove();
            $('#woo-fitroom-preview-modal-bottom .product-image-preview .error').remove();
            // $('#preview-product-image').removeClass('image-error').show();
        });

        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if ($(e.target).is('.woo-fitroom-preview-modal')) {
                $('.woo-fitroom-preview-modal').removeClass('is-open').hide();
            }
        });

        // Helper: open uploaded images modal (used by both inline "More" and legacy button)
        function openUploadedImagesModal(){
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_uploaded_images',
                    user_id: WooFitroomPreview.user_id,
                    nonce: WooFitroomPreview.nonce
                },
                success: function(res){
                    if (res.success && res.data.images.length){
                        let html = '<div class="uploaded-images-grid">';
                        res.data.images.forEach(u=>{ html += `<div class="img-item"><img src="${getProxyImageUrl(u)}" data-url="${u}"/><button class="delete-btn tryon-delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`; });
                        html += '</div>';
                        let modal = document.getElementById('uploaded-images-modal');
                        if(!modal){
                            $('body').append(`<div id="uploaded-images-modal" class="woo-fitroom-preview-modal" style="display:none;"><div class="modal-content"><span class="close">&times;</span><h3 style="margin-bottom:20px !important;">My Uploads</h3><div class="images-wrap"></div></div></div>`);
                            modal = document.getElementById('uploaded-images-modal');
                            $(modal).on('click','.close',()=>$(modal).removeClass('is-open').hide());
                            $(window).on('click',evt=>{ if(evt.target===modal){ $(modal).removeClass('is-open').hide(); }});
                        }
                        $(modal).find('.images-wrap').html(html);
                        $(modal).classList ? modal.classList.add('is-open') : $(modal).addClass('is-open');
                        $(modal).show();
                        $(modal).off('click', '.img-item img').on('click','.img-item img',function(){
                            const selectedImageUrl = $(this).data('url') || $(this).attr('src');
                            
                            // Check which modal is currently open and update appropriate fields
                            const isTopBottomModalOpen = $('#woo-fitroom-preview-modal-top-bottom').hasClass('is-open') || $('#woo-fitroom-preview-modal-top-bottom').is(':visible');
                            const isBottomModalOpen = $('#woo-fitroom-preview-modal-bottom').hasClass('is-open') || $('#woo-fitroom-preview-modal-bottom').is(':visible');
                            
                            if (isTopBottomModalOpen) {
                                // Update top-bottom modal fields
                                $('#saved_user_image_url_top_bottom').val(selectedImageUrl);
                                $('#user_image_top_bottom').val('').prop('required', false);
                                $('#selected-photo-name-top-bottom').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + selectedImageUrl.split('/').pop() );
                                
                                // Update the inline strip selection
                                $('#my_uploads_list_top_bottom .thumb').removeClass('selected');
                                // Find and select the corresponding thumbnail in the inline strip
                                $('#my_uploads_list_top_bottom .thumb').each(function() {
                                    if ($(this).data('url') === selectedImageUrl) {
                                        $(this).addClass('selected');
                                    }
                                });
                            } else if (isBottomModalOpen) {
                                // Update bottom modal fields
                                $('#saved_user_image_url_bottom').val(selectedImageUrl);
                                $('#user_image_bottom').val('').prop('required', false);
                                $('#selected-photo-name-bottom').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + selectedImageUrl.split('/').pop() );
                                
                                // Update the inline strip selection
                                $('#my_uploads_list_bottom .thumb').removeClass('selected');
                                // Find and select the corresponding thumbnail in the inline strip
                                $('#my_uploads_list_bottom .thumb').each(function() {
                                    if ($(this).data('url') === selectedImageUrl) {
                                        $(this).addClass('selected');
                                    }
                                });
                            } else {
                                // Update regular modal fields
                            $('#saved_user_image_url').val(selectedImageUrl);
                            $('#user_image').val('').prop('required', false);
                            $('#selected-photo-name').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + selectedImageUrl.split('/').pop() );
                                
                                // Update the inline strip selection
                                $('#my_uploads_list .thumb').removeClass('selected');
                                // Find and select the corresponding thumbnail in the inline strip
                                $('#my_uploads_list .thumb').each(function() {
                                    if ($(this).data('url') === selectedImageUrl) {
                                        $(this).addClass('selected');
                                    }
                                });
                            }
                            
                            // Close modal completely
                            $(modal).removeClass('is-open').hide();
                            showInfoPopup( __( 'Photo selected! Click Generate Preview.', 'woo-fitroom-preview' ) );
                        });
                    } else {
                        showInfoPopup( __( 'No saved images.', 'woo-fitroom-preview' ) );
                    }
                },
                error: () => showErrorPopup( __( 'Error fetching images', 'woo-fitroom-preview' ) )
            });
        }

        // Handle image click to select and update the input field (gallery modal)
        $(document).on('click', '.img-item img', function() {
            // Get the URL of the clicked image
            const selectedImageUrl = $(this).attr('src');
            
            // Update the hidden input field with the selected image URL
            $('#saved_user_image_url').val(selectedImageUrl);
            // Clear file input and remove required since we are using saved image
            $('#user_image').val('').prop('required', false);
            // Display name of selected image for user feedback
            $('#selected-photo-name').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + selectedImageUrl.split('/').pop() );
            console.log('Selected Image URL:', selectedImageUrl);
            console.log('Input field value set to:', $('#saved_user_image_url').val());
            
            // Provide feedback to the user (translated)
            showInfoPopup( __( 'Photo selected! Click Generate Preview.', 'woo-fitroom-preview' ) );

            // Close the gallery modal
            $('#uploaded-images-modal').removeClass('is-open').hide();
        });

        // Handle click on inline strip thumbnails
        $(document).on('click', '#my_uploads_list .thumb:not(.more)', function(){
            const originalUrl = $(this).data('url');
            $('#saved_user_image_url').val(originalUrl);
            $('#user_image').val('').prop('required', false);
            $('#selected-photo-name').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + originalUrl.split('/').pop() );
            $('#my_uploads_list .thumb').removeClass('selected');
            $(this).addClass('selected');
        });

        // Handle click on top-bottom modal images
        $(document).on('click', '#my_uploads_list_top_bottom .thumb:not(.more)', function(){
            const originalUrl = $(this).data('url');
            $('#saved_user_image_url_top_bottom').val(originalUrl);
            $('#user_image_top_bottom').val('').prop('required', false);
            $('#selected-photo-name-top-bottom').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + originalUrl.split('/').pop() );
            $('#my_uploads_list_top_bottom .thumb').removeClass('selected');
            $(this).addClass('selected');
        });

        // Handle click on the "More" tile -> open existing modal loader
        $(document).on('click', '#my_uploads_more', function(){
            openUploadedImagesModal();
        });

        // Handle click on bottom modal images
        $(document).on('click', '#my_uploads_list_bottom .thumb:not(.more)', function(){
            const originalUrl = $(this).data('url');
            $('#saved_user_image_url_bottom').val(originalUrl);
            $('#user_image_bottom').val('').prop('required', false);
            $('#selected-photo-name-bottom').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + originalUrl.split('/').pop() );
            $('#my_uploads_list_bottom .thumb').removeClass('selected');
            $(this).addClass('selected');
        });

        // Handle click on the "More" tile in bottom modal -> open existing modal loader
        $(document).on('click', '#my_uploads_more_bottom', function(){
            openUploadedImagesModal();
        });

        // Handle click on the "More" tile in top-bottom modal -> open existing modal loader
        $(document).on('click', '#my_uploads_more_top_bottom', function(){
            openUploadedImagesModal();
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function(e){
            e.stopPropagation(); // Prevent triggering image selection
            const imageUrl = $(this).data('url');
            showDeleteConfirmation(imageUrl);
        });

        // Handle delete all button clicks
        $(document).on('click', '#delete_all_images_btn', function(e){
            e.preventDefault();
            e.stopPropagation();
            showDeleteAllConfirmation();
        });

        // Delete confirmation popup functions
        function showDeleteConfirmation(imageUrl) {
            const popup = $(`
                <div class="delete-confirmation-popup">
                    <div class="delete-confirmation-content">
                        <h3>${__('Delete Image', 'woo-fitroom-preview')}</h3>
                        <p>${__('Are you sure you want to delete this image permanently?', 'woo-fitroom-preview')}</p>
                        <div class="delete-confirmation-buttons">
                            <button class="delete-cancel-btn">${__('Cancel', 'woo-fitroom-preview')}</button>
                            <button class="delete-confirm-btn" data-url="${imageUrl}">${__('Delete', 'woo-fitroom-preview')}</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(popup);
            
            // Handle cancel
            popup.on('click', '.delete-cancel-btn', function(){
                popup.remove();
            });
            
            // Handle confirm
            popup.on('click', '.delete-confirm-btn', function(){
                const urlToDelete = $(this).data('url');
                deleteImage(urlToDelete);
                popup.remove();
            });
            
            // Close on background click
            popup.on('click', function(e){
                if (e.target === this) {
                    popup.remove();
                }
            });
        }

        // Delete all confirmation popup function
        function showDeleteAllConfirmation() {
            const popup = $(`
                <div class="delete-confirmation-popup">
                    <div class="delete-confirmation-content">
                        <h3>${__('Delete All Images', 'woo-fitroom-preview')}</h3>
                        <p>${__('Are you sure you want to delete ALL your uploaded images permanently? This action cannot be undone.', 'woo-fitroom-preview')}</p>
                        <div class="delete-confirmation-buttons">
                            <button class="delete-cancel-btn">${__('Cancel', 'woo-fitroom-preview')}</button>
                            <button class="delete-confirm-btn delete-all-confirm-btn">${__('Delete All', 'woo-fitroom-preview')}</button>
                        </div>
                    </div>
                </div>
            `);
            
            $('body').append(popup);
            
            // Handle cancel
            popup.on('click', '.delete-cancel-btn', function(){
                popup.remove();
            });
            
            // Handle confirm
            popup.on('click', '.delete-all-confirm-btn', function(){
                deleteAllImages();
                popup.remove();
            });
            
            // Close on background click
            popup.on('click', function(e){
                if (e.target === this) {
                    popup.remove();
                }
            });
        }

        // Delete image function
        function deleteImage(imageUrl) {
            console.log('Delete Image: Attempting to delete URL:', imageUrl);
            
            // Show loading state on the delete button
            const deleteBtn = $(`.delete-btn[data-url="${imageUrl}"]`);
            const originalText = deleteBtn.attr('title');
            deleteBtn.attr('title', __('Deleting...', 'woo-fitroom-preview')).prop('disabled', true);
            
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_user_uploaded_image',
                    image_url: imageUrl,
                    user_id: WooFitroomPreview.user_id,
                    nonce: WooFitroomPreview.nonce
                },
                success: function(response) {
                    console.log('Delete Image: Server response:', response);
                    
                    // Reset button state
                    deleteBtn.attr('title', originalText).prop('disabled', false);
                    
                    if (response.success) {
                        // Only remove the image from UI if deletion was successful
                        $(`.thumb[data-url="${imageUrl}"]`).remove();
                        $(`.img-item img[data-url="${imageUrl}"]`).closest('.img-item').remove();
                        
                        // Refresh the inline strip if needed
                        if (WooFitroomPreview.user_id && WooFitroomPreview.user_id !== 0) {
                            refreshInlineStrip();
                        }
                        
                        // Show success message
                        showSuccessPopup(__('Image deleted successfully', 'woo-fitroom-preview'));
                    } else {
                        console.error('Delete Image: Server error:', response.data.message);
                        // Show error message but keep image in UI
                        showErrorPopup(response.data.message || __('Failed to delete image. Please try again.', 'woo-fitroom-preview'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete Image: AJAX error:', {xhr, status, error});
                    
                    // Reset button state
                    deleteBtn.attr('title', originalText).prop('disabled', false);
                    
                    // Show error message but keep image in UI
                    let errorMessage = __('Error communicating with server. Please check your internet connection and try again.', 'woo-fitroom-preview');
                    
                    if (xhr.status === 0) {
                        errorMessage = __('No internet connection. Please check your connection and try again.', 'woo-fitroom-preview');
                    } else if (xhr.status === 500) {
                        errorMessage = __('Server error. Please try again later.', 'woo-fitroom-preview');
                    } else if (xhr.status === 404) {
                        errorMessage = __('Service not available. Please try again later.', 'woo-fitroom-preview');
                    }
                    
                    showErrorPopup(errorMessage);
                }
            });
        }

        // Delete all images function
        function deleteAllImages() {
            console.log('Delete All Images: Attempting to delete all images for user:', WooFitroomPreview.user_id);
            
            // Show loading state on the delete all button
            const deleteAllBtn = $('#delete_all_images_btn');
            const originalText = deleteAllBtn.text();
            deleteAllBtn.text(__('Deleting...', 'woo-fitroom-preview')).prop('disabled', true);
            
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: {
                    action: 'delete_all_user_images',
                    user_id: WooFitroomPreview.user_id,
                    nonce: WooFitroomPreview.nonce
                },
                success: function(response) {
                    console.log('Delete All Images: Server response:', response);
                    
                    // Reset button state
                    deleteAllBtn.text(originalText).prop('disabled', false);
                    
                    if (response.success) {
                        // Only clear images from UI if deletion was successful
                        $('#my_uploads_list').empty();
                        $('#my_uploads_strip').hide();
                        $('#delete_all_images_btn').hide();
                        
                        // Clear any modal grid if open
                        $('.uploaded-images-grid').empty();
                        
                        // Clear saved image selection
                        $('#saved_user_image_url').val('');
                        $('#selected-photo-name').text('');
                        
                        // Show success message
                        const message = response.data.message || __('All images deleted successfully', 'woo-fitroom-preview');
                        showSuccessPopup(message);
                    } else {
                        console.error('Delete All Images: Server error:', response.data.message);
                        // Show error message but keep images in UI
                        showErrorPopup(response.data.message || __('Failed to delete all images. Please try again.', 'woo-fitroom-preview'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete All Images: AJAX error:', {xhr, status, error});
                    
                    // Reset button state
                    deleteAllBtn.text(originalText).prop('disabled', false);
                    
                    // Show error message but keep images in UI
                    let errorMessage = __('Error communicating with server. Please check your internet connection and try again.', 'woo-fitroom-preview');
                    
                    if (xhr.status === 0) {
                        errorMessage = __('No internet connection. Please check your connection and try again.', 'woo-fitroom-preview');
                    } else if (xhr.status === 500) {
                        errorMessage = __('Server error. Please try again later.', 'woo-fitroom-preview');
                    } else if (xhr.status === 404) {
                        errorMessage = __('Service not available. Please try again later.', 'woo-fitroom-preview');
                    }
                    
                    showErrorPopup(errorMessage);
                }
            });
        }

        // Refresh inline strip after deletion
        function refreshInlineStrip() {
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_uploaded_images',
                    user_id: WooFitroomPreview.user_id,
                    nonce: WooFitroomPreview.nonce
                },
                success: function(res){
                    if(res.success && res.data.images.length){
                        const list = $('#my_uploads_list');
                        const strip = $('#my_uploads_strip');
                        strip.show();
                        list.empty();
                        const maxThumbs = 3;
                        const images = res.data.images;
                        const visible = images.slice(0, maxThumbs);
                        visible.forEach((u, idx) => {
                            const isLastAndMore = (idx === maxThumbs - 1) && (images.length > maxThumbs);
                            if (isLastAndMore) {
                                list.append(`<div class="thumb more" id="my_uploads_more"><span>${__('View More','woo-fitroom-preview')}</span></div>`);
                            } else {
                                const prox = getProxyImageUrl(u);
                                list.append(`<div class="thumb" data-url="${u}"><img src="${prox}" data-url="${u}" alt="uploaded"/><button class="delete-btn tryon-delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`);
                            }
                        });
                        // Pre-select first image if available
                        if (images.length > 0) {
                            $('#saved_user_image_url').val(images[0]);
                            list.find('.thumb').first().addClass('selected');
                        }
                        // Show delete all button if there are images
                        $('#delete_all_images_btn').show();
                    } else {
                        $('#my_uploads_strip').hide();
                        $('#delete_all_images_btn').hide();
                    }
                }
            });
        }

        // Load images for the top-bottom modal
        function loadTopBottomModalImages() {
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_uploaded_images',
                    user_id: WooFitroomPreview.user_id,
                    nonce: WooFitroomPreview.nonce
                },
                success: function(res){
                    if(res.success && res.data.images.length){
                        const list = $('#my_uploads_list_top_bottom');
                        const strip = $('#my_uploads_strip_top_bottom');
                        strip.show();
                        list.empty();
                        const maxThumbs = 3;
                        const images = res.data.images;
                        const visible = images.slice(0, maxThumbs);
                        visible.forEach((u, idx) => {
                            const isLastAndMore = (idx === maxThumbs - 1) && (images.length > maxThumbs);
                            if (isLastAndMore) {
                                list.append(`<div class="thumb more" id="my_uploads_more_top_bottom"><span>${__('View More','woo-fitroom-preview')}</span></div>`);
                            } else {
                                const prox = getProxyImageUrl(u);
                                list.append(`<div class="thumb" data-url="${u}"><img src="${prox}" data-url="${u}" alt="uploaded"/><button class="delete-btn tryon-delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`);
                            }
                        });
                        // Pre-select first image if available
                        if (images.length > 0) {
                            $('#saved_user_image_url_top_bottom').val(images[0]);
                            list.find('.thumb').first().addClass('selected');
                        }
                        // Show delete all button if there are images
                        $('#delete_all_images_btn_top_bottom').show();
                    } else {
                        $('#my_uploads_strip_top_bottom').hide();
                        $('#delete_all_images_btn_top_bottom').hide();
                    }
                }
            });
        }

        // Load images for the bottom modal
        function loadBottomModalImages() {
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: {
                    action: 'get_user_uploaded_images',
                    user_id: WooFitroomPreview.user_id,
                    nonce: WooFitroomPreview.nonce
                },
                success: function(res){
                    if(res.success && res.data.images.length){
                        const list = $('#my_uploads_list_bottom');
                        const strip = $('#my_uploads_strip_bottom');
                        strip.show();
                        list.empty();
                        const maxThumbs = 3;
                        const images = res.data.images;
                        const visible = images.slice(0, maxThumbs);
                        visible.forEach((u, idx) => {
                            const isLastAndMore = (idx === maxThumbs - 1) && (images.length > maxThumbs);
                            if (isLastAndMore) {
                                list.append(`<div class="thumb more" id="my_uploads_more_bottom"><span>${__('View More','woo-fitroom-preview')}</span></div>`);
                            } else {
                                const prox = getProxyImageUrl(u);
                                list.append(`<div class="thumb" data-url="${u}"><img src="${prox}" data-url="${u}" alt="uploaded"/><button class="delete-btn tryon-delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`);
                            }
                        });
                        // Pre-select first image if available
                        if (images.length > 0) {
                            $('#saved_user_image_url_bottom').val(images[0]);
                            list.find('.thumb').first().addClass('selected');
                        }
                        // Show delete all button if there are images
                        $('#delete_all_images_btn_bottom').show();
                    } else {
                        $('#my_uploads_strip_bottom').hide();
                        $('#delete_all_images_btn_bottom').hide();
                    }
                }
            });
        }

        // Handle form submission for full outfit
        $('#woo-fitroom-preview-form').on('submit', function(e) {
            e.preventDefault();
            
            // Hide the button immediately when form is submitted
            $(this).find('button[type="submit"]').hide();
            $(this).closest('.form-submit').hide();
            
            // Determine consent requirements from modal attributes
            const modal = document.getElementById('woo-fitroom-preview-modal');
            const needConsent = modal && modal.getAttribute('data-require-consent') === '1';
            const showExtra = modal && modal.getAttribute('data-show-extra-consents') === '1';
            
            // Client-side consent validation (prevents generic server error)
            if (needConsent && !$('#user_consent').prop('checked')) {
                $('.preview-error').show().find('.error-message').text(__('Please provide consent before generating a preview.', 'woo-fitroom-preview'));
                $('#user_consent').closest('.form-field').show();
                // Show the button again if validation fails
                $(this).find('button[type="submit"]').show();
                $(this).closest('.form-submit').show();
                return;
            }
            if (showExtra) {
                const hasTerms = $('#terms_consent').length ? $('#terms_consent').prop('checked') : true;
                const hasRefund = $('#refund_consent').length ? $('#refund_consent').prop('checked') : true;
                if (!hasTerms || !hasRefund) {
                    $('.preview-error').show().find('.error-message').text(__('Please agree to the Terms/Privacy and Refund Policy.', 'woo-fitroom-preview'));
                    $('#terms_consent, #refund_consent').each(function(){ $(this).closest('.form-field').show(); });
                    // Show the button again if validation fails
                    $(this).find('button[type="submit"]').show();
                    $(this).closest('.form-submit').show();
                    return;
                }
            }
            
            // Hide previous results and errors, and hide the upload UI while generating
            $('.preview-result').hide();
            $('.preview-error').hide();
            // hide the initial content area (uploads strip + dropzone + button)
            $('#my_uploads_strip').hide();
            $('#user_image_dropzone').closest('.form-field').hide();
            // Hide form submit (already hidden above, but ensure it stays hidden)
            $(this).closest('.form-submit').hide();
            // Hide all consent blocks once validation passes for the first submission
            $('#user_consent').closest('.form-field').hide();
            $('#terms_consent, #refund_consent').each(function(){ $(this).closest('.form-field').hide(); });
            // Ensure inputs won't block subsequent submissions
            $('#user_consent').prop('required', false);
            $('#terms_consent, #refund_consent').prop('required', false);
            
            // Show loading indicator
            if (!$('.loading-indicator').length) {
                $('.preview-error').after('<div class="loading-indicator"><p>' + __( 'Generating image… This may take up to 60 seconds.', 'woo-fitroom-preview' ) + '</p><div class="spinner"></div></div>');
            }
            $('.loading-indicator').show();
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'woo_fitroom_generate_preview');
            formData.append('nonce', WooFitroomPreview.nonce);
            
            // Add the selected image URL to the form data
            const selectedImageUrl = $('#saved_user_image_url').val();
            if (selectedImageUrl) {
                // Ensure the server receives the parameter name it expects
                formData.append('saved_user_image_url', selectedImageUrl);
            }
            // Ensure consent fields are explicitly sent on first run
            if ($('#user_consent').length) {
                formData.append('user_consent', $('#user_consent').is(':checked') ? '1' : '');
            }
            if ($('#terms_consent').length) {
                formData.append('terms_consent', $('#terms_consent').is(':checked') ? '1' : '');
            }
            if ($('#refund_consent').length) {
                formData.append('refund_consent', $('#refund_consent').is(':checked') ? '1' : '');
            }
            
            // Disable the submit button and change its text
            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text(WooFitroomPreview.i18n.processing);
            
            // Send AJAX request
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-WP-Nonce': WooFitroomPreview.nonce
                },
                success: function(response) {
                    console.log('API Response:', response);
                    if (response.success) {
                        if (response.data && response.data.image_url) {
                            const img = new Image();
                            img.onload = function() {
                                console.log('Generated image loaded successfully');
                                $('.preview-image').html(`<img src="${response.data.image_url}" alt="AI Preview">`);
                                // If server saved the newly uploaded user photo, prepend it to the inline strip immediately
                                if (response.data && response.data.user_image_saved_url) {
                                    const u = response.data.user_image_saved_url;
                                    const prox = getProxyImageUrl(u);
                                    const list = $('#my_uploads_list');
                                    if (list.length) {
                                        // Prepend and trim grid to 3 tiles (keeping "More" at the end if exists)
                                        list.prepend(`<div class="thumb" data-url="${u}"><img src="${prox}" alt="uploaded"/></div>`);
                                        // Remove any surplus thumbnails before the "more" tile
                                        const thumbs = list.find('.thumb:not(.more)');
                                        if (thumbs.length > 3) {
                                            thumbs.last().remove();
                                        }
                                        // Ensure strip is visible for next regenerate session
                                        $('#my_uploads_strip').show();
                                    }
                                }
                                $('.preview-result').show();
                                // hide the top UI while preview is visible
                                $('#my_uploads_strip').hide();
                                $('#user_image_dropzone').closest('.form-field').hide();
                                $('#woo-fitroom-preview-form .form-submit').hide();
                                // Mark consents as completed for this session and future interactions
                                const modalEl = document.getElementById('woo-fitroom-preview-modal');
                                if (modalEl) {
                                    modalEl.setAttribute('data-require-consent', '0');
                                    modalEl.setAttribute('data-show-extra-consents', '0');
                                    modalEl.classList.add('preview-mode');
                                }
                                // Permanently hide all consent checkboxes after first success
                                $('#user_consent').closest('.form-field').hide();
                                $('#terms_consent, #refund_consent').each(function(){
                                    $(this).closest('.form-field').hide();
                                });
                                $('#user_consent').prop('required', false);
                                $('#terms_consent, #refund_consent').prop('required', false);
                                $('.preview-error').hide();
                                $('#woo-fitroom-preview-modal .download-preview').attr('data-url', response.data.image_url);
                                
                                // Show the "Try Full Outfit" button for regular modal
                                $('#woo-fitroom-preview-modal .try-full-outfit-btn').css('display', 'inline-block');
                                
                                console.log('Regular Modal - Download URL set:', response.data.image_url);
                            };
                            img.onerror = function() {
                                console.error('Failed to load generated image from: ' + response.data.image_url);
                                $('.preview-error')
                                    .show()
                                    .find('.error-message')
                                    .text(__('Generated image could not be loaded. Please try again.', 'woo-fitroom-preview'));
                            };
                            (function(){
                                var outUrl = response.data.image_url || '';
                                // Only cache-bust same-origin (local/proxy) URLs to avoid breaking signed external URLs
                                try {
                                    var u = new URL(outUrl, window.location.origin);
                                    var isLocal = (u.origin === window.location.origin);
                                    if (isLocal) {
                                        outUrl = outUrl + (outUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                    }
                                } catch(e) {
                                    // Fallback for relative-only strings
                                    if (outUrl.indexOf('/') === 0) {
                                        outUrl = outUrl + (outUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                    }
                                }
                                img.src = outUrl;
                            })();
                            console.log('Attempting to load image from:', img.src);
                        } else {
                            console.error('Success response, but image_url missing:', response);
                            $('.preview-error')
                                .show()
                                .find('.error-message')
                                .text(response.data && response.data.message ? response.data.message : __('AI preview generated, but the result could not be retrieved.', 'woo-fitroom-preview'));
                        }
                    } else {
                        console.error('API Error Response:', response);
                        let errorMessage = WooFitroomPreview.i18n.error;
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        $('.preview-error')
                            .show()
                            .find('.error-message')
                            .text(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr, status, error});
                    let errorText = WooFitroomPreview.i18n.error + ': ' + error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorText = xhr.responseJSON.data.message;
                    }
                    $('.preview-error')
                        .show()
                        .find('.error-message')
                        .text(errorText);
                },
                complete: function() {
                    submitButton.prop('disabled', false).text(WooFitroomPreview.i18n.success);
                    $('.loading-indicator').hide();
                }
            });
        });

        // Handle form submission for top/bottom
        $('#woo-fitroom-preview-form-top-bottom').on('submit', function(e) {
            e.preventDefault();
            
            // Hide the button immediately when form is submitted
            $(this).find('button[type="submit"]').hide();
            $(this).closest('.form-submit').hide();
            
            // Determine consent requirements from modal attributes
            const modal = document.getElementById('woo-fitroom-preview-modal-top-bottom');
            const needConsent = modal && modal.getAttribute('data-require-consent') === '1';
            const showExtra = modal && modal.getAttribute('data-show-extra-consents') === '1';
            
            // Client-side consent validation (prevents generic server error)
            if (needConsent && !$('#user_consent_top_bottom').prop('checked')) {
                $('.preview-error').show().find('.error-message').text(__('Please provide consent before generating a preview.', 'woo-fitroom-preview'));
                $('#user_consent_top_bottom').closest('.form-field').show();
                // Show the button again if validation fails
                $(this).find('button[type="submit"]').show();
                $(this).closest('.form-submit').show();
                return;
            }
            if (showExtra) {
                const hasTerms = $('#terms_consent_top_bottom').length ? $('#terms_consent_top_bottom').prop('checked') : true;
                const hasRefund = $('#refund_consent_top_bottom').length ? $('#refund_consent_top_bottom').prop('checked') : true;
                if (!hasTerms || !hasRefund) {
                    $('.preview-error').show().find('.error-message').text(__('Please agree to the Terms/Privacy and Refund Policy.', 'woo-fitroom-preview'));
                    $('#terms_consent_top_bottom, #refund_consent_top_bottom').each(function(){ $(this).closest('.form-field').show(); });
                    // Show the button again if validation fails
                    $(this).find('button[type="submit"]').show();
                    $(this).closest('.form-submit').show();
                    return;
                }
            }
            
            // Hide previous results and errors, and hide the upload UI while generating
            $('.preview-result').hide();
            $('.preview-error').hide();
            // hide the initial content area (uploads strip + dropzone + button)
            $('#my_uploads_strip_top_bottom').hide();
            $('#user_image_dropzone_top_bottom').closest('.form-field').hide();
            // Hide form submit (already hidden above, but ensure it stays hidden)
            $(this).closest('.form-submit').hide();
            // Hide all consent blocks once validation passes for the first submission
            $('#user_consent_top_bottom').closest('.form-field').hide();
            $('#terms_consent_top_bottom, #refund_consent_top_bottom').each(function(){ $(this).closest('.form-field').hide(); });
            // Ensure inputs won't block subsequent submissions
            $('#user_consent_top_bottom').prop('required', false);
            $('#terms_consent_top_bottom, #refund_consent_top_bottom').prop('required', false);
            
            // Show loading indicator
            if (!$('.loading-indicator').length) {
                $('.preview-error').after('<div class="loading-indicator"><p>' + __( 'Generating image… This may take up to 60 seconds.', 'woo-fitroom-preview' ) + '</p><div class="spinner"></div></div>');
            }
            $('.loading-indicator').show();
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'woo_fitroom_generate_preview');
            formData.append('nonce', WooFitroomPreview.nonce);
            
            // Add the selected image URL to the form data
            const selectedImageUrl = $('#saved_user_image_url_top_bottom').val();
            if (selectedImageUrl) {
                // Ensure the server receives the parameter name it expects
                formData.append('saved_user_image_url', selectedImageUrl);
            }
            // Ensure consent fields are explicitly sent on first run
            if ($('#user_consent_top_bottom').length) {
                formData.append('user_consent', $('#user_consent_top_bottom').is(':checked') ? '1' : '');
            }
            if ($('#terms_consent_top_bottom').length) {
                formData.append('terms_consent', $('#terms_consent_top_bottom').is(':checked') ? '1' : '');
            }
            if ($('#refund_consent_top_bottom').length) {
                formData.append('refund_consent', $('#refund_consent_top_bottom').is(':checked') ? '1' : '');
            }
            
            // Disable the submit button and change its text
            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text(WooFitroomPreview.i18n.processing);
            
            // Send AJAX request
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-WP-Nonce': WooFitroomPreview.nonce
                },
                success: function(response) {
                    console.log('API Response:', response);
                    if (response.success) {
                        if (response.data && response.data.image_url) {
                            const img = new Image();
                            img.onload = function() {
                                console.log('Generated image loaded successfully');
                                $('.preview-image').html(`<img src="${response.data.image_url}" alt="AI Preview">`);
                                // If server saved the newly uploaded user photo, prepend it to the inline strip immediately
                                if (response.data && response.data.user_image_saved_url) {
                                    const u = response.data.user_image_saved_url;
                                    const prox = getProxyImageUrl(u);
                                    const list = $('#my_uploads_list_top_bottom');
                                    if (list.length) {
                                        // Prepend and trim grid to 3 tiles (keeping "More" at the end if exists)
                                        list.prepend(`<div class="thumb" data-url="${u}"><img src="${prox}" alt="uploaded"/></div>`);
                                        // Remove any surplus thumbnails before the "more" tile
                                        const thumbs = list.find('.thumb:not(.more)');
                                        if (thumbs.length > 3) {
                                            thumbs.last().remove();
                                        }
                                        // Don't show the strip here - it will be hidden below anyway
                                    }
                                }
                                $('.preview-result').show();
                                // Store the generated preview image URL for later use by "Try Full Outfit" button
                                $('#woo-fitroom-preview-modal-top-bottom .preview-result').attr('data-user-image-url', response.data.image_url);
                                // hide the top UI while preview is visible
                                $('#my_uploads_strip_top_bottom').hide();
                                $('#user_image_dropzone_top_bottom').closest('.form-field').hide();
                                $('#woo-fitroom-preview-form-top-bottom .form-submit').hide();
                                // Mark consents as completed for this session and future interactions
                                const modalEl = document.getElementById('woo-fitroom-preview-modal-top-bottom');
                                if (modalEl) {
                                    modalEl.setAttribute('data-require-consent', '0');
                                    modalEl.setAttribute('data-show-extra-consents', '0');
                                    modalEl.classList.add('preview-mode');
                                }
                            
                                // Permanently hide all consent checkboxes after first success
                                $('#user_consent_top_bottom').closest('.form-field').hide();
                                $('#terms_consent_top_bottom, #refund_consent_top_bottom').each(function(){
                                    $(this).closest('.form-field').hide();
                                });
                                $('#user_consent_top_bottom').prop('required', false);
                                $('#terms_consent_top_bottom, #refund_consent_top_bottom').prop('required', false);
                                $('.preview-error').hide();
                                $('#woo-fitroom-preview-modal-top-bottom .download-preview').attr('data-url', response.data.image_url);
                                
                                // Show the "Try Full Outfit" button for top/bottom modal
                                $('#woo-fitroom-preview-modal-top-bottom .try-full-outfit-btn').css('display', 'inline-block');
                                
                                console.log('Top-Bottom Modal - Download URL set:', response.data.image_url);
                            };
                            img.onerror = function() {
                                console.error('Failed to load generated image from: ' + response.data.image_url);
                                $('.preview-error')
                                    .show()
                                    .find('.error-message')
                                    .text(__('Generated image could not be loaded. Please try again.', 'woo-fitroom-preview'));
                            };
                            (function(){
                                var outUrl = response.data.image_url || '';
                                // Only cache-bust same-origin (local/proxy) URLs to avoid breaking signed external URLs
                                try {
                                    var u = new URL(outUrl, window.location.origin);
                                    var isLocal = (u.origin === window.location.origin);
                                    if (isLocal) {
                                        outUrl = outUrl + (outUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                    }
                                } catch(e) {
                                    // Fallback for relative-only strings
                                    if (outUrl.indexOf('/') === 0) {
                                        outUrl = outUrl + (outUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                    }
                                }
                                img.src = outUrl;
                            })();
                            console.log('Attempting to load image from:', img.src);
                        } else {
                            console.error('Success response, but image_url missing:', response);
                            $('.preview-error')
                                .show()
                                .find('.error-message')
                                .text(response.data && response.data.message ? response.data.message : __('AI preview generated, but the result could not be retrieved.', 'woo-fitroom-preview'));
                        }
                    } else {
                        console.error('API Error Response:', response);
                        let errorMessage = WooFitroomPreview.i18n.error;
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        $('.preview-error')
                            .show()
                            .find('.error-message')
                            .text(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr, status, error});
                    let errorText = WooFitroomPreview.i18n.error + ': ' + error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorText = xhr.responseJSON.data.message;
                    }
                    $('.preview-error')
                        .show()
                        .find('.error-message')
                        .text(errorText);
                },
                complete: function() {
                    submitButton.prop('disabled', false).text(WooFitroomPreview.i18n.success);
                    $('.loading-indicator').hide();
                }
            });
        });

        // Handle form submission for bottom
        $('#woo-fitroom-preview-form-bottom').on('submit', function(e) {
            e.preventDefault();
            
            // Hide the button immediately when form is submitted
            $(this).find('button[type="submit"]').hide();
            $(this).closest('.form-submit').hide();
            
            // Determine consent requirements from modal attributes
            const modal = document.getElementById('woo-fitroom-preview-modal-bottom');
            const needConsent = modal && modal.getAttribute('data-require-consent') === '1';
            const showExtra = modal && modal.getAttribute('data-show-extra-consents') === '1';
            
            // Client-side consent validation (prevents generic server error)
            if (needConsent && !$('#user_consent_bottom').prop('checked')) {
                $('.preview-error').show().find('.error-message').text(__('Please provide consent before generating a preview.', 'woo-fitroom-preview'));
                $('#user_consent_bottom').closest('.form-field').show();
                // Show the button again if validation fails
                $(this).find('button[type="submit"]').show();
                $(this).closest('.form-submit').show();
                return;
            }
            if (showExtra) {
                const hasTerms = $('#terms_consent_bottom').length ? $('#terms_consent_bottom').prop('checked') : true;
                const hasRefund = $('#refund_consent_bottom').length ? $('#refund_consent_bottom').prop('checked') : true;
                if (!hasTerms || !hasRefund) {
                    $('.preview-error').show().find('.error-message').text(__('Please agree to the Terms/Privacy and Refund Policy.', 'woo-fitroom-preview'));
                    $('#terms_consent_bottom, #refund_consent_bottom').each(function(){ $(this).closest('.form-field').show(); });
                    // Show the button again if validation fails
                    $(this).find('button[type="submit"]').show();
                    $(this).closest('.form-submit').show();
                    return;
                }
            }
            
            // Hide previous results and errors, and hide the upload UI while generating
            $('.preview-result').hide();
            $('.preview-error').hide();
            // hide the initial content area (uploads strip + dropzone + button)
            $('#my_uploads_strip_bottom').hide();
            $('#user_image_dropzone_bottom').closest('.form-field').hide();
            // Hide form submit (already hidden above, but ensure it stays hidden)
            $(this).closest('.form-submit').hide();
            // Hide all consent blocks once validation passes for the first submission
            $('#user_consent_bottom').closest('.form-field').hide();
            $('#terms_consent_bottom, #refund_consent_bottom').each(function(){ $(this).closest('.form-field').hide(); });
            // Ensure inputs won't block subsequent submissions
            $('#user_consent_bottom').prop('required', false);
            $('#terms_consent_bottom, #refund_consent_bottom').prop('required', false);
            
            // Show loading indicator
            if (!$('.loading-indicator').length) {
                $('.preview-error').after('<div class="loading-indicator"><p>' + __( 'Generating image… This may take up to 60 seconds.', 'woo-fitroom-preview' ) + '</p><div class="spinner"></div></div>');
            }
            $('.loading-indicator').show();
            
            // Prepare form data
            const formData = new FormData(this);
            formData.append('action', 'woo_fitroom_generate_preview');
            formData.append('nonce', WooFitroomPreview.nonce);
            
            // Add the selected image URL to the form data
            const selectedImageUrl = $('#saved_user_image_url_bottom').val();
            if (selectedImageUrl) {
                // Ensure the server receives the parameter name it expects
                formData.append('saved_user_image_url', selectedImageUrl);
            }
            // Ensure consent fields are explicitly sent on first run
            if ($('#user_consent_bottom').length) {
                formData.append('user_consent', $('#user_consent_bottom').is(':checked') ? '1' : '');
            }
            if ($('#terms_consent_bottom').length) {
                formData.append('terms_consent', $('#terms_consent_bottom').is(':checked') ? '1' : '');
            }
            if ($('#refund_consent_bottom').length) {
                formData.append('refund_consent', $('#refund_consent_bottom').is(':checked') ? '1' : '');
            }
            
            // Disable the submit button and change its text
            const submitButton = $(this).find('button[type="submit"]');
            submitButton.prop('disabled', true).text(WooFitroomPreview.i18n.processing);
            
            // Send AJAX request
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-WP-Nonce': WooFitroomPreview.nonce
                },
                success: function(response) {
                    console.log('API Response:', response);
                    if (response.success) {
                        if (response.data && response.data.image_url) {
                            // If server saved the newly uploaded user photo, prepend it to the inline strip immediately
                            if (response.data && response.data.user_image_saved_url) {
                                const u = response.data.user_image_saved_url;
                                const prox = getProxyImageUrl(u);
                                const list = $('#my_uploads_list_bottom');
                                if (list.length) {
                                    // Prepend and trim grid to 3 tiles (keeping "More" at the end if exists)
                                    list.prepend(`<div class="thumb" data-url="${u}"><img src="${prox}" alt="uploaded"/></div>`);
                                    // Remove any surplus thumbnails before the "more" tile
                                    const thumbs = list.find('.thumb:not(.more)');
                                    if (thumbs.length > 3) {
                                        thumbs.last().remove();
                                    }
                                    // Don't show the strip here - it will be hidden below anyway
                                }
                            }
                            $('.preview-result').show();
                            // hide the top UI while preview is visible
                            $('#my_uploads_strip_bottom').hide();
                            $('#user_image_dropzone_bottom').closest('.form-field').hide();
                            $('#woo-fitroom-preview-form-bottom .form-submit').hide();
                            // Mark consents as completed for this session and future interactions
                            const modalEl = document.getElementById('woo-fitroom-preview-modal-bottom');
                            if (modalEl) {
                                modalEl.setAttribute('data-require-consent', '0');
                                modalEl.setAttribute('data-show-extra-consents', '0');
                                modalEl.classList.add('preview-mode');
                            }
                            
                            const img = new Image();
                            img.onload = function() {
                                $('.preview-image').html(img);
                                $('.preview-error').hide();
                                $('#woo-fitroom-preview-modal-bottom .download-preview').attr('data-url', response.data.image_url);
                                
                                // Store the generated preview image URL for later use by "Try Full Outfit" button
                                $('#woo-fitroom-preview-modal-bottom .preview-result').attr('data-user-image-url', response.data.image_url);
                                
                                // Show the "Try Full Outfit" button for bottom modal
                                $('#woo-fitroom-preview-modal-bottom .try-full-outfit-btn').css('display', 'inline-block');
                                
                                console.log('Bottom Modal - Download URL set:', response.data.image_url);
                            };
                            img.onerror = function() {
                                console.error('Failed to load generated image from: ' + response.data.image_url);
                                $('.preview-error')
                                    .show()
                                    .find('.error-message')
                                    .text(__('Generated image could not be loaded. Please try again.', 'woo-fitroom-preview'));
                            };
                            (function(){
                                var outUrl = response.data.image_url || '';
                                // Only cache-bust same-origin (local/proxy) URLs to avoid breaking signed external URLs
                                try {
                                    var u = new URL(outUrl, window.location.origin);
                                    var isLocal = (u.origin === window.location.origin);
                                    if (isLocal) {
                                        outUrl = outUrl + (outUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                    }
                                } catch(e) {
                                    // Fallback for relative-only strings
                                    if (outUrl.indexOf('/') === 0) {
                                        outUrl = outUrl + (outUrl.indexOf('?') > -1 ? '&' : '?') + 't=' + Date.now();
                                    }
                                }
                                img.src = outUrl;
                            })();
                            console.log('Attempting to load image from:', img.src);
                        } else {
                            console.error('Success response, but image_url missing:', response);
                            $('.preview-error')
                                .show()
                                .find('.error-message')
                                .text(response.data && response.data.message ? response.data.message : __('AI preview generated, but the result could not be retrieved.', 'woo-fitroom-preview'));
                        }
                    } else {
                        console.error('API Error Response:', response);
                        let errorMessage = WooFitroomPreview.i18n.error;
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        $('.preview-error')
                            .show()
                            .find('.error-message')
                            .text(errorMessage);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', {xhr, status, error});
                    let errorText = WooFitroomPreview.i18n.error + ': ' + error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorText = xhr.responseJSON.data.message;
                    }
                    $('.preview-error')
                        .show()
                        .find('.error-message')
                        .text(errorText);
                },
                complete: function() {
                    // Re-enable the submit button and restore its text
                    submitButton.prop('disabled', false).text(WooFitroomPreview.i18n.success);
                    $('.loading-indicator').hide();
                }
            });
        });

        // Handle download button click
        $(document).on('click', '.download-preview', function() {
            const $button = $(this);
            const $modal = $button.closest('.woo-fitroom-preview-modal');
            const imageUrl = $(this).attr('data-url');
            
            console.log('Download button clicked');
            console.log('Modal ID:', $modal.attr('id'));
            console.log('Download URL:', imageUrl);
            console.log('Button data-url attr:', $(this).attr('data-url'));
            
            if (imageUrl) {
                fetch(imageUrl)
                .then(response => response.blob())
                .then(blob => {
                     const link = document.createElement('a');
                     link.href = URL.createObjectURL(blob);
                     link.download = 'tryontool-preview.jpg';
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     URL.revokeObjectURL(link.href);
                })
                .catch(err => {
                     console.error('Error downloading image:', err);
                     const link = document.createElement('a');
                     link.href = imageUrl;
                     link.download = 'tryontool-preview.jpg';
                     link.target = '_blank';
                     document.body.appendChild(link);
                     link.click();
                     document.body.removeChild(link);
                     showErrorPopup('Try On Tool Preview is not available. Please check your settings.');
                });
            }
        });

        // Handle "Try Full Outfit" button click
        $(document).on('click', '.try-full-outfit-btn', function() {
            const $button = $(this);
            const $modal = $button.closest('.woo-fitroom-preview-modal');
            const productId = $modal.find('input[name="product_id"]').val();
            const productImageUrl = $modal.find('input[name="product_image_url"]').val();
            // Try to get user image from multiple sources
            let userImageUrl = $modal.find('.preview-result').attr('data-user-image-url');
            
            // If not found in data attribute, try form fields
            if (!userImageUrl) {
                userImageUrl = $modal.find('input[name="saved_user_image_url"]').val();
            }
            
            // If still not found, try the specific field ID
            if (!userImageUrl) {
                userImageUrl = $modal.find('#saved_user_image_url_top_bottom').val();
            }
            
            // If still not found, try the bottom modal field ID
            if (!userImageUrl) {
                userImageUrl = $modal.find('#saved_user_image_url_bottom').val();
            }
            
            console.log('Try Full Outfit - User Image URL:', userImageUrl);
            console.log('Try Full Outfit - Modal:', $modal.attr('id'));
            console.log('Try Full Outfit - Data attribute:', $modal.find('.preview-result').attr('data-user-image-url'));
            console.log('Try Full Outfit - Form fields:', $modal.find('input[name="saved_user_image_url"]').length);
            
            if (!userImageUrl) {
                alert('No user image selected. Please select an image first.');
                return;
            }
            
            // Check if we're in the top-bottom modal or bottom modal (where Try Full Outfit button is available)
            if (!$modal.is('#woo-fitroom-preview-modal-top-bottom') && !$modal.is('#woo-fitroom-preview-modal-bottom')) {
                alert('This feature is only available for Top Only or Bottom Only previews.');
                return;
            }
            
            // Hide the current preview and show loading
            $modal.find('.preview-result').hide();
            $modal.find('.preview-error').hide();
            
            // Disable button and show loading
            $modal.find('.try-full-outfit-btn').prop('disabled', true).text('Full Outfit');
            
            // Show loading indicator in the current modal
            if (!$modal.find('.loading-indicator').length) {
                $modal.find('.preview-result').after('<div class="loading-indicator"><p>Generating full outfit… This may take up to 60 seconds.</p><div class="spinner"></div></div>');
            }
            $modal.find('.loading-indicator').show();
            
            // Prepare form data for full outfit generation
            const formData = new FormData();
            formData.append('action', 'woo_fitroom_generate_preview');
            formData.append('nonce', WooFitroomPreview.nonce);
            formData.append('product_id', productId);
            formData.append('product_image_url', productImageUrl);
            formData.append('saved_user_image_url', userImageUrl);
            formData.append('preview_type', 'full-outfit');
            
            // Send AJAX request for full outfit
            $.ajax({
                url: WooFitroomPreview.ajaxurl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-WP-Nonce': WooFitroomPreview.nonce
                },
                success: function(response) {
                    console.log('Full Outfit API Response:', response);
                    if (response.success && response.data && response.data.image_url) {
                        // Show preview result and hide form in the current modal
                        $modal.find('.preview-result').show();
                        
                        // Hide form elements based on which modal we're in
                        if ($modal.is('#woo-fitroom-preview-modal-top-bottom')) {
                            $modal.find('#my_uploads_strip_top_bottom').hide();
                            $modal.find('#user_image_dropzone_top_bottom').closest('.form-field').hide();
                            $modal.find('#woo-fitroom-preview-form-top-bottom .form-submit').hide();
                        } else if ($modal.is('#woo-fitroom-preview-modal-bottom')) {
                            $modal.find('#my_uploads_strip_bottom').hide();
                            $modal.find('#user_image_dropzone_bottom').closest('.form-field').hide();
                            $modal.find('#woo-fitroom-preview-form-bottom .form-submit').hide();
                        }
                        
                        // Mark consents as completed for this session and future interactions
                        const modalEl = $modal[0];
                        if (modalEl) {
                            modalEl.setAttribute('data-require-consent', '0');
                            modalEl.setAttribute('data-show-extra-consents', '0');
                            modalEl.classList.add('preview-mode');
                        }
                        
                        // Permanently hide all consent checkboxes after first success based on which modal we're in
                        if ($modal.is('#woo-fitroom-preview-modal-top-bottom')) {
                            $('#user_consent_top_bottom').closest('.form-field').hide();
                            $('#terms_consent_top_bottom, #refund_consent_top_bottom').each(function(){
                                $(this).closest('.form-field').hide();
                            });
                            $('#user_consent_top_bottom').prop('required', false);
                            $('#terms_consent_top_bottom, #refund_consent_top_bottom').prop('required', false);
                        } else if ($modal.is('#woo-fitroom-preview-modal-bottom')) {
                            $('#user_consent_bottom').closest('.form-field').hide();
                            $('#terms_consent_bottom, #refund_consent_bottom').each(function(){
                                $(this).closest('.form-field').hide();
                            });
                            $('#user_consent_bottom').prop('required', false);
                            $('#terms_consent_bottom, #refund_consent_bottom').prop('required', false);
                        }
                        
                        // Update the preview image with full outfit result
                        const img = new Image();
                        img.onload = function() {
                            $modal.find('.preview-image').html(img);
                            $modal.find('.preview-error').hide();
                            
                            // Update download button with new URL
                            const $downloadBtn = $modal.find('.download-preview');
                            $downloadBtn.attr('data-url', response.data.image_url);
                            
                            // Verify the update worked
                            setTimeout(() => {
                                const verifyUrl = $downloadBtn.data('url');
                                if (verifyUrl !== response.data.image_url) {
                                    // Force update using jQuery data method
                                    $downloadBtn.data('url', response.data.image_url);
                                }
                            }, 100);
                        };
                        img.onerror = function() {
                            console.error('Failed to load full outfit image from: ' + response.data.image_url);
                            $modal.find('.preview-error')
                                .show()
                                .find('.error-message')
                                .text('Full outfit image could not be loaded. Please try again.');
                        };
                        img.src = response.data.image_url;
                        
                        // Show the new preview result
                        $modal.find('.preview-result').show();
                        
                        // Update preview title for full outfit
                        $modal.find('.preview-title').text('Your Full Outfit Try On Preview');
                        
                        // Hide the "Try Full Outfit" button since we now have full outfit
                        $modal.find('.try-full-outfit-btn').hide();
                    } else {
                        console.error('Full Outfit API Error Response:', response);
                        let errorMessage = 'Failed to generate full outfit preview.';
                        if (response.data && response.data.message) {
                            errorMessage = response.data.message;
                        }
                        $modal.find('.preview-error')
                            .show()
                            .find('.error-message')
                            .text(errorMessage);
                        
                        // Hide loading indicator on error
                        $modal.find('.loading-indicator').hide();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Full Outfit AJAX Error:', {xhr, status, error});
                    let errorText = 'Error generating full outfit: ' + error;
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorText = xhr.responseJSON.data.message;
                    }
                    $modal.find('.preview-error')
                        .show()
                        .find('.error-message')
                        .text(errorText);
                    
                    // Hide loading indicator on error
                    $modal.find('.loading-indicator').hide();
                },
                complete: function() {
                    // Re-enable the button
                    $modal.find('.try-full-outfit-btn').prop('disabled', false).text('Try Full Outfit');
                    $modal.find('.loading-indicator').hide();
                }
            });
        });

        // Handle share button click
        $(document).on('click', '.share-preview', function() {
            const $button = $(this);
            const $modal = $button.closest('.woo-fitroom-preview-modal');
            const imageUrl = $modal.find('.download-preview').attr('data-url');
            
            console.log('Share button clicked');
            console.log('Modal ID:', $modal.attr('id'));
            console.log('Share URL:', imageUrl);
            console.log('Download button data-url attr:', $modal.find('.download-preview').attr('data-url'));
            
            if (!imageUrl) { return; }

            const shareTitle = __('My Try-On Tool preview', 'woo-fitroom-preview');
            const shareText  = __('Check out my virtual try-on preview!', 'woo-fitroom-preview');

            // Prefer the Web Share API (with files if supported), otherwise fall back to platform URLs
            if (navigator.share) {
                // Try link share first (most platforms block direct file shares from blobs)
                navigator.share({ title: shareTitle, text: shareText, url: imageUrl }).catch(()=>{});
                return;
            }

            // Fallback panel: open a simple chooser with anchors
            const encUrl  = encodeURIComponent(imageUrl);
            const encText = encodeURIComponent(shareText);
            const links = [
                { name: 'WhatsApp', href: 'https://api.whatsapp.com/send?text=' + encText + '%20' + encUrl },
                { name: 'Twitter',  href: 'https://twitter.com/intent/tweet?text=' + encText + '&url=' + encUrl },
                { name: 'Facebook', href: 'https://www.facebook.com/sharer/sharer.php?u=' + encUrl },
                { name: 'LinkedIn', href: 'https://www.linkedin.com/sharing/share-offsite/?url=' + encUrl },
                // Instagram and TikTok do not support direct web URL shares; prompt user instead
            ];

            // Open the first in a new tab to keep UX minimal; advanced UI can be added later
            window.open(links[0].href, '_blank');
        });

        // Regenerate: hide preview and show the initial upload UI again
        $(document).on('click', '.regenerate-preview', function(){
            $('.preview-result').hide();
            
            // Hide "Try Full Outfit" button when regenerating (will be shown again after new preview)
            $('.try-full-outfit-btn').hide();
            
            // Check which modal we're in and reset accordingly
            const isTopBottomModal = $(this).closest('#woo-fitroom-preview-modal-top-bottom').length > 0;
            const isBottomModal = $(this).closest('#woo-fitroom-preview-modal-bottom').length > 0;
            
            if (isTopBottomModal) {
                // Reset top/bottom modal
                $('#user_image_top_bottom').val('');
                $('#woo-fitroom-preview-form-top-bottom .form-submit').show();
                $('#woo-fitroom-preview-form-top-bottom .form-submit .button').css('display','inline-block').prop('disabled', false);
                $('#user_image_dropzone_top_bottom').closest('.form-field').show();
                if (WooFitroomPreview.user_id && WooFitroomPreview.user_id !== 0) {
                    $('#my_uploads_strip_top_bottom').show();
                }
                // Do NOT show consents again after first completion (per requirement)
                $('#user_consent_top_bottom').closest('.form-field').hide();
                $('#terms_consent_top_bottom, #refund_consent_top_bottom').each(function(){
                    $(this).closest('.form-field').hide();
                });
                const modalEl2 = document.getElementById('woo-fitroom-preview-modal-top-bottom');
                if (modalEl2) { modalEl2.classList.remove('preview-mode'); }
                
                // Reset preview title
                $('.preview-title').text('Your Try On Preview');
            } else if (isBottomModal) {
                // Reset bottom modal
                $('#user_image_bottom').val('');
                $('#woo-fitroom-preview-form-bottom .form-submit').show();
                $('#woo-fitroom-preview-form-bottom .form-submit .button').css('display','inline-block').prop('disabled', false);
                $('#user_image_dropzone_bottom').closest('.form-field').show();
                if (WooFitroomPreview.user_id && WooFitroomPreview.user_id !== 0) {
                    $('#my_uploads_strip_bottom').show();
                }
                // Do NOT show consents again after first completion (per requirement)
                $('#user_consent_bottom').closest('.form-field').hide();
                $('#terms_consent_bottom, #refund_consent_bottom').each(function(){
                    $(this).closest('.form-field').hide();
                });
                const modalEl3 = document.getElementById('woo-fitroom-preview-modal-bottom');
                if (modalEl3) { modalEl3.classList.remove('preview-mode'); }
                
                // Reset preview title
                $('.preview-title').text('Your Try On Preview');
            } else {
                // Reset full outfit modal
                $('#user_image').val('');
                $('#woo-fitroom-preview-form .form-submit').show();
                // Also ensure the generate button is visible/enabled
                $('#woo-fitroom-preview-form .form-submit .button').css('display','inline-block').prop('disabled', false);
                $('#user_image_dropzone').closest('.form-field').show();
                if (WooFitroomPreview.user_id && WooFitroomPreview.user_id !== 0) {
                    $('#my_uploads_strip').show();
                }
                // Do NOT show consents again after first completion (per requirement)
                $('#user_consent').closest('.form-field').hide();
                $('#terms_consent, #refund_consent').each(function(){
                    $(this).closest('.form-field').hide();
                });
                const modalEl2 = document.getElementById('woo-fitroom-preview-modal');
                if (modalEl2) { modalEl2.classList.remove('preview-mode'); }
                
                // Reset preview title
                $('#woo-fitroom-preview-modal .preview-title').text('Your Try On Preview');
            }
        });

        // Handle save to account button click
        $(document).on('click', '.save-preview', function() {
            const $button = $(this);
            const $modal = $button.closest('.woo-fitroom-preview-modal');
            const imageUrl = $modal.find('.download-preview').data('url');
            if (!imageUrl) return;

            const data = {
                action: 'woo_fitroom_save_preview', // Reverted action
                nonce: WooFitroomPreview.nonce,
                image_url: imageUrl,
                product_id: $('#product_id').val()
            };

            const saveButton = $(this);
            saveButton.text( __( 'Saving…', 'woo-fitroom-preview' ) ).prop('disabled', true);
            
            $.post(WooFitroomPreview.ajaxurl, data, function(response) {
                if (response.success) {
                    showSuccessPopup(response.data.message || 'Preview saved successfully!');
                } else {
                    showErrorPopup(response.data.message || 'Failed to save preview.');
                }
            }).fail(function() {
                showErrorPopup('Error communicating with server to save preview.');
            }).always(function() {
                 saveButton.text('Save to My Account').prop('disabled', false);
            });
        });

        // Add click handler to inspect button data
        $('.woo-fitroom-preview-button').on('click', function() {
            console.log('Button Data Product ID:', $(this).data('product-id'));
            console.log('Button Data Product Image:', $(this).data('product-image'));

             if(typeof window.checkModalImage === 'function'){
                 setTimeout(window.checkModalImage, 500);
             }
        });

        // Check credits and update button state
        if (WooFitroomPreview.credits <= 0) {
            $('.woo-fitroom-preview-button').prop('disabled', true);
            $('#woo-fitroom-preview-modal .preview-error .error-message').text(WooFitroomPreview.i18n.out_of_credits).show();
        } else {
            $('.woo-fitroom-preview-button').prop('disabled', false);
        }

        $('#woo-fitroom-preview-modal .preview-error').hide();

        try {
            console.log('WooTryOnTool Debug: jQuery is available');
            console.log('WooTryOnTool Debug: Document ready fired');
            console.log('WooTryOnTool Debug: Button elements found:', $('.woo-tryontool-preview-button').length);
            console.log('Debug Image Element:', imgElement);
            console.log('Image src:', imgElement ? imgElement.src : 'No image element');
            console.log('Image displayed:', imgElement ? window.getComputedStyle(imgElement).display : 'No image element');
            console.log('Image width:', imgElement ? imgElement.offsetWidth : 'No image element');
            console.log('Image complete:', imgElement ? imgElement.complete : 'No image element');
            if (imgElement) {
                console.log('Forced image reload with:', imgElement.src);
            }
            console.log('Button Data Product ID:', $(this).data('product-id'));
            console.log('Button Data Product Image:', $(this).data('product-image'));
            console.log('WooTryOnTool Debug: Window load event fired');
            console.log('WooTryOnTool Debug: Button found after page load');
            console.log('WooTryOnTool Debug: Button NOT found after page load');
            console.log('WooTryOnTool Debug: Modal found after page load');
            console.log('WooTryOnTool Debug: Modal NOT found after page load');
        } catch (e) {
            console.warn('WooTryOnTool Debug block skipped due to error:', e.message);
        }

        // Hide legacy button (replaced by inline strip)
        $('#view-uploaded-images').hide();

        // Use delegated handler since modal can be injected after scripts
        $(document).on('change input', '#user_image, #user_image_top_bottom, #user_image_bottom', function(){
            const id = $(this).attr('id');
            const isTopBottom = id === 'user_image_top_bottom';
            const isBottom = id === 'user_image_bottom';
            const savedUrlField = isTopBottom ? '#saved_user_image_url_top_bottom' : (isBottom ? '#saved_user_image_url_bottom' : '#saved_user_image_url');
            const selectedPhotoField = isTopBottom ? '#selected-photo-name-top-bottom' : (isBottom ? '#selected-photo-name-bottom' : '#selected-photo-name');
            
            $(savedUrlField).val('');
            // Reinstate the required attribute when a new file is chosen
            if (this.files && this.files.length) {
                $(this).prop('required', true);
                // Show chosen file name
                $(selectedPhotoField).text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + this.files[0].name );
 
                // Replace dz-icon with a live thumbnail of the selected file
                try {
                    const file = this.files[0];
                    const dropzone = jQuery(this).closest('.upload-dropzone');
                    const dzInner = dropzone.find('.dz-inner').first();
                    const dzIcon  = dzInner.find('.dz-icon').first();
                    const dzThumb = dzInner.find('.dz-thumb').first();
                    if (file && dzIcon.length && dzThumb.length) {
                        // Immediately swap visibility for faster feedback
                        dzIcon.attr('style','display:none !important');
                        dzThumb.attr('style','display:block !important');
                        dropzone.addClass('show-thumb');

                        const reader = new FileReader();
                        reader.onload = function(e){
                            const dataUrl = e.target.result;
                            dzThumb.attr('src', dataUrl).attr('style','display:block !important');
                            dzIcon.attr('aria-hidden','true').removeClass('has-thumb').css({'background':'','background-image':'','background-size':'','background-position':''});
                        };
                        reader.readAsDataURL(file);
                    }
                } catch(e) { /* no-op */ }
            } else {
                $(selectedPhotoField).text('');
                // No file selected: revert dz-icon to default icon
                const dropzone = jQuery(this).closest('.upload-dropzone');
                const dzInner = dropzone.find('.dz-inner').first();
                const dzIcon  = dzInner.find('.dz-icon').first();
                const dzThumb = dzInner.find('.dz-thumb').first();
                dzThumb.attr('src','').css('display','none');
                dropzone.removeClass('show-thumb');
                dzIcon.css('display','').attr('aria-hidden','false');
            }
        });

        // Allow dropping of non-standard image extensions by trusting the server-side conversion
        // (No client-side filtering beyond the accept attribute)

        /* ----------------------------------------------------------
           Enhance upload UX: click "Browse" triggers file dialog
           and dragover styling for dropzone container
           -------------------------------------------------------- */
        $(document).on('click', '.upload-dropzone .dz-browse', function(e){
            e.preventDefault();
            const dropzone = $(this).closest('.upload-dropzone');
            const input = dropzone.find('#user_image, #user_image_top_bottom, #user_image_bottom');
            input.trigger('click');
        });
        // Delegate dropzone interactions so they work after modal injection
        $(document).on('click', '#user_image_dropzone, #user_image_dropzone_top_bottom, #user_image_dropzone_bottom', function(e){
            const input = this.querySelector('#user_image') || this.querySelector('#user_image_top_bottom') || this.querySelector('#user_image_bottom');
            if (!input || e.target === input) return;
            if (!$(e.target).closest('.dz-browse').length) {
                $(input).trigger('click');
            }
        });
        $(document).on('keypress', '#user_image_dropzone, #user_image_dropzone_top_bottom, #user_image_dropzone_bottom', function(e){ 
            if (e.key === 'Enter' || e.key === ' ') { 
                e.preventDefault(); 
                const input = this.querySelector('#user_image') || this.querySelector('#user_image_top_bottom') || this.querySelector('#user_image_bottom');
                $(input).trigger('click'); 
            } 
        });
        $(document).on('dragover dragenter', '#user_image_dropzone, #user_image_dropzone_top_bottom, #user_image_dropzone_bottom', function(e){ e.preventDefault(); e.stopPropagation(); $(this).addClass('dragover'); });
        $(document).on('dragleave dragend drop', '#user_image_dropzone, #user_image_dropzone_top_bottom, #user_image_dropzone_bottom', function(e){ e.preventDefault(); e.stopPropagation(); $(this).removeClass('dragover'); });
        $(document).on('drop', '#user_image_dropzone, #user_image_dropzone_top_bottom, #user_image_dropzone_bottom', function(e){
            const files = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
            if (files && files.length) {
                const input = this.querySelector('#user_image') || this.querySelector('#user_image_top_bottom') || this.querySelector('#user_image_bottom');
                if (input) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    input.files = dataTransfer.files;
                    $(input).trigger('change');
                }
            }
        });

        $(document).on('click', '#view-uploaded-images', function(e){
            e.preventDefault();
            openUploadedImagesModal();
        });

        // Extract object key from Wasabi URL irrespective of bucket / host
        function getWasabiKeyFromUrl(url){
            try {
                const u       = new URL(url);
                const bits    = u.pathname.replace(/^\/+/, '').split('/'); // [bucket, ...rest]
                bits.shift();                                               // drop bucket
                return bits.join('/');                                      // rest is key
            } catch(e){
                // Fallback: strip protocol+host then first segment (bucket)
                return url.replace(/^https?:\/\/[^/]+\//, '').replace(/^[^/]+\//, '');
            }
        }

        // Helper: build proxy URL (only if Wasabi host detected)
        function getProxyImageUrl(inputUrl){
            if(!/wasabisys\.com/i.test(inputUrl)){ return inputUrl; }
            const key = getWasabiKeyFromUrl(inputUrl);
            if (!key) { return inputUrl; }
            const base = '/wp-json/woo-tryontool/v1/wasabi-image?key=' + encodeURIComponent(key);
            // Cache-bust thumbs so broken cached responses aren't reused
            return base + '&t=' + Date.now();
        }
    });

})(jQuery);
