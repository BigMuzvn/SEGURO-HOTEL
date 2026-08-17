<?php include(__DIR__ . '/../layouts/header.php'); ?>

<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(32px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
  }
  @keyframes heroZoom {
    from { transform: scale(1); }
    to   { transform: scale(1.06); }
  }
  @keyframes scrollPulse {
    0%,100% { transform: scaleY(0.7); opacity: 0.3; }
    50%      { transform: scaleY(1);   opacity: 1; }
  }

  /* ── Icon Badges de Luxe ── */
  .about-icon-badge {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: rgba(var(--or-rgb), 0.12);
    border: 1.5px solid rgba(var(--or-rgb), 0.35);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    color: var(--or);
    font-size: 1.3rem;
    transition: all 0.35s ease;
    flex-shrink: 0;
  }
  .about-icon-badge-lg {
    width: 64px;
    height: 64px;
    font-size: 1.55rem;
    margin-bottom: 22px;
  }
  .pillar:hover .about-icon-badge,
  .promise-card:hover .about-icon-badge,
  .bienetre-card:hover .about-icon-badge,
  .event-item:hover .about-icon-badge,
  .nautique-item:hover .about-icon-badge,
  .service6-card:hover .about-icon-badge,
  .value-item:hover .about-icon-badge {
    background: var(--or);
    color: var(--noir, #111111);
    border-color: var(--or);
    transform: scale(1.08);
  }

  .about-hero {
    position: relative; width: 100%; height: 100vh;
    min-height: 600px; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
  }
  .about-hero-bg {
    position: absolute; inset: 0;
    background: url('../assets/images/about.jpg') center/cover no-repeat;
    animation: heroZoom 20s ease-in-out infinite alternate;
  }
  .about-hero-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(180deg, rgba(var(--noir-rgb),0.3) 0%, rgba(var(--noir-rgb),0.65) 50%, rgba(var(--noir-rgb),0.9) 100%);
  }
  .about-hero-content {
    position: relative; z-index: 2;
    text-align: center; max-width: 760px; padding: 0 32px;
  }
  .about-eyebrow {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.6rem;
    letter-spacing: 0.8em; text-transform: uppercase; color: var(--or);
    display: block; margin-bottom: 28px; animation: fadeUp 1.4s ease 0.3s both;
  }
  .about-hero-quote {
    font-family: 'Cormorant Garamond', serif; font-weight: 300; font-style: italic;
    font-size: clamp(1.8rem, 4vw, 3.2rem); color: #ffffff; line-height: 1.5;
    letter-spacing: 0.02em; animation: fadeUp 1.4s ease 0.5s both;
  }
  .about-hero-quote em {
    font-style: normal; color: var(--or-pale); display: block;
    font-size: 0.65em; letter-spacing: 0.14em; text-transform: uppercase;
    font-family: 'Jost', sans-serif; font-weight: 300; margin-top: 24px;
  }
  .about-hero-ornament {
    display: flex; align-items: center; justify-content: center;
    gap: 16px; margin-top: 36px; animation: fadeUp 1.4s ease 0.7s both;
  }
  .orn-line { width: 60px; height: 1px; background: linear-gradient(to right, transparent, rgba(var(--or-rgb),0.6)); }
  .orn-line.r { background: linear-gradient(to left, transparent, rgba(var(--or-rgb),0.6)); }
  .orn-dot { width: 6px; height: 6px; border: 1px solid rgba(var(--or-rgb),0.7); transform: rotate(45deg); }
  .about-scroll {
    position: absolute; bottom: 40px; left: 50%; transform: translateX(-50%);
    z-index: 5; text-align: center; animation: fadeIn 2s ease 1.5s both;
  }
  .about-scroll span {
    display: block; font-family: 'Jost', sans-serif; font-weight: 200;
    font-size: 0.46rem; letter-spacing: 0.5em; text-transform: uppercase;
    color: rgba(255,255,255,0.7); margin-bottom: 10px;
  }
  .about-scroll-line {
    width: 1px; height: 50px;
    background: linear-gradient(to bottom, rgba(255,255,255,0.6), transparent);
    margin: 0 auto; animation: scrollPulse 2.2s ease-in-out infinite;
  }

  /* ── Mot de la direction ── */
  .story-section { padding: 120px 0; position: relative; overflow: hidden; background: #ffffff; }
  .story-section::before {
    content: '<?= substr(hotel_short_name(), 0, 1) ?>'; position: absolute; top: -60px; right: -40px;
    font-family: 'Cormorant Garamond', serif; font-size: 40vw;
    color: rgba(var(--noir-rgb),0.02); line-height: 1; pointer-events: none; user-select: none;
  }
  .story-inner {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 80px; align-items: center; padding: 0 80px;
  }
  .story-image-wrap { position: relative; }
  .story-main-img { width: 100%; aspect-ratio: 3/4; object-fit: cover; display: block; border-radius: 8px; }
  .story-float-img {
    position: absolute; bottom: -40px; right: -40px;
    width: 55%; aspect-ratio: 4/3; object-fit: cover;
    border: 6px solid #fff; box-shadow: 0 20px 60px rgba(0,0,0,0.15); border-radius: 8px;
  }
  .story-image-wrap::before {
    content: ''; position: absolute; top: 30px; left: -20px;
    width: 2px; height: 80px; background: var(--or);
  }
  .story-text { padding-top: 20px; }
  .section-label {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.58rem;
    letter-spacing: 0.65em; text-transform: uppercase; color: var(--or);
    display: block; margin-bottom: 20px;
  }
  .section-heading {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: clamp(2rem, 3.5vw, 3rem); color: var(--noir, #111111);
    line-height: 1.2; letter-spacing: 0.03em; margin-bottom: 32px;
  }
  .section-heading em { font-style: italic; color: var(--or); }
  .story-divider { display: flex; align-items: center; gap: 12px; margin-bottom: 32px; }
  .story-divider span { display: block; height: 1px; width: 40px; background: linear-gradient(to right, var(--or), transparent); }
  .story-divider i { width: 5px; height: 5px; background: var(--or); transform: rotate(45deg); display: block; }
  .story-body p {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.85rem;
    color: #4a5568; line-height: 2.1; letter-spacing: 0.02em; margin-bottom: 20px;
  }
  .story-body p:first-child {
    font-family: 'Cormorant Garamond', serif; font-style: italic;
    font-size: 1.18rem; color: var(--noir, #111111); line-height: 1.9;
  }
  .signature-block {
    margin-top: 40px; padding-top: 32px;
    border-top: 1px solid rgba(var(--or-rgb),0.2);
    display: flex; align-items: center; gap: 20px;
  }
  .signature-or { width: 3px; height: 50px; background: linear-gradient(to bottom, var(--or), transparent); flex-shrink: 0; }
  .signature-name {
    font-family: 'Cormorant Garamond', serif; font-weight: 500;
    font-size: 1.15rem; color: var(--noir, #111111); display: block; letter-spacing: 0.04em;
  }
  .signature-role {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.58rem;
    letter-spacing: 0.4em; text-transform: uppercase; color: var(--or);
    display: block; margin-top: 4px;
  }

  /* ── Vision (Fond Sombre Noble Garanti) ── */
  #vision {
    background: linear-gradient(145deg, var(--noir, #111111) 0%, var(--noir-surface, #181818) 100%);
    padding: 120px 0; position: relative; overflow: hidden;
  }
  #vision::before {
    content: ''; position: absolute; top: -100px; left: -100px;
    width: 500px; height: 500px; border-radius: 50%;
    border: 1px solid rgba(var(--or-rgb),0.1);
  }
  #vision::after {
    content: ''; position: absolute; bottom: -150px; right: -80px;
    width: 600px; height: 600px; border-radius: 50%;
    border: 1px solid rgba(var(--or-rgb),0.08);
  }
  .vision-inner { padding: 0 80px; position: relative; z-index: 2; }
  .vision-top {
    display: flex; align-items: flex-end; justify-content: space-between;
    gap: 60px; margin-bottom: 80px;
  }
  .vision-heading {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: clamp(2.5rem, 5vw, 4.5rem); color: #ffffff;
    line-height: 1.1; letter-spacing: 0.04em; max-width: 560px;
  }
  .vision-heading em { font-style: italic; color: var(--or-pale); }
  .vision-intro { max-width: 380px; flex-shrink: 0; }
  .vision-intro p {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.85rem;
    color: rgba(255,255,255,0.78); line-height: 2; letter-spacing: 0.03em;
  }
  .vision-pillars { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2px; }
  .pillar {
    padding: 48px 40px; background: rgba(255,255,255,0.04);
    border-top: 1px solid rgba(var(--or-rgb),0.25); transition: background 0.4s, transform 0.3s;
  }
  .pillar:hover { background: rgba(255,255,255,0.08); }
  .pillar-num {
    font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: 3.5rem;
    color: rgba(var(--or-rgb),0.35); line-height: 1; display: block; margin-bottom: 16px;
  }
  .pillar-name { font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: 1.4rem; color: #ffffff; margin-bottom: 12px; letter-spacing: 0.03em; }
  .pillar-desc { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.78rem; color: rgba(255,255,255,0.75); line-height: 2; letter-spacing: 0.03em; }

  /* ── Triple promesse ── */
  .promise-section { padding: 120px 0; background: #ffffff; }
  .promise-inner { padding: 0 80px; }
  .promise-header { text-align: center; margin-bottom: 72px; }
  .promise-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
  .promise-card {
    padding: 48px 36px; background: var(--blanc, #fbf9f4);
    border: 1px solid rgba(var(--or-rgb),0.15);
    border-top: 3px solid transparent; transition: all 0.4s;
  }
  .promise-card:hover {
    background: #ffffff; border-top-color: var(--or);
    box-shadow: 0 20px 60px rgba(0,0,0,0.06);
  }
  .promise-title { font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: 1.5rem; color: var(--noir, #111111); margin-bottom: 16px; letter-spacing: 0.03em; }
  .promise-text { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.82rem; color: #4a5568; line-height: 2; letter-spacing: 0.02em; }
  .promise-quote {
    font-family: 'Cormorant Garamond', serif; font-style: italic;
    font-size: 1.05rem; color: var(--or); display: block;
    margin-top: 20px; padding-top: 16px; border-top: 1px solid rgba(var(--or-rgb),0.2);
  }

  /* ── Gastronomie ── */
  .gastro-section { padding: 120px 0; background: var(--blanc, #fbf9f4); }
  .gastro-inner { padding: 0 80px; }
  .gastro-header { margin-bottom: 64px; }

  .gastro-grid {
    display: grid;
    grid-template-columns: 1.2fr 0.8fr 0.8fr;
    gap: 16px;
  }

  .gastro-card {
    position: relative;
    overflow: hidden;
    cursor: pointer;
    background: #ffffff;
    border: 1px solid rgba(var(--or-rgb),0.15);
    transition: transform 0.4s, box-shadow 0.4s;
  }
  .gastro-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 50px rgba(0,0,0,0.08);
  }

  .gastro-card-img {
    position: relative;
    overflow: hidden;
    height: 280px;
  }
  .gastro-card-img img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
    transition: transform 0.7s ease;
  }
  .gastro-card:hover .gastro-card-img img { transform: scale(1.06); }

  .gastro-card-tag {
    position: absolute;
    top: 20px; left: 20px; z-index: 2;
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.52rem; letter-spacing: 0.4em; text-transform: uppercase;
    color: #ffffff; background: var(--noir, #111111);
    padding: 6px 14px;
    border-left: 2px solid var(--or);
  }

  .gastro-card-body {
    padding: 28px 28px 32px;
    background: #ffffff;
    border-top: 2px solid transparent;
    transition: border-color 0.4s;
  }
  .gastro-card:hover .gastro-card-body {
    border-top-color: var(--or);
  }

  .gastro-card-name {
    font-family: 'Cormorant Garamond', serif; font-weight: 500;
    font-size: 1.35rem; color: var(--noir, #111111);
    margin-bottom: 12px; letter-spacing: 0.03em;
  }
  .gastro-card-desc {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.8rem; color: #4a5568;
    line-height: 1.9; letter-spacing: 0.02em;
  }
  .gastro-card-note {
    font-family: 'Cormorant Garamond', serif; font-style: italic;
    font-size: 0.95rem; color: var(--or);
    display: block; margin-top: 14px;
    padding-top: 14px; border-top: 1px solid rgba(var(--or-rgb),0.15);
  }

  /* ── Bien-être & Loisirs (Fond Sombre Garanti) ── */
  .bienetre-section {
    padding: 120px 0;
    background: linear-gradient(145deg, var(--noir, #111111) 0%, var(--noir-surface, #181818) 100%);
    position: relative; overflow: hidden;
  }
  .bienetre-section::before {
    content: '';
    position: absolute; top: -80px; right: -80px;
    width: 400px; height: 400px; border-radius: 50%;
    border: 1px solid rgba(var(--or-rgb),0.08);
  }
  .bienetre-inner { padding: 0 80px; position: relative; z-index: 2; }
  .bienetre-top {
    display: grid; grid-template-columns: 1fr 1fr;
    gap: 80px; align-items: center; margin-bottom: 64px;
  }
  .bienetre-heading {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: clamp(2rem, 3.5vw, 3.2rem); color: #ffffff;
    line-height: 1.2; letter-spacing: 0.03em;
  }
  .bienetre-heading em { font-style: italic; color: var(--or-pale); }
  .bienetre-intro {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.85rem; color: rgba(255,255,255,0.78);
    line-height: 2; letter-spacing: 0.03em;
  }

  .bienetre-cards {
    display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
  }
  .bienetre-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(var(--or-rgb),0.22);
    border-radius: 8px;
    display: grid; grid-template-columns: 240px 1fr;
    overflow: hidden;
    transition: background 0.4s, transform 0.3s;
  }
  .bienetre-card:hover {
    background: rgba(255,255,255,0.08);
    transform: translateY(-3px);
  }

  .bienetre-card-img {
    overflow: hidden; height: 100%; min-height: 220px;
  }
  .bienetre-card-img img {
    width: 100%; height: 100%;
    object-fit: cover; display: block;
    transition: transform 0.6s ease;
  }
  .bienetre-card:hover .bienetre-card-img img { transform: scale(1.06); }

  .bienetre-card-body { padding: 32px; display: flex; flex-direction: column; justify-content: center; }
  .bienetre-card-name {
    font-family: 'Cormorant Garamond', serif; font-weight: 500;
    font-size: 1.3rem; color: #ffffff;
    margin-bottom: 10px; letter-spacing: 0.03em;
  }
  .bienetre-card-desc {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.78rem; color: rgba(255,255,255,0.75);
    line-height: 1.9; letter-spacing: 0.03em;
  }

  /* ── Événements & Affaires ── */
  .events-section { padding: 120px 0; background: #ffffff; }
  .events-inner { padding: 0 80px; }
  .events-layout {
    display: grid; grid-template-columns: 1.1fr 1fr;
    gap: 60px; align-items: start; margin-top: 64px;
  }

  .events-list { display: flex; flex-direction: column; gap: 12px; }

  .event-item {
    display: flex; align-items: flex-start; gap: 20px;
    padding: 24px 28px;
    background: var(--blanc, #fbf9f4);
    border: 1px solid rgba(var(--or-rgb),0.12);
    border-left: 3px solid transparent;
    border-radius: 6px;
    transition: all 0.3s;
    cursor: default;
  }
  .event-item:hover {
    border-left-color: var(--or);
    background: #ffffff;
    box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    transform: translateX(4px);
  }

  .event-name {
    font-family: 'Cormorant Garamond', serif; font-weight: 500;
    font-size: 1.2rem; color: var(--noir, #111111);
    margin-bottom: 6px; letter-spacing: 0.02em;
  }
  .event-desc {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.78rem; color: #4a5568;
    line-height: 1.9; letter-spacing: 0.02em;
  }

  .events-img-wrap { position: relative; }
  .events-main-img {
    width: 100%; aspect-ratio: 4/3;
    object-fit: cover; display: block;
    border-radius: 8px;
  }
  .events-img-badge {
    position: absolute; bottom: 28px; left: -24px;
    background: var(--noir, #111111); padding: 20px 28px;
    border-left: 3px solid var(--or);
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
  }
  .events-img-badge-num {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: 2.2rem; color: var(--or); line-height: 1; display: block;
  }
  .events-img-badge-label {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.55rem; letter-spacing: 0.35em; text-transform: uppercase;
    color: rgba(255,255,255,0.85); display: block; margin-top: 6px;
  }

  /* ── Galerie ── */
  .gallery-section { padding: 120px 0; background: var(--blanc, #fbf9f4); }
  .gallery-header { padding: 0 80px; display: flex; align-items: flex-end; justify-content: space-between; margin-bottom: 60px; }
  .gallery-heading { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: clamp(2rem, 3vw, 2.8rem); color: var(--noir, #111111); line-height: 1.2; }
  .gallery-heading em { font-style: italic; color: var(--or); }
  .gallery-note { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.7rem; color: #718096; letter-spacing: 0.25em; text-transform: uppercase; }
  .gallery-mosaic { display: grid; grid-template-columns: 2fr 1fr 1fr; grid-template-rows: 280px 280px; gap: 4px; padding: 0 80px; }
  .mosaic-item { overflow: hidden; position: relative; }
  .mosaic-item:first-child { grid-row: span 2; }
  .mosaic-item img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.7s ease; }
  .mosaic-item:hover img { transform: scale(1.05); }
  .mosaic-caption {
    position: absolute; bottom: 0; left: 0; right: 0; padding: 20px;
    background: linear-gradient(transparent, rgba(var(--noir-rgb),0.75));
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.65rem;
    letter-spacing: 0.25em; text-transform: uppercase; color: #ffffff;
    opacity: 0; transition: opacity 0.4s;
  }
  .mosaic-item:hover .mosaic-caption { opacity: 1; }

  /* ── Équipe ── */
  .team-section { padding: 120px 0; background: #ffffff; }
  .team-header { text-align: center; padding: 0 80px; margin-bottom: 70px; }
  .team-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; padding: 0 80px; }
  .team-card { position: relative; overflow: hidden; cursor: pointer; background: var(--blanc, #fbf9f4); border: 1px solid rgba(var(--or-rgb),0.15); }
  .team-photo { position: relative; aspect-ratio: 3/4; overflow: hidden; }
  .team-photo img { width: 100%; height: 100%; object-fit: cover; object-position: top; transition: transform 0.6s ease; }
  .team-card:hover .team-photo img { transform: scale(1.04); }
  .team-photo-overlay { position: absolute; inset: 0; background: linear-gradient(180deg, transparent 50%, rgba(var(--noir-rgb),0.7) 100%); opacity: 0; transition: opacity 0.5s; }
  .team-card:hover .team-photo-overlay { opacity: 1; }
  .team-info { padding: 24px 20px; background: #ffffff; border-top: 2px solid transparent; transition: border-color 0.4s; }
  .team-card:hover .team-info { border-top-color: var(--or); }
  .team-name { font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: 1.2rem; color: var(--noir, #111111); letter-spacing: 0.03em; margin-bottom: 4px; }
  .team-role { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.6rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--or); }
  .team-bio { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.76rem; color: #718096; line-height: 1.8; letter-spacing: 0.02em; margin-top: 10px; }

  /* ── Valeurs ── */
  .values-section { padding: 100px 0; background: var(--blanc, #fbf9f4); overflow: hidden; }
  .values-header { text-align: center; margin-bottom: 70px; padding: 0 80px; }
  .values-timeline { display: flex; padding: 0 80px; position: relative; }
  .values-timeline::before {
    content: ''; position: absolute; top: 28px; left: 80px; right: 80px;
    height: 1px; background: linear-gradient(to right, transparent, rgba(var(--or-rgb),0.3), transparent);
  }
  .value-item { flex: 1; padding: 0 24px; text-align: center; position: relative; }
  .value-dot { width: 12px; height: 12px; border-radius: 50%; border: 2px solid var(--or); background: var(--blanc, #fbf9f4); margin: 22px auto 28px; transition: background 0.3s; }
  .value-item:hover .value-dot { background: var(--or); }
  .value-name { font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: 1.3rem; color: var(--noir, #111111); margin-bottom: 10px; }
  .value-desc { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.78rem; color: #4a5568; line-height: 1.85; letter-spacing: 0.02em; }

  /* ── CTA ── */
  .about-cta { position: relative; padding: 140px 0; overflow: hidden; text-align: center; }
  .about-cta-bg {
    position: absolute; inset: 0;
    background: linear-gradient(rgba(var(--noir-rgb),0.75), rgba(var(--noir-rgb),0.75)),
      url('https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=1920&q=85') center/cover no-repeat;
  }
  .about-cta-content { position: relative; z-index: 2; max-width: 600px; margin: 0 auto; padding: 0 32px; }
  .about-cta-label { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.58rem; letter-spacing: 0.65em; text-transform: uppercase; color: var(--or); display: block; margin-bottom: 24px; }
  .about-cta-title { font-family: 'Cormorant Garamond', serif; font-weight: 300; font-size: clamp(2rem, 4vw, 3.5rem); color: #ffffff; line-height: 1.2; margin-bottom: 20px; }
  .about-cta-title em { font-style: italic; color: var(--or-pale); }
  .about-cta-text { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.85rem; color: rgba(255,255,255,0.8); letter-spacing: 0.03em; line-height: 2; margin-bottom: 48px; }
  .about-cta-btns { display: flex; align-items: center; justify-content: center; gap: 20px; flex-wrap: wrap; }
  .btn-cta-or { font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.65rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--noir, #111111); background: var(--or); padding: 16px 44px; text-decoration: none; display: inline-block; transition: background 0.3s, transform 0.25s; border-radius: 4px; }
  .btn-cta-or:hover { background: var(--or-clair); transform: translateY(-2px); color: var(--noir, #111111); }
  .btn-cta-ghost { font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.65rem; letter-spacing: 0.35em; text-transform: uppercase; color: rgba(255,255,255,0.9); border: 1px solid rgba(255,255,255,0.4); padding: 15px 40px; text-decoration: none; display: inline-block; transition: all 0.3s; border-radius: 4px; }
  .btn-cta-ghost:hover { border-color: var(--or); color: var(--or); }

  /* ── Reveal ── */
  .reveal { opacity: 0; transform: translateY(40px); transition: opacity 0.9s ease, transform 0.9s ease; }
  .reveal.visible { opacity: 1; transform: translateY(0); }
  .reveal-left { opacity: 0; transform: translateX(-50px); transition: opacity 0.9s ease, transform 0.9s ease; }
  .reveal-right { opacity: 0; transform: translateX(50px); transition: opacity 0.9s ease, transform 0.9s ease; }
  .reveal-left.visible, .reveal-right.visible { opacity: 1; transform: translateX(0); }

  /* ── Expérience Signature §4 ── */
  .signature-exp-section { padding: 0; background: #ffffff; }

  .nautique-wrap {
    display: grid; grid-template-columns: 1fr 1fr; min-height: 460px;
  }
  .nautique-img { position: relative; overflow: hidden; }
  .nautique-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.8s; }
  .nautique-wrap:hover .nautique-img img { transform: scale(1.04); }
  .nautique-img-overlay { position: absolute; inset: 0; background: linear-gradient(90deg, transparent 50%, rgba(var(--noir-rgb),0.5) 100%); }
  .nautique-content {
    background: linear-gradient(145deg, var(--noir, #111111) 0%, var(--noir-surface, #181818) 100%);
    padding: 60px 56px;
    display: flex; flex-direction: column; justify-content: center; position: relative;
  }
  .nautique-content::before {
    content: ''; position: absolute; top: 40px; left: 0;
    width: 3px; height: 60px; background: linear-gradient(to bottom, var(--or), transparent);
  }
  .nautique-tag { font-family:'Jost',sans-serif; font-weight:300; font-size:0.55rem; letter-spacing:0.6em; text-transform:uppercase; color:var(--or); display:block; margin-bottom:16px; }
  .nautique-title { font-family:'Cormorant Garamond',serif; font-weight:300; font-size:clamp(1.8rem,3vw,2.8rem); color:#ffffff; line-height:1.2; margin-bottom:20px; }
  .nautique-title em { font-style:italic; color:var(--or-pale); }
  .nautique-desc { font-family:'Jost',sans-serif; font-weight:300; font-size:0.82rem; color:rgba(255,255,255,0.78); line-height:2; letter-spacing:0.03em; margin-bottom:32px; }
  .nautique-items { display:flex; flex-direction:column; gap:16px; }
  .nautique-item { display:flex; align-items:flex-start; gap:16px; padding:18px 22px; background:rgba(255,255,255,0.05); border:1px solid rgba(var(--or-rgb),0.2); border-left:3px solid var(--or); border-radius:6px; transition:all 0.3s; }
  .nautique-item:hover { background:rgba(255,255,255,0.09); transform: translateX(4px); }
  .nautique-item-name { font-family:'Cormorant Garamond',serif; font-weight:500; font-size:1.15rem; color:#ffffff; display:block; margin-bottom:4px; }
  .nautique-item-desc { font-family:'Jost',sans-serif; font-weight:300; font-size:0.75rem; color:rgba(255,255,255,0.75); letter-spacing:0.02em; line-height:1.7; }

  .exp-duo { display:grid; grid-template-columns:1fr 1fr; gap:4px; margin-top:4px; }
  .exp-card { position:relative; overflow:hidden; min-height:400px; display:flex; flex-direction:column; justify-content:flex-end; }
  .exp-card-bg { position:absolute; inset:0; background-size:cover; background-position:center; transition:transform 0.8s; }
  .exp-card:hover .exp-card-bg { transform:scale(1.05); }
  .exp-card-overlay { position:absolute; inset:0; background:linear-gradient(180deg, transparent 20%, rgba(var(--noir-rgb),0.9) 100%); }
  .exp-card-body { position:relative; z-index:2; padding:36px; }
  .exp-card-tag { font-family:'Jost',sans-serif; font-weight:300; font-size:0.55rem; letter-spacing:0.5em; text-transform:uppercase; color:var(--or); display:block; margin-bottom:10px; }
  .exp-card-title { font-family:'Cormorant Garamond',serif; font-weight:300; font-size:1.7rem; color:#ffffff; letter-spacing:0.04em; margin-bottom:12px; line-height:1.2; }
  .exp-card-title em { font-style:italic; color:var(--or-pale); }
  .exp-card-items { display:flex; flex-direction:column; gap:10px; margin-top:16px; }
  .exp-card-item { display:flex; align-items:flex-start; gap:10px; font-family:'Jost',sans-serif; font-weight:300; font-size:0.75rem; color:rgba(255,255,255,0.85); letter-spacing:0.02em; line-height:1.6; }
  .exp-card-item::before { content:'—'; color:var(--or); flex-shrink:0; }

  /* ── Localisation §5 ── */
  .location-section { padding: 120px 0; background: var(--blanc, #fbf9f4); }
  .location-inner { padding: 0 80px; }
  .location-layout { display:grid; grid-template-columns:1fr 1fr; gap:80px; align-items:start; margin-top:64px; }
  .location-intro { font-family:'Cormorant Garamond',serif; font-style:italic; font-size:1.15rem; color:#4a5568; line-height:1.9; margin-bottom:32px; }
  .location-item { display:flex; align-items:flex-start; gap:18px; padding:20px 0; border-bottom:1px solid rgba(var(--or-rgb),0.15); }
  .location-item:first-child { border-top:1px solid rgba(var(--or-rgb),0.15); }
  .location-item-icon { width:44px; height:44px; flex-shrink:0; border:1px solid rgba(var(--or-rgb),0.3); border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:1.1rem; color:var(--or); margin-top:2px; transition:all 0.3s; }
  .location-item:hover .location-item-icon { background:var(--or); color:var(--noir, #111111); border-color:var(--or); }
  .location-item-name { font-family:'Cormorant Garamond',serif; font-weight:500; font-size:1.15rem; color:var(--noir, #111111); display:block; margin-bottom:4px; }
  .location-item-desc { font-family:'Jost',sans-serif; font-weight:300; font-size:0.78rem; color:#4a5568; line-height:1.8; letter-spacing:0.02em; }
  .location-map-embed { width:100%; height:300px; display:block; border:none; filter:grayscale(20%); transition:filter 0.4s; border-radius: 8px 8px 0 0; }
  .location-map-embed:hover { filter:grayscale(0%); }
  .location-address-card { background:var(--noir, #111111); padding:26px 30px; border-top:3px solid var(--or); border-radius: 0 0 8px 8px; }
  .location-address-label { font-family:'Jost',sans-serif; font-weight:300; font-size:0.55rem; letter-spacing:0.5em; text-transform:uppercase; color:var(--or); display:block; margin-bottom:10px; }
  .location-address-text { font-family:'Cormorant Garamond',serif; font-weight:400; font-size:1.1rem; color:#ffffff; line-height:1.8; }
  .location-address-text a { color:var(--or); text-decoration:none; font-size:0.65rem; letter-spacing:0.3em; text-transform:uppercase; font-family:'Jost',sans-serif; font-weight:400; display:block; margin-top:12px; transition:color 0.3s; }
  .location-address-text a:hover { color:var(--or-clair); }

  /* ── Services §6 (Fond Sombre Garanti) ── */
  .services6-section {
    padding: 120px 0;
    background: linear-gradient(145deg, var(--noir, #111111) 0%, var(--noir-surface, #181818) 100%);
    position:relative; overflow:hidden;
  }
  .services6-section::before { content:'06'; position:absolute; bottom:-40px; left:-20px; font-family:'Cormorant Garamond',serif; font-size:20vw; color:rgba(255,255,255,0.02); line-height:1; pointer-events:none; user-select:none; }
  .services6-inner { padding: 0 80px; position:relative; z-index:2; }
  .services6-top { display:grid; grid-template-columns:1fr 1fr; gap:80px; align-items:end; margin-bottom:64px; }
  .services6-heading { font-family:'Cormorant Garamond',serif; font-weight:300; font-size:clamp(2rem,3.5vw,3.2rem); color:#ffffff; line-height:1.2; }
  .services6-heading em { font-style:italic; color:var(--or-pale); }
  .services6-intro { font-family:'Jost',sans-serif; font-weight:300; font-size:0.85rem; color:rgba(255,255,255,0.78); line-height:2; letter-spacing:0.03em; }
  .services6-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:16px; }
  .service6-card { background:rgba(255,255,255,0.04); border:1px solid rgba(var(--or-rgb),0.2); border-radius:8px; padding:36px 28px; transition:background 0.4s, transform 0.3s; }
  .service6-card:hover { background:rgba(255,255,255,0.08); transform: translateY(-4px); }
  .service6-name { font-family:'Cormorant Garamond',serif; font-weight:500; font-size:1.2rem; color:#ffffff; margin-bottom:12px; letter-spacing:0.03em; }
  .service6-desc { font-family:'Jost',sans-serif; font-weight:300; font-size:0.76rem; color:rgba(255,255,255,0.75); line-height:1.9; letter-spacing:0.02em; }
  .service6-badge { display:inline-block; margin-top:16px; font-family:'Jost',sans-serif; font-weight:300; font-size:0.52rem; letter-spacing:0.4em; text-transform:uppercase; color:var(--or); border:1px solid rgba(var(--or-rgb),0.35); padding:4px 12px; border-radius:4px; }

  @media (max-width: 1100px) {
    .story-inner, .vision-inner, .gallery-header, .gallery-mosaic,
    .team-grid, .values-timeline, .team-header, .values-header,
    .promise-inner, .gastro-inner, .bienetre-inner, .events-inner,
    .location-inner, .services6-inner { padding-left: 40px; padding-right: 40px; }
    .team-grid { grid-template-columns: repeat(2, 1fr); }
    .vision-pillars, .promise-grid { grid-template-columns: 1fr 1fr; }
    .gallery-mosaic { grid-template-columns: 1fr 1fr; grid-template-rows: auto; }
    .mosaic-item:first-child { grid-row: span 1; }
    .values-timeline { flex-wrap: wrap; }
    .value-item { flex: 0 0 50%; }
    .values-timeline::before { display: none; }
    .gastro-grid { grid-template-columns: 1fr 1fr; }
    .bienetre-top { grid-template-columns: 1fr; gap: 32px; }
    .bienetre-cards { grid-template-columns: 1fr; }
    .bienetre-card { grid-template-columns: 200px 1fr; }
    .events-layout { grid-template-columns: 1fr; gap: 48px; }
    .events-img-badge { left: 0; }
    .nautique-wrap, .location-layout, .services6-top, .exp-duo { grid-template-columns: 1fr; }
    .services6-grid { grid-template-columns: 1fr 1fr; }
  }
  @media (max-width: 767px) {
    .story-inner { grid-template-columns: 1fr; padding: 0 24px; gap: 60px; }
    .story-float-img { display: none; }
    .vision-inner { padding: 0 24px; }
    .vision-top { flex-direction: column; gap: 32px; }
    .vision-pillars, .promise-grid, .gastro-grid { grid-template-columns: 1fr; }
    .gallery-mosaic { grid-template-columns: 1fr; padding: 0 24px; }
    .gallery-header { padding: 0 24px; flex-direction: column; align-items: flex-start; gap: 12px; }
    .team-grid { grid-template-columns: 1fr 1fr; padding: 0 24px; }
    .team-header, .values-header, .promise-inner,
    .gastro-inner, .bienetre-inner, .events-inner,
    .location-inner, .services6-inner { padding: 0 24px; }
    .values-timeline { padding: 0 24px; }
    .value-item { flex: 0 0 100%; }
    .bienetre-card { grid-template-columns: 1fr; }
    .bienetre-card-img { height: 180px; }
    .services6-grid { grid-template-columns: 1fr; }
    .nautique-content { padding: 36px 24px; }
    .exp-card { min-height: 300px; }
  }
</style>

<!-- HERO -->
<section class="about-hero">
  <div class="about-hero-bg"></div>
  <div class="about-hero-overlay"></div>
  <div class="about-hero-content">
    <span class="about-eyebrow">Notre Histoire · Notre Vision · Notre Promesse</span>
    <p class="about-hero-quote">
      « Le vrai luxe ne réside pas dans l'ostentation,<br>
      mais dans la tranquillité d'esprit<br>
      et la qualité des prestations. »
      <em>— La Philosophie de l'Établissement</em>
    </p>
    <div class="about-hero-ornament">
      <span class="orn-line"></span><span class="orn-dot"></span><span class="orn-line r"></span>
    </div>
  </div>
  <div class="about-scroll">
    <span>Découvrir</span>
    <div class="about-scroll-line"></div>
  </div>
</section>

<!-- MOT DE LA DIRECTION -->
<section class="story-section">
  <div class="story-inner">
    <div class="story-image-wrap reveal-left">
      <img class="story-main-img"
        src="https://images.unsplash.com/photo-1566073771259-6a8506099945?w=800&q=80"
        alt="<?= htmlspecialchars(hotel_name()) ?> - Architecture &amp; Espaces">
      <img class="story-float-img"
        src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&q=80"
        alt="Détail de nos suites">
    </div>
    <div class="story-text reveal-right">
      <span class="section-label">Mot de la Direction</span>
      <h2 class="section-heading">
        L'hospitalité réinventée,<br>une <em>promesse tenue</em>
      </h2>
      <div class="story-divider">
        <span></span><i></i><span></span>
      </div>
      <div class="story-body">
        <p>
          « Nous avons pensé cet établissement pour ceux qui ne veulent plus choisir
          entre le raffinement d'un grand hôtel et la chaleur d'un accueil authentique. »
        </p>
        <p>
          Implanté au cœur de <?= htmlspecialchars(hotel_city()) ?>, <?= htmlspecialchars(hotel_name()) ?> est né
          d'une ambition claire : offrir aux voyageurs d'affaires comme aux familles
          un séjour d'exception, alliant confort moderne, sécurité absolue et
          standards internationaux de service.
        </p>
        <p>
          De nos chambres pensées dans les moindres détails à notre Skibar panoramique,
          chaque espace a été conçu pour faire de votre passage parmi nous un moment
          de sérénité et d'élégance.
        </p>
      </div>
      <div class="signature-block">
        <div class="signature-or"></div>
        <div>
          <span class="signature-name">La Direction Générale</span>
          <span class="signature-role"><?= htmlspecialchars(hotel_name()) ?> · <?= htmlspecialchars(hotel_city()) ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- VISION & MISSION -->
<section id="vision">
  <div class="vision-inner">
    <div class="vision-top reveal">
      <h2 class="vision-heading">
        Redéfinir l'expérience<br>hôtelière d'<em>Excellence</em>
      </h2>
      <div class="vision-intro">
        <p>
          Une offre hôtelière haut de gamme à la hauteur des plus grandes exigences. <?= htmlspecialchars(hotel_name()) ?> se positionne avec une
          ambition claire : devenir la référence du séjour de confiance.
          Un choix évident. Une garantie de qualité. Une tranquillité absolue.
        </p>
      </div>
    </div>
    <div class="vision-pillars reveal">
      <div class="pillar">
        <span class="pillar-num">01</span>
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-feather-alt"></i></div>
        <h3 class="pillar-name">Sérénité</h3>
        <p class="pillar-desc">
          Nous créons un environnement apaisant et assurons un service fluide et
          anticipatif pour libérer nos clients de tout tracas. Franchir les portes
          de notre hôtel, c'est entrer dans un havre de paix où le tumulte s'estompe.
        </p>
      </div>
      <div class="pillar">
        <span class="pillar-num">02</span>
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-gem"></i></div>
        <h3 class="pillar-name">Raffinement</h3>
        <p class="pillar-desc">
          Une attention méticuleuse à chaque détail, du design de nos espaces à la
          qualité de notre gastronomie. De nos espaces panoramiques à nos suites, chaque
          instant est une expérience esthétique et sensorielle.
        </p>
      </div>
      <div class="pillar">
        <span class="pillar-num">03</span>
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-hand-holding-heart"></i></div>
        <h3 class="pillar-name">Excellence Accessible</h3>
        <p class="pillar-desc">
          Nous créons des séjours d'exception par une offre intelligente et
          une atmosphère chaleureuse — loin de toute intimidation. L'art de vivre chez
          nous, c'est une question d'expérience et d'harmonie.
        </p>
      </div>
    </div>
  </div>
</section>

<!-- TRIPLE PROMESSE -->
<section class="promise-section">
  <div class="promise-inner">
    <div class="promise-header reveal">
      <span class="section-label">Notre Contrat avec Vous</span>
      <h2 class="section-heading" style="font-size:clamp(2rem,3.5vw,3rem);max-width:560px;margin:0 auto;">
        La Triple Promesse <em>d'Excellence</em>
      </h2>
    </div>
    <div class="promise-grid reveal">
      <div class="promise-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-shield-alt"></i></div>
        <h3 class="promise-title">La Sérénité</h3>
        <p class="promise-text">
          Franchir nos portes, c'est entrer dans un refuge. C'est l'assurance
          de trouver un havre de paix, une bulle de quiétude où le stress
          s'estompe. C'est la certitude d'un séjour sécurisé et reposant, en toute confiance.
        </p>
        <span class="promise-quote">« Un havre où tout s'apaise. »</span>
      </div>
      <div class="promise-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-certificate"></i></div>
        <h3 class="promise-title">La Qualité</h3>
        <p class="promise-text">
          L'assurance d'un standard d'excellence constant — une connexion
          fibre haut débit, une propreté irréprochable, un personnel
          formé aux standards internationaux. Une valeur sûre pour votre confort.
        </p>
        <span class="promise-quote">« L'excellence à chaque instant. »</span>
      </div>
      <div class="promise-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-heart"></i></div>
        <h3 class="promise-title">La Confiance</h3>
        <p class="promise-text">
          En séjournant à <?= htmlspecialchars(hotel_name()) ?>, nos clients se sentent valorisés et sereins.
          Ils savent qu'ils ont fait le bon choix — un choix éclairé qui reflète leur propre
          standard d'exigence. Nous leur offrons le cadre parfait pour leur bien-être.
        </p>
        <span class="promise-quote">« Le choix évident, la garantie absolue. »</span>
      </div>
    </div>
  </div>
</section>

<!-- GASTRONOMIE -->
<section class="gastro-section">
  <div class="gastro-inner">
    <div class="gastro-header reveal">
      <span class="section-label">L'Art de Recevoir</span>
      <h2 class="section-heading">
        Gastronomie &amp;<br><em>Saveurs Raffinées</em>
      </h2>
      <p style="font-family:'Cormorant Garamond',serif;font-style:italic;font-size:1.1rem;color:#718096;margin-top:12px;max-width:560px;">
        L'art de recevoir selon <?= htmlspecialchars(hotel_name()) ?> passe par une offre de restauration riche et variée,
        adaptée à chaque moment de la journée.
      </p>
    </div>

    <div class="gastro-grid reveal">

      <!-- Restaurant Signature -->
      <div class="gastro-card">
        <div class="gastro-card-img">
          <img src="https://images.unsplash.com/photo-1551218808-94e220e084d2?w=900&q=80" alt="Restaurant Signature">
          <span class="gastro-card-tag">Restaurant Principal</span>
        </div>
        <div class="gastro-card-body">
          <h3 class="gastro-card-name">Le Restaurant Signature</h3>
          <p class="gastro-card-desc">
            Notre restaurant principal vous accueille dans un cadre élégant et lumineux.
            Notre Chef y propose une cuisine inventive qui célèbre le meilleur des produits
            locaux tout en s'inspirant des saveurs du monde.
          </p>
          <span class="gastro-card-note">Déjeuner d'affaires · Dîner mémorable</span>
        </div>
      </div>

      <!-- Skibar Panoramique -->
      <div class="gastro-card">
        <div class="gastro-card-img">
          <img src="https://images.unsplash.com/photo-1574096079513-d8259312b785?w=600&q=80" alt="Skibar Panoramique">
          <span class="gastro-card-tag">Rooftop · Vue Panoramique</span>
        </div>
        <div class="gastro-card-body">
          <h3 class="gastro-card-name">Le Skibar Panoramique</h3>
          <p class="gastro-card-desc">
            Point d'orgue de l'hôtel, notre Skibar offre une vue imprenable sur les toits
            de <?= htmlspecialchars(hotel_city()) ?>. Cocktails créatifs, tapas gourmandes et ambiance musicale chic —
            le nouveau lieu de rendez-vous incontournable.
          </p>
          <span class="gastro-card-note">Fin de journée · Soirée privée · Événements</span>
        </div>
      </div>

      <!-- Lounge & Bar -->
      <div class="gastro-card">
        <div class="gastro-card-img">
          <img src="https://images.unsplash.com/photo-1559329007-40df8a9345d8?w=600&q=80" alt="Lounge Bar Lobby">
          <span class="gastro-card-tag">Lobby · Lounge</span>
        </div>
        <div class="gastro-card-body">
          <h3 class="gastro-card-name">Le Lounge &amp; Bar du Lobby</h3>
          <p class="gastro-card-desc">
            Chaleureux et accueillant, notre bar du lobby est le point de rencontre idéal.
            Parfait pour un café en journée, un rendez-vous informel ou un dernier verre
            dans une atmosphère feutrée.
          </p>
          <span class="gastro-card-note">Café · Rendez-vous informel · Dernier verre</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- BIEN-ÊTRE & LOISIRS -->
<section class="bienetre-section">
  <div class="bienetre-inner">

    <div class="bienetre-top reveal">
      <div>
        <span class="section-label" style="color:var(--or);">Détente &amp; Évasion</span>
        <h2 class="bienetre-heading">
          Bien-être &amp;<br><em>Loisirs</em>
        </h2>
      </div>
      <p class="bienetre-intro">
        Parce que le bien-être est au cœur de la sérénité, nos espaces de loisirs
        sont conçus comme une invitation à la relaxation. Chaque espace est pensé
        pour que vous puissiez vous ressourcer pleinement, à votre rythme.
      </p>
    </div>

    <div class="bienetre-cards reveal">

      <!-- Piscine & Jacuzzi -->
      <div class="bienetre-card">
        <div class="bienetre-card-img">
          <img src="https://images.pexels.com/photos/37585969/pexels-photo-37585969.jpeg" alt="Piscine &amp; Jacuzzi">
        </div>
        <div class="bienetre-card-body">
          <div class="about-icon-badge" style="margin-bottom:16px;"><i class="fas fa-water"></i></div>
          <h3 class="bienetre-card-name">La Piscine &amp; son Jacuzzi</h3>
          <p class="bienetre-card-desc">
            Entourée d'un solarium aménagé, notre piscine est une véritable oasis.
            Parfaite pour quelques longueurs matinales ou pour se prélasser au soleil.
            Le jacuzzi attenant promet des moments de détente absolue.
          </p>
        </div>
      </div>

      <!-- Espace Fitness -->
      <div class="bienetre-card">
        <div class="bienetre-card-img">
          <img src="https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=600&q=80" alt="Espace Fitness">
        </div>
        <div class="bienetre-card-body">
          <div class="about-icon-badge" style="margin-bottom:16px;"><i class="fas fa-dumbbell"></i></div>
          <h3 class="bienetre-card-name">L'Espace Fitness</h3>
          <p class="bienetre-card-desc">
            Pour garder la forme, notre salle de sport est équipée des dernières
            technologies en matière d'équipements de cardio-training et de musculation.
            Accessible à toute heure pour nos clients.
          </p>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ÉVÉNEMENTS & AFFAIRES -->
<section class="events-section">
  <div class="events-inner">

    <div class="reveal">
      <span class="section-label">Le Cadre de Vos Succès</span>
      <h2 class="section-heading">
        Événements &amp;<br><em>Espaces Affaires</em>
      </h2>
      <p style="font-family:'Cormorant Garamond',serif;font-style:italic;font-size:1.1rem;color:#718096;margin-top:12px;max-width:560px;">
        Nous offrons aux professionnels un environnement optimal pour travailler
        et se réunir, soutenu par une technologie de pointe.
      </p>
    </div>

    <div class="events-layout reveal">

      <div class="events-list">

        <div class="event-item">
          <div class="about-icon-badge"><i class="fas fa-microphone-alt"></i></div>
          <div>
            <h4 class="event-name">Salles de Séminaire &amp; Conférence</h4>
            <p class="event-desc">
              Modulables, climatisées et baignées de lumière naturelle, nos salles
              peuvent accueillir vos réunions, formations et événements corporate.
              Toutes équipées de vidéoprojecteurs, systèmes de sonorisation
              et connexion Wi-Fi très haut débit dédiée.
            </p>
          </div>
        </div>

        <div class="event-item">
          <div class="about-icon-badge"><i class="fas fa-laptop"></i></div>
          <div>
            <h4 class="event-name">Le Centre d'Affaires</h4>
            <p class="event-desc">
              En accès libre pour nos clients, notre centre d'affaires propose
              des postes de travail connectés et des services d'impression pour
              répondre à tous les besoins professionnels de dernière minute.
            </p>
          </div>
        </div>

        <div class="event-item">
          <div class="about-icon-badge"><i class="fas fa-wifi"></i></div>
          <div>
            <h4 class="event-name">Connexion Fibre Optique Garantie</h4>
            <p class="event-desc">
              L'ensemble de l'hôtel est équipé d'une connexion très haut débit
              par fibre optique — visioconférences sans coupure, téléchargements
              rapides, Wi-Fi managé et gratuit dans la totalité de l'établissement.
            </p>
          </div>
        </div>

        <div class="event-item">
          <div class="about-icon-badge"><i class="fas fa-glass-cheers"></i></div>
          <div>
            <h4 class="event-name">Le Skibar en Privatisation</h4>
            <p class="event-desc">
              Espace privatisable unique pour vos lancements de produits, cocktails
              d'entreprise ou soirées privées. La vue panoramique garantit
              un événement qui marquera les esprits.
            </p>
          </div>
        </div>

      </div>

      <div class="events-img-wrap reveal-right">
        <img class="events-main-img"
          src="https://images.unsplash.com/photo-1517457373958-b7bdd4587205?w=900&q=80"
          alt="Salle de conférence &amp; séminaires">
        <div class="events-img-badge">
          <span class="events-img-badge-num">120</span>
          <span class="events-img-badge-label">Personnes max<br>· Capacité événement ·</span>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     §4 — EXPÉRIENCE SIGNATURE
══════════════════════════════════════════ -->
<div class="signature-exp-section">

  <!-- 4.1 Nautique -->
  <div class="nautique-wrap reveal">
    <div class="nautique-img">
      <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=900&q=85" alt="Côte maritime et océan">
      <div class="nautique-img-overlay"></div>
    </div>
    <div class="nautique-content">
      <span class="nautique-tag">Exclusivité &amp; Évasion</span>
      <h2 class="nautique-title">
        Séjournez en ville,<br><em>évadez-vous sur l'océan</em>
      </h2>
      <p class="nautique-desc">
        <?= htmlspecialchars(hotel_name()) ?> vous ouvre les portes
        de l'évasion maritime avec ses prestations exclusives et ses partenaires de prestige.
      </p>
      <div class="nautique-items">
        <div class="nautique-item">
          <div class="about-icon-badge"><i class="fas fa-ship"></i></div>
          <div>
            <span class="nautique-item-name">Sensations en Jet Ski</span>
            <span class="nautique-item-desc">Sessions nautiques pour découvrir le littoral sous un angle nouveau et vivifiant.</span>
          </div>
        </div>
        <div class="nautique-item">
          <div class="about-icon-badge"><i class="fas fa-compass"></i></div>
          <div>
            <span class="nautique-item-name">Croisières Privées en Yacht</span>
            <span class="nautique-item-desc">Apéritif au coucher du soleil, réunion confidentielle ou célébration privée au large — des souvenirs impérissables.</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- 4.2 Skibar + 4.3 Connexion -->
  <div class="exp-duo reveal">

    <div class="exp-card">
      <div class="exp-card-bg" style="background-image:url('https://images.unsplash.com/photo-1589182373726-e4f658ab50f0?w=900&q=85');"></div>
      <div class="exp-card-overlay"></div>
      <div class="exp-card-body">
        <span class="exp-card-tag">Lieu d'Exception</span>
        <h3 class="exp-card-title">Le <em>Skibar</em><br>Panoramique</h3>
        <div class="exp-card-items">
          <div class="exp-card-item">Le Spot Panoramique : l'une des vues les plus spectaculaires, idéal pour admirer le coucher de soleil.</div>
          <div class="exp-card-item">Espace privatisable exclusif pour lancements, cocktails d'entreprise ou soirées d'exception.</div>
          <div class="exp-card-item">Clientèle internationale et locale dans une atmosphère chic et décontractée.</div>
        </div>
      </div>
    </div>

    <div class="exp-card">
      <div class="exp-card-bg" style="background-image:url('https://images.unsplash.com/photo-1531482615713-2afd69097998?w=900&q=85');"></div>
      <div class="exp-card-overlay"></div>
      <div class="exp-card-body">
        <span class="exp-card-tag">Garantie Business</span>
        <h3 class="exp-card-title">Connexion<br><em>Sans Faille</em></h3>
        <div class="exp-card-items">
          <div class="exp-card-item">Technologie Fibre Optique dans l'ensemble de l'établissement — navigation fluide, visioconférences sans coupure.</div>
          <div class="exp-card-item">Wi-Fi Sécurisé et Gratuit, signal stable et performant dans toutes les chambres et salons de réunion.</div>
          <div class="exp-card-item">Pour nos clients professionnels, la connectivité est une garantie fondamentale.</div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════════
     §5 — LOCALISATION
══════════════════════════════════════════ -->
<section class="location-section">
  <div class="location-inner">

    <div class="reveal">
      <span class="section-label">Notre Ancrage</span>
      <h2 class="location-heading">
        <?= htmlspecialchars(hotel_city()) ?>, un emplacement<br><em>stratégique</em>
      </h2>
    </div>

    <div class="location-layout">
      <div class="reveal">
        <p class="location-intro">
          Idéalement situé à <?= htmlspecialchars(hotel_location()) ?>,
          <?= htmlspecialchars(hotel_name()) ?> offre le parfait équilibre entre l'effervescence
          et la quiétude nécessaire à votre repos.
        </p>
        <div style="display:flex;flex-direction:column;">
          <div class="location-item">
            <div class="location-item-icon"><i class="fas fa-briefcase"></i></div>
            <div>
              <span class="location-item-name">Clientèle d'Affaires</span>
              <span class="location-item-desc">Proximité avec les centres d'affaires et pôles décisionnels. Le cadre idéal pour optimiser votre séjour.</span>
            </div>
          </div>
          <div class="location-item">
            <div class="location-item-icon"><i class="fas fa-umbrella-beach"></i></div>
            <div>
              <span class="location-item-name">Voyageurs Loisirs</span>
              <span class="location-item-desc">Point de départ parfait pour découvrir les merveilles de la région et ses paysages authentiques.</span>
            </div>
          </div>
          <div class="location-item">
            <div class="location-item-icon"><i class="fas fa-plane-departure"></i></div>
            <div>
              <span class="location-item-name">Accès Facilité</span>
              <span class="location-item-desc">Accès rapide aux grands axes. Service de transfert privé disponible sur réservation.</span>
            </div>
          </div>
          <div class="location-item">
            <div class="location-item-icon"><i class="fas fa-parking"></i></div>
            <div>
              <span class="location-item-name">Parking Sécurisé</span>
              <span class="location-item-desc">Espace de stationnement privé et surveillé 24h/24 pour une totale sérénité.</span>
            </div>
          </div>
        </div>
      </div>

      <div class="reveal-right">
        <iframe
          class="location-map-embed"
          src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31797.28!2d1.60!3d6.22!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1023ef94b4b4b4b4%3A0x0!2sAgbodrafo%2C+Togo!5e0!3m2!1sfr!2sfr!4v1"
          allowfullscreen="" loading="lazy"
          referrerpolicy="no-referrer-when-downgrade"
          title="<?= htmlspecialchars(hotel_name()) ?>">
        </iframe>
        <div class="location-address-card">
          <span class="location-address-label">Adresse de l'établissement</span>
          <div class="location-address-text">
            <?= htmlspecialchars(hotel_name()) ?><br>
            <?= htmlspecialchars(hotel_location()) ?><br>
            <?= htmlspecialchars(hotel_country()) ?>
            <a href="https://maps.google.com/?q=<?= urlencode(hotel_name() . ' ' . hotel_location()) ?>" target="_blank">
              Ouvrir dans Google Maps →
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     §6 — EXCELLENCE DU SERVICE
══════════════════════════════════════════ -->
<section class="services6-section">
  <div class="services6-inner">

    <div class="services6-top reveal">
      <div>
        <span class="section-label" style="color:var(--or);">Notre Engagement</span>
        <h2 class="services6-heading">
          L'Excellence<br>du <em>Service</em>
        </h2>
      </div>
      <p class="services6-intro">
        La véritable âme de <?= htmlspecialchars(hotel_name()) ?> réside dans la passion de nos équipes.
        De la réception à la restauration, en passant par le service d'étage et
        la conciergerie, vous trouverez toujours un interlocuteur disponible,
        souriant et proactif. Nous ne nous contentons pas de vous servir ;
        nous prenons soin de vous.
      </p>
    </div>

    <div class="services6-grid reveal">

      <div class="service6-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-concierge-bell"></i></div>
        <h4 class="service6-name">Conciergerie Dédiée</h4>
        <p class="service6-desc">Réserver un taxi, organiser une visite guidée, recommander le meilleur restaurant local — notre conciergerie est votre allié pour un séjour réussi.</p>
        <span class="service6-badge">Disponible 24h/24</span>
      </div>

      <div class="service6-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-utensils"></i></div>
        <h4 class="service6-name">Room Service 24h/24</h4>
        <p class="service6-desc">Une petite faim en pleine nuit ou un dîner en toute intimité ? Notre service en chambre propose une sélection de plats à toute heure.</p>
        <span class="service6-badge">Toute la nuit</span>
      </div>

      <div class="service6-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-user-tie"></i></div>
        <h4 class="service6-name">Blanchisserie &amp; Pressing</h4>
        <p class="service6-desc">Un service rapide et soigné pour être impeccable en toutes circonstances lors de votre séjour. Nettoyage à sec disponible.</p>
        <span class="service6-badge">Express disponible</span>
      </div>

      <div class="service6-card">
        <div class="about-icon-badge about-icon-badge-lg"><i class="fas fa-car"></i></div>
        <h4 class="service6-name">Parking Sécurisé</h4>
        <p class="service6-desc">Un espace de stationnement privé et surveillé à la disposition de notre clientèle pour une tranquillité d'esprit totale.</p>
        <span class="service6-badge">Inclus</span>
      </div>

    </div>
  </div>
</section>

<!-- GALERIE -->
<section class="gallery-section">
  <div class="gallery-header reveal">
    <div>
      <span class="section-label">L'Hôtel en images</span>
      <h2 class="gallery-heading">Le cadre qui<br>fait la <em>différence</em></h2>
    </div>
    <span class="gallery-note">Espaces · Skibar · Piscine · Suites</span>
  </div>
  <div class="gallery-mosaic reveal">
    <div class="mosaic-item">
      <img src="https://images.unsplash.com/photo-1609137144813-7d9921338f24?w=900&q=80" alt="<?= htmlspecialchars(hotel_name()) ?>">
      <div class="mosaic-caption">L'hôtel · Vue d'ensemble</div>
    </div>
    <div class="mosaic-item">
      <img src="https://images.unsplash.com/photo-1540541338537-1220059b27be?w=600&q=80" alt="Piscine & Jacuzzi">
      <div class="mosaic-caption">Piscine & Jacuzzi</div>
    </div>
    <div class="mosaic-item">
      <img src="https://images.unsplash.com/photo-1551218808-94e220e084d2?w=600&q=80" alt="Restaurant Signature">
      <div class="mosaic-caption">Restaurant Signature</div>
    </div>
    <div class="mosaic-item">
      <img src="https://images.unsplash.com/photo-1612965607446-25e1332775ae?w=600&q=80" alt="Suites d'exception">
      <div class="mosaic-caption">Suite avec vue</div>
    </div>
    <div class="mosaic-item">
      <img src="https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80" alt="<?= htmlspecialchars(hotel_location()) ?>">
      <div class="mosaic-caption"><?= htmlspecialchars(hotel_location()) ?></div>
    </div>
  </div>
</section>

<!-- ÉQUIPE -->
<section class="team-section">
  <div class="team-header reveal">
    <span class="section-label">Les Ambassadeurs de l'Excellence</span>
    <h2 class="team-heading" style="font-size:clamp(2rem,3.5vw,3rem);max-width:500px;margin:0 auto;">
      Une équipe passionnée,<br>à votre <em>service</em>
    </h2>
  </div>
  <div class="team-grid reveal">
    <div class="team-card">
      <div class="team-photo">
        <img src="https://images.unsplash.com/photo-1560250097-0b93528c311a?w=500&q=80" alt="Direction Générale">
        <div class="team-photo-overlay"></div>
      </div>
      <div class="team-info">
        <h4 class="team-name">Direction Générale</h4>
        <span class="team-role">Management &amp; Qualité</span>
        <p class="team-bio">Incarne au quotidien notre philosophie : excellence du service, hospitalité attentive et prestations irréprochables.</p>
      </div>
    </div>
    <div class="team-card">
      <div class="team-photo">
        <img src="https://images.unsplash.com/photo-1607631568010-a87245c0daf8?w=500&q=80" alt="Chef Signature">
        <div class="team-photo-overlay"></div>
      </div>
      <div class="team-info">
        <h4 class="team-name">Notre Chef Signature</h4>
        <span class="team-role">Chef Exécutif · Restaurant Signature</span>
        <p class="team-bio">Il célèbre le meilleur des produits locaux en s'inspirant des saveurs du monde — pour une cuisine inventive et mémorable à chaque service.</p>
      </div>
    </div>
    <div class="team-card">
      <div class="team-photo">
        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=500&q=80" alt="Chef Concierge">
        <div class="team-photo-overlay"></div>
      </div>
      <div class="team-info">
        <h4 class="team-name">Notre Conciergerie</h4>
        <span class="team-role">Service Conciergerie & Réception</span>
        <p class="team-bio">Disponibles 24h/24, nos équipes de réception et de conciergerie sont vos alliés pour un séjour fluide, serein et parfaitement organisé.</p>
      </div>
    </div>
    <div class="team-card">
      <div class="team-photo">
        <img src="https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?w=500&q=80" alt="Responsable Événements">
        <div class="team-photo-overlay"></div>
      </div>
      <div class="team-info">
        <h4 class="team-name">Notre Équipe Événements</h4>
        <span class="team-role">Séminaires & Événements Corporate</span>
        <p class="team-bio">Des espaces privatisés aux salles de conférence modulables, notre équipe orchestre chaque événement pour qu'il marque les esprits.</p>
      </div>
    </div>
  </div>
</section>

<!-- VALEURS -->
<section class="values-section">
  <div class="values-header reveal">
    <span class="section-label">Ce qui nous anime</span>
    <h2 class="section-heading" style="font-size:clamp(2rem,3.5vw,3rem);max-width:480px;margin:0 auto;">
      Nos <em>valeurs</em>,<br>notre identité
    </h2>
  </div>
  <div class="values-timeline reveal">
    <div class="value-item">
      <div class="value-dot"></div>
      <div class="about-icon-badge about-icon-badge-lg" style="margin: 0 auto 18px;"><i class="fas fa-dove"></i></div>
      <h4 class="value-name">Sérénité</h4>
      <p class="value-desc">Chaque espace, chaque interaction est pensée pour libérer nos clients du stress. Un vrai havre de paix dédié au ressourcement.</p>
    </div>
    <div class="value-item">
      <div class="value-dot"></div>
      <div class="about-icon-badge about-icon-badge-lg" style="margin: 0 auto 18px;"><i class="fas fa-bullseye"></i></div>
      <h4 class="value-name">Excellence</h4>
      <p class="value-desc">Des standards internationaux dans chaque prestation : connectivité haut débit, literie de prestige, gastronomie soignée, propreté irréprochable.</p>
    </div>
    <div class="value-item">
      <div class="value-dot"></div>
      <div class="about-icon-badge about-icon-badge-lg" style="margin: 0 auto 18px;"><i class="fas fa-heart"></i></div>
      <h4 class="value-name">Hospitalité</h4>
      <p class="value-desc">La chaleur authentique de l'accueil attentionné au service de vos attentes — nous veillons sur chaque instant de votre séjour.</p>
    </div>
    <div class="value-item">
      <div class="value-dot"></div>
      <div class="about-icon-badge about-icon-badge-lg" style="margin: 0 auto 18px;"><i class="fas fa-lightbulb"></i></div>
      <h4 class="value-name">Harmonie</h4>
      <p class="value-desc">Le raffinement harmonieux n'est pas un compromis — c'est une philosophie. Se sentir parfaitement privilégié dans un cadre exceptionnel.</p>
    </div>
  </div>
</section>

<!-- CTA FINAL -->
<section class="about-cta">
  <div class="about-cta-bg"></div>
  <div class="about-cta-content reveal">
    <span class="about-cta-label">Vivez l'expérience <?= htmlspecialchars(hotel_short_name()) ?></span>
    <h2 class="about-cta-title">
      Faites de votre séjour<br>une <em>promesse tenue</em>
    </h2>
    <p class="about-cta-text">
      Nous sommes impatients de vous accueillir et de faire de votre séjour une
      expérience mémorable. <?= htmlspecialchars(hotel_name()) ?> — votre refuge d'excellence à <?= htmlspecialchars(hotel_location()) ?>.
    </p>
    <div class="about-cta-btns">
      <a href="<?= $baseUrl ?>/pages/reservation-system.php" class="btn-cta-or">Réserver maintenant</a>
      <a href="<?= $baseUrl ?>/pages/contact.php" class="btn-cta-ghost">Nous contacter</a>
    </div>
  </div>
</section>

<script>
  const allReveals = document.querySelectorAll('.reveal, .reveal-left, .reveal-right');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        setTimeout(() => entry.target.classList.add('visible'), i * 80);
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.08 });
  allReveals.forEach(el => observer.observe(el));
</script>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>