<?php
/**
 * ════════════════════════════════════════════════════════
 * CONNEXION CLIENT — Espace personnel Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Reservation.php';

// Rediriger si déjà connecté
if (isset($_SESSION['user_id'])) {
    if (in_array($_SESSION['user_role'] ?? '', ['admin', 'super_admin'])) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: mon-compte.php');
    }
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);
$reservation = new Reservation($db);

$erreur = '';
$succes = '';

// Traitement de la connexion avec protection Anti-Brute-Force
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrfToken = $_POST['csrf_token'] ?? '';
    $failedAttempts = $_SESSION['login_failed_attempts'] ?? 0;
    $lockoutTime    = $_SESSION['login_lockout_until'] ?? 0;

    if ($lockoutTime > time()) {
        $remaining = ceil(($lockoutTime - time()) / 60);
        $erreur = "Trop de tentatives infructueuses consécutives. Votre accès est temporairement verrouillé pendant {$remaining} minute(s).";
    } elseif (!verify_csrf_token($csrfToken)) {
        $erreur = 'Session de connexion expirée. Veuillez rafraîchir la page et réessayer.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $code_client = trim($_POST['code_client'] ?? '');
        
        if (empty($email) || empty($code_client)) {
            $erreur = 'Veuillez remplir tous les champs';
        } else {
            $client = $user->login($email, $code_client);
            
            if ($client) {
                // Réinitialiser les compteurs d'échec
                unset($_SESSION['login_failed_attempts']);
                unset($_SESSION['login_lockout_until']);

                // Régénérer l'ID de session pour prévenir la fixation de session
                session_regenerate_id(true);

                $_SESSION['user_id'] = $client->id;
                $_SESSION['user_email'] = $client->email;
                $_SESSION['user_nom'] = $client->nom;
                $_SESSION['user_prenom'] = $client->prenom;
                $_SESSION['user_role'] = $client->role;
                $_SESSION['user_code'] = $client->code_client;
                $_SESSION['user_code_client'] = $client->code_client;
                $_SESSION['connecte'] = true;

                // Redirection selon le rôle
                if ($client->isAdmin()) {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: mon-compte.php');
                }
                exit;
            } else {
                $failedAttempts++;
                $_SESSION['login_failed_attempts'] = $failedAttempts;
                if ($failedAttempts >= 5) {
                    $_SESSION['login_lockout_until'] = time() + (10 * 60); // 10 minutes de blocage
                    $erreur = 'Trop de tentatives infructueuses (5/5). Votre accès est temporairement bloqué pendant 10 minutes.';
                } else {
                    $remaining = 5 - $failedAttempts;
                    $erreur = "Email ou code client incorrect. ({$remaining} tentative(s) restante(s))";
                }
            }
        }
    }
}

include(__DIR__ . '/../layouts/header.php');
?>

<style>
/* ════════════════════════════════════════════════════════
   PAGE CONNEXION — Styles
═══════════════════════════════════════════════════════ */
.connexion-container {
    max-width: 500px;
    margin: 80px auto;
    padding: 0 20px;
}

.connexion-box {
    background: white;
    border-radius: 16px;
    padding: 48px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
}

.connexion-box::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(to right, var(--vert), var(--or));
}

.connexion-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.5rem;
    color: var(--vert);
    text-align: center;
    margin-bottom: 16px;
}

.connexion-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 40px;
    font-family: 'Jost', sans-serif;
    font-size: 0.95rem;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    font-size: 0.9rem;
    color: var(--vert);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 0.95rem;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: var(--or);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.1);
}

.btn-connexion {
    width: 100%;
    background: var(--vert);
    color: white;
    border: none;
    padding: 16px;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 32px;
}

.btn-connexion:hover {
    background: var(--vert-clair);
    transform: translateY(-2px);
}



.help-section {
    background: rgba(201,168,76,0.05);
    border-radius: 12px;
    padding: 24px;
    margin-top: 32px;
}

.help-title {
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    color: var(--vert);
    margin-bottom: 12px;
    font-size: 1rem;
}

.help-text {
    color: #666;
    font-size: 0.9rem;
    line-height: 1.6;
    margin-bottom: 8px;
}

.code-format {
    background: white;
    border: 1px solid rgba(201,168,76,0.2);
    border-radius: 6px;
    padding: 8px 12px;
    font-family: 'Courier New', monospace;
    font-weight: 600;
    color: var(--or);
    text-align: center;
    margin: 12px 0;
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-family: 'Jost', sans-serif;
}

.alert-error {
    background: rgba(220,53,69,0.1);
    color: #dc3545;
    border: 1px solid rgba(220,53,69,0.2);
}

@media (max-width: 768px) {
    .connexion-container {
        margin: 40px auto;
        padding: 0 16px;
    }
    
    .connexion-box {
        padding: 32px 24px;
    }
    
    .connexion-title {
        font-size: 2rem;
    }
}
</style>

<div class="connexion-container">
    <div class="connexion-box">
        <h1 class="connexion-title">Connexion</h1>
        <p class="connexion-subtitle">Accédez à votre espace personnel</p>
        
        <?php if ($erreur): ?>
            <div class="alert alert-error"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        
        <form method="post" action="connexion-client.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label class="form-label">Adresse Email</label>
                <input type="email" name="email" class="form-control" 
                       placeholder="nom@exemple.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:8px;">
                    <label class="form-label" style="margin-bottom:0;">Code Client</label>
                    <a href="mot-de-passe-oublie.php" style="color:var(--or); font-size:0.82rem; text-decoration:none; font-family:'Jost',sans-serif; font-weight:500;">
                        Code Client oublié ?
                    </a>
                </div>
                <input type="text" name="code_client" class="form-control" 
                       placeholder="Ex: SEG-2026-AB3K" required style="letter-spacing: 0.08em; text-transform: uppercase;">
                <small style="color: #777; font-size: 0.82rem; margin-top: 6px; display: block;">
                    Ce code confidentiel unique vous a été envoyé par email lors de votre réservation.
                </small>
            </div>
            
            <button type="submit" class="btn-connexion">
                <i class="fas fa-sign-in-alt" style="margin-right:6px;"></i> Me connecter
            </button>
        </form>
        
        <div class="help-section">
            <div class="help-title"><i class="fas fa-key" style="color:var(--or); margin-right:6px;"></i> Où trouver mon code client ?</div>
            <div class="help-text">
                Votre code client confidentiel vous a été envoyé par email lors de votre réservation. En cas d'oubli, cliquez ci-dessous pour le recevoir à nouveau :
            </div>
            
            <div style="text-align:center; margin:14px 0;">
                <a href="mot-de-passe-oublie.php" style="display:inline-block; background:rgba(201,168,76,0.15); color:var(--vert); border:1px solid var(--or); padding:8px 18px; border-radius:6px; text-decoration:none; font-size:0.85rem; font-weight:500;">
                    <i class="fas fa-envelope-open-text"></i> Récupérer mon code client par email
                </a>
            </div>
            
            <div style="text-align: center; margin-top: 20px; padding-top: 14px; border-top: 1px solid rgba(201,168,76,0.15);">
                <a href="reservation-system.php" style="color: var(--vert); text-decoration: none; font-weight: 500; font-size:0.9rem;">
                    Effectuer une nouvelle réservation →
                </a>
            </div>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>
