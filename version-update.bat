@echo off
REM Version Update Script for Try-On Tool Plugin
REM Usage: version-update.bat <new-version>

if "%1"=="" (
    echo Usage: version-update.bat ^<new-version^>
    echo Example: version-update.bat 1.1.0
    exit /b 1
)

set NEW_VERSION=%1
for /f "tokens=2 delims==" %%a in ('wmic OS Get localdatetime /value') do set "dt=%%a"
set "YEAR=%dt:~2,2%"
set "MONTH=%dt:~4,2%"
set "DAY=%dt:~6,2%"
set CURRENT_DATE=20%YEAR%-%MONTH%-%DAY%

echo Updating version to %NEW_VERSION%...

REM Update main plugin file (using PowerShell for regex)
powershell -Command "(Get-Content woo-fitroom-preview.php) -replace 'Version: [0-9]+\.[0-9]+\.[0-9]*', 'Version: %NEW_VERSION%' | Set-Content woo-fitroom-preview.php"
powershell -Command "(Get-Content woo-fitroom-preview.php) -replace \"define\('WOO_FITROOM_PREVIEW_VERSION', '[0-9]+\.[0-9]+\.[0-9]*'\);\", \"define('WOO_FITROOM_PREVIEW_VERSION', '%NEW_VERSION%');\" | Set-Content woo-fitroom-preview.php"
powershell -Command "(Get-Content woo-fitroom-preview.php) -replace '// Modified by DataDove LTD on [0-9]{4}-[0-9]{2}-[0-9]{2}', '// Modified by DataDove LTD on %CURRENT_DATE%' | Set-Content woo-fitroom-preview.php"

echo âœ… Updated woo-fitroom-preview.php

REM Update RELEASE_CHECKLIST.md
if exist "RELEASE_CHECKLIST.md" (
    powershell -Command "(Get-Content RELEASE_CHECKLIST.md) -replace '# Release Checklist - Version [0-9]+\.[0-9]+', '# Release Checklist - Version %NEW_VERSION%' | Set-Content RELEASE_CHECKLIST.md"
    echo âœ… Updated RELEASE_CHECKLIST.md
)

REM Update VERSION_CONTROL.md
if exist "VERSION_CONTROL.md" (
    powershell -Command "(Get-Content VERSION_CONTROL.md) -replace '### Current Version: [0-9]+\.[0-9]+\.[0-9]*', '### Current Version: %NEW_VERSION%' | Set-Content VERSION_CONTROL.md"
    echo âœ… Updated VERSION_CONTROL.md
)

echo.
echo ðŸŽ‰ Version updated to %NEW_VERSION%
echo ðŸ“ Don't forget to:
echo    - Review the changes
echo    - Test the plugin
echo    - Update any additional version references
echo    - Commit changes: git add . ^&^& git commit -m "Update version to %NEW_VERSION%" 
