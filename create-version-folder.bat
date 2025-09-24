@echo off
echo Creating versioned folder for Try-On Tool...
echo.

REM Get current version from plugin file
for /f "tokens=2 delims=: " %%a in ('findstr "Version:" woo-fitroom-preview.php') do set PLUGIN_VERSION=%%a
set PLUGIN_VERSION=%PLUGIN_VERSION: =%

echo Plugin Version: %PLUGIN_VERSION%
echo.

REM Create versioned folder name
set FOLDER_NAME=try-on-tool-plugin-v%PLUGIN_VERSION%

echo Creating folder: %FOLDER_NAME%
echo.

REM Check if folder already exists
if exist "%FOLDER_NAME%" (
    echo Folder %FOLDER_NAME% already exists!
    echo Please delete it first or choose a different name.
    pause
    exit /b 1
)

REM Create the versioned folder
mkdir "%FOLDER_NAME%"

REM Copy all files to the new folder
echo Copying files...
xcopy /E /I /Y * "%FOLDER_NAME%\"

echo.
echo ✅ Success! Created folder: %FOLDER_NAME%
echo.
echo Next steps:
echo 1. Create a zip file from the %FOLDER_NAME% folder
echo 2. Upload the zip file to WordPress admin
echo 3. WordPress will detect it as an update
echo 4. Click update to replace the old version
echo.

pause
