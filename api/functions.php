<?php
/**
 * includes/functions.php
 * Fonctions utilitaires réutilisées par tous les scripts api/*.php
 */

/** Envoie une réponse JSON normalisée et arrête le script */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/** Nettoie une chaîne reçue de l'utilisateur (anti-XSS basique) */
function sanitize($str) {
    return htmlspecialchars(trim($str ?? ''), ENT_QUOTES, 'UTF-8');
}

/** Lit le corps JSON d'une requête POST/PUT et le retourne en tableau */
function get_json_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/**
 * Gère l'upload d'une image envoyée en multipart/form-data.
 * Retourne le chemin relatif (à stocker en BDD) ou null si aucun fichier valide.
 */
function handle_image_upload($fieldName, $subFolder) {
    if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $file = $_FILES[$fieldName];
    $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        json_response(['success' => false, 'message' => 'Format d\'image non autorisé (jpg, png, gif, webp uniquement)'], 400);
    }
    if ($file['size'] > $maxSize) {
        json_response(['success' => false, 'message' => 'Image trop lourde (5 Mo maximum)'], 400);
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($mime, $allowedMime)) {
        json_response(['success' => false, 'message' => 'Fichier invalide'], 400);
    }

    $newName = uniqid('img_', true) . '.' . $ext;
    $targetDir = __DIR__ . '/../assets/images/uploads/' . $subFolder . '/';
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0777, true);
    }
    $targetPath = $targetDir . $newName;

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        json_response(['success' => false, 'message' => 'Échec de l\'enregistrement de l\'image'], 500);
    }

    // Chemin relatif utilisable depuis les pages vues/clients/*.html
    return 'assets/images/uploads/' . $subFolder . '/' . $newName;
}
