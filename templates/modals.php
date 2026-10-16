<?php
/** @var array $state */
$p1 = $state['profiles'][0] ?? ['name' => 'Axel'];
$p2 = $state['profiles'][1] ?? ['name' => 'Ariane'];
$lots = $state['travauxLots'] ?? [];
?>
    <!-- MODAL: ADD / EDIT EXPENSE -->
    <div class="modal-backdrop" id="expense-modal" style="display: none;">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title" id="expense-modal-title">Ajouter une dépense</h3>
          <button class="modal-close-btn" id="btn-close-expense-modal">&times;</button>
        </div>
        <form id="expense-form">
          <input type="hidden" id="modal-expense-id">
          
          <div class="form-field">
            <label for="modal-expense-desc">Description / Commerçant</label>
            <input type="text" id="modal-expense-desc" class="custom-input" placeholder="ex: Courses Carrefour, Castorama..." required>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-expense-amount">Montant (€)</label>
              <input type="number" step="0.01" id="modal-expense-amount" class="custom-input" placeholder="0.00" required>
            </div>
            <div class="form-field">
              <label for="modal-expense-date">Date</label>
              <input type="date" id="modal-expense-date" class="custom-input" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-expense-account">Compte concerné</label>
              <select id="modal-expense-account" class="custom-select">
                <option value="Revolut">Compte Revolut (Vie courante)</option>
                <option value="LCL">Compte LCL (Maison statique)</option>
              </select>
            </div>
            <div class="form-field">
              <label for="modal-expense-cat">Catégorie</label>
              <select id="modal-expense-cat" class="custom-select">
                <option value="Courses & Alimentation">Courses & Alimentation</option>
                <option value="Charges & Fluides">Charges & Fluides</option>
                <option value="Sorties & Restos">Sorties & Restos</option>
                <option value="Maison & Déco">Maison & Déco</option>
                <option value="Prêt & Patrimoine">Prêt & Patrimoine</option>
                <option value="Animaux / Divers">Animaux / Divers</option>
              </select>
            </div>
          </div>

          <div class="form-field">
            <label for="modal-expense-payer">Payé par</label>
            <select id="modal-expense-payer" class="custom-select">
              <option value="<?= htmlspecialchars($p1['name']) ?>"><?= htmlspecialchars($p1['name']) ?></option>
              <option value="<?= htmlspecialchars($p2['name']) ?>"><?= htmlspecialchars($p2['name']) ?></option>
            </select>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" id="btn-cancel-modal">Annuler</button>
            <button type="submit" class="btn btn-primary" id="btn-save-expense">Enregistrer la dépense</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: ADD / EDIT LCL STATIC CHARGE -->
    <div class="modal-backdrop" id="lcl-item-modal" style="display: none;">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title" id="lcl-item-modal-title">Modifier / Ajouter une charge LCL</h3>
          <button class="modal-close-btn" id="btn-close-lcl-modal">&times;</button>
        </div>
        <form id="lcl-item-form">
          <input type="hidden" id="modal-lcl-id">
          
          <div class="form-field">
            <label for="modal-lcl-label">Intitulé du poste</label>
            <input type="text" id="modal-lcl-label" class="custom-input" placeholder="ex: Assurance Emprunteur LCL..." required>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-lcl-amount">Montant Mensuel (€)</label>
              <input type="number" step="0.01" id="modal-lcl-amount" class="custom-input" placeholder="0.00" required>
            </div>
            <div class="form-field">
              <label for="modal-lcl-category">Package d'appartenance</label>
              <select id="modal-lcl-category" class="custom-select">
                <option value="credit">Package Crédit Immobilier & Assurances</option>
                <option value="taxe">Fiscalité Logement (Taxe Foncière)</option>
                <option value="maintenance">Prévoyance & Entretien Logement</option>
              </select>
            </div>
          </div>

          <div class="form-field">
            <label for="modal-lcl-notes">Notes / Détail contrat</label>
            <input type="text" id="modal-lcl-notes" class="custom-input" placeholder="ex: Prélèvement le 5 du mois">
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" id="btn-cancel-lcl-modal">Annuler</button>
            <button type="submit" class="btn btn-primary" id="btn-save-lcl-item">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: ADD / EDIT TRAVAUX EXPENSE -->
    <div class="modal-backdrop" id="travaux-modal" style="display: none;">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title" id="travaux-modal-title">Ajouter une dépense travaux</h3>
          <button class="modal-close-btn" id="btn-close-travaux-modal">&times;</button>
        </div>
        <form id="travaux-form">
          <input type="hidden" id="modal-trv-id">

          <div class="form-field">
            <label for="modal-trv-reason">Raison / Intitulé du poste de travaux</label>
            <input type="text" id="modal-trv-reason" class="custom-input" placeholder="ex: Toiture Roman Avance, acompte vmc..." required>
          </div>

          <div class="form-field">
            <label for="modal-trv-lot">Lot de travaux rattaché</label>
            <select id="modal-trv-lot" class="custom-select">
              <option value="">(Aucun lot rattaché)</option>
              <?php foreach ($lots as $lot): ?>
                <option value="<?= htmlspecialchars($lot['name']) ?>"><?= htmlspecialchars($lot['icon'] ?? '🔨') ?> <?= htmlspecialchars($lot['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-trv-date">Date de l'opération</label>
              <input type="date" id="modal-trv-date" class="custom-input" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-field">
              <label for="modal-trv-reimbursed">Statut remboursement</label>
              <select id="modal-trv-reimbursed" class="custom-select">
                <option value="false">Non remboursé (En attente)</option>
                <option value="true">Remboursé par le compte travaux</option>
              </select>
            </div>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-trv-axel" id="modal-trv-axel-label">Avance <?= htmlspecialchars($p1['name']) ?> (€)</label>
              <input type="number" step="0.01" id="modal-trv-axel" class="custom-input" placeholder="0.00" value="0.00">
            </div>
            <div class="form-field">
              <label for="modal-trv-ariane" id="modal-trv-ariane-label">Avance <?= htmlspecialchars($p2['name']) ?> (€)</label>
              <input type="number" step="0.01" id="modal-trv-ariane" class="custom-input" placeholder="0.00" value="0.00">
            </div>
          </div>

          <!-- Quick split helpers -->
          <div class="quick-split-bar mb-3">
            <span class="quick-split-label">Répartition rapide :</span>
            <div class="quick-split-btns">
              <button type="button" class="btn btn-secondary btn-sm" id="btn-split-50-trv" title="Diviser le montant total en parts égales 50/50">⚖️ 50 / 50</button>
              <button type="button" class="btn btn-secondary btn-sm" id="btn-split-100-axel" title="100% payé par <?= htmlspecialchars($p1['name']) ?>">100% <?= htmlspecialchars($p1['name']) ?></button>
              <button type="button" class="btn btn-secondary btn-sm" id="btn-split-100-ariane" title="100% payé par <?= htmlspecialchars($p2['name']) ?>">100% <?= htmlspecialchars($p2['name']) ?></button>
            </div>
          </div>

          <div class="form-field">
            <label for="modal-trv-notes">Notes / Détails devis (optionnel)</label>
            <input type="text" id="modal-trv-notes" class="custom-input" placeholder="ex: Devis signé Roman Toiture, virement Bourso...">
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" id="btn-cancel-travaux-modal">Annuler</button>
            <button type="submit" class="btn btn-primary" id="btn-save-travaux-item">Enregistrer</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: EDIT TRAVAUX ENVELOPE BUDGET -->
    <div class="modal-backdrop" id="travaux-budget-modal" style="display: none;">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title">Ajuster l'Enveloppe Globale Travaux</h3>
          <button class="modal-close-btn" id="btn-close-budget-modal">&times;</button>
        </div>
        <form id="travaux-budget-form">
          <div class="form-field">
            <label for="modal-budget-val">Budget Total Financement Travaux (€)</label>
            <input type="number" step="100" id="modal-budget-val" class="custom-input font-mono" style="font-size:1.15rem; font-weight:700;" value="<?= (float)($state['travauxBudget'] ?? 50000) ?>" required>
            <small style="color:var(--text-dim); margin-top:4px;">Par défaut : 50 000,00 € (montant du fonds prêt ou épargne dédiée travaux maison).</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" id="btn-cancel-budget-modal">Annuler</button>
            <button type="submit" class="btn btn-primary" id="btn-save-budget-val">Valider le budget</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: ADD / EDIT TRAVAUX LOT -->
    <div class="modal-backdrop" id="travaux-lot-modal" style="display: none;">
      <div class="modal-card">
        <div class="modal-header">
          <h3 class="modal-title" id="travaux-lot-modal-title">Nouveau lot de travaux</h3>
          <button class="modal-close-btn" id="btn-close-lot-modal">&times;</button>
        </div>
        <form id="travaux-lot-form">
          <input type="hidden" id="modal-lot-id">

          <div class="form-field">
            <label for="modal-lot-name">Nom du lot / Chantier</label>
            <input type="text" id="modal-lot-name" class="custom-input" placeholder="ex: Isolation Combles, Toiture Roman, Plâtrerie..." required>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-lot-budget">Budget de Base Alloué (€)</label>
              <input type="number" step="50" id="modal-lot-budget" class="custom-input font-mono" placeholder="1000" required>
            </div>
            <div class="form-field">
              <label for="modal-lot-status">Statut du lot</label>
              <select id="modal-lot-status" class="custom-select">
                <option value="ongoing">En cours</option>
                <option value="future">À venir</option>
                <option value="completed">Clôturé / Terminé</option>
                <option value="reserve">Réserve / Imprévus</option>
              </select>
            </div>
          </div>

          <div class="form-group-row">
            <div class="form-field">
              <label for="modal-lot-icon">Icône (Emoji)</label>
              <input type="text" id="modal-lot-icon" class="custom-input" placeholder="🏠, ⚡, 🪟, 🚿..." value="🔨" maxlength="4">
            </div>
            <div class="form-field">
              <label for="modal-lot-notes">Notes / Entreprise (optionnel)</label>
              <input type="text" id="modal-lot-notes" class="custom-input" placeholder="ex: Devis Roman Toiture, Atlantic...">
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-ghost" id="btn-cancel-lot-modal">Annuler</button>
            <button type="submit" class="btn btn-primary" id="btn-save-lot">Enregistrer le lot</button>
          </div>
        </form>
      </div>
    </div>

    <!-- MODAL: GOCARDLESS OPEN BANKING API CONFIGURATION -->
    <div class="modal-backdrop" id="banking-modal" style="display: none;">
      <div class="modal-card" style="max-width: 580px;">
        <div class="modal-header">
          <div>
            <h3 class="modal-title">Synchronisation Bancaire Automatique (DSP2)</h3>
            <p class="modal-subtitle" style="font-size:0.8rem; color:var(--text-dim); margin-top:2px;">Connexion directe Open Banking LCL & Revolut via GoCardless</p>
          </div>
          <button class="modal-close-btn" id="btn-close-banking-modal">&times;</button>
        </div>
        <form id="banking-config-form">
          <div class="banking-notice-box" style="background:rgba(14,165,233,0.06); border:1px solid rgba(14,165,233,0.2); border-radius:8px; padding:12px; margin-bottom:16px;">
            <p style="font-size:0.82rem; color:var(--text-main); margin-bottom:6px;">
              <strong>Comment obtenir vos identifiants gratuits ?</strong>
            </p>
            <ol style="font-size:0.78rem; color:var(--text-muted); margin-left:18px; line-height:1.5;">
              <li>Créez un compte gratuit sur <a href="https://bankaccountdata.gocardless.com/overview/" target="_blank" rel="noopener" style="color:var(--accent-blue); text-decoration:underline;">GoCardless Bank Account Data</a>.</li>
              <li>Allez dans <em>User Secrets</em> et générez un <strong>Secret ID</strong> et un <strong>Secret Key</strong>.</li>
              <li>Collez-les ci-dessous. Vos identifiants restent stockés localement sur votre serveur.</li>
            </ol>
          </div>

          <div class="form-field">
            <label for="banking-secret-id">Secret ID GoCardless</label>
            <input type="text" id="banking-secret-id" class="custom-input font-mono" placeholder="ex: 12345678-abcd-ef01-2345-6789abcdef01" required>
          </div>

          <div class="form-field">
            <label for="banking-secret-key">Secret Key GoCardless</label>
            <input type="password" id="banking-secret-key" class="custom-input font-mono" placeholder="••••••••••••••••••••••••••••••••" required>
          </div>

          <div class="modal-footer" style="justify-content:space-between; align-items:center;">
            <button type="button" class="btn btn-ghost" id="btn-banking-demo-mode" title="Tester immédiatement sans clés">
              ⚡ Mode Démo Direct
            </button>
            <div style="display:flex; gap:8px;">
              <button type="button" class="btn btn-ghost" id="btn-cancel-banking-modal">Fermer</button>
              <button type="submit" class="btn btn-primary" id="btn-save-banking-keys">Enregistrer les clés</button>
            </div>
          </div>
        </form>
      </div>
    </div>
