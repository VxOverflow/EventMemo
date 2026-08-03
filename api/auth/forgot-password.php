<?php
session_start();
required_once '../db.php';
required_once '../functions.php';

$data = get_json_body();
$email = $data['email'];
if ($_SERVER['REQUEST_METHOD'] != 'POST'){
    json_response('success' => false, 'message' => 'Methode de connexion invalide', )
}else{
    $req = $pdo->prepare(SELECT * FROM users where email = :email);
    $req -> prepare($email);
    
    if($req->fetch()){
        $message = "Utilisez le code suivant pour vous connecter. \n Attention, ce code n'est valide que pendant 2 minutes "
        $sujet = "Code OTP de connexion";
        mail($email, $sujet, $message);
        json_response('success' => true, 'message' => 'Code envoyé avec succès. Verifiez votre boîte mail' )
    }else{
        json_response('success' => false, 'message' => 'Erreur serveur. Reesayez plus tard' )

    }
}