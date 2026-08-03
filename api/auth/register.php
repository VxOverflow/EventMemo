<?php
session_start();
require_once '../db.php';
require_once '../includes/functions.php';



if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Méthode non autorisée'], 405);
}

$data = get_json_body();

$nom = ($data['nom'] ?? '');
$prenom = ($data['prenom'] ?? '');
$email = trim($data['email'] ?? '');
$tel = trim($data['tel'] ?? '');
$motDePasse = $data['mot_de_passe'] ?? '';
$confirmation = $data['confirmation'] ?? '';

if (!$nom || !$prenom || !$email || !$motDePasse) {
    json_response(['success' => false, 'message' => 'Tous les champs sont obligatoires'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['success' => false, 'message' => 'Adresse email invalide'], 400);
}
if (strlen($motDePasse) < 6) {
    json_response(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères'], 400);
}
if ($motDePasse !== $confirmation) {
    json_response(['success' => false, 'message' => 'Les mots de passe ne correspondent pas'], 400);
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['success' => false, 'message' => 'Cet email est déjà utilisé'], 409);
}

$hash = password_hash($motDePasse, PASSWORD_DEFAULT);

$stmt = $pdo->prepare(
    "INSERT INTO users (nom, prenom, email, mot_de_passe, date_creation)
     VALUES (?, ?, ?, ?, NOW())"
);
$stmt->execute([$nom,
                $prenom,
                $email,
                $hash
                ]);
if (!$stmt->rowCount()) {
    json_response(['success' => false, 'message' => 'Erreur lors de la création du compte'], 500);
}else{
    json_response(['success' => true, 'message' => 'Compte créé avec succès']);
    $_SESSION[
        'identifiant' => $email;
        'mdp'=>$motDePasse;
    ]

}
