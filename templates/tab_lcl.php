<?php
/** @var array $state */
/** @var array $budgets */
/** @var array $simulator */
use Tunasse\Utils\Format;

$p1 = $state['profiles'][0] ?? ['name' => 'Axel', 'initials' => 'AX'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane', 'initials' => 'AR'];
$lclItems = $state['lclItems'] ?? [];

$creditItems = array_filter($lclItems, fn($it) => ($it['category'] ?? '') === 'credit');
$taxeItems = array_filter($lclItems, fn($it) => ($it['category'] ?? '') === 'taxe');
$maintItems = array_filter($lclItems, fn($it) => ($it['category'] ?? '') === 'maintenance');

$subtotalCredit = array_sum(array_column($creditItems, 'amount'));
$subtotalTaxe = array_sum(array_column($taxeItems, 'amount'));
$subtotalMaint = array_sum(array_column($maintItems, 'amount'));
$totalLcl = $budgets['lclTotal'] ?? 1382;

$p1Lcl = $budgets['p1Contrib']['lcl'] ?? 760.44;
$p2Lcl = $budgets['p2Contrib']['lcl'] ?? 621.56;
?>
      <!-- ================= TAB 3: COMPTE LCL (PACKAGE MAISON & PRÊT IMMO) (SERVER-RENDERED VIA PHP) ================= -->
      <section class="tab-pane" id="pane-lcl">
        <div class="section-banner lcl-banner">
          <div class="banner-icon">🏛️</div>
          <div>
            <h2 class="banner-title">Compte Commun LCL • Package Prêt Immo & Logement</h2>
            <p class="banner-desc">Regroupement statique complet : Package Crédit Immobilier + Assurances, Fiscalité (Taxe Foncière) & Charges fixes.</p>
          </div>
          <div class="banner-total">
            <span class="banner-total-label">Total Fixe Package / Mois</span>
            <span class="banner-total-val text-cyan" id="lcl-banner-total"><?= Format::currency($totalLcl) ?></span>
            <span class="banner-total-annual" id="lcl-banner-annual"><?= Format::currency($totalLcl * 12) ?> / an</span>
          </div>
        </div>

        <div class="grid-2-col">
          <!-- LCL Structured Packages Column -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h3 class="card-title">Le Gros Package Maison Statique</h3>
                <p class="card-subtitle">Ventilation des postes de crédit, assurances et charges foncières</p>
              </div>
              <button class="btn btn-secondary btn-sm" id="btn-add-lcl-item">
                + Ajouter une ligne
              </button>
            </div>

            <!-- PACKAGE 1: CREDIT IMMOBILIER & ASSURANCES -->
            <div class="package-section-card mb-4">
              <div class="package-card-header">
                <div class="package-title-wrap">
                  <span class="package-icon">🏠</span>
                  <div>
                    <h4 class="package-title">Package Crédit Immobilier & Assurances</h4>
                    <span class="package-sub">Prêt bancaire amortissable (15 ans) + assurances obligatoires</span>
                  </div>
                </div>
                <div class="package-subtotal-badge">
                  <span class="subtotal-val" id="subtotal-credit-pack"><?= Format::currency($subtotalCredit) ?></span>
                  <span class="subtotal-label">/ mois</span>
                </div>
              </div>

              <div class="package-items-container" id="lcl-credit-items">
                <?php foreach ($creditItems as $it): ?>
                  <div class="lcl-item-row" data-id="<?= htmlspecialchars($it['id']) ?>">
                    <div class="item-info">
                      <strong class="item-name"><?= htmlspecialchars($it['label']) ?></strong>
                      <?php if (!empty($it['notes'])): ?>
                        <span class="item-notes"><?= htmlspecialchars($it['notes']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="item-amount-action">
                      <span class="item-val font-mono"><?= Format::currency($it['amount']) ?></span>
                      <button class="btn-icon btn-edit-lcl" data-id="<?= htmlspecialchars($it['id']) ?>" title="Modifier">✏️</button>
                      <button class="btn-icon btn-delete-lcl" data-id="<?= htmlspecialchars($it['id']) ?>" title="Supprimer">🗑️</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- PACKAGE 2: TAXE FONCIERE STATIQUE -->
            <div class="package-section-card mb-4 taxe-fonciere-card">
              <div class="package-card-header">
                <div class="package-title-wrap">
                  <span class="package-icon">📋</span>
                  <div>
                    <h4 class="package-title">Fiscalité Logement • Taxe Foncière Statique</h4>
                    <span class="package-sub">Mensualisation automatique pour lisser la dépense sur l'année</span>
                  </div>
                </div>
                <div class="package-subtotal-badge">
                  <span class="subtotal-val text-amber" id="subtotal-taxe-pack"><?= Format::currency($subtotalTaxe) ?></span>
                  <span class="subtotal-label">/ mois</span>
                </div>
              </div>

              <div class="taxe-interactive-editor">
                <div class="form-group-row">
                  <div class="form-field" style="margin-bottom:0;">
                    <label for="input-tf-annual" style="font-size:0.78rem;">Montant Annuel de la Taxe (€ / an)</label>
                    <div class="slider-input-badge">
                      <input type="number" id="input-tf-annual" class="num-input" value="<?= (int)($subtotalTaxe * 12) ?>" step="10" style="width:85px;">
                      <span>€/an</span>
                    </div>
                  </div>
                  <div class="form-field" style="margin-bottom:0;">
                    <label for="input-tf-monthly" style="font-size:0.78rem;">Provision Mensuelle (€ / mois)</label>
                    <div class="slider-input-badge">
                      <input type="number" id="input-tf-monthly" class="num-input" value="<?= (int)$subtotalTaxe ?>" step="1" style="width:85px;">
                      <span>€/mois</span>
                    </div>
                  </div>
                </div>
                <p class="taxe-hint-text mt-2">
                  💡 En modifiant l'un des deux champs, l'autre se synchronise automatiquement (Total annuel ÷ 12).
                </p>
              </div>
            </div>

            <!-- PACKAGE 3: CHARGES & PREVOYANCE MAISON -->
            <div class="package-section-card">
              <div class="package-card-header">
                <div class="package-title-wrap">
                  <span class="package-icon">🔨</span>
                  <div>
                    <h4 class="package-title">Prévoyance & Entretien Logement</h4>
                    <span class="package-sub">Provision travaux, réparations ou charges de copropriété</span>
                  </div>
                </div>
                <div class="package-subtotal-badge">
                  <span class="subtotal-val" id="subtotal-maintenance-pack"><?= Format::currency($subtotalMaint) ?></span>
                  <span class="subtotal-label">/ mois</span>
                </div>
              </div>

              <div class="package-items-container" id="lcl-maintenance-items">
                <?php foreach ($maintItems as $it): ?>
                  <div class="lcl-item-row" data-id="<?= htmlspecialchars($it['id']) ?>">
                    <div class="item-info">
                      <strong class="item-name"><?= htmlspecialchars($it['label']) ?></strong>
                      <?php if (!empty($it['notes'])): ?>
                        <span class="item-notes"><?= htmlspecialchars($it['notes']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="item-amount-action">
                      <span class="item-val font-mono"><?= Format::currency($it['amount']) ?></span>
                      <button class="btn-icon btn-edit-lcl" data-id="<?= htmlspecialchars($it['id']) ?>" title="Modifier">✏️</button>
                      <button class="btn-icon btn-delete-lcl" data-id="<?= htmlspecialchars($it['id']) ?>" title="Supprimer">🗑️</button>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

          <!-- LCL Breakdown & Allocation Column -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h3 class="card-title">Répartition du Package LCL</h3>
                <p class="card-subtitle">Quote-part mensuelle à verser pour couvrir la maison statique</p>
              </div>
            </div>

            <div class="lcl-split-box">
              <div class="split-row">
                <div class="split-user">
                  <div class="avatar-sm" id="lcl-avatar-p1"><?= htmlspecialchars($p1['initials']) ?></div>
                  <div>
                    <div class="split-user-name" id="lcl-p1-name">Part mensuelle de <?= htmlspecialchars($p1['name']) ?></div>
                    <div class="split-user-sub" id="lcl-p1-sub">50% + Prêt familial compensé</div>
                  </div>
                </div>
                <div class="split-amount-badge text-cyan" id="lcl-p1-amount"><?= Format::currency($p1Lcl) ?></div>
              </div>

              <div class="split-row">
                <div class="split-user">
                  <div class="avatar-sm ariane" id="lcl-avatar-p2"><?= htmlspecialchars($p2['initials']) ?></div>
                  <div>
                    <div class="split-user-name" id="lcl-p2-name">Part mensuelle d'<?= htmlspecialchars($p2['name']) ?></div>
                    <div class="split-user-sub" id="lcl-p2-sub">50% - Prêt familial compensé</div>
                  </div>
                </div>
                <div class="split-amount-badge text-purple" id="lcl-p2-amount"><?= Format::currency($p2Lcl) ?></div>
              </div>

              <div class="package-summary-highlight">
                <div class="ps-header">
                  <strong>Synthèse du Gros Package LCL :</strong>
                  <span class="badge-tag lcl-tag" id="lcl-pack-count-tag"><?= count($lclItems) ?> postes fixes</span>
                </div>
                <div class="ps-grid mt-2">
                  <div class="ps-item">
                    <span>Crédit + Assurances :</span>
                    <strong id="ps-credit-val"><?= Format::currency($subtotalCredit) ?></strong>
                  </div>
                  <div class="ps-item">
                    <span>Taxe Foncière :</span>
                    <strong id="ps-taxe-val"><?= Format::currency($subtotalTaxe) ?></strong>
                  </div>
                  <div class="ps-item">
                    <span>Prévoyance Travaux :</span>
                    <strong id="ps-maint-val"><?= Format::currency($subtotalMaint) ?></strong>
                  </div>
                  <div class="ps-item highlight-cyan">
                    <span>Total Package LCL :</span>
                    <strong id="ps-total-val"><?= Format::currency($totalLcl) ?></strong>
                  </div>
                </div>
              </div>

              <!-- LCL Visual Category Chart -->
              <div class="chart-box mt-4">
                <h4 class="chart-box-title">Poids de chaque poste dans le LCL</h4>
                <div class="progress-bar-stack" id="lcl-progress-stack">
                  <div class="stack-segment segment-cyan" style="width: <?= round(($subtotalCredit / ($totalLcl ?: 1)) * 100, 1) ?>%;" title="Crédit & Assurances"></div>
                  <div class="stack-segment segment-amber" style="width: <?= round(($subtotalTaxe / ($totalLcl ?: 1)) * 100, 1) ?>%;" title="Taxe Foncière"></div>
                  <div class="stack-segment segment-emerald" style="width: <?= round(($subtotalMaint / ($totalLcl ?: 1)) * 100, 1) ?>%;" title="Prévoyance"></div>
                </div>
                <div class="stack-legend" id="lcl-stack-legend">
                  <span class="legend-chip"><span class="chip-dot dot-cyan"></span> Crédit : <?= Format::currency($subtotalCredit) ?></span>
                  <span class="legend-chip"><span class="chip-dot dot-amber"></span> Taxe : <?= Format::currency($subtotalTaxe) ?></span>
                  <span class="legend-chip"><span class="chip-dot dot-emerald"></span> Prévoyance : <?= Format::currency($subtotalMaint) ?></span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>
