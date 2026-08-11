<?php
/**
 * ════════════════════════════════════════════════════════
 * WIDGET CONCIERGERIE & ASSISTANCE VIP FLOTTANT — Hôtel SEGURO
 * ════════════════════════════════════════════════════════
 */
$hotelPhone = "+228 90 00 00 00";
$hotelWhatsApp = "22890000000";
$hotelEmail = "reservations@hotelseguro.com";
?>
<style>
/* ── Bouton Conciergerie Flottant ── */
.concierge-floating-btn {
  position: fixed;
  bottom: 28px;
  right: 28px;
  z-index: 9990;
  display: flex;
  align-items: center;
  gap: 10px;
  background: linear-gradient(135deg, #1a3a2a, #0e2218);
  color: #c9a84c;
  padding: 12px 20px;
  border-radius: 50px;
  border: 1.5px solid rgba(201, 168, 76, 0.4);
  box-shadow: 0 8px 25px rgba(0, 0, 0, 0.25);
  cursor: pointer;
  transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
  font-family: 'Jost', sans-serif;
  text-decoration: none;
}
.concierge-floating-btn:hover {
  transform: translateY(-3px) scale(1.03);
  box-shadow: 0 12px 30px rgba(201, 168, 76, 0.35);
  border-color: #c9a84c;
  color: #f7e6b8;
}
.concierge-pulse-dot {
  width: 10px;
  height: 10px;
  background: #28a745;
  border-radius: 50%;
  box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7);
  animation: pulseGreen 1.8s infinite;
}
@keyframes pulseGreen {
  0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.7); }
  70% { transform: scale(1); box-shadow: 0 0 0 8px rgba(40, 167, 69, 0); }
  100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
}
.concierge-btn-text {
  font-size: 0.88rem;
  font-weight: 500;
  letter-spacing: 0.5px;
}

/* ── Boîte de Conciergerie / Modal ── */
.concierge-modal-overlay {
  position: fixed;
  inset: 0;
  background: rgba(14, 34, 24, 0.55);
  backdrop-filter: blur(4px);
  z-index: 9998;
  opacity: 0;
  visibility: hidden;
  transition: all 0.3s ease;
}
.concierge-modal-overlay.active {
  opacity: 1;
  visibility: visible;
}
.concierge-card {
  position: fixed;
  bottom: 90px;
  right: 28px;
  width: 380px;
  max-width: calc(100vw - 40px);
  max-height: 80vh;
  background: #ffffff;
  border-radius: 16px;
  box-shadow: 0 15px 50px rgba(0, 0, 0, 0.3);
  border: 1px solid #eae5d7;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  transform: translateY(20px) scale(0.95);
  opacity: 0;
  visibility: hidden;
  transition: all 0.35s cubic-bezier(0.25, 0.8, 0.25, 1);
}
.concierge-card.active {
  transform: translateY(0) scale(1);
  opacity: 1;
  visibility: visible;
}

/* En-tête Concierge */
.concierge-header {
  background: linear-gradient(135deg, #1a3a2a, #0e2218);
  color: #fff;
  padding: 18px 20px;
  display: flex;
  justify-content: space-between;
  align-items: center;
  border-bottom: 2px solid #c9a84c;
}
.concierge-title {
  font-family: 'Cormorant Garamond', serif;
  font-size: 1.3rem;
  font-weight: 600;
  color: #c9a84c;
  margin: 0;
}
.concierge-status {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.75);
  display: flex;
  align-items: center;
  gap: 6px;
  margin-top: 2px;
}
.concierge-close {
  background: none;
  border: none;
  color: #c9a84c;
  font-size: 1.2rem;
  cursor: pointer;
  padding: 4px;
}

/* Corps */
.concierge-body {
  padding: 18px 20px;
  overflow-y: auto;
  flex: 1;
}

/* Action WhatsApp direct */
.whatsapp-action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  background: #25D366;
  color: #fff;
  font-weight: 600;
  padding: 12px 18px;
  border-radius: 10px;
  text-decoration: none;
  font-size: 0.95rem;
  transition: all 0.25s;
  box-shadow: 0 4px 14px rgba(37, 211, 102, 0.35);
  margin-bottom: 16px;
}
.whatsapp-action-btn:hover {
  background: #1ebc59;
  color: #fff;
  transform: translateY(-2px);
}

/* Onglets d'action rapide */
.concierge-quick-links {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
  margin-bottom: 18px;
}
.quick-btn {
  background: #faf8f3;
  border: 1px solid #e8e2d4;
  border-radius: 8px;
  padding: 10px;
  text-align: center;
  color: #1a3a2a;
  text-decoration: none;
  font-size: 0.82rem;
  font-weight: 500;
  transition: all 0.2s;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
}
.quick-btn i {
  color: #c9a84c;
  font-size: 1.1rem;
}
.quick-btn:hover {
  background: #f4efe4;
  border-color: #c9a84c;
  color: #1a3a2a;
}

