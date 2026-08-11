<?php
/**
 * CALENDRIER ADMIN — Hôtel SEGURO
 */

session_start();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['admin', 'super_admin'])) {
    header('Location: ../pages/connexion-client.php');
    exit;
}

require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/Chambre.php';
require_once __DIR__ . '/../includes/AdminAuth.php';

AdminAuth::requireAccess('calendrier');

$database = new Database();
$db       = $database->getConnection();
$chambre  = new Chambre($db);

$year      = intval($_GET['year']      ?? date('Y'));
$month     = intval($_GET['month']     ?? date('m'));
$chambre_id = $_GET['chambre_id']      ?? null;

if ($year  < 2024 || $year  > 2030) $year  = date('Y');
if ($month < 1    || $month > 12  ) $month = date('m');

$chambres_all        = $chambre->getAllAvailable();
$chambre_selectionnee = null;
if ($chambre_id) {
    $chambre_selectionnee = $chambre->getById($chambre_id);
}

// ── Récupérer les dates bloquées pour le mois ──────────────
$date_debut_mois = "$year-" . str_pad($month, 2, '0', STR_PAD_LEFT) . "-01";
$date_fin_mois   = date('Y-m-t', strtotime($date_debut_mois));

$jours_bloques = [];

// Source 1 — indisponibilites (validées)
$sql1 = "SELECT chambre_id, date_debut, date_fin, motif
          FROM indisponibilites
          WHERE date_fin >= ? AND date_debut <= ?";
$params1 = [$date_debut_mois, $date_fin_mois];
if ($chambre_id) { $sql1 .= " AND chambre_id = ?"; $params1[] = $chambre_id; }
$stmt1 = $db->prepare($sql1);
$stmt1->execute($params1);
foreach ($stmt1->fetchAll() as $row) {
    $d = new DateTime($row['date_debut']);
    $fin = new DateTime($row['date_fin']);
    while ($d <= $fin) {
        $key = $d->format('Y-m-d');
        $jours_bloques[$row['chambre_id']][$key] = ['statut' => 'validee', 'motif' => $row['motif']];
        $d->modify('+1 day');
    }
}

// Source 2 — réservations en cours
$sql2 = "SELECT r.chambre_id, r.date_arrivee, r.date_depart, r.reference, r.statut, c.nom as chambre_nom
          FROM reservations r
          JOIN chambres c ON c.id = r.chambre_id
          WHERE r.statut IN ('en_cours','modifiee')
          AND r.date_depart >= ? AND r.date_arrivee <= ?";
$params2 = [$date_debut_mois, $date_fin_mois];
if ($chambre_id) { $sql2 .= " AND r.chambre_id = ?"; $params2[] = $chambre_id; }
$stmt2 = $db->prepare($sql2);
$stmt2->execute($params2);
foreach ($stmt2->fetchAll() as $row) {
    $d = new DateTime($row['date_arrivee']);
    $fin = new DateTime($row['date_depart']);
    while ($d <= $fin) {
        $key = $d->format('Y-m-d');
        if (!isset($jours_bloques[$row['chambre_id']][$key])) {
            $jours_bloques[$row['chambre_id']][$key] = ['statut' => 'en_cours', 'motif' => 'Résa #'.$row['reference'].' (en attente)'];
        }
        $d->modify('+1 day');
    }
}

// ── Pour chaque jour du mois, calculer le statut global ───
$nb_chambres = count($chambres_all);
$statuts_par_jour = [];

