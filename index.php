<?php
// Set the default directory to the current directory, or the one provided in the URL
$directory = isset($_GET['dir']) ? $_GET['dir'] : '.';
// Normalize the directory path
$directory = realpath($directory);
// Handle file download request
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $directory . '/' . $file;
    if (file_exists($filepath)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename=' . basename($filepath));
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        echo "File not found!";
    }
}
// List files and directories
$items = scandir($directory);
// Function to create a clickable link for directories
function createDirectoryLink($dir, $name) {
    return '<a href="?dir=' . urlencode($dir) . '">' . htmlspecialchars($name) . '</a>';
}
// Function to get the appropriate icon for a file type
function getFileIcon($file) {
    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    switch ($extension) {
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return '<i class="fas fa-image icon"></i>'; // Image icon
        case 'mp4':
        case 'avi':
        case 'mov':
        case 'mkv':
            return '<i class="fas fa-video icon"></i>'; // Video icon
        case 'mp3':
        case 'wav':
        case 'ogg':
        case 'flac':
            return '<i class="fas fa-music icon"></i>'; // Audio icon
        case 'zip':
        case 'rar':
        case 'tar':
        case 'gz':
            return '<i class="fas fa-file-archive icon"></i>'; // Archive icon
        default:
            return '<i class="fas fa-file icon"></i>'; // Default file icon
    }
}
// Function to format the last modified date
function getLastModifiedDate($path) {
    return date("Y-m-d H:i:s", filemtime($path));
}
// Simple dark-themed styling with responsive design
echo '<style>
    body { background-color: #2e2e2e; color: #f5f5f5; font-family: Arial, sans-serif; margin: 0; padding: 0; }
    h2 { color: #00bfff; padding: 20px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
    th, td { padding: 10px; text-align: left; border-bottom: 1px solid #555; }
    a { color: #00bfff; text-decoration: none; }
    a:hover { text-decoration: underline; }
    .folder { color: #ffcc00; }
    .file { color: #ffffff; }
    .icon { margin-right: 8px; }
    .footer { margin-top: 20px; text-align: center; color: #f5f5f5; }
    @media (max-width: 768px) {
        table { font-size: 14px; }
        th, td { padding: 8px; }
        .folder, .file { font-size: 16px; }
        .footer { font-size: 14px; padding: 10px 0; }
    }
    @media (max-width: 480px) {
        h2 { font-size: 18px; padding: 10px; }
        table { font-size: 12px; }
        th, td { padding: 6px; }
        .folder, .file { font-size: 14px; }
        .footer { font-size: 12px; }
    }
</style>';
// Include Font Awesome for icons
echo '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">';
// Show current directory and navigation back
echo '<h2>Browsing: ' . htmlspecialchars($directory) . '</h2>';
if ($directory !== realpath('.')) {
    $parent = dirname($directory);
    echo '<p><a href="?dir=' . urlencode($parent) . '">Back to Parent Directory</a></p>';
}
// Start table
echo '<table>';
echo '<tr><th>Name</th><th>Type</th><th>Last Modified</th><th>Action</th></tr>';
// List directories first
foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }
    $path = $directory . '/' . $item;
    if (is_dir($path)) {
        echo '<tr><td class="folder"><i class="fas fa-folder icon"></i>' . createDirectoryLink($path, $item) . '</td><td>Folder</td><td>' . getLastModifiedDate($path) . '</td><td></td></tr>';
    }
}
// Then list files
foreach ($items as $item) {
    if ($item === '.' || $item === '..') {
        continue;
    }
    $path = $directory . '/' . $item;
    if (is_file($path)) {
        echo '<tr><td class="file">' . getFileIcon($item) . htmlspecialchars($item) . '</td><td>File</td><td>' . getLastModifiedDate($path) . '</td><td><a href="?download=' . urlencode($item) . '&dir=' . urlencode($directory) . '">Download</a></td></tr>';
    }
}
echo '</table>';
// Footer section with GitHub link
echo '<div class="footer">';
echo '<p><a href="https://github.com/nixietab/simple-php-file-manger" target="_blank">Made with freedom <i class="fab fa-github"></i></a></p>';
echo '</div>';
?>
