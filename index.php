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

// Get sort parameters
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'asc';

// Get files and prepare for sorting
$files = scandir($current_dir);
$file_list = [];

foreach($files as $file) {
    if ($file === '.' || $file === '..') continue;
    
    $file_path = $current_dir . DIRECTORY_SEPARATOR . $file;
    $is_dir = is_dir($file_path);
    $file_info = [
        'name' => $file,
        'is_dir' => $is_dir,
        'modified' => filemtime($file_path),
        'size' => $is_dir ? 0 : filesize($file_path),
        'type' => $is_dir ? 'directory' : pathinfo($file, PATHINFO_EXTENSION)
    ];
    $file_list[] = $file_info;
}

// Sort files
usort($file_list, function($a, $b) use ($sort_by, $sort_order) {
    // Directories always come first
    if ($a['is_dir'] !== $b['is_dir']) {
        return $b['is_dir'] - $a['is_dir'];
    }
    
    $mult = $sort_order === 'desc' ? -1 : 1;
    switch($sort_by) {
        case 'type':
            return $mult * strcasecmp($a['type'], $b['type']);
        case 'size':
            return $mult * ($a['size'] - $b['size']);
        case 'modified':
            return $mult * ($a['modified'] - $b['modified']);
        case 'name':
        default:
            return $mult * strcasecmp($a['name'], $b['name']);
    }
});

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

function formatSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

// Function to generate sort URL
function getSortUrl($sort_field, $current_sort, $current_order, $path) {
    $new_order = ($sort_field === $current_sort && $current_order === 'asc') ? 'desc' : 'asc';
    return '?path=' . urlencode($path) . '&sort=' . $sort_field . '&order=' . $new_order;
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
            --sort-indicator-color: #666;
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
            padding: 1rem;
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
            overflow-x: auto;
            white-space: nowrap;
            padding-bottom: 0.5rem;
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
            overflow-x: auto;
        }

        .file-list-header {
            display: grid;
            grid-template-columns: minmax(200px, 2fr) minmax(150px, 1fr) minmax(100px, 1fr) minmax(100px, 1fr);
            padding: 0.5rem 1rem;
            background-color: var(--hover-color);
            border-bottom: 2px solid var(--border-color);
            position: sticky;
            top: 0;
        }

        .file-list-header a {
            color: var(--text-color);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .sort-indicator::after {
            content: '↑';
            color: var(--sort-indicator-color);
            font-size: 0.8em;
        }

        .sort-indicator.desc::after {
            content: '↓';
        }

        .file-item {
            display: grid;
            grid-template-columns: minmax(200px, 2fr) minmax(150px, 1fr) minmax(100px, 1fr) minmax(100px, 1fr);
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

        @media (max-width: 768px) {
            .file-list-header,
            .file-item {
                grid-template-columns: minmax(150px, 2fr) minmax(100px, 1fr) minmax(80px, 1fr);
            }
            
            .file-type {
                display: none;
            }
        }

        @media (max-width: 480px) {
            .file-list-header,
            .file-item {
                grid-template-columns: 2fr 1fr;
            }
            
            .file-modified {
                display: none;
            }
            
            body {
                padding: 0.5rem;
            }
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
        <div class="file-list-header">
            <a href="<?php echo getSortUrl('name', $sort_by, $sort_order, $relative_path); ?>">
                Name
                <?php if ($sort_by === 'name'): ?>
                <span class="sort-indicator <?php echo $sort_order; ?>"></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo getSortUrl('type', $sort_by, $sort_order, $relative_path); ?>">
                Type
                <?php if ($sort_by === 'type'): ?>
                <span class="sort-indicator <?php echo $sort_order; ?>"></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo getSortUrl('modified', $sort_by, $sort_order, $relative_path); ?>">
                Modified
                <?php if ($sort_by === 'modified'): ?>
                <span class="sort-indicator <?php echo $sort_order; ?>"></span>
                <?php endif; ?>
            </a>
            <a href="<?php echo getSortUrl('size', $sort_by, $sort_order, $relative_path); ?>">
                Size
                <?php if ($sort_by === 'size'): ?>
                <span class="sort-indicator <?php echo $sort_order; ?>"></span>
                <?php endif; ?>
            </a>
        </div>

        <?php if ($relative_path): ?>
        <a href="?path=<?php echo urlencode(dirname($relative_path)); ?>" class="file-item folder">
            <span class="file-name">..</span>
            <span class="file-type">Directory</span>
            <span class="file-modified"></span>
            <span class="file-size"></span>
        </a>
        <?php endif; ?>

        <?php foreach($file_list as $file): ?>
            <?php
                $link_path = $relative_path ? $relative_path . '/' . $file['name'] : $file['name'];
                $display_name = $file['name'] . ($file['is_dir'] ? '/' : '');
            ?>
            <a href="<?php echo $file['is_dir'] ? '?path=' . urlencode($link_path) : '?download=' . urlencode($file['name']) . '&path=' . urlencode($relative_path); ?>" 
               class="file-item <?php echo $file['is_dir'] ? 'folder' : ''; ?>"
               <?php echo !$file['is_dir'] ? 'target="_blank"' : ''; ?>>
                <span class="file-name"><?php echo htmlspecialchars($display_name); ?></span>
                <span class="file-type"><?php echo $file['is_dir'] ? 'Directory' : strtoupper($file['type'] ?: 'File'); ?></span>
                <span class="file-modified"><?php echo date("Y-m-d H:i:s", $file['modified']); ?></span>
                <span class="file-size"><?php echo $file['is_dir'] ? '' : formatSize($file['size']); ?></span>
            </a>
        <?php endforeach; ?>
    </div>
</body>
</html>
