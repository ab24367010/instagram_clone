<?php
/**
 * Instagram Clone Setup Script
 * Run this file once to set up the necessary directories and files
 */

// Create necessary directories
$directories = [
    'assets/uploads',
    'logs',
    'api'
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "✓ Created directory: $dir<br>";
        } else {
            echo "✗ Failed to create directory: $dir<br>";
        }
    } else {
        echo "✓ Directory exists: $dir<br>";
    }
}

// Create default avatar SVG
$defaultAvatarSVG = '<?xml version="1.0" encoding="UTF-8"?>
<svg width="150" height="150" viewBox="0 0 150 150" xmlns="http://www.w3.org/2000/svg">
    <rect width="150" height="150" fill="#DBDBDB"/>
    <circle cx="75" cy="50" r="25" fill="#8E8E8E"/>
    <ellipse cx="75" cy="110" rx="45" ry="30" fill="#8E8E8E"/>
</svg>';

$avatarPath = 'assets/uploads/default_avatar.svg';
if (file_put_contents($avatarPath, $defaultAvatarSVG)) {
    echo "✓ Created default avatar: $avatarPath<br>";
} else {
    echo "✗ Failed to create default avatar<br>";
}

// Create .htaccess for security
$htaccessContent = '# Protect PHP files in uploads directory
<FilesMatch "\.(php|php3|php4|php5|phtml|pl|py|jsp|asp|sh|cgi)$">
    Order Deny,Allow
    Deny from all
</FilesMatch>

# Allow image files
<FilesMatch "\.(jpg|jpeg|png|gif|svg|webp)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>';

$htaccessPath = 'assets/uploads/.htaccess';
if (file_put_contents($htaccessPath, $htaccessContent)) {
    echo "✓ Created .htaccess for security<br>";
} else {
    echo "✗ Failed to create .htaccess<br>";
}

// Create default PNG avatar (base64 encoded)
$defaultAvatarPNG = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAJYAAACWCAYAAAA8AXHiAAAACXBIWXMAAAsTAAALEwEAmpwYAAAE5klEQVR4nO3dT2hcVRTH8e+ZJE2btGmtVkRFXLhQEFy4EHThwkVBEAQXLly4cCEiCIIgCIIgCIKIICKIiCAIgiAIgiAIgiAIgiAIgnjuzLy8ee/de+6950zS+X5gFpn3Zt6853fPvefee+8VERERERERERERkbPTZeBL4CfgKPBn/Pc34EvgEjlDLMDzwEngf9b7DzgBPEc2zCLwMeuDKvMRsMjYmQdeZXNQZV4B5hgbM8AhhhVUmYPADGNhFjjM8IMqcxiYJdoF4BjpBVXmGHCBKBeBE6QfVJkTwEWymweOsn1BlTkKzJPNHPAa2x9UmdeAOZKbAQ4xnqDKHAJmSGYWOMx4gypzGJhlaPPAq4w/qDKvAvMMZRF4kc0NKvQ5HuD+d4B5sYHPnANeZvOhxJg5Zl6u3pOUWeBRhhvU58BTwO3AHcBdwCPAu8Afnb/9HXi3fqbtZ9u+f1hvaxZ4lF6uAT8z3KA+BO4l3BzwFPA168fXPp7b+iRFcxv4neEG9RFwtedZL7M5kBeBv4B3gMdrFZsHLgMPN/6/Wfmdbp1YPu9vTv8jAFwBfqXfoL4AnqhN9luqE26HN5sC+hZ4hl5uAn8w3KA+Be4hzgXgE9aP/UvgdsBdJ9qjzBJhrlT/31F/+VNsXqKnCLvb8//DG9Rxwkc3C7wOnGJ1uD8DHzO9g98C/mTz+M4Ar9T3xdCHlY8RfpCfw0c1C7zN8rC/IY0RLQBvsjoYZVD/ESeo3lpXsm3R5bZfzCSwJa7jWWBLXCdChldi/lfiOqGcCBleiUuGd9+BXLPE+8wSk1hiElgSS4QLOMoH7eUkrlP9kcRYKaFR3s/lE7jqb4qhB3UnlvJBBzZViTJBBzp17Qi5gJXYPNfUcE3rGqwtUdcUQ1uirqlMo2tqU9y6pjg1NfV3TVFPO7V2TVOv6kLcicW+qgt1Jx5lBxvVT8ddvYoQ946oKxF2cXRFhRWFJcOjwkpEYYWKNcVQYSkqHGOosBQVjitSWLKi3BH1lnA/VRRlQeIS2VJYeflS5BKWDRJXvr5QNT5QYeXpCy3xAYUVyAdhKeHhzP0hLCU8ZOQPGlM4y8kfNJab0pFelBvKTxfITGdJkZsWSJHBLJHCCqJbJYUlwxNGYYVSWEEUFhQWCmuLKSwUlhJeJ+GSXkp4iyjhaaTOe1JnwZyU8DBRZz6RNUWa2JzPRJ35zEQ12ifqzGcm1OvfTKjXv5lQr39Uj/9Uj/9EIw5aIw7jaBtRBznwUG4AhSXDWuIGEBkeFZYE06qX5VNYcCZ2YimDWE6sq+RDd5UoLBketCy7zz2hLRnX/y0ZR6uSu5lQWLRU7kSr3ruZUFjQsvguJhQWLCU8xLISCgsUlgxPhSXBnFjKnJezcyJjnvIQmXdtQKbXGxy0GXCLKKxA2owfVNcUQ7VuaZ+z4NqMH1RXNKGwYCkq9N+wT6QdWlVYKiwVVioUFigsFVYaJhQWKCwVVhooLFBYKqw0UFigsFRYaaCwQGGpsNJAYYHCUmGlgcIChTWRhZXZRB7WthOdsEKZ2LnCrXHRnm1lJnCusEGPGGdC5wq3xkV6tpWJnCvcGhfp2VYmcq4QbYc7v8jYxqJzhdsmvwvPtjLh5wpFREREREREREREREREzjbn2UM7VwQNy9AAAAAASUVORK5CYII=');

$pngPath = 'assets/uploads/default_avatar.png';
if (file_put_contents($pngPath, $defaultAvatarPNG)) {
    echo "✓ Created default PNG avatar: $pngPath<br>";
} else {
    echo "✗ Failed to create PNG avatar<br>";
}

// Check database connection
require_once __DIR__ . '/includes/database.php';

if ($pdo) {
    echo "✓ Database connection successful<br>";
    
    // Create tables if they don't exist
    $sqlFile = file_get_contents('database_schema.sql');
    if ($sqlFile) {
        try {
            $pdo->exec($sqlFile);
            echo "✓ Database tables created/verified<br>";
        } catch (PDOException $e) {
            echo "✗ Error creating tables: " . $e->getMessage() . "<br>";
        }
    }
} else {
    echo "✗ Database connection failed<br>";
}

echo "<br><strong>Setup Complete!</strong><br>";
echo "You can now <a href='auth/register.php'>register a new account</a> or <a href='auth/login.php'>login</a>.";
?>