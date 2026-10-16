<?php
/** @var array $state */
use Tunasse\Utils\Format;

$profiles = $state['profiles'] ?? [];
$splitMode = $state['splitRule']['mode'] ?? '50';
?>
      <!-- ================= TAB 5: PROFILES & REPARTITION (GENERIC) (SERVER-RENDERED VIA PHP) ================= -->
      <section class="tab-pane" id="pane-profiles">
        <div class="grid-2-col">
          <!-- Profiles Cards -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Gestion Générique des Profils</h2>
                <p class="card-subtitle">Personnalisation des membres du foyer (actuellement configuré pour <?= htmlspecialchars($profiles[0]['name'] ?? 'Axel') ?> & <?= htmlspecialchars($profiles[1]['name'] ?? 'Ariane') ?>)</p>
              </div>
            </div>

            <div class="profiles-edit-list" id="profiles-container">
              <?php foreach ($profiles as $idx => $p): ?>
                <div class="profile-card" data-index="<?= (int)$idx ?>">
                  <div class="profile-card-header">
                    <div class="avatar-lg" style="background:<?= htmlspecialchars($p['color'] ?? '#0ea5e9') ?>">
                      <?= htmlspecialchars($p['initials'] ?? '') ?>
                    </div>
                    <div>
                      <h3 class="profile-title"><?= htmlspecialchars($p['name'] ?? '') ?></h3>
                      <span class="profile-role">Membre <?= (int)$idx + 1 ?></span>
                    </div>
                  </div>
                  <div class="form-group-row mt-3">
                    <div class="form-field">
                      <label>Prénom / Nom</label>
                      <input type="text" class="custom-input profile-input-name" data-index="<?= (int)$idx ?>" value="<?= htmlspecialchars($p['name'] ?? '') ?>">
                    </div>
                    <div class="form-field">
                      <label>Initiales</label>
                      <input type="text" class="custom-input profile-input-initials" data-index="<?= (int)$idx ?>" value="<?= htmlspecialchars($p['initials'] ?? '') ?>" maxlength="3">
                    </div>
                  </div>
                  <div class="form-group-row">
                    <div class="form-field">
                      <label>Revenu Net Mensuel (€)</label>
                      <input type="number" class="custom-input profile-input-income" data-index="<?= (int)$idx ?>" value="<?= (float)($p['income'] ?? 0) ?>" step="50">
                    </div>
                    <div class="form-field">
                      <label>Couleur du thème</label>
                      <input type="color" class="custom-input profile-input-color" data-index="<?= (int)$idx ?>" value="<?= htmlspecialchars($p['color'] ?? '#0ea5e9') ?>" style="height:42px; padding:2px;">
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>

          <!-- Automated Banking Integration (DSP2) -->
          <div class="glass-card mb-4">
            <div class="card-header">
              <div>
                <h2 class="card-title">Synchronisation Bancaire Automatique (DSP2)</h2>
                <p class="card-subtitle">Connexion en temps réel aux comptes LCL & Revolut via l'API Open Banking GoCardless</p>
              </div>
              <button class="btn btn-secondary btn-sm" id="btn-open-banking-config">
                ⚙️ Configurer l'API
              </button>
            </div>

            <div class="banking-sync-dashboard mt-3">
              <div class="grid-2-col" style="gap:12px; margin-bottom:14px;">
                <div class="metric-card" style="padding:14px;">
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:8px;">
                      <span style="font-size:1.4rem;">🏛️</span>
                      <div>
                        <strong style="display:block; font-size:0.9rem;">Compte LCL</strong>
                        <span style="font-size:0.75rem; color:var(--text-dim);" id="banking-status-lcl">Non connecté</span>
                      </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-connect-lcl">
                      Connecter LCL
                    </button>
                  </div>
                </div>

                <div class="metric-card" style="padding:14px;">
                  <div style="display:flex; justify-content:space-between; align-items:center;">
                    <div style="display:flex; align-items:center; gap:8px;">
                      <span style="font-size:1.4rem;">💳</span>
                      <div>
                        <strong style="display:block; font-size:0.9rem;">Compte Revolut</strong>
                        <span style="font-size:0.75rem; color:var(--text-dim);" id="banking-status-revolut">Non connecté</span>
                      </div>
                    </div>
                    <button type="button" class="btn btn-secondary btn-sm" id="btn-connect-revolut">
                      Connecter Revolut
                    </button>
                  </div>
                </div>
              </div>

              <div class="banking-sync-actions" style="display:flex; justify-content:space-between; align-items:center; padding:10px 14px; background:rgba(255,255,255,0.02); border:1px solid var(--border-subtle); border-radius:8px;">
                <div style="font-size:0.8rem; color:var(--text-muted);">
                  Dernière synchronisation automatique : <strong class="font-mono text-cyan" id="banking-last-sync-display">Jamais</strong>
                </div>
                <button type="button" class="btn btn-primary btn-sm" id="btn-trigger-bank-sync">
                  ⟳ Synchroniser maintenant
                </button>
              </div>
            </div>
          </div>

          <!-- Data Management & Backups -->
          <div class="glass-card">
            <div class="card-header">
              <div>
                <h2 class="card-title">Sauvegarde & Export PHP</h2>
                <p class="card-subtitle">Données sécurisées et persistées sur le serveur dans <code>data/app_state.json</code></p>
              </div>
            </div>

            <div class="data-management-zone" style="border:none; padding-top:0; margin-top:0;">
              <p class="data-desc">
                Vous pouvez exporter l'intégralité de votre configuration (profils, simulateur de prêt familial, package LCL, enveloppes Revolut et tableau des travaux 50 000 €) sous forme de fichier JSON sécurisé.
              </p>
              
              <div class="btn-group-wrap">
                <a href="api.php?action=export_json" class="btn btn-secondary" id="btn-export-json">
                  📥 Télécharger sauvegarde JSON
                </a>
                <label class="btn btn-secondary cursor-pointer">
                  📤 Importer un fichier JSON
                  <input type="file" id="import-json-file" accept=".json" style="display:none;">
                </label>
                <button type="button" class="btn btn-danger" id="btn-reset-all-data">
                  ⚠️ Réinitialiser aux données par défaut
                </button>
              </div>
            </div>

            <div class="quick-summary-box mt-6">
              <h4 class="card-title" style="font-size:0.95rem;">Architecture Full PHP 8.3 & Reactivité Hybride</h4>
              <p class="card-subtitle" style="line-height:1.5;">
                Toutes les vues sont rendues côté serveur par <strong>PHP 8.3</strong> en amont pour un affichage immédiat sans saut d'interface. En parallèle, les interactions en direct (déplacement des curseurs, ajout de dépenses, filtres) sont calculées instantanément dans le navigateur et synchronisées via l'API REST PHP (<code>api.php</code>).
              </p>
            </div>
          </div>
        </div>
      </section>
