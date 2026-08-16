# 🏨 HospitOS — Hospitality Operating System & White-Label Hotel Digitization Engine

> **Système Modulaire de Numérisation & Gestion Hôtelière Clé-en-Main (Marque Blanche)**  
> *Plateforme hôtelière complète combinant un Front-Office d'exception 100% personnalisable, un moteur de réservation temps réel, un module de Room Service connecté, un espace client VIP et un Back-Office d'administration RBAC complet.*

---

## 🌟 Présentation

**HospitOS** est un moteur applicatif haut de gamme conçu pour digitaliser intégralement les établissements hôteliers de luxe, resorts, lodges et résidences de standing.

Conçu selon une architecture **Marque Blanche (White-Label)**, le système s'adapte instantanément à la charte graphique et à l'identité de n'importe quel établissement partenaire en moins de 5 minutes via configuration centralisée (`.env` & tokens CSS).

---

## 🎨 Personnalisation Marque Blanche (5 Minutes Setup)

Pour déployer le système pour un nouvel hôtel :

1. Renseignez l'identité de l'hôtel dans votre fichier `.env` :
```env
SYSTEM_NAME=HospitOS
HOTEL_NAME="Hôtel Grand Prestige & Spa"
HOTEL_NAME_SHORT="Grand Prestige"
HOTEL_TAGLINE="L'Excellence · Le Confort · L'Hospitalité"
HOTEL_LOCATION="Boulevard de la Marina"
HOTEL_CITY="Lomé"
HOTEL_COUNTRY="Togo"
HOTEL_PHONE="+228 90 00 00 00"
HOTEL_WHATSAPP="22890000000"
HOTEL_EMAIL="reservations@grandprestige-hotel.com"
HOTEL_CURRENCY="FCFA"
HOTEL_REF_PREFIX="HTL-"
HOTEL_CLIENT_PREFIX="CLI-"

# Palette de couleurs sur-mesure (Tokens CSS)
THEME_COLOR_PRIMARY="#1a3a2a"
THEME_COLOR_PRIMARY_LIGHT="#2d5c40"
THEME_COLOR_ACCENT="#c9a84c"
THEME_COLOR_ACCENT_LIGHT="#e0c068"
THEME_COLOR_ACCENT_PALE="#f5e9c4"
```

2. Le moteur injecte automatiquement le Design System personnalisé (`hotel_theme_css()`), les logos, coordonnées, devises, templates d'emails transactionnels et préfixes de dossiers.

---

## ✨ Fonctionnalités Principales

### 👤 Côté Client (Front-Office)
- **Catalogue d'hébergements haut de gamme** : Suites, chambres supérieures, villas privées avec galerie photos, plans et équipements détaillés.
- **Moteur de réservation intelligent & dynamique** :
  - Calcul instantané des nuitées et tarifications.
  - Sélection d'options sur-mesure (*Petit-déjeuner, Transfert aéroport, Soins Spa botaniques, Dîner romantique, Sortie Yacht privée, etc.*).
  - Gestion des codes promotionnels (pourcentage ou montant fixe) avec validation temps réel.
  - Attribution automatique d'un compte et d'un **Code Client unique** sécurisé (`CLI-2026-XXXX`).
- **Espace Personnel Client (Mon Compte)** :
  - Suivi des réservations actives, passées et demandes spéciales.
  - Modification et annulation autonomes de séjours avec notification de l'équipe.
  - Programme de fidélité & avantages statut VIP.
  - Système de renouvellement sécurisé du Code Client et changement d'email sous validation **OTP**.
  - Dépôt d'avis clients certifiés post-séjour.
  - Facturation officielle et reçu direct téléchargeable / imprimable.
- **Conciergerie & Room Service Connecté** :
  - Carte gastronomique et soins en chambre.
  - Vérification automatique de séjour actif (*règle métier Check-in*).
  - Suivi en temps réel de l'état de préparation (*Reçue &rarr; En Cuisine &rarr; Livrée*).
