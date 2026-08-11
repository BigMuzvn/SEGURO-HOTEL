# 🏨 Hôtel SEGURO — Plateforme de Réservation & Conciergerie de Luxe

> **La Sérénité · La Qualité · La Confiance**  
> *Plateforme web hôtelière haut de gamme développée avec une architecture sécurisée, un système de réservation temps réel, un module de Room Service connecté, un espace client VIP et un back-office d'administration complet.*

---

## 🌟 Présentation du Projet

**Hôtel SEGURO** est une solution hôtelière complète combinant une expérience utilisateur immersive et raffinée (inspirée des standards de l'hôtellerie 5 étoiles) et une suite d'outils de gestion opérationnelle puissante pour l'équipe hôtelière.

Situé sur la côte togolaise (Agbodrafo / Aného), l'établissement propose un parcours 100% digitalisé allant de la réservation avec options personnalisées jusqu'à la commande Room Service en chambre avec suivi en direct.

---

## ✨ Fonctionnalités Principales

### 👤 Côté Client (Front-Office)
- **Catalogue d'hébergements haut de gamme** : Suites, chambres supérieures, villas privées avec galerie photos, plans et équipements détaillés.
- **Moteur de réservation intelligent & dynamique** :
  - Calcul instantané des nuitées et tarifications.
  - Sélection d'options sur-mesure (*Petit-déjeuner, Transfert aéroport, Soins Spa botaniques, Dîner romantique, Sortie Yacht privée, etc.*).
  - Gestion des codes promotionnels (pourcentage ou montant fixe) avec validation temps réel.
  - Attribution automatique d'un compte et d'un **Code Client unique** sécurisé.
- **Espace Personnel Client (Mon Compte)** :
  - Suivi des réservations actives, passées et demandes spéciales.
  - Modification et annulation autonomes de séjours avec notification de l'équipe.
  - Programme de fidélité & avantages statut VIP.
  - Système de renouvellement sécurisé du Code Client et changement d'email sous validation **OTP**.
  - Dépôt d'avis clients certifiés post-séjour.
  - Facturation officielle et reçu direct téléchargeable.
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
  - Export complet des réservations au format CSV / Excel avec protection contre l'injection de formules.
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
- **Frontend** : HTML5 sémantique, Vanilla CSS (Design System sur-mesure, Cormorant Garamond & Jost, Dark & Gold Glassmorphism), JavaScript ES6+ (Fetch API, AJAX)
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
   Renseignez vos identifiants MySQL et votre clé API Brevo dans le fichier `.env`.

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
