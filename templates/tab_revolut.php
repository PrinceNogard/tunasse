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
            <p class="banner-desc">Suivi dynamique des dépenses quotidiennes : alimentation commune, fluides, sorties & vie du foyer.</p>
          </div>
          <div class="banner-total">
            <span class="banner-total-label">Budget Alloué / Mois</span>
            <span class="banner-total-val text-violet" id="rev-banner-total"><?= Format::currency($revTotal) ?></span>
            <span class="banner-total-annual" id="rev-banner-spent">Dépensé : <?= Format::currency($revSpent) ?></span>
          </div>
        </div>

        <div class="grid-2-col">
          <!-- Revolut Budget Envelopes -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h3 class="card-title">Enveloppes Budgétaires Mensuelles</h3>
                <p class="card-subtitle">Budgets alloués par catégorie pour faire tourner la maison</p>
              </div>
              <button class="btn btn-secondary btn-sm" id="btn-add-revolut-cat">
                + Modifier enveloppes
              </button>
            </div>

            <div class="envelope-list" id="revolut-envelopes-list">
              <?php foreach ($envelopes as $env): 
                $spentInCat = 0;
                foreach ($revExpenses as $e) {
                  if (($e['category'] ?? '') === ($env['name'] ?? '')) {
                    $spentInCat += (float)($e['amount'] ?? 0);
                  }
                }
                $allocated = (float)($env['allocated'] ?? 0);
                $pct = $allocated > 0 ? min(100, round(($spentInCat / $allocated) * 100)) : 0;
              ?>
                <div class="envelope-card">
                  <div class="envelope-main">
                    <span class="envelope-icon"><?= htmlspecialchars($env['icon'] ?? '📁') ?></span>
                    <div class="envelope-info">
                      <div class="envelope-name"><?= htmlspecialchars($env['name'] ?? '') ?></div>
                      <div class="envelope-sub"><?= Format::currency($spentInCat) ?> dépensés sur <?= Format::currency($allocated) ?></div>
                    </div>
                    <div class="envelope-target font-mono"><?= Format::currency($allocated) ?></div>
                  </div>
                    <div class="envelope-bar-wrap">
                      <div class="envelope-bar-fill" style="width: <?= (int)$pct ?>%;"></div>
                    </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Revolut Transactions & Tracker -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h3 class="card-title">Dépenses de Vie Réalisées</h3>
                <p class="card-subtitle">Saisie et suivi des tickets & sorties du mois</p>
              </div>
              <button class="btn btn-primary btn-sm" id="btn-add-revolut-expense">
                + Nouvelle dépense
              </button>
            </div>

            <!-- Filters -->
            <div class="filter-strip">
              <select id="filter-rev-cat" class="custom-select">
                <option value="all">Toutes catégories</option>
                <option value="Courses & Alimentation">Courses & Alimentation</option>
                <option value="Charges & Fluides">Charges & Fluides</option>
                <option value="Sorties & Restos">Sorties & Restos</option>
                <option value="Maison & Déco">Maison & Déco</option>
                <option value="Animaux / Divers">Animaux / Divers</option>
              </select>

              <select id="filter-rev-payer" class="custom-select">
                <option value="all">Tous les payeurs</option>
                <option value="<?= htmlspecialchars($p1['name']) ?>"><?= htmlspecialchars($p1['name']) ?></option>
                <option value="<?= htmlspecialchars($p2['name']) ?>"><?= htmlspecialchars($p2['name']) ?></option>
              </select>
            </div>

            <div class="table-responsive mt-3">
              <table class="custom-table" id="revolut-expenses-table">
                <thead>
                  <tr>
                    <th>Date</th>
                    <th>Titre</th>
                    <th>Catégorie</th>
                    <th>Payé avec</th>
                    <th class="text-right">Montant</th>
                    <th class="text-center">Action</th>
                  </tr>
                </thead>
                <tbody id="revolut-expenses-tbody">
                  <?php if (empty($revExpenses)): ?>
                    <tr><td colspan="6" class="text-center text-dim py-4">Aucune dépense Revolut ce mois-ci.</td></tr>
                  <?php else: ?>
                    <?php foreach ($revExpenses as $exp): ?>
                      <tr>
                        <td class="font-mono text-dim"><?= htmlspecialchars(Format::date($exp['date'] ?? '')) ?></td>
                        <td><strong><?= htmlspecialchars($exp['description'] ?? '') ?></strong></td>
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
        </div>
      </section>