- **Demandes de Devis Événements & Séminaires** :
  - Formulaire dédié pour mariages, séminaires d'entreprise et réceptions privées avec suivi de dossier.

---

### 🛡️ Côté Administration (Back-Office)
- **Tableau de bord dynamique** : KPI en temps réel (chiffre d'affaires, taux d'occupation, réservations du mois, alertes conciergerie).
- **Gestion des Réservations & Planning** :
  - Validation, modification, check-in (*en séjour*), check-out et annulation.
  - Export complet des réservations au format CSV / Excel avec protection contre l'injection de formules (CWE-1236).
  - Calendrier interactif des disponibilités et blocage de chambres.
- **Gestion du Catalogue & Housekeeping** :
  - Ajout/édition de chambres, tarifs, photos et équipements.
  - Suivi du statut de ménage / entretien des chambres (*Propre, À nettoyer, En maintenance*).
- **Gestion des Commandes Room Service** :
  - Traitement des commandes en cuisine avec notification WhatsApp directe et email client.
- **Modération des Avis Clients** :
  - Validation des avis et publication de réponses officielles de l'établissement.
- **Gestion des Devis Événements** :
  - Chiffrage, rédaction de propositions commerciales et envoi de devis par email.
- **Contrôle d'Accès RBAC & Gestion d'Équipe** :
  - Gestion multi-administrateurs avec attribution fine des permissions par module.
  - Verrouillage exclusif des droits Super Admin.

---

## 🔒 Sécurité & Conformité OWASP

- **Protection Anti-CSRF** : Jetons cryptographiques uniques et validation systématique de toutes les requêtes POST.
- **Défense Anti-Brute-Force OTP & Login** :
  - Révocation automatique et blocage du code OTP après 5 tentatives erronées.
  - Comparaison en temps constant avec `hash_equals()`.
  - Verrouillage temporaire après échecs répétés de connexion.
- **Protection contre l'Élévation de Privilèges** : Contrôle strict RBAC (`AdminAuth`).
- **Prévention CSV Injection (CWE-1236)** : Neutralisation des cellules contenant des formules Excel malveillantes.
- **Gestion Sécurisée des Variables d'Environnement** : Clés API et identifiants isolés dans `.env` et exclus de Git.
- **En-têtes HTTP de Sécurité** : `X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy` et cookies `HttpOnly` / `SameSite=Lax`.

---

## 🛠️ Stack Technologique

- **Backend** : PHP 8.2+ (Architecture Orientée Objet, PDO, Prepared Statements)
- **Base de Données** : MySQL / MariaDB (Collation `utf8mb4_unicode_ci`, intégrité référentielle, indexations)
- **Frontend** : HTML5 sémantique, Vanilla CSS (Design System dynamique via CSS Tokens, Cormorant Garamond & Jost, Dark & Gold Glassmorphism), JavaScript ES6+ (Fetch API, AJAX)
- **Mailing Transactionnel** : API REST Brevo v3 (Emails HTML responsive, accusés de réception, alertes admin)

---

## 🚀 Installation & Déploiement Local

1. **Cloner le dépôt** :
   ```bash
   git clone https://github.com/BigMuzvn/SEGURO-HOTEL.git
   cd SEGURO-HOTEL
   ```

2. **Configurer l'environnement** :
   ```bash
   cp .env.example .env
   ```
   Renseignez vos identifiants MySQL, votre marque d'hôtel et votre clé API Brevo dans le fichier `.env`.

3. **Importer la base de données** :
   - Créez la base de données `seguro_hotel`.
   - Importez le fichier `database/seguro_hotel.sql`.

4. **Lancer le serveur** :
   - Démarrez votre environnement WAMP / XAMPP / LAMP ou utilisez le serveur intégré PHP :
   ```bash
   php -S localhost:8000
   ```

---

## 📄 Licence
Ce projet est développé sous licence privée pour l'**Hôtel SEGURO**. Tous droits réservés.
