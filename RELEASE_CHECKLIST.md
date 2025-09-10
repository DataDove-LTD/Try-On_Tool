# Release Checklist - Version 1.2.0

## Pre-Release Tasks

### âœ… Version Updates
- [x] Update version in `woo-fitroom-preview.php` (1.2.0)
- [x] Update `WOO_FITROOM_PREVIEW_VERSION` constant
- [x] Create/update `CHANGELOG.md`
- [x] Update `README.md` with current version

### âœ… Code Quality
- [x] Fix GPL license headers in template files
- [x] Ensure all PHP syntax is correct
- [x] Test admin settings page functionality
- [x] Verify frontend templates render correctly

### âœ… Testing Checklist
- [ ] Test plugin activation/deactivation
- [ ] Test admin settings page loads without errors
- [ ] Test license validation functionality
- [ ] Test frontend try-on button display
- [ ] Test modal template rendering
- [ ] Test WooCommerce integration
- [ ] Test user permission controls
- [ ] Test image upload functionality
- [ ] Test credit system
- [ ] Test GDPR compliance features

### âœ… Documentation
- [x] Update CHANGELOG.md
- [x] Update README.md
- [ ] Review and update INSTALL.md if needed
- [ ] Review and update DEPENDENCIES.md if needed

## Release Tasks

### ðŸ“¦ Package Preparation
- [ ] Create new zip file: `try-on_tool_plugin_v1.2.0.zip`
- [ ] Ensure all files are included:
  - [ ] Main plugin file (`woo-fitroom-preview.php`)
  - [ ] All `includes/` classes
  - [ ] All `templates/` files
  - [ ] All `assets/` files (CSS, JS, images)
  - [ ] All `languages/` files
  - [ ] All documentation files
  - [ ] `vendor/` directory (Composer dependencies)
  - [ ] `composer.json` and `composer.lock`

### ðŸ” Final Verification
- [ ] Test zip file installation on fresh WordPress site
- [ ] Verify no PHP errors in error logs
- [ ] Test all major functionality
- [ ] Verify GPL compliance files are present:
  - [ ] `COPYING.txt`
  - [ ] `WRITTEN_OFFER.txt`
  - [ ] License headers in all source files

### ðŸ“‹ Distribution Files
- [ ] `try-on_tool_plugin_v1.2.0.zip` (main plugin)
- [ ] `CHANGELOG.md` (release notes)
- [ ] Updated documentation

## Post-Release Tasks

### ðŸ“ Documentation Updates
- [ ] Update any external documentation
- [ ] Update website/landing page if applicable
- [ ] Prepare release announcement

### ðŸ”„ Version Management
- [ ] Tag this version in version control (if using Git)
- [ ] Create backup of v1.2.0 release files
- [ ] Prepare for next development cycle

## Git Release Workflow

### Step 1: Create Release Branch
```bash
# Ensure you're on main branch
git checkout main
git pull origin main

# Create release branch
git checkout -b release/v1.2.0

# Commit all changes
git add .
git commit -m "Prepare for v1.2.0 release"
```

### Step 2: Create Release Tag
```bash
# Create annotated tag
git tag -a v1.2.0 -m "Release version 1.2.0"

# Push tag to remote
git push origin v1.2.0
```

### Step 3: Merge to Main
```bash
# Switch to main branch
git checkout main

# Merge release branch
git merge release/v1.2.0

# Push to main
git push origin main

# Delete release branch (optional)
git branch -d release/v1.2.0
git push origin --delete release/v1.2.0
```

### Step 4: Create GitHub Release
1. Go to GitHub repository
2. Click "Releases" â†’ "Create a new release"
3. Select the `v1.2.0` tag
4. Add release title: "Try-On Tool v1.2.0"
5. Add release notes from CHANGELOG.md
6. Upload the zip file: `try-on_tool_plugin_v1.2.0.zip`

## Release Notes Summary

**Version 1.2.0**
- **Added:** Initial release of Try-On Tool plugin
- **Added:** WooCommerce integration for virtual try-on functionality
- **Added:** Admin settings page with license validation
- **Added:** Frontend try-on button and modal interface
 - **Added:** Social share button in preview modal; uses `images/share-2.svg`
 - **Added:** Regenerate button icon updated; uses `images/repeat-2.svg`

 - **Changed:** Subscription switch (upgrade/downgrade) proration messaging refined to show base, negative credit from remaining yearly value, and expected next renewal total (applies at renewal, not today)
 - **Changed:** Preview footer icons given explicit fallback color for visibility and CSS simplified (removed external mask dependency)

**Breaking Changes:** None
**Compatibility:** WordPress 6.0+, WooCommerce 8.0+, PHP 7.4+ 
