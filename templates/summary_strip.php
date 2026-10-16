<?php
/** @var array $state */
/** @var array $budgets */
/** @var array $travaux */
/** @var array $simulator */
use Tunasse\Utils\Format;
?>
    <!-- SUMMARY HERO STRIP (SERVER-RENDERED VIA PHP) -->
    <section class="summary-strip">
      <div class="summary-card stat-total">
        <div class="card-icon-wrapper blue">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
        </div>
        <div class="stat-content">
          <span class="stat-label">Budget Mensuel Total</span>
          <span class="stat-value" id="top-total-budget"><?= Format::currency($budgets['totalMonthly'] ?? 0) ?></span>
          <span class="stat-subtext" id="top-total-annual"><?= Format::currency($budgets['totalAnnual'] ?? 0) ?> / an</span>
        </div>
      </div>

      <div class="summary-card stat-lcl">
        <div class="card-icon-wrapper cyan">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-header-row">
            <span class="stat-label">LCL • Package Immo Statique</span>
            <span class="bank-tag lcl-tag">LCL</span>
          </div>
          <span class="stat-value" id="top-lcl-budget"><?= Format::currency($budgets['lclTotal'] ?? 0) ?></span>
          <span class="stat-subtext" id="top-lcl-subtext">Crédit immo, assurances & taxe</span>
        </div>
      </div>

      <div class="summary-card stat-revolut">
        <div class="card-icon-wrapper violet">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-header-row">
            <span class="stat-label">Revolut • Vie Courante</span>
            <span class="bank-tag rev-tag">Revolut</span>
          </div>
          <span class="stat-value" id="top-revolut-budget"><?= Format::currency($budgets['revolutTotal'] ?? 0) ?></span>
          <span class="stat-subtext" id="top-revolut-spent"><?= Format::currency($budgets['revolutSpent'] ?? 0) ?> dépensés ce mois</span>
        </div>
      </div>

      <div class="summary-card stat-equalizer clickable" id="card-quick-equalizer" title="Cliquez pour ouvrir le simulateur de prêt">
        <div class="card-icon-wrapper amber">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-header-row">
            <span class="stat-label">Remboursement Prêt Familial</span>
            <span class="bank-tag amber-tag" id="top-repayment-tag"><?= (int)($state['simulator']['durationYears'] ?? 15) ?> ans</span>
          </div>
          <span class="stat-value text-amber" id="top-repayment-monthly">+<?= Format::currency($simulator['monthlyPayment'] ?? 69.44) ?></span>
          <span class="stat-subtext" id="top-repayment-subtext"><?= htmlspecialchars($state['profiles'][0]['name'] ?? 'Axel') ?> rembourse <?= htmlspecialchars($state['profiles'][1]['name'] ?? 'Ariane') ?></span>
        </div>
      </div>

      <div class="summary-card stat-travaux clickable" id="card-quick-travaux" title="Cliquez pour ouvrir l'enveloppe travaux 50 000 €">
        <div class="card-icon-wrapper emerald">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m14.7 6.3 3 3-9.4 9.4H5.3v-3l9.4-9.4z"/><path d="m18 3 3 3-2 2-3-3z"/></svg>
        </div>
        <div class="stat-content">
          <div class="stat-header-row">
            <span class="stat-label">Enveloppe Travaux</span>
            <span class="bank-tag emerald-tag" id="top-travaux-tag">Reste <?= Format::currency($travaux['remainingBudget'] ?? 36885.15) ?></span>
          </div>
          <span class="stat-value text-emerald" id="top-travaux-consumed"><?= Format::currency($travaux['totalSpent'] ?? 13114.85) ?></span>
          <span class="stat-subtext" id="top-travaux-subtext">consommé sur <?= Format::currency($travaux['totalBudget'] ?? 50000) ?></span>
        </div>
      </div>
    </section>
