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
 * WooFitroomPreview_API_Handler
 *
 * WordPress wrapper around the TryOnTool REST API.
 * Docs: handled via FITROOM_RELAY_ENDPOINT
 *
 * © 2025 Your Company — MIT / GPL-compatible
 */

if (!class_exists('WooFitroomPreview_API_Handler')) {
    class WooFitroomPreview_API_Handler {

	/* ─── CONSTANTS ────────────────────────────────────────────────── */
	// Direct external endpoints removed – we use the relay constant

	/**
	 * Send a virtual-try-on request and poll until the output is ready.
	 *
	 * @param string $user_image_path   Local path to the model image (uploaded by user).
	 * @param string $garment_image_url Public URL to the garment image.
	 * @param string $category          Garment category: auto | tops | bottoms | one-pieces.
	 * @param array  $options           Extra API fields (mode, seed, segmentation_free …).
	 *
	 * @return array|WP_Error [ 'image_url' => 'https://…' ] on success.
	 */
	public function generate_preview( $user_image_path, $garment_image_url, $category = 'auto', array $options = array() ) {

		/* 1.  Move model image into uploads & read binary  */
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'wp_uploads_error', $upload_dir['error'] );
		}

		$dest_path = $upload_dir['path'] . '/fitroom_' . time() . '_' . wp_basename( $user_image_path );
		if ( ! copy( $user_image_path, $dest_path ) ) {
			return new WP_Error( 'file_copy_error', __( 'Could not copy image', 'woo-fitroom-preview' ) );
		}

		/* 2.  Base-64 encode both images (SiteGround WAF fix) */
		$model_img_data   = $this->get_simple_image_data( $dest_path );
		if ( is_wp_error( $model_img_data ) ) { return $model_img_data; }
		$model_b64        = 'data:image/jpeg;base64,' . base64_encode( $model_img_data );
		
		$garment_img_data = $this->get_simple_remote_image_data( $garment_image_url );
		if ( is_wp_error( $garment_img_data ) ) { return $garment_img_data; }
		$garment_b64      = 'data:image/jpeg;base64,' . base64_encode( $garment_img_data );

		/* 3.  Send to RELAY  */
		$license_key = get_option( 'WOO_FITROOM_license_key' );
		$client_site_url = home_url();

		// --- >>> ADDED: Log the retrieved key and the JSON body <<< ---
		error_log("TryOnTool Client Plugin: Retrieved license key from options: " . ($license_key ?: 'EMPTY') );
		$request_body_array = array(
			'model_image'   => $model_b64,
			'garment_image' => $garment_b64,
			'category'      => $category ?: 'auto',
			'license_key'     => $license_key,
			'client_site_url' => $client_site_url,
		);
		$json_body_to_send = wp_json_encode( $request_body_array );
		error_log("TryOnTool Client Plugin: Sending JSON body to relay: " . $json_body_to_send);
		// --- >>> END ADDED LOGGING <<< ---

		$response = wp_remote_post(
			FITROOM_RELAY_ENDPOINT, // Reverted Constant name
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $json_body_to_send, // Use the logged variable
				'timeout' => 120,
			)
		);

		// --- 4. Handle Relay Response ---
		if ( is_wp_error( $response ) ) {
			// LOG THE WP_Error object before returning
			error_log("TryOnTool Client Plugin: WP_Error contacting relay: " . $response->get_error_message() . ' | Data: ' . print_r($response->get_error_data(), true));
			$error_message = __('Try-On Tool API Error: ', 'woo-fitroom-preview') . $response->get_error_message();
			return new WP_Error('relay_wp_error', __('Error communicating with the license server.', 'woo-fitroom-preview'), $error_message);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// LOG THE RAW RESPONSE received from the relay
		error_log("TryOnTool Client Plugin: Received relay response - Code: $code, Body: " . print_r($body, true));

		// Check for successful generation (200 OK from relay)
		if ( $code === 200 && ! empty( $body['image_url'] ) ) {
			$success_message = __('Try On Tool API Success: ', 'woo-fitroom-preview') . $body['image_url'];
			return array( 'image_url' => $body['image_url'] );
		}

		// Handle specific errors from the relay
		$error_message = __( 'An unknown error occurred on the license server.', 'woo-fitroom-preview' );
		$error_code = 'relay_unknown_error';

		// Check if the response body itself contains a structured WP_Error from the relay's REST response
		if (isset($body['code']) && isset($body['message']) && isset($body['data']['status']) ) {
			 $error_code = $body['code'];
			 $error_message = $body['message'];
			 // You could potentially use $body['data']['status'] here too if needed
		} elseif (isset($body['error'])) { // Simple error message string in response
			 $error_message = $body['error'];
		}

		// Provide user-friendly messages based on common codes from relay
		switch ($error_code) {
			case 'no_license_key':
			case 'invalid_key':
			case 'site_mismatch':
				$error_message = __('License key validation failed. Please check your key in the plugin settings.', 'woo-fitroom-preview');
				break;
			case 'license_inactive':
			case 'license_expired':
				 $error_message = __('Your license is inactive or expired. Please renew your plan.', 'woo-fitroom-preview');
				 break;
			case 'no_credits':
				$error_message = __('You have run out of preview credits. Please purchase more.', 'woo-fitroom-preview');
				break;
			// Map to fitroom errors
			case 'fitroom_create_failed':
			case 'fitroom_failed':
			case 'fitroom_create_error': 
			case 'fitroom_status_error': 
			case 'fitroom_no_output': 
				$error_message = __('The AI engine failed to process the images. Please try different images or contact support.', 'woo-fitroom-preview');
				break;
			case 'fitroom_timeout':
				 $error_message = __('The AI generation timed out. Please try again later.', 'woo-fitroom-preview');
				 break;
			case 'missing_images': 
			case 'missing_params': 
			case 'auth_required': 
				 $error_message = __('An internal error occurred processing the request. Missing required data.', 'woo-fitroom-preview');
					break;
			// Add more specific mappings as needed
		}

		 // Log the final resolved error before returning
		 error_log("TryOnTool Client Plugin: Final Relay Error - Code=$error_code, Message='$error_message', Original Body: " . print_r($body, true));
		return new WP_Error( $error_code, $error_message, $body ); // Return the original body for potential context
	}

	/**
	 * Send a top/bottom specific virtual-try-on request for selective replacement.
	 *
	 * @param string $user_image_path   Local path to the user image.
	 * @param string $garment_image_url Public URL to the product image.
	 * @param string $user_category     Category for user image: tops | bottoms.
	 * @param string $garment_category  Category for product image: tops | bottoms.
	 * @param array  $options           Extra API fields.
	 *
	 * @return array|WP_Error [ 'image_url' => 'https://…' ] on success.
	 */
	public function generate_preview_top_bottom( $user_image_path, $garment_image_url, $user_category, $garment_category, array $options = array() ) {

		/* 1.  Move model image into uploads & read binary  */
		$upload_dir = wp_upload_dir();
		if ( ! empty( $upload_dir['error'] ) ) {
			return new WP_Error( 'wp_uploads_error', $upload_dir['error'] );
		}

		$dest_path = $upload_dir['path'] . '/fitroom_' . time() . '_' . wp_basename( $user_image_path );
		if ( ! copy( $user_image_path, $dest_path ) ) {
			return new WP_Error( 'file_copy_error', __( 'Could not copy image', 'woo-fitroom-preview' ) );
		}

		/* 2.  Base-64 encode both images (SiteGround WAF fix) */
		$user_img_data   = $this->get_simple_image_data( $dest_path );
		if ( is_wp_error( $user_img_data ) ) { return $user_img_data; }
		$user_b64        = 'data:image/jpeg;base64,' . base64_encode( $user_img_data );
		
		$garment_img_data = $this->get_simple_remote_image_data( $garment_image_url );
		if ( is_wp_error( $garment_img_data ) ) { return $garment_img_data; }
		$garment_b64      = 'data:image/jpeg;base64,' . base64_encode( $garment_img_data );

		/* 3.  Send to RELAY with top/bottom specific parameters */
		$license_key = get_option( 'WOO_FITROOM_license_key' );
		$client_site_url = home_url();

		error_log("TryOnTool Client Plugin: Top/Bottom mode - User category: $user_category, Garment category: $garment_category");
		
		$request_body_array = array(
			'model_image'      => $user_b64,
			'garment_image'    => $garment_b64,
			'user_category'    => $user_category,    // Top, Bottom, or Full Outfit
			'garment_category' => $garment_category, // Top, Bottom, or Full Outfit
			'category'         => 'top-bottom',      // Special category for top-bottom mode
			'license_key'      => $license_key,
			'client_site_url'  => $client_site_url,
		);
		$json_body_to_send = wp_json_encode( $request_body_array );
		error_log("TryOnTool Client Plugin: Sending top/bottom JSON body to relay: " . $json_body_to_send);

		$response = wp_remote_post(
			FITROOM_RELAY_ENDPOINT,
			array(
				'method'  => 'POST',
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $json_body_to_send,
				'timeout' => 120,
			)
		);

		// --- 4. Handle Relay Response ---
		if ( is_wp_error( $response ) ) {
			error_log("TryOnTool Client Plugin: WP_Error contacting relay for top/bottom: " . $response->get_error_message());
			$error_message = __('Try-On Tool API Error: ', 'woo-fitroom-preview') . $response->get_error_message();
			return new WP_Error('relay_wp_error', __('Error communicating with the license server.', 'woo-fitroom-preview'), $error_message);
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		error_log("TryOnTool Client Plugin: Received top/bottom relay response - Code: $code, Body: " . print_r($body, true));

		// Check for successful generation (200 OK from relay)
		if ( $code === 200 && ! empty( $body['image_url'] ) ) {
			$success_message = __('Try On Tool Top/Bottom API Success: ', 'woo-fitroom-preview') . $body['image_url'];
			return array( 'image_url' => $body['image_url'] );
		}

		// Handle specific errors from the relay
		$error_message = __( 'An unknown error occurred on the license server.', 'woo-fitroom-preview' );
		$error_code = 'relay_unknown_error';

		// Check if the response body itself contains a structured WP_Error from the relay's REST response
		if (isset($body['code']) && isset($body['message']) && isset($body['data']['status']) ) {
			 $error_code = $body['code'];
			 $error_message = $body['message'];
		} elseif (isset($body['error'])) { // Simple error message string in response
			 $error_message = $body['error'];
		}

		// Provide user-friendly messages based on common codes from relay
		switch ($error_code) {
			case 'no_license_key':
			case 'invalid_key':
			case 'site_mismatch':
				$error_message = __('License key validation failed. Please check your key in the plugin settings.', 'woo-fitroom-preview');
				break;
			case 'license_inactive':
			case 'license_expired':
				 $error_message = __('Your license is inactive or expired. Please renew your plan.', 'woo-fitroom-preview');
				 break;
			case 'no_credits':
				$error_message = __('You have run out of preview credits. Please purchase more.', 'woo-fitroom-preview');
				break;
			// Map to fitroom errors
			case 'fitroom_create_failed':
			case 'fitroom_failed':
			case 'fitroom_create_error': 
			case 'fitroom_status_error': 
			case 'fitroom_no_output': 
				$error_message = __('The AI engine failed to process the images. Please try different images or contact support.', 'woo-fitroom-preview');
				break;
			case 'fitroom_timeout':
				 $error_message = __('The AI generation timed out. Please try again later.', 'woo-fitroom-preview');
				 break;
			case 'missing_images': 
			case 'missing_params': 
			case 'auth_required': 
				 $error_message = __('An internal error occurred processing the request. Missing required data.', 'woo-fitroom-preview');
					break;
		}

		 error_log("TryOnTool Client Plugin: Final Top/Bottom Relay Error - Code=$error_code, Message='$error_message', Original Body: " . print_r($body, true));
		return new WP_Error( $error_code, $error_message, $body );
	}

	/* ────────────────────────────────────────────────────────────────
	   OPTIONAL: helper functions preserved from your original file
	   (JPEG normalisation, remote image download, etc.). Keep them if
	   other plugin code still relies on them. Otherwise you can safely
	   delete to slim down the class.                                 */

	/**
	 * Load a local image, coercing to JPEG if necessary.
	 *
	 * @param string $file_path
	 * @return string|WP_Error Binary string or error.
	 */
	private function get_simple_image_data( $file_path ) {
		// Validate path
		if ( ! file_exists( $file_path ) ) {
			return new WP_Error( 'file_not_found', __( 'Model image file not found', 'woo-fitroom-preview' ) );
		}

		// Read binary
		$image_data = file_get_contents( $file_path );
		if ( $image_data === false ) {
			return new WP_Error( 'file_read_error', __( 'Cannot read model image file', 'woo-fitroom-preview' ) );
		}

		// Ensure JPEG format (backend prefers JPG)
		$image_type = @exif_imagetype( $file_path );
		if ( $image_type !== IMAGETYPE_JPEG ) {
			error_log( 'WooFashnai API: Converting non-JPEG model image to JPEG' );

			switch ( $image_type ) {
				case IMAGETYPE_GIF:
					$src_img = imagecreatefromgif( $file_path );
					break;
				case IMAGETYPE_PNG:
					$src_img = imagecreatefrompng( $file_path );
					break;
				case IMAGETYPE_WEBP:
					if ( function_exists( 'imagecreatefromwebp' ) ) {
						$src_img = imagecreatefromwebp( $file_path );
					} else {
						$src_img = false;
					}
					break;
				case false:
					// Formats like HEIC/HEIF/AVIF often report false here. Try Imagick, then generic decoder.
					if ( class_exists( 'Imagick' ) ) {
						try {
							$imagick = new \Imagick( $file_path );
							$imagick->setImageFormat( 'jpg' );
							$blob = $imagick->getImageBlob();
							$src_img = imagecreatefromstring( $blob );
							$imagick->clear();
							$imagick->destroy();
						} catch ( \Exception $e ) {
							$src_img = false;
						}
					}
					if ( ! $src_img ) {
						$raw = @file_get_contents( $file_path );
						if ( $raw !== false ) {
							$src_img = @imagecreatefromstring( $raw );
						}
					}
					break;
				default:
					$src_img = false;
					break;
			}

			if ( $src_img ) {
				$upload_dir = wp_upload_dir();
				$temp_file  = $upload_dir['basedir'] . '/woo-fitroom-temp/' . uniqid( 'model_' ) . '.jpg';
				// Ensure temp dir exists
				if ( ! file_exists( dirname( $temp_file ) ) ) {
					wp_mkdir_p( dirname( $temp_file ) );
				}

				// For PNG/WEBP preserve transparency by merging onto white
				if ( in_array( $image_type, array( IMAGETYPE_PNG, IMAGETYPE_WEBP ), true ) ) {
					$width  = imagesx( $src_img );
					$height = imagesy( $src_img );
					$bg     = imagecreatetruecolor( $width, $height );
					$white  = imagecolorallocate( $bg, 255, 255, 255 );
					imagefilledrectangle( $bg, 0, 0, $width, $height, $white );
					imagecopy( $bg, $src_img, 0, 0, 0, 0, $width, $height );
					imagejpeg( $bg, $temp_file, 90 );
					imagedestroy( $bg );
				} else {
					imagejpeg( $src_img, $temp_file, 90 );
				}

				imagedestroy( $src_img );

				if ( file_exists( $temp_file ) ) {
					$image_data = file_get_contents( $temp_file );
					@unlink( $temp_file );
				}
			}
		}

		return $image_data;
	}

	/**
	 * Download a remote image and return binary (JPEG-normalised) data.
	 *
	 * @param string $url
	 * @return string|WP_Error
	 */
    private function get_simple_remote_image_data($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 60, // Longer timeout for image downloads
            'sslverify' => false, // Try without SSL verification
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('remote_image_error', __('Cannot retrieve product image', 'woo-fitroom-preview'));
        }

        $image_data = wp_remote_retrieve_body($response);
        
        // Convert to JPEG if needed
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/woo-fitroom-temp'; 
        $temp_file = $temp_dir . '/' . md5($url) . '.tmp';
        
        // Ensure temp dir exists
        if ( ! file_exists( $temp_dir ) ) {
             wp_mkdir_p( $temp_dir );
        }
        
        file_put_contents($temp_file, $image_data);
        
        // Detect image type
        $image_type = @exif_imagetype($temp_file);

        /*
         * Some hosting stacks (older GD, missing WEBP support, files served with
         *  a query-string, etc.) cause exif_imagetype() to return FALSE even
         *  though the data is a valid image.  To avoid sending an unsupported
         *  format to the back-end we fall back to imagecreatefromstring()
         *  when the type is undetectable.
         */

        if ($image_type !== IMAGETYPE_JPEG) {
            error_log('WooFitroom API: Converting remote non-JPEG image (detected type ' . var_export($image_type, true) . ') to JPEG');

            $src_img = false;
            switch ($image_type) {
                case IMAGETYPE_GIF:
                    $src_img = imagecreatefromgif($temp_file);
                    break;
                case IMAGETYPE_PNG:
                    $src_img = imagecreatefrompng($temp_file);
                    break;
                case IMAGETYPE_WEBP:
                    if (function_exists('imagecreatefromwebp')) {
                        $src_img = imagecreatefromwebp($temp_file);
                    }
                    break;
                case false: // Unknown – last-ditch attempt
                    $raw = file_get_contents($temp_file);
                    if ($raw !== false) {
                        $src_img = imagecreatefromstring($raw);
                    }
                    break;
            }

            if ($src_img) {
                $jpeg_file = $temp_file . '.jpg';

                $width  = imagesx($src_img);
                $height = imagesy($src_img);
                $canvas = imagecreatetruecolor($width, $height);
                $white  = imagecolorallocate($canvas, 255, 255, 255);
                imagefilledrectangle($canvas, 0, 0, $width, $height, $white);
                imagecopy($canvas, $src_img, 0, 0, 0, 0, $width, $height);
                imagejpeg($canvas, $jpeg_file, 90);
                imagedestroy($canvas);
                imagedestroy($src_img);

                if (file_exists($jpeg_file)) {
                    $image_data = file_get_contents($jpeg_file);
                    @unlink($jpeg_file);
                }
            }
        }
        
        @unlink($temp_file); // Clean up original temp file
        
        return $image_data;
    }
    }
}
