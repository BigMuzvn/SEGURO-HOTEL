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
       PRÉSENTATION — L'Art de Vivre & Sérénité
  ══════════════════════════════════════════ -->
  <section class="section-experience" style="padding: 120px 40px; background: var(--blanc); position: relative; overflow: hidden;">
    
    <div style="max-width: 1240px; margin: 0 auto; position: relative; z-index: 2;">
      
      <div style="text-align: center; max-width: 780px; margin: 0 auto 70px;">
        <div style="display: inline-flex; align-items: center; gap: 14px; margin-bottom: 20px;">
          <span style="display: block; width: 40px; height: 1px; background: linear-gradient(to right, transparent, var(--or));"></span>
          <span style="font-family: 'Jost', sans-serif; font-size: 0.68rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--or); font-weight: 500;">Sanctuaire d'Exception</span>
          <span style="display: block; width: 40px; height: 1px; background: linear-gradient(to left, transparent, var(--or));"></span>
        </div>
        
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: clamp(2.4rem, 4.5vw, 3.6rem); color: var(--vert); margin-bottom: 24px; font-weight: 300; line-height: 1.2;">
          Une parenthèse de <em>sérénité absolue</em>
        </h2>
        
        <p style="font-size: 1.05rem; color: #555; line-height: 1.9; font-weight: 300;">
          Niché au cœur d'un environnement privilégié à <?= htmlspecialchars(hotel_location()) ?>, <strong><?= htmlspecialchars(hotel_name()) ?></strong> vous invite à découvrir 
          l'harmonie parfaite entre nature préservée et confort raffiné. Un refuge hors du temps où chaque instant a été pensé pour sublimer votre séjour.
        </p>
      </div>
      
      <!-- Grille des 4 Piliers d'Excellence -->
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 28px;">
        
        <div class="pillar-card" style="background: var(--blanc-surface, #ffffff); border: 1px solid rgba(var(--or-rgb), 0.22); border-radius: 12px; padding: 40px 30px; text-align: center; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 8px 30px rgba(0,0,0,0.03); position: relative;">
          <div style="width: 70px; height: 70px; margin: 0 auto 24px; border: 1.5px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(var(--or-rgb), 0.08); color: var(--or); font-size: 1.6rem;">
            <i class="fas fa-leaf"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); margin-bottom: 12px; font-weight: 600;">Nature Préservée</h3>
          <p style="font-size: 0.92rem; color: #666; line-height: 1.7; font-weight: 300; margin: 0;">Un cadre tropical luxuriant à quelques pas des vagues de l'océan Atlantique.</p>
        </div>

        <div class="pillar-card" style="background: var(--blanc-surface, #ffffff); border: 1px solid rgba(var(--or-rgb), 0.22); border-radius: 12px; padding: 40px 30px; text-align: center; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 8px 30px rgba(0,0,0,0.03); position: relative;">
          <div style="width: 70px; height: 70px; margin: 0 auto 24px; border: 1.5px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(var(--or-rgb), 0.08); color: var(--or); font-size: 1.6rem;">
            <i class="fas fa-gem"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); margin-bottom: 12px; font-weight: 600;">Luxe Authentique</h3>
          <p style="font-size: 0.92rem; color: #666; line-height: 1.7; font-weight: 300; margin: 0;">Des espaces architecturaux célébrant l'art de vivre et l'artisanat de prestige.</p>
        </div>

        <div class="pillar-card" style="background: var(--blanc-surface, #ffffff); border: 1px solid rgba(var(--or-rgb), 0.22); border-radius: 12px; padding: 40px 30px; text-align: center; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 8px 30px rgba(0,0,0,0.03); position: relative;">
          <div style="width: 70px; height: 70px; margin: 0 auto 24px; border: 1.5px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(var(--or-rgb), 0.08); color: var(--or); font-size: 1.6rem;">
            <i class="fas fa-utensils"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); margin-bottom: 12px; font-weight: 600;">Haute Gastronomie</h3>
          <p style="font-size: 0.92rem; color: #666; line-height: 1.7; font-weight: 300; margin: 0;">Une table d'exception fusionnant saveurs africaines et haute cuisine contemporaine.</p>
        </div>

        <div class="pillar-card" style="background: var(--blanc-surface, #ffffff); border: 1px solid rgba(var(--or-rgb), 0.22); border-radius: 12px; padding: 40px 30px; text-align: center; transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1); box-shadow: 0 8px 30px rgba(0,0,0,0.03); position: relative;">
          <div style="width: 70px; height: 70px; margin: 0 auto 24px; border: 1.5px solid var(--or); border-radius: 50%; display: flex; align-items: center; justify-content: center; background: rgba(var(--or-rgb), 0.08); color: var(--or); font-size: 1.6rem;">
            <i class="fas fa-sun"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); margin-bottom: 12px; font-weight: 600;">Couchers de Soleil</h3>
          <p style="font-size: 0.92rem; color: #666; line-height: 1.7; font-weight: 300; margin: 0;">Des panoramas infinis contemplés dans l'intimité de votre suite ou terrasse.</p>
        </div>

      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       CHAMBRES & SUITES D'EXCEPTION
  ══════════════════════════════════════════ -->
  <section style="padding: 120px 40px; background: linear-gradient(180deg, var(--blanc) 0%, rgba(var(--or-rgb), 0.05) 50%, var(--blanc) 100%);">
    <div style="max-width: 1240px; margin: 0 auto;">
      
      <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 56px; flex-wrap: wrap; gap: 24px;">
        <div>
          <span style="font-family: 'Jost', sans-serif; font-size: 0.68rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--or); font-weight: 500;">Collection Privée</span>
          <h2 style="font-family: 'Cormorant Garamond', serif; font-size: clamp(2.2rem, 4vw, 3.4rem); color: var(--vert); margin-top: 8px; font-weight: 300; line-height: 1.2;">
            Chambres & Suites <em>de Prestige</em>
          </h2>
        </div>
        <a href="pages/chambres.php" class="btn-card-action" style="background: transparent; color: var(--vert); border: 1.5px solid var(--vert); padding: 12px 28px; text-decoration: none; font-size: 0.72rem; letter-spacing: 0.2em; text-transform: uppercase; font-weight: 500; transition: all 0.3s;">
          Explorer toute la collection <i class="fas fa-arrow-right" style="margin-left: 6px;"></i>
        </a>
      </div>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 36px;">
        
        <!-- Suite Royale -->
        <div class="luxury-room-card" style="background: var(--blanc-surface, #ffffff); border-radius: 12px; overflow: hidden; border: 1px solid rgba(var(--or-rgb), 0.2); box-shadow: 0 12px 36px rgba(0,0,0,0.06); transition: all 0.4s ease;">
          <div style="height: 290px; overflow: hidden; position: relative;">
            <img src="https://images.unsplash.com/photo-1590490360182-c33d57733427?w=800&q=85" 
                 alt="Suite Royale - Vue océan" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s ease;">
            <span style="position: absolute; top: 18px; left: 18px; background: rgba(var(--vert-rgb), 0.85); color: var(--or); font-size: 0.62rem; font-weight: 600; letter-spacing: 0.18em; text-transform: uppercase; padding: 6px 14px; border-radius: 20px; backdrop-filter: blur(8px); border: 1px solid rgba(var(--or-rgb), 0.3);">
              <i class="fas fa-crown"></i> Suite Signature
            </span>
          </div>
          <div style="padding: 32px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
              <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: var(--vert); margin: 0; font-weight: 600;">Suite Royale Vue Océan</h3>
            </div>
            <p style="font-size: 0.82rem; color: var(--or-texte, var(--or)); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 16px; font-weight: 500;">
              80 m² • Vue Mer Panoramique • Jacuzzi Privatif
            </p>
            <p style="font-size: 0.92rem; color: #666; line-height: 1.7; margin-bottom: 24px; font-weight: 300;">
              Terrasse suspendue face au large, majordome dédié 24h/24 et salon d'apparat baigné de lumière naturelle.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 18px; border-top: 1px solid rgba(var(--or-rgb), 0.15);">
              <div>
                <span style="font-size: 0.72rem; color: #888; text-transform: uppercase; letter-spacing: 0.1em; display: block;">Tarif Privilège</span>
                <span style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); font-weight: 700;">185 000 <?= htmlspecialchars(hotel_currency()) ?> <small style="font-size: 0.8rem; color: #888; font-weight: 300;">/ nuit</small></span>
              </div>
              <a href="pages/reservation-system.php" class="btn-action-view" style="background: var(--or); color: #111; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 700; transition: all 0.3s;">
                Réserver
              </a>
            </div>
          </div>
        </div>
        
        <!-- Villa Privée -->
        <div class="luxury-room-card" style="background: var(--blanc-surface, #ffffff); border-radius: 12px; overflow: hidden; border: 1px solid rgba(var(--or-rgb), 0.2); box-shadow: 0 12px 36px rgba(0,0,0,0.06); transition: all 0.4s ease;">
          <div style="height: 290px; overflow: hidden; position: relative;">
            <img src="https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?w=800&q=85" 
                 alt="Villa Privée avec Piscine" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s ease;">
            <span style="position: absolute; top: 18px; left: 18px; background: rgba(var(--vert-rgb), 0.85); color: var(--or); font-size: 0.62rem; font-weight: 600; letter-spacing: 0.18em; text-transform: uppercase; padding: 6px 14px; border-radius: 20px; backdrop-filter: blur(8px); border: 1px solid rgba(var(--or-rgb), 0.3);">
              <i class="fas fa-water"></i> Villa & Piscine
            </span>
          </div>
          <div style="padding: 32px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
              <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: var(--vert); margin: 0; font-weight: 600;">Villa Privée Sanctuaire</h3>
            </div>
            <p style="font-size: 0.82rem; color: var(--or-texte, var(--or)); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 16px; font-weight: 500;">
              120 m² • Jardin Tropical • Bassin Privé
            </p>
            <p style="font-size: 0.92rem; color: #666; line-height: 1.7; margin-bottom: 24px; font-weight: 300;">
              L'intimité la plus exclusive avec piscine privée, salon en plein air et accès direct au rivage.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 18px; border-top: 1px solid rgba(var(--or-rgb), 0.15);">
              <div>
                <span style="font-size: 0.72rem; color: #888; text-transform: uppercase; letter-spacing: 0.1em; display: block;">Tarif Privilège</span>
                <span style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); font-weight: 700;">320 000 <?= htmlspecialchars(hotel_currency()) ?> <small style="font-size: 0.8rem; color: #888; font-weight: 300;">/ nuit</small></span>
              </div>
              <a href="pages/reservation-system.php" class="btn-action-view" style="background: var(--or); color: #111; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 700; transition: all 0.3s;">
                Réserver
              </a>
            </div>
          </div>
        </div>
        
        <!-- Chambre Supérieure -->
        <div class="luxury-room-card" style="background: var(--blanc-surface, #ffffff); border-radius: 12px; overflow: hidden; border: 1px solid rgba(var(--or-rgb), 0.2); box-shadow: 0 12px 36px rgba(0,0,0,0.06); transition: all 0.4s ease;">
          <div style="height: 290px; overflow: hidden; position: relative;">
            <img src="https://images.unsplash.com/photo-1618773928121-c32242e63f39?w=800&q=85" 
                 alt="Chambre Supérieure" 
                 style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.8s ease;">
            <span style="position: absolute; top: 18px; left: 18px; background: rgba(var(--vert-rgb), 0.85); color: var(--or); font-size: 0.62rem; font-weight: 600; letter-spacing: 0.18em; text-transform: uppercase; padding: 6px 14px; border-radius: 20px; backdrop-filter: blur(8px); border: 1px solid rgba(var(--or-rgb), 0.3);">
              <i class="fas fa-spa"></i> Confort Deluxe
            </span>
          </div>
          <div style="padding: 32px 28px;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 8px;">
              <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.6rem; color: var(--vert); margin: 0; font-weight: 600;">Chambre Deluxe Jardin</h3>
            </div>
            <p style="font-size: 0.82rem; color: var(--or-texte, var(--or)); letter-spacing: 0.08em; text-transform: uppercase; margin-bottom: 16px; font-weight: 500;">
              45 m² • Balcon Privatif • Bain en Marbre
            </p>
            <p style="font-size: 0.92rem; color: #666; line-height: 1.7; margin-bottom: 24px; font-weight: 300;">
              Une atmosphère apaisante ornée de bois nobles avec vue dégagée sur les allées de palmiers.
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; padding-top: 18px; border-top: 1px solid rgba(var(--or-rgb), 0.15);">
              <div>
                <span style="font-size: 0.72rem; color: #888; text-transform: uppercase; letter-spacing: 0.1em; display: block;">Tarif Privilège</span>
                <span style="font-family: 'Cormorant Garamond', serif; font-size: 1.45rem; color: var(--vert); font-weight: 700;">85 000 <?= htmlspecialchars(hotel_currency()) ?> <small style="font-size: 0.8rem; color: #888; font-weight: 300;">/ nuit</small></span>
              </div>
              <a href="pages/reservation-system.php" class="btn-action-view" style="background: var(--or); color: #111; padding: 10px 20px; border-radius: 4px; text-decoration: none; font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase; font-weight: 700; transition: all 0.3s;">
                Réserver
              </a>
            </div>
          </div>
        </div>

      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       EXPÉRIENCES & SENSORIALITÉ
  ══════════════════════════════════════════ -->
  <section style="padding: 120px 40px; background: linear-gradient(135deg, var(--vert) 0%, var(--vert-sombre, var(--color-dark)) 100%); color: #ffffff; position: relative; overflow: hidden;">
    
    <div style="max-width: 1240px; margin: 0 auto; position: relative; z-index: 2;">
      
      <div style="text-align: center; max-width: 760px; margin: 0 auto 64px;">
        <span style="font-family: 'Jost', sans-serif; font-size: 0.68rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--or); font-weight: 500;">Moments d'Exception</span>
        <h2 style="font-family: 'Cormorant Garamond', serif; font-size: clamp(2.2rem, 4vw, 3.4rem); color: #ffffff; margin-top: 8px; font-weight: 300;">
          S'évader, se ressourcer, <em>se réinventer</em>
        </h2>
        <p style="font-size: 0.95rem; color: rgba(255,255,255,0.75); margin-top: 14px; font-weight: 300; line-height: 1.8;">
          Chaque journée à <?= htmlspecialchars(hotel_name()) ?> s'articule autour d'expériences exclusives sur-mesure orchestrées par notre conciergerie.
        </p>
      </div>
      
      <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 28px;">
        
        <div class="exp-box" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(var(--or-rgb), 0.25); border-radius: 12px; padding: 40px 32px; transition: all 0.3s ease;">
          <div style="font-size: 2rem; color: var(--or); margin-bottom: 20px;">
            <i class="fas fa-spa"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px;">Détente & Spa Holistique</h3>
          <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.75); font-weight: 300; margin: 0;">
            Massages signatures aux huiles essentielles rares d'Afrique de l'Ouest, sauna et rituels de relaxation face à l'océan.
          </p>
        </div>

        <div class="exp-box" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(var(--or-rgb), 0.25); border-radius: 12px; padding: 40px 32px; transition: all 0.3s ease;">
          <div style="font-size: 2rem; color: var(--or); margin-bottom: 20px;">
            <i class="fas fa-wine-glass-alt"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px;">Gastronomie & Mixologie</h3>
          <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.75); font-weight: 300; margin: 0;">
            Dîners gastronomiques privés sur le sable étoilé, accords mets-vins prestigieux et bar à cocktails signature.
          </p>
        </div>

        <div class="exp-box" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(var(--or-rgb), 0.25); border-radius: 12px; padding: 40px 32px; transition: all 0.3s ease;">
          <div style="font-size: 2rem; color: var(--or); margin-bottom: 20px;">
            <i class="fas fa-ship"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px;">Évasions Nautiques</h3>
          <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.75); font-weight: 300; margin: 0;">
            Excursions privées en pirogue artisanale, paddle dans la lagune et croisières coucher de soleil au champagne.
          </p>
        </div>

        <div class="exp-box" style="background: rgba(255,255,255,0.04); border: 1px solid rgba(var(--or-rgb), 0.25); border-radius: 12px; padding: 40px 32px; transition: all 0.3s ease;">
          <div style="font-size: 2rem; color: var(--or); margin-bottom: 20px;">
            <i class="fas fa-compass"></i>
          </div>
          <h3 style="font-family: 'Cormorant Garamond', serif; font-size: 1.5rem; color: var(--or); margin-bottom: 12px;">Immersion & Culture</h3>
          <p style="font-size: 0.92rem; line-height: 1.8; color: rgba(255,255,255,0.75); font-weight: 300; margin: 0;">
            Rencontres privilégiées avec les maîtres artisans sculpteurs, ateliers de tissage et découverte des trésors locaux.
          </p>
        </div>

      </div>

    </div>
  </section>

  <!-- ══════════════════════════════════════════
       TÉMOIGNAGES — Paroles d'Hôtes Privilégiés
  ══════════════════════════════════════════ -->
  <section style="padding: 110px 40px; background: var(--blanc); position: relative;">
    <div style="max-width: 920px; margin: 0 auto; text-align: center;">
      
      <div style="margin-bottom: 24px;">
        <span style="font-family:'Cormorant Garamond',serif; font-size: 3.8rem; color: var(--or); line-height: 1; display: inline-block;">❝</span>
      </div>
      
      <blockquote style="font-family: 'Cormorant Garamond', serif; font-size: clamp(1.5rem, 3vw, 2.1rem); color: var(--vert); font-style: italic; line-height: 1.6; margin: 0 0 32px 0; font-weight: 300;">
        « Un havre de paix intemporel. De l'accueil personnalisé de la conciergerie à la perfection des suites face au couchant, chaque détail témoigne d'une quête absolue de perfection. »
      </blockquote>
      
      <div style="font-size: 0.92rem; color: #666; font-family: 'Jost', sans-serif;">
        <strong style="color: var(--vert); font-weight: 600; text-transform: uppercase; letter-spacing: 0.12em;">Marie & Jean-Pierre L.</strong>
        <span style="display: block; font-size: 0.75rem; color: var(--or); margin-top: 4px; letter-spacing: 0.2em; text-transform: uppercase;">Séjour en Suite Royale · Paris, France</span>
      </div>
      
      <div style="margin-top: 14px; color: var(--or); font-size: 1.1rem; letter-spacing: 4px;">★★★★★</div>
    </div>
  </section>

  <!-- ══════════════════════════════════════════
       CALL TO ACTION — Réservez votre Évasion
  ══════════════════════════════════════════ -->
  <section style="padding: 100px 40px; background: linear-gradient(135deg, var(--vert-clair) 0%, var(--vert) 100%); text-align: center; position: relative; border-top: 2px solid rgba(var(--or-rgb), 0.3);">
    <div style="max-width: 700px; margin: 0 auto;">
      <span style="font-family: 'Jost', sans-serif; font-size: 0.68rem; letter-spacing: 0.35em; text-transform: uppercase; color: var(--or-pale); font-weight: 600; display: block; margin-bottom: 12px;">Votre Écrin de Sérénité</span>
      <h2 style="font-family: 'Cormorant Garamond', serif; font-size: clamp(2.4rem, 4.5vw, 3.6rem); color: #ffffff; margin-bottom: 20px; font-weight: 300; line-height: 1.2;">
        Réservez votre <em>séjour inoubliable</em>
      </h2>
      <p style="font-size: 1.05rem; color: rgba(255,255,255,0.85); margin-bottom: 38px; line-height: 1.8; font-weight: 300;">
        Profitez de nos offres exclusives et d'un accueil VIP sur-mesure dès votre arrivée à l'hôtel.
      </p>
      <a href="pages/reservation-system.php" style="display: inline-block; background: var(--or); color: #111; padding: 18px 48px; text-decoration: none; font-size: 0.78rem; letter-spacing: 0.25em; text-transform: uppercase; border-radius: 4px; font-weight: 700; box-shadow: 0 10px 30px rgba(var(--or-rgb), 0.35); transition: all 0.3s ease;">
        <i class="fas fa-calendar-check" style="margin-right: 8px;"></i> Réserver Maintenant
      </a>
    </div>
  </section>

<?php include("./layouts/footer.php") ?>