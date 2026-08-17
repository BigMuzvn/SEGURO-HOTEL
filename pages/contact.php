<?php include(__DIR__ . '/../layouts/header.php'); ?>

<style>
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(28px); }
    to   { opacity: 1; transform: translateY(0); }
  }
  @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

  /* ── Hero split (Garantie contraste 100% multi-thèmes) ── */
  .contact-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 60vh;
    margin-top: 0;
  }
  .contact-hero-img {
    position: relative;
    overflow: hidden;
    min-height: 480px;
  }
  .contact-hero-img-bg {
    position: absolute; inset: 0;
    background: url('https://images.unsplash.com/photo-1609137144813-7d9921338f24?w=1200&q=85') center/cover no-repeat;
    transition: transform 0.8s ease;
  }
  .contact-hero-img:hover .contact-hero-img-bg { transform: scale(1.04); }
  .contact-hero-img-overlay {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, rgba(var(--noir-rgb),0.6) 0%, rgba(var(--noir-rgb),0.4) 100%);
    z-index: 1;
  }
  .hero-img-badge {
    position: absolute; bottom: 40px; left: 40px; z-index: 2;
    border-left: 2px solid var(--or); padding-left: 16px;
    animation: fadeUp 1.2s ease 0.4s both;
  }
  .hero-img-badge-title {
    font-family: 'Cormorant Garamond', serif; font-weight: 400;
    font-size: 1.2rem; color: #fff; display: block; letter-spacing: 0.05em;
  }
  .hero-img-badge-sub {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.6rem; letter-spacing: 0.35em; text-transform: uppercase;
    color: var(--or-pale); display: block; margin-top: 4px;
  }

  .contact-hero-text {
    background: linear-gradient(145deg, var(--noir, #111111) 0%, var(--noir-surface, #181818) 100%);
    display: flex; flex-direction: column;
    align-items: flex-start; justify-content: center;
    padding: 80px 70px; position: relative; overflow: hidden;
    border-left: 1px solid rgba(var(--or-rgb), 0.15);
  }
  .contact-hero-text::before {
    content: ''; position: absolute; bottom: -120px; right: -80px;
    width: 350px; height: 350px; border-radius: 50%;
    border: 1px solid rgba(var(--or-rgb),0.08);
  }
  .contact-hero-text::after {
    content: ''; position: absolute; top: -80px; left: -60px;
    width: 200px; height: 200px; border-radius: 50%;
    border: 1px solid rgba(var(--or-rgb),0.06);
  }
  .contact-eyebrow {
    font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.62rem;
    letter-spacing: 0.5em; text-transform: uppercase; color: var(--or);
    display: block; margin-bottom: 24px; position: relative; z-index: 2;
    animation: fadeUp 1.2s ease 0.2s both;
  }
  .contact-hero-title {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: clamp(2.5rem, 4.5vw, 4.5rem); color: #ffffff; line-height: 1.15;
    letter-spacing: 0.04em; margin-bottom: 28px; position: relative; z-index: 2;
    animation: fadeUp 1.2s ease 0.4s both;
  }
  .contact-hero-title em { font-style: italic; color: var(--or-pale); }
  .contact-hero-desc {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.9rem;
    color: rgba(255,255,255,0.78); line-height: 1.8; letter-spacing: 0.02em;
    max-width: 440px; margin-bottom: 40px; position: relative; z-index: 2;
    animation: fadeUp 1.2s ease 0.6s both;
  }
  .contact-quick-info { display: flex; flex-direction: column; gap: 18px; position: relative; z-index: 2; animation: fadeUp 1.2s ease 0.8s both; }
  .quick-info-item { display: flex; align-items: center; gap: 16px; }
  .quick-info-icon {
    width: 38px; height: 38px;
    background: rgba(var(--or-rgb), 0.12);
    border: 1px solid rgba(var(--or-rgb), 0.35);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--or);
    font-size: 0.95rem; flex-shrink: 0;
    transition: all 0.3s ease;
  }
  .quick-info-item:hover .quick-info-icon {
    background: var(--or);
    color: var(--noir);
    transform: scale(1.08);
  }
  .quick-info-text { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.82rem; color: rgba(255,255,255,0.85); letter-spacing: 0.04em; }
  .quick-info-text a { color: var(--or-pale); text-decoration: none; font-weight: 400; transition: color 0.3s; }
  .quick-info-text a:hover { color: var(--or); text-decoration: underline; }

  /* Fil d'Ariane */
  .breadcrumb-bar {
    background: #f9f7f2; border-bottom: 1px solid rgba(var(--or-rgb),0.15);
    padding: 14px 80px; display: flex; align-items: center; gap: 10px;
  }
  .breadcrumb-bar a, .breadcrumb-bar span {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.65rem;
    letter-spacing: 0.2em; text-transform: uppercase; color: #718096;
    text-decoration: none; transition: color 0.3s;
  }
  .breadcrumb-bar a:hover { color: var(--or); }
  .breadcrumb-bar .sep { color: var(--or); font-size: 0.5rem; }
  .breadcrumb-bar .current { color: var(--noir, #111111); font-weight: 500; }

  /* ── Formulaire + Infos ── */
  .contact-main { display: grid; grid-template-columns: 1.15fr 0.85fr; gap: 0; min-height: 700px; }

  .contact-form-wrap { padding: 80px; background: #ffffff; border-right: 1px solid rgba(var(--or-rgb),0.15); }

  .section-label {
    font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.62rem;
    letter-spacing: 0.5em; text-transform: uppercase; color: var(--or-texte);
    display: block; margin-bottom: 18px;
  }
  .form-title { font-family: 'Cormorant Garamond', serif; font-weight: 400; font-size: clamp(2rem, 3.2vw, 2.8rem); color: var(--noir, #111111); line-height: 1.2; letter-spacing: 0.02em; margin-bottom: 12px; }
  .form-title em { font-style: italic; color: var(--or-texte); }
  .form-subtitle { font-family: 'Cormorant Garamond', serif; font-style: italic; font-weight: 400; font-size: 1.15rem; color: #4a5568; margin-bottom: 48px; letter-spacing: 0.02em; }

  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 28px; margin-bottom: 28px; }
  .form-group { position: relative; margin-bottom: 28px; }
  .form-row .form-group { margin-bottom: 0; }
  .form-group label { font-family: 'Jost', sans-serif; font-weight: 500; font-size: 0.75rem; letter-spacing: 0.12em; text-transform: uppercase; color: var(--noir, #111111); display: block; margin-bottom: 8px; transition: color 0.3s; }
  .form-group:focus-within label { color: var(--or-texte); }
  .form-group input, .form-group select, .form-group textarea {
    width: 100%; background: transparent; border: none;
    border-bottom: 1.5px solid var(--bordure-form); color: #111111;
    font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.95rem;
    letter-spacing: 0.02em; padding: 10px 0; outline: none;
    transition: border-color 0.3s; border-radius: 0; -webkit-appearance: none;
  }
  .form-group input:focus, .form-group select:focus, .form-group textarea:focus { border-bottom-color: var(--or); }
  .form-group input::placeholder, .form-group textarea::placeholder { color: #a0aec0; font-style: normal; opacity: 1; }
  .form-group select { cursor: pointer; color: #2d3748; font-weight: 400; }
  .form-group select option { color: #111111; }
  .form-group::after { content: ''; position: absolute; bottom: 0; left: 0; width: 0; height: 2px; background: var(--or); transition: width 0.4s ease; }
  .form-group:focus-within::after { width: 100%; }
  .form-group textarea { resize: none; min-height: 110px; line-height: 1.8; }

  .form-consent { display: flex; align-items: flex-start; gap: 14px; margin-bottom: 36px; margin-top: 8px; }
  .form-consent input[type="checkbox"] { width: 18px; height: 18px; border: 1.5px solid var(--or); background: transparent; flex-shrink: 0; margin-top: 2px; cursor: pointer; accent-color: var(--or); }
  .form-consent label { font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.82rem; color: #4a5568; letter-spacing: 0.02em; line-height: 1.7; cursor: pointer; margin-bottom: 0; }
  .form-consent label a { color: var(--or-texte); font-weight: 500; text-decoration: none; }
  .form-consent label a:hover { text-decoration: underline; }

  .btn-send { font-family: 'Jost', sans-serif; font-weight: 500; font-size: 0.72rem; letter-spacing: 0.3em; text-transform: uppercase; color: var(--noir, #111111); background: var(--or); border: 1px solid var(--or); padding: 18px 56px; cursor: pointer; transition: all 0.35s ease; display: inline-flex; align-items: center; justify-content: center; gap: 12px; }
  .btn-send:hover { background: var(--or-clair); border-color: var(--or-clair); transform: translateY(-2px); box-shadow: 0 8px 24px rgba(var(--or-rgb), 0.3); }

  .form-success { display: none; padding: 24px 28px; background: #f0fdf4; border-left: 3px solid #22c55e; border-radius: 4px; margin-top: 24px; }
  .form-success p { font-family: 'Cormorant Garamond', serif; font-style: italic; font-size: 1.15rem; color: #166534; margin: 0; }

  /* ── Infos contact ── */
  .contact-info-wrap { padding: 80px 60px; background: #faf8f3; display: flex; flex-direction: column; justify-content: space-between; }
  .info-block { margin-bottom: 44px; }
  .info-block h5 { font-family: 'Jost', sans-serif; font-weight: 500; font-size: 0.68rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--or-texte); margin-bottom: 20px; padding-bottom: 12px; border-bottom: 1px solid rgba(var(--or-rgb),0.25); }
  .info-item { display: flex; align-items: flex-start; gap: 18px; padding: 14px 0; border-bottom: 1px solid rgba(var(--or-rgb),0.12); transition: padding-left 0.3s; }
  .info-item:hover { padding-left: 6px; }
  .info-item:last-child { border-bottom: none; }
  .info-item-icon {
    width: 36px; height: 36px;
    background: rgba(var(--or-rgb), 0.12);
    border: 1px solid rgba(var(--or-rgb), 0.25);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--or);
    font-size: 0.95rem; flex-shrink: 0; margin-top: 2px;
  }
  .info-item-label { font-family: 'Jost', sans-serif; font-weight: 500; font-size: 0.68rem; letter-spacing: 0.2em; text-transform: uppercase; color: #718096; display: block; margin-bottom: 4px; }
  .info-item-value { font-family: 'Cormorant Garamond', serif; font-weight: 500; font-size: 1.15rem; color: var(--noir, #111111); letter-spacing: 0.02em; line-height: 1.4; }
  .info-item-value a { color: var(--noir, #111111); text-decoration: none; transition: color 0.3s; font-weight: 500; }
  .info-item-value a:hover { color: var(--or-texte); }

  /* Horaires */
  .horaires-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 20px; }
  .horaire-line { display: flex; justify-content: space-between; align-items: center; padding: 8px 0; border-bottom: 1px solid rgba(var(--or-rgb),0.12); font-family: 'Jost', sans-serif; font-weight: 300; }
  .horaire-service { font-size: 0.75rem; letter-spacing: 0.08em; color: #4a5568; text-transform: uppercase; }
  .horaire-time { font-size: 0.82rem; color: var(--noir, #111111); font-weight: 600; }
  .horaire-24 { color: var(--or-texte); font-style: italic; font-weight: 600; }

  .social-row { display: flex; gap: 12px; }
  .social-btn { display: flex; align-items: center; gap: 10px; padding: 10px 18px; border: 1px solid rgba(var(--or-rgb),0.35); color: #4a5568; text-decoration: none; font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.68rem; letter-spacing: 0.15em; text-transform: uppercase; transition: all 0.3s; border-radius: 4px; }
  .social-btn:hover { border-color: var(--or); color: var(--or-texte); background: rgba(var(--or-rgb),0.08); transform: translateY(-1px); }

  /* ════════════════════════════════════════════
     SECTION INTERLOCUTEURS PRIVILÉGIÉS (100% Lisible & Somptueux)
  ════════════════════════════════════════════ */
  .interlocuteurs-section {
    padding: 110px 0;
    background: linear-gradient(145deg, var(--noir, #111111) 0%, var(--noir-surface, #181818) 100%);
    position: relative; overflow: hidden;
    border-top: 1px solid rgba(var(--or-rgb), 0.2);
    border-bottom: 1px solid rgba(var(--or-rgb), 0.2);
  }
  .interlocuteurs-section::before {
    content: 'CONCIERGERIE';
    position: absolute; bottom: -30px; right: -20px;
    font-family: 'Cormorant Garamond', serif; font-weight: 700;
    font-size: 14vw; color: rgba(255,255,255,0.015);
    letter-spacing: 0.1em;
    line-height: 1; pointer-events: none;
  }
  .interlocuteurs-inner { padding: 0 80px; position: relative; z-index: 2; max-width: 1300px; margin: 0 auto; }
  .interlocuteurs-header { margin-bottom: 64px; }
  .interlocuteurs-heading {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: clamp(2.2rem, 3.8vw, 3.4rem); color: #ffffff;
    line-height: 1.2; letter-spacing: 0.03em; margin-bottom: 16px;
  }
  .interlocuteurs-heading em { font-style: italic; color: var(--or-pale); }
  .interlocuteurs-intro {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.92rem; color: rgba(255,255,255,0.78);
    line-height: 1.9; letter-spacing: 0.02em; max-width: 640px;
  }

  .interlocuteurs-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 30px;
  }
  .interlocuteur-card {
    background: rgba(255,255,255,0.035);
    border: 1px solid rgba(var(--or-rgb),0.22);
    border-radius: 12px;
    padding: 46px 42px;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
    position: relative;
    backdrop-filter: blur(8px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.25);
  }
  .interlocuteur-card:hover {
    background: rgba(255,255,255,0.06);
    border-color: var(--or);
    transform: translateY(-4px);
    box-shadow: 0 16px 40px rgba(var(--or-rgb),0.12);
  }
  .interlocuteur-card::before {
    content: '';
    position: absolute; top: 0; left: 0;
    width: 0; height: 3px; background: var(--or);
    transition: width 0.5s ease;
    border-radius: 12px 12px 0 0;
  }
  .interlocuteur-card:hover::before { width: 100%; }

  .interlocuteur-dept {
    font-family: 'Jost', sans-serif; font-weight: 500;
    font-size: 0.65rem; letter-spacing: 0.3em; text-transform: uppercase;
    color: var(--or); display: inline-flex; align-items: center; gap: 8px; margin-bottom: 16px;
    background: rgba(var(--or-rgb), 0.1); padding: 4px 12px; border-radius: 20px;
  }
  .interlocuteur-name {
    font-family: 'Cormorant Garamond', serif; font-weight: 400;
    font-size: 1.8rem; color: #ffffff;
    line-height: 1.25; margin-bottom: 16px; letter-spacing: 0.02em;
  }
  .interlocuteur-name em { font-style: italic; color: var(--or-pale); }
  .interlocuteur-desc {
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.88rem; color: rgba(255,255,255,0.78);
    line-height: 1.85; letter-spacing: 0.02em; margin-bottom: 28px;
  }
  .interlocuteur-contacts { display: flex; flex-direction: column; gap: 12px; border-top: 1px solid rgba(var(--or-rgb), 0.15); padding-top: 20px; }
  .interlocuteur-contact-line {
    display: flex; align-items: center; gap: 14px;
    font-family: 'Jost', sans-serif; font-weight: 300;
    font-size: 0.85rem; color: rgba(255,255,255,0.85); letter-spacing: 0.03em;
  }
  .interlocuteur-contact-line a {
    color: var(--or-pale); text-decoration: none; transition: color 0.3s; font-weight: 400;
  }
  .interlocuteur-contact-line a:hover { color: var(--or); text-decoration: underline; }
  .interlocuteur-contact-icon {
    color: var(--or); font-size: 0.95rem; flex-shrink: 0; width: 22px; text-align: center;
  }

  /* ── Carte & Accès ── */
  .map-section { position: relative; }
  .map-header { padding: 80px 80px 48px; display: flex; align-items: flex-end; justify-content: space-between; background: #ffffff; }
  .map-heading { font-family: 'Cormorant Garamond', serif; font-weight: 400; font-size: clamp(2rem, 3.2vw, 2.8rem); color: var(--noir, #111111); line-height: 1.2; }
  .map-heading em { font-style: italic; color: var(--or-texte); }
  .map-address { font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.85rem; color: #4a5568; line-height: 1.8; letter-spacing: 0.02em; text-align: right; }
  .map-embed { width: 100%; height: 440px; display: block; border: none; filter: grayscale(20%) contrast(1.05); transition: filter 0.4s; }
  .map-embed:hover { filter: grayscale(0%) contrast(1); }
  .map-access-bar { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1px; background: rgba(var(--or-rgb),0.15); }
  .access-item { background: #ffffff; padding: 32px 24px; text-align: center; transition: all 0.3s; }
  .access-item:hover { background: #faf8f3; }
  .access-icon { font-size: 1.5rem; display: block; margin-bottom: 12px; color: var(--or); }
  .access-label { font-family: 'Jost', sans-serif; font-weight: 400; font-size: 0.65rem; letter-spacing: 0.25em; text-transform: uppercase; color: #718096; display: block; margin-bottom: 6px; }
  .access-value { font-family: 'Cormorant Garamond', serif; font-weight: 600; font-size: 1.15rem; color: var(--noir, #111111); letter-spacing: 0.02em; }

  /* ════════════════════════════════════════════
     NOUVELLE FAQ LUXE & HAUTE COUTURE (Refonte Totale)
  ════════════════════════════════════════════ */
  .faq-section {
    padding: 110px 80px;
    background: #faf8f3;
    position: relative;
    overflow: hidden;
  }
  .faq-section::before {
    content: 'FAQ';
    position: absolute; right: 20px; top: -30px;
    font-family: 'Cormorant Garamond', serif; font-size: 22vw;
    color: rgba(var(--or-rgb), 0.04); font-weight: 700;
    line-height: 1; pointer-events: none;
  }
  .faq-container { max-width: 1200px; margin: 0 auto; position: relative; z-index: 2; }
  
  .faq-header { text-align: center; margin-bottom: 50px; }
  .faq-header .section-label { margin-bottom: 12px; }
  .faq-heading {
    font-family: 'Cormorant Garamond', serif; font-weight: 300;
    font-size: clamp(2.4rem, 4vw, 3.5rem); color: var(--noir, #111111); line-height: 1.2;
    margin-bottom: 16px;
  }
  .faq-heading em { font-style: italic; color: var(--or-texte); }
  .faq-subheading {
    font-family: 'Jost', sans-serif; font-weight: 300; font-size: 0.95rem;
    color: #718096; max-width: 600px; margin: 0 auto; line-height: 1.7;
  }

  /* Filtres par Catégorie */
  .faq-filter-bar {
    display: flex; justify-content: center; align-items: center;
    gap: 10px; flex-wrap: wrap; margin-bottom: 40px;
  }
  .faq-filter-btn {
    background: #ffffff;
    border: 1px solid rgba(var(--or-rgb), 0.3);
    padding: 10px 20px;
    border-radius: 30px;
    font-family: 'Jost', sans-serif;
    font-size: 0.75rem;
    font-weight: 400;
    letter-spacing: 0.08em;
    color: #4a5568;
    cursor: pointer;
    transition: all 0.3s ease;
    display: inline-flex; align-items: center; gap: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
  }
  .faq-filter-btn i { color: var(--or); font-size: 0.85rem; }
  .faq-filter-btn:hover {
    border-color: var(--or);
    color: var(--noir, #111111);
    transform: translateY(-2px);
    box-shadow: 0 4px 14px rgba(var(--or-rgb), 0.15);
  }
  .faq-filter-btn.active {
    background: var(--noir, #111111);
    border-color: var(--noir, #111111);
    color: #ffffff;
    box-shadow: 0 6px 18px rgba(0,0,0,0.15);
  }
  .faq-filter-btn.active i { color: var(--or-pale); }

  /* Grille des Cartes Accordéon */
  .faq-grid {
    display: grid; grid-template-columns: 1fr 1fr; gap: 20px;
  }
  .faq-card {
    background: #ffffff;
    border: 1px solid rgba(var(--or-rgb), 0.18);
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 16px rgba(0,0,0,0.03);
  }
  .faq-card:hover {
    border-color: var(--or);
    box-shadow: 0 10px 30px rgba(var(--or-rgb), 0.12);
    transform: translateY(-2px);
  }
  .faq-card.open {
    border-color: var(--or);
    box-shadow: 0 12px 36px rgba(var(--or-rgb), 0.15);
    background: #ffffff;
  }

  .faq-trigger {
    padding: 24px 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    cursor: pointer;
    user-select: none;
    background: transparent;
    transition: background 0.3s ease;
  }
  .faq-card.open .faq-trigger {
    background: rgba(var(--or-rgb), 0.04);
    border-bottom: 1px solid rgba(var(--or-rgb), 0.12);
  }

  .faq-header-content { display: flex; flex-direction: column; gap: 6px; }
  .faq-badge-tag {
    font-family: 'Jost', sans-serif; font-size: 0.6rem; font-weight: 600;
    letter-spacing: 0.18em; text-transform: uppercase; color: var(--or-texte);
    display: inline-flex; align-items: center; gap: 6px;
  }
  .faq-q-title {
    font-family: 'Cormorant Garamond', serif;
    font-weight: 600;
    font-size: 1.25rem;
    color: var(--noir, #111111);
    letter-spacing: 0.01em;
    line-height: 1.35;
    margin: 0;
    transition: color 0.3s;
  }
  .faq-card:hover .faq-q-title, .faq-card.open .faq-q-title { color: var(--or-texte); }

  .faq-icon-bubble {
    width: 36px; height: 36px;
    background: rgba(var(--or-rgb), 0.1);
    border: 1px solid rgba(var(--or-rgb), 0.25);
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    color: var(--or-texte);
    font-size: 0.85rem;
    flex-shrink: 0;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }
  .faq-card.open .faq-icon-bubble {
    background: var(--or);
    color: var(--noir, #111111);
    transform: rotate(180deg);
  }

  .faq-body {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.45s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.3s ease;
    opacity: 0;
  }
  .faq-card.open .faq-body {
    max-height: 350px;
    opacity: 1;
  }
  .faq-body-inner {
    padding: 22px 28px 26px;
    font-family: 'Jost', sans-serif;
    font-weight: 300;
    font-size: 0.92rem;
    color: #4a5568;
    line-height: 1.85;
    letter-spacing: 0.015em;
  }
  .faq-body-highlight {
    margin-top: 14px;
    padding: 10px 16px;
    background: #faf8f3;
    border-left: 2px solid var(--or);
    border-radius: 4px;
    font-size: 0.85rem;
    color: #2d3748;
    font-weight: 400;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .faq-body-highlight i { color: var(--or); }

  /* ── Reveal ── */
  .reveal { opacity: 0; transform: translateY(36px); transition: opacity 0.8s ease, transform 0.8s ease; }
  .reveal.visible { opacity: 1; transform: translateY(0); }

  @media (max-width: 1100px) {
    .contact-main { grid-template-columns: 1fr; }
    .contact-info-wrap { padding: 60px 40px; }
    .contact-form-wrap { padding: 60px 40px; border-right: none; border-bottom: 1px solid rgba(var(--or-rgb),0.15); }
    .map-header { padding: 60px 40px 40px; }
    .faq-section { padding: 80px 40px; }
    .breadcrumb-bar { padding: 14px 40px; }
    .map-access-bar { grid-template-columns: 1fr 1fr; }
    .horaires-grid { grid-template-columns: 1fr; }
    .interlocuteurs-inner { padding: 0 40px; }
    .interlocuteurs-grid { grid-template-columns: 1fr; }
    .faq-grid { grid-template-columns: 1fr; }
  }
  @media (max-width: 767px) {
    .contact-hero { grid-template-columns: 1fr; }
    .contact-hero-img { min-height: 300px; }
    .contact-hero-text { padding: 60px 32px; }
    .contact-form-wrap, .contact-info-wrap { padding: 48px 24px; }
    .form-row { grid-template-columns: 1fr; }
    .map-header { padding: 48px 24px 32px; flex-direction: column; align-items: flex-start; gap: 20px; }
    .map-header .map-address { text-align: left; }
    .faq-section { padding: 60px 24px; }
    .faq-grid { grid-template-columns: 1fr; }
    .map-access-bar { grid-template-columns: 1fr 1fr; }
    .breadcrumb-bar { padding: 14px 24px; }
    .social-row { flex-wrap: wrap; }
    .interlocuteurs-inner { padding: 0 24px; }
    .interlocuteur-card { padding: 32px 24px; }
  }
  @media (max-width: 576px) {
    .map-access-bar { grid-template-columns: 1fr; }
    .contact-hero-text { padding: 48px 20px; }
    .contact-form-wrap, .contact-info-wrap { padding: 36px 18px; }
    .faq-filter-bar { justify-content: flex-start; overflow-x: auto; flex-wrap: nowrap; padding-bottom: 8px; }
    .faq-filter-btn { white-space: nowrap; }
  }
</style>

<!-- HERO SPLIT -->
<div class="contact-hero" style="margin-top:0;padding-top:0;">
  <div class="contact-hero-img">
    <div class="contact-hero-img-bg"></div>
    <div class="contact-hero-img-overlay"></div>
    <div class="hero-img-badge">
      <span class="hero-img-badge-title"><?= htmlspecialchars(hotel_name()) ?></span>
      <span class="hero-img-badge-sub"><?= htmlspecialchars(hotel_location()) ?> · <?= htmlspecialchars(hotel_country()) ?></span>
    </div>
  </div>
  <div class="contact-hero-text">
    <span class="contact-eyebrow">Contact &amp; Conciergerie Dédiée</span>
    <h1 class="contact-hero-title">
      Prêt à vivre<br>l'expérience <em><?= htmlspecialchars(hotel_short_name()) ?></em> ?
    </h1>
    <p class="contact-hero-desc">
      Toutes les informations pour nous rejoindre, réserver votre séjour
      ou échanger avec nos équipes de conciergerie et de direction.
    </p>
    <div class="contact-quick-info">
      <div class="quick-info-item">
        <div class="quick-info-icon"><i class="fas fa-phone-alt"></i></div>
        <span class="quick-info-text"><a href="tel:<?= htmlspecialchars(hotel_phone()) ?>"><?= htmlspecialchars(hotel_phone()) ?></a></span>
      </div>
      <div class="quick-info-item">
        <div class="quick-info-icon"><i class="fas fa-envelope"></i></div>
        <span class="quick-info-text"><a href="mailto:<?= htmlspecialchars(hotel_email()) ?>"><?= htmlspecialchars(hotel_email()) ?></a></span>
      </div>
      <div class="quick-info-item">
        <div class="quick-info-icon"><i class="fab fa-whatsapp"></i></div>
        <span class="quick-info-text"><a href="https://wa.me/<?= htmlspecialchars(hotel_whatsapp()) ?>" target="_blank">WhatsApp Direct</a></span>
      </div>
      <div class="quick-info-item">
        <div class="quick-info-icon"><i class="fas fa-map-marker-alt"></i></div>
        <span class="quick-info-text"><?= htmlspecialchars(hotel_location()) ?>, <?= htmlspecialchars(hotel_country()) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Fil d'Ariane -->
<div class="breadcrumb-bar">
  <a href="<?= $baseUrl ?>/index.php">Accueil</a>
  <span class="sep"><i class="fas fa-chevron-right" style="font-size:0.5rem;"></i></span>
  <span class="current">Contact &amp; Partenariats</span>
</div>

<!-- FORMULAIRE + INFOS -->
<div class="contact-main">

  <!-- Formulaire -->
  <div class="contact-form-wrap reveal">
    <span class="section-label">Écrivez-nous</span>
    <h2 class="form-title">Votre message,<br>notre <em>priorité</em></h2>
    <p class="form-subtitle">Réponse garantie sous 2 heures en journée ouvrée.</p>

    <form id="contactForm" onsubmit="handleSubmit(event)" novalidate>
      <div class="form-row">
        <div class="form-group">
          <label for="prenom">Prénom</label>
          <input type="text" id="prenom" name="prenom" placeholder="Jean" required>
        </div>
        <div class="form-group">
          <label for="nom">Nom</label>
          <input type="text" id="nom" name="nom" placeholder="Dupont" required>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="email">Adresse e-mail</label>
          <input type="email" id="email" name="email" placeholder="jean@exemple.com" required>
        </div>
        <div class="form-group">
          <label for="tel">Téléphone</label>
          <input type="tel" id="tel" name="tel" placeholder="<?= htmlspecialchars(hotel_phone()) ?>">
        </div>
      </div>
      <div class="form-group">
        <label for="sujet">Sujet de votre demande</label>
        <select id="sujet" name="sujet" required>
          <option value="" disabled selected>Sélectionnez un sujet…</option>
          <option value="reservation">Réservation de chambre ou suite</option>
          <option value="evenement">Séminaire / Conférence / Événement privé</option>
          <option value="skibar">Privatisation d'espaces (Skibar, Jardins)</option>
          <option value="nautique">Expériences Nautiques (Jet Ski, Yacht)</option>
          <option value="partenariat">Partenariat stratégique &amp; B2B</option>
          <option value="presse">Demande Presse &amp; Médias</option>
          <option value="autre">Autre demande conciergerie</option>
        </select>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="arrivee">Date d'arrivée souhaitée</label>
          <input type="date" id="arrivee" name="arrivee">
        </div>
        <div class="form-group">
          <label for="depart">Date de départ souhaitée</label>
          <input type="date" id="depart" name="depart">
        </div>
      </div>
      <div class="form-group">
        <label for="message">Votre message</label>
        <textarea id="message" name="message" placeholder="Décrivez votre demande, notre équipe est à votre entière écoute…" required></textarea>
      </div>
      <div class="form-consent">
        <input type="checkbox" id="consent" name="consent" required>
        <label for="consent">
          J'accepte que mes coordonnées soient utilisées pour traiter ma demande,
          conformément à la politique de confidentialité de <?= htmlspecialchars(hotel_name()) ?>.
        </label>
      </div>
      <button type="submit" class="btn-send">
        <span>Envoyer le message</span>
        <i class="fas fa-paper-plane"></i>
      </button>
      <div class="form-success" id="formSuccess">
        <p><i class="fas fa-check-circle" style="margin-right:8px;"></i> Merci pour votre message. Notre équipe de conciergerie vous répondra dans les plus brefs délais.</p>
      </div>
    </form>
  </div>

  <!-- Infos -->
  <div class="contact-info-wrap reveal">

    <!-- Coordonnées -->
    <div class="info-block">
      <h5>Coordonnées de l'Établissement</h5>
      <div class="info-item">
        <div class="info-item-icon"><i class="fas fa-map-marker-alt"></i></div>
        <div>
          <span class="info-item-label">Adresse</span>
          <span class="info-item-value"><?= htmlspecialchars(hotel_name()) ?><br><?= htmlspecialchars(hotel_location()) ?><br><?= htmlspecialchars(hotel_country()) ?></span>
        </div>
      </div>
      <div class="info-item">
        <div class="info-item-icon"><i class="fas fa-phone-alt"></i></div>
        <div>
          <span class="info-item-label">Téléphone Principal</span>
          <span class="info-item-value"><a href="tel:<?= htmlspecialchars(hotel_phone()) ?>"><?= htmlspecialchars(hotel_phone()) ?></a></span>
        </div>
      </div>
      <div class="info-item">
        <div class="info-item-icon"><i class="fas fa-calendar-check"></i></div>
        <div>
          <span class="info-item-label">Réservations</span>
          <span class="info-item-value"><a href="mailto:<?= htmlspecialchars(hotel_email()) ?>"><?= htmlspecialchars(hotel_email()) ?></a></span>
        </div>
      </div>
      <div class="info-item">
        <div class="info-item-icon"><i class="fas fa-envelope"></i></div>
        <div>
          <span class="info-item-label">Direction &amp; Administration</span>
          <span class="info-item-value"><a href="mailto:<?= htmlspecialchars(defined('HOTEL_CONTACT_EMAIL') ? HOTEL_CONTACT_EMAIL : hotel_email()) ?>"><?= htmlspecialchars(defined('HOTEL_CONTACT_EMAIL') ? HOTEL_CONTACT_EMAIL : hotel_email()) ?></a></span>
        </div>
      </div>
    </div>

    <!-- Horaires -->
    <div class="info-block">
      <h5>Horaires des Services</h5>
      <div class="horaires-grid">
        <div class="horaire-line">
          <span class="horaire-service">Réception</span>
          <span class="horaire-time horaire-24">24h/24</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Conciergerie</span>
          <span class="horaire-time horaire-24">24h/24</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Room Service</span>
          <span class="horaire-time horaire-24">24h/24</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Restaurant</span>
          <span class="horaire-time">07h – 22h</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Skibar</span>
          <span class="horaire-time">16h – 00h</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Piscine / Jacuzzi</span>
          <span class="horaire-time">07h – 21h</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Fitness</span>
          <span class="horaire-time horaire-24">24h/24</span>
        </div>
        <div class="horaire-line">
          <span class="horaire-service">Check-in</span>
          <span class="horaire-time">Dès 14h00</span>
        </div>
      </div>
    </div>

    <!-- Réseaux -->
    <div class="info-block">
      <h5>Nous Suivre</h5>
      <div class="social-row">
        <a href="#" class="social-btn"><i class="fab fa-instagram" style="color:var(--or);"></i> Instagram</a>
        <a href="#" class="social-btn"><i class="fab fa-facebook-f" style="color:var(--or);"></i> Facebook</a>
        <a href="#" class="social-btn"><i class="fab fa-linkedin-in" style="color:var(--or);"></i> LinkedIn</a>
      </div>
    </div>

  </div>
</div>

<!-- ══════════════════════════════════════════
     INTERLOCUTEURS PRIVILÉGIÉS (100% Contrasté & Lisible)
══════════════════════════════════════════ -->
<section class="interlocuteurs-section">
  <div class="interlocuteurs-inner">

    <div class="interlocuteurs-header reveal">
      <span class="section-label" style="color:var(--or);">Liaison Directe &amp; Accompagnement Dédié</span>
      <h2 class="interlocuteurs-heading">
        Nos Interlocuteurs<br><em>Privilégiés</em>
      </h2>
      <p class="interlocuteurs-intro">
        Notre équipe de direction et de conciergerie se tient à votre disposition pour toute demande spécifique.
        Que ce soit pour un événement corporate, un partenariat ou une demande sur-mesure, bénéficiez d'un contact direct.
      </p>
    </div>

    <div class="interlocuteurs-grid reveal">

      <!-- Département Commercial & Événementiel -->
      <div class="interlocuteur-card">
        <span class="interlocuteur-dept"><i class="fas fa-briefcase"></i> Département Commercial &amp; Événementiel</span>
        <h3 class="interlocuteur-name">
          Séminaires, Conférences<br>&amp; <em>Événements Privés</em>
        </h3>
        <p class="interlocuteur-desc">
          Pour l'organisation de vos séminaires d'affaires, réunions de direction et réceptions privées.
          Nos espaces modulables accueillent de 10 à 180 personnes avec privatisation des salons panoramiques sur demande.
        </p>
        <div class="interlocuteur-contacts">
          <div class="interlocuteur-contact-line">
            <span class="interlocuteur-contact-icon"><i class="fas fa-user-tie"></i></span>
            Responsable Commercial &amp; Événements
          </div>
          <div class="interlocuteur-contact-line">
            <span class="interlocuteur-contact-icon"><i class="fas fa-envelope"></i></span>
            <a href="mailto:<?= htmlspecialchars(hotel_email()) ?>"><?= htmlspecialchars(hotel_email()) ?></a>
          </div>
          <div class="interlocuteur-contact-line">
            <span class="interlocuteur-contact-icon"><i class="fas fa-phone-alt"></i></span>
            <a href="tel:<?= htmlspecialchars(hotel_phone()) ?>"><?= htmlspecialchars(hotel_phone()) ?></a>
          </div>
        </div>
      </div>

      <!-- Direction Générale -->
      <div class="interlocuteur-card">
        <span class="interlocuteur-dept"><i class="fas fa-gem"></i> Direction Générale</span>
        <h3 class="interlocuteur-name">
          Partenariats<br><em>Stratégiques</em>
        </h3>
        <p class="interlocuteur-desc">
          Pour les demandes de partenariats institutionnels, collaborations de marque,
          demandes presse et médias, ou toute proposition de coopération avec <?= htmlspecialchars(hotel_name()) ?>.
        </p>
        <div class="interlocuteur-contacts">
          <div class="interlocuteur-contact-line">
            <span class="interlocuteur-contact-icon"><i class="fas fa-user-shield"></i></span>
            Direction Générale
          </div>
          <div class="interlocuteur-contact-line">
            <span class="interlocuteur-contact-icon"><i class="fas fa-envelope"></i></span>
            <a href="mailto:<?= htmlspecialchars(hotel_email()) ?>"><?= htmlspecialchars(hotel_email()) ?></a>
          </div>
          <div class="interlocuteur-contact-line">
            <span class="interlocuteur-contact-icon"><i class="fas fa-phone-alt"></i></span>
            <a href="tel:<?= htmlspecialchars(hotel_phone()) ?>"><?= htmlspecialchars(hotel_phone()) ?></a>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- CARTE & ACCÈS -->
<section class="map-section">
  <div class="map-header reveal">
    <div>
      <span class="section-label">Comment nous trouver</span>
      <h2 class="map-heading">Accès &amp; <em>Localisation</em></h2>
    </div>
    <div class="map-address">
      <?= htmlspecialchars(hotel_name()) ?><br>
      <?= htmlspecialchars(hotel_location()) ?><br>
      <?= htmlspecialchars(hotel_country()) ?><br>
      <a href="https://maps.google.com/?q=<?= urlencode(hotel_name() . ' ' . hotel_location()) ?>" target="_blank"
         style="color:var(--or-texte);text-decoration:none;font-size:0.75rem;letter-spacing:0.2em;text-transform:uppercase;font-weight:600;display:inline-block;margin-top:6px;">
        Ouvrir dans Google Maps <i class="fas fa-external-link-alt" style="font-size:0.7rem;"></i>
      </a>
    </div>
  </div>

  <iframe
    class="map-embed"
    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d126916.28!2d1.5!3d6.2!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x1023f00a0a0a0a0a%3A0x0!2sAgbodrafo%2C+Togo!5e0!3m2!1sfr!2sfr!4v1"
    allowfullscreen="" loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
    title="<?= htmlspecialchars(hotel_name()) ?> — <?= htmlspecialchars(hotel_city()) ?>">
  </iframe>

  <div class="map-access-bar reveal">
    <div class="access-item">
      <span class="access-icon"><i class="fas fa-plane-arrival"></i></span>
      <span class="access-label">Aéroport de Lomé</span>
      <span class="access-value">45 min en voiture</span>
    </div>
    <div class="access-item">
      <span class="access-icon"><i class="fas fa-car-side"></i></span>
      <span class="access-label">Transfert Privé VIP</span>
      <span class="access-value">Sur simple réservation</span>
    </div>
    <div class="access-item">
      <span class="access-icon"><i class="fas fa-city"></i></span>
      <span class="access-label">Centre de Lomé</span>
      <span class="access-value">55 km · 45 min</span>
    </div>
    <div class="access-item">
      <span class="access-icon"><i class="fas fa-parking"></i></span>
      <span class="access-label">Parking Privé</span>
      <span class="access-value">Gratuit &amp; Sécurisé 24h/24</span>
    </div>
  </div>
</section>

<!-- ══════════════════════════════════════════
     NOUVELLE FAQ LUXE & HAUTE COUTURE (Refonte Totale)
══════════════════════════════════════════ -->
<section class="faq-section">
  <div class="faq-container">
    
    <div class="faq-header reveal">
      <span class="section-label">✦ Foire Aux Questions</span>
      <h2 class="faq-heading">Tout ce que vous devez <em>savoir</em></h2>
      <p class="faq-subheading">
        Retrouvez les réponses à toutes vos questions pour préparer sereinement votre séjour d'exception parmi nous.
      </p>
    </div>

    <!-- Filtres Interactifs de Catégorie -->
    <div class="faq-filter-bar reveal">
      <button type="button" class="faq-filter-btn active" onclick="filterFaq('all', this)">
        <i class="fas fa-th-large"></i> Toutes les questions
      </button>
      <button type="button" class="faq-filter-btn" onclick="filterFaq('sejour', this)">
        <i class="fas fa-concierge-bell"></i> Séjour &amp; Check-in
      </button>
      <button type="button" class="faq-filter-btn" onclick="filterFaq('nautique', this)">
        <i class="fas fa-ship"></i> Expériences &amp; Loisirs
      </button>
      <button type="button" class="faq-filter-btn" onclick="filterFaq('b2b', this)">
        <i class="fas fa-briefcase"></i> Événements &amp; Affaires
      </button>
      <button type="button" class="faq-filter-btn" onclick="filterFaq('conditions', this)">
        <i class="fas fa-shield-alt"></i> Réservations &amp; Annulation
      </button>
    </div>

    <!-- Grille de Questions / Réponses -->
    <div class="faq-grid reveal">

      <!-- FAQ 1 : Horaires -->
      <div class="faq-card open" data-category="sejour">
        <div class="faq-trigger" onclick="toggleFaqCard(this)">
          <div class="faq-header-content">
            <span class="faq-badge-tag"><i class="fas fa-clock"></i> Séjour &amp; Réception</span>
            <h3 class="faq-q-title">Quels sont les horaires de check-in et check-out ?</h3>
          </div>
          <div class="faq-icon-bubble"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="faq-body">
          <div class="faq-body-inner">
            L'arrivée est possible à partir de <strong>14h00</strong> et la libération de la chambre est demandée avant <strong>12h00</strong>.
            Des facilités d'early check-in ou de late check-out sont envisageables selon les disponibilités de l'établissement.
            <div class="faq-body-highlight">
              <i class="fas fa-info-circle"></i> Contactez notre conciergerie 24h avant votre arrivée pour toute demande spécifique.
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ 2 : Transfert Aéroport -->
      <div class="faq-card" data-category="sejour">
        <div class="faq-trigger" onclick="toggleFaqCard(this)">
          <div class="faq-header-content">
            <span class="faq-badge-tag"><i class="fas fa-car"></i> Transport VIP</span>
            <h3 class="faq-q-title">Proposez-vous un transfert depuis l'aéroport de Lomé ?</h3>
          </div>
          <div class="faq-icon-bubble"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="faq-body">
          <div class="faq-body-inner">
            Absolument. Nous mettons à votre disposition un service de navette privée en véhicule climatisé avec chauffeur bilingue depuis l'Aéroport International Gnassingbé Eyadéma (LFW).
            <div class="faq-body-highlight">
              <i class="fas fa-shield-alt"></i> Accueil personnalisé avec pancarte nominative à la sortie du terminal.
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ 3 : Jet Ski & Yacht -->
      <div class="faq-card" data-category="nautique">
        <div class="faq-trigger" onclick="toggleFaqCard(this)">
          <div class="faq-header-content">
            <span class="faq-badge-tag"><i class="fas fa-water"></i> Loisirs &amp; Aventure</span>
            <h3 class="faq-q-title">Comment réserver une session Jet Ski ou une croisière Yacht ?</h3>
          </div>
          <div class="faq-icon-bubble"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="faq-body">
          <div class="faq-body-inner">
            Nos expériences nautiques signatures — jet ski dernière génération et croisières privatisées en yacht sur le lac Togo — sont ouvertes à nos résidents et visiteurs sur réservation.
            <div class="faq-body-highlight">
              <i class="fas fa-calendar-check"></i> Réservation directe via le formulaire ci-dessus, auprès de la conciergerie ou via WhatsApp.
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ 4 : Privatisation & Événements -->
      <div class="faq-card" data-category="b2b">
        <div class="faq-trigger" onclick="toggleFaqCard(this)">
          <div class="faq-header-content">
            <span class="faq-badge-tag"><i class="fas fa-microphone-alt"></i> B2B &amp; Réceptions</span>
            <h3 class="faq-q-title">Peut-on privatiser des espaces pour un séminaire ou un mariage ?</h3>
          </div>
          <div class="faq-icon-bubble"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="faq-body">
          <div class="faq-body-inner">
            Oui, nos salons panoramiques, jardins tropicaux et terrasses avec vue sur le lac sont entièrement privatisables pour vos lancements de produits, séminaires ou cocktails de mariage.
            <div class="faq-body-highlight">
              <i class="fas fa-file-invoice-dollar"></i> Un devis technique et tarifaire sur-mesure vous est transmis sous 24h ouvrées.
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ 5 : Politique d'Annulation -->
      <div class="faq-card" data-category="conditions">
        <div class="faq-trigger" onclick="toggleFaqCard(this)">
          <div class="faq-header-content">
            <span class="faq-badge-tag"><i class="fas fa-undo"></i> Conditions</span>
            <h3 class="faq-q-title">Quelle est votre politique de flexibilité et d'annulation ?</h3>
          </div>
          <div class="faq-icon-bubble"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="faq-body">
          <div class="faq-body-inner">
            Les annulations ou modifications de séjour effectuées plus de <strong>48 heures</strong> avant la date d'arrivée sont 100% gratuites et intégralement remboursées.
            <div class="faq-body-highlight">
              <i class="fas fa-check-circle"></i> Gestion et annulation autonomes directement depuis votre Espace Membre.
            </div>
          </div>
        </div>
      </div>

      <!-- FAQ 6 : Partenariats & Presse -->
      <div class="faq-card" data-category="b2b">
        <div class="faq-trigger" onclick="toggleFaqCard(this)">
          <div class="faq-header-content">
            <span class="faq-badge-tag"><i class="fas fa-handshake"></i> Partenariats</span>
            <h3 class="faq-q-title">Comment proposer une collaboration avec <?= htmlspecialchars(hotel_name()) ?> ?</h3>
          </div>
          <div class="faq-icon-bubble"><i class="fas fa-chevron-down"></i></div>
        </div>
        <div class="faq-body">
          <div class="faq-body-inner">
            Pour les propositions de sponsoring, tournages, partenariats médias et collaborations institutionnelles, veuillez adresser votre dossier à notre Direction Générale via notre adresse officielle.
            <div class="faq-body-highlight">
              <i class="fas fa-envelope"></i> Email direct : <?= htmlspecialchars(hotel_email()) ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<script>
  // Animation au scroll
  const reveals = document.querySelectorAll('.reveal');
  const obs = new IntersectionObserver((entries) => {
    entries.forEach((e, i) => {
      if (e.isIntersecting) {
        setTimeout(() => e.target.classList.add('visible'), i * 60);
        obs.unobserve(e.target);
      }
    });
  }, { threshold: 0.08 });
  reveals.forEach(el => obs.observe(el));

  // Accordéon FAQ Moderne
  function toggleFaqCard(triggerEl) {
    const card = triggerEl.closest('.faq-card');
    const wasOpen = card.classList.contains('open');
    
    // Fermer les autres cartes
    document.querySelectorAll('.faq-card.open').forEach(c => {
      if (c !== card) c.classList.remove('open');
    });

    if (!wasOpen) {
      card.classList.add('open');
    } else {
      card.classList.remove('open');
    }
  }

  // Filtrage des FAQ par catégorie
  function filterFaq(category, btn) {
    document.querySelectorAll('.faq-filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const cards = document.querySelectorAll('.faq-card');
    cards.forEach(card => {
      const cardCat = card.getAttribute('data-category');
      if (category === 'all' || cardCat === category) {
        card.style.display = 'block';
        setTimeout(() => { card.style.opacity = '1'; }, 10);
      } else {
        card.style.display = 'none';
        card.classList.remove('open');
      }
    });
  }

  // Soumission Formulaire
  function handleSubmit(e) {
    e.preventDefault();
    const form = document.getElementById('contactForm');
    const success = document.getElementById('formSuccess');
    const btn = form.querySelector('.btn-send');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement en cours…';
    btn.disabled = true;
    setTimeout(() => {
      form.style.opacity = '0.5';
      form.style.pointerEvents = 'none';
      success.style.display = 'block';
      btn.innerHTML = '<span>Message envoyé</span> <i class="fas fa-check"></i>';
    }, 1000);
  }

  // Calendrier date départ après date arrivée
  const arrivee = document.getElementById('arrivee');
  const depart  = document.getElementById('depart');
  if (arrivee && depart) {
    const today = new Date().toISOString().split('T')[0];
    arrivee.min = today;
    arrivee.addEventListener('change', () => {
      depart.min = arrivee.value;
      if (depart.value && depart.value <= arrivee.value) depart.value = '';
    });
  }
</script>

<?php include(__DIR__ . '/../layouts/footer.php'); ?>