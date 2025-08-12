# Git Release Workflow - Try-On Tool v1.1.0

## Current Status
- **Current Version:** 1.1.0
- **Previous Version:** 1.0.0
- **Release Type:** Feature Release
- **Branch:** master (needs to be renamed to main)

## Step-by-Step Release Process

### Step 1: Prepare Current State
```bash
# Check current status
git status

# Add all changes
git add .

# Commit current changes
git commit -m "Prepare release v1.1.0"
```

### Step 2: Rename Master to Main (if needed)
```bash
# Rename master branch to main locally
git branch -m master main

# Push the renamed branch
git push -u origin main

# Delete the old master branch on remote
git push origin --delete master
```

### Step 3: Create Release Branch
```bash
# Create release branch
git checkout -b release/v1.1.0

# Verify you're on the release branch
git branch
```

### Step 4: Final Testing
- [ ] Test plugin activation/deactivation
- [ ] Test admin settings page loads without errors
- [ ] Test frontend templates render correctly
- [ ] Verify all syntax errors are fixed
- [ ] Test WooCommerce integration

### Step 5: Create Release Tag
```bash
# Create annotated tag
git tag -a v1.1.0 -m "Release version 1.1.0

- Initial release of Try-On Tool plugin
- WooCommerce integration for virtual try-on functionality
- Admin settings page with license validation
- Frontend try-on button and modal interface"

# Push tag to remote
git push origin v1.1.0
```

### Step 6: Merge to Main
```bash
# Switch to main branch
git checkout main

# Merge release branch
git merge release/v1.1.0

# Push to main
git push origin main
```

### Step 7: Clean Up
```bash
# Delete release branch locally
git branch -d release/v1.1.0

# Delete release branch on remote
git push origin --delete release/v1.1.0
```

### Step 8: Create GitHub Release

1. **Go to GitHub Repository**
   - Navigate to: https://github.com/DataDove-LTD/Try-On_Tool.git

2. **Create New Release**
   - Click "Releases" in the right sidebar
   - Click "Create a new release"

3. **Configure Release**
   - **Tag version:** `v1.1.0`
- **Release title:** `Try-On Tool v1.1.0`
   - **Description:** Copy from CHANGELOG.md

4. **Upload Files**
   - Create zip file: `try-on_tool_plugin_v1.1.0.zip`
   - Upload to GitHub release

5. **Publish Release**
   - Click "Publish release"

## Release Notes for GitHub

```markdown
# Try-On Tool v1.1.0

## What's New
This is the initial release of the Try-On Tool plugin for WooCommerce.

## Changes

### Added
- Initial release of Try-On Tool plugin
- WooCommerce integration for virtual try-on functionality
- Admin settings page with license validation
- Frontend try-on button and modal interface
- Wasabi S3 integration for image storage
- GDPR-compliant image handling and user consent
- Credit-based usage system
- User role and permission controls
- Automatic image cleanup for inactive users

## Installation
1. Download the zip file
2. Upload to WordPress admin → Plugins → Add New → Upload Plugin
3. Activate the plugin
4. Configure settings in WooCommerce → Settings → Try-On Tool

## Compatibility
- WordPress 6.0+
- WooCommerce 8.0+
- PHP 7.4+

## Breaking Changes
None - this is the initial release.
```

## Verification Commands

```bash
# Check current branch
git branch

# Check tags
git tag -l

# Check remote branches
git branch -r

# Check commit history
git log --oneline -10
```

## Troubleshooting

### If tag already exists
```bash
# Delete local tag
git tag -d v1.0.0

# Delete remote tag
git push origin --delete v1.0.0

# Recreate tag
git tag -a v1.0.0 -m "Release version 1.0.0"
git push origin v1.0.0
```

### If merge conflicts occur
```bash
# Abort merge
git merge --abort

# Reset to before merge
git reset --hard HEAD

# Try merge again
git merge release/v1.0.0
```

## Next Release Planning

### Version 1.1.0 (Feature Release)
- **Target Date:** TBD
- **Type:** Feature Release
- **Planned Features:**
  - Enhanced user interface
  - Additional customization options
  - Performance improvements

### Version 1.0.1 (Hotfix - if needed)
- **Type:** Bug Fix
- **Trigger:** Critical issues discovered in 1.0.0 