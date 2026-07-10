# Memora 📖✨

**Memora** est une alternative numérique au livre d'or physique, destinée aux agents événementiels. Elle permet de créer des événements, de collecter les souvenirs laissés par les invités via un simple lien ou QR code, et de les consulter depuis un dashboard.

## ✨ Fonctionnalités

- 🔐 Inscription/connexion avec vérification du numéro de téléphone par OTP (WhatsApp)
- 🔑 Réinitialisation de mot de passe par OTP
- 📅 Création d'événements avec génération automatique d'un lien public + QR code
- 📲 Lien et QR code envoyés automatiquement par WhatsApp à l'organisateur
- 💌 Page publique de contribution : les invités laissent un souvenir sans créer de compte
- 📊 Dashboard avec statistiques (souvenirs, invités, événements)
- 🗂️ Consultation et filtrage des souvenirs par événement
- 🌗 Mode clair / mode sombre
- 💳 Architecture prête pour l'ajout futur d'un moyen de paiement (FedaPay, Kkiapay, Stripe...)

## 🛠️ Stack technique

- **Frontend** : HTML5, CSS3, JavaScript (vanilla)
- **Backend** : PHP (API JSON, appels via `fetch()`)
- **Base de données** : MySQL / MariaDB
- **Notifications** : API WhatsApp (Twilio sandbox en développement)

Le frontend ne contient que des pages HTML statiques ; toute donnée dynamique transite via des appels AJAX vers des endpoints PHP qui répondent en JSON. Voir [`charges.md`](./charges.md) pour le détail complet de l'architecture et du modèle de données.

## 📁 Structure du projet

```
/
├── index.html
├── login.html / register.html / verify-otp.html
├── forgot-password.html / reset-password.html
├── dashboard.html
├── events.html
├── souvenirs.html
├── profil.html / tarifs.html
├── contribuer.html          # page publique invités
├── css/
├── js/
└── api/
    ├── auth/
    ├── notifications/
    ├── payments/
    ├── events/
    ├── souvenirs/
    └── dashboard/
```

## 🚀 Installation

### Prérequis
- PHP 8+
- MySQL / MariaDB
- Un serveur local (XAMPP, WAMP, Laravel Herd, ou `php -S`)
- Un compte Twilio (sandbox WhatsApp) pour les notifications

### Étapes

```bash
git clone https://github.com/<ton-user>/Memora.git
cd Memora
```

1. Créer la base de données et importer le schéma :
   ```bash
   mysql -u root -p < database/schema.sql
   ```
2. Copier le fichier de config d'exemple et renseigner tes identifiants :
   ```bash
   cp api/config.example.php api/config.php
   ```
3. Renseigner dans `api/config.php` :
   - les identifiants de connexion à la base de données
   - les identifiants Twilio (`ACCOUNT_SID`, `AUTH_TOKEN`, numéro sandbox)
4. Lancer le serveur local :
   ```bash
   php -S localhost:8000
   ```
5. Ouvrir `http://localhost:8000` dans le navigateur.

## 🗄️ Base de données

Tables principales : `users`, `events`, `guests`, `event_guest`, `souvenirs`, `otp_codes`, `plans`, `transactions`. Le script de création est disponible dans `database/schema.sql`.

## 🤝 Contribuer

1. Fork du projet
2. Crée une branche (`git checkout -b feature/ma-fonctionnalite`)
3. Commit tes changements (`git commit -m "Ajoute ma fonctionnalité"`)
4. Push la branche (`git push origin feature/ma-fonctionnalite`)
5. Ouvre une Pull Request

## 📄 Licence

Ce projet est distribué sous licence MIT — voir le fichier [`LICENSE`](./LICENSE).

## 👤 Auteur

Vianney — étudiant en informatique à l'Ecole Spérieure de Gestion, d'Informatique et de Sciences (ESGIS), Cotonou, Bénin.
