#!/bin/bash
echo "Renaming plugin folder for WordPress compatibility..."
echo

# Check if current folder has version in name
if [[ "$PWD" == *"v1.2.1" ]]; then
    echo "Current folder: $PWD"
    echo "Renaming to: try-on-tool-plugin"
    echo
    
    cd ..
    mv "try-on_tool_plugin_v1.2.1" "try-on-tool-plugin"
    
    echo
    echo "Folder renamed successfully!"
    echo "New folder: try-on-tool-plugin"
    echo
    echo "You can now create a zip file from the 'try-on-tool-plugin' folder"
    echo "and upload it to WordPress. WordPress will recognize it as an update."
else
    echo "Current folder does not match expected pattern."
    echo "Please run this from the plugin folder that ends with 'v1.2.1'"
fi

echo
read -p "Press Enter to continue..."
