# Changelog

All notable changes to the Try-On Tool plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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