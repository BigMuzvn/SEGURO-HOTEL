<?php
/**
 * ════════════════════════════════════════════════════════
 * GALERIE & EXPÉRIENCE IMMERSIVE
 * ════════════════════════════════════════════════════════
 */

session_start();
require_once __DIR__ . '/../includes/database.php';

$photos = [
    [
        'url' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=1200&q=85',
        'title' => 'Suite Royale Vue Lac',
        'cat' => 'chambres',
        'desc' => 'Espace nuit d\'exception avec literie king size et terrasse panoramique privée.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=1200&q=85',
        'title' => 'Chambre Supérieure Boisée',
        'cat' => 'chambres',
        'desc' => 'Alliance subtile de boiseries nobles et de confort contemporain.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1200&q=85',
        'title' => 'Piscine à Débordement & Lagon',
        'cat' => 'piscine',
        'desc' => 'Bassin à débordement avec transats immergés face au coucher de soleil sur le lac Togo.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1200&q=85',
        'title' => 'Plage Privée & Jardins Tropicaux',
        'cat' => 'piscine',
        'desc' => 'Berges verdoyantes et cocoteraie pour des promenades paisibles.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=1200&q=85',
        'title' => 'Restaurant Gastronomique L\'Écrin',
        'cat' => 'gastro',
        'desc' => 'Une cuisine signature mariant produits locaux d\'Agbodrafo et haute gastronomie.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1514933651103-005eec06c04b?w=1200&q=85',
        'title' => 'Le Bar Lounge & Cocktails',
        'cat' => 'gastro',
        'desc' => 'Sélection de rhums rares, grands crus et créations mixologiques signatures.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1540555700478-4be289fbecef?w=1200&q=85',
        'title' => 'Le Spa & Rituels Bien-Être',
        'cat' => 'spa',
        'desc' => 'Massages aux huiles précieuses de baobab et rituels de relaxation profonde.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1540575467063-178a50c2df87?w=1200&q=85',
        'title' => 'Salle Grand Palais — Séminaire',
        'cat' => 'evenements',
        'desc' => 'Espace de conférence modulable équipé des dernières technologies audiovisuelles.'
    ],
    [
        'url' => 'https://images.unsplash.com/photo-1519167758481-83f550bb49b3?w=1200&q=85',
        'title' => 'Soirée de Gala & Mariage en Terrasse',
        'cat' => 'evenements',
        'desc' => 'Scénographie lumineuse féerique pour célébrations inoubliables au bord de l\'eau.'
    ],
];

include(__DIR__ . '/../layouts/header.php');
?>

<style>
.galerie-hero {
    background: linear-gradient(180deg, rgba(var(--noir-rgb),0.82) 0%, rgba(var(--noir-rgb),0.92) 100%), 
                url('https://images.unsplash.com/photo-1571896349842-33c89424de2d?w=1920&q=80') center/cover no-repeat;
    padding: 140px 24px 75px;
    text-align: center;
    color: #fff;
}

.galerie-hero-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 300;
    margin-bottom: 12px;
    color: #fff;
}

.galerie-hero-title em {
    font-style: italic;
    color: var(--or-pale);
}

.galerie-hero-sub {
    font-family: 'Jost', sans-serif;
    font-size: 1.05rem;
    color: rgba(255,255,255,0.85);
    max-width: 650px;
    margin: 0 auto;
    line-height: 1.6;
}

.galerie-container {
    max-width: 1200px;
    margin: 50px auto 100px;
    padding: 0 24px;
}

.galerie-filters {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    margin-bottom: 45px;
    flex-wrap: nowrap;
    overflow-x: auto;
    padding: 6px 4px;
}

.g-filter-btn {
    background: #ffffff;
    border: 1px solid rgba(var(--or-rgb),0.35);
    padding: 10px 22px;
    border-radius: 0;
    font-family: 'Jost', sans-serif;
    font-size: 0.68rem;
    font-weight: 500;
    letter-spacing: 0.2em;
    text-transform: uppercase;
    color: var(--noir, #111111);
    cursor: pointer;
    white-space: nowrap;
    transition: all 0.35s ease;
    flex-shrink: 0;
}

.g-filter-btn.active, .g-filter-btn:hover {
    background: var(--noir, #111111);
    color: var(--or-pale);
    border-color: var(--noir, #111111);
    box-shadow: 0 4px 14px rgba(0,0,0,0.15);
}

.galerie-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 28px;
}

.galerie-card {
    position: relative;
    border-radius: 0;
    overflow: hidden;
    height: 280px;
    cursor: pointer;
    box-shadow: 0 8px 24px rgba(0,0,0,0.06);
    transition: transform 0.4s, box-shadow 0.4s;
}

.galerie-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 16px 36px rgba(var(--vert-rgb),0.2);
}

.galerie-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.6s ease;
}