/* Mini FAQ Accordéon */
.concierge-faq-title {
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  color: #8c6d1f;
  margin: 12px 0 8px;
}
.faq-item {
  border-bottom: 1px solid #f0ecdf;
  padding: 8px 0;
}
.faq-q {
  font-size: 0.85rem;
  font-weight: 500;
  color: #1a3a2a;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.faq-a {
  font-size: 0.8rem;
  color: #666;
  margin-top: 6px;
  line-height: 1.5;
  display: none;
}
.faq-item.active .faq-a {
  display: block;
}
.faq-item.active .faq-q i {
  transform: rotate(180deg);
}

/* Footer widget */
.concierge-footer {
  padding: 10px 18px;
  background: #faf8f3;
  border-top: 1px solid #eae5d7;
  text-align: center;
  font-size: 0.75rem;
  color: #888;
}
</style>

<!-- Bouton Lanceur -->
<div class="concierge-floating-btn" id="btnConciergeOpen" onclick="toggleConcierge(true)">
  <span class="concierge-pulse-dot"></span>
  <i class="fas fa-concierge-bell"></i>
  <span class="concierge-btn-text">Conciergerie SEGURO</span>
</div>

<!-- Modal / Drawer -->
<div class="concierge-modal-overlay" id="conciergeOverlay" onclick="toggleConcierge(false)"></div>
<div class="concierge-card" id="conciergeCard">
  <div class="concierge-header">
    <div>
      <h4 class="concierge-title"><i class="fas fa-crown" style="font-size:1rem;"></i> Conciergerie Privée</h4>
      <div class="concierge-status">
        <span style="width:6px;height:6px;background:#28a745;border-radius:50%;display:inline-block;"></span>
        Équipe de réception en ligne 24h/24
      </div>
    </div>
    <button class="concierge-close" onclick="toggleConcierge(false)">✕</button>
  </div>

  <div class="concierge-body">
    <!-- Bouton WhatsApp -->
    <a href="https://wa.me/<?= $hotelWhatsApp ?>?text=<?= urlencode("Bonjour Conciergerie SEGURO, je souhaiterais des renseignements concernant vos chambres et prestations.") ?>" target="_blank" class="whatsapp-action-btn">
      <i class="fab fa-whatsapp" style="font-size:1.3rem;"></i>
      Discuter sur WhatsApp Direct
    </a>

    <!-- Raccourcis -->
    <div class="concierge-quick-links">
      <a href="tel:<?= str_replace(' ', '', $hotelPhone) ?>" class="quick-btn">
        <i class="fas fa-phone-alt"></i>
        <span>Appel Réception</span>
      </a>
      <a href="<?= $baseUrl ?? '' ?>/pages/reservation-system.php" class="quick-btn">
        <i class="fas fa-calendar-alt"></i>
        <span>Réserver Séjour</span>
      </a>
    </div>

    <!-- Mini FAQ -->
    <div class="concierge-faq-title">Questions fréquentes</div>
    
    <div class="faq-item">
      <div class="faq-q" onclick="this.parentElement.classList.toggle('active')">
        <span>🕒 Heures de Check-in &amp; Check-out</span>
        <i class="fas fa-chevron-down" style="font-size:0.75rem; transition:transform 0.2s;"></i>
      </div>
      <div class="faq-a">
        Arrivée (Check-in) dès <strong>14h00</strong>. Départ (Check-out) jusqu'à <strong>12h00</strong>. Arrivée anticipée sur demande.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="this.parentElement.classList.toggle('active')">
        <span>🚗 Navette aéroport disponible ?</span>
        <i class="fas fa-chevron-down" style="font-size:0.75rem; transition:transform 0.2s;"></i>
      </div>
      <div class="faq-a">
        Oui, un service de transfert privé VIP est disponible 24h/24 avec chauffeur dédié. Vous pouvez le cocher lors de votre réservation.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="this.parentElement.classList.toggle('active')">
        <span>💳 Moyens de paiement acceptés</span>
        <i class="fas fa-chevron-down" style="font-size:0.75rem; transition:transform 0.2s;"></i>
      </div>
      <div class="faq-a">
        Mobile Money (Flooz, Moov, MTN, T-Money, Orange, Wave), Carte Bancaire (Visa, Mastercard) et espèces à la réception.
      </div>
    </div>

    <div class="faq-item">
      <div class="faq-q" onclick="this.parentElement.classList.toggle('active')">
        <span>🥐 Petit-déjeuner buffet</span>
        <i class="fas fa-chevron-down" style="font-size:0.75rem; transition:transform 0.2s;"></i>
      </div>
      <div class="faq-a">
        Buffet gourmand chaud &amp; froid servi chaque matin de 06h30 à 10h30 au restaurant panoramique avec vue lagune.
      </div>
    </div>
  </div>

  <div class="concierge-footer">
    Hôtel SEGURO · Boulevard de la Marina, Lomé
  </div>
</div>

<script>
function toggleConcierge(show) {
  const card = document.getElementById('conciergeCard');
  const overlay = document.getElementById('conciergeOverlay');
  if (show) {
    card.classList.add('active');
    overlay.classList.add('active');
  } else {
    card.classList.remove('active');
    overlay.classList.remove('active');
  }
}
</script>
