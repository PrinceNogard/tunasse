import test, { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import {
  getLotStatusConfig,
  getLotProgressBarConfig,
  getLotRemainingHtml,
  generateTravauxCsvContent
} from '../js/travaux-ui.js';
import { calculateTravauxMetrics } from '../js/finance.js';
import { DEFAULT_STATE } from '../js/state.js';

describe('Travaux UI & CSV Tests', () => {
  describe('getLotStatusConfig()', () => {
    it('returns appropriate label and cssClass for ongoing status', () => {
      const config = getLotStatusConfig('ongoing');
      assert.equal(config.label, 'En cours');
      assert.equal(config.cssClass, 'status-ongoing');
    });

    it('returns appropriate label and cssClass for completed status', () => {
      const config = getLotStatusConfig('completed');
      assert.equal(config.label, 'Clôturé');
      assert.equal(config.cssClass, 'status-completed');
    });

    it('returns fallback for unknown or future status', () => {
      const config = getLotStatusConfig('future');
      assert.equal(config.label, 'À venir');
      assert.equal(config.cssClass, 'status-future');
    });
  });

  describe('getLotProgressBarConfig()', () => {
    it('sets emerald bar for normal consumption (< 80%)', () => {
      const res = getLotProgressBarConfig(45, false);
      assert.equal(res.barColor, '#10b981');
      assert.equal(res.clampedPct, 45);
    });

    it('sets amber bar for warning consumption (>= 80%)', () => {
      const res = getLotProgressBarConfig(85, false);
      assert.equal(res.barColor, '#f59e0b');
      assert.equal(res.clampedPct, 85);
    });

    it('sets rose bar and clamps to 100 on over-budget', () => {
      const res = getLotProgressBarConfig(115, true);
      assert.equal(res.barColor, '#f43f5e');
      assert.equal(res.clampedPct, 100);
    });
  });

  describe('generateTravauxCsvContent()', () => {
    it('produces valid CSV with UTF-8 BOM, table headers and lines matching calculations', () => {
      const m = calculateTravauxMetrics(
        DEFAULT_STATE.travauxFund,
        DEFAULT_STATE.profiles,
        DEFAULT_STATE.splitRule
      );

      const fixedDate = new Date(2026, 9, 16, 17, 30);
      const csv = generateTravauxCsvContent(m, fixedDate);

      // Check UTF-8 BOM
      assert.ok(csv.startsWith('\uFEFF'));

      // Check title and dates
      assert.match(csv, /DUO BUDGET - SUIVI FINANCIER DES TRAVAUX DE RENOVATION/);
      assert.match(csv, /50000,00 €/);
      assert.match(csv, /13114,85 €/);
      assert.match(csv, /36885,15 €/);

      // Check Lots section
      assert.match(csv, /TABLEAU DE SUIVI BUDGETAIRE : BUDGET DE BASE VS DEPENSES REELLES PAR LOT/);
      assert.match(csv, /"Toiture & Couverture \(Chantier Roman\)";"En cours";15000,00;10478,85/);
      assert.match(csv, /"Logistique, Repas & Frais Chantier";"Clôturé";2000,00;2000,00/);
      assert.match(csv, /"Ventilation & Qualité de l'air \(VMC\)";"En cours";1500,00;636,00/);

      // Check Total line
      assert.match(csv, /"TOTAL DU BUDGET TRAVAUX";"Global";50000,00;13114,85;5302,00;7812,85;36885,15;26,2%/);

      // Check Invoices section
      assert.match(csv, /DETAIL DES FACTURES ET ACOMPTES TRAVAUX NOTES/);
      assert.match(csv, /"2026-10-10";"Toiture Roman Avance"/);
      assert.match(csv, /"2026-10-15";"acompte vmc"/);
    });
  });
});
