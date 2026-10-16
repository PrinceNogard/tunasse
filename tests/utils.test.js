import test, { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import {
  formatCurrency,
  formatDateFR,
  computeMonthsElapsedFromStart,
  computeStartMonthFromMonthsElapsed,
  computeEndDate,
  computeRemainingTimeText,
  sanitizeCsvCell
} from '../js/utils.js';

describe('Utils & Formatting Tests', () => {
  describe('formatCurrency()', () => {
    it('formats zero correctly', () => {
      const res = formatCurrency(0);
      assert.match(res, /0,00/);
      assert.match(res, /€/);
    });

    it('formats round and decimal values with thousands separator', () => {
      const res1 = formatCurrency(50000);
      assert.match(res1, /50\s?000,00/);

      const res2 = formatCurrency(13114.85);
      assert.match(res2, /13\s?114,85/);
    });

    it('handles negative numbers safely', () => {
      const res = formatCurrency(-1255.43);
      assert.match(res, /-1\s?255,43/);
    });

    it('handles string numbers and NaN fallback safely', () => {
      assert.match(formatCurrency('1500.5'), /1\s?500,50/);
      assert.match(formatCurrency('invalid'), /0,00/);
      assert.match(formatCurrency(null), /0,00/);
    });
  });

  describe('formatDateFR()', () => {
    it('converts ISO YYYY-MM-DD to DD/MM/YYYY', () => {
      assert.equal(formatDateFR('2026-10-15'), '15/10/2026');
      assert.equal(formatDateFR('2026-03-01'), '01/03/2026');
    });

    it('returns empty string on null or undefined', () => {
      assert.equal(formatDateFR(''), '');
      assert.equal(formatDateFR(null), '');
    });

    it('returns original input if not in 3-part YYYY-MM-DD format', () => {
      assert.equal(formatDateFR('15-10-2026'), '2026/10/15');
      assert.equal(formatDateFR('October'), 'October');
    });
  });

  describe('computeMonthsElapsedFromStart() & computeStartMonthFromMonthsElapsed()', () => {
    const fixedNow = new Date(2026, 9, 16); // October 2026

    it('computes elapsed months from start date', () => {
      // March 2026 to October 2026 = 7 full calendar months elapsed
      const elapsed = computeMonthsElapsedFromStart('2026-03', fixedNow);
      assert.equal(elapsed, 7);
    });

    it('returns 0 if start date is in the future', () => {
      const elapsed = computeMonthsElapsedFromStart('2027-01', fixedNow);
      assert.equal(elapsed, 0);
    });

    it('computes start month reversely from elapsed months', () => {
      const startMonth = computeStartMonthFromMonthsElapsed(7, fixedNow);
      assert.equal(startMonth, '2026-03');
    });
  });

  describe('computeEndDate()', () => {
    it('computes exact end date label from start month and duration in months', () => {
      // March 2026 + 180 months (15 years) = March 2041
      const endDate = computeEndDate('2026-03', 180);
      assert.equal(endDate, 'Mars 2041');
    });

    it('handles year boundary transitions correctly', () => {
      // November 2026 + 3 months = February 2027
      const endDate = computeEndDate('2026-11', 3);
      assert.equal(endDate, 'Février 2027');
    });
  });

  describe('computeRemainingTimeText()', () => {
    it('reports completed status when remaining months is 0', () => {
      const text = computeRemainingTimeText(0);
      assert.match(text, /🎉 Remboursement entièrement terminé !/);
    });

    it('reports pluralized years and months', () => {
      const text = computeRemainingTimeText(172);
      // 172 months = 14 years and 4 months
      assert.match(text, /14 ans et 4 mois/);
      assert.match(text, /172 mensualités/);
    });

    it('handles single year and single month correctly', () => {
      const text = computeRemainingTimeText(13);
      assert.match(text, /1 an et 1 mois/);
    });
  });

  describe('sanitizeCsvCell()', () => {
    it('wraps string in quotes and escapes internal quotes', () => {
      assert.equal(sanitizeCsvCell('Toiture Roman'), '"Toiture Roman"');
      assert.equal(sanitizeCsvCell('Fenêtre "Double Vitrage"'), '"Fenêtre ""Double Vitrage"""');
      assert.equal(sanitizeCsvCell(1250), '"1250"');
    });
  });
});
