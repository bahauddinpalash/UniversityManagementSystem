<?php
if (session_status() === PHP_SESSION_NONE) session_start();

/**
 * Prevent browser caching on protected pages so clicking "Back"
 * after logging out forces a server-side session re-validation.
 */
function prevent_page_caching() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
}

function require_login() {
    prevent_page_caching();
    
    if (empty($_SESSION['user'])) {
        // Resolve path dynamically relative to root or use absolute path
        $root_path = (dirname($_SERVER['SCRIPT_NAME']) !== '/' && dirname($_SERVER['SCRIPT_NAME']) !== '\\') 
            ? '/' . trim(explode('/', trim($_SERVER['SCRIPT_NAME'], '/'))[0], '/') . '/login.php' 
            : '/login.php';
            
        header("Location: " . $root_path);
        exit;
    }
}

function require_role($role) {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        exit("Access denied.");
    }
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_dashboard() {
    prevent_page_caching();
    $role = $_SESSION['user']['role'] ?? '';
    
    // Determine path prefix if executed from root vs subdirectory
    $prefix = (basename(getcwd()) === 'admin' || basename(getcwd()) === 'lecturer' || basename(getcwd()) === 'student') ? '../' : '';

    if ($role === 'admin') {
        header("Location: " . $prefix . "admin/dashboard.php");
    } elseif ($role === 'lecturer') {
        header("Location: " . $prefix . "lecturer/dashboard.php");
    } else {
        header("Location: " . $prefix . "student/dashboard.php");
    }
    exit;
}