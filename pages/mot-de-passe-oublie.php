<?php
/**
 * ════════════════════════════════════════════════════════
 * RÉCUPÉRATION DU CODE CLIENT / IDENTIFIANTS — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/User.php';
require_once __DIR__ . '/../includes/Mail.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$erreur = '';
$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    
    if (empty($email)) {
        $erreur = "Veuillez renseigner votre adresse email.";
    } else {
        $res = $user->recoverClientCode($email);
        if ($res['success']) {
            $succes = $res['message'];
        } else {
            $erreur = $res['message'];
        }
    }
}

include(__DIR__ . '/../layouts/header.php');
?>

<style>
.recup-container {
    max-width: 520px;
    margin: 80px auto;
    padding: 0 20px;
}

.recup-box {
    background: white;
    border-radius: 16px;
    padding: 48px;
    box-shadow: 0 20px 60px rgba(0,0,0,0.08);
    position: relative;
    overflow: hidden;
}

.recup-box::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 4px;
    background: linear-gradient(to right, var(--vert), var(--or));
}

.recup-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 2.2rem;
    color: var(--vert);
    text-align: center;
    margin-bottom: 12px;
}

.recup-subtitle {
    text-align: center;
    color: #666;
    margin-bottom: 32px;
    font-family: 'Jost', sans-serif;
    font-size: 0.95rem;
    line-height: 1.5;
}

.form-group {
    margin-bottom: 24px;
}

.form-label {
    font-family: 'Jost', sans-serif;
    font-weight: 500;
    font-size: 0.88rem;
    color: var(--vert);
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 8px;
    display: block;
}

.form-control {
    width: 100%;
    padding: 14px 16px;
    border: 1px solid rgba(201,168,76,0.25);
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-size: 0.95rem;
    transition: all 0.3s;
    box-sizing: border-box;
}

.form-control:focus {
    outline: none;
    border-color: var(--or);
    box-shadow: 0 0 0 3px rgba(201,168,76,0.12);
}

.btn-submit {
    width: 100%;
    background: var(--vert);
    color: var(--or);
    border: none;
    padding: 16px;
    border-radius: 8px;
    font-family: 'Jost', sans-serif;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    cursor: pointer;
    transition: all 0.3s;
    font-size: 0.95rem;
}

.btn-submit:hover {
    background: var(--vert-clair);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(26,58,42,0.15);
}

.alert {
    padding: 16px 20px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-family: 'Jost', sans-serif;
    font-size: 0.9rem;
    line-height: 1.5;
}

.alert-success {
    background: rgba(40,167,69,0.1);
    color: #155724;
    border: 1px solid rgba(40,167,69,0.25);
}

.alert-error {
    background: rgba(220,53,69,0.1);
    color: #721c24;
    border: 1px solid rgba(220,53,69,0.25);
}

.links-box {
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #f0f0f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.9rem;
}

.links-box a {
    color: var(--vert);
    text-decoration: none;
    transition: color 0.3s;
}

.links-box a:hover {
    color: var(--or);
}

@media (max-width: 768px) {
    .recup-container { margin: 40px auto; padding: 0 16px; }
    .recup-box { padding: 32px 20px; }
    .recup-title { font-size: 1.8rem; }
    .links-box { flex-direction: column; gap: 12px; text-align: center; }
}
</style>

<div class="recup-container">
    <div class="recup-box">
        <h1 class="recup-title">Code Client Oublié</h1>
        <p class="recup-subtitle">
            Renseignez votre adresse email pour recevoir immédiatement votre <strong>Code Client</strong> confidentiel.
        </p>

        <?php if ($succes): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle" style="color:#28a745; margin-right:6px;"></i>
                <?= htmlspecialchars($succes) ?>
            </div>
            <div style="text-align:center; margin-top:24px;">
                <a href="connexion-client.php" class="btn-submit" style="display:inline-block; text-decoration:none; width:auto; padding:14px 32px;">
                    Retourner à la connexion
                </a>
            </div>
        <?php else: ?>
            <?php if ($erreur): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-triangle" style="color:#dc3545; margin-right:6px;"></i>
                    <?= htmlspecialchars($erreur) ?>
                </div>
            <?php endif; ?>

            <form method="post" action="mot-de-passe-oublie.php">
                <div class="form-group">
                    <label class="form-label">Votre Adresse Email</label>
                    <input type="email" name="email" class="form-control" 
                           placeholder="nom@exemple.com" required autofocus>
                    <small style="color:#777; font-size:0.85rem; margin-top:6px; display:block;">
                        L'adresse email utilisée lors de votre réservation ou création de compte.
                    </small>
                </div>

                <button type="submit" class="btn-submit">
                    <i class="fas fa-paper-plane" style="margin-right:6px;"></i> M'envoyer mon Code Client
                </button>
            </form>
        <?php endif; ?>

        <div class="links-box">
            <a href="connexion-client.php"><i class="fas fa-arrow-left"></i> Retour à la connexion</a>
            <a href="reservation-system.php" style="color:var(--or);">Nouvelle réservation <i class="fas fa-arrow-right"></i></a>
        </div>
    </div>
</div>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>
