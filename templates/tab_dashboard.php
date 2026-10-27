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

        <!-- Synthèse Interactive : Virements Réels vs Répartition Estimée -->
        <div class="glass-card mt-6">
          <div class="card-header">
            <div>
              <div class="badge-amber mb-2">Virements Réels vs Estimation Théorique</div>
              <h2 class="card-title">Virements Automatiques Mensuels Programmés</h2>
              <p class="card-subtitle">Saisissez les montants exacts que vous virez chaque mois sur LCL et Revolut pour comparer avec la répartition calculée</p>
            </div>
            <div id="transfer-sync-badge" class="badge-tag" style="background:rgba(16,185,129,0.12); color:#34d399;">
              ● Synchronisé en direct
            </div>
          </div>

          <div class="table-responsive">
            <table class="custom-table transfers-comparison-table">
              <thead>
                <tr>
                  <th>Compte Destinataire</th>
                  <th class="text-right">Part Estimée <?= htmlspecialchars($p1['name']) ?></th>
                  <th class="text-right" style="color:<?= htmlspecialchars($p1['color']) ?>;">Viré Réel <?= htmlspecialchars($p1['name']) ?></th>
                  <th class="text-right">Part Estimée <?= htmlspecialchars($p2['name']) ?></th>
                  <th class="text-right" style="color:<?= htmlspecialchars($p2['color']) ?>;">Viré Réel <?= htmlspecialchars($p2['name']) ?></th>
                  <th class="text-right">Total Réel Viré</th>
                  <th class="text-right">Écart vs Budget</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td>
                    <span class="bank-tag lcl-tag">LCL</span> <strong>Compte Joint LCL</strong>
                    <div style="font-size:0.75rem; color:var(--text-dim); margin-top:2px;">Package Logement (<?= Format::currency($budgets['lclTotal'] ?? 1382) ?>)</div>
                  </td>
                  <td class="text-right font-mono" id="target-lcl-p1"><?= Format::currency($p1Contrib['lcl'] ?? 760.44) ?></td>
                  <td class="text-right">
                    <input type="number" step="0.5" id="actual-lcl-p1" class="transfer-input font-mono" value="<?= (float)($state['actualTransfers']['p1Lcl'] ?? 760.44) ?>">
                  </td>
                  <td class="text-right font-mono" id="target-lcl-p2"><?= Format::currency($p2Contrib['lcl'] ?? 621.56) ?></td>
                  <td class="text-right">
                    <input type="number" step="0.5" id="actual-lcl-p2" class="transfer-input font-mono" value="<?= (float)($state['actualTransfers']['p2Lcl'] ?? 621.56) ?>">
                  </td>
                  <td class="text-right font-mono font-bold text-cyan" id="actual-total-lcl"><?= Format::currency(($state['actualTransfers']['p1Lcl'] ?? 760.44) + ($state['actualTransfers']['p2Lcl'] ?? 621.56)) ?></td>
                  <td class="text-right font-mono" id="diff-lcl">
                    <span class="badge-tag" style="background:rgba(16,185,129,0.15); color:#34d399;">0,00 €</span>
                  </td>
                </tr>
                <tr>
                  <td>
                    <span class="bank-tag rev-tag">Revolut</span> <strong>Compte Commun Revolut</strong>
                    <div style="font-size:0.75rem; color:var(--text-dim); margin-top:2px;">Vie Courante Foyer (<?= Format::currency($budgets['revolutTotal'] ?? 900) ?>)</div>
                  </td>
                  <td class="text-right font-mono" id="target-rev-p1"><?= Format::currency($p1Contrib['rev'] ?? 450) ?></td>
                  <td class="text-right">
                    <input type="number" step="0.5" id="actual-rev-p1" class="transfer-input font-mono" value="<?= (float)($state['actualTransfers']['p1Rev'] ?? 450) ?>">
                  </td>
                  <td class="text-right font-mono" id="target-rev-p2"><?= Format::currency($p2Contrib['rev'] ?? 450) ?></td>
                  <td class="text-right">
                    <input type="number" step="0.5" id="actual-rev-p2" class="transfer-input font-mono" value="<?= (float)($state['actualTransfers']['p2Rev'] ?? 450) ?>">
                  </td>
                  <td class="text-right font-mono font-bold text-violet" id="actual-total-rev"><?= Format::currency(($state['actualTransfers']['p1Rev'] ?? 450) + ($state['actualTransfers']['p2Rev'] ?? 450)) ?></td>
                  <td class="text-right font-mono" id="diff-rev">
                    <span class="badge-tag" style="background:rgba(16,185,129,0.15); color:#34d399;">0,00 €</span>
                  </td>
                </tr>
                <tr style="background:rgba(255,255,255,0.02); font-weight:700;">
                  <td><strong>TOTAL DES VIREMENTS MENSUELS</strong></td>
                  <td class="text-right font-mono" id="target-total-p1"><?= Format::currency($p1Contrib['total'] ?? 1210.44) ?></td>
                  <td class="text-right font-mono text-cyan" id="actual-sum-p1"><?= Format::currency(($state['actualTransfers']['p1Lcl'] ?? 760.44) + ($state['actualTransfers']['p1Rev'] ?? 450)) ?></td>
                  <td class="text-right font-mono" id="target-total-p2"><?= Format::currency($p2Contrib['total'] ?? 1071.56) ?></td>
                  <td class="text-right font-mono text-violet" id="actual-sum-p2"><?= Format::currency(($state['actualTransfers']['p2Lcl'] ?? 621.56) + ($state['actualTransfers']['p2Rev'] ?? 450)) ?></td>
                  <td class="text-right font-mono font-bold text-emerald" id="actual-grand-total"><?= Format::currency(($state['actualTransfers']['p1Lcl'] ?? 760.44) + ($state['actualTransfers']['p2Lcl'] ?? 621.56) + ($state['actualTransfers']['p1Rev'] ?? 450) + ($state['actualTransfers']['p2Rev'] ?? 450)) ?></td>
                  <td class="text-right font-mono font-bold" id="diff-grand-total">
                    <span class="badge-tag" style="background:rgba(16,185,129,0.15); color:#34d399;">0,00 € (Équilibré)</span>
                  </td>
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
              <div class="travaux-bar-segment segment-planned" id="dash-trv-bar-planned" style="width: <?= $travaux['pendingPlannedPct'] ?? 73.77 ?>%;" title="Prévu chantiers restant : <?= Format::currency($travaux['pendingPlannedBudget'] ?? 36885.15) ?>"></div>
              <div class="travaux-bar-segment segment-remaining" id="dash-trv-bar-remaining" style="width: <?= $travaux['marginPct'] ?? 0 ?>%;" title="Réserve / Non alloué : <?= Format::currency($travaux['marginBudget'] ?? 0) ?>"></div>
            </div>
            <div class="travaux-progress-legend">
              <span class="legend-chip"><span class="chip-dot dot-cyan"></span> <?= htmlspecialchars($p1['name']) ?> : <strong id="dash-trv-legend-axel"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?></strong></span>
              <span class="legend-chip"><span class="chip-dot dot-violet"></span> <?= htmlspecialchars($p2['name']) ?> : <strong id="dash-trv-legend-ariane"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></strong></span>
              <span class="legend-chip"><span class="chip-dot dot-amber"></span> Prévu chantiers : <strong id="dash-trv-legend-planned"><?= Format::currency($travaux['pendingPlannedBudget'] ?? 36885.15) ?></strong></span>
              <span class="legend-chip"><span class="chip-dot dot-emerald"></span> Réserve : <strong id="dash-trv-legend-rem"><?= Format::currency($travaux['marginBudget'] ?? 0) ?></strong></span>
            </div>
          </div>
        </div>
      </section>
