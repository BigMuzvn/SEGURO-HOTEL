<?php
/**
 * ════════════════════════════════════════════════════════
 * CLASS MAIL — Envoi d'emails HTML Luxe Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/config.php';

class Mail {

    private static function getApiKey(): string {
        return defined('BREVO_API_KEY') ? BREVO_API_KEY : (getenv('BREVO_API_KEY') ?: '');
    }

    private static function getFromName(): string {
        return defined('BREVO_SENDER_NAME') ? BREVO_SENDER_NAME : (getenv('BREVO_SENDER_NAME') ?: "Hôtel SEGURO — Conciergerie");
    }

    private static function getFromEmail(): string {
        return defined('BREVO_SENDER_EMAIL') ? BREVO_SENDER_EMAIL : (getenv('BREVO_SENDER_EMAIL') ?: "reservations@hotelseguro.com");
    }

    /**
     * Envoie un email au format HTML via l'API Brevo (avec fallback mail standard)
     */
    public static function send($toEmail, $subject, $htmlContent): bool {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log("Mail::send invalid email: " . var_export($toEmail, true));
            return false;
        }

        $fullHtml = self::wrapTemplate($subject, $htmlContent);
        $apiKey = self::getApiKey();

        // 1. Essai d'envoi prioritaire via l'API Brevo (HTTPS REST)
        if (!empty($apiKey)) {
            $brevoSuccess = self::sendViaBrevoApi($toEmail, $subject, $fullHtml);
            if ($brevoSuccess) {
                error_log("BREVO MAIL SUCCESS [TO: {$toEmail}] [SUBJECT: {$subject}]");
                return true;
            }
        }

        // 2. Fallback via mail() standard PHP
        $fromName = self::getFromName();
        $fromEmail = self::getFromEmail();
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . mb_encode_mimeheader($fromName, "UTF-8") . " <" . $fromEmail . ">\r\n";
        $headers .= "Reply-To: " . $fromEmail . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

        error_log("FALLBACK PHP MAIL [TO: {$toEmail}] [SUBJECT: {$subject}]");
        return @mail($toEmail, mb_encode_mimeheader($subject, "UTF-8"), $fullHtml, $headers);
    }

    /**
     * Appel de l'API REST Transactionnelle Brevo v3
     */
    private static function sendViaBrevoApi($toEmail, $subject, $fullHtml): bool {
        $url = "https://api.brevo.com/v3/smtp/email";
        $payload = [
            "sender" => [
                "name" => self::getFromName(),
                "email" => self::getFromEmail()
            ],
            "to" => [
                [
                    "email" => $toEmail
                ]
            ],
            "subject" => $subject,
            "htmlContent" => $fullHtml
        ];

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "accept: application/json",
            "api-key: " . self::getApiKey(),
            "content-type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        error_log("Brevo API error (HTTP {$httpCode}): " . $response . ($curlErr ? " | Curl: " . $curlErr : ""));
        return false;
    }

    /**
     * Template HTML global aux couleurs de l'Hôtel SEGURO (#1a3a2a & #c9a84c)
     */
    private static function wrapTemplate($title, $bodyContent): string {
        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{$title}</title>
<style>
    body { margin: 0; padding: 0; background-color: #f4f2eb; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; color: #2b2b2b; }
    .email-container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
    .email-header { background: #1a3a2a; padding: 40px 30px; text-align: center; color: #ffffff; }
    .header-logo { font-family: Georgia, serif; font-size: 28px; font-weight: bold; letter-spacing: 4px; color: #c9a84c; text-transform: uppercase; margin: 0; }
    .header-tagline { font-size: 11px; letter-spacing: 2px; color: rgba(255,255,255,0.7); text-transform: uppercase; margin-top: 6px; }
    .gold-divider { height: 2px; background: linear-gradient(90deg, transparent, #c9a84c, transparent); margin: 20px 0; }
    .email-body { padding: 40px 35px; line-height: 1.7; font-size: 15px; color: #444444; }
    .greeting { font-family: Georgia, serif; font-size: 22px; color: #1a3a2a; margin-bottom: 18px; }
    .code-box { background: #fdfbf7; border: 2px dashed #c9a84c; border-radius: 10px; padding: 25px; text-align: center; margin: 25px 0; }
    .code-title { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #888888; margin-bottom: 8px; }
    .code-value { font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #1a3a2a; font-family: monospace; }
    .btn-action { display: inline-block; background: #1a3a2a; color: #c9a84c !important; text-decoration: none; padding: 14px 32px; border-radius: 6px; font-weight: bold; font-size: 14px; letter-spacing: 1px; text-transform: uppercase; margin-top: 20px; transition: background 0.3s; }
    .info-card { background: #faf8f3; border-left: 4px solid #c9a84c; padding: 18px 20px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; color: #555555; }
    .email-footer { background: #12281d; padding: 30px; text-align: center; color: rgba(255,255,255,0.6); font-size: 12px; line-height: 1.6; }
    .footer-links a { color: #c9a84c; text-decoration: none; margin: 0 10px; }
</style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <div class="header-logo">Hôtel Seguro</div>
            <div class="header-tagline">Lomé · Agbodrafo · Togo</div>
            <div class="gold-divider"></div>
        </div>

        <div class="email-body">
            {$bodyContent}
        </div>

        <div class="email-footer">
            <p style="margin:0 0 10px 0; color:#ffffff; font-weight:bold;">Hôtel SEGURO ****</p>
            <p style="margin:0 0 15px 0;">Agbodrafo, entrée d'Aného, Togo — Afrique de l'Ouest<br>Tél : +228 00 00 00 00 | Email : contact@hotelseguro.com</p>
            <div class="footer-links">
                <a href="https://hotelseguro.com/pages/chambres.php">Nos Chambres</a> ·
                <a href="https://hotelseguro.com/pages/services.php">Services</a> ·
                <a href="https://hotelseguro.com/pages/contact.php">Contact</a>
            </div>
            <p style="margin-top:20px; font-size:11px; color:rgba(255,255,255,0.4);">&copy; 2026 Hôtel SEGURO. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Email 1 : Code OTP de vérification d'adresse email
     */
    public static function sendOTP($toEmail, $nomPrenom, $otpCode): bool {
        $subject = "Votre code de vérification OTP — Hôtel SEGURO";
        $content = <<<HTML
        <div class="greeting">Bonjour {$nomPrenom},</div>
        <p>Afin de valider votre adresse email et de sécuriser l'accès à votre espace personnel client, voici votre code de vérification à usage unique (OTP) :</p>
        
        <div class="code-box">
            <div class="code-title">Code de vérification OTP</div>
            <div class="code-value">{$otpCode}</div>
        </div>

        <p style="font-size:13px; color:#777; text-align:center;">Ce code est valable pendant <strong>15 minutes</strong>. Ne le partagez avec personne.</p>
        
        <div class="info-card">
            💡 <strong>Pourquoi vérifier votre email ?</strong><br>
            Un compte vérifié vous donne accès aux offres promotionnelles exclusives, aux codes de réduction privés et garantit la sécurité de vos données de réservation.
        </div>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 2 : Bienvenue & Code Client
     */
    public static function sendWelcomeClientCode($toEmail, $nomPrenom, $codeClient): bool {
        $subject = "Bienvenue à l'Hôtel SEGURO — Vos identifiants client";
        $content = <<<HTML
        <div class="greeting">Bienvenue {$nomPrenom},</div>
        <p>Nous sommes ravis de vous compter parmi les hôtes privilégiés de l'Hôtel SEGURO.</p>
        <p>Votre compte client a été créé avec succès. Voici votre code d'accès personnel :</p>
        
        <div class="code-box">
            <div class="code-title">Votre Code Client Personnel</div>
            <div class="code-value">{$codeClient}</div>
        </div>

        <p style="text-align:center;">
            <a href="http://localhost/ACATHON/pages/connexion-client.php" class="btn-action">Accéder à mon espace client</a>
        </p>

        <p style="margin-top:25px; font-size:13px; color:#666;">Conservez ce code précieusement. Associé à votre adresse email (<strong>{$toEmail}</strong>), il vous permet de gérer vos réservations à tout moment.</p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 3 : Confirmation de création de réservation (Client)
     */
    public static function sendReservationConfirmation($toEmail, $nomPrenom, $reference, $chambreNom, $dateArrivee, $dateDepart, $prixTotalFormatted, $optionsList = [], $promoCode = null, $paiementInfo = null, $demandesSpeciales = null): bool {
        $subject = "Réservation enregistrée [{$reference}] — Hôtel SEGURO";
        $arrFmt = date('d/m/Y', strtotime($dateArrivee));
        $depFmt = date('d/m/Y', strtotime($dateDepart));
        
        $optionsHtml = '';
        if (!empty($optionsList)) {
            $optItems = [];
            foreach ($optionsList as $opt) {
                $nom = is_array($opt) ? ($opt['nom'] ?? '') : (string)$opt;
                $prix = is_array($opt) && isset($opt['prix_unitaire']) ? ' (' . number_format($opt['prix_unitaire'], 0, ',', ' ') . ' FCFA)' : '';
                $optItems[] = "• " . htmlspecialchars($nom) . $prix;
            }
            $optionsHtml = '<tr><td style="padding:6px 0; color:#777; vertical-align:top;">Options & Services :</td><td style="font-weight:600; color:#1a3a2a; line-height:1.4;">' . implode('<br>', $optItems) . '</td></tr>';
        }

        $promoHtml = '';
        if (!empty($promoCode)) {
            $promoHtml = '<tr><td style="padding:6px 0; color:#777;">Code Promo :</td><td style="font-weight:bold; color:#28a745;">' . htmlspecialchars($promoCode) . '</td></tr>';
        }

        $paiementHtml = '';
        if (!empty($paiementInfo)) {
            $paiementHtml = '<tr><td style="padding:6px 0; color:#777;">Règlement :</td><td style="font-weight:600; color:#2e7d32;">' . htmlspecialchars($paiementInfo) . '</td></tr>';
        }

        $demandesHtml = '';
        if (!empty($demandesSpeciales)) {
            $demandesHtml = '<tr><td style="padding:6px 0; color:#777; vertical-align:top;">Demandes particulières :</td><td style="color:#555; font-style:italic;">' . nl2br(htmlspecialchars($demandesSpeciales)) . '</td></tr>';
        }

        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomPrenom},</div>
        <p>Nous avons bien reçu votre demande de réservation et notre équipe la traite actuellement avec la plus grande attention.</p>
        
        <div class="info-card">
            <h4 style="margin:0 0 12px 0; color:#1a3a2a; border-bottom:1px solid #e0dacb; padding-bottom:6px;">Récapitulatif de votre séjour</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:6px 0; color:#777; width:40%;">Référence :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:6px 0; color:#777;">Hébergement :</td><td style="font-weight:bold;">{$chambreNom}</td></tr>
                <tr><td style="padding:6px 0; color:#777;">Période :</td><td style="font-weight:bold;">Du {$arrFmt} au {$depFmt}</td></tr>
                {$optionsHtml}
                {$promoHtml}
                {$paiementHtml}
                {$demandesHtml}
                <tr><td style="padding:8px 0 4px 0; color:#777; border-top:1px dashed #d4c8b0;">Montant total :</td><td style="font-weight:bold; color:#c9a84c; font-size:17px; border-top:1px dashed #d4c8b0;">{$prixTotalFormatted}</td></tr>
            </table>
        </div>

        <p style="font-size:14px; color:#555;">Notre équipe validera votre séjour sous <strong>24 heures</strong>. Vous recevrez une notification de confirmation dès sa validation.</p>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/pages/mon-compte.php" class="btn-action">Consulter ma réservation &amp; Facture</a>
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 4 : Notification de nouvelle réservation aux Administrateurs
     */
    public static function sendAdminNewReservationNotification($adminEmails, $reference, $clientNom, $clientEmail, $chambreNom, $dateArrivee, $dateDepart, $prixTotalFormatted, $optionsList = [], $promoCode = null, $paiementInfo = null, $demandesSpeciales = null): bool {
        $subject = "[ADMIN] Nouvelle réservation reçue [{$reference}] — Hôtel SEGURO";
        $arrFmt  = date('d/m/Y', strtotime($dateArrivee));
        $depFmt  = date('d/m/Y', strtotime($dateDepart));

        $optionsHtml = '';
        if (!empty($optionsList)) {
            $optItems = [];
            foreach ($optionsList as $opt) {
                $nom = is_array($opt) ? ($opt['nom'] ?? '') : (string)$opt;
                $prix = is_array($opt) && isset($opt['prix_unitaire']) ? ' (' . number_format($opt['prix_unitaire'], 0, ',', ' ') . ' FCFA)' : '';
                $optItems[] = "• " . htmlspecialchars($nom) . $prix;
            }
            $optionsHtml = '<tr><td style="padding:6px 0; color:#777; vertical-align:top;">Options & Services :</td><td style="font-weight:bold; color:#1a3a2a; line-height:1.4;">' . implode('<br>', $optItems) . '</td></tr>';
        } else {
            $optionsHtml = '<tr><td style="padding:6px 0; color:#777;">Options & Services :</td><td style="color:#888;">Aucune option sélectionnée</td></tr>';
        }

        $promoHtml = '';
        if (!empty($promoCode)) {
            $promoHtml = '<tr><td style="padding:6px 0; color:#777;">Code Promo appliqué :</td><td style="font-weight:bold; color:#28a745;">' . htmlspecialchars($promoCode) . '</td></tr>';
        }

        $paiementHtml = '';
        if (!empty($paiementInfo)) {
            $paiementHtml = '<tr><td style="padding:6px 0; color:#777;">Paiement :</td><td style="font-weight:bold; color:#2e7d32;">' . htmlspecialchars($paiementInfo) . '</td></tr>';
        }

        $demandesHtml = '';
        if (!empty($demandesSpeciales)) {
            $demandesHtml = '<tr><td style="padding:6px 0; color:#777; vertical-align:top;">Demandes particulières :</td><td style="color:#555; font-style:italic;">' . nl2br(htmlspecialchars($demandesSpeciales)) . '</td></tr>';
        }

        $content = <<<HTML
        <div class="greeting">Alerte Réception / Administration</div>
        <p>Une nouvelle réservation vient d'être enregistrée sur la plateforme. Voici le détail complet des prestations :</p>

        <div class="info-card">
            <h4 style="margin:0 0 12px 0; color:#1a3a2a; border-bottom:1px solid #e0dacb; padding-bottom:6px;">Détails complets du dossier</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:6px 0; color:#777; width:40%;">Référence :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:6px 0; color:#777;">Client :</td><td style="font-weight:bold;">{$clientNom} &lt;{$clientEmail}&gt;</td></tr>
                <tr><td style="padding:6px 0; color:#777;">Chambre :</td><td style="font-weight:bold;">{$chambreNom}</td></tr>
                <tr><td style="padding:6px 0; color:#777;">Période du séjour :</td><td style="font-weight:bold;">Du {$arrFmt} au {$depFmt}</td></tr>
                {$optionsHtml}
                {$promoHtml}
                {$paiementHtml}
                {$demandesHtml}
                <tr><td style="padding:8px 0 4px 0; color:#777; border-top:1px dashed #d4c8b0;">Montant total :</td><td style="font-weight:bold; color:#c9a84c; font-size:17px; border-top:1px dashed #d4c8b0;">{$prixTotalFormatted}</td></tr>
            </table>
        </div>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/admin/reservations.php" class="btn-action">Gérer dans le Panneau Admin</a>
        </p>
HTML;
        $allSuccess = true;
        if (is_array($adminEmails)) {
            foreach ($adminEmails as $e) {
                if (!empty($e)) {
                    usleep(150000);
                    $ok = self::send($e, $subject, $content);
                    if (!$ok) $allSuccess = false;
                }
            }
            return $allSuccess;
        } elseif (!empty($adminEmails)) {
            return self::send($adminEmails, $subject, $content);
        }
        return false;
    }

    /**
     * Email 5 : Mise à jour de statut (Validation / Annulation)
     */
    public static function sendStatusUpdate($toEmail, $nomPrenom, $reference, $newStatut, $chambreNom, $noteAdmin = null): bool {
        $isValidee = ($newStatut === 'validee');
        $statutTxt = $isValidee ? "VALIDÉE ✓" : "ANNULÉE";
        $subject   = "Statut de votre réservation [{$reference}] : {$statutTxt} — Hôtel SEGURO";
        $colorStat = $isValidee ? "#28a745" : "#dc3545";
        $introText = $isValidee 
            ? "Nous avons le plaisir de vous informer que votre réservation a été <strong>validée avec succès</strong> par l'équipe de l'Hôtel SEGURO !" 
            : "Votre réservation a été annulée. Si vous pensez qu'il s'agit d'une erreur, contactez notre équipe.";

        $noteHtml = '';
        if (!empty($noteAdmin)) {
            $noteEscaped = nl2br(htmlspecialchars($noteAdmin));
            $noteHtml = <<<HTML
            <div class="info-card" style="border-left-color: #1a3a2a; background: #fdfbf7;">
                <strong style="color: #1a3a2a;">🛎️ Note de la Conciergerie / Réception :</strong><br>
                <div style="margin-top: 6px; color: #444; font-style: italic;">{$noteEscaped}</div>
            </div>
HTML;
        }

        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomPrenom},</div>
        <p>{$introText}</p>

        <div class="code-box" style="border-color: {$colorStat};">
            <div class="code-title">Statut de la réservation {$reference}</div>
            <div class="code-value" style="color: {$colorStat}; font-size: 24px;">{$statutTxt}</div>
            <div style="font-size:14px; color:#555; margin-top:8px;">Hébergement : <strong>{$chambreNom}</strong></div>
        </div>

        {$noteHtml}

        <p style="text-align:center;">
            <a href="http://localhost/ACATHON/pages/mon-compte.php" class="btn-action">Voir les détails de mon séjour</a>
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 6 : Alerte Admin lors d'une MODIFICATION de réservation par le client
     */
    public static function sendAdminReservationModificationNotification($adminEmails, $reference, $clientNom, $clientEmail, $chambreNom, $dateArrivee, $dateDepart, $prixTotalFormatted, $demandes = ''): bool {
        $subject = "[ADMIN] Réservation MODIFIÉE par le client [{$reference}] — Hôtel SEGURO";
        $arrFmt  = date('d/m/Y', strtotime($dateArrivee));
        $depFmt  = date('d/m/Y', strtotime($dateDepart));

        $demandesHtml = !empty($demandes) ? "<tr><td style='padding:4px 0; color:#777;'>Demandes :</td><td style='font-weight:bold;'>" . htmlspecialchars($demandes) . "</td></tr>" : "";

        $content = <<<HTML
        <div class="greeting">Alerte Administration — Modification</div>
        <p>Le client <strong>{$clientNom}</strong> vient de <strong>modifier</strong> sa réservation :</p>

        <div class="info-card" style="border-left-color: #2980b9;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:4px 0; color:#777;">Référence :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Client :</td><td style="font-weight:bold;">{$clientNom} ({$clientEmail})</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Chambre :</td><td style="font-weight:bold;">{$chambreNom}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Nouvelles Dates :</td><td style="font-weight:bold; color:#2980b9;">Du {$arrFmt} au {$depFmt}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Nouveau Total :</td><td style="font-weight:bold; color:#c9a84c;">{$prixTotalFormatted}</td></tr>
                {$demandesHtml}
            </table>
        </div>

        <p style="text-align:center;">
            <a href="http://localhost/ACATHON/admin/reservations.php" class="btn-action">Vérifier et Revalider sur le Dashboard</a>
        </p>
HTML;
        $allSuccess = true;
        if (is_array($adminEmails)) {
            foreach ($adminEmails as $e) {
                if (!empty($e)) {
                    usleep(150000);
                    $ok = self::send($e, $subject, $content);
                    if (!$ok) $allSuccess = false;
                }
            }
            return $allSuccess;
        } elseif (!empty($adminEmails)) {
            return self::send($adminEmails, $subject, $content);
        }
        return false;
    }

    /**
     * Email 7 : Alerte Admin lors d'une ANNULATION de réservation par le client
     */
    public static function sendAdminReservationCancellationNotification($adminEmails, $reference, $clientNom, $clientEmail, $chambreNom, $dateArrivee, $dateDepart): bool {
        $subject = "[ADMIN] Réservation ANNULÉE par le client [{$reference}] — Hôtel SEGURO";
        $arrFmt  = date('d/m/Y', strtotime($dateArrivee));
        $depFmt  = date('d/m/Y', strtotime($dateDepart));

        $content = <<<HTML
        <div class="greeting">Alerte Administration — Annulation</div>
        <p>Le client <strong>{$clientNom}</strong> a <strong>annulé</strong> sa réservation :</p>

        <div class="info-card" style="border-left-color: #c0392b;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:4px 0; color:#777;">Référence :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Client :</td><td style="font-weight:bold;">{$clientNom} ({$clientEmail})</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Chambre libérée :</td><td style="font-weight:bold;">{$chambreNom}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Période initiale :</td><td style="font-weight:bold;">Du {$arrFmt} au {$depFmt}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Statut actuel :</td><td style="font-weight:bold; color:#c0392b;">ANNULÉE PAR LE CLIENT</td></tr>
            </table>
        </div>

        <p style="text-align:center;">
            <a href="http://localhost/ACATHON/admin/reservations.php" class="btn-action">Voir sur le Dashboard Admin</a>
        </p>
HTML;
        $allSuccess = true;
        if (is_array($adminEmails)) {
            foreach ($adminEmails as $e) {
                if (!empty($e)) {
                    usleep(150000);
                    $ok = self::send($e, $subject, $content);
                    if (!$ok) $allSuccess = false;
                }
            }
            return $allSuccess;
        } elseif (!empty($adminEmails)) {
            return self::send($adminEmails, $subject, $content);
        }
        return false;
    }

    /**
     * Email 8 : Invitation automatique à laisser un avis suite au Check-out
     */
    public static function sendReviewInvitation($toEmail, $nomPrenom, $reference, $chambreNom): bool {
        $subject = "Votre expérience à l'Hôtel SEGURO — Donnez votre avis [{$reference}]";
        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomPrenom},</div>
        <p>Nous espérons que votre séjour au sein de notre établissement (<strong>{$chambreNom}</strong>) s'est déroulé à la hauteur de vos attentes.</p>
        <p>L'excellence et la satisfaction de nos hôtes sont au cœur de nos priorités. Pourriez-vous nous accorder quelques instants pour partager votre expérience ?</p>
        
        <div class="code-box" style="border-color: #c9a84c; background: #faf8f3;">
            <div class="code-title">Votre avis compte précieusement</div>
            <div style="font-size: 26px; color: #c9a84c; margin: 8px 0;">★★★★★</div>
            <div style="font-size: 14px; color: #555;">Notez votre séjour et laissez vos impressions en 1 clic</div>
        </div>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/pages/mon-compte.php" class="btn-action">⭐ Déposer mon avis maintenant</a>
        </p>

        <p style="margin-top:25px; font-size:13px; color:#777; text-align:center;">
            En laissant votre avis vérifié, vous bénéficierez également d'avantages exclusifs pour votre prochain séjour.
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 9 : Récupération du Code Client / Identifiants
     */
    public static function sendClientCodeRecovery($toEmail, $nomPrenom, $codeClient): bool {
        $subject = "Récupération de vos identifiants — Hôtel SEGURO [{$codeClient}]";
        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomPrenom},</div>
        <p>Vous avez demandé la récupération de vos identifiants de connexion à votre espace personnel <strong>Hôtel SEGURO</strong>.</p>
        
        <div class="code-box">
            <div class="code-title">Votre Code Client Confidentiel</div>
            <div class="otp-code">{$codeClient}</div>
            <div style="font-size: 13px; color: #777; margin-top: 8px;">Utilisez ce code ainsi que votre email pour vous connecter à votre compte.</div>
        </div>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/pages/connexion-client.php" class="btn-action">Se connecter à mon compte</a>
        </p>

        <p style="margin-top:25px; font-size:13px; color:#777; text-align:center;">
            Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet email en toute sécurité.
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 10 : Demande de Nouveau Code Client
     */
    public static function sendNewCodeClientRequest($toEmail, $nomPrenom, $newCodeClient): bool {
        $subject = "Votre nouveau Code Client — Hôtel SEGURO [{$newCodeClient}]";
        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomPrenom},</div>
        <p>Vous avez demandé la génération d'un <strong>nouveau Code Client</strong> pour votre espace personnel Hôtel SEGURO.</p>
        
        <div class="code-box">
            <div class="code-title">Votre Nouveau Code Client</div>
            <div class="otp-code">{$newCodeClient}</div>
            <div style="font-size: 13px; color: #777; margin-top: 8px;">Saisissez ce code sur votre espace personnel pour valider définitivement le renouvellement.</div>
        </div>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/pages/mon-compte.php#securite" class="btn-action">Confirmer sur mon compte</a>
        </p>

        <p style="margin-top:25px; font-size:13px; color:#777; text-align:center;">
            Si vous n'êtes pas à l'origine de cette demande, votre ancien code client reste actif tant que vous ne confirmez pas ce nouveau code.
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 11 : Confirmation de réception de demande de Devis Événement (Prospect)
     */
    public static function sendEventQuoteConfirmation($toEmail, $nomContact, $reference, $typeEvent, $dateEvent): bool {
        $subject = "Votre demande de devis événement — Hôtel SEGURO [{$reference}]";
        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomContact},</div>
        <p>Nous avons bien reçu votre demande de devis concernant votre événement : <strong>{$typeEvent}</strong> prévu le <strong>{$dateEvent}</strong>.</p>
        
        <div class="info-card" style="border-left-color: #c9a84c;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:4px 0; color:#777;">Référence devis :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Type d'événement :</td><td style="font-weight:bold;">{$typeEvent}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Date souhaitée :</td><td style="font-weight:bold;">{$dateEvent}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Délai de traitement :</td><td style="font-weight:bold; color:#2d5c40;">Sous 24h ouvrées</td></tr>
            </table>
        </div>

        <p>Notre équipe Événements & Séminaires analyse actuellement vos besoins spécifiques et vous contactera très prochainement avec une proposition sur-mesure.</p>

        <p style="margin-top:25px; font-size:13px; color:#777; text-align:center;">
            Pour toute question urgente, notre conciergerie reste joignable au +228 00 00 00 00.
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 12 : Alerte Admin Nouvelle Demande de Devis Événement
     */
    public static function sendAdminNewEventQuoteNotification($adminEmails, $reference, $nomContact, $entreprise, $email, $tel, $typeEvent, $espace, $dateEvent, $nbParticipants): bool {
        $subject = "[DEVIS ÉVÉNEMENT] Nouvelle demande {$typeEvent} - {$reference}";
        $content = <<<HTML
        <div class="greeting">Alerte Direction &amp; Événements,</div>
        <p>Une nouvelle demande de devis événement a été soumise sur le site de l'Hôtel SEGURO :</p>
        
        <div class="info-card" style="border-left-color: #1a3a2a;">
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:4px 0; color:#777;">Référence :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Contact :</td><td style="font-weight:bold;">{$nomContact} " . ($entreprise ? "({$entreprise})" : "") . "</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Email :</td><td><a href="mailto:{$email}">{$email}</a></td></tr>
                <tr><td style="padding:4px 0; color:#777;">Téléphone :</td><td style="font-weight:bold;"><a href="tel:{$tel}">{$tel}</a></td></tr>
                <tr><td style="padding:4px 0; color:#777;">Type d'événement :</td><td style="font-weight:bold;">{$typeEvent}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Espace souhaité :</td><td style="font-weight:bold;">{$espace}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Date &amp; Participants :</td><td style="font-weight:bold;">Le {$dateEvent} — {$nbParticipants} personnes</td></tr>
            </table>
        </div>

        <p style="text-align:center;">
            <a href="http://localhost/ACATHON/admin/dashboard.php" class="btn-action">Voir sur le Dashboard</a>
        </p>
HTML;
        if (is_array($adminEmails)) {
            foreach ($adminEmails as $e) {
                if (!empty($e)) {
                    usleep(150000);
                    self::send($e, $subject, $content);
                }
            }
            return true;
        } elseif (!empty($adminEmails)) {
            return self::send($adminEmails, $subject, $content);
        }
        return false;
    }

    /**
     * Email 13 : Réponse & Notification de Devis Événement (Prospect)
     */
    public static function sendEventQuoteResponse($toEmail, $nomContact, $reference, $typeEvent, $statut, $messageReponse = '', $budgetEstime = null): bool {
        $statutLibelle = 'En attente de traitement';
        $statutColor = '#c9a84c';
        $statutBg = '#faf8f3';

        if ($statut === 'traite') {
            $statutLibelle = 'Devis Traité & Proposition Disponible';
            $statutColor = '#2e7d32';
            $statutBg = '#e8f5e9';
        } elseif ($statut === 'rejete') {
            $statutLibelle = 'Demande Non Retenue / Espace Indisponible';
            $statutColor = '#c62828';
            $statutBg = '#ffebee';
        }

        $subject = "Mise à jour de votre demande de devis [{$reference}] — Hôtel SEGURO";

        $reponseHtml = '';
        if (!empty($messageReponse)) {
            $reponseHtml = <<<HTML
            <div style="margin: 20px 0; background:#ffffff; border:1px solid #e0dacb; border-radius:8px; padding:18px 20px;">
                <h4 style="margin:0 0 10px 0; color:#1a3a2a; font-size:14px; font-weight:bold; border-bottom:1px solid #f0ede6; padding-bottom:6px;">Message de notre Direction Événements :</h4>
                <p style="margin:0; font-size:14px; color:#333; line-height:1.6; font-style:italic;">
                    "{$messageReponse}"
                </p>
            </div>
HTML;
        }

        $budgetHtml = '';
        if (!empty($budgetEstime)) {
            $budgetHtml = "<tr><td style=\"padding:5px 0; color:#777;\">Estimation / Chiffrage :</td><td style=\"font-weight:bold; color:#1a3a2a;\">" . htmlspecialchars($budgetEstime) . "</td></tr>";
        }

        $content = <<<HTML
        <div class="greeting">Cher(e) {$nomContact},</div>
        <p>Nous faisons suite à votre demande de devis n° <strong>{$reference}</strong> concernant votre événement <strong>{$typeEvent}</strong> à l'Hôtel SEGURO.</p>
        
        <div style="background: {$statutBg}; border: 1.5px solid {$statutColor}; border-radius: 8px; padding: 14px 18px; margin: 20px 0; text-align: center;">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #666; margin-bottom: 4px;">État de votre dossier</div>
            <div style="font-size: 17px; font-weight: bold; color: {$statutColor};">{$statutLibelle}</div>
        </div>

        {$reponseHtml}

        <div class="info-card">
            <h4 style="margin:0 0 10px 0; color:#1a3a2a;">Rappel du projet</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                <tr><td style="padding:4px 0; color:#777; width:40%;">Référence :</td><td style="font-weight:bold; color:#1a3a2a;">{$reference}</td></tr>
                <tr><td style="padding:4px 0; color:#777;">Type d'événement :</td><td style="font-weight:bold;">{$typeEvent}</td></tr>
                {$budgetHtml}
            </table>
        </div>

        <p style="margin-top:20px;">Notre équipe commerciale reste à votre entière disposition pour tout ajustement ou pour finaliser la réservation de vos espaces.</p>

        <p style="text-align:center; margin-top:25px;">
            <a href="https://wa.me/22800000000?text=Bonjour,%20je%20vous%20contacte%20concernant%20mon%20devis%20{$reference}" class="btn-action">Contacter la Direction Événements</a>
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 14 : Confirmation de Commande Room Service (Client)
     */
    public static function sendRoomServiceConfirmation($toEmail, $clientNom, $reference, $chambreNumero, $items, $totalEstime, $instructions = null): bool {
        $subject = "Commande Room Service confirmée [{$reference}] — Hôtel SEGURO";

        $itemsRows = '';
        if (is_array($items)) {
            foreach ($items as $it) {
                $nom = htmlspecialchars($it['name'] ?? $it['titre'] ?? 'Article');
                $qty = intval($it['qty'] ?? $it['quantite'] ?? 1);
                $prix = floatval($it['price'] ?? $it['prix'] ?? 0);
                $st = number_format($prix * $qty, 0, ',', ' ') . ' F';
                $itemsRows .= "<tr><td style=\"padding:6px 0; border-bottom:1px solid #eee;\"><strong>{$nom}</strong> (x{$qty})</td><td style=\"padding:6px 0; border-bottom:1px solid #eee; text-align:right; font-weight:bold; color:#1a3a2a;\">{$st}</td></tr>";
            }
        }

        $instructionsHtml = '';
        if (!empty($instructions)) {
            $instructionsHtml = "<div style=\"margin-top:12px; font-size:13px; color:#666; font-style:italic;\"><strong>Instructions spéciales :</strong> " . nl2br(htmlspecialchars($instructions)) . "</div>";
        }

        $totalFmt = number_format($totalEstime, 0, ',', ' ') . ' FCFA';

        $content = <<<HTML
        <div class="greeting">Cher(e) {$clientNom},</div>
        <p>Votre commande Room Service a été enregistrée avec succès par notre équipe de restauration.</p>

        <div style="background: #faf8f3; border: 2px dashed #c9a84c; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0;">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #888;">Référence de commande</div>
            <div style="font-size: 24px; font-weight: bold; color: #1a3a2a; letter-spacing: 3px; font-family: monospace; margin: 4px 0;">{$reference}</div>
            <div style="font-size: 13px; color: #2d5c40; font-weight: 600;">Destination : Chambre {$chambreNumero} · Livraison estimée sous 25 à 35 min</div>
        </div>

        <div class="info-card">
            <h4 style="margin:0 0 10px 0; color:#1a3a2a;">Détail de votre plateau</h4>
            <table style="width:100%; border-collapse:collapse; font-size:14px;">
                {$itemsRows}
                <tr>
                    <td style="padding:10px 0 4px; font-weight:bold; font-size:15px; color:#1a3a2a;">Total Estimé :</td>
                    <td style="padding:10px 0 4px; font-weight:bold; font-size:16px; color:#c9a84c; text-align:right;">{$totalFmt}</td>
                </tr>
            </table>
            {$instructionsHtml}
        </div>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/pages/room-service.php?suivi={$reference}" class="btn-action">Suivre ma commande en direct</a>
        </p>

        <p style="margin-top:20px; font-size:13px; color:#777; text-align:center;">
            Le montant sera ajouté à la note de votre chambre ou réglé directement à la livraison. Bon appétit !
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }

    /**
     * Email 15 : Notification de Mise à Jour du Statut Room Service (Client)
     */
    public static function sendRoomServiceStatusUpdate($toEmail, $clientNom, $reference, $chambreNumero, $nouveauStatut): bool {
        $statutLibelle = 'En cours de traitement';
        $statutMessage = 'Votre commande est prise en charge par notre personnel.';
        $statutColor = '#c9a84c';

        if ($nouveauStatut === 'en_preparation') {
            $statutLibelle = 'En Cuisine / En Préparation 🍳';
            $statutMessage = 'Notre chef et sa brigade préparent vos plats avec les ingrédients les plus frais.';
            $statutColor = '#e67e22';
        } elseif ($nouveauStatut === 'livree') {
            $statutLibelle = 'Commande Prête & Livrée en Chambre 🛎️';
            $statutMessage = 'Notre maître d\'hôtel est en route pour servir votre commande dans votre chambre.';
            $statutColor = '#28a745';
        } elseif ($nouveauStatut === 'annulee') {
            $statutLibelle = 'Commande Annulée';
            $statutMessage = 'Votre commande Room Service a été annulée. Contactez la réception pour toute précision.';
            $statutColor = '#dc3545';
        }

        $subject = "Mise à jour Room Service [{$reference}] : {$statutLibelle}";

        $content = <<<HTML
        <div class="greeting">Cher(e) {$clientNom},</div>
        <p>Voici l'évolution en temps réel de votre commande Room Service pour la <strong>Chambre {$chambreNumero}</strong> :</p>

        <div style="background: #faf8f3; border-left: 5px solid {$statutColor}; border-radius: 8px; padding: 20px; margin: 20px 0;">
            <div style="font-size: 11px; text-transform: uppercase; letter-spacing: 2px; color: #888;">Statut actuel [Ref: {$reference}]</div>
            <div style="font-size: 20px; font-weight: bold; color: {$statutColor}; margin: 6px 0;">{$statutLibelle}</div>
            <p style="margin: 6px 0 0 0; font-size: 14px; color: #444;">{$statutMessage}</p>
        </div>

        <p style="text-align:center; margin-top:25px;">
            <a href="http://localhost/ACATHON/pages/room-service.php?suivi={$reference}" class="btn-action">Voir le suivi en direct</a>
        </p>

        <p style="margin-top:20px; font-size:13px; color:#777; text-align:center;">
            Pour toute demande complémentaire, notre conciergerie reste à votre écoute.
        </p>
HTML;
        return self::send($toEmail, $subject, $content);
    }
}
?>
