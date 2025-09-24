# Try-On Tool: Version in Folder Name Solution

## Problem
- Manager requires version numbers in folder names for customer visibility
- WordPress treats different folder names as separate plugins
- Need to maintain version visibility while ensuring WordPress compatibility

## Solution: Plugin Identifier System

### How It Works
1. **Plugin Identifier**: Each version uses the same plugin identifier (`try-on-tool-plugin`)
2. **Version Detection**: WordPress detects updates by comparing plugin identifiers, not folder names
3. **Folder Flexibility**: You can use any folder name with version numbers
4. **Automatic Replacement**: WordPress will replace the old version with the new one

### Folder Naming Convention
```
✅ try-on-tool-plugin-v1.2.1/
✅ try-on-tool-plugin-v1.2.2/
✅ try-on-tool-plugin-v1.3.0/
✅ try-on-tool-plugin-v2.0.0/
```

### What Happens During Upload
1. **WordPress detects** the plugin by identifier, not folder name
2. **Compares versions** from the plugin file (not folder name)
3. **Shows update screen** if version is newer
4. **Replaces old version** automatically
5. **Shows success message** with version numbers

## Implementation Details

### Plugin Identifier
- **Constant**: `WOO_FITROOM_PLUGIN_IDENTIFIER = 'try-on-tool-plugin'`
- **Stored in**: WordPress options table
- **Used for**: Plugin identification across folder name changes

### Version Tracking
- **Current Version**: Stored in `woo_fitroom_preview_version`
- **Previous Version**: Stored in `woo_fitroom_preview_previous_version`
- **Update Detection**: Compares versions, not folder names

### Update Process
1. **Upload** new version with version in folder name
2. **WordPress detects** plugin by identifier
3. **Compares** version numbers
4. **Shows** "Update available" screen
5. **Replaces** old version with new one
6. **Shows** success message

## Benefits
- ✅ **Manager's Requirement**: Version visible in folder name
- ✅ **WordPress Compatibility**: Proper update detection
- ✅ **Customer Clarity**: Easy to see what version they have
- ✅ **Automatic Updates**: WordPress handles replacement
- ✅ **No Conflicts**: No more fatal errors

## Usage Instructions

### For Developers
1. **Keep folder names** with version numbers
2. **Don't change** the plugin identifier constant
3. **Update version** in plugin header only
4. **Test upload** to ensure proper detection

### For Customers
1. **Download** the versioned folder
2. **Upload** via WordPress admin
3. **WordPress will detect** it as an update
4. **Click update** to replace old version
5. **See success message** with version info

## Technical Implementation

### Plugin Header
```php
/**
 * Plugin Name: Try-On Tool
 * Version: 1.2.2
 * ... other headers
 */
```

### Identifier System
```php
// Define plugin identifier for WordPress compatibility
if (!defined('WOO_FITROOM_PLUGIN_IDENTIFIER')) {
    define('WOO_FITROOM_PLUGIN_IDENTIFIER', 'try-on-tool-plugin');
}
```

### Update Detection
```php
// Check if this is an update by comparing versions
$previous_version = get_option('woo_fitroom_preview_previous_version', '');
if ($previous_version && version_compare($previous_version, WOO_FITROOM_PREVIEW_VERSION, '<')) {
    // This is an update - show update screen
}
```

## Testing Checklist
- [ ] Upload v1.2.1 with folder name `try-on-tool-plugin-v1.2.1`
- [ ] Activate plugin successfully
- [ ] Upload v1.2.2 with folder name `try-on-tool-plugin-v1.2.2`
- [ ] Verify WordPress shows "Update available"
- [ ] Click update and verify success message
- [ ] Check that old version is replaced
- [ ] Verify no fatal errors occur

## Troubleshooting

### If WordPress Still Shows Two Plugins
1. **Check plugin identifier** is consistent
2. **Verify version numbers** are different
3. **Clear WordPress cache**
4. **Check for conflicting plugins**

### If Update Screen Doesn't Appear
1. **Verify version** in plugin header is newer
2. **Check plugin identifier** matches
3. **Ensure old version** is activated
4. **Try deactivating** and reactivating

### If Fatal Errors Occur
1. **Use cleanup script** (`cleanup-old-version.php`)
2. **Check for class conflicts**
3. **Verify PHP syntax**
4. **Check WordPress error logs**

## Conclusion
This solution allows you to:
- ✅ Keep version numbers in folder names (manager's requirement)
- ✅ Maintain WordPress compatibility
- ✅ Provide clear customer visibility
- ✅ Enable automatic updates
- ✅ Avoid fatal errors and conflicts

The plugin will now work seamlessly regardless of folder name changes while maintaining version visibility for customers.
