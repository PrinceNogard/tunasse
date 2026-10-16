<?php
/** @var array $state */
/** @var array $budgets */
$p1 = $state['profiles'][0] ?? ['name' => 'Axel', 'initials' => 'AX', 'color' => '#0ea5e9'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane', 'initials' => 'AR', 'color' => '#8b5cf6'];
?>
    <!-- APP HEADER -->
    <header class="app-header">
      <div class="brand-area">
        <div class="brand-icon">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/>
          </svg>
        </div>
        <div>
          <h1 class="brand-title">DuoBudget</h1>
          <div class="header-profiles-strip" id="header-profiles-display">
            <span class="header-couple-names"><?= htmlspecialchars($p1['name']) ?> & <?= htmlspecialchars($p2['name']) ?></span>
          </div>
        </div>
      </div>

      <nav class="nav-tabs" role="tablist">
        <button class="nav-tab active" data-tab="dashboard" id="tab-btn-dashboard" role="tab" aria-selected="true">
          <span class="tab-text">Vue d'ensemble</span>
        </button>
        <button class="nav-tab" data-tab="simulator" id="tab-btn-simulator" role="tab" aria-selected="false">
          <span class="tab-text">Simulateur Prêt</span>
        </button>
        <button class="nav-tab" data-tab="lcl" id="tab-btn-lcl" role="tab" aria-selected="false">
          <span class="tab-text">Compte LCL</span>
        </button>
        <button class="nav-tab" data-tab="revolut" id="tab-btn-revolut" role="tab" aria-selected="false">
          <span class="tab-text">Compte Revolut</span>
        </button>
        <button class="nav-tab" data-tab="travaux" id="tab-btn-travaux" role="tab" aria-selected="false">
          <span class="tab-text">Enveloppe Travaux</span>
        </button>
        <button class="nav-tab" data-tab="profiles" id="tab-btn-profiles" role="tab" aria-selected="false">
          <span class="tab-text">Profils & Clés</span>
        </button>
      </nav>

      <div class="header-actions">
        <a href="api.php?action=export_json" class="btn btn-secondary btn-sm" id="btn-export-data" title="Sauvegarder les données en JSON">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          Export
        </a>
      </div>
    </header>
