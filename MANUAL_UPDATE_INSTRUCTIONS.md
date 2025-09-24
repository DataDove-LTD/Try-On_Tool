# Manual Update Instructions for Try-On Tool Plugin

## Issue Description
When manually uploading version 1.2.2 over version 1.2.1, WordPress treats them as separate plugins, causing conflicts and fatal errors.

## Solution
The plugin now includes:
- Proper WordPress plugin identification headers
- Conflict detection and prevention mechanisms
- Version tracking and update notifications
- WordPress-compatible update process

## Manual Update Process

### Method 1: Recommended (Clean Update)
1. **Deactivate** the current version (1.2.1) in WordPress admin
2. **Delete** the old version from the plugins page
3. **Upload** the new version (1.2.2) via WordPress admin
4. **Activate** the new version

### ✅ NEW: Version in Folder Name Support
- **✅ CAN include version numbers** in folder name
- **✅ Use format**: `try-on-tool-plugin-v1.2.2`
- **✅ WordPress will detect** as update automatically
- **✅ No more conflicts** or fatal errors

### Correct Folder Structure
```
try-on-tool-plugin-v1.2.2/   ← Version in folder name (NOW SUPPORTED!)
├── woo-fitroom-preview.php  ← Main plugin file (version 1.2.2)
├── includes/
├── assets/
├── templates/
└── ... (other files)
```

### How It Works
- **Plugin Identifier**: WordPress uses `try-on-tool-plugin` identifier
- **Version Detection**: Compares version numbers, not folder names
- **Automatic Updates**: WordPress shows "Update available" screen
- **No Conflicts**: Same plugin identifier = same plugin

### Method 0: If You're Still Getting Fatal Errors (Use Cleanup Script)
1. **Upload** `cleanup-old-version.php` to your WordPress root directory
2. **Visit** `yoursite.com/cleanup-old-version.php` in your browser
3. **Follow** the instructions on the cleanup page
4. **Delete** the cleanup script after use
5. **Upload** and activate version 1.2.2

### Method 2: Direct File Replacement
1. **Deactivate** the current version (1.2.1) in WordPress admin
2. **FTP/File Manager**: Replace all files in the plugin directory with version 1.2.2 files
3. **Activate** the plugin again

### Method 3: Using WordPress Admin Upload (Now Improved!)
1. **Upload** the new version (1.2.2) zip file via WordPress admin
2. WordPress will now properly detect this as an update to the existing plugin
3. You'll see the standard WordPress update screen showing:
   - Current version: 1.2.1
   - New version: 1.2.2
   - "Replace current with uploaded" option
4. Click **"Replace current with uploaded"**
5. WordPress will automatically deactivate, replace, and reactivate the plugin
6. You'll see a success notice confirming the update

## What's Fixed in Version 1.2.2

### WordPress Update Integration
- Added proper plugin headers (Plugin URI, Author URI, Update URI)
- Added activation/deactivation hooks for proper WordPress integration
- Added version tracking to detect updates
- Added success notice when update is completed
- WordPress now properly recognizes the plugin for updates

### Conflict Prevention
- Added plugin instance detection to prevent multiple versions running simultaneously
- Added class existence checks to prevent fatal errors
- Added function existence checks for HPOS compatibility functions
- Added admin notice when multiple versions are detected

### Enhanced Theme Compatibility
- **Astra Theme**: Full color and styling detection
- **OceanWP Theme**: Comprehensive styling extraction (padding, border, typography, shadows)
- **GeneratePress Theme**: Customizer color forcing to override WooCommerce defaults
- **Storefront Theme**: Native WooCommerce theme integration

### Technical Improvements
- OceanWP primary color detection using `--ocean-primary` CSS variable
- GeneratePress customizer color forcing with priority system
- CSS specificity improvements (removed `!important` declarations)
- Enhanced admin settings text with "Try-On Tool defined" terminology
- JavaScript enhancements for better theme integration

## Troubleshooting

### If You Still See Two Versions
1. Deactivate both versions
2. Delete both versions
3. Upload and activate version 1.2.2

### If You Get Fatal Errors
1. Deactivate the plugin via FTP by renaming the plugin folder
2. Access WordPress admin
3. Delete the plugin
4. Upload and activate version 1.2.2

### If Theme Colors Don't Work
1. Check that your theme is supported (Astra, OceanWP, GeneratePress, Storefront)
2. Clear any caching plugins
3. Check browser console for JavaScript errors
4. Verify theme customizer settings are saved

## Support
If you continue to experience issues, please contact support with:
- WordPress version
- Theme name and version
- PHP version
- Error messages (if any)
- Steps to reproduce the issue

---
**Version**: 1.2.2  
**Date**: September 24, 2025  
**Compatibility**: WordPress 5.6+, WooCommerce 5.0+, PHP 7.4+
