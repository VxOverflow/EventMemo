# Cahier des charges — Livre d'Or Numérique (EventMemo)

## 1. Présentation du projet

### 1.1 Contexte
Lors d'événements prestigieux (mariages, galas, conférences, inaugurations...), les hôtes mettent à disposition un livre d'or physique où les invités laissent un message. Le projet consiste à digitaliser cet usage : une plateforme web permettant à des **agents événementiels** de créer des événements, de générer un lien/QR code d'accès, et de collecter les souvenirs de leurs invités — **texte, photo, audio et vidéo** — le tout consultable depuis un dashboard.

### 1.2 Objectif
Fournir un outil simple, rapide et élégant pour :
- créer et gérer plusieurs événements ;
- collecter des souvenirs **texte, photo, message vocal ou vidéo courte**, via un lien public, sans que l'invité n'ait besoin de créer de compte ni d'installer une app (dans l'esprit de plateformes comme GuestPix, mais à l'échelle d'un projet étudiant/prototype) ;
- consulter, filtrer, trier et relire ces souvenirs ;
- offrir une expérience visuelle soignée, avec mode clair/sombre.

### 1.3 Stack technique
- **Frontend** : HTML5 / CSS3 / JavaScript vanilla (+ API navigateur `MediaRecorder` pour l'enregistrement audio/vidéo)
- **Backend** : PHP (API interne)
- **Communication** : le frontend ne contient que des pages HTML statiques ; toute donnée dynamique transite via des appels **fetch()** vers des endpoints PHP qui répondent en JSON (ou `FormData` pour l'envoi de fichiers)
- **Base de données** : MySQL/MariaDB
- **Notifications** : API WhatsApp (Twilio sandbox en développement)

### 1.4 Principe d'architecture
```
[Page HTML] --fetch()/FormData--> [api/xxx.php] --requête SQL--> [Base de données]
                                <--JSON--
[Page HTML] <--JS injecte le DOM--
```
Aucune donnée n'est générée côté PHP dans le HTML. Chaque page HTML est un conteneur vide rempli par le JS après appel à l'API.

---

## 2. Charte graphique — mode clair / mode sombre

Palette pensée pour un rendu **sobre, premium et « livre d'or »** (ton bronze/doré + charbon), qui reste lisible et professionnelle plutôt que festive/criarde.

### 2.1 Mode clair
| Rôle | Couleur | Hex |
|---|---|---|
| Fond de page | Ivoire chaud | `#FAF8F3` |
| Fond des cartes/panels | Blanc | `#FFFFFF` |
| Texte principal | Charbon | `#1F2328` |
| Texte secondaire | Gris ardoise | `#5B6169` |
| Accent primaire (boutons, liens) | Bronze doré | `#A67C52` |
| Accent primaire (hover) | Bronze foncé | `#8B6541` |
| Accent secondaire (titres, icônes) | Bleu nuit | `#2C3E50` |
| Bordures / séparateurs | Beige clair | `#E7E2D8` |
| Succès | Vert sauge | `#4CAF7D` |
| Erreur | Rouge terracotta | `#D6634A` |

### 2.2 Mode sombre
| Rôle | Couleur | Hex |
|---|---|---|
| Fond de page | Charbon profond | `#14161A` |
| Fond des cartes/panels | Charbon clair | `#1E2126` |
| Texte principal | Ivoire | `#F2F0EA` |
| Texte secondaire | Gris clair | `#A8ACB3` |
| Accent primaire (boutons, liens) | Or clair | `#D4AF7A` |
| Accent primaire (hover) | Or soutenu | `#C9A265` |
| Accent secondaire (titres, icônes) | Bleu poudré | `#7FA0C4` |
| Bordures / séparateurs | Gris charbon | `#2C2F36` |
| Succès | Vert menthe | `#5FCB93` |
| Erreur | Rouge corail | `#E5786A` |

### 2.3 Implémentation CSS
```css
:root {
  --bg: #FAF8F3;
  --surface: #FFFFFF;
  --text: #1F2328;
  --text-secondary: #5B6169;
  --accent: #A67C52;
  --accent-hover: #8B6541;
  --accent-2: #2C3E50;
  --border: #E7E2D8;
  --success: #4CAF7D;
  --error: #D6634A;
}

[data-theme="dark"] {
  --bg: #14161A;
  --surface: #1E2126;
  --text: #F2F0EA;
  --text-secondary: #A8ACB3;
  --accent: #D4AF7A;
  --accent-hover: #C9A265;
  --accent-2: #7FA0C4;
  --border: #2C2F36;
  --success: #5FCB93;
  --error: #E5786A;
}

body {
  background: var(--bg);
  color: var(--text);
  transition: background 0.2s ease, color 0.2s ease;
}
```
Typographie suggérée : une police serif discrète pour les titres (ex. `"Fraunces"`, `"Playfair Display"`) pour l'effet « livre » élégant, et une sans-serif lisible pour le corps de texte (ex. `"Inter"`, `"Work Sans"`).

`js/theme.js` : un bouton toggle bascule `document.documentElement.dataset.theme` entre `"light"` et `"dark"`, sauvegarde le choix dans `localStorage`, et un script en tête de page lit `localStorage` avant l'affichage pour éviter le flash de couleur.

---

## 3. Modèle de données

### 3.1 `users` (organisateurs / agents événementiels)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| nom | VARCHAR(100) | |
| email | VARCHAR(150) UNIQUE | |
| telephone | VARCHAR(20) UNIQUE | format international, utilisé pour l'OTP et l'envoi WhatsApp |
| telephone_verifie | BOOLEAN DEFAULT FALSE | passe à TRUE une fois l'OTP validé |
| mot_de_passe | VARCHAR(255) | haché (password_hash) |
| avatar | VARCHAR(255) NULL | optionnel |
| plan_id | INT FK → plans.id | plan « Gratuit » par défaut |
| plan_expire_le | DATETIME NULL | |
| date_creation | DATETIME | |

### 3.2 `events`
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| user_id | INT FK → users.id | organisateur |
| nom | VARCHAR(150) | |
| description | TEXT NULL | |
| date_evenement | DATE | |
| lieu | VARCHAR(150) NULL | |
| token_acces | VARCHAR(64) UNIQUE | utilisé dans le lien public et le QR code |
| statut | ENUM('actif','clos') | ferme la collecte après l'événement |
| date_creation | DATETIME | |

### 3.3 `guests` (invités)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| nom | VARCHAR(100) | |
| email | VARCHAR(150) NULL | identifie un invité récurrent |
| date_creation | DATETIME | |

### 3.4 `event_guest` (liaison invité ↔ événement)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| event_id | INT FK → events.id | |
| guest_id | INT FK → guests.id | |
| date_participation | DATETIME | |

### 3.5 `souvenirs`
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| event_id | INT FK → events.id | |
| guest_id | INT FK → guests.id | |
| contenu | TEXT NULL | message texte (peut être vide si l'invité n'envoie qu'un média) |
| date_creation | DATETIME | |

### 3.6 `souvenir_media` (photos, audio, vidéo — un souvenir peut en avoir plusieurs)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| souvenir_id | INT FK → souvenirs.id | |
| type | ENUM('photo','audio','video') | |
| url | VARCHAR(255) | chemin du fichier stocké |
| duree_secondes | INT NULL | pour audio/vidéo uniquement |
| taille_octets | INT NULL | suivi de l'espace disque utilisé |
| date_creation | DATETIME | |

### 3.7 `otp_codes` (vérification du numéro + mot de passe oublié)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| user_id | INT FK → users.id | |
| code | VARCHAR(6) | |
| type | ENUM('inscription','reset_password') | |
| expire_a | DATETIME | |
| utilise | BOOLEAN DEFAULT FALSE | |
| date_creation | DATETIME | |

### 3.8 `plans` (offres tarifaires)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| nom | VARCHAR(50) | ex: « Gratuit », « Pro » |
| prix | DECIMAL(10,2) | 0 pour le plan gratuit |
| nb_evenements_max | INT NULL | NULL = illimité |
| nb_souvenirs_max_par_event | INT NULL | NULL = illimité |
| espace_max_mo | INT NULL | quota de stockage (important une fois la vidéo/audio ajoutés) |
| description | TEXT NULL | |
| actif | BOOLEAN DEFAULT TRUE | |

### 3.9 `transactions` (historique des paiements)
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| user_id | INT FK → users.id | |
| plan_id | INT FK → plans.id | |
| montant | DECIMAL(10,2) | |
| moyen_paiement | VARCHAR(50) NULL | rempli à l'intégration réelle |
| reference_externe | VARCHAR(100) NULL | |
| statut | ENUM('en_attente','reussi','echoue') | |
| date_creation | DATETIME | |

### 3.10 (Optionnel v2) `sessions`
Table de tokens si tu ne pars pas sur `$_SESSION` (utile pour une future appli mobile).

---

## 4. Fonctionnalités par page

### 4.1 Inscription / Connexion (`register.html`, `login.html`, `verify-otp.html`)
- Inscription (nom, email, téléphone, mot de passe) → OTP généré et envoyé par WhatsApp → saisie du code sur `verify-otp.html` → compte activé
- Connexion refusée tant que `telephone_verifie = FALSE`

### 4.2 Mot de passe oublié (`forgot-password.html`, `reset-password.html`)
- OTP (`type = 'reset_password'`) envoyé par WhatsApp → saisie du code + nouveau mot de passe

### 4.3 Dashboard (`dashboard.html`)
Statistiques (`GET api/dashboard/stats.php`) :
- Nombre total de souvenirs, répartis par type (texte / photo / audio / vidéo)
- Nombre de personnes distinctes ayant contribué
- Nombre d'événements organisés
- Espace de stockage utilisé (utile dès que la vidéo entre en jeu)

### 4.4 Événements (`events.html`)
- Création d'un événement → génération d'un `token_acces` unique
- Envoi automatique du lien + QR code par WhatsApp à l'organisateur, avec bouton pour le renvoyer manuellement
- Actions : clore, modifier, supprimer, voir les souvenirs liés

### 4.5 Page publique de contribution (`contribuer.html`) — sans compte
- Vérifie le `token` dans l'URL (`GET api/events/verify.php`)
- Formulaire : nom (+ email optionnel), message texte optionnel, et choix du type de souvenir :
  - **Photo** : upload classique (`<input type="file" accept="image/*" multiple>`) ou capture directe via webcam
  - **Audio** : enregistrement direct dans le navigateur via `MediaRecorder` (micro), durée plafonnée (ex. 2 minutes), avec bouton « réécouter » avant envoi
  - **Vidéo** : enregistrement direct via webcam (`MediaRecorder` + `getUserMedia({video, audio})`), durée plafonnée (ex. 60 secondes, à la GuestPix), avec relecture avant envoi
- Envoi en `multipart/form-data` vers `POST api/souvenirs/create.php` : crée/retrouve le `guest`, lie à l'événement, crée le `souvenir`, enregistre chaque média dans `souvenir_media`
- Barre de progression pendant l'upload (les vidéos peuvent être volumineuses)
- Message de confirmation

### 4.6 Page Souvenirs (`souvenirs.html`)
- Liste paginée, filtrable par événement et par type de média, triable par date ou par nom
- Chaque souvenir affiche son contenu texte + ses médias : galerie photo (lightbox), lecteur audio intégré (`<audio controls>`), lecteur vidéo intégré (`<video controls>`)
- Téléchargement individuel ou export global d'un événement (v2)

### 4.7 Profil / Paramètres (`profil.html`)
- Modifier nom / email / mot de passe
- Choix du thème clair/sombre
- Section « Abonnement » : plan actuel, quota d'espace utilisé/restreint, historique de facturation

### 4.8 Tarifs (`tarifs.html`)
- Présentation des plans (voir §5.7 architecture paiement)

---

## 5. Aide technique : appels API

### 5.1 Convention générale
Chaque endpoint PHP répond en JSON homogène :
```json
{ "success": true, "data": { ... } }
{ "success": false, "message": "Erreur : ..." }
```

### 5.2 Exemple : statistiques du dashboard
**Frontend (`js/dashboard.js`)**
```javascript
async function chargerStats() {
  try {
    const reponse = await fetch('api/dashboard/stats.php', { credentials: 'include' });
    const resultat = await reponse.json();
    if (resultat.success) {
      document.querySelector('#nb-souvenirs').textContent = resultat.data.nb_souvenirs;
      document.querySelector('#nb-invites').textContent = resultat.data.nb_invites;
      document.querySelector('#nb-evenements').textContent = resultat.data.nb_evenements;
    }
  } catch (erreur) {
    console.error('Erreur réseau :', erreur);
  }
}
document.addEventListener('DOMContentLoaded', chargerStats);
```

**Backend (`api/dashboard/stats.php`)**
```php
<?php
session_start();
header('Content-Type: application/json');
require_once '../db.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM events WHERE user_id = ?");
$stmt->execute([$userId]);
$nbEvenements = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(s.id) FROM souvenirs s JOIN events e ON s.event_id = e.id WHERE e.user_id = ?");
$stmt->execute([$userId]);
$nbSouvenirs = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT s.guest_id) FROM souvenirs s JOIN events e ON s.event_id = e.id WHERE e.user_id = ?");
$stmt->execute([$userId]);
$nbInvites = $stmt->fetchColumn();

echo json_encode(['success' => true, 'data' => [
    'nb_evenements' => (int)$nbEvenements,
    'nb_souvenirs' => (int)$nbSouvenirs,
    'nb_invites' => (int)$nbInvites,
]]);
```

### 5.3 Génération de QR code (affichage écran)
```html
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
  new QRCode(document.getElementById("qrcode"), "https://tondomaine.com/contribuer.html?token=" + token);
</script>
```

### 5.4 Envoi de l'OTP et du QR code par WhatsApp
Nécessite une API tierce (pas de fonction PHP native). Pour un projet étudiant : Twilio sandbox WhatsApp est le plus rapide à mettre en place. Alternative « pro » : WhatsApp Cloud API (Meta), plus lourde à configurer.

```php
<?php
// api/notifications/send-whatsapp.php
require_once '../../vendor/autoload.php'; // composer require twilio/sdk
use Twilio\Rest\Client;

function envoyerWhatsapp(string $numeroDestinataire, string $message): bool {
    $client = new Client('TON_ACCOUNT_SID', 'TON_AUTH_TOKEN');
    try {
        $client->messages->create("whatsapp:$numeroDestinataire", [
            "from" => "whatsapp:+14155238886",
            "body" => $message
        ]);
        return true;
    } catch (Exception $e) {
        error_log("Erreur WhatsApp : " . $e->getMessage());
        return false;
    }
}
```

### 5.5 Enregistrement audio/vidéo dans le navigateur (`js/recorder.js`)
Pas besoin de librairie externe : l'API native `MediaRecorder` suffit.

```javascript
let mediaRecorder;
let chunks = [];

async function demarrerEnregistrement(type) {
  const contraintes = type === 'video' ? { video: true, audio: true } : { audio: true };
  const stream = await navigator.mediaDevices.getUserMedia(contraintes);

  if (type === 'video') {
    document.querySelector('#previsualisation').srcObject = stream;
  }

  mediaRecorder = new MediaRecorder(stream);
  chunks = [];
  mediaRecorder.ondataavailable = (e) => chunks.push(e.data);
  mediaRecorder.start();

  const dureeMax = type === 'video' ? 60000 : 120000;
  setTimeout(() => { if (mediaRecorder.state === 'recording') mediaRecorder.stop(); }, dureeMax);
}

function arreterEnregistrement() {
  return new Promise((resolve) => {
    mediaRecorder.onstop = () => {
      const blob = new Blob(chunks, { type: mediaRecorder.mimeType });
      resolve(blob);
    };
    mediaRecorder.stop();
  });
}
```
Le `blob` obtenu est ensuite ajouté à un `FormData` (voir §5.6) exactement comme un fichier issu d'un `<input type="file">`.

### 5.6 Upload de médias (photo / audio / vidéo)

**Frontend (`js/contribuer.js`)**
```javascript
async function envoyerSouvenir(event) {
  event.preventDefault();
  const formData = new FormData();
  formData.append('token', tokenEvenement);
  formData.append('nom', document.querySelector('#nom').value);
  formData.append('contenu', document.querySelector('#message').value);

  for (const fichier of document.querySelector('#photos').files) {
    formData.append('photos[]', fichier);
  }
  if (blobAudio) formData.append('audio', blobAudio, 'message.webm');
  if (blobVideo) formData.append('video', blobVideo, 'message.webm');

  try {
    const reponse = await fetch('api/souvenirs/create.php', { method: 'POST', body: formData });
    const resultat = await reponse.json();
    document.querySelector('#confirmation').textContent = resultat.success
      ? 'Merci, ton souvenir a bien été envoyé !'
      : resultat.message;
  } catch (erreur) {
    console.error('Erreur réseau :', erreur);
  }
}
```

**Backend (`api/souvenirs/create.php`)** — principes clés :
```php
<?php
header('Content-Type: application/json');
require_once '../db.php';

$regles = [
  'photo' => ['extensions' => ['jpg','jpeg','png','webp'], 'taille_max' => 5 * 1024 * 1024],
  'audio' => ['extensions' => ['webm','mp3','ogg','wav'],   'taille_max' => 15 * 1024 * 1024],
  'video' => ['extensions' => ['webm','mp4'],               'taille_max' => 80 * 1024 * 1024],
];

function traiterFichier(string $tmpName, string $nomOriginal, string $type, array $regles, string $dossierBase): ?array {
    $extension = strtolower(pathinfo($nomOriginal, PATHINFO_EXTENSION));
    if (!in_array($extension, $regles[$type]['extensions'])) return null;
    if (filesize($tmpName) > $regles[$type]['taille_max']) return null;

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpName);
    finfo_close($finfo);
    $prefixesAutorises = ['photo' => 'image/', 'audio' => 'audio/', 'video' => 'video/'];
    if (strpos($mime, $prefixesAutorises[$type]) !== 0) return null;

    $dossier = $dossierBase . $type . 's/' . date('Y') . '/';
    if (!is_dir($dossier)) mkdir($dossier, 0755, true);

    $nomFichier = bin2hex(random_bytes(16)) . '.' . $extension;
    $cheminFinal = $dossier . $nomFichier;
    move_uploaded_file($tmpName, $cheminFinal);

    return ['url' => '/uploads/' . $type . 's/' . date('Y') . '/' . $nomFichier, 'taille_octets' => filesize($cheminFinal)];
}

// ... vérifier le token, retrouver/créer le guest, créer le souvenir ...

$dossierBase = '../../uploads/';
$mediasEnregistres = [];

if (!empty($_FILES['photos'])) {
    foreach ($_FILES['photos']['tmp_name'] as $i => $tmpName) {
        $resultat = traiterFichier($tmpName, $_FILES['photos']['name'][$i], 'photo', $regles, $dossierBase);
        if ($resultat) $mediasEnregistres[] = $resultat + ['type' => 'photo'];
    }
}
if (!empty($_FILES['audio']['tmp_name'])) {
    $resultat = traiterFichier($_FILES['audio']['tmp_name'], $_FILES['audio']['name'], 'audio', $regles, $dossierBase);
    if ($resultat) $mediasEnregistres[] = $resultat + ['type' => 'audio'];
}
if (!empty($_FILES['video']['tmp_name'])) {
    $resultat = traiterFichier($_FILES['video']['tmp_name'], $_FILES['video']['name'], 'video', $regles, $dossierBase);
    if ($resultat) $mediasEnregistres[] = $resultat + ['type' => 'video'];
}
// Pour chaque élément de $mediasEnregistres : INSERT INTO souvenir_media (...)

echo json_encode(['success' => true, 'data' => ['medias' => $mediasEnregistres]]);
```

### 5.7 Architecture prête pour un futur moyen de paiement
Ne jamais appeler une passerelle de paiement directement depuis le code métier : passer par une interface, implémentable plus tard sans rien casser.

```php
// api/payments/PaymentGatewayInterface.php
interface PaymentGatewayInterface {
    public function initierPaiement(int $userId, int $planId, float $montant): array;
    public function verifierTransaction(string $referenceExterne): string;
}
```

Une implémentation factice (`FakeGateway.php`, paiement toujours réussi) permet de développer/tester tout le flux dès maintenant. Le jour venu : `FedapayGateway.php` ou `KkiapayGateway.php` (Mobile Money local, préférables à Stripe pour une clientèle béninoise) implémentent la même interface.

Endpoints à prévoir : `POST api/payments/create.php`, `POST api/payments/webhook.php` (signature à vérifier une fois branché), `GET api/payments/history.php`.

---

## 6. Sécurité — points à ne pas négliger

- **Mots de passe** : toujours `password_hash()` / `password_verify()`
- **Requêtes préparées PDO** partout, jamais de concaténation SQL
- **Échappement HTML** (`htmlspecialchars()`) des souvenirs affichés, contenu non authentifié
- **Validation serveur systématique**, en plus de la validation JS
- **Protection CSRF** sur les formulaires sensibles (token en session)
- **Rate limiting** sur la page de contribution publique (anti-spam)
- `token_acces` suffisamment long et aléatoire

### 6.1 Sécurité OTP
- Max 5 tentatives par heure par utilisateur
- Vérifier `expire_a > NOW()` et `utilise = FALSE`
- Invalider les anciens OTP non utilisés quand un nouveau est généré (même `user_id` + `type`)
- Ne jamais renvoyer le code dans la réponse JSON

### 6.2 Sécurité upload (photo / audio / vidéo)
- Ne jamais faire confiance à l'extension déclarée : vérifier le vrai type MIME (`finfo_file`, ou `getimagesize()` pour les photos)
- Toujours renommer le fichier stocké avec un nom aléatoire
- Limiter la taille par fichier et le nombre de médias par souvenir
- Plafonner la durée d'enregistrement côté client (particulièrement pour la vidéo, la plus lourde en stockage)
- Stocker les fichiers hors de `api/`, désactiver l'exécution de scripts dans `uploads/` (`.htaccess` avec `php_flag engine off` sous Apache)
- Suivre le quota d'espace disque par utilisateur (`plans.espace_max_mo`)

### 6.3 Sécurité paiement
Une fois une vraie passerelle branchée : toujours vérifier la signature du webhook avant de modifier `plan_id`.

---

## 7. Roadmap

### Phase 1 — Fondations
- [ ] Base de données (script SQL, 10 tables)
- [ ] Connexion PDO (`api/db.php`)
- [ ] Inscription/connexion + sessions PHP
- [ ] Compte Twilio (sandbox WhatsApp) + test d'envoi simple
- [ ] Flux OTP inscription + mot de passe oublié
- [ ] Squelette HTML/CSS + navigation
- [ ] Variables CSS clair/sombre (palette §2) + toggle

### Phase 2 — Cœur fonctionnel
- [ ] CRUD événements + génération lien/QR code
- [ ] Envoi automatique du lien/QR code par WhatsApp
- [ ] Page publique de contribution : texte + upload photo
- [ ] Enregistrement audio dans le navigateur (`MediaRecorder`) + upload
- [ ] Enregistrement vidéo dans le navigateur + upload
- [ ] Page souvenirs : galerie photo, lecteur audio, lecteur vidéo, filtres et tri
- [ ] Dashboard avec statistiques (dont espace de stockage utilisé)

### Phase 3 — Finitions
- [ ] Page profil / paramètres
- [ ] Sécurisation complète (CSRF, rate limiting, XSS, upload — §6)
- [ ] Responsive / mobile-first
- [ ] Tests manuels sur les parcours organisateur et invité

### Phase 4 — Améliorations futures
- [ ] Export PDF / ZIP du livre d'or d'un événement
- [ ] Notification email à chaque nouveau souvenir
- [ ] Statistiques avancées

### Phase 5 — Monétisation
- [ ] Tables `plans` / `transactions` + plan Gratuit par défaut
- [ ] `PaymentGatewayInterface` + `FakeGateway`
- [ ] Page `tarifs.html` + section abonnement dans `profil.html`
- [ ] Application des quotas selon le plan
- [ ] Intégration réelle FedaPay/Kkiapay + sécurisation du webhook

---

## 8. Arborescence de fichiers

GoldBook/
- charges.md
- contribuer.html
- dashboard.html
- event.html
- forgot-password.html
- index.html
- LICENSE.md
- login.html
- profil.html
- readme.md
- register.html
- reset-password.html
- souvenir.html
- tarif.html
- verify-otp.html
- api/
  - db.php
  - db.sql
  - auth/
    - forgot-password.php
    - login.php
    - logout.php
    - register.php
    - reset-password.php
    - verify-otp.php
  - back-office/
    - dashboard-admin.php
    - login-admin.php
  - dashboard/
    - stats.php
  - events/
    - create.php
    - list.php
    - verify.php
  - notifications/
    - send-whatsapp.php
  - paiements/
    - create.php
    - FakeGateway.php
    - history.php
    - PaymentGatewayInterface.php
    - webhook.php
  - souvenirs/
    - create.php
    - list.php
- assets/
  - css/
    - style.css
    - theme.css
  - images/
    - accueil.webp
    - accueil (2).webp
    - accueil (3).webp
    - accueil (4).webp
    - events.webp
    - events (2).webp
    - logout.jpg
    - logout.webp
    - profil (2).webp
    - profil (3).webp
    - profil (4).webp
    - profil.webp
  - js/
    - auth.js
    - contribuer.js
    - dashboard.js
    - events.js
    - otp.js
    - souvenirs.js
    - theme.js
- vues-back-office/
  - dashboard.html
  - login.html
  - users.html

## 9. Back-office administrateur

### 9.1 Contexte
En plus de l'espace « organisateur » (dashboard, événements, souvenirs), la plateforme a besoin d'un espace **admin** réservé à toi (exploitant de GoldBook), pour superviser l'ensemble de la plateforme : tous les organisateurs, tous les événements, l'usage global, et la modération si besoin. Cet espace est totalement séparé de l'espace organisateur, avec son propre login et ses propres sessions.

### 9.2 Modèle de données — table `admins`
| Champ | Type | Description |
|---|---|---|
| id | INT PK AI | |
| nom | VARCHAR(100) | |
| email | VARCHAR(150) UNIQUE | |
| mot_de_passe | VARCHAR(255) | haché (password_hash) |
| date_creation | DATETIME | |

> Une table **séparée** de `users` (et non un simple champ `role` sur `users`) : ça évite qu'une faille ou un bug d'escalade de privilèges sur le compte d'un organisateur ne donne accidentellement accès au back-office. Le premier compte admin est créé manuellement en base (ou via un script CLI) — il n'y a **pas** de formulaire d'inscription admin public.

### 9.3 Pages (`vues-back-office/`)
- **`login.html`** : formulaire de connexion admin → `POST api/back-office/login-admin.php` → démarre une session **distincte** de celle des organisateurs (ex. `$_SESSION['admin_id']`, jamais mélangée avec `$_SESSION['user_id']`)
- **`dashboard.html`** : vue globale de la plateforme (tous organisateurs confondus) — nombre total d'organisateurs, d'événements, de souvenirs (par type texte/photo/audio/vidéo), espace de stockage total utilisé, revenus si le paiement est actif (§5.7)
- **`users.html`** : liste des organisateurs (`users`) avec recherche, et actions : voir le détail d'un compte, suspendre/réactiver, changer son plan manuellement, supprimer

### 9.4 Endpoints (`api/back-office/`)
- `POST api/back-office/login-admin.php` : authentifie contre la table `admins`
- `POST api/back-office/logout-admin.php` *(à ajouter — absent de l'arborescence actuelle)*
- `GET api/back-office/dashboard-admin.php` : statistiques globales toutes plateformes confondues
- `GET api/back-office/users.php` *(à ajouter)* : liste paginée des organisateurs, avec recherche
- `PATCH api/back-office/users.php` *(à ajouter)* : suspendre/réactiver un compte, changer son `plan_id`
- `DELETE api/back-office/users.php` *(à ajouter)* : supprimer un compte organisateur (et en cascade ses événements/souvenirs, à confirmer côté UX avant suppression définitive)

### 9.5 Sécurité spécifique
- Chaque endpoint de `api/back-office/` vérifie `isset($_SESSION['admin_id'])` — **jamais** la même vérification que les endpoints organisateurs
- Nom de session/cookie différent entre espace organisateur et back-office, pour qu'une session compromise côté organisateur ne donne jamais accès à l'admin
- Rate limiting renforcé sur `login-admin.php` (cible privilégiée)
- Optionnel mais recommandé vu le niveau de privilège : réutiliser le mécanisme OTP WhatsApp existant (§5.4) comme double authentification pour la connexion admin
- Optionnel v2 : table `admin_logs` pour tracer les actions sensibles (suspension de compte, changement de plan, suppression)