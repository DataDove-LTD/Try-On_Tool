# Changelog

All notable changes to the Try-On Tool plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.3.0] - 2025-11-05
### Added
- **Try Top Only Feature**: Allow users to try only the top part of outfits
- **Try Bottom Only Feature**: Allow users to try only the bottom part of outfits
- **Try Full Outfit Feature**: Button to apply complete outfit on user's image
- **Automatic Category Assignment**: Categories automatically assigned as top/bottom/full outfit based on product type
- **Dynamic Button Display**: Buttons automatically display based on product category assignment
- **Try Full Outfit Button**: Displayed in preview popups for top-only and bottom-only previews
- **Category-Based Button Logic**: System automatically shows the correct button based on product category
- **Automatic Category Detection**: OpenAI integration for intelligent category assignment with keyword fallback
- **Analytics Dashboard**: Comprehensive analytics dashboard in admin settings showing site-specific metrics
- **Analytics – Images & Storage**: Settings analytics now display site-specific Images Stored and Storage Used sourced directly from Wasabi (site URL–scoped)
- **Analytics – Total Users**: Display total users with consent for the current site
- **Analytics – Active Users (10D)**: Track and display users who have uploaded images or generated previews in the last 10 days (site-specific)
- **Analytics – Preview Trends Chart**: Interactive Chart.js line chart showing preview generation trends over the last 30 days
- **Analytics – Real-time Refresh**: Refresh button to manually update analytics data and clear all caches
- **Analytics – Top Categories**: Display top product categories by preview count (site-specific)
- **Analytics – Storage Calculation**: Real-time storage usage calculation from Wasabi metadata with fallback to HEAD requests

### Changed
- **Button Text Standardization**: All try-on buttons now display "Try It On" for consistency
- **Category Assignment System**: Automatic assignment system replaces manual category management
- **Button Visibility Logic**: Product-specific buttons shown based on category assignment (top/bottom/full)
- **Theme Color Integration**: All buttons now inherit theme colors automatically for unified appearance
- **Analytics – Total Previews**: Counts all preview generations, including reusing images from the gallery (not just new uploads)
- **Analytics – Site-Specific Filtering**: All analytics data now strictly filtered by site URL to ensure accurate per-site metrics
- **Analytics – Cache Management**: Comprehensive cache clearing system (transients and object cache) for fresh data on refresh
- **Analytics – Active Users Calculation**: Active users now determined by actual Wasabi image upload dates (created_date/last_modified) instead of user meta
- **Analytics – Top Categories**: Temporarily disabled list rendering; shows "Coming Soon" only

### Fixed
- **Analytics – Debug Buttons**: Ensured jQuery/$ and ajaxurl availability so Test Tracking, Clear Cache, Check DB/Consents/Active Users work reliably
- **Analytics – CSS Conflicts**: Fixed CSS styling conflicts between analytics dashboard and categories table in admin settings
- **Analytics – Chart Display**: Fixed Chart.js graph not rendering due to timing issues and tab visibility detection
- **Analytics – Data Loading**: Fixed missing analytics data (dashes) by correcting AJAX response handling and Chart.js initialization
- **Analytics – Preview Popup Scrolling**: Fixed scrollbar not appearing when preview popup content height increases
- **Analytics – Storage Data**: Fixed storage usage not displaying correctly by implementing Wasabi metadata retrieval with size information
- **Analytics – Site-Specific Images**: Fixed images from other sites being included by implementing strict site URL filtering in consent records
- **Analytics – Active Users Count**: Fixed active users always showing 0 by switching from user meta to Wasabi image metadata for accurate date tracking

