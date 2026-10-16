<?php
/** @var array $state */
/** @var array $simulator */
use Tunasse\Utils\Format;
use Tunasse\Finance\LoanCalculator;

$sim = $state['simulator'] ?? [];
$p1 = $state['profiles'][0] ?? ['name' => 'Axel'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane'];

$schedule = LoanCalculator::generateAmortizationSchedule(
    $simulator['borrowedAmount'] ?? 12500,
    $simulator['monthlyPayment'] ?? 69.44,
    $simulator['durationMonths'] ?? 180,
    $simulator['annualRate'] ?? 0,
    $simulator['startMonth'] ?? '2026-03',
    $simulator['elapsedMonths'] ?? 8
);
?>
      <!-- ================= TAB 2: SIMULATOR PRÊT DYNAMIQUE (SERVER-RENDERED VIA PHP) ================= -->
      <section class="tab-pane" id="pane-simulator">
        <div class="simulator-hero">
          <div class="badge-amber">Moteur de calcul PHP & Temps Réel</div>
          <h2 class="hero-title">Simulateur Prêt Familial & Historique de Remboursement</h2>
          <p class="hero-subtitle" id="sim-hero-description">
            Prêt familial de <strong><?= Format::currency($sim['totalAmount'] ?? 25000) ?></strong> consenti par <strong><?= htmlspecialchars($p2['name']) ?></strong>. <strong><?= htmlspecialchars($p1['name']) ?></strong> rembourse sa part (<?= (int)($sim['userShare'] ?? 50) ?>% = <?= Format::currency($simulator['borrowedAmount'] ?? 12500) ?>) sur <strong><?= (int)($sim['durationYears'] ?? 15) ?> ans</strong> (<?= (int)($simulator['durationMonths'] ?? 180) ?> mois). Le nombre de mensualités déjà versées est calculé automatiquement depuis la date de départ jusqu'au mois actuel.
          </p>
        </div>

        <div class="grid-2-col">
          <!-- Controls Panel -->
          <div class="glass-card controls-card">
            <div class="card-header">
              <div>
                <h3 class="card-title">Paramètres du Prêt</h3>
                <p class="card-subtitle">Curseurs en temps réel et sélection de la date de départ</p>
              </div>
              <button class="btn btn-ghost btn-sm" id="btn-reset-simulator" title="Réinitialiser aux 25 000 € / 15 ans par défaut">
                ↺ Réinitialiser (25k€ / 15 ans)
              </button>
            </div>

            <!-- Generic Borrower / Lender Selector -->
            <div class="loan-roles-box">
              <div class="form-group-row">
                <div class="form-field">
                  <label for="select-loan-borrower">Emprunteur (qui rembourse)</label>
                  <select id="select-loan-borrower" class="custom-select">
                    <option value="<?= htmlspecialchars($p1['name']) ?>" selected><?= htmlspecialchars($p1['name']) ?></option>
                    <option value="<?= htmlspecialchars($p2['name']) ?>"><?= htmlspecialchars($p2['name']) ?></option>
                  </select>
                </div>
                <div class="form-field">
                  <label for="select-loan-lender">Prêteur</label>
                  <select id="select-loan-lender" class="custom-select">
                    <option value="<?= htmlspecialchars($p2['name']) ?>" selected><?= htmlspecialchars($p2['name']) ?></option>
                    <option value="<?= htmlspecialchars($p1['name']) ?>"><?= htmlspecialchars($p1['name']) ?></option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Start Month & Already Paid Section -->
            <!-- Start Month & Automatic Deduction Section -->
            <div class="loan-start-box">
              <div class="slider-header">
                <label for="input-loan-start-month" class="slider-label">
                  📅 Date de départ du remboursement
                </label>
                <div class="slider-input-badge">
                  <span id="label-elapsed-months" class="text-amber font-mono" style="font-weight:700;"><?= (int)($simulator['elapsedMonths'] ?? 8) ?> mois</span>
                  <span style="font-size:0.75rem; color:var(--text-dim);">écoulés</span>
                </div>
              </div>

              <div class="form-group-row mt-2">
                <div class="form-field" style="margin-bottom:0;">
                  <label for="input-loan-start-month" style="font-size:0.75rem; color:var(--text-dim);">Date du premier versement (calendrier)</label>
                  <input type="month" id="input-loan-start-month" class="custom-input" value="<?= htmlspecialchars($sim['startMonth'] ?? '2026-03') ?>">
                </div>
                <div class="form-field" style="margin-bottom:0;">
                  <label style="font-size:0.75rem; color:var(--text-dim);">Déduction automatique</label>
                  <div class="slider-input-badge" style="width:100%; justify-content:space-between; padding:8px 12px; background:rgba(255,255,255,0.03);">
                    <span style="font-size:0.8rem; color:var(--text-muted);">Jusqu'au mois actuel :</span>
                    <strong class="text-amber font-mono" id="auto-months-paid-display"><?= (int)($simulator['elapsedMonths'] ?? 8) ?> mois réglés</strong>
                  </div>
                </div>
              </div>

              <!-- Interactive Month Calendar Selector -->
              <div class="month-calendar-selector mt-3">
                <div class="cal-header">
                  <span style="font-size:0.78rem; color:var(--text-dim); text-transform:uppercase; letter-spacing:0.05em; font-weight:600;">Calendrier mensuel cliquable :</span>
                  <div class="cal-year-nav">
                    <button type="button" class="btn-step" id="btn-cal-year-prev" title="Année précédente">◀</button>
                    <span id="cal-selected-year" class="font-mono text-cyan" style="font-weight:700;">2026</span>
                    <button type="button" class="btn-step" id="btn-cal-year-next" title="Année suivante">▶</button>
                  </div>
                </div>
                <div class="cal-months-grid mt-2" id="cal-months-grid">
                  <button type="button" class="cal-month-btn" data-m="01">Jan</button>
                  <button type="button" class="cal-month-btn" data-m="02">Fév</button>
                  <button type="button" class="cal-month-btn active" data-m="03">Mar</button>
                  <button type="button" class="cal-month-btn" data-m="04">Avr</button>
                  <button type="button" class="cal-month-btn" data-m="05">Mai</button>
                  <button type="button" class="cal-month-btn" data-m="06">Juin</button>
                  <button type="button" class="cal-month-btn" data-m="07">Juil</button>
                  <button type="button" class="cal-month-btn" data-m="08">Aoû</button>
                  <button type="button" class="cal-month-btn" data-m="09">Sep</button>
                  <button type="button" class="cal-month-btn" data-m="10">Oct</button>
                  <button type="button" class="cal-month-btn" data-m="11">Nov</button>
                  <button type="button" class="cal-month-btn" data-m="12">Déc</button>
                </div>
              </div>

              <!-- Quick presets for start month -->
              <div class="quick-presets-row mt-2">
                <button type="button" class="preset-btn" data-start="current">Mois actuel</button>
                <button type="button" class="preset-btn" data-start="6m">Il y a 6 mois</button>
                <button type="button" class="preset-btn active" data-start="8m">Mars 2026 (Défaut)</button>
                <button type="button" class="preset-btn" data-start="1y">Il y a 1 an</button>
                <button type="button" class="preset-btn" data-start="jan24">Janvier 2024</button>
              </div>

              <!-- Summary pill of progress -->
              <div class="loan-history-pills mt-3">
                <div class="history-pill">
                  <span class="history-pill-lbl">Déjà versé (compte commun)</span>
                  <strong class="history-pill-val text-emerald" id="sim-paid-sum-pill"><?= Format::currency($simulator['alreadyPaid'] ?? 555.52) ?></strong>
                </div>
                <div class="history-pill">
                  <span class="history-pill-lbl">Reste à rembourser</span>
                  <strong class="history-pill-val text-amber" id="sim-remaining-sum-pill"><?= Format::currency($simulator['remainingCapital'] ?? 11944.48) ?></strong>
                </div>
                <div class="history-pill">
                  <span class="history-pill-lbl">Fin estimée</span>
                  <strong class="history-pill-val text-cyan" id="sim-end-date-pill"><?= htmlspecialchars($simulator['endDateFormatted'] ?? 'Février 2041') ?></strong>
                </div>
              </div>
            </div>

            <!-- Slider 1: Total Amount -->
            <div class="control-group">
              <div class="slider-header">
                <label for="slider-loan-amount" class="slider-label">
                  Montant total de l'apport / somme prêtée
                </label>
                <div class="slider-input-badge">
                  <input type="number" id="num-loan-amount" class="num-input" value="<?= (int)($sim['totalAmount'] ?? 25000) ?>" min="500" max="250000" step="500">
                  <span>€</span>
                </div>
              </div>
              <input type="range" id="slider-loan-amount" class="range-slider" min="5000" max="60000" step="500" value="<?= (int)($sim['totalAmount'] ?? 25000) ?>">
              
              <div class="quick-presets-row">
                <button type="button" class="preset-btn" data-target="amount" data-val="10000">10 000 €</button>
                <button type="button" class="preset-btn" data-target="amount" data-val="15000">15 000 €</button>
                <button type="button" class="preset-btn active" data-target="amount" data-val="25000">25 000 € (Défaut)</button>
                <button type="button" class="preset-btn" data-target="amount" data-val="35000">35 000 €</button>
                <button type="button" class="preset-btn" data-target="amount" data-val="50000">50 000 €</button>
              </div>
            </div>

            <!-- Slider 2: User Share (%) -->
            <div class="control-group">
              <div class="slider-header">
                <label for="slider-user-share" class="slider-label">
                  Part remboursée par l'emprunteur
                </label>
                <div class="slider-input-badge">
                  <input type="number" id="num-user-share" class="num-input" value="<?= (int)($sim['userShare'] ?? 50) ?>" min="5" max="100" step="5">
                  <span>%</span>
                </div>
              </div>
              <input type="range" id="slider-user-share" class="range-slider" min="10" max="100" step="5" value="<?= (int)($sim['userShare'] ?? 50) ?>">
              
              <div class="quick-presets-row">
                <button type="button" class="preset-btn" data-target="share" data-val="25">25% (Quart)</button>
                <button type="button" class="preset-btn active" data-target="share" data-val="50">50% (Moitié = 12 500 €)</button>
                <button type="button" class="preset-btn" data-target="share" data-val="75">75%</button>
                <button type="button" class="preset-btn" data-target="share" data-val="100">100% (Totalité)</button>
              </div>
            </div>

            <!-- Slider 3: Duration in Years & Months -->
            <div class="control-group">
              <div class="slider-header">
                <label for="slider-loan-duration" class="slider-label">Durée de remboursement</label>
                <div class="duration-display">
                  <span id="display-duration-years"><?= (int)($sim['durationYears'] ?? 15) ?></span> ans
                  <span class="duration-months">(<span id="display-duration-months"><?= (int)($simulator['durationMonths'] ?? 180) ?></span> mois)</span>
                </div>
              </div>
              <input type="range" id="slider-loan-duration" class="range-slider" min="1" max="25" step="1" value="<?= (int)($sim['durationYears'] ?? 15) ?>">
              
              <div class="quick-presets-row">
                <button type="button" class="preset-btn" data-target="duration" data-val="5">5 ans (60m)</button>
                <button type="button" class="preset-btn" data-target="duration" data-val="10">10 ans (120m)</button>
                <button type="button" class="preset-btn active" data-target="duration" data-val="15">15 ans (180m - Prêt Immo)</button>
                <button type="button" class="preset-btn" data-target="duration" data-val="20">20 ans (240m)</button>
              </div>
            </div>

            <!-- Slider 4: Interest Rate -->
            <div class="control-group">
              <div class="slider-header">
                <label for="slider-loan-interest" class="slider-label">Taux d'intérêt annuel (prêt familial amical)</label>
                <div class="slider-input-badge">
                  <input type="number" id="num-loan-interest" class="num-input" value="<?= (float)($sim['interestRate'] ?? 0) ?>" min="0" max="10" step="0.1">
                  <span>%</span>
                </div>
              </div>
              <input type="range" id="slider-loan-interest" class="range-slider" min="0" max="5" step="0.1" value="<?= (float)($sim['interestRate'] ?? 0) ?>">
              <div class="slider-ticks">
                <span class="active-tick">0,0% (Sans intérêt)</span>
                <span>1,5%</span>
                <span>3,0%</span>
                <span>5,0%</span>
              </div>
            </div>

            <!-- Equalization Method Selector -->
            <div class="payment-method-selector">
              <div class="method-title">Méthode d'application dans votre quotidien :</div>
              <div class="method-options">
                <label class="method-option-card">
                  <input type="radio" name="repay-method" value="offset" id="method-offset" <?= ($sim['paymentMethod'] ?? 'offset') === 'offset' ? 'checked' : '' ?>>
                  <div class="method-box">
                    <span class="method-radio-custom"></span>
                    <div>
                      <span class="method-name">Ajustement Compte Commun LCL (Recommandé)</span>
                      <p class="method-desc" id="method-desc-offset">
                        Zéro virement privé ! <?= htmlspecialchars($p1['name']) ?> verse <strong>+<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> de plus au compte joint LCL et <?= htmlspecialchars($p2['name']) ?> verse <strong>-<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> de moins.
                      </p>
                    </div>
                  </div>
                </label>

                <label class="method-option-card">
                  <input type="radio" name="repay-method" value="direct" id="method-direct" <?= ($sim['paymentMethod'] ?? '') === 'direct' ? 'checked' : '' ?>>
                  <div class="method-box">
                    <span class="method-radio-custom"></span>
                    <div>
                      <span class="method-name">Virement direct d'<?= htmlspecialchars($p1['name']) ?> à <?= htmlspecialchars($p2['name']) ?></span>
                      <p class="method-desc" id="method-desc-direct">
                        Vous alimentez à 50/50 le compte joint, et <?= htmlspecialchars($p1['name']) ?> effectue un virement mensuel séparé de <strong><?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> sur le compte perso d'<?= htmlspecialchars($p2['name']) ?>.
                      </p>
                    </div>
                  </div>
                </label>
              </div>
            </div>
          </div>

          <!-- Simulator Dynamic Results & Cards -->
          <div class="simulator-results-col">
            <!-- Key Highlight Results Card -->
            <div class="glass-card result-hero-card">
              <div class="result-top-badge">Résultat calculé en temps réel</div>
              
              <div class="big-monthly-result">
                <span class="big-number" id="sim-monthly-payment"><?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></span>
                <span class="big-period">/ mois</span>
              </div>
              <p class="result-clarification" id="sim-clarification-text">
                Montant exact de la mensualité due par <strong><?= htmlspecialchars($p1['name']) ?></strong> à <strong><?= htmlspecialchars($p2['name']) ?></strong> pendant <strong><?= (int)($sim['durationYears'] ?? 15) ?> ans</strong> (<?= (int)($simulator['durationMonths'] ?? 180) ?> mois).
              </p>

              <!-- Detailed Mathematical Breakdown Box -->
              <div class="result-details-grid">
                <div class="res-item">
                  <span class="res-item-label">Capital net emprunté</span>
                  <span class="res-item-val" id="sim-total-borrowed"><?= Format::currency($simulator['borrowedAmount'] ?? 12500) ?></span>
                  <small id="sim-borrowed-sub">(<?= (int)($sim['userShare'] ?? 50) ?>% de <?= Format::currency($sim['totalAmount'] ?? 25000) ?>)</small>
                </div>
                <div class="res-item">
                  <span class="res-item-label">Durée totale</span>
                  <span class="res-item-val" id="sim-total-months"><?= (int)($simulator['durationMonths'] ?? 180) ?> mois</span>
                  <small id="sim-years-sub">(<?= (int)($sim['durationYears'] ?? 15) ?> années)</small>
                </div>
                <div class="res-item">
                  <span class="res-item-label">Intérêts totaux</span>
                  <span class="res-item-val" id="sim-total-interests"><?= Format::currency($simulator['totalInterests'] ?? 0) ?></span>
                  <small id="sim-interest-sub">(Taux à <?= number_format((float)($sim['interestRate'] ?? 0), 2, ',', ' ') ?>%)</small>
                </div>
                <div class="res-item">
                  <span class="res-item-label">Coût total remboursé</span>
                  <span class="res-item-val highlight-amber" id="sim-grand-total"><?= Format::currency($simulator['grandTotal'] ?? 12500) ?></span>
                  <small>À terme</small>
                </div>
              </div>

              <!-- Visual Explanation on Account Offset -->
              <div class="offset-math-explainer" id="offset-math-box">
                <div class="explainer-heading">
                  <span class="explainer-icon">🔍</span>
                  <strong>L'explication mathématique : Combien en plus par mois ?</strong>
                </div>
                <div class="explainer-content" id="offset-explainer-content">
                  En versant <strong>+<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> sur le compte commun LCL et en laissant <?= htmlspecialchars($p2['name']) ?> verser <strong>-<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> de moins, <?= htmlspecialchars($p1['name']) ?> prend en charge sa part du prêt immobilier exactement à hauteur de sa dette, annulant tout besoin de virement personnel.
                </div>
              </div>
            </div>

            <!-- Reimbursement Tracker & Amortization preview -->
            <div class="glass-card mt-6">
              <div class="card-header">
                <div>
                  <h3 class="card-title">Progression Réelle & Échéancier</h3>
                  <p class="card-subtitle">Visualisation basée sur la date de départ et les mensualités écoulées</p>
                </div>
                <div class="month-counter-badge" id="sim-counter-badge">
                  Mois <?= (int)($simulator['elapsedMonths'] ?? 8) ?> / <span id="track-total-months"><?= (int)($simulator['durationMonths'] ?? 180) ?></span>
                </div>
              </div>

              <div class="progress-bar-container">
                <div class="progress-fill amber-gradient" id="sim-progress-fill" style="width: <?= $simulator['paidPct'] ?? 4.4 ?>%;"></div>
              </div>
              <div class="progress-meta">
                <span>Remboursé : <strong id="sim-paid-so-far" class="text-emerald"><?= Format::currency($simulator['alreadyPaid'] ?? 555.52) ?></strong></span>
                <span>Restant dû : <strong id="sim-remaining-capital" class="text-amber"><?= Format::currency($simulator['remainingCapital'] ?? 11944.48) ?></strong></span>
              </div>
              <div class="time-remaining-meta">
                <span id="sim-time-remaining-label">⏱️ <?= htmlspecialchars($simulator['remainingTimeFormatted'] ?? '14 ans et 4 mois restants') ?></span>
              </div>

              <div class="quick-amort-table-wrapper mt-3">
                <table class="custom-table tight-table">
                  <thead>
                    <tr>
                      <th>Période</th>
                      <th>Mensualité</th>
                      <th>Cumul remboursé</th>
                      <th>Capital restant</th>
                      <th class="text-center">Statut</th>
                    </tr>
                  </thead>
                  <tbody id="sim-amort-tbody">
                    <?php foreach ($schedule as $row): ?>
                      <tr class="<?= $row['isPaid'] ? 'row-paid' : '' ?>">
                        <td class="font-mono"><strong>Mois <?= (int)$row['month'] ?></strong> <small class="text-dim">(<?= htmlspecialchars($row['dateFormatted']) ?>)</small></td>
                        <td class="font-mono"><?= Format::currency($row['payment']) ?></td>
                        <td class="font-mono text-emerald"><?= Format::currency($row['cumulPaid']) ?></td>
                        <td class="font-mono text-amber"><?= Format::currency($row['remainingCapital']) ?></td>
                        <td class="text-center">
                          <?php if ($row['isPaid']): ?>
                            <span class="badge-tag" style="background:rgba(16,185,129,0.15); color:#34d399;">✓ Payé</span>
                          <?php else: ?>
                            <span class="badge-subtle">À venir</span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </section>
