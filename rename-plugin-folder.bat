@echo off
echo Renaming plugin folder for WordPress compatibility...
echo.

REM Check if current folder has version in name
if "%CD:~-8%"=="v1.2.1" (
    echo Current folder: %CD%
    echo Renaming to: try-on-tool-plugin
    echo.
    cd ..
    ren "try-on_tool_plugin_v1.2.1" "try-on-tool-plugin"
    echo.
    echo Folder renamed successfully!
    echo New folder: try-on-tool-plugin
    echo.
    echo You can now create a zip file from the "try-on-tool-plugin" folder
    echo and upload it to WordPress. WordPress will recognize it as an update.
) else (
    echo Current folder does not match expected pattern.
    echo Please run this from the plugin folder that ends with "v1.2.1"
)

echo.
pause
