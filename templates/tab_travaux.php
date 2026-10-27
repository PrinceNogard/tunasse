<?php
/** @var array $state */
/** @var array $travaux */
use Tunasse\Utils\Format;

$p1 = $state['profiles'][0] ?? ['name' => 'Axel', 'initials' => 'AX'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane', 'initials' => 'AR'];
$travauxItems = $state['travauxItems'] ?? [];
$lots = $travaux['lots'] ?? [];

$unreimbursedCount = count(array_filter($travauxItems, fn($it) => empty($it['reimbursed'])));
$reimbursedCount = count(array_filter($travauxItems, fn($it) => !empty($it['reimbursed'])));
?>
      <!-- ================= TAB: ENVELOPPE TRAVAUX & AVANCES (50 000 €) (SERVER-RENDERED VIA PHP) ================= -->
      <section class="tab-pane" id="pane-travaux">
        <!-- Hero Header -->
        <div class="travaux-hero-banner glass-card">
          <div class="travaux-hero-content">
            <div class="travaux-hero-left">
              <div class="badge-emerald mb-2">Financement & Rénovation Maison</div>
              <h2 class="card-title" style="font-size:1.3rem;">Enveloppe Travaux Rénovation</h2>
              <p class="card-subtitle">
                Suivi du compte travaux commun de <strong><?= Format::currency($travaux['totalBudget'] ?? 50000) ?></strong>, contrôle des dépenses engagées et rééquilibrage automatisé des avances personnelles entre <strong><?= htmlspecialchars($p1['name']) ?></strong> et <strong><?= htmlspecialchars($p2['name']) ?></strong>.
              </p>
            </div>
            <div class="travaux-hero-actions">
              <button class="btn btn-primary" id="btn-open-travaux-modal">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                + Ajouter une dépense travaux
              </button>
              <button class="btn btn-secondary btn-sm" id="btn-edit-travaux-budget" title="Modifier le montant total de l'enveloppe (actuellement 50 000 €)">
                Ajuster l'enveloppe
              </button>
            </div>
          </div>

          <!-- 4 KPI Cards -->
          <div class="travaux-kpi-grid mt-6">
            <div class="kpi-card">
              <div class="kpi-header">
                <span class="kpi-label">Solde Initial Travaux</span>
                <span class="bank-tag lcl-tag">Fonds Dédié</span>
              </div>
              <div class="kpi-value font-mono" id="trv-kpi-total-budget"><?= Format::currency($travaux['totalBudget'] ?? 50000) ?></div>
              <span class="kpi-subtext">Financement global prévu</span>
            </div>

            <div class="kpi-card">
              <div class="kpi-header">
                <span class="kpi-label">Total Consommé</span>
                <span class="bank-tag amber-tag" id="trv-kpi-consumed-badge"><?= $travaux['spentPct'] ?? 26.2 ?>% engagé</span>
              </div>
              <div class="kpi-value font-mono text-amber" id="trv-kpi-consumed"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></div>
              <span class="kpi-subtext" id="trv-kpi-items-count">Sur <?= count($travauxItems) ?> factures / acomptes</span>
            </div>

            <div class="kpi-card">
              <div class="kpi-header">
                <span class="kpi-label">Solde Restant Disponible</span>
                <span class="bank-tag emerald-tag" id="trv-kpi-remaining-badge"><?= $travaux['remainingPct'] ?? 73.8 ?>% dispo</span>
              </div>
              <div class="kpi-value font-mono text-emerald" id="trv-kpi-remaining"><?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></div>
              <span class="kpi-subtext">Disponible pour la suite des chantiers</span>
            </div>

            <div class="kpi-card">
              <div class="kpi-header">
                <span class="kpi-label">Balance des Avances</span>
                <span class="bank-tag rev-tag">Avances Perso</span>
              </div>
              <div class="kpi-value font-mono text-violet" id="trv-kpi-delta">+<?= Format::currency($travaux['delta'] ?? 2510.85) ?></div>
              <span class="kpi-subtext" id="trv-kpi-delta-sub"><?= htmlspecialchars($travaux['whoAdvancedMore'] ?? 'Ariane') ?> a avancé plus qu'<?= htmlspecialchars($p1['name']) ?></span>
            </div>
          </div>

          <!-- Visual Multi-segment Progress Bar -->
          <div class="travaux-visual-box mt-6">
            <div class="visual-box-header">
              <span class="visual-box-title">Progression de l'Enveloppe (<?= Format::currency($travaux['totalBudget'] ?? 50000) ?>)</span>
              <span class="visual-box-status" id="trv-visual-status"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?> consommés • <?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?> disponibles</span>
            </div>

            <div class="travaux-progress-bar-large">
              <div class="travaux-bar-segment segment-axel" id="trv-bar-axel" style="width: <?= $travaux['axelPctOfBudget'] ?? 10.6 ?>%;" title="Avance <?= htmlspecialchars($p1['name']) ?> : <?= Format::currency($travaux['totalAxel'] ?? 5302) ?> (<?= $travaux['axelPctOfSpent'] ?? 40.4 ?>% du consommé)"></div>
              <div class="travaux-bar-segment segment-ariane" id="trv-bar-ariane" style="width: <?= $travaux['arianePctOfBudget'] ?? 15.63 ?>%;" title="Avance <?= htmlspecialchars($p2['name']) ?> : <?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?> (<?= $travaux['arianePctOfSpent'] ?? 59.6 ?>% du consommé)"></div>
              <div class="travaux-bar-segment segment-remaining" id="trv-bar-remaining" style="width: <?= $travaux['remainingPct'] ?? 73.77 ?>%;" title="Solde restant : <?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?> (<?= $travaux['remainingPct'] ?? 73.8 ?>%)"></div>
            </div>

            <div class="travaux-visual-legend">
              <div class="legend-col">
                <div class="legend-dot-label">
                  <span class="chip-dot dot-cyan"></span>
                  <strong>Avancé par <?= htmlspecialchars($p1['name']) ?></strong>
                </div>
                <div class="legend-val text-cyan font-mono" id="trv-legend-axel-val"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?></div>
                <span class="legend-pct" id="trv-legend-axel-pct"><?= $travaux['axelPctOfSpent'] ?? 40.43 ?>% du consommé • <?= $travaux['axelPctOfBudget'] ?? 10.6 ?>% du budget</span>
              </div>

              <div class="legend-col">
                <div class="legend-dot-label">
                  <span class="chip-dot dot-violet"></span>
                  <strong>Avancé par <?= htmlspecialchars($p2['name']) ?></strong>
                </div>
                <div class="legend-val text-violet font-mono" id="trv-legend-ariane-val"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></div>
                <span class="legend-pct" id="trv-legend-ariane-pct"><?= $travaux['arianePctOfSpent'] ?? 59.57 ?>% du consommé • <?= $travaux['arianePctOfBudget'] ?? 15.63 ?>% du budget</span>
              </div>

              <div class="legend-col">
                <div class="legend-dot-label">
                  <span class="chip-dot dot-emerald"></span>
                  <strong>Reste Disponible</strong>
                </div>
                <div class="legend-val text-emerald font-mono" id="trv-legend-rem-val"><?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></div>
                <span class="legend-pct" id="trv-legend-rem-pct"><?= $travaux['remainingPct'] ?? 73.77 ?>% du budget restant</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Dual Equalization & Settlement Strategy Cards -->
        <div class="grid-2-col mt-6">
          <!-- Card 1: Bank / Joint Travaux Fund Reimbursement -->
          <div class="glass-card settlement-card">
            <div class="settlement-header">
              <div class="settlement-icon-wrap emerald">🏦</div>
              <div>
                <h3 class="card-title">1. Remboursement par le Compte Travaux (<?= Format::currency($travaux['totalBudget'] ?? 50000) ?>)</h3>
                <p class="card-subtitle">Option standard : remboursement des avances via déblocage du prêt travaux</p>
              </div>
            </div>

            <div class="settlement-body mt-4">
              <p class="settlement-explainer">
                Comme ces factures ont été payées avec vos cartes personnelles, le compte travaux dédié (<?= Format::currency($travaux['totalBudget'] ?? 50000) ?>) doit reverser à chacun le montant exact avancé :
              </p>

              <div class="settlement-pills-row">
                <div class="settlement-pill pill-axel">
                  <span class="pill-title">À rembourser à <?= htmlspecialchars($p1['name']) ?> :</span>
                  <strong class="pill-amount text-cyan font-mono" id="trv-reimburse-axel"><?= Format::currency($travaux['reimburseAxel'] ?? 5302) ?></strong>
                  <span class="pill-subtext" id="trv-reimburse-axel-status">Statut : <?= ($travaux['reimburseAxel'] ?? 0) > 0 ? 'Non remboursé' : 'Soldé' ?></span>
                </div>
                <div class="settlement-pill pill-ariane">
                  <span class="pill-title">À rembourser à <?= htmlspecialchars($p2['name']) ?> :</span>
                  <strong class="pill-amount text-violet font-mono" id="trv-reimburse-ariane"><?= Format::currency($travaux['reimburseAriane'] ?? 7812.85) ?></strong>
                  <span class="pill-subtext" id="trv-reimburse-ariane-status">Statut : <?= ($travaux['reimburseAriane'] ?? 0) > 0 ? 'Non remboursé' : 'Soldé' ?></span>
                </div>
              </div>

              <div class="settlement-summary-box mt-3">
                <div class="summary-line">
                  <span>Total à débloquer / virer aux conjoints :</span>
                  <strong class="text-amber font-mono" id="trv-reimburse-total"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></strong>
                </div>
                <div class="summary-line">
                  <span>Solde net du compte travaux après remboursement :</span>
                  <strong class="text-emerald font-mono" id="trv-reimburse-after-rem"><?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></strong>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Direct Partner-to-Partner Settlement -->
          <div class="glass-card settlement-card">
            <div class="settlement-header">
              <div class="settlement-icon-wrap violet">⚖️</div>
              <div>
                <h3 class="card-title">2. Rééquilibrage Direct Entre <?= htmlspecialchars($p1['name']) ?> & <?= htmlspecialchars($p2['name']) ?></h3>
                <p class="card-subtitle">Alternative : si les dépenses restent à votre charge personnelle (50/50)</p>
              </div>
            </div>

            <div class="settlement-body mt-4">
              <p class="settlement-explainer">
                Si vous ne passez pas par le compte travaux et souhaitez équilibrer vos comptes personnels à égalité stricte (50/50) :
              </p>

              <div class="settlement-equalizer-box">
                <div class="eq-row">
                  <span>Part théorique de chacun (50% de <?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?>) :</span>
                  <strong class="font-mono" id="trv-eq-target-50"><?= Format::currency($travaux['halfTheoretical'] ?? 6557.43) ?></strong>
                </div>
                <div class="eq-row">
                  <span><?= htmlspecialchars($p1['name']) ?> a avancé :</span>
                  <span class="font-mono text-cyan" id="trv-eq-paid-axel"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?> <small class="text-rose">(-<?= Format::currency(abs($travaux['axelDiff50'] ?? 1255.43)) ?>)</small></span>
                </div>
                <div class="eq-row">
                  <span><?= htmlspecialchars($p2['name']) ?> a avancé :</span>
                  <span class="font-mono text-violet" id="trv-eq-paid-ariane"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?> <small class="text-emerald">(+<?= Format::currency(abs($travaux['arianeDiff50'] ?? 1255.42)) ?>)</small></span>
                </div>
                <div class="eq-verdict-box">
                  <div class="eq-verdict-icon">👉</div>
                  <div class="eq-verdict-text" id="trv-eq-verdict-text">
                    <strong><?= htmlspecialchars($travaux['verdict'] ?? 'Axel doit virer 1 255,43 € à Ariane pour atteindre une égalité parfaite.') ?></strong>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ================= SUIVI BUDGÉTAIRE : LOTS DE TRAVAUX ================= -->
        <div class="glass-card mt-6" id="section-travaux-lots">
          <div class="card-header">
            <div>
              <div class="badge-emerald mb-2">📋 Suivi Prévisionnel vs Réalisé</div>
              <h2 class="card-title">Tableau de Suivi Budgétaire : Budget de Base vs Dépenses Réelles</h2>
              <p class="card-subtitle">
                Pilotage par lot comparant le fonds dédié global (<?= Format::currency($travaux['totalBudget'] ?? 50000) ?>), la somme des budgets prévus alloués aux chantiers, les dépenses réelles et les soldes restants.
              </p>
            </div>
            <div class="table-actions-strip">
              <button class="btn btn-secondary btn-sm" id="btn-add-travaux-lot">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                + Nouveau Lot de Travaux
              </button>
              <a href="api.php?action=export_travaux_csv" class="btn btn-secondary btn-sm" id="btn-export-travaux-csv" title="Télécharger le tableau complet au format CSV / Excel">
                📥 Export CSV
              </a>
              <button class="btn btn-secondary btn-sm" id="btn-print-travaux" title="Imprimer ou enregistrer en PDF la synthèse des travaux">
                🖨️ Imprimer
              </button>
            </div>
          </div>

          <!-- Quick Indicators Strip -->
          <div class="lots-kpi-strip mt-3 mb-4">
            <div class="lots-kpi-item">
              <span class="lots-kpi-label">Budget de Base Total :</span>
              <strong class="lots-kpi-val text-cyan font-mono" id="lots-kpi-total-budget"><?= Format::currency($travaux['totalAllocatedLots'] ?? $travaux['totalBudget'] ?? 50000) ?></strong>
            </div>
            <div class="lots-kpi-item">
              <span class="lots-kpi-label">Total Dépensé Noté :</span>
              <strong class="lots-kpi-val text-amber font-mono" id="lots-kpi-total-spent"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></strong>
              <small class="lots-kpi-pct" id="lots-kpi-total-spent-pct">(<?= $travaux['lotsSpentPct'] ?? $travaux['spentPct'] ?? 26.2 ?>%)</small>
            </div>
            <div class="lots-kpi-item">
              <span class="lots-kpi-label">Reste Disponible :</span>
              <strong class="lots-kpi-val text-emerald font-mono" id="lots-kpi-total-remaining"><?= Format::currency($travaux['lotsRemaining'] ?? $travaux['remainingBudget'] ?? 36885.15) ?></strong>
              <small class="lots-kpi-pct" id="lots-kpi-total-rem-pct">(<?= $travaux['lotsRemainingPct'] ?? $travaux['remainingPct'] ?? 73.8 ?>%)</small>
            </div>
            <div class="lots-kpi-item">
              <span class="lots-kpi-label">Avances <?= htmlspecialchars($p1['name']) ?> / <?= htmlspecialchars($p2['name']) ?> :</span>
              <span class="font-mono" style="font-size:0.86rem;"><strong class="text-cyan" id="lots-kpi-axel"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?></strong> / <strong class="text-violet" id="lots-kpi-ariane"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></strong></span>
            </div>
          </div>

          <div class="table-responsive">
            <table class="custom-table travaux-lots-table" id="travaux-lots-table">
              <thead>
                <tr>
                  <th>Lot / Chantier</th>
                  <th class="text-center">Statut</th>
                  <th class="text-right">Budget de Base</th>
                  <th class="text-right">Dépensé Réel</th>
                  <th class="text-right">Avance <?= htmlspecialchars($p1['name']) ?></th>
                  <th class="text-right">Avance <?= htmlspecialchars($p2['name']) ?></th>
                  <th class="text-right">Écart / Reste</th>
                  <th style="min-width: 140px;">Consommation</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody id="travaux-lots-tbody">
                <?php foreach ($lots as $lot): ?>
                  <tr data-lot-id="<?= htmlspecialchars($lot['id']) ?>">
                    <td>
                      <div class="lot-cell-name">
                        <span class="lot-icon"><?= htmlspecialchars($lot['icon'] ?? '🔨') ?></span>
                        <div>
                          <strong><?= htmlspecialchars($lot['name']) ?></strong>
                          <?php if (!empty($lot['notes'])): ?>
                            <div class="lot-subtext"><?= htmlspecialchars($lot['notes']) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </td>
                    <td class="text-center">
                      <span class="badge-subtle"><?= htmlspecialchars($lot['status'] ?? 'ongoing') ?></span>
                    </td>
                    <td class="text-right">
                      <div style="display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        <input type="number" step="100" min="0" class="input-lot-budget font-mono" data-id="<?= htmlspecialchars($lot['id']) ?>" value="<?= (float)$lot['budget'] ?>" aria-label="Budget de base pour <?= htmlspecialchars($lot['name']) ?>">
                        <span class="text-dim">€</span>
                      </div>
                    </td>
                    <td class="text-right font-mono font-bold text-amber lot-spent-cell"><?= Format::currency($lot['spent']) ?></td>
                    <td class="text-right font-mono text-cyan"><?= Format::currency($lot['axel']) ?></td>
                    <td class="text-right font-mono text-violet"><?= Format::currency($lot['ariane']) ?></td>
                    <td class="text-right font-mono lot-rem-cell <?= $lot['remaining'] >= 0 ? 'text-emerald' : 'text-rose' ?>">
                      <?= ($lot['remaining'] >= 0 ? '+' : '') . Format::currency($lot['remaining']) ?>
                    </td>
                    <td class="lot-progress-cell">
                      <div class="tfoot-progress-wrap">
                        <div class="tfoot-progress-bar">
                          <div class="tfoot-progress-fill lot-progress-fill" style="width: <?= min(100, (float)$lot['pct']) ?>%;"></div>
                        </div>
                        <span class="tfoot-progress-label lot-progress-label font-mono <?= ($lot['isOverBudget'] ?? false) ? 'text-rose' : '' ?>"><?= $lot['pct'] ?>%</span>
                      </div>
                    </td>
                    <td class="text-center">
                      <button class="btn-icon btn-edit-lot" data-id="<?= htmlspecialchars($lot['id']) ?>" title="Modifier">✏️</button>
                      <button class="btn-icon btn-delete-lot" data-id="<?= htmlspecialchars($lot['id']) ?>" title="Supprimer">🗑️</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot id="travaux-lots-tfoot" class="travaux-lots-tfoot">
                <tr class="tfoot-lots-total">
                  <td colspan="2">
                    <strong>TOTAL DU BUDGET TRAVAUX</strong>
                  </td>
                  <td class="text-right font-mono" style="font-weight:800;" id="tfoot-lots-budget"><?= Format::currency($travaux['totalAllocatedLots'] ?? $travaux['totalBudget'] ?? 50000) ?></td>
                  <td class="text-right font-mono text-amber" style="font-weight:800;" id="tfoot-lots-spent"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></td>
                  <td class="text-right font-mono text-cyan" id="tfoot-lots-axel"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?></td>
                  <td class="text-right font-mono text-violet" id="tfoot-lots-ariane"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></td>
                  <td class="text-right font-mono <?= ($travaux['lotsRemaining'] ?? $travaux['remainingBudget'] ?? 0) >= 0 ? 'text-emerald' : 'text-rose' ?>" style="font-weight:800;" id="tfoot-lots-remaining"><?= (($travaux['lotsRemaining'] ?? $travaux['remainingBudget'] ?? 0) >= 0 ? '+' : '') . Format::currency($travaux['lotsRemaining'] ?? $travaux['remainingBudget'] ?? 36885.15) ?></td>
                  <td>
                    <div class="tfoot-progress-wrap">
                      <div class="tfoot-progress-fill" id="tfoot-lots-progress-fill" style="width: <?= min(100, (float)($travaux['lotsSpentPct'] ?? $travaux['spentPct'] ?? 26.2)) ?>%;"></div>
                    </div>
                    <span class="tfoot-progress-label font-mono" id="tfoot-lots-progress-pct"><?= $travaux['lotsSpentPct'] ?? $travaux['spentPct'] ?? 26.2 ?>%</span>
                  </td>
                  <td class="text-center" id="tfoot-lots-status-col">
                    <span class="badge-tag" id="tfoot-lots-status-badge" style="background:rgba(16,185,129,0.15); color:#34d399;">Solde sain</span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>

        <!-- The Spreadsheet / Detailed Table -->
        <div class="glass-card mt-6">
          <div class="card-header">
            <div>
              <h2 class="card-title">Détail des Dépenses & Avances Travaux</h2>
              <p class="card-subtitle">Tableau de suivi des postes de rénovation, saisies manuelles et statuts de remboursement</p>
            </div>
            <div class="table-actions-strip">
              <div class="table-search-wrap">
                <input type="text" id="travaux-search-input" class="table-search-input" placeholder="🔍 Rechercher (Toiture, VMC...)">
              </div>
              <div class="status-filter-pills" role="radiogroup">
                <button type="button" class="filter-pill active" data-filter="all" id="filter-trv-all">Tous (<span id="count-trv-all"><?= count($travauxItems) ?></span>)</button>
                <button type="button" class="filter-pill" data-filter="unreimbursed" id="filter-trv-unreimbursed">Non remboursés (<span id="count-trv-unreimbursed"><?= (int)$unreimbursedCount ?></span>)</button>
                <button type="button" class="filter-pill" data-filter="reimbursed" id="filter-trv-reimbursed">Remboursés (<span id="count-trv-reimbursed"><?= (int)$reimbursedCount ?></span>)</button>
              </div>
              <button class="btn btn-primary btn-sm" id="btn-add-travaux-row">
                + Ajouter une ligne
              </button>
            </div>
          </div>

          <div class="table-responsive mt-3">
            <table class="custom-table travaux-table" id="travaux-detail-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Raison / Poste de Dépense</th>
                  <th class="text-right">Dépense <?= htmlspecialchars($p1['name']) ?></th>
                  <th class="text-right">Dépense <?= htmlspecialchars($p2['name']) ?></th>
                  <th class="text-right">Total Dépense</th>
                  <th class="text-center">Remboursé ?</th>
                  <th class="text-center">Action</th>
                </tr>
              </thead>
              <tbody id="travaux-tbody">
                <?php
                // Build hierarchical structure: main parent tasks first, followed immediately by their sub-tasks
                $parentItems = [];
                $childrenByParent = [];
                foreach ($travauxItems as $it) {
                    if (!empty($it['parentId'])) {
                        $childrenByParent[$it['parentId']][] = $it;
                    } else {
                        $parentItems[] = $it;
                    }
                }
                $orderedItems = [];
                foreach ($parentItems as $parent) {
                    $orderedItems[] = ['item' => $parent, 'isChild' => false];
                    if (isset($childrenByParent[$parent['id']])) {
                        foreach ($childrenByParent[$parent['id']] as $child) {
                            $orderedItems[] = ['item' => $child, 'isChild' => true];
                        }
                        unset($childrenByParent[$parent['id']]);
                    }
                }
                foreach ($childrenByParent as $orphans) {
                    foreach ($orphans as $orphan) {
                        $orderedItems[] = ['item' => $orphan, 'isChild' => true];
                    }
                }

                foreach ($orderedItems as $entry): 
                  $it = $entry['item'];
                  $isChild = $entry['isChild'];
                  $itemTotal = (float)($it['amountAxel'] ?? 0) + (float)($it['amountAriane'] ?? 0);
                  $isReimbursed = !empty($it['reimbursed']);
                ?>
                  <tr data-id="<?= htmlspecialchars($it['id']) ?>" class="<?= $isReimbursed ? 'row-reimbursed' : '' ?> <?= $isChild ? 'row-subtask' : 'row-parent-task' ?>">
                    <td class="font-mono text-dim"><?= htmlspecialchars(Format::date($it['date'] ?? '')) ?></td>
                    <td>
                      <?php if ($isChild): ?>
                        <div class="subtask-indent" style="display:flex; align-items:center; gap:6px; padding-left: 18px;">
                          <span class="subtask-branch" style="color:var(--text-dim); font-size:1.1rem; line-height:1;">↳</span>
                          <strong><?= htmlspecialchars($it['reason'] ?? '') ?></strong>
                          <span class="badge-tag" style="background:rgba(148,163,184,0.12); color:#94a3b8; font-size:0.68rem; padding:1px 6px;">Sous-tâche</span>
                          <?php if (!empty($it['lotId'])): ?>
                            <span class="badge-subtle ml-1"><?= htmlspecialchars($it['lotId']) ?></span>
                          <?php endif; ?>
                        </div>
                      <?php else: ?>
                        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                          <strong><?= htmlspecialchars($it['reason'] ?? '') ?></strong>
                          <?php if (!empty($it['lotId'])): ?>
                            <span class="badge-subtle ml-1"><?= htmlspecialchars($it['lotId']) ?></span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($it['notes'])): ?>
                        <div style="font-size:0.75rem; color:var(--text-dim); margin-top:2px; <?= $isChild ? 'padding-left: 28px;' : '' ?>"><?= htmlspecialchars($it['notes']) ?></div>
                      <?php endif; ?>
                    </td>
                    <td class="text-right font-mono text-cyan"><?= (float)($it['amountAxel'] ?? 0) > 0 ? Format::currency($it['amountAxel']) : '—' ?></td>
                    <td class="text-right font-mono text-violet"><?= (float)($it['amountAriane'] ?? 0) > 0 ? Format::currency($it['amountAriane']) : '—' ?></td>
                    <td class="text-right font-mono font-bold text-amber"><?= Format::currency($itemTotal) ?></td>
                    <td class="text-center">
                      <button type="button" class="btn-toggle-reimbursed badge-pill <?= $isReimbursed ? 'reimbursed' : 'pending' ?>" data-id="<?= htmlspecialchars($it['id']) ?>">
                        <?= $isReimbursed ? '✓ Remboursé' : 'Non' ?>
                      </button>
                    </td>
                    <td class="text-center">
                      <?php if (!$isChild): ?>
                        <button class="btn-icon btn-add-subtask" data-parent-id="<?= htmlspecialchars($it['id']) ?>" title="Ajouter une sous-tâche sous cette ligne">➕</button>
                      <?php endif; ?>
                      <button class="btn-icon btn-edit-travaux" data-id="<?= htmlspecialchars($it['id']) ?>" title="Modifier">✏️</button>
                      <button class="btn-icon btn-delete-travaux" data-id="<?= htmlspecialchars($it['id']) ?>" title="Supprimer">🗑️</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot id="travaux-tfoot" class="travaux-tfoot">
                <tr class="tfoot-total-row">
                  <td colspan="2">
                    <strong>Total Dépensé (Consommé)</strong>
                  </td>
                  <td class="text-right font-mono text-cyan" id="tfoot-axel-total"><?= Format::currency($travaux['totalAxel'] ?? 5302) ?></td>
                  <td class="text-right font-mono text-violet" id="tfoot-ariane-total"><?= Format::currency($travaux['totalAriane'] ?? 7812.85) ?></td>
                  <td class="text-right font-mono text-amber" id="tfoot-consumed-total"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></td>
                  <td colspan="2" class="text-center">
                    <span class="badge-subtle" id="tfoot-status-summary"><?= (int)$unreimbursedCount ?> non remboursé(s)</span>
                  </td>
                </tr>
                <tr class="tfoot-solde-row">
                  <td colspan="4">
                    <span class="text-dim">Solde Commun Travaux Initial : <strong id="tfoot-initial-display"><?= Format::currency($travaux['totalBudget'] ?? 50000) ?></strong> &nbsp;|&nbsp; Consommé : <strong id="tfoot-consumed-display"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></strong></span>
                  </td>
                  <td colspan="3" class="text-right">
                    <span class="reste-label">Reste Disponible sur l'enveloppe :</span>
                    <strong class="reste-amount text-emerald font-mono" id="tfoot-remaining-total"><?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></strong>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </section>