.galerie-card:hover .galerie-img {
    transform: scale(1.08);
}

.galerie-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(180deg, transparent 35%, rgba(var(--noir-rgb),0.92) 100%);
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    padding: 24px;
    color: #fff;
    opacity: 0.95;
    transition: opacity 0.3s;
}

.galerie-card-title {
    font-family: 'Cormorant Garamond', serif;
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 4px;
    color: #fff;
}

.galerie-card-desc {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.85);
    line-height: 1.4;
}

/* Lightbox Modal */
.lightbox-modal {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.92);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 30px;
}

.lightbox-content {
    max-width: 90vw;
    max-height: 85vh;
    border-radius: 8px;
    box-shadow: 0 0 50px rgba(0,0,0,0.8);
}

.lightbox-close {
    position: absolute;
    top: 24px;
    right: 32px;
    color: #fff;
    font-size: 2.2rem;
    cursor: pointer;
    transition: color 0.3s;
}

.lightbox-close:hover {
    color: var(--or);
}

@media (max-width: 768px) {
    .galerie-hero { padding: 60px 18px 36px; }
    .galerie-hero-title { font-size: 2.1rem; }
    .galerie-container { padding: 0 16px 60px; }
    .galerie-grid { grid-template-columns: 1fr; gap: 18px; }
    .galerie-card { height: 240px; }
    .galerie-filters { padding-bottom: 8px; margin-bottom: 24px; }
}
</style>

<!-- HERO -->
<div class="galerie-hero">
    <span style="text-transform:uppercase; letter-spacing:3px; color:var(--or); font-size:0.8rem; font-weight:600; display:block; margin-bottom:12px;">
        Immersion Visuelle · <?= htmlspecialchars(hotel_name()) ?>
    </span>
    <h1 class="galerie-hero-title">Galerie &amp; <em>Expérience des Lieux</em></h1>
    <p class="galerie-hero-sub">
        Explorez en haute résolution les suites d'exception, les jardins tropicaux, la gastronomie et l'atmosphère envoûtante de notre établissement.
    </p>
</div>

<!-- GALERIE -->
<div class="galerie-container">
    
    <!-- Filtres -->
    <div class="galerie-filters">
        <button type="button" class="g-filter-btn active" onclick="filterGallery('all', this)">Toutes les Photos</button>
        <button type="button" class="g-filter-btn" onclick="filterGallery('chambres', this)"><i class="fas fa-bed" style="margin-right:6px; color:var(--or);"></i> Chambres &amp; Suites</button>
        <button type="button" class="g-filter-btn" onclick="filterGallery('piscine', this)"><i class="fas fa-water" style="margin-right:6px; color:var(--or);"></i> Piscine &amp; Lagon</button>
        <button type="button" class="g-filter-btn" onclick="filterGallery('gastro', this)"><i class="fas fa-wine-glass-alt" style="margin-right:6px; color:var(--or);"></i> Gastronomie &amp; Bar</button>
        <button type="button" class="g-filter-btn" onclick="filterGallery('spa', this)"><i class="fas fa-spa" style="margin-right:6px; color:var(--or);"></i> Spa &amp; Bien-être</button>
        <button type="button" class="g-filter-btn" onclick="filterGallery('evenements', this)"><i class="fas fa-glass-cheers" style="margin-right:6px; color:var(--or);"></i> Événements &amp; Réceptions</button>
    </div>

    <!-- Grille -->
    <div class="galerie-grid">
        <?php foreach ($photos as $idx => $p): ?>
            <div class="galerie-card" data-cat="<?= $p['cat'] ?>" onclick="openLightbox('<?= $p['url'] ?>', '<?= htmlspecialchars($p['title'], ENT_QUOTES) ?>')">
                <img src="<?= $p['url'] ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="galerie-img" loading="lazy">
                <div class="galerie-overlay">
                    <div class="galerie-card-title"><?= htmlspecialchars($p['title']) ?></div>
                    <div class="galerie-card-desc"><?= htmlspecialchars($p['desc']) ?></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

</div>

<!-- LIGHTBOX MODAL -->
<div class="lightbox-modal" id="lightboxModal" onclick="closeLightbox()">
    <span class="lightbox-close">&times;</span>
    <img src="" id="lightboxImg" class="lightbox-content" onclick="event.stopPropagation()">
</div>

<script>
function filterGallery(cat, btn) {
    document.querySelectorAll('.g-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.galerie-card');
    cards.forEach(card => {
        if (cat === 'all' || card.getAttribute('data-cat') === cat) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function openLightbox(url, title) {
    document.getElementById('lightboxImg').src = url;
    document.getElementById('lightboxModal').style.display = 'flex';
}

function closeLightbox() {
    document.getElementById('lightboxModal').style.display = 'none';
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeLightbox();
});
</script>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>
