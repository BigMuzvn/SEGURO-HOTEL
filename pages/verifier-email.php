<?php
/**
 * ════════════════════════════════════════════════════════
 * VÉRIFICATION EMAIL (OTP) — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mail.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion-client.php');
    exit;
}

if (in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
    header('Location: ../admin/dashboard.php');
    exit;
}

$database = new Database();
$db       = $database->getConnection();
$user     = new User($db);

if (!$user->getById($_SESSION['user_id'])) {
    header('Location: deconnexion.php');
    exit;
}

$erreur = '';
$succes = '';

// Si l'email est déjà vérifié
if ($user->email_verified == 1) {
    $succes = "Votre adresse email est déjà vérifiée !";
}

// ── Traitement POST ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    if (!verify_csrf_token($csrfToken)) {
        $erreur = "Session de sécurité expirée ou invalide. Veuillez recharger la page.";
    } elseif (isset($_POST['action_verifier'])) {
        $otpSaisi = trim($_POST['otp_code'] ?? '');
        if (empty($otpSaisi)) {
            $erreur = "Veuillez saisir le code à 6 chiffres.";
        } else {
            $resVerify = $user->verifyOTP($otpSaisi);
            if ($resVerify['success']) {
                $succes = htmlspecialchars($resVerify['message']);
                $_SESSION['user_email_verified'] = 1;
                $user->getById($_SESSION['user_id']); // recharger l'objet
            } else {
                $erreur = htmlspecialchars($resVerify['message']);
            }
        }
    } elseif (isset($_POST['action_renvoyer'])) {
        // Anti-spam cooldown (60 secondes)
        $lastResend = $_SESSION['last_otp_resend'] ?? 0;
        if (time() - $lastResend < 60) {
            $waitSecs = 60 - (time() - $lastResend);
            $erreur = "Veuillez patienter encore {$waitSecs} seconde(s) avant de demander un nouveau code.";
        } else {
            $newOtp = $user->generateOTP();
            if ($newOtp) {
                $_SESSION['last_otp_resend'] = time();
                try {
                    Mail::sendOTP($user->email, $user->prenom . ' ' . $user->nom, $newOtp);
                    $succes = "Un nouveau code OTP a été envoyé à l'adresse <strong>" . htmlspecialchars($user->email) . "</strong>.";
                } catch (Exception $e) {
                    error_log("OTP mail send error: " . $e->getMessage());
                    $erreur = "Erreur lors de l'envoi du mail de sécurité. Veuillez réessayer.";
                }
            } else {
                $erreur = "Impossible de générer un nouveau code pour le moment. Veuillez réessayer.";
            }
        }
    }
} else {
    // ── Requête GET initiale : Générer un OTP si l'email n'est pas vérifié et qu'aucun code actif n'existe ──
    if ($user->email_verified == 0) {
        $isExpired = empty($user->otp_expires_at) || (strtotime($user->otp_expires_at) < time());
        if (empty($user->otp_code) || $isExpired) {
            $newOtp = $user->generateOTP();
            if ($newOtp) {
                try {
                    Mail::sendOTP($user->email, $user->prenom . ' ' . $user->nom, $newOtp);
                    $succes = "Un code de sécurité à 6 chiffres vient d'être envoyé à votre adresse email : <strong>" . htmlspecialchars($user->email) . "</strong>";
                } catch (Exception $e) {
                    error_log("Initial OTP mail error: " . $e->getMessage());
                }
            }
        }
    }
}

$isLocalhost = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1', '::1']) || str_contains($_SERVER['HTTP_HOST'] ?? '', 'localhost:');

include(__DIR__ . '/../layouts/header.php');
?>

<style>
.otp-section {
    min-height: 70vh;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 60px 20px;
    background: #faf8f3;
}

.otp-card {
    background: #ffffff;
    max-width: 480px;
    width: 100%;
    border-radius: 16px;
    box-shadow: 0 12px 40px rgba(0,0,0,0.06);
    border: 1px solid rgba(201,168,76,0.2);
    padding: 40px 35px;
    text-align: center;
}

.otp-icon {
    width: 70px;
    height: 70px;
    background: rgba(201,168,76,0.12);
    color: var(--or);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.8rem;
    margin: 0 auto 20px;
}

.otp-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2rem;
    color: var(--vert);
    margin-bottom: 8px;
    font-weight: 600;
}

.otp-subtitle {
    font-size: .9rem;
    color: #666;
    line-height: 1.6;
    margin-bottom: 24px;
}

.otp-input-group {
    margin-bottom: 24px;
}

.otp-input {
    width: 100%;
    font-size: 2rem;
    letter-spacing: 12px;
    text-align: center;
    font-family: monospace;
    padding: 12px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    color: var(--vert);
    outline: none;
    transition: all .3s;
}

.otp-input:focus {
    border-color: var(--or);
    box-shadow: 0 0 0 4px rgba(201,168,76,0.15);
}

.btn-otp-primary {
    width: 100%;
    background: var(--vert);
    color: #ffffff;
    border: 1px solid var(--vert);
    padding: 14px;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-weight: 400;
    font-size: 0.75rem;
    letter-spacing: 0.28em;
    text-transform: uppercase;
    cursor: pointer;
    transition: all .3s ease;
}

.btn-otp-primary:hover {
    background: #0e2218;
    border-color: #0e2218;
}

.btn-resend {
    background: none;
    border: none;
    color: var(--vert);
    font-size: .85rem;
    cursor: pointer;
    text-decoration: underline;
    margin-top: 18px;
}

.alert-custom {
    padding: 12px 16px;
    border-radius: 8px;
    font-size: .88rem;
    margin-bottom: 20px;
    text-align: left;
}
.alert-danger-custom { background: #fdf2f2; color: #dc3545; border: 1px solid #f8d7da; }
.alert-success-custom { background: #f0f9f4; color: #28a745; border: 1px solid #c3e6cb; }

.local-dev-box {
    background: #fdfaf0;
    border: 1px dashed var(--or);
    border-radius: 8px;
    padding: 12px;
    font-size: .85rem;
    color: #795548;
    margin-bottom: 20px;
}
</style>

<div class="otp-section">
    <div class="otp-card">
        <div class="otp-icon">
            <i class="fas fa-shield-alt"></i>
        </div>

        <h1 class="otp-title">Vérification de l'Email</h1>

        <?php if ($user->email_verified == 1): ?>
            <div class="alert-custom alert-success-custom" style="text-align:center;">
                <i class="fas fa-check-circle" style="font-size:1.5rem; margin-bottom:8px; display:block;"></i>
                <strong>Email Vérifié !</strong><br>
                Votre adresse email <strong><?= htmlspecialchars($user->email) ?></strong> est confirmée.
            </div>
            <p class="otp-subtitle">Vous bénéficiez d'un compte hôte certifié à l'Hôtel SEGURO.</p>
            <a href="mon-compte.php" class="btn-otp-primary" style="display:block; text-decoration:none; text-align:center;">
                Accéder à Mon Compte
            </a>
        <?php else: ?>
            <p class="otp-subtitle">
                Un code de sécurité à 6 chiffres (OTP) a été envoyé à :<br>
                <strong style="color:var(--vert);"><?= htmlspecialchars($user->email) ?></strong>
            </p>

            <?php if (!empty($erreur)): ?>
                <div class="alert-custom alert-danger-custom">
                    <i class="fas fa-exclamation-circle"></i> <?= $erreur ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($succes)): ?>
                <div class="alert-custom alert-success-custom">
                    <i class="fas fa-check-circle"></i> <?= $succes ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <?= csrf_field() ?>
                <div class="otp-input-group">
                    <input type="text" name="otp_code" class="otp-input" maxlength="6" placeholder="000000" required autocomplete="off" autofocus>
                </div>
                <button type="submit" name="action_verifier" class="btn-otp-primary">
                    <i class="fas fa-key"></i> Valider mon code OTP
                </button>
            </form>

            <form method="POST" action="">
                <?= csrf_field() ?>
                <button type="submit" name="action_renvoyer" class="btn-resend">
                    <i class="fas fa-paper-plane"></i> Vous n'avez pas reçu le code ? Renvoyer un OTP
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>