### Technical
- **Category Assignment Database**: New custom table for storing category assignments
- **REST API Endpoints**: Category management endpoints for assignment retrieval and updates
- **OpenAI Integration**: Intelligent category detection using OpenAI API with keyword fallback
- **Product Category Detection**: Automatic detection of product category from WooCommerce product data
- **Conditional Button Rendering**: PHP-based conditional rendering for product-specific buttons
- **Multi-Modal System**: Three separate modals for full outfit, top-only, and bottom-only previews
- **Preview Type Handling**: Backend logic for top/bottom/full outfit API calls
- **Automatic Assignment Hooks**: Multiple WordPress hooks for automatic category assignment on various events
- **Analytics – Wasabi Summary**: Server Wasabi list supports site-wide (host-prefixed) listing with pagination; client added `get_site_images_summary()` to compute image count and total bytes for analytics
- **Analytics – Wasabi Metadata API**: Enhanced server-side API to return image metadata (size, created_date, last_modified, storage_class) when `include_metadata` parameter is provided
- **Analytics – Chart.js Integration**: Integrated Chart.js library for interactive trend visualization with proper initialization and resize handling
- **Analytics – Site URL Tracking**: Added `site_url` field to consent records to enable strict site-specific data filtering
- **Analytics – Force Refresh Parameter**: Implemented `$force_refresh` parameter throughout analytics data retrieval chain to bypass all caches
- **Analytics – Date Comparison Logic**: Optimized active user date comparison using direct string comparison (Y-m-d format) for better performance
- **Analytics – Image Size Retrieval**: Added fallback mechanism using HTTP HEAD requests to retrieve image sizes when metadata is missing
- **Analytics – CSS Scoping**: Scoped analytics CSS rules to specific dashboard containers to prevent conflicts with other admin pages

## [1.2.3] - 2025-09-29
### Added
- Custom Button Text Feature: Allow users to customize "Try It On" button text (max 15 characters)
- Excel Export for Consent Records: Export all user consent records to CSV/Excel format
- "Delete All Images" Feature: Bulk deletion of all user uploaded images with confirmation popup
- Character Counter: Real-time character counter with color-coded feedback for button text
- Consolidated Consent Logic: Single consent date calculation based on latest of all required consents
- Migration System: Automatic migration of existing consent records to consolidated format
- Enhanced Error Messages: Specific error messages for different deletion failure scenarios
- Loading States: Visual feedback during image deletion operations
- Security Enhancements: Nonce protection and capability checks for export functionality
- UI Improvements: Dynamic button visibility and better user experience

