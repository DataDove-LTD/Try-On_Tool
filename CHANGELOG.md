# Changelog

All notable changes to the Try-On Tool plugin will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.2.0] - 2025-09-10
### Added
- Inline "My Uploads" thumbnail strip with "View More" tile on product modal
- Drag-and-drop upload box with theme-aware styling
- Regenerate icon button in preview view; matches theme color
- Centered modals on screen for better UX
- Social share action in preview modal (next to Regenerate)
- Change the Tabs text on my account sidebar Plans to Active Plans & Subscriptions to Orders

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