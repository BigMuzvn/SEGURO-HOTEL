<?php include("./layouts/header.php") ?>

  <!-- ══════════════════════════════════════════
       HERO — Vidéo plage, style Singita
  ══════════════════════════════════════════ -->
  <section id="hero">
    <video class="hero-video" autoplay muted loop playsinline poster="">
      <source src="./assets/video/video.mp4" type="video/mp4">
      <div class="hero-video-fallback"></div>
    </video>

    <div class="hero-overlay"></div>

    <!-- Coins décoratifs dynamiques selon couleur d'accent -->
    <div class="hero-corner tl">
      <svg width="50" height="50" viewBox="0 0 50 50" fill="none">
        <path d="M0 50 L0 0 L50 0" stroke="var(--color-accent)" stroke-width="1" fill="none"/>
        <rect x="3" y="3" width="10" height="10" stroke="var(--color-accent)" stroke-width="0.5" fill="none"/>
      </svg>
    </div>
    <div class="hero-corner tr">
      <svg width="50" height="50" viewBox="0 0 50 50" fill="none">
        <path d="M0 50 L0 0 L50 0" stroke="var(--color-accent)" stroke-width="1" fill="none"/>
        <rect x="3" y="3" width="10" height="10" stroke="var(--color-accent)" stroke-width="0.5" fill="none"/>
      </svg>
    </div>
    <div class="hero-corner bl">
      <svg width="50" height="50" viewBox="0 0 50 50" fill="none">
        <path d="M0 50 L0 0 L50 0" stroke="var(--color-accent)" stroke-width="1" fill="none"/>
        <rect x="3" y="3" width="10" height="10" stroke="var(--color-accent)" stroke-width="0.5" fill="none"/>
      </svg>
    </div>
    <div class="hero-corner br">
      <svg width="50" height="50" viewBox="0 0 50 50" fill="none">
        <path d="M0 50 L0 0 L50 0" stroke="var(--color-accent)" stroke-width="1" fill="none"/>
        <rect x="3" y="3" width="10" height="10" stroke="var(--color-accent)" stroke-width="0.5" fill="none"/>
      </svg>
    </div>

    <div class="hero-content">
      <div class="hero-ornament">
        <span class="hero-orn-line"></span>
        <span class="hero-orn-diamond"></span>
        <span class="hero-orn-line right"></span>
      </div>

      <h1 class="hero-title"><?= htmlspecialchars(hotel_short_name()) ?></h1>

      <p class="hero-subtitle">
        <?= htmlspecialchars(hotel_tagline()) ?><br>Rien n'a été sacrifié sur l'autel du confort et de l'excellence.
      </p>

      <div class="hero-cta-wrap">
        <a href="pages/reservation-system.php" class="cta-primary">Réserver un séjour</a>
        <a href="pages/chambres.php" class="cta-secondary">Découvrir les chambres</a>
      </div>
    </div>

  </section>

  <!-- ══════════════════════════════════════════
       PRÉSENTATION — L'expérience
  ══════════════════════════════════════════ -->
  <section style="padding: 120px 60px; background: var(--blanc);">
    <div style="max-width: 1200px; margin: 0 auto; text-align: center;">
      <div style="margin-bottom: 32px;">
        <span style="display: inline-block; width: 60px; height: 1px; background: var(--or); vertical-align: middle;"></span>
        <span style="display: inline-block; width: 8px; height: 8px; background: var(--or); transform: rotate(45deg); margin: 0 16px; vertical-align: middle;"></span>
        <span style="display: inline-block; width: 60px; height: 1px; background: var(--or); vertical-align: middle;"></span>
      </div>
      
      <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2.8rem; color: var(--vert); margin-bottom: 24px; font-weight: 300;">
        Une parenthèse de sérénité
      </h2>
      
      <p style="font-size: 1.1rem; color: #555; line-height: 1.8; max-width: 700px; margin: 0 auto 48px;">
        Niché au cœur d'un environnement privilégié à <?= htmlspecialchars(hotel_location()) ?>, <?= htmlspecialchars(hotel_name()) ?> vous invite à découvrir 
        l'harmonie parfaite entre nature préservée et confort raffiné. Notre établissement 
        d'exception vous offre un refuge hors du temps où chaque détail a été pensé pour 
        sublimer votre séjour.
      </p>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 48px; margin-top: 64px;">
        <div style="text-align: center;">
          <div style="width: 90px; height: 90px; margin: 0 auto 20px; border: 1px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;"> <img src="./assets/images/leaf.png" alt="Nature Préservée" srcset=""></div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--vert); margin-bottom: 12px;">Nature Préservée</h3>
          <p style="font-size: 0.95rem; color: #666; line-height: 1.6;">Un cadre verdoyant à quelques pas de l'océan Atlantique</p>
        </div>
        <div style="text-align: center;">
          <div style="width: 90px; height: 90px; margin: 0 auto 20px; border: 1px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;"> <img src="./assets/images/diamonds.png" alt="Luxueux & Authentique" srcset=""></div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--vert); margin-bottom: 12px;">Luxueux & Authentique</h3>
          <p style="font-size: 0.95rem; color: #666; line-height: 1.6;">Des espaces raffinés célébrant l'art de vivre togolais</p>
        </div>
        <div style="text-align: center;">
          <div style="width: 90px; height: 90px; margin: 0 auto 20px; border: 1px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;"> <img src="./assets/images/restaurants.png" alt="Gastronomie Locale" srcset=""></div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--vert); margin-bottom: 12px;">Gastronomie Locale</h3>
          <p style="font-size: 0.95rem; color: #666; line-height: 1.6;">Une cuisine fusion entre saveurs africaines et techniques modernes</p>
        </div>
        <div style="text-align: center;">
          <div style="width: 90px; height: 90px; margin: 0 auto 20px; border: 1px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;"> <img src="./assets/images/sun.png" alt="Couchers de Soleil" srcset=""></div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--vert); margin-bottom: 12px;">Couchers de Soleil</h3>
          <p style="font-size: 0.95rem; color: #666; line-height: 1.6;">Des vues imprenables sur l'océan depuis votre terrasse privée</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       CHAMBRES EN VEDETTE
  ══════════════════════════════════════════ -->
  <section style="padding: 120px 60px; background: linear-gradient(to bottom, #f8f6f1, var(--blanc));">
    <div style="max-width: 1200px; margin: 0 auto;">
      <div style="text-align: center; margin-bottom: 64px;">
        <span style="font-family: 'Jost', sans-serif; font-size: 0.75rem; letter-spacing: 0.3em; text-transform: uppercase; color: var(--or);">Nos Hébergements</span>
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2.4rem; color: var(--vert); margin-top: 16px; font-weight: 300;">
          Des espaces pensés pour votre confort
        </h2>
      </div>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 32px;">
        <!-- Suite Royale -->
        <div style="background: white; border-radius: 4px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
          <div style="height: 280px; overflow: hidden;">
            <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?w=600&h=400&fit=crop" 
                 alt="Suite Royale - Vue océan" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
          </div>
          <div style="padding: 32px;">
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--vert); margin-bottom: 8px;">Suite Royale</h3>
            <p style="font-size: 0.9rem; color: #888; margin-bottom: 16px;">80 m² • Vue océan • Jacuzzi privé</p>
            <p style="font-size: 0.95rem; color: #555; line-height: 1.6; margin-bottom: 24px;">Notre suite d'exception avec terrasse panoramique, jacuzzi extérieur et service de majordome.</p>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--or); font-weight: 600;">185 000 FCFA<span style="font-size: 0.8rem; color: #888; font-weight: 300;">/nuit</span></span>
              <a href="pages/chambres.php" style="font-size: 0.8rem; color: var(--vert); text-decoration: none; border-bottom: 1px solid var(--or); padding-bottom: 2px;">Découvrir →</a>
            </div>
          </div>
        </div>
        
        <!-- Villa Privée -->
        <div style="background: white; border-radius: 4px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
          <div style="height: 280px; overflow: hidden;">
            <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=600&h=400&fit=crop" 
                 alt="Villa Privée - Piscine privée" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
          </div>
          <div style="padding: 32px;">
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--vert); margin-bottom: 8px;">Villa Privée</h3>
            <p style="font-size: 0.9rem; color: #888; margin-bottom: 16px;">120 m² • Jardin privatif • Piscine</p>
            <p style="font-size: 0.95rem; color: #555; line-height: 1.6; margin-bottom: 24px;">Une villa indépendante avec piscine privée, jardin tropical et espace de réception pour 4 personnes.</p>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--or); font-weight: 600;">320 000 FCFA<span style="font-size: 0.8rem; color: #888; font-weight: 300;">/nuit</span></span>
              <a href="pages/chambres.php" style="font-size: 0.8rem; color: var(--vert); text-decoration: none; border-bottom: 1px solid var(--or); padding-bottom: 2px;">Découvrir →</a>
            </div>
          </div>
        </div>
        
        <!-- Chambre Supérieure -->
        <div style="background: white; border-radius: 4px; overflow: hidden; box-shadow: 0 4px 24px rgba(0,0,0,0.06);">
          <div style="height: 280px; overflow: hidden;">
            <img src="https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=600&h=400&fit=crop" 
                 alt="Chambre Supérieure - Vue jardin" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
          </div>
          <div style="padding: 32px;">
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--vert); margin-bottom: 8px;">Chambre Supérieure</h3>
            <p style="font-size: 0.9rem; color: #888; margin-bottom: 16px;">40 m² • Vue jardin • Terrasse</p>
            <p style="font-size: 0.95rem; color: #555; line-height: 1.6; margin-bottom: 24px;">Confort élégant avec terrasse privée donnant sur notre jardin tropical et accès direct à la piscine.</p>
            <div style="display: flex; justify-content: space-between; align-items: center;">
              <span style="font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; color: var(--or); font-weight: 600;">85 000 FCFA<span style="font-size: 0.8rem; color: #888; font-weight: 300;">/nuit</span></span>
              <a href="pages/chambres.php" style="font-size: 0.8rem; color: var(--vert); text-decoration: none; border-bottom: 1px solid var(--or); padding-bottom: 2px;">Découvrir →</a>
            </div>
          </div>
        </div>
      </div>
      
      <div style="text-align: center; margin-top: 48px;">
        <a href="pages/chambres.php" class="cta-primary" style="display: inline-block; background: var(--vert); color: var(--or-pale); padding: 16px 40px; text-decoration: none; font-size: 0.75rem; letter-spacing: 0.2em; text-transform: uppercase; border-radius: 2px;">Voir toutes les chambres</a>
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       EXPÉRIENCES & SENSORIALITÉ
  ══════════════════════════════════════════ -->
  <style>
    .experience-card-item {
      background: rgba(255, 255, 255, 0.04);
      border: 1px solid rgba(var(--or-rgb), 0.22);
      border-radius: 12px;
      padding: 38px 32px;
      transition: all 0.35s cubic-bezier(0.16, 1, 0.3, 1);
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: space-between;
    }
    .experience-card-item::before {
      content: '';
      position: absolute;
      top: 0; left: 0; right: 0;
      height: 2px;
      background: linear-gradient(90deg, transparent, var(--or), transparent);
      opacity: 0;
      transition: opacity 0.35s ease;
    }
    .experience-card-item:hover {
      transform: translateY(-6px);
      background: rgba(255, 255, 255, 0.08);
      border-color: var(--or);
      box-shadow: 0 18px 40px rgba(0, 0, 0, 0.22);
    }
    .experience-card-item:hover::before {
      opacity: 1;
    }
    .experience-card-item:hover .exp-icon-badge {
      background: var(--or);
      border-color: var(--or);
      transform: scale(1.06);
    }
    .exp-icon-badge {
      width: 66px;
      height: 66px;
      border-radius: 50%;
      background: rgba(var(--or-rgb), 0.12);
      border: 1.5px solid rgba(var(--or-rgb), 0.35);
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 24px;
      transition: all 0.35s ease;
      flex-shrink: 0;
    }
    .exp-icon-badge img {
      width: 36px;
      height: 36px;
      object-fit: contain;
      transition: transform 0.3s ease;
    }
  </style>

  <section style="padding: 120px 40px; background: linear-gradient(140deg, var(--vert) 0%, var(--vert-sombre, var(--color-dark, #0d1e16)) 100%); color: #ffffff; position: relative;">
    <div style="max-width: 1240px; margin: 0 auto;">
      
      <div style="text-align: center; max-width: 760px; margin: 0 auto 64px;">
        <div style="display: inline-flex; align-items: center; gap: 12px; margin-bottom: 16px;">
          <span style="width: 35px; height: 1px; background: linear-gradient(to right, transparent, var(--or));"></span>
          <span style="font-family: 'Jost', sans-serif; font-size: 0.72rem; letter-spacing: 0.32em; text-transform: uppercase; color: var(--or); font-weight: 500;">Moments d'Exception</span>
          <span style="width: 35px; height: 1px; background: linear-gradient(to left, transparent, var(--or));"></span>
        </div>
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: clamp(2.2rem, 4vw, 3.2rem); color: #ffffff; font-weight: 300; line-height: 1.2; margin: 0;">
          S'évader, se ressourcer, <em>se réinventer</em>
        </h2>
        <p style="font-size: 0.95rem; color: rgba(255,255,255,0.7); margin-top: 14px; font-weight: 300; line-height: 1.8;">
          Une sélection d'expériences exclusives sur-mesure pour sublimer chaque instant de votre séjour.
        </p>
      </div>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(270px, 1fr)); gap: 28px;">
        
        <!-- 1. Détente & Bien-être -->
        <div class="experience-card-item">
          <div>
            <div class="exp-icon-badge">
              <img src="./assets/images/mastery.png" alt="Détente &amp; Bien-être">
            </div>
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px; font-weight: 600; letter-spacing: 0.02em;">
              Détente &amp; Bien-être
            </h3>
            <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.78); font-weight: 300; margin: 0;">
              Spa ouvert sur la nature, massages traditionnels aux huiles rares locales et séances de yoga au lever du soleil sur la plage.
            </p>
          </div>
        </div>

        <!-- 2. Gastronomie & Mixologie -->
        <div class="experience-card-item">
          <div>
            <div class="exp-icon-badge">
              <img src="./assets/images/food.png" alt="Gastronomie &amp; Mixologie">
            </div>
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px; font-weight: 600; letter-spacing: 0.02em;">
              Gastronomie &amp; Mixologie
            </h3>
            <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.78); font-weight: 300; margin: 0;">
              Restaurant gastronomique, bar à cocktails avec vue océan, dégustations privées et accords mets-vins prestigieux.
            </p>
          </div>
        </div>

        <!-- 3. Activités Nautiques -->
        <div class="experience-card-item">
          <div>
            <div class="exp-icon-badge">
              <img src="./assets/images/sport.png" alt="Activités Nautiques">
            </div>
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px; font-weight: 600; letter-spacing: 0.02em;">
              Activités Nautiques
            </h3>
            <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.78); font-weight: 300; margin: 0;">
              Plongée sous-marine, pêche sportive, excursions en bateau traditionnel, paddle et escapades en kayak dans la mangrove.
            </p>
          </div>
        </div>

        <!-- 4. Culture & Découverte -->
        <div class="experience-card-item">
          <div>
            <div class="exp-icon-badge">
              <img src="./assets/images/theater.png" alt="Culture &amp; Découverte">
            </div>
            <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px; font-weight: 600; letter-spacing: 0.02em;">
              Culture &amp; Découverte
            </h3>
            <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.78); font-weight: 300; margin: 0;">
              Visites des villages voisins, ateliers de tissage traditionnel, marchés locaux et rencontres exclusives avec les maîtres artisans.
            </p>
          </div>
        </div>
        
      </div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       TÉMOIGNAGES
  ══════════════════════════════════════════ -->
  <section style="padding: 120px 60px; background: var(--blanc);">
    <div style="max-width: 900px; margin: 0 auto; text-align: center;">
      <div style="margin-bottom: 32px;">
        <span style="display: inline-block; width: 40px; height: 1px; background: var(--or); vertical-align: middle;"></span>
        <span style="display: inline-block; font-size: 1.5rem; margin: 0 16px; vertical-align: middle;">❝</span>
        <span style="display: inline-block; width: 40px; height: 1px; background: var(--or); vertical-align: middle;"></span>
      </div>
      
      <blockquote style="font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: var(--vert); font-style: italic; line-height: 1.6; margin-bottom: 32px;">
        "Un havre de paix où le temps semble s'arrêter. L'accueil chaleureux, les paysages à couper le souffle et le raffinement des lieux ont fait de notre séjour une expérience inoubliable."
      </blockquote>
      
      <div style="font-size: 0.9rem; color: #666;">
        <strong style="color: var(--vert); font-weight: 400;">Marie & Jean-Pierre L.</strong> — Paris, France
      </div>
      <div style="margin-top: 8px; color: var(--or); font-size: 1.2rem;">★★★★★</div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       CALL TO ACTION
  ══════════════════════════════════════════ -->
  <section style="padding: 100px 60px; background: linear-gradient(135deg, var(--vert-clair) 0%, var(--vert) 100%); text-align: center;">
    <div style="max-width: 600px; margin: 0 auto;">
      <h2 style="font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; color: var(--or-pale); margin-bottom: 24px; font-weight: 300;">Réservez votre évasion</h2>
      <p style="font-size: 1rem; color: rgba(250,248,243,0.8); margin-bottom: 40px; line-height: 1.7;">Profitez de nos offres spéciales pour les séjours de 3 nuits ou plus. Petit-déjeuner gastronomique inclus.</p>
      <a href="pages/reservation-system.php" style="display: inline-block; background: var(--or); color: #111; padding: 18px 48px; text-decoration: none; font-size: 0.75rem; letter-spacing: 0.2em; text-transform: uppercase; border-radius: 2px; font-weight: 600;">Réserver maintenant</a>
    </div>
  </section>

<?php include("./layouts/footer.php") ?>