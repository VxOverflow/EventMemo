<?php
session_start();
//connexion à la base de donnes
require_once '../db.php';
require_once '../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$data = get_json_body();

$email = trim($data['email'] ?? '');
$motDePasse = $data['mot_de_passe'] ?? '';


if (!$email || !$motDePasse) {
    json_response(['success' => false, 'message' => 'Tous les champs sont obligatoires'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Adresse email invalide'], 400);
}
if (strlen($motDePasse) < 6) {
    json_response(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
}

//verif de l'identifiant et generation du token de securité

$stmt = $pdo->prepare("SELECT id, mot_de_passe FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();
if ($user) {
    if(password_verify($motDePasse, $user['mot_de_passe'])) {
        $token = bin2hex(random_bytes(32));
        json_response(['success' => true, 'message' => 'Connexion réussie'], 200);
        $_SESSION['token'=>$token]
    } else {
        json_response(['success' => false, 'message' => 'Mot de passe incorrect'], 401);
    }
}

