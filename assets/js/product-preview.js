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

    $(document).ready(function() {
        // Declare variable in outer scope to avoid ReferenceError in later debug logs
        let imgElement = null;

        console.log('WooTryOnTool Preview: JavaScript initialized');
        console.log('WooTryOnTool Preview Buttons found:', $('.woo-fitroom-preview-button').length);
        
        // Handle preview button click
        $('.woo-fitroom-preview-button').on('click', function(e) {
            console.log('WooTryOnTool Preview: Button clicked');
            e.preventDefault();
            
            const productId = $(this).data('product-id');
            const productImage = $(this).data('product-image');
            
            console.log('Product Image URL:', productImage);
            
            $('#product_id').val(productId);
            $('#product_image_url').val(productImage);
            
            $('#woo-fitroom-preview-modal').addClass('is-open').show();

            // Try to detect theme primary color and apply to modal as CSS var --tryon-primary
            try {
                // 1st: Try global theme CSS variable if exposed by WP theme.json
                let primary = getComputedStyle(document.body).getPropertyValue('--wp--preset--color--primary').trim();

                // 2nd: Try secondary token
                if(!primary){
                    primary = getComputedStyle(document.body).getPropertyValue('--wp--preset--color--secondary').trim();
                }

                // 3rd: Fallback to link colour detected from a dummy anchor
                if(!primary){
                    const testEl = document.createElement('a');
                    testEl.href = '#';
                    document.body.appendChild(testEl);
                    primary = getComputedStyle(testEl).color;
                    document.body.removeChild(testEl);
                }
                // 4th: hard default if everything else fails
                if (!primary || !/rgb|#/.test(primary)) {
                    primary = '#F4BD7A';
                }
                const modalRoot = document.getElementById('woo-fitroom-preview-modal');
                if (modalRoot && primary) {
                    // Only override if not already customised
                    const existing = modalRoot.style.getPropertyValue('--tryon-primary');
                    if (!existing) {
                        modalRoot.style.setProperty('--tryon-primary', primary.trim());
                    }
                }
            } catch (_) {
                const modalRoot = document.getElementById('woo-fitroom-preview-modal');
                if (modalRoot) { modalRoot.style.setProperty('--tryon-primary', '#FB5607'); }
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
                                    list.append(`<div class="thumb" data-url="${u}"><img src="${prox}" data-url="${u}" alt="uploaded"/><button class="delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`);
                                }
                            });
                            // Pre-select first image
                            $('#saved_user_image_url').val(images[0]);
                            list.find('.thumb').first().addClass('selected');
                        } else {
                            $('#my_uploads_strip').hide();
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
                    $('.product-image-preview').append('<p class="error">' + __( 'Failed to load product image', 'woo-fitroom-preview' ) + '</p>');
                };
            } else {
                console.error('Image element #preview-product-image or product image URL not found');
                if (imgElement) imgElement.style.display = 'none';
                 $('.product-image-preview .error').remove();
                 $('.product-image-preview').append('<p class="error">' + __( 'No product image available', 'woo-fitroom-preview' ) + '</p>');
            }
        });

        // Handle modal close
        $('.woo-fitroom-preview-modal .close').on('click', function() {
            $('#woo-fitroom-preview-modal').removeClass('is-open').hide();
            $('.product-image-preview .error').remove(); 
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
                        res.data.images.forEach(u=>{ html += `<div class="img-item"><img src="${getProxyImageUrl(u)}" data-url="${u}"/><button class="delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`; });
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
                            $('#saved_user_image_url').val(selectedImageUrl);
                            $('#user_image').val('').prop('required', false);
                            $('#selected-photo-name').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + selectedImageUrl.split('/').pop() );
                            // Close modal completely
                            $(modal).removeClass('is-open').hide();
                            alert( __( 'Photo selected! Click Generate Preview.', 'woo-fitroom-preview' ) );
                        });
                    } else {
                        alert( __( 'No saved images.', 'woo-fitroom-preview' ) );
                    }
                },
                error: () => alert( __( 'Error fetching images', 'woo-fitroom-preview' ) )
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
            alert( __( 'Photo selected! Click Generate Preview.', 'woo-fitroom-preview' ) );

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

        // Handle click on the "More" tile -> open existing modal loader
        $(document).on('click', '#my_uploads_more', function(){
            openUploadedImagesModal();
        });

        // Handle delete button clicks
        $(document).on('click', '.delete-btn', function(e){
            e.stopPropagation(); // Prevent triggering image selection
            const imageUrl = $(this).data('url');
            showDeleteConfirmation(imageUrl);
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

        // Delete image function
        function deleteImage(imageUrl) {
            console.log('Delete Image: Attempting to delete URL:', imageUrl);
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
                    if (response.success) {
                        // Remove the image from both inline strip and modal grid
                        $(`.thumb[data-url="${imageUrl}"]`).remove();
                        $(`.img-item img[data-url="${imageUrl}"]`).closest('.img-item').remove();
                        
                        // Refresh the inline strip if needed
                        if (WooFitroomPreview.user_id && WooFitroomPreview.user_id !== 0) {
                            refreshInlineStrip();
                        }
                        
                        // Show success message
                        alert(__('Image deleted successfully', 'woo-fitroom-preview'));
                    } else {
                        console.error('Delete Image: Server error:', response.data.message);
                        alert(response.data.message || __('Failed to delete image', 'woo-fitroom-preview'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Delete Image: AJAX error:', {xhr, status, error});
                    alert(__('Error communicating with server', 'woo-fitroom-preview'));
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
                                list.append(`<div class="thumb" data-url="${u}"><img src="${prox}" data-url="${u}" alt="uploaded"/><button class="delete-btn" data-url="${u}" title="${__('Delete image', 'woo-fitroom-preview')}"></button></div>`);
                            }
                        });
                        // Pre-select first image if available
                        if (images.length > 0) {
                            $('#saved_user_image_url').val(images[0]);
                            list.find('.thumb').first().addClass('selected');
                        }
                    } else {
                        $('#my_uploads_strip').hide();
                    }
                }
            });
        }

        // Handle form submission
        $('#woo-fitroom-preview-form').on('submit', function(e) {
            e.preventDefault();
            
            // Determine consent requirements from modal attributes
            const modal = document.getElementById('woo-fitroom-preview-modal');
            const needConsent = modal && modal.getAttribute('data-require-consent') === '1';
            const showExtra = modal && modal.getAttribute('data-show-extra-consents') === '1';
            
            // Client-side consent validation (prevents generic server error)
            if (needConsent && !$('#user_consent').prop('checked')) {
                $('.preview-error').show().find('.error-message').text(__('Please provide consent before generating a preview.', 'woo-fitroom-preview'));
                $('#user_consent').closest('.form-field').show();
                return;
            }
            if (showExtra) {
                const hasTerms = $('#terms_consent').length ? $('#terms_consent').prop('checked') : true;
                const hasRefund = $('#refund_consent').length ? $('#refund_consent').prop('checked') : true;
                if (!hasTerms || !hasRefund) {
                    $('.preview-error').show().find('.error-message').text(__('Please agree to the Terms/Privacy and Refund Policy.', 'woo-fitroom-preview'));
                    $('#terms_consent, #refund_consent').each(function(){ $(this).closest('.form-field').show(); });
                    return;
                }
            }
            
            // Hide previous results and errors, and hide the upload UI while generating
            $('.preview-result').hide();
            $('.preview-error').hide();
            // hide the initial content area (uploads strip + dropzone + button)
            $('#my_uploads_strip').hide();
            $('#user_image_dropzone').closest('.form-field').hide();
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
                                $('.download-preview').attr('data-url', response.data.image_url);
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

        // Handle download button click
        $(document).on('click', '.download-preview', function() {
            const imageUrl = $(this).data('url');
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
                     alert('Try On Tool Preview is not available. Please check your settings.');
                });
            }
        });

        // Handle share button click
        $(document).on('click', '.share-preview', function() {
            const imageUrl = $('.download-preview').data('url');
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
            // Reset selected file/state but keep saved user image selection
            $('#user_image').val('');
            $('#woo-fitroom-preview-form .form-submit').show();
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
        });

        // Handle save to account button click
        $(document).on('click', '.save-preview', function() {
            const imageUrl = $('.download-preview').data('url');
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
                    alert(response.data.message || 'Preview saved successfully!');
                } else {
                    alert(response.data.message || 'Failed to save preview.');
                }
            }).fail(function() {
                alert('Error communicating with server to save preview.');
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
        $(document).on('change input', '#user_image', function(){
            $('#saved_user_image_url').val('');
            // Reinstate the required attribute when a new file is chosen
            if (this.files && this.files.length) {
                $(this).prop('required', true);
                // Show chosen file name
                $('#selected-photo-name').text( __( 'Selected photo:', 'woo-fitroom-preview' ) + ' ' + this.files[0].name );
 
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
                $('#selected-photo-name').text('');
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
            $('#user_image').trigger('click');
        });
        // Delegate dropzone interactions so they work after modal injection
        $(document).on('click', '#user_image_dropzone', function(e){
            const input = this.querySelector('#user_image');
            if (!input || e.target === input) return;
            if (!$(e.target).closest('.dz-browse').length) {
                $('#user_image').trigger('click');
            }
        });
        $(document).on('keypress', '#user_image_dropzone', function(e){ if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $('#user_image').trigger('click'); } });
        $(document).on('dragover dragenter', '#user_image_dropzone', function(e){ e.preventDefault(); e.stopPropagation(); $(this).addClass('dragover'); });
        $(document).on('dragleave dragend drop', '#user_image_dropzone', function(e){ e.preventDefault(); e.stopPropagation(); $(this).removeClass('dragover'); });
        $(document).on('drop', '#user_image_dropzone', function(e){
            const files = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer.files : null;
            if (files && files.length) {
                const input = this.querySelector('#user_image');
                if (input) {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(files[0]);
                    input.files = dataTransfer.files;
                    $('#user_image').trigger('change');
                }
            }
        });
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
        // Cache-bust thumbs so broken cached responses aren’t reused
        return base + '&t=' + Date.now();
    }

})(jQuery);
