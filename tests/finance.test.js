import test, { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import {
  computeMonthlyAnnuity,
  calculateLoanMetrics,
  calculateLclSubtotals,
  calculateSplitRatios,
  calculateOffsetDistribution,
  calculateBudgets,
  resolveItemLot,
  calculateLotStatistics,
  calculateEqualization,
  calculateTravauxMetrics
} from '../js/finance.js';
import { DEFAULT_STATE } from '../js/state.js';

describe('Finance & Calculation Engine Tests', () => {
  describe('computeMonthlyAnnuity()', () => {
    it('calculates zero-interest annuity correctly (P / n)', () => {
      // 12 500 € / 180 months = 69.444... €
      const payment = computeMonthlyAnnuity(12500, 0, 180);
      assert.equal(payment.toFixed(2), '69.44');
    });

    it('calculates compound interest annuity correctly with positive rate', () => {
      // 12 500 € at 2.5% over 180 months = 83.35 €
      const payment = computeMonthlyAnnuity(12500, 2.5, 180);
      assert.ok(payment > 69.44);
      assert.equal(payment.toFixed(2), '83.35');
    });

    it('returns 0 if duration or principal is non-positive', () => {
      assert.equal(computeMonthlyAnnuity(0, 2.5, 180), 0);
      assert.equal(computeMonthlyAnnuity(12500, 2.5, 0), 0);
    });
  });

  describe('calculateLoanMetrics()', () => {
    it('computes exact values for default Axel & Ariane simulator configuration', () => {
      const res = calculateLoanMetrics({
        totalGift: 25000,
        userShare: 50,
        durationYears: 15,
        interestRate: 0,
        monthsPaid: 8,
        startMonth: '2026-03',
        method: 'offset'
      });

      assert.equal(res.borrowedAmount, 12500);
      assert.equal(res.totalMonths, 180);
      assert.equal(res.durationYears, 15);
      assert.equal(res.monthlyPayment.toFixed(2), '69.44');
      assert.equal(res.totalCost, 12500);
      assert.equal(res.totalInterest, 0);
      assert.equal(res.monthsPaid, 8);
      assert.equal(res.remainingMonths, 172);
      assert.equal(res.capitalPaidSoFar.toFixed(2), (69.44444444444444 * 8).toFixed(2));
      assert.equal(res.remainingCapital.toFixed(2), (12500 - 69.44444444444444 * 8).toFixed(2));
      assert.equal(res.endDateText, 'Mars 2041');
      assert.equal(res.method, 'offset');
    });

    it('clamps monthsPaid between 0 and totalMonths', () => {
      const overPaid = calculateLoanMetrics({
        totalGift: 10000,
        userShare: 50,
        durationYears: 5,
        monthsPaid: 100 // exceeds 60
      });
      assert.equal(overPaid.monthsPaid, 60);
      assert.equal(overPaid.remainingMonths, 0);
      assert.equal(overPaid.remainingCapital, 0);
    });
  });

  describe('calculateLclSubtotals()', () => {
    it('correctly aggregates credit, taxe foncière and maintenance packages', () => {
      const items = [
        { label: 'Prêt', amount: 1150, category: 'credit' },
        { label: 'Assurance', amount: 38, category: 'credit' },
        { label: 'Taxe Foncière', amount: 110, category: 'taxe' },
        { label: 'Entretien', amount: 50, category: 'maintenance' }
      ];

      const res = calculateLclSubtotals(items);
      assert.equal(res.creditSubtotal, 1188);
      assert.equal(res.taxeSubtotal, 110);
      assert.equal(res.maintSubtotal, 50);
      assert.equal(res.lclTotal, 1348);
      assert.equal(res.lclAnnual, 1348 * 12);
    });
  });

  describe('calculateSplitRatios()', () => {
    const profiles = [
      { id: 'p1', name: 'Axel', income: 3000 },
      { id: 'p2', name: 'Ariane', income: 2000 }
    ];

    it('returns 50/50 ratio when splitRule is 50', () => {
      const { p1Ratio, p2Ratio } = calculateSplitRatios(profiles, '50');
      assert.equal(p1Ratio, 0.5);
      assert.equal(p2Ratio, 0.5);
    });

    it('returns prorata ratio according to incomes when splitRule is income', () => {
      const { p1Ratio, p2Ratio } = calculateSplitRatios(profiles, 'income');
      // 3000 / 5000 = 60%, 2000 / 5000 = 40%
      assert.equal(p1Ratio, 0.6);
      assert.equal(p2Ratio, 0.4);
    });
  });

  describe('calculateOffsetDistribution()', () => {
    it('applies offset method (direct account compensation)', () => {
      const res = calculateOffsetDistribution(674, 674, 69.44, true, 'offset');
      assert.equal(res.p1Total.toFixed(2), '743.44');
      assert.equal(res.p2Total.toFixed(2), '604.56');
      assert.equal(res.diffAmount.toFixed(2), '138.88'); // 2 * monthlyPayment
    });

    it('applies direct partner transfer method', () => {
      const res = calculateOffsetDistribution(674, 674, 69.44, true, 'direct');
      assert.equal(res.p1Total.toFixed(2), '743.44');
      assert.equal(res.p2Total.toFixed(2), '674.00');
      assert.equal(res.diffAmount.toFixed(2), '69.44');
    });
  });

  describe('calculateBudgets()', () => {
    it('computes accurate global monthly and annual budgets with DEFAULT_STATE', () => {
      const res = calculateBudgets(DEFAULT_STATE);
      // LCL total = 1150 + 38 + 34 + 110 + 50 = 1382.00
      assert.equal(res.lclTotal, 1382);
      // Revolut envelopes total = 550 + 140 + 250 + 45 + 80 + 90 = 1155.00
      assert.equal(res.revolutBudgetTotal, 1155);
      // Global monthly = 1382 + 1155 = 2537.00
      assert.equal(res.globalMonthlyBudget, 2537);
      assert.equal(res.globalAnnualBudget, 2537 * 12);
      // Loan monthly payment = 69.44
      assert.equal(res.loan.monthlyPayment.toFixed(2), '69.44');
    });
  });

  describe('resolveItemLot()', () => {
    const lots = [
      { id: 'lot-1', name: 'Toiture' },
      { id: 'lot-2', name: 'Logistique / Repas' },
      { id: 'lot-3', name: 'VMC' }
    ];

    it('matches lot explicitly by lotId when provided', () => {
      const lot = resolveItemLot({ lotId: 'lot-2', reason: 'Autre' }, lots);
      assert.equal(lot.id, 'lot-2');
    });

    it('falls back to keyword matching if lotId is not specified', () => {
      assert.equal(resolveItemLot({ reason: 'Frais de bouche chantier' }, lots).id, 'lot-2');
      assert.equal(resolveItemLot({ reason: 'Toiture Roman Avance' }, lots).id, 'lot-1');
      assert.equal(resolveItemLot({ reason: 'acompte vmc' }, lots).id, 'lot-3');
    });
  });

  describe('calculateTravauxMetrics() - Renovation Envelope 50 000 €', () => {
    it('reconciles exactly to Axel and Ariane noted figures', () => {
      const res = calculateTravauxMetrics(
        DEFAULT_STATE.travauxFund,
        DEFAULT_STATE.profiles,
        DEFAULT_STATE.splitRule
      );

      // Initial budget = 50 000,00 €
      assert.equal(res.initialBudget, 50000);

      // Noted advances:
      // Axel: 4302 (Toiture) + 1000 (Bouche) = 5 302,00 €
      assert.equal(res.totalAxel, 5302);
      // Ariane: 4302 (Toiture) + 1000 (Bouche) + 1874.85 (Toiture) + 636 (VMC) = 7 812,85 €
      assert.equal(res.totalAriane, 7812.85);

      // Total consumed = 5 302 + 7 812.85 = 13 114,85 €
      assert.equal(res.totalConsumed, 13114.85);

      // Remaining budget = 50 000 - 13 114.85 = 36 885,15 €
      assert.equal(res.remainingBudget, 36885.15);

      // Percentages:
      assert.equal(res.percentageConsumed.toFixed(1), '26.2');
      assert.equal(res.percentageRemaining.toFixed(1), '73.8');

      // Personal advance delta: Ariane advanced 2 510,85 € more than Axel
      assert.equal(res.advanceDelta, 2510.85);

      // 50/50 target: each should pay 13 114.85 / 2 = 6 557.425 €
      assert.equal(res.target50Share.toFixed(2), '6557.43');
      // Axel owes Ariane to reach 50/50: 6557.425 - 5302 = 1 255.425 €
      assert.equal(res.axelOwesAriane50.toFixed(2), '1255.43');

      // Check Lot breakdown totals:
      const toitureLot = res.lots.find(l => l.id === 'lot-1');
      assert.equal(toitureLot.allocatedBudget, 15000);
      assert.equal(toitureLot.totalSpent, 10478.85);
      assert.equal(toitureLot.remaining, 4521.15);

      const boucheLot = res.lots.find(l => l.id === 'lot-2');
      assert.equal(boucheLot.allocatedBudget, 2000);
      assert.equal(boucheLot.totalSpent, 2000);
      assert.equal(boucheLot.remaining, 0);

      const vmcLot = res.lots.find(l => l.id === 'lot-3');
      assert.equal(vmcLot.allocatedBudget, 1500);
      assert.equal(vmcLot.totalSpent, 636);
      assert.equal(vmcLot.remaining, 864);

      // Check overall allocated lots metrics:
      assert.equal(res.totalAllocatedLots, 50000);
      assert.equal(res.unallocatedBudget, 0);
      assert.equal(res.lotsRemaining, 36885.15);
    });

    it('recalculates totals and remaining on the fly when lot base budgets are modified', () => {
      // Clone fund state
      const fund = JSON.parse(JSON.stringify(DEFAULT_STATE.travauxFund));
      const lot1 = fund.lots.find(l => l.id === 'lot-1');
      
      // Simulate user editing base budget inline: 15 000 -> 12 000 €
      lot1.allocatedBudget = 12000;
      const res = calculateTravauxMetrics(fund, DEFAULT_STATE.profiles, DEFAULT_STATE.splitRule);
      
      assert.equal(res.totalAllocatedLots, 47000);
      assert.equal(res.unallocatedBudget, 3000); // 50 000 - 47 000
      assert.equal(res.lotsRemaining, 33885.15); // 47 000 - 13 114.85
      
      const updatedLot1 = res.lots.find(l => l.id === 'lot-1');
      assert.equal(updatedLot1.remaining, 1521.15); // 12 000 - 10 478.85
      assert.equal(updatedLot1.isOverBudget, false);

      // Simulate over-budget inline modification: 12 000 -> 8 000 €
      lot1.allocatedBudget = 8000;
      const resOver = calculateTravauxMetrics(fund, DEFAULT_STATE.profiles, DEFAULT_STATE.splitRule);
      const overLot1 = resOver.lots.find(l => l.id === 'lot-1');
      assert.equal(overLot1.remaining, -2478.85); // 8 000 - 10 478.85
      assert.equal(overLot1.isOverBudget, true);
    });
  });
});

