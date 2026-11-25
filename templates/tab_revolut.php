<?php
/** @var array $state */
/** @var array $budgets */
use Tunasse\Utils\Format;

$p1 = $state['profiles'][0] ?? ['name' => 'Axel'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane'];
$envelopes = $state['revolutEnvelopes'] ?? [];
$expenses = $state['expenses'] ?? [];
$revExpenses = array_filter($expenses, fn($e) => ($e['account'] ?? '') === 'Revolut');
$revSpent = array_sum(array_column($revExpenses, 'amount'));
$revTotal = $budgets['revolutTotal'] ?? 1155;
$revRatios = $budgets['revolutRatios'] ?? \Tunasse\Finance\BudgetEngine::calculateRevolutRatios(
    $state['profiles'] ?? [],
    $state['revolutSplit'] ?? [],
    (string)($state['splitRule'] ?? '50')
);
$p1Ratio = $revRatios['p1Ratio'];
$p2Ratio = $revRatios['p2Ratio'];
$p1Percent = $revRatios['p1Percent'];
$p2Percent = $revRatios['p2Percent'];
?>
      <!-- ================= TAB 4: COMPTE REVOLUT (VIE COURANTE) (SERVER-RENDERED VIA PHP) ================= -->
      <section class="tab-pane" id="pane-revolut">
        <div class="section-banner revolut-banner">
          <div class="banner-icon">💳</div>
          <div>
            <h2 class="banner-title">Compte Commun Revolut • Vie Courante</h2>
            <p class="banner-desc">Budget prévisionnel des charges courantes du foyer : alimentation, fluides, sorties & intendance commune.</p>
          </div>
          <div class="banner-total">
            <span class="banner-total-label">Budget Prévu / Mois</span>
            <span class="banner-total-val text-violet" id="rev-banner-total"><?= Format::currency($revTotal) ?></span>
            <span class="banner-total-annual" id="rev-banner-annual"><?= Format::currency($revTotal * 12) ?> / an</span>
          </div>
        </div>

        <!-- Revolut Split Percentage Management Card -->
        <div class="glass-card mt-6">
          <div class="card-header" style="align-items:flex-start;">
            <div>
              <div class="badge-tag rev-tag mb-2">⚖️ Clé de Répartition Revolut</div>
              <h3 class="card-title">Répartition du Compte Revolut entre Partenaires</h3>
              <p class="card-subtitle">Gérez directement le pourcentage attribué à chacun pour le budget mensuel Revolut.</p>
            </div>
            <!-- Quick Presets -->
            <div class="split-presets-group" style="display:flex; gap:8px; flex-wrap:wrap;">
              <button type="button" class="btn <?= ($p1Percent == 50 && $p2Percent == 50 && ($revRatios['mode'] ?? '') !== 'income') ? 'btn-primary' : 'btn-secondary' ?> btn-sm rev-preset-btn" data-preset="50" id="btn-rev-split-50">
                ⚖️ 50 / 50
              </button>
              <button type="button" class="btn <?= (($revRatios['mode'] ?? '') === 'income') ? 'btn-primary' : 'btn-secondary' ?> btn-sm rev-preset-btn" data-preset="income" id="btn-rev-split-income">
                📊 Au prorata des revenus
              </button>
              <button type="button" class="btn <?= ($p1Percent == 60) ? 'btn-primary' : 'btn-secondary' ?> btn-sm rev-preset-btn" data-preset="60" id="btn-rev-split-60">
                60 / 40
              </button>
              <button type="button" class="btn <?= ($p1Percent == 70) ? 'btn-primary' : 'btn-secondary' ?> btn-sm rev-preset-btn" data-preset="70" id="btn-rev-split-70">
                70 / 30
              </button>
            </div>
          </div>

          <!-- Interactive Split Controller -->
          <div class="revolut-split-controller" style="margin-top:16px; padding:16px; background:rgba(15,23,42,0.4); border-radius:var(--radius-md); border:1px solid var(--border-subtle);">
            <div class="grid-2-col" style="gap:20px; align-items:center;">
              
              <!-- Partner 1 -->
              <div class="revolut-partner-split-box" style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:rgba(14,165,233,0.06); border:1px solid rgba(14,165,233,0.25); border-radius:var(--radius-sm);">
                <div style="display:flex; align-items:center; gap:10px;">
                  <div class="avatar-sm" style="background:<?= htmlspecialchars($p1['color'] ?? '#0ea5e9') ?>; width:34px; height:34px; font-size:0.8rem; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">
                    <?= htmlspecialchars($p1['initials'] ?? 'AX') ?>
                  </div>
                  <div>
                    <strong style="color:var(--text-main); font-size:0.92rem;" id="rev-p1-name-display"><?= htmlspecialchars($p1['name'] ?? 'Axel') ?></strong>
                    <div class="font-mono text-cyan" id="rev-p1-amount-preview" style="font-size:0.82rem; font-weight:600;">
                      <?= Format::currency($revTotal * $p1Ratio) ?> / mois
                    </div>
                  </div>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                  <input type="number" min="0" max="100" step="1" id="input-rev-split-p1" class="custom-input font-mono" style="width:68px; text-align:right; font-weight:700; color:#38bdf8; padding:6px 8px;" value="<?= $p1Percent ?>">
                  <span style="font-weight:700; color:var(--text-dim);">%</span>
                </div>
              </div>

              <!-- Partner 2 -->
              <div class="revolut-partner-split-box" style="display:flex; align-items:center; justify-content:space-between; padding:12px 16px; background:rgba(139,92,246,0.06); border:1px solid rgba(139,92,246,0.25); border-radius:var(--radius-sm);">
                <div style="display:flex; align-items:center; gap:10px;">
                  <div class="avatar-sm" style="background:<?= htmlspecialchars($p2['color'] ?? '#8b5cf6') ?>; width:34px; height:34px; font-size:0.8rem; border-radius:50%; display:flex; align-items:center; justify-content:center; font-weight:700;">
                    <?= htmlspecialchars($p2['initials'] ?? 'AR') ?>
                  </div>
                  <div>
                    <strong style="color:var(--text-main); font-size:0.92rem;" id="rev-p2-name-display"><?= htmlspecialchars($p2['name'] ?? 'Ariane') ?></strong>
                    <div class="font-mono text-violet" id="rev-p2-amount-preview" style="font-size:0.82rem; font-weight:600;">
                      <?= Format::currency($revTotal * $p2Ratio) ?> / mois
                    </div>
                  </div>
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                  <input type="number" min="0" max="100" step="1" id="input-rev-split-p2" class="custom-input font-mono" style="width:68px; text-align:right; font-weight:700; color:#c084fc; padding:6px 8px;" value="<?= $p2Percent ?>">
                  <span style="font-weight:700; color:var(--text-dim);">%</span>
                </div>
              </div>

            </div>

            <!-- Slider Range -->
            <div style="margin-top:16px;">
              <input type="range" min="0" max="100" step="1" id="slider-rev-split" class="range-slider" value="<?= $p1Percent ?>" style="accent-color: #8b5cf6; margin:0;">
              <div style="display:flex; justify-content:space-between; font-size:0.75rem; color:var(--text-dim); margin-top:4px;">
                <span>0% <?= htmlspecialchars($p1['name'] ?? 'Axel') ?> / 100% <?= htmlspecialchars($p2['name'] ?? 'Ariane') ?></span>
                <span id="rev-split-indicator" style="font-weight:600; color:var(--text-main);"><?= $p1Percent ?>% / <?= $p2Percent ?>%</span>
                <span>100% <?= htmlspecialchars($p1['name'] ?? 'Axel') ?> / 0% <?= htmlspecialchars($p2['name'] ?? 'Ariane') ?></span>
              </div>
            </div>

            <!-- Visual Bar -->
            <div style="margin-top:12px; height:8px; border-radius:999px; overflow:hidden; display:flex; background:rgba(255,255,255,0.06);">
              <div id="rev-split-bar-p1" style="width:<?= $p1Percent ?>%; background:#0ea5e9; transition:width 0.15s ease;"></div>
              <div id="rev-split-bar-p2" style="width:<?= $p2Percent ?>%; background:#8b5cf6; transition:width 0.15s ease;"></div>
            </div>

          </div>
        </div>

        <!-- Revolut Planned Budget Envelopes -->
        <div class="glass-card mt-6">
          <div class="card-header">
            <div>
              <div class="badge-tag rev-tag mb-2">Prévisionnel Vie Commune</div>
              <h3 class="card-title">Enveloppes Budgétaires Mensuelles Prévues</h3>
              <p class="card-subtitle">Lignes de dépenses prévues sur le compte Revolut. Modifiez directement les montants pour ajuster l'enveloppe globale.</p>
            </div>
            <button class="btn btn-primary btn-sm" id="btn-add-revolut-cat">
              + Nouvelle enveloppe
            </button>
          </div>

          <div class="table-responsive mt-3">
            <table class="custom-table revolut-envelopes-table" id="revolut-envelopes-table">
              <thead>
                <tr>
                  <th>Ligne de Dépense Prévue</th>
                  <th class="text-right" style="width: 220px;">Budget Alloué / Mois</th>
                  <th class="text-right" id="th-revolut-p1">Part <?= htmlspecialchars($p1['name']) ?> (<?= $p1Percent ?>%)</th>
                  <th class="text-right" id="th-revolut-p2">Part <?= htmlspecialchars($p2['name']) ?> (<?= $p2Percent ?>%)</th>
                  <th class="text-center" style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody id="revolut-envelopes-tbody">
                <?php foreach ($envelopes as $env): 
                  $b = (float)($env['budget'] ?? 0);
                  $part1 = $b * $p1Ratio;
                  $part2 = $b * $p2Ratio;
                ?>
                  <tr data-id="<?= htmlspecialchars($env['id']) ?>">
                    <td>
                      <div style="display:flex; align-items:center; gap:8px;">
                        <span class="envelope-icon" style="font-size:1.15rem;"><?= htmlspecialchars($env['icon'] ?? '📁') ?></span>
                        <strong style="color:var(--text-main);"><?= htmlspecialchars($env['name'] ?? '') ?></strong>
                      </div>
                    </td>
                    <td class="text-right">
                      <div style="display:inline-flex; align-items:center; gap:4px; justify-content:flex-end;">
                        <input type="number" step="5" min="0" class="input-env-budget font-mono" data-id="<?= htmlspecialchars($env['id']) ?>" value="<?= (float)$b ?>">
                        <span class="text-dim">€</span>
                      </div>
                    </td>
                    <td class="text-right font-mono text-cyan env-half-p1"><?= Format::currency($part1) ?></td>
                    <td class="text-right font-mono text-violet env-half-p2"><?= Format::currency($part2) ?></td>
                    <td class="text-center">
                      <button class="btn-icon btn-delete-envelope" data-id="<?= htmlspecialchars($env['id']) ?>" title="Supprimer cette enveloppe">🗑️</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr class="tfoot-total-row" style="font-weight: 800; font-size: 0.95rem;">
                  <td><strong>TOTAL DU BUDGET MENSUEL REVOLUT</strong></td>
                  <td class="text-right font-mono text-violet font-bold" id="revolut-envelopes-total-display"><?= Format::currency($revTotal) ?></td>
                  <td class="text-right font-mono text-cyan" id="revolut-envelopes-p1-total"><?= Format::currency($revTotal * $p1Ratio) ?></td>
                  <td class="text-right font-mono text-violet" id="revolut-envelopes-p2-total"><?= Format::currency($revTotal * $p2Ratio) ?></td>
                  <td class="text-center">
                    <span class="badge-tag" style="background:rgba(139,92,246,0.15); color:#c084fc;">Prévu</span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </section>