### Changed
- Admin Dashboard Styling: TryOnTool orange color theme (#FF6E0E) replacing purplish gradients
- Radio Button Styling: Orange color for radio button ::before pseudo-elements
- Consent Display: Simplified to show single "Consent Given" date instead of separate dates
- Image Deletion Behavior: Images only removed from UI if successfully deleted from Wasabi
- Error Handling: Enhanced error handling for all image deletion scenarios
- CSS Specificity: Enhanced styling with better theme integration
- User Experience: Improved confirmation popups and clear success/error messaging

### Fixed
- Data Integrity: Images remain in UI until successfully deleted from storage
- Connection Error Handling: Specific error messages for connection issues
- Authentication Error Handling: Clear messaging for credential and permission issues
- Button State Management: Prevents multiple clicks during processing
- Mobile Responsiveness: Enhanced mobile experience for all new features
- Accessibility: Proper form labels and keyboard navigation maintained

### Technical
- AJAX Integration: Seamless export functionality with new tab opening
- Wasabi Integration: Proper S3 deletion with error handling
- WordPress Settings API: Proper setting registration and sanitization
- Error Logging: Comprehensive logging for debugging
- Performance: Efficient data processing for large datasets
- Security: Proper validation and sanitization for all new features

## [1.2.2] - 2025-09-24
### Added
- Enhanced theme compatibility system for Astra, OceanWP, GeneratePress, and Storefront
- OceanWP comprehensive styling detection (padding, border, typography, shadows)
- GeneratePress customizer color forcing to override WooCommerce defaults
- Improved admin settings text with "Try-On Tool defined" terminology
- Enhanced CSS specificity system for better theme integration

### Changed
- Admin settings text updated for better clarity:
  - "Use Try-On Tool defined color or your custom color"
  - "Use Try-On Tool defined border radius (50px) or your own custom"
- CSS variable priority system improved (custom → theme → default)
- JavaScript function priority updated (admin settings over theme detection)
- Removed !important declarations that interfered with dynamic theme detection

### Fixed
- OceanWP primary color detection now uses correct CSS variable (--ocean-primary)
- GeneratePress customizer colors now properly override WooCommerce purple defaults
- CSS specificity issues resolved for better theme integration
- Theme detection algorithms optimized for supported themes
- **Plugin Update Conflict**: Fixed issue where manual upload of v1.2.2 over v1.2.1 caused WordPress to treat them as separate plugins
- **WordPress Update Integration**: Added proper plugin headers and hooks for WordPress to recognize updates properly
- **Update Screen**: WordPress now shows the standard update screen with version comparison and "Replace current with uploaded" option
- **Fatal Error Prevention**: Added class and function existence checks to prevent conflicts during manual updates
- **Multiple Version Detection**: Added admin notice when multiple plugin versions are detected
- **Update Notifications**: Added success notice when plugin is successfully updated
- **Fatal Error Prevention**: Added comprehensive constant and class existence checks
- **Cleanup Mechanism**: Added automatic cleanup of old version conflicts during activation
- **Cleanup Script**: Created `cleanup-old-version.php` for manual conflict resolution
- **Syntax Error Fix**: Fixed duplicate function declaration causing fatal error
- **Version Folder Support**: Added support for version numbers in folder names
- **Plugin Identifier System**: Implemented identifier-based plugin detection

### Technical
- Enhanced OceanWP detection to extract comprehensive styling from Add to Cart button
- Added GeneratePress-specific detection method with priority system
- Improved CSS variable fallback hierarchy
- Streamlined theme compatibility to focus on key themes (Astra, GeneratePress, OceanWP, Storefront)

## [1.2.1] - 2025-09-15
### Added
- Image deletion functionality with confirmation popup
- Delete buttons on uploaded image thumbnails (both inline strip and modal grid)
- HPOS (High-Performance Order Storage) compatibility for WooCommerce
- Mobile-optimized 3-column grid layout for image thumbnails
- Comprehensive theme color inheritance for upload dropzone

### Changed
- Grid layout changed from 5 columns to 3 columns for better mobile experience
- Thumbnail images now properly fill their containers with centered positioning
- Upload dropzone border color now properly inherits theme primary color
- Admin interface text updated: "Try-On Tool Preview" → "Try-On Tool" in sidebar and settings
- Mobile thumbnails are now larger and more touch-friendly
- Image thumbnails display inside upload dropzone when selected

### Fixed
- Images no longer overflow their container borders
- Upload dropzone no longer gets compressed on mobile devices
- White space issues in image thumbnails resolved
- External CSS margin conflicts resolved with proper overrides
- Delete button positioning fixed to be inside container boundaries
- HPOS compatibility issues resolved for modern WooCommerce installations

### Technical
- Added HPOS-compatible order meta functions
- Implemented Wasabi S3 image deletion with proper error handling
- Enhanced mobile responsive design with breakpoints for 600px and 480px
- Added comprehensive CSS overrides for theme color inheritance
- Improved image positioning with object-fit and object-position properties

## [1.2.0] - 2025-09-08
### Added
- Inline "My Uploads" thumbnail strip with "View More" tile on product modal
- Drag-and-drop upload box with theme-aware styling
- Regenerate icon button in preview view; matches theme color
- Centered modals on screen for better UX
- Social share action in preview modal (next to Regenerate)
- Change the Tabs text on my account sidebar Plans to Active Plans & Subscriptions to Orders

## [1.2.0]
### Changed
- All uploaded/saved image URLs normalized through proxy for reliability
- Accepts more image formats (HEIC/HEIF/AVIF/BMP/TIFF/etc.) with server-side JPEG conversion
- Hide consent checkboxes after first consent; do not re-show on regenerate
- Title hidden in preview mode; restores on regenerate
- Subscription switch proration notice refined: shows base price, negative credit adjustment from remaining yearly value, and the expected next renewal total; applies across upgrade and downgrade scenarios (yearly → yearly/monthly).
- Preview modal footer icons now have a visible default color to guarantee visibility; simplified CSS (removed external mask dependency) and swapped assets to `images/repeat-2.svg` (regenerate) and `images/share-2.svg` (share).

### Fixed
- Selecting a saved image no longer fails due to URL or CORS issues
- Gallery modal shows 5 images per row; thumbnails sharper

### Added
- Initial release of Try-On Tool plugin
- WooCommerce integration for virtual try-on functionality
- Admin settings page with license validation
- Frontend try-on button and modal interface
- Wasabi S3 integration for image storage
- GDPR-compliant image handling and user consent
- Free trial system (3 credits for new users)
- Credit-based usage system
- User role and permission controls
- Automatic image cleanup for inactive users
- GPL-2.0 license compliance
- Comprehensive documentation and installation guides 