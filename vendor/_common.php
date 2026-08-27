<?php
session_start();
require_once __DIR__ . '/../config/database.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'vendor') {
    header('Location: ../login.php');
    exit();
}
$vendor_id = (int)$_SESSION['user_id'];
$vendor_name = $_SESSION['name'] ?? 'Vendor';
function vendor_image_upload($file, &$error = '') {
    if (!isset($file) || $file['error'] === UPLOAD_ERR_NO_FILE) return null;
    if ($file['error'] !== UPLOAD_ERR_OK) { $error = 'Image upload failed.'; return null; }
    if ($file['size'] > 5 * 1024 * 1024) { $error = 'Image must be 5 MB or smaller.'; return null; }
    $allowed = ['jpg','jpeg','png','webp','gif'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) { $error = 'Only JPG, JPEG, PNG, WEBP and GIF images are allowed.'; return null; }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) { $error = 'Please upload a valid image file.'; return null; }
    $dir = __DIR__ . '/../assets/uploads/';
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) { $error = 'Upload directory could not be created.'; return null; }
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) { $error = 'Could not save the uploaded image.'; return null; }
    return $filename;
}
?>
