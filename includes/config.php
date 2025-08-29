<?php
// config.php
// Database credentials and global config

// Use environment variables if available, otherwise default
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'instagram_clone');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'MNAng3l_112');

// Site settings
define('SITE_NAME', 'Instagram Clone');
define('BASE_URL', 'http://localhost/instagram_clone'); // өөрийн URL-ийг оруулна уу
