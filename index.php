<?php
// Configure error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get and validate the current directory path
$base_dir = __DIR__;
$current_dir = $base_dir;

if (isset($_GET['path']) && !empty($_GET['path'])) {
    $requested_path = $_GET['path'];
    // Sanitize the path to prevent directory traversal
    $requested_path = str_replace('..', '', $requested_path);
    $requested_path = ltrim($requested_path, '/');
    $full_path = realpath($base_dir . DIRECTORY_SEPARATOR . $requested_path);
    
    // Ensure we're still within the base directory
    if ($full_path && strpos($full_path, $base_dir) === 0 && is_dir($full_path)) {
        $current_dir = $full_path;
    }
}

$relative_path = substr($current_dir, strlen($base_dir));
$relative_path = trim($relative_path, DIRECTORY_SEPARATOR);
$path_parts = $relative_path ? explode(DIRECTORY_SEPARATOR, $relative_path) : [];

$files = scandir($current_dir);

// Get current domain and protocol
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
$domain = $_SERVER['HTTP_HOST'];
$current_url = $protocol . $domain . $_SERVER['REQUEST_URI'];

// Handle file download if requested
if (isset($_GET['download'])) {
    $file = $_GET['download'];
    $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
    
    if (file_exists($file_path) && !is_dir($file_path)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="'.basename($file).'"');
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <style>
        :root {
            --bg-color: #1a1a1a;
            --text-color: #e0e0e0;
            --hover-color: #2a2a2a;
            --border-color: #333;
            --link-color: #8ab4f8;
            --breadcrumb-color: #666;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            font-family: 'Courier New', monospace;
            line-height: 1.6;
            padding: 2rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        h1 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .breadcrumb {
            margin-bottom: 2rem;
            color: var(--breadcrumb-color);
        }

        .breadcrumb a {
            color: var(--link-color);
            text-decoration: none;
        }

        .breadcrumb a:hover {
            text-decoration: underline;
        }

        .file-list {
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .file-item {
            display: grid;
            grid-template-columns: 1fr 200px 150px;
            padding: 0.5rem 1rem;
            border-bottom: 1px solid var(--border-color);
            text-decoration: none;
            color: var(--text-color);
        }

        .file-item:last-child {
            border-bottom: none;
        }

        .file-item:hover {
            background-color: var(--hover-color);
        }

        .file-item.folder {
            color: var(--link-color);
        }

        .file-name {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .wget-section {
            margin-top: 2rem;
            padding: 1rem;
            background-color: var(--hover-color);
            border-radius: 4px;
        }

        .wget-command {
            background-color: var(--bg-color);
            padding: 1rem;
            border-radius: 4px;
            margin-top: 0.5rem;
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h1>File Manager</h1>
    <div class="breadcrumb">
        <a href="?path=">root</a>
        <?php
        $current_path = '';
        foreach ($path_parts as $part) {
            $current_path .= '/' . $part;
            echo ' / <a href="?path=' . urlencode(ltrim($current_path, '/')) . '">' . htmlspecialchars($part) . '</a>';
        }
        ?>
    </div>
    <div class="file-list">
        <?php if ($relative_path): ?>
        <a href="?path=<?php echo urlencode(dirname($relative_path)); ?>" class="file-item folder">
            <span class="file-name">..</span>
            <span class="file-modified"></span>
            <span class="file-size"></span>
        </a>
        <?php endif; ?>

        <?php foreach($files as $file): ?>
            <?php
                if ($file === '.' || $file === '..') continue;
                $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
                $is_dir = is_dir($file_path);
                $last_modified = date("Y-m-d H:i:s", filemtime($file_path));
                $size = $is_dir ? '' : formatSize(filesize($file_path));
                $display_name = $file . ($is_dir ? '/' : '');
                
                $link_path = $relative_path ? $relative_path . '/' . $file : $file;
            ?>
            <a href="<?php echo $is_dir ? '?path=' . urlencode($link_path) : '?download=' . urlencode($file) . '&path=' . urlencode($relative_path); ?>" 
               class="file-item <?php echo $is_dir ? 'folder' : ''; ?>"
               <?php echo !$is_dir ? 'target="_blank"' : ''; ?>>
                <span class="file-name"><?php echo htmlspecialchars($display_name); ?></span>
                <span class="file-modified"><?php echo $last_modified; ?></span>
                <span class="file-size"><?php echo $size; ?></span>
            </a>
        <?php endforeach; ?>
    </div>
    </div>
</body>
</html>

<?php
function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}
?>
