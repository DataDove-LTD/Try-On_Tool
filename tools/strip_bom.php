<?php
// Utility: Strip UTF-8 BOM from all PHP files in this plugin (excluding vendor)
// Usage: Upload to your plugin folder and visit it once in the browser.
// Remove the file after successful run.

if ( php_sapi_name() !== 'cli' ) {
    header('Content-Type: text/plain; charset=UTF-8');
}

$root = dirname(__DIR__);
$excluded = ['vendor' . DIRECTORY_SEPARATOR];
$updated = 0; $checked = 0; $errors = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS | FilesystemIterator::FOLLOW_SYMLINKS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($iterator as $file) {
    $path = $file->getPathname();
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') { continue; }
    foreach ($excluded as $ex) {
        if (strpos($path, $root . DIRECTORY_SEPARATOR . $ex) === 0) { continue 2; }
    }
    $checked++;
    $fh = @fopen($path, 'rb');
    if (!$fh) { $errors[] = "Cannot open: $path"; continue; }
    $bom = fread($fh, 3);
    $hasBom = ($bom === "\xEF\xBB\xBF");
    if ($hasBom) {
        $rest = stream_get_contents($fh);
        fclose($fh);
        $ok = @file_put_contents($path, $rest, LOCK_EX);
        if ($ok === false) { $errors[] = "Failed to write: $path"; }
        else { $updated++; }
    } else {
        fclose($fh);
    }
}

echo "Checked: $checked\n";
echo "Stripped BOM: $updated\n";
if ($errors) {
    echo "Errors:\n" . implode("\n", $errors) . "\n";
}


