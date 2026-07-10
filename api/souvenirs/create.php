<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db.php';

const MAX_PHOTO_SIZE = 5 * 1024 * 1024;
const MAX_AUDIO_SIZE = 15 * 1024 * 1024;
const MAX_VIDEO_SIZE = 80 * 1024 * 1024;

/**
 * Termine la requête avec une réponse JSON homogène.
 */
function respond(bool $success, string $message, int $status = 200, array $data = []): never
{
    http_response_code($status);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
    ]);
    exit;
}

/**
 * Déplace un téléversement contrôlé et retourne ses métadonnées persistables.
 */
function storeUpload(array $file, string $type): array
{
    $rules = [
        'photo' => [
            'maxSize' => MAX_PHOTO_SIZE,
            'directory' => 'photos',
            'mimes' => [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ],
        ],
        'audio' => [
            'maxSize' => MAX_AUDIO_SIZE,
            'directory' => 'audios',
            'mimes' => [
                'audio/webm' => 'webm',
                'audio/mpeg' => 'mp3',
                'audio/ogg' => 'ogg',
                'audio/wav' => 'wav',
                'audio/x-wav' => 'wav',
            ],
        ],
        'video' => [
            'maxSize' => MAX_VIDEO_SIZE,
            'directory' => 'videos',
            'mimes' => [
                'video/webm' => 'webm',
                'video/mp4' => 'mp4',
            ],
        ],
    ];

    if (!isset($rules[$type])) {
        throw new RuntimeException('Type de média non pris en charge.');
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Le téléversement du média a échoué.');
    }

    if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] < 1) {
        throw new RuntimeException('Fichier téléversé invalide.');
    }

    $rule = $rules[$type];
    if ((int) $file['size'] > $rule['maxSize']) {
        throw new RuntimeException('Le fichier dépasse la taille autorisée.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    if (!isset($rule['mimes'][$mime])) {
        throw new RuntimeException('Le type réel du fichier n’est pas autorisé.');
    }

    if ($type === 'photo' && @getimagesize($file['tmp_name']) === false) {
        throw new RuntimeException('L’image téléversée est invalide.');
    }

    $year = date('Y');
    $relativeDirectory = '/uploads/' . $rule['directory'] . '/' . $year;
    $absoluteDirectory = dirname(__DIR__, 2) . $relativeDirectory;

    if (!is_dir($absoluteDirectory) && !mkdir($absoluteDirectory, 0755, true) && !is_dir($absoluteDirectory)) {
        throw new RuntimeException('Impossible de préparer le dossier de stockage.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $rule['mimes'][$mime];
    $absolutePath = $absoluteDirectory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        throw new RuntimeException('Impossible d’enregistrer le média.');
    }

    return [
        'type' => $type,
        'url' => $relativeDirectory . '/' . $filename,
        'taille_octets' => filesize($absolutePath),
        'absolute_path' => $absolutePath,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Méthode non autorisée.', 405);
}

$token = trim((string) ($_POST['token'] ?? ''));
$nom = trim((string) ($_POST['nom'] ?? ''));
$email = trim((string) ($_POST['email'] ?? ''));
$contenu = trim((string) ($_POST['contenu'] ?? ''));

if ($token === '' || $nom === '' || mb_strlen($nom) > 100) {
    respond(false, 'Le lien et votre nom sont obligatoires.', 422);
}

if ($email !== '' && (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150)) {
    respond(false, 'L’adresse e-mail est invalide.', 422);
}

if (mb_strlen($contenu) > 5000) {
    respond(false, 'Le message est trop long.', 422);
}

$hasPhotos = !empty($_FILES['photos']['name']) && is_array($_FILES['photos']['name']);
$hasAudio = !empty($_FILES['audio']['name']);
$hasVideo = !empty($_FILES['video']['name']);
if ($contenu === '' && !$hasPhotos && !$hasAudio && !$hasVideo) {
    respond(false, 'Ajoutez un message ou au moins un média.', 422);
}

if ($hasAudio && $hasVideo) {
    respond(false, 'Envoyez un enregistrement audio ou vidéo, pas les deux à la fois.', 422);
}

try {
    $eventStatement = $bdd->prepare(
        "SELECT id FROM events WHERE token_acces = :token AND statut = 'actif' LIMIT 1"
    );
    $eventStatement->execute(['token' => $token]);
    $event = $eventStatement->fetch();
    if ($event === false) {
        respond(false, 'Ce livre d’or est indisponible.', 404);
    }

    $media = [];
    if ($hasPhotos) {
        $photoCount = count($_FILES['photos']['name']);
        if ($photoCount > 10) {
            respond(false, 'Vous pouvez envoyer au maximum 10 photos.', 422);
        }

        foreach ($_FILES['photos']['name'] as $index => $_unusedName) {
            $media[] = storeUpload([
                'name' => $_FILES['photos']['name'][$index],
                'tmp_name' => $_FILES['photos']['tmp_name'][$index],
                'error' => $_FILES['photos']['error'][$index],
                'size' => $_FILES['photos']['size'][$index],
            ], 'photo');
        }
    }

    if ($hasAudio) {
        $media[] = storeUpload($_FILES['audio'], 'audio');
    }

    if ($hasVideo) {
        $media[] = storeUpload($_FILES['video'], 'video');
    }

    $bdd->beginTransaction();

    if ($email !== '') {
        $guestStatement = $bdd->prepare('SELECT id FROM guests WHERE email = :email LIMIT 1');
        $guestStatement->execute(['email' => $email]);
        $guestId = $guestStatement->fetchColumn();
    } else {
        $guestId = false;
    }

    if ($guestId === false) {
        $insertGuest = $bdd->prepare('INSERT INTO guests (nom, email) VALUES (:nom, :email)');
        $insertGuest->execute([
            'nom' => $nom,
            'email' => $email !== '' ? $email : null,
        ]);
        $guestId = (int) $bdd->lastInsertId();
    }

    $insertParticipation = $bdd->prepare(
        'INSERT IGNORE INTO event_guest (event_id, guest_id) VALUES (:event_id, :guest_id)'
    );
    $insertParticipation->execute([
        'event_id' => $event['id'],
        'guest_id' => $guestId,
    ]);

    $insertSouvenir = $bdd->prepare(
        'INSERT INTO souvenirs (event_id, guest_id, contenu) VALUES (:event_id, :guest_id, :contenu)'
    );
    $insertSouvenir->execute([
        'event_id' => $event['id'],
        'guest_id' => $guestId,
        'contenu' => $contenu !== '' ? $contenu : null,
    ]);
    $souvenirId = (int) $bdd->lastInsertId();

    $insertMedia = $bdd->prepare(
        'INSERT INTO souvenir_media (souvenir_id, type, url, taille_octets) VALUES (:souvenir_id, :type, :url, :taille_octets)'
    );
    foreach ($media as $medium) {
        $insertMedia->execute([
            'souvenir_id' => $souvenirId,
            'type' => $medium['type'],
            'url' => $medium['url'],
            'taille_octets' => $medium['taille_octets'],
        ]);
    }

    $bdd->commit();
    $publicMedia = array_map(
        static fn (array $medium): array => [
            'type' => $medium['type'],
            'url' => $medium['url'],
            'taille_octets' => $medium['taille_octets'],
        ],
        $media
    );

    respond(true, 'Votre souvenir a bien été ajouté.', 201, [
        'souvenir_id' => $souvenirId,
        'medias' => $publicMedia,
    ]);
} catch (Throwable $exception) {
    if ($bdd->inTransaction()) {
        $bdd->rollBack();
    }

    foreach ($media ?? [] as $medium) {
        if (isset($medium['absolute_path']) && is_file($medium['absolute_path'])) {
            unlink($medium['absolute_path']);
        }
    }

    error_log($exception->getMessage());
    respond(false, 'Impossible d’enregistrer ce souvenir.', 500);
}