$d = new DateTime($date_debut_mois);
$fin_mois = new DateTime($date_fin_mois);
while ($d <= $fin_mois) {
    $key = $d->format('Y-m-d');
    $chambres_bloquees = 0;
    $motifs = [];
    $has_validee = false;
    $has_en_cours = false;

    foreach ($chambres_all as $ch) {
        if (isset($jours_bloques[$ch['id']][$key])) {
            $chambres_bloquees++;
            $info = $jours_bloques[$ch['id']][$key];
            $motifs[] = ($chambre_id ? '' : $ch['nom'].' : ') . $info['motif'];
            if ($info['statut'] === 'validee') $has_validee = true;
            else $has_en_cours = true;
        }
    }

    if ($chambres_bloquees === 0) {
        $statut = 'disponible';
    } elseif ($chambre_id) {
        $statut = $has_validee ? 'indisponible' : 'en_attente';
    } else {
        $statut = ($chambres_bloquees >= $nb_chambres) ? 'indisponible' : 'partiellement';
    }

    $statuts_par_jour[$key] = [
        'statut'   => $statut,
        'nb'       => $chambres_bloquees,
        'motifs'   => $motifs,
        'validee'  => $has_validee,
        'en_cours' => $has_en_cours,
    ];
    $d->modify('+1 day');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier — Admin Hôtel Seguro</title>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600&family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --vert:#1a3a2a; --vert-clair:#2d5c40; --or:#c9a84c;
            --blanc:#faf8f3; --gris:#f5f5f5; --gris-fonce:#666;
            --success:#28a745; --danger:#dc3545; --warning:#ffc107;
        }
        *{box-sizing:border-box;margin:0;padding:0;}
        body{font-family:'Jost',sans-serif;background:var(--gris);color:var(--vert);display:flex;min-height:100vh;}
        
        /* ── Sidebar ── */
        .sidebar {
            width: 260px;
            background: var(--vert);
            color: var(--blanc);
            position: fixed;
            top: 0; bottom: 0; left: 0;
            display: flex;
            flex-direction: column;
            z-index: 1000;
            overflow: hidden;
        }
        .sidebar-header {
            padding: 14px 18px;
            border-bottom: 1px solid rgba(250,248,243,0.1);
        }
        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--or);
            font-family: 'Cormorant Garamond', serif;
            font-size: 1.3rem;
            text-decoration: none;
        }
        .sidebar-nav {
            flex: 1;
            padding: 6px 0;
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        .sidebar-nav::-webkit-scrollbar {
            display: none;
            width: 0;
            height: 0;
        }
        .nav-section {
            padding: 6px 18px 2px;
            font-size: 0.58rem;
            text-transform: uppercase;
            letter-spacing: 0.18em;
            color: rgba(250,248,243,0.4);
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 7px 18px;
            color: rgba(250,248,243,0.8);
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.2s;
        }
        .nav-item:hover, .nav-item.active {
            background: rgba(201,168,76,0.1);
            color: var(--or);
            border-left: 3px solid var(--or);
        }
        .nav-item i {
            width: 16px;
            font-size: 0.85rem;
            text-align: center;
        }
        .sidebar-footer {
            padding: 10px 18px;
            border-top: 1px solid rgba(250,248,243,0.1);
        }
        .admin-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .admin-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background: var(--or);
            color: var(--vert);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.75rem;
        }
        .admin-details h4 {
            font-size: 0.78rem;
            color: #fff;
            line-height: 1.2;
        }
        .admin-details p {
            font-size: 0.62rem;
            color: rgba(255,255,255,0.6);
            line-height: 1.2;
        }
        .logout-btn{display:flex;align-items:center;gap:8px;margin-top:12px;padding:8px 0;color:rgba(250,248,243,.6);text-decoration:none;font-size:.8rem;transition:color .3s;}
        .logout-btn:hover{color:var(--or);}
        
        /* ── Main ── */
        .main-content{flex:1;margin-left:260px;padding:30px;}
        .top-bar{display:flex;justify-content:space-between;align-items:center;margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #e0e0e0;}
        .page-title h1{font-family:'Cormorant Garamond',serif;font-size:1.8rem;font-weight:400;}
        .page-title p{font-size:.85rem;color:var(--gris-fonce);margin-top:4px;}

        /* Calendrier styles */
        .filtres-section{background:#fff;border-radius:12px;padding:20px 24px;margin-bottom:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
        .filtres-row{display:flex;gap:20px;align-items:center;flex-wrap:wrap;}
        .filtre-group{display:flex;align-items:center;gap:8px;}
        .filtre-label{font-weight:500;color:var(--vert);font-size:.88rem;}
        .filtre-select{padding:8px 12px;border:1px solid #ddd;border-radius:6px;font-family:'Jost',sans-serif;font-size:.88rem;cursor:pointer;}
        .filtre-select:focus{outline:none;border-color:var(--or);}

        .nav-mois{display:flex;justify-content:center;align-items:center;gap:30px;margin-bottom:24px;}
        .btn-nav{background:var(--vert);color:#fff;border:none;padding:8px 18px;border-radius:6px;cursor:pointer;font-family:'Jost',sans-serif;font-size:.85rem;transition:all .3s;}
        .btn-nav:hover{background:var(--vert-clair);}
        .mois-label{font-family:'Cormorant Garamond',serif;font-size:1.6rem;color:var(--vert);font-weight:600;}

        .cal-wrap{background:#fff;border-radius:12px;padding:24px;box-shadow:0 2px 8px rgba(0,0,0,.05);}
        .cal-table{width:100%;border-collapse:collapse;}
        .cal-table thead th{font-weight:600;color:var(--vert);text-align:center;padding:12px 8px;font-size:.82rem;text-transform:uppercase;letter-spacing:.05em;border-bottom:2px solid var(--or);}
        .cal-day{height:85px;border:1px solid #eee;vertical-align:top;position:relative;cursor:default;transition:background .2s;}
        .cal-day:hover{background:rgba(201,168,76,.04);}
        .day-num{font-weight:500;font-size:.85rem;color:#333;padding:6px 8px;display:inline-block;}

        .cal-day.passe{opacity:.45;}
        .cal-day.passe .day-num{color:#bbb;}
        .cal-day.disponible{background:rgba(40,167,69,.05);}
        .cal-day.disponible .day-num{color:#28a745;}
        .cal-day.indisponible{background:rgba(220,53,69,.12);}
        .cal-day.indisponible .day-num{color:#dc3545;}
        .cal-day.en_attente{background:rgba(255,193,7,.12);}
        .cal-day.en_attente .day-num{color:#856404;}
        .cal-day.partiellement{background:rgba(255,160,0,.08);}
        .cal-day.partiellement .day-num{color:#e67e00;}
        .cal-day.hors-mois{opacity:.2;}
        .cal-day.aujourd-hui .day-num{background:var(--or);color:#fff;border-radius:50%;width:26px;height:26px;display:flex;align-items:center;justify-content:center;margin:2px;}

        .day-barre{position:absolute;bottom:0;left:0;right:0;height:4px;}
        .barre-disponible{background:rgba(40,167,69,.5);}
        .barre-indisponible{background:rgba(220,53,69,.6);}
        .barre-en_attente{background:rgba(255,193,7,.7);}
        .barre-partiellement{background:linear-gradient(to right,rgba(40,167,69,.5),rgba(220,53,69,.5));}

        .day-tooltip{display:none;position:absolute;bottom:100%;left:50%;transform:translateX(-50%);background:#1a3a2a;color:#fff;font-size:.72rem;padding:6px 10px;border-radius:6px;white-space:normal;z-index:100;max-width:200px;text-align:center;line-height:1.4;}
        .cal-day:hover .day-tooltip{display:block;}

        .legende{display:flex;justify-content:center;gap:24px;margin-top:20px;flex-wrap:wrap;}
        .legende-item{display:flex;align-items:center;gap:8px;font-size:.82rem;color:#666;cursor:pointer;padding:4px 10px;border-radius:6px;transition:all .2s;}
        .legende-item:hover{background:rgba(201,168,76,.1);}
        .legende-item.active{font-weight:600;background:rgba(201,168,76,.18);}
        .legende-couleur{width:14px;height:14px;border-radius:3px;border:1px solid rgba(0,0,0,.1);}

        @media(max-width:768px){.sidebar{width:70px;}.sidebar-logo span,.nav-item span,.nav-section,.admin-details{display:none;}.main-content{margin-left:70px;}}
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-logo"><i class="fas fa-crown"></i><span>Hôtel Seguro</span></a>
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <?php if (AdminAuth::can('dashboard')): ?>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-chart-line"></i><span>Dashboard</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('calendrier')): ?>
            <a href="calendrier.php" class="nav-item active"><i class="fas fa-calendar-alt"></i><span>Calendrier</span></a>
        <?php endif; ?>
        
        <div class="nav-section" style="margin-top:14px;">Gestion</div>
        <?php if (AdminAuth::can('reservations')): ?>
            <a href="reservations.php" class="nav-item"><i class="fas fa-book"></i><span>Réservations</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('room_service')): ?>
            <a href="room-service.php" class="nav-item"><i class="fas fa-concierge-bell"></i><span>Room Service</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('evenements')): ?>
            <a href="evenements.php" class="nav-item"><i class="fas fa-glass-cheers"></i><span>Devis Événements</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('clients')): ?>
            <a href="clients.php" class="nav-item"><i class="fas fa-users"></i><span>Clients</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('chambres')): ?>
            <a href="chambres.php" class="nav-item"><i class="fas fa-bed"></i><span>Chambres</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('avis')): ?>
            <a href="avis.php" class="nav-item"><i class="fas fa-star"></i><span>Avis Clients</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('codes_promo')): ?>
            <a href="codes-promo.php" class="nav-item"><i class="fas fa-tags"></i><span>Codes Promo</span></a>
        <?php endif; ?>
        <?php if (AdminAuth::can('profil')): ?>
            <a href="profil.php" class="nav-item"><i class="fas fa-user-shield"></i><span>Équipe &amp; Profil</span></a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-info">
            <div class="admin-avatar"><?= strtoupper(substr($_SESSION['user_prenom']??'A',0,1)) ?></div>
            <div class="admin-details">
                <h4><?= htmlspecialchars(($_SESSION['user_prenom']??'').' '.($_SESSION['user_nom']??'')) ?></h4>
                <p><?= ($_SESSION['user_role'] ?? '') === 'super_admin' ? 'Super Administrateur' : 'Administrateur' ?></p>
            </div>
        </div>
        <a href="../pages/deconnexion.php" class="logout-btn" style="display:flex; align-items:center; gap:8px; margin-top:10px; color:rgba(250,248,243,0.6); text-decoration:none; font-size:0.75rem;">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</aside>

<main class="main-content">
    <div class="top-bar">
        <div class="page-title">
            <h1>Calendrier des Disponibilités</h1>
            <p>Vue d'ensemble de l'occupation des chambres</p>
        </div>
    </div>

    <!-- Filtres -->
    <div class="filtres-section">
        <div class="filtres-row">
            <div class="filtre-group">
                <label class="filtre-label">Chambre :</label>
                <select class="filtre-select" id="chambreSelect" onchange="changeChambre()">
                    <option value="">Toutes les chambres</option>
                    <?php foreach ($chambres_all as $ch): ?>
                    <option value="<?= $ch['id'] ?>" <?= $chambre_id===$ch['id']?'selected':'' ?>>
                        <?= htmlspecialchars($ch['nom']) ?> — <?= ucfirst($ch['type']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filtre-group">
                <label class="filtre-label">Mois :</label>
                <select class="filtre-select" onchange="changeMonth(this.value)">
                    <?php
                    $moisFR=[1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',
                             7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'];
                    for ($m=1;$m<=12;$m++):?>
                    <option value="<?= $m ?>" <?= $month===$m?'selected':'' ?>><?= $moisFR[$m] ?></option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="filtre-group">
                <label class="filtre-label">Année :</label>
                <select class="filtre-select" onchange="changeYear(this.value)">
                    <?php for($y=date('Y');$y<=date('Y')+2;$y++):?>
                    <option value="<?= $y ?>" <?= $year===$y?'selected':'' ?>><?= $y ?></option>
                    <?php endfor;?>
                </select>
            </div>
        </div>
    </div>

    <!-- Navigation mois -->
    <div class="nav-mois">
        <button class="btn-nav" onclick="changeMonth(<?= $month==1?12:$month-1 ?>)">← Mois précédent</button>
        <div class="mois-label"><?= $moisFR[$month] ?> <?= $year ?></div>
        <button class="btn-nav" onclick="changeMonth(<?= $month==12?1:$month+1 ?>)">Mois suivant →</button>
    </div>

    <!-- Calendrier -->
    <div class="cal-wrap">
        <table class="cal-table">
            <thead>
                <tr>
                    <th>Dim</th><th>Lun</th><th>Mar</th><th>Mer</th><th>Jeu</th><th>Ven</th><th>Sam</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $today_str = date('Y-m-d');
            $premier_jour = new DateTime("$year-$month-01");
            $nb_jours = (int)$premier_jour->format('t');
            $debut_semaine = (int)$premier_jour->format('w');

            $jour = 1;
            $total_cases = $debut_semaine + $nb_jours;
            $nb_semaines = ceil($total_cases / 7);

            for ($s = 0; $s < $nb_semaines; $s++):
            ?>
            <tr>
            <?php for ($col = 0; $col < 7; $col++):
                $case_idx = $s * 7 + $col;
                $hors_mois = ($case_idx < $debut_semaine || $jour > $nb_jours);

                if ($hors_mois) {
                    if ($case_idx < $debut_semaine) {
                        $prev_day = $nb_jours - ($debut_semaine - $col - 1);
                        $display = $prev_day;
                    } else {
                        $display = $jour - $nb_jours;
                        $jour++;
                    }
                    echo '<td class="cal-day hors-mois"><div class="day-num">'.$display.'</div></td>';
                    continue;
                }

                $date_str = sprintf('%04d-%02d-%02d', $year, $month, $jour);
                $info = $statuts_par_jour[$date_str] ?? ['statut'=>'disponible','motifs'=>[],'nb'=>0];
                $statut = $info['statut'];
                $passe = ($date_str < $today_str);
                $auj   = ($date_str === $today_str);

                $classes = ['cal-day', $statut];
                if ($passe) $classes[] = 'passe';
                if ($auj)   $classes[] = 'aujourd-hui';

                $tooltip = '';
                if ($passe) {
                    $tooltip = 'Date passée';
                } elseif ($statut === 'disponible') {
                    $tooltip = $chambre_id ? 'Disponible' : 'Toutes les chambres disponibles';
                } elseif ($statut === 'indisponible') {
                    $tooltip = $chambre_id ? 'Chambre réservée' : 'Toutes les chambres réservées';
                    if (!empty($info['motifs'])) $tooltip .= ' — ' . implode(', ', array_slice($info['motifs'],0,2));
                } elseif ($statut === 'en_attente') {
                    $tooltip = 'Réservation en attente de validation';
                    if (!empty($info['motifs'])) $tooltip .= ' — ' . $info['motifs'][0];
                } elseif ($statut === 'partiellement') {
                    $dispo_ch = count($chambres_all) - $info['nb'];
                    $tooltip = "$dispo_ch chambre(s) encore disponible(s)";
                }

                $barre_class = 'barre-'.$statut;
                echo '<td class="'.implode(' ',$classes).'">';
                if ($tooltip) echo '<div class="day-tooltip">'.htmlspecialchars($tooltip).'</div>';
                echo '<div class="day-num">'.$jour.'</div>';
                echo '<div class="day-barre '.$barre_class.'"></div>';
                echo '</td>';

                $jour++;
            endfor; ?>
            </tr>
            <?php endfor; ?>
            </tbody>
        </table>

        <!-- Légende -->
        <div class="legende">
            <div class="legende-item" onclick="filtrer('disponible',this)">
                <div class="legende-couleur" style="background:rgba(40,167,69,.15);border-color:rgba(40,167,69,.4);"></div>
                <span>Disponible</span>
            </div>
            <div class="legende-item" onclick="filtrer('en_attente',this)">
                <div class="legende-couleur" style="background:rgba(255,193,7,.2);border-color:rgba(255,193,7,.5);"></div>
                <span>En attente</span>
            </div>
            <?php if (!$chambre_id): ?>
            <div class="legende-item" onclick="filtrer('partiellement',this)">
                <div class="legende-couleur" style="background:linear-gradient(to right,rgba(40,167,69,.3),rgba(220,53,69,.3));border-color:#ccc;"></div>
                <span>Partiellement disponible</span>
            </div>
            <?php endif; ?>
            <div class="legende-item" onclick="filtrer('indisponible',this)">
                <div class="legende-couleur" style="background:rgba(220,53,69,.15);border-color:rgba(220,53,69,.4);"></div>
                <span>Indisponible</span>
            </div>
            <div class="legende-item" onclick="filtrer('tous',this)">
                <div class="legende-couleur" style="background:#eee;border-color:#ccc;"></div>
                <span>Tout afficher</span>
            </div>
        </div>
    </div>
</main>

<script>
function changeChambre() {
    const id  = document.getElementById('chambreSelect').value;
    const url = new URL(window.location);
    id ? url.searchParams.set('chambre_id', id) : url.searchParams.delete('chambre_id');
    url.searchParams.set('month', <?= $month ?>);
    url.searchParams.set('year',  <?= $year ?>);
    window.location.href = url.toString();
}

function changeMonth(m) {
    m = parseInt(m);
    let y = <?= $year ?>;
    if (<?= $month ?> === 12 && m === 1) y++;
    if (<?= $month ?> === 1  && m === 12) y--;
    const url = new URL(window.location);
    url.searchParams.set('month', m);
    url.searchParams.set('year',  y);
    window.location.href = url.toString();
}

function changeYear(y) {
    const url = new URL(window.location);
    url.searchParams.set('year', y);
    window.location.href = url.toString();
}

function filtrer(statut, el) {
    document.querySelectorAll('.legende-item').forEach(function(i){
        i.classList.remove('active');
        i.style.opacity = '0.5';
    });
    el.classList.add('active');
    el.style.opacity = '1';

    document.querySelectorAll('.cal-day').forEach(function(cell) {
        if (cell.classList.contains('hors-mois')) return;
        cell.style.transition = 'opacity 0.2s';
        if (statut === 'tous') {
            cell.style.opacity = '1';
        } else if (cell.classList.contains(statut)) {
            cell.style.opacity = '1';
        } else {
            cell.style.opacity = '0.08';
        }
    });
}
</script>
</body>
</html>
