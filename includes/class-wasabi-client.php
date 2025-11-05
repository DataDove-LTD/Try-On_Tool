<?php
/**
 * Lightweight Wasabi Client - NO AWS SDK REQUIRED
 * Uses server-side API instead
 *
 * @package Try-On Tool
 * @copyright 2025 DataDove LTD
 * @license GPL-2.0-only
 */
if (!class_exists('WooFITROOM_Wasabi')) {
    class WooFITROOM_Wasabi {

    /**
     * Server-side API endpoint for Wasabi operations
     */
    private static $server_api = 'https://tryontool.com/wp-json/tryontool/v1';
    
    /**
     * Upload image to Wasabi via server API
     */
    public static function upload($user_id, $local_file) {
        error_log('=== Wasabi Upload START (Direct File) ===');
        error_log('User ID: ' . $user_id);
        error_log('Local file: ' . $local_file);
        error_log('File exists: ' . (file_exists($local_file) ? 'YES' : 'NO'));
        
        $license_key = get_option('WOO_FITROOM_license_key');
        if (!$license_key) {
            error_log('Wasabi Upload Error: License key missing');
            return false;
        }

        error_log('License key found: YES');

        if (!file_exists($local_file)) {
            error_log('Wasabi Upload Error: File not found at ' . $local_file);
            return false;
        }

        $file_size = filesize($local_file);
        error_log('File size: ' . $file_size . ' bytes');
        
        // Get user email from client
        $user = get_userdata($user_id);
        $user_email = $user ? $user->user_email : 'unknown';
        error_log('User email: ' . $user_email);
        
        // Send file using cURL directly (WordPress wp_remote_post doesn't handle multipart well)
        $boundary = '----WebKitFormBoundary' . uniqid();
        
        $post_data = "--$boundary\r\n";
        $post_data .= 'Content-Disposition: form-data; name="license_key"' . "\r\n\r\n";
        $post_data .= $license_key . "\r\n";
        
        $post_data .= "--$boundary\r\n";
        $post_data .= 'Content-Disposition: form-data; name="user_id"' . "\r\n\r\n";
        $post_data .= $user_id . "\r\n";
        
        $post_data .= "--$boundary\r\n";
        $post_data .= 'Content-Disposition: form-data; name="user_email"' . "\r\n\r\n";
        $post_data .= $user_email . "\r\n";
        
        $post_data .= "--$boundary\r\n";
        $post_data .= 'Content-Disposition: form-data; name="filename"' . "\r\n\r\n";
        $post_data .= basename($local_file) . "\r\n";
        
        $post_data .= "--$boundary\r\n";
        $post_data .= 'Content-Disposition: form-data; name="site_url"' . "\r\n\r\n";
        $post_data .= home_url() . "\r\n";
        
        $post_data .= "--$boundary\r\n";
        $post_data .= 'Content-Disposition: form-data; name="image_file"; filename="' . basename($local_file) . '"' . "\r\n";
        $post_data .= 'Content-Type: image/jpeg' . "\r\n\r\n";
        
        $image_content = file_get_contents($local_file);
        $post_data .= $image_content;
        $post_data .= "\r\n--$boundary--\r\n";
        
        error_log('Sending direct file upload to: ' . self::$server_api . '/wasabi/upload');
        error_log('Post data size: ' . strlen($post_data) . ' bytes');
        
        $ch = curl_init(self::$server_api . '/wasabi/upload');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: multipart/form-data; boundary=' . $boundary
        ));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response_body = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($curl_error) {
            error_log('Wasabi Upload cURL Error: ' . $curl_error);
            return false;
        }
        
        // Log response for debugging
        $response_code = $http_code;
        error_log('Wasabi Upload Response Code: ' . $response_code);
        error_log('Wasabi Upload Response Body Length: ' . strlen($response_body) . ' bytes');
        error_log('Wasabi Upload Response Body: ' . substr($response_body, 0, 1000));
        
        if ($response_code !== 200) {
            error_log('Wasabi Upload Failed: Server returned HTTP ' . $response_code);
            error_log('=== Wasabi Upload END (HTTP ERROR) ===');
            return false;
        }
        
        $body = json_decode($response_body, true);
        
        if (!is_array($body)) {
            error_log('Wasabi Upload Failed: Response body is not valid JSON');
            error_log('Response body: ' . $response_body);
            error_log('=== Wasabi Upload END (JSON ERROR) ===');
            return false;
        }
        
        error_log('Wasabi Upload: Parsed JSON body structure: ' . print_r(array_keys($body), true));
        
        // Check for WP_Error format (code, message, data)
        if (isset($body['code'])) {
            error_log('Wasabi Upload: Server returned WP_Error');
            error_log('Error code: ' . $body['code']);
            error_log('Error message: ' . ($body['message'] ?? 'N/A'));
            error_log('Error data: ' . print_r($body['data'] ?? array(), true));
            error_log('=== Wasabi Upload END (SERVER ERROR) ===');
            return false;
        }
        
        if (isset($body['success']) && $body['success'] && isset($body['url'])) {
            // Use the key from server response (not generate our own!)
            $key = $body['key'] ?? self::object_key($user_id, basename($local_file));
            
            error_log('Wasabi Upload: Using key from server: ' . $key);
            
            // Store for deletion tracking
            $transient_key = 'WOO_FITROOM_image_deletion_' . md5($key);
            set_transient($transient_key, array(
                'user_id' => $user_id,
                'key' => $key,
                'timestamp' => time(),
                'upload_time' => time(),
                'image_url' => $body['url'],
            ), 0);
            
            error_log('Wasabi Upload Success: ' . $body['url']);
            error_log('=== Wasabi Upload END (SUCCESS) ===');
            return $body['url'];
        }
        
        error_log('Wasabi Upload Failed: No URL in response');
        error_log('Response data: ' . print_r($body, true));
        error_log('=== Wasabi Upload END (NO URL) ===');
        return false;
    }

    /**
     * List user images via server API
     */
    public static function list_user_images($user_id) {
        error_log('=== Wasabi List START ===');
        error_log('User ID: ' . $user_id);
        
        $license_key = get_option('WOO_FITROOM_license_key');
        if (!$license_key) {
            error_log('Wasabi List: No license key found');
            return [];
        }
        
        error_log('License key found: YES');

        // Get user email
        $user = get_userdata($user_id);
        $user_email = $user ? $user->user_email : 'unknown';
        
        error_log('User email: ' . $user_email);
        error_log('Site URL: ' . home_url());
        
        $request_data = array(
            'license_key' => $license_key,
            'user_id' => $user_id,
            'user_email' => $user_email,
            'site_url' => home_url(),
        );
        
        error_log('Sending request to: ' . self::$server_api . '/wasabi/list');
        error_log('Request data: ' . json_encode($request_data));
        
        $response = wp_remote_post(self::$server_api . '/wasabi/list', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($request_data),
            'timeout' => 20,
        ));
        
        if (is_wp_error($response)) {
            error_log('Wasabi List Error: ' . $response->get_error_message());
            error_log('Error code: ' . $response->get_error_code());
            error_log('=== Wasabi List END (ERROR) ===');
            return [];
        }

        // Log response details
        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        
        error_log('Wasabi List Response Code: ' . $response_code);
        error_log('Wasabi List Response Body (first 500 chars): ' . substr($response_body, 0, 500));
        
        $body = json_decode($response_body, true);
        
        if (isset($body['images'])) {
            error_log('Wasabi List: Found ' . count($body['images']) . ' images');
            if (count($body['images']) > 0) {
                error_log('First image URL: ' . $body['images'][0]);
            }
            error_log('=== Wasabi List END (SUCCESS) ===');
            return $body['images'];
        } else {
            error_log('Wasabi List: No images key in response');
            error_log('Full body: ' . print_r($body, true));
            error_log('=== Wasabi List END (NO IMAGES KEY) ===');
            return [];
        }
    }
    
    /**
     * Delete image via server API
     */
    public static function delete($key) {
        $license_key = get_option('WOO_FITROOM_license_key');
        if (!$license_key) {
            return false;
        }

        $response = wp_remote_post(self::$server_api . '/wasabi/delete', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'license_key' => $license_key,
                'key' => $key,
                'site_url' => home_url(),
            )),
            'timeout' => 20,
        ));
        
        if (is_wp_error($response)) {
            error_log('Wasabi Delete Error: ' . $response->get_error_message());
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['success']) && $body['success'];
    }
    
    /**
     * Get detailed image information including creation dates
     * 
     * @param int $user_id The user ID
     * @param string|null $site_url Optional site URL to use for filtering. If not provided, uses home_url()
     */
    public static function get_user_images_with_metadata($user_id, $site_url = null) {
        $license_key = get_option('WOO_FITROOM_license_key');
        if (!$license_key) {
            return [];
        }

        $user = get_userdata($user_id);
        $user_email = $user ? $user->user_email : 'unknown';
        
        // Use provided site_url or fall back to home_url()
        // This ensures we query for images with the correct Wasabi prefix
        $request_site_url = $site_url !== null ? $site_url : home_url();
        
        $request_data = array(
            'license_key' => $license_key,
            'user_id' => $user_id,
            'user_email' => $user_email,
            'site_url' => $request_site_url,
            'include_metadata' => true,
        );
        
        error_log('Wasabi Get Metadata: Querying for user_id=' . $user_id . ', site_url=' . $request_site_url);
        
        $response = wp_remote_post(self::$server_api . '/wasabi/list', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($request_data),
            'timeout' => 20,
        ));
        
        if (is_wp_error($response)) {
            error_log('Wasabi Get Metadata Error: ' . $response->get_error_message());
            return [];
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);
        $body = json_decode($response_body, true);
        
        // Log response for debugging
        error_log('Wasabi Get Metadata Response: Code=' . $response_code . ', Has images_with_metadata=' . (isset($body['images_with_metadata']) ? 'yes(' . count($body['images_with_metadata']) . ')' : 'no') . ', Has images=' . (isset($body['images']) ? 'yes(' . count($body['images']) . ')' : 'no'));
        
        if ($response_code === 200 && isset($body['images_with_metadata'])) {
            // Server returned metadata with sizes
            $images = $body['images_with_metadata'];
            
            // Ensure all images have size field
            $total_size = 0;
            foreach ($images as &$image) {
                // Check if size field exists (could be 'size' or 'Size')
                $size_value = isset($image['size']) ? $image['size'] : (isset($image['Size']) ? $image['Size'] : 0);
                
                if (empty($size_value) || intval($size_value) <= 0) {
                    // Try to get size via HEAD request if URL is available
                    $url = isset($image['url']) ? $image['url'] : (isset($image['Url']) ? $image['Url'] : '');
                    if (!empty($url)) {
                        $size_value = self::get_image_size_from_url($url);
                    }
                }
                
                // Ensure size is integer
                $image['size'] = intval($size_value);
                $total_size += $image['size'];
            }
            unset($image);
            
            error_log('Wasabi Get Metadata: Returned ' . count($images) . ' images with metadata, total size: ' . round($total_size / (1024 * 1024), 2) . ' MB');
            return $images;
        } elseif ($response_code === 200 && isset($body['images'])) {
            // Fallback: convert simple URLs to metadata format and fetch sizes
            $images = [];
            foreach ($body['images'] as $index => $url) {
                $size = 0;
                // If it's an array with metadata, use it
                if (is_array($url) && isset($url['size'])) {
                    $size = intval($url['size']);
                    $actual_url = isset($url['url']) ? $url['url'] : (isset($url['key']) ? self::public_url($url['key']) : '');
                    $key = isset($url['key']) ? $url['key'] : self::extract_key_from_url($actual_url);
                    $last_modified = isset($url['last_modified']) ? $url['last_modified'] : date('Y-m-d H:i:s');
                    $created_date = isset($url['created_date']) ? $url['created_date'] : date('Y-m-d');
                } else {
                    // It's just a URL string - fetch size via HEAD request
                    $actual_url = is_array($url) ? ($url['url'] ?? '') : $url;
                    $size = self::get_image_size_from_url($actual_url);
                    $key = self::extract_key_from_url($actual_url);
                    $last_modified = date('Y-m-d H:i:s');
                    $created_date = date('Y-m-d');
                }
                
                $images[] = [
                    'key' => $key,
                    'url' => is_array($url) ? ($url['url'] ?? '') : $url,
                    'size' => $size,
                    'last_modified' => $last_modified,
                    'created_date' => $created_date,
                    'storage_class' => 'STANDARD'
                ];
            }
            return $images;
        }
        
        error_log('Wasabi Get Metadata: Unexpected response format. Code: ' . $response_code . ', Body keys: ' . (isset($body) && is_array($body) ? implode(', ', array_keys($body)) : 'N/A'));
        return [];
    }

    /**
     * Get all users' image data for analytics (site-specific)
     * 
     * @param bool $force_refresh Whether to bypass cache and get fresh data
     */
    public static function get_all_users_images_data_for_site($force_refresh = false) {
        $all_images = [];
        $current_site_url = parse_url(home_url(), PHP_URL_HOST);
        $current_full_url = rtrim(home_url(), '/'); // Use full URL to match upload format
        
        // If forcing refresh, clear option cache to get latest data
        if ($force_refresh) {
            wp_cache_delete('WOO_FITROOM_consents', 'options');
        }
        
        // Get site-specific consents
        $consents = get_option('WOO_FITROOM_consents', array());
        $site_specific_consents = array();
        
        $skipped_legacy = 0;
        $skipped_other_sites = 0;
        
        foreach ($consents as $user_id => $consent_data) {
            // Check if this consent record belongs to the current site
            // Compare hostnames (stored in consent) with current site hostname
            $consent_host = isset($consent_data['site_url']) ? $consent_data['site_url'] : null;
            
            if ($consent_host === $current_site_url) {
                // Explicit match - include this record
                $site_specific_consents[$user_id] = $consent_data;
            } elseif (!isset($consent_data['site_url'])) {
                // Legacy records without site_url - DO NOT include them
                // They could belong to any site, so we can't safely assume they belong to current site
                // Only include if we can verify they have images with current site's prefix
                $skipped_legacy++;
                error_log('Wasabi Client: Skipping legacy consent record for user_id=' . $user_id . ' (no site_url - cannot verify site ownership)');
            } else {
                // Record has site_url but it doesn't match current site - skip it
                $skipped_other_sites++;
                error_log('Wasabi Client: Skipping consent record for user_id=' . $user_id . ' (site_url=' . $consent_host . ' does not match current site=' . $current_site_url . ')');
            }
        }
        
        error_log('Wasabi Client: Filtered ' . count($site_specific_consents) . ' site-specific consents out of ' . count($consents) . ' total for site: ' . $current_site_url . ' (skipped ' . $skipped_legacy . ' legacy, ' . $skipped_other_sites . ' other sites)');
        
        foreach ($site_specific_consents as $user_id => $consent_data) {
            if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                // Use home_url() directly to match upload format exactly
                // This ensures the Wasabi prefix matches what was used during upload
                $query_site_url = $current_full_url;
                
                error_log('Wasabi Client: Fetching images for user_id=' . $user_id . ', using site_url=' . $query_site_url);
                $user_images = self::get_user_images_with_metadata($user_id, $query_site_url);
                if (!empty($user_images)) {
                    $all_images[$user_id] = $user_images;
                    error_log('Wasabi Client: Found ' . count($user_images) . ' images for user_id=' . $user_id);
                } else {
                    error_log('Wasabi Client: No images found for user_id=' . $user_id . ' (this may be normal if user has no images)');
                }
            }
        }
        
        $total_images = 0;
        foreach ($all_images as $user_images) {
            $total_images += count($user_images);
        }
        
        error_log('Wasabi Client: Retrieved ' . $total_images . ' total images from ' . count($all_images) . ' users for site: ' . $current_site_url);
        return $all_images;
    }

    /**
     * Get site-wide image summary (count and total bytes) by listing under host prefix
     *
     * @param bool $force_refresh Unused (kept for signature consistency)
     * @return array{count:int,total_bytes:int}
     */
    public static function get_site_images_summary($force_refresh = false) {
        $license_key = get_option('WOO_FITROOM_license_key');
        if (!$license_key) {
            return array('count' => 0, 'total_bytes' => 0);
        }

        $request_data = array(
            'license_key' => $license_key,
            'site_url' => rtrim(home_url(), '/'),
            'user_id' => 0, // signal site-wide listing on server
            'include_metadata' => true,
        );

        error_log('Wasabi Site Summary: Requesting site-wide list for ' . $request_data['site_url']);

        $response = wp_remote_post(self::$server_api . '/wasabi/list', array(
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode($request_data),
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            error_log('Wasabi Site Summary Error: ' . $response->get_error_message());
            return array('count' => 0, 'total_bytes' => 0);
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($response_code === 200 && is_array($body)) {
            if (isset($body['images_with_metadata']) && is_array($body['images_with_metadata'])) {
                $images = $body['images_with_metadata'];
                $total_size = isset($body['total_size']) ? intval($body['total_size']) : 0;
                if ($total_size <= 0) {
                    foreach ($images as $img) {
                        if (isset($img['size'])) {
                            $total_size += intval($img['size']);
                        }
                    }
                }
                error_log('Wasabi Site Summary: images=' . count($images) . ', total_bytes=' . $total_size);
                return array('count' => count($images), 'total_bytes' => $total_size);
            }

            if (isset($body['images']) && is_array($body['images'])) {
                // No metadata/size available
                error_log('Wasabi Site Summary: images (no metadata)=' . count($body['images']));
                return array('count' => count($body['images']), 'total_bytes' => 0);
            }
        }

        error_log('Wasabi Site Summary: Unexpected response; returning zeros');
        return array('count' => 0, 'total_bytes' => 0);
    }

    /**
     * Get total count of users who have actually generated previews (have images in Wasabi) - site-specific
     * 
     * @param bool $force_refresh Whether to bypass cache and get fresh data
     */
    public static function get_total_users_with_previews_for_site($force_refresh = false) {
        $current_site_url = parse_url(home_url(), PHP_URL_HOST);
        $current_full_url = rtrim(home_url(), '/'); // Use full URL to match upload format
        
        // If forcing refresh, clear option cache to get latest data
        if ($force_refresh) {
            wp_cache_delete('WOO_FITROOM_consents', 'options');
        }
        
        // Get site-specific consents
        $consents = get_option('WOO_FITROOM_consents', array());
        $site_specific_consents = array();
        
        $skipped_legacy = 0;
        $skipped_other_sites = 0;
        
        foreach ($consents as $user_id => $consent_data) {
            // Check if this consent record belongs to the current site
            // Compare hostnames (stored in consent) with current site hostname
            $consent_host = isset($consent_data['site_url']) ? $consent_data['site_url'] : null;
            
            if ($consent_host === $current_site_url) {
                // Explicit match - include this record
                $site_specific_consents[$user_id] = $consent_data;
            } elseif (!isset($consent_data['site_url'])) {
                // Legacy records without site_url - DO NOT include them
                // They could belong to any site, so we can't safely assume they belong to current site
                $skipped_legacy++;
            } else {
                // Record has site_url but it doesn't match current site - skip it
                $skipped_other_sites++;
            }
        }
        
        error_log('Wasabi Client: Filtered ' . count($site_specific_consents) . ' site-specific consents for user count (skipped ' . $skipped_legacy . ' legacy, ' . $skipped_other_sites . ' other sites)');
        
        $users_with_previews = 0;
        
        foreach ($site_specific_consents as $user_id => $consent_data) {
            if (isset($consent_data['consent_timestamp']) && !empty($consent_data['consent_timestamp'])) {
                // Use home_url() directly to match upload format exactly
                // This ensures the Wasabi prefix matches what was used during upload
                $query_site_url = $current_full_url;
                
                $user_images = self::get_user_images_with_metadata($user_id, $query_site_url);
                if (!empty($user_images)) {
                    $users_with_previews++;
                }
            }
        }
        
        error_log('Wasabi Client: Found ' . $users_with_previews . ' users with actual previews in Wasabi for site: ' . $current_site_url);
        return $users_with_previews;
    }
    
    /**
     * Helper: Extract key from Wasabi URL
     */
    private static function extract_key_from_url($url) {
        // Extract key from Wasabi URL format: https://s3.eu-west-1.wasabisys.com/bucket/key
        $parsed = parse_url($url);
        if (isset($parsed['path'])) {
            $path = ltrim($parsed['path'], '/');
            // Remove bucket name from path
            $parts = explode('/', $path, 2);
            return isset($parts[1]) ? $parts[1] : $parts[0];
        }
        return basename($url);
    }
    
    /**
     * Helper: Get image size from URL using HEAD request
     */
    private static function get_image_size_from_url($url) {
        if (empty($url)) {
            return 0;
        }
        
        // Use HEAD request to get Content-Length header
        // Use shorter timeout for faster processing
        $response = wp_remote_head($url, array(
            'timeout' => 5,
            'redirection' => 2,
            'sslverify' => true,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url()
        ));
        
        if (is_wp_error($response)) {
            // Don't log every failure to avoid log spam
            // error_log('Wasabi Get Image Size: Failed to fetch size for ' . $url . ' - ' . $response->get_error_message());
            return 0;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return 0;
        }
        
        // Try both lowercase and case-sensitive header names
        $content_length = wp_remote_retrieve_header($response, 'content-length');
        if (!$content_length) {
            $content_length = wp_remote_retrieve_header($response, 'Content-Length');
        }
        
        if ($content_length && intval($content_length) > 0) {
            return intval($content_length);
        }
        
        return 0;
    }
    
    /**
     * Helper: Generate public URL from key
     */
    private static function public_url($key) {
        // Generate Wasabi public URL from key
        $bucket = 'tryontool-user-images'; // Default bucket
        return 'https://s3.eu-west-1.wasabisys.com/' . $bucket . '/' . $key;
    }
    
    /**
     * Helper: Generate object key (for tracking)
     */
    private static function object_key($user_id, $file_name) {
        $site_url = parse_url(home_url(), PHP_URL_HOST);
        $user = get_userdata($user_id);
        $email = $user ? $user->user_email : 'unknown';
        $base = pathinfo($file_name, PATHINFO_FILENAME);
        return $site_url . '/' . $user_id . '-' . $email . '/' . time() . '_' . $base . '.jpg';
    }
    
    /**
     * Helper: Get bucket name (deprecated - now server-managed)
     */
    public static function bucket() {
        return 'server-managed';
    }
    
    /**
     * Helper: Get client (deprecated - no longer uses AWS SDK)
     */
    public static function client() {
        // Deprecated - now uses HTTP API
        return null;
    }
    
    /**
     * Fetch credentials (for backward compatibility)
     */
    private static function fetch_credentials() {
        // No longer needed - server handles credentials
        return array(
            'bucket' => 'server-managed',
            'access_key' => '',
            'secret_key' => '',
        );
    }
    }
}


