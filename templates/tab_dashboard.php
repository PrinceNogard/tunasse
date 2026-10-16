<?php
/** @var array $state */
/** @var array $budgets */
/** @var array $travaux */
/** @var array $simulator */
use Tunasse\Utils\Format;

$p1 = $state['profiles'][0] ?? ['name' => 'Axel', 'initials' => 'AX', 'color' => '#0ea5e9'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane', 'initials' => 'AR', 'color' => '#8b5cf6'];
$p1Contrib = $budgets['p1Contrib'] ?? [];
$p2Contrib = $budgets['p2Contrib'] ?? [];
$recentExpenses = array_slice(array_reverse($state['expenses'] ?? []), 0, 8);
?>
      <!-- ================= TAB 1: DASHBOARD (SERVER-RENDERED VIA PHP) ================= -->
      <section class="tab-pane active" id="pane-dashboard">
        <div class="grid-2-col">
          <!-- Monthly Couple Breakdown Card -->
          <div class="glass-card contribution-card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Répartition Mensuelle du Budget</h2>
                <p class="card-subtitle">Contribution exacte de chaque profil après rééquilibrage du prêt familial</p>
              </div>
              <div class="split-mode-pill" id="split-mode-label">
                <?= ($state['splitRule']['mode'] ?? '50') === '50' ? '50 / 50' : 'Proportionnelle aux Revenus' ?> + Prêt compensé
              </div>
            </div>

            <!-- Couple Dynamic Cards -->
            <div class="couple-balance-grid">
              <!-- Profile 1: Axel -->
              <div class="partner-card you-card" id="card-partner-1">
                <div class="partner-avatar" id="avatar-p1" style="background:<?= htmlspecialchars($p1['color']) ?>">
                  <span class="avatar-initials" id="initials-p1"><?= htmlspecialchars($p1['initials']) ?></span>
                </div>
                <div class="partner-meta">
                  <span class="partner-name" id="name-p1-label">Contribution de <?= htmlspecialchars($p1['name']) ?></span>
                  <div class="partner-amount" id="contrib-p1-total"><?= Format::currency($p1Contrib['total'] ?? 0) ?></div>
                  <span class="partner-detail text-amber" id="contrib-p1-breakdown">dont +<?= Format::currency($p1Contrib['loan'] ?? 69.44) ?> pour prêt familial</span>
                </div>
                <div class="partner-bar-wrapper">
                  <div class="partner-bar" id="bar-p1" style="width: <?= (int)($budgets['p1Pct'] ?? 53) ?>%;"></div>
                </div>
                <div class="partner-sublist">
                  <div class="sublist-item">
                    <span>Part LCL (Package Immo & Taxe)</span>
                    <strong id="contrib-p1-lcl"><?= Format::currency($p1Contrib['lcl'] ?? 0) ?></strong>
                  </div>
                  <div class="sublist-item">
                    <span>Part Revolut (Vie courante)</span>
                    <strong id="contrib-p1-rev"><?= Format::currency($p1Contrib['rev'] ?? 0) ?></strong>
                  </div>
                  <div class="sublist-item highlight-amber">
                    <span id="contrib-p1-loan-label">Mensualité prêt familial</span>
                    <strong id="contrib-p1-loan">+<?= Format::currency($p1Contrib['loan'] ?? 69.44) ?></strong>
                  </div>
                </div>
              </div>

              <!-- Profile 2: Ariane -->
              <div class="partner-card ariane-card" id="card-partner-2">
                <div class="partner-avatar ariane-avatar" id="avatar-p2" style="background:<?= htmlspecialchars($p2['color']) ?>">
                  <span class="avatar-initials" id="initials-p2"><?= htmlspecialchars($p2['initials']) ?></span>
                </div>
                <div class="partner-meta">
                  <span class="partner-name" id="name-p2-label">Contribution d'<?= htmlspecialchars($p2['name']) ?></span>
                  <div class="partner-amount" id="contrib-p2-total"><?= Format::currency($p2Contrib['total'] ?? 0) ?></div>
                  <span class="partner-detail text-emerald" id="contrib-p2-breakdown">mensualité prêt déduite</span>
                </div>
                <div class="partner-bar-wrapper">
                  <div class="partner-bar ariane" id="bar-p2" style="width: <?= (int)($budgets['p2Pct'] ?? 47) ?>%;"></div>
                </div>
                <div class="partner-sublist">
                  <div class="sublist-item">
                    <span>Part LCL (Package Immo & Taxe)</span>
                    <strong id="contrib-p2-lcl"><?= Format::currency($p2Contrib['lcl'] ?? 0) ?></strong>
                  </div>
                  <div class="sublist-item">
                    <span>Part Revolut (Vie courante)</span>
                    <strong id="contrib-p2-rev"><?= Format::currency($p2Contrib['rev'] ?? 0) ?></strong>
                  </div>
                  <div class="sublist-item highlight-green">
                    <span id="contrib-p2-loan-label">Crédit prêt reçu / déduit</span>
                    <strong id="contrib-p2-loan">-<?= Format::currency($p2Contrib['loan'] ?? 69.44) ?></strong>
                  </div>
                </div>
              </div>
            </div>

            <!-- Clear explainer on difference -->
            <div class="smart-notice" id="equalizer-dashboard-notice">
              <div class="notice-icon">💡</div>
              <div class="notice-text" id="equalizer-notice-text">
                Méthode active : <strong>Ajustement direct sur compte joint LCL</strong>. <?= htmlspecialchars($p1['name']) ?> verse <strong>+<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> de plus au compte joint LCL et <?= htmlspecialchars($p2['name']) ?> verse <strong>-<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></strong> de moins chaque mois, soldant le prêt sans aucun virement privé séparé.
              </div>
            </div>

            <!-- In-dashboard Quick Slider for convenience -->
            <div class="quick-sim-dashboard mt-4">
              <div class="quick-sim-header">
                <span class="quick-sim-title">⚡ Ajustement rapide du Prêt Familial :</span>
                <span class="quick-sim-val text-amber font-mono" id="dash-quick-loan-val"><?= Format::currency($state['simulator']['totalAmount'] ?? 25000) ?> • <?= (int)($state['simulator']['durationYears'] ?? 15) ?> ans = <?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?>/m</span>
              </div>
              <div class="quick-sim-controls">
                <input type="range" id="dash-slider-amount" class="range-slider" min="5000" max="60000" step="500" value="<?= (int)($state['simulator']['totalAmount'] ?? 25000) ?>">
                <div class="quick-presets-row">
                  <button type="button" class="preset-btn" data-amount="15000">15 k€</button>
                  <button type="button" class="preset-btn active" data-amount="25000">25 k€ (Défaut)</button>
                  <button type="button" class="preset-btn" data-amount="30000">30 k€</button>
                  <button type="button" class="preset-btn" data-amount="40000">40 k€</button>
                  <button type="button" class="btn btn-ghost btn-sm" id="btn-jump-to-simulator">Ouvrir le simulateur complet →</button>
                </div>
              </div>
            </div>
          </div>

          <!-- Quick Charts / Overview -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Poids des Dépenses</h2>
                <p class="card-subtitle">Répartition Maison Statique (LCL) vs Dépenses Courantes (Revolut)</p>
              </div>
            </div>

            <div class="chart-container">
              <div class="donut-chart-wrapper">
                <svg viewBox="0 0 100 100" class="donut-svg" id="global-donut-svg">
                  <!-- SVG tracks populated dynamically via JS -->
                  <circle cx="50" cy="50" r="38" fill="transparent" stroke="#1e293b" stroke-width="12"></circle>
                  <circle cx="50" cy="50" r="38" fill="transparent" stroke="#0ea5e9" stroke-width="12" stroke-dasharray="130 238" stroke-dashoffset="0"></circle>
                  <circle cx="50" cy="50" r="38" fill="transparent" stroke="#8b5cf6" stroke-width="12" stroke-dasharray="108 238" stroke-dashoffset="-130"></circle>
                </svg>
                <div class="donut-center-info">
                  <span class="donut-center-value" id="donut-center-total"><?= Format::currency($budgets['totalMonthly'] ?? 0) ?></span>
                  <span class="donut-center-label">Mensuel</span>
                </div>
              </div>

              <div class="donut-legend" id="global-donut-legend">
                <div class="legend-row">
                  <span class="chip-dot dot-cyan"></span>
                  <span class="legend-title">LCL (Maison Statique) :</span>
                  <strong class="font-mono"><?= Format::currency($budgets['lclTotal'] ?? 1382) ?></strong>
                  <span class="legend-pct">(<?= round(($budgets['lclTotal'] / ($budgets['totalMonthly'] ?: 1)) * 100, 1) ?>%)</span>
                </div>
                <div class="legend-row">
                  <span class="chip-dot dot-violet"></span>
                  <span class="legend-title">Revolut (Vie Courante) :</span>
                  <strong class="font-mono"><?= Format::currency($budgets['revolutTotal'] ?? 1155) ?></strong>
                  <span class="legend-pct">(<?= round(($budgets['revolutTotal'] / ($budgets['totalMonthly'] ?: 1)) * 100, 1) ?>%)</span>
                </div>
              </div>
            </div>

            <!-- Annual projection widget -->
            <div class="annual-forecast-box">
              <div class="forecast-header">
                <span class="forecast-title">Projection Annuelle du Foyer</span>
                <span class="forecast-badge">Sur 12 mois</span>
              </div>
              <div class="forecast-grid">
                <div class="forecast-item">
                  <span class="forecast-label">LCL (Statique Annuel)</span>
                  <span class="forecast-val" id="forecast-lcl-annual"><?= Format::currency(($budgets['lclTotal'] ?? 0) * 12) ?></span>
                </div>
                <div class="forecast-item">
                  <span class="forecast-label">Revolut (Vie Annuelle)</span>
                  <span class="forecast-val" id="forecast-revolut-annual"><?= Format::currency(($budgets['revolutTotal'] ?? 0) * 12) ?></span>
                </div>
                <div class="forecast-item highlight">
                  <span class="forecast-label">Total Foyer Annuel</span>
                  <span class="forecast-val" id="forecast-total-annual"><?= Format::currency($budgets['totalAnnual'] ?? 0) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Synthèse : Virements Automatiques Mensuels Programmés (LCL & Revolut) -->
        <div class="glass-card mt-6">
          <div class="card-header">
            <div>
              <div class="badge-amber mb-2">Fonctionnement Récurrent Estimé</div>
              <h2 class="card-title">Virements Automatiques Mensuels Programmés</h2>
              <p class="card-subtitle">Ce qu'<?= htmlspecialchars($p1['name']) ?> et <?= htmlspecialchars($p2['name']) ?> mettent chaque mois de manière automatique sur le compte LCL et le compte Revolut</p>
            </div>
            <button type="button" class="btn btn-primary btn-sm" id="btn-quick-expense-banner">
              + Ajouter une dépense
            </button>
          </div>

          <div class="table-responsive">
            <table class="custom-table">
              <thead>
                <tr>
                  <th>Compte Destinataire</th>
                  <th>Affectation / Type de charges</th>
                  <th class="text-right">Alimentation Totale</th>
                  <th class="text-right" style="color:<?= htmlspecialchars($p1['color']) ?>;">Virement <?= htmlspecialchars($p1['name']) ?></th>
                  <th class="text-right" style="color:<?= htmlspecialchars($p2['color']) ?>;">Virement <?= htmlspecialchars($p2['name']) ?></th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td><span class="bank-tag lcl-tag">LCL</span> <strong>Compte Joint LCL</strong></td>
                  <td class="text-dim">Package Logement Fixe (Prêt bancaire, assurances, taxe foncière, charges)</td>
                  <td class="text-right font-mono font-bold text-cyan"><?= Format::currency($budgets['lclTotal'] ?? 1382) ?></td>
                  <td class="text-right font-mono text-cyan" id="auto-lcl-p1"><?= Format::currency($p1Contrib['lcl'] ?? 760.44) ?></td>
                  <td class="text-right font-mono text-cyan" id="auto-lcl-p2"><?= Format::currency($p2Contrib['lcl'] ?? 621.56) ?></td>
                </tr>
                <tr>
                  <td><span class="bank-tag rev-tag">Revolut</span> <strong>Compte Commun Revolut</strong></td>
                  <td class="text-dim">Vie Courante & Foyer (Courses alimentaires, abonnements, sorties, imprévus)</td>
                  <td class="text-right font-mono font-bold text-violet"><?= Format::currency($budgets['revolutTotal'] ?? 900) ?></td>
                  <td class="text-right font-mono text-violet" id="auto-rev-p1"><?= Format::currency($p1Contrib['rev'] ?? 450) ?></td>
                  <td class="text-right font-mono text-violet" id="auto-rev-p2"><?= Format::currency($p2Contrib['rev'] ?? 450) ?></td>
                </tr>
                <tr style="background:rgba(255,255,255,0.02); font-weight:700;">
                  <td colspan="2"><strong>TOTAL DES VIREMENTS AUTOMATIQUES MENSUELS</strong></td>
                  <td class="text-right font-mono font-bold text-emerald"><?= Format::currency($budgets['totalMonthly'] ?? 2282) ?></td>
                  <td class="text-right font-mono font-bold text-emerald" id="auto-total-p1"><?= Format::currency($p1Contrib['total'] ?? 1210.44) ?></td>
                  <td class="text-right font-mono font-bold text-emerald" id="auto-total-p2"><?= Format::currency($p2Contrib['total'] ?? 1071.56) ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Travaux Quick Snapshot in Dashboard -->
        <div class="glass-card mt-6 clickable" id="dash-travaux-card" title="Cliquez pour ouvrir l'enveloppe travaux">
          <div class="card-header">
            <div>
              <div class="badge-emerald mb-2">Rénovation & Chantier Maison</div>
              <h2 class="card-title">Enveloppe Travaux Commune (<?= Format::currency($travaux['totalBudget'] ?? 50000) ?>)</h2>
              <p class="card-subtitle">Suivi du fonds travaux, des factures payées et des avances personnelles de chaque conjoint</p>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-jump-to-travaux">Ouvrir l'espace travaux →</button>
          </div>

          <div class="travaux-dash-grid">
            <div class="travaux-dash-stat">
              <span class="stat-label">Solde Initial Travaux</span>
              <span class="stat-value" id="dash-trv-budget"><?= Format::currency($travaux['totalBudget'] ?? 50000) ?></span>
              <span class="stat-subtext">Fonds disponible</span>
            </div>
            <div class="travaux-dash-stat">
              <span class="stat-label">Consommé Réel</span>
              <span class="stat-value text-amber" id="dash-trv-consumed"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></span>
              <span class="stat-subtext" id="dash-trv-consumed-pct"><?= $travaux['spentPct'] ?? 26.23 ?>% du budget</span>
            </div>
            <div class="travaux-dash-stat">
              <span class="stat-label">Solde Restant</span>
              <span class="stat-value text-emerald" id="dash-trv-remaining"><?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></span>
              <span class="stat-subtext" id="dash-trv-remaining-pct"><?= $travaux['remainingPct'] ?? 73.77 ?>% restant</span>
            </div>
            <div class="travaux-dash-stat">
              <span class="stat-label">Avances Personnelles</span>
              <div class="dash-advances-pills">
                <span class="partner-pill p1-pill" id="dash-trv-p1"><?= htmlspecialchars($p1['name']) ?> : <?= Format::currency($travaux['totalAxel'] ?? 5302) ?></span>
                <span class="partner-pill p2-pill" id="dash-trv-p2"><?= htmlspecialchars($p2['name']) ?> : <?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></span>
              </div>
              <span class="stat-subtext text-emerald" id="dash-trv-delta"><?= htmlspecialchars($travaux['whoAdvancedMore'] ?? 'Ariane') ?> a avancé +<?= Format::currency($travaux['delta'] ?? 2510.85) ?></span>
            </div>
          </div>

          <div class="travaux-progress-bar-container mt-4">
            <div class="travaux-progress-bar">
              <div class="travaux-bar-segment segment-axel" id="dash-trv-bar-axel" style="width: <?= $travaux['axelPctOfBudget'] ?? 10.6 ?>%;" title="Avance <?= htmlspecialchars($p1['name']) ?> : <?= Format::currency($travaux['totalAxel'] ?? 5302) ?>"></div>
              <div class="travaux-bar-segment segment-ariane" id="dash-trv-bar-ariane" style="width: <?= $travaux['arianePctOfBudget'] ?? 15.63 ?>%;" title="Avance <?= htmlspecialchars($p2['name']) ?> : <?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?>"></div>
            </div>
            <div class="travaux-progress-legend">
              <span class="legend-chip"><span class="chip-dot dot-cyan"></span> <?= htmlspecialchars($p1['name']) ?> : <strong id="dash-trv-legend-axel"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?></strong> (<?= $travaux['axelPctOfSpent'] ?? 40.4 ?>%)</span>
              <span class="legend-chip"><span class="chip-dot dot-violet"></span> <?= htmlspecialchars($p2['name']) ?> : <strong id="dash-trv-legend-ariane"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></strong> (<?= $travaux['arianePctOfSpent'] ?? 59.6 ?>%)</span>
              <span class="legend-chip"><span class="chip-dot dot-slate"></span> Restant : <strong id="dash-trv-legend-rem"><?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></strong> (<?= $travaux['remainingPct'] ?? 73.8 ?>%)</span>
            </div>
          </div>
        </div>

        <!-- Recent Transactions / Activity Strip -->
        <div class="glass-card mt-6">
          <div class="card-header">
            <div>
              <h2 class="card-title">Dernières Dépenses Communes</h2>
              <p class="card-subtitle">Historique des achats sur le compte commun Revolut ou LCL</p>
            </div>
            <button class="btn btn-secondary btn-sm" id="btn-view-all-expenses">Voir toutes les dépenses</button>
          </div>

          <div class="table-responsive">
            <table class="custom-table" id="recent-expenses-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Description</th>
                  <th>Compte</th>
                  <th>Catégorie</th>
                  <th>Payé par</th>
                  <th class="text-right">Montant</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
              <tbody id="recent-expenses-tbody">
                <?php if (empty($recentExpenses)): ?>
                  <tr><td colspan="7" class="text-center text-dim py-4">Aucune dépense enregistrée.</td></tr>
                <?php else: ?>
                  <?php foreach ($recentExpenses as $exp): ?>
                    <tr>
                      <td class="font-mono text-dim"><?= htmlspecialchars(Format::date($exp['date'] ?? '')) ?></td>
                      <td><strong><?= htmlspecialchars($exp['description'] ?? '') ?></strong></td>
                      <td><span class="bank-tag <?= ($exp['account'] ?? '') === 'LCL' ? 'lcl-tag' : 'rev-tag' ?>"><?= htmlspecialchars($exp['account'] ?? '') ?></span></td>
                      <td><span class="badge-subtle"><?= htmlspecialchars($exp['category'] ?? '') ?></span></td>
                      <td>
                        <span class="badge-tag <?= ($exp['payer'] ?? '') === 'Ariane' ? 'ariane-tag' : 'axel-tag' ?>">
                          <?= htmlspecialchars($exp['payer'] ?? '') ?>
                        </span>
                      </td>
                      <td class="text-right font-mono font-bold"><?= Format::currency($exp['amount'] ?? 0) ?></td>
                      <td class="text-center">
                        <button class="btn-icon btn-delete-expense" data-id="<?= htmlspecialchars($exp['id'] ?? '') ?>" title="Supprimer">🗑️</button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </section>
