<!-- FOOTER -->
  <footer id="footer">
    <div class="container-fluid px-5">

      <!-- Logo centré -->
      <div class="footer-logo-area">
        <div class="footer-divider">
          <span></span><i></i><span class="right"></span>
        </div>
        <p class="footer-logo-name"><?= htmlspecialchars(hotel_short_name()) ?></p>
        <p class="footer-logo-tagline"><?= htmlspecialchars(hotel_tagline()) ?></p>
        <div class="footer-divider" style="margin-top:16px;">
          <span></span><i></i><span class="right"></span>
        </div>
      </div>

      <!-- Colonnes footer -->
      <div class="row g-5 pb-5">

        <!-- Navigation -->
        <div class="col-lg-3 col-md-6">
          <div class="footer-nav-col">
            <h6>Navigation</h6>
            <ul>
              <li><a href="<?= $baseUrl ?>/index.php">Accueil</a></li>
              <li><a href="<?= $baseUrl ?>/pages/chambres.php">Chambres &amp; Suites</a></li>
              <li><a href="<?= $baseUrl ?>/pages/services.php">Services &amp; Activités</a></li>
              <li><a href="<?= $baseUrl ?>/pages/room-service.php">Room Service 24h/24</a></li>
              <li><a href="<?= $baseUrl ?>/pages/evenements.php">Événements &amp; Séminaires</a></li>
              <li><a href="<?= $baseUrl ?>/pages/galerie.php">Galerie Photos</a></li>
              <li><a href="<?= $baseUrl ?>/pages/reservation-system.php">Réserver un séjour</a></li>
            </ul>
          </div>
        </div>

        <!-- L'Hôtel -->
        <div class="col-lg-3 col-md-6">
          <div class="footer-nav-col">
            <h6><?= htmlspecialchars(hotel_name()) ?></h6>
            <ul>
              <li><a href="<?= $baseUrl ?>/pages/about.php">Notre Histoire</a></li>
              <li><a href="<?= $baseUrl ?>/pages/about.php#vision">Notre Vision</a></li>
              <li><a href="<?= $baseUrl ?>/pages/mon-compte.php">Espace Privilège Membre</a></li>
              <li><a href="<?= $baseUrl ?>/pages/contact.php">Contact &amp; Accès</a></li>
            </ul>
          </div>
        </div>

        <!-- Contact -->
        <div class="col-lg-3 col-md-6">
          <div class="footer-nav-col">
            <h6>Contact</h6>
            <div class="footer-contact-text">
              <p>
                <?= htmlspecialchars(hotel_name()) ?><br>
                <?= htmlspecialchars(hotel_location()) ?><br>
                <?= htmlspecialchars(hotel_country()) ?>
              </p>
              <br>
              <p><a href="tel:<?= htmlspecialchars(hotel_phone()) ?>"><?= htmlspecialchars(hotel_phone()) ?></a></p>
              <p><a href="mailto:<?= htmlspecialchars(hotel_email()) ?>"><?= htmlspecialchars(hotel_email()) ?></a></p>
              <p><a href="mailto:<?= htmlspecialchars(defined('HOTEL_CONTACT_EMAIL') ? HOTEL_CONTACT_EMAIL : hotel_email()) ?>"><?= htmlspecialchars(defined('HOTEL_CONTACT_EMAIL') ? HOTEL_CONTACT_EMAIL : hotel_email()) ?></a></p>
            </div>
            <div class="footer-social mt-4">
              <a href="#" title="Instagram">IG</a>
              <a href="#" title="Facebook">FB</a>
              <a href="#" title="LinkedIn">LI</a>
            </div>
          </div>
        </div>

        <!-- Newsletter -->
        <div class="col-lg-3 col-md-6">
          <div class="footer-nav-col">
            <h6>Restez Informé</h6>
            <p class="footer-contact-text" style="margin-bottom:24px;">
              Offres exclusives, événements et instants d'exception
              à <?= htmlspecialchars(hotel_name()) ?> — directement dans votre boîte mail.
            </p>
            <div class="footer-newsletter">
              <input type="email" placeholder="Votre adresse e-mail">
              <button>S'abonner</button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Footer bottom -->
    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(hotel_name()) ?> · <?= htmlspecialchars(hotel_location()) ?> · Tous droits réservés</p>
      <div class="footer-bottom-links">
        <a href="<?= $baseUrl ?>/pages/contact.php">Politique de confidentialité</a>
        <a href="<?= $baseUrl ?>/pages/contact.php">Mentions légales</a>
        <a href="<?= $baseUrl ?>/pages/contact.php">CGV</a>
      </div>
    </div>
  </footer>

  <!-- Widget Conciergerie Flottant -->
  <?php include_once __DIR__ . '/concierge-widget.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // ══════════════════════════════════════════
    // HEADER ADAPTATIF UNIVERSEL
    // — Sur le hero de n'importe quelle page : transparent + texte blanc/or
    // — Après scroll : fond blanc luxe + texte sombre
    // ══════════════════════════════════════════
    const header = document.getElementById('header');

    function getPageHero() {
      return document.getElementById('hero') 
          || document.querySelector('.page-hero, .about-hero, .contact-hero, .event-hero, .rs-hero, .galerie-hero, .hero-section, [class*="-hero"]');
    }

    function updateHeader() {
      const hero = getPageHero();
      if (hero) {
        const threshold = Math.max(hero.offsetHeight - 90, 80);
        if (window.scrollY > threshold) {
          header.classList.add('scrolled');
        } else {
          header.classList.remove('scrolled');
        }
      } else {
        // Page sans hero (ex: connexion, mon-compte)
        header.classList.add('scrolled');
      }
    }

    updateHeader();
    window.addEventListener('scroll', updateHeader);
    window.addEventListener('resize', updateHeader);

    // ── Mobile menu drawer ────────────────────────
    function toggleMenu() {
      const menu = document.getElementById('mobileMenu');
      const btn = document.getElementById('hamburger');
      if (!menu) return;
      const isActive = menu.classList.contains('active');
      if (isActive) {
        closeMenu();
      } else {
        menu.classList.add('active');
        if (btn) btn.classList.add('open');
        menu.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
      }
    }

    function closeMenu() {
      const menu = document.getElementById('mobileMenu');
      const btn = document.getElementById('hamburger');
      if (menu) {
        menu.classList.remove('active');
        menu.setAttribute('aria-hidden', 'true');
      }
      if (btn) btn.classList.remove('open');
      document.body.style.overflow = '';
    }

    // Fermeture avec la touche Échap
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Escape') closeMenu();
    });
  </script>
</body>
</html>