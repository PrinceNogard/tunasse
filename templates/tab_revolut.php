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
                  <th class="text-right">Part <?= htmlspecialchars($p1['name']) ?> (50%)</th>
                  <th class="text-right">Part <?= htmlspecialchars($p2['name']) ?> (50%)</th>
                  <th class="text-center" style="width: 100px;">Actions</th>
                </tr>
              </thead>
              <tbody id="revolut-envelopes-tbody">
                <?php foreach ($envelopes as $env): 
                  $b = (float)($env['budget'] ?? 0);
                  $half = $b / 2.0;
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
                    <td class="text-right font-mono text-cyan env-half-p1"><?= Format::currency($half) ?></td>
                    <td class="text-right font-mono text-violet env-half-p2"><?= Format::currency($half) ?></td>
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
                  <td class="text-right font-mono text-cyan" id="revolut-envelopes-p1-total"><?= Format::currency($revTotal / 2.0) ?></td>
                  <td class="text-right font-mono text-violet" id="revolut-envelopes-p2-total"><?= Format::currency($revTotal / 2.0) ?></td>
                  <td class="text-center">
                    <span class="badge-tag" style="background:rgba(139,92,246,0.15); color:#c084fc;">Prévu</span>
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </section>
