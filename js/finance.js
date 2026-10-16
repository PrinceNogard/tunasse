/**
 * DuoBudget • Finance & Mathematical Engines Module
 * Pure calculation functions refactored for low cyclomatic complexity (< 6).
 */

import { computeEndDate, computeRemainingTimeText } from './utils.js';

/**
 * Compute monthly payment according to loan amortization formula:
 * M = P * [r / (1 - (1+r)^-n)] or P / n if interest is 0.
 * @param {number} principal 
 * @param {number} annualRate 
 * @param {number} totalMonths 
 * @returns {number}
 */
export function computeMonthlyAnnuity(principal, annualRate, totalMonths) {
  if (totalMonths <= 0 || principal <= 0) return 0;
  if (annualRate <= 0) return principal / totalMonths;

  const monthlyRate = (annualRate / 100) / 12;
  return principal * (monthlyRate / (1 - Math.pow(1 + monthlyRate, -totalMonths)));
}

/**
 * Calculate dynamic loan metrics for partner gift/repayment simulation
 * @param {Object} simulator 
 * @returns {Object}
 */
export function calculateLoanMetrics(simulator = {}) {
  const totalGift = Number(simulator.totalGift) || 0;
  const userShare = Number(simulator.userShare) || 50;
  const durationYears = Number(simulator.durationYears) || 15;
  const interestRate = Number(simulator.interestRate) || 0;
  const monthsPaidInput = Number(simulator.monthsPaid) || 0;
  const startMonth = simulator.startMonth || '2026-03';
  const method = simulator.method || 'offset';

  const borrowedAmount = totalGift * (userShare / 100);
  const totalMonths = durationYears * 12;
  const monthlyPayment = computeMonthlyAnnuity(borrowedAmount, interestRate, totalMonths);
  const totalCost = interestRate > 0 ? (monthlyPayment * totalMonths) : borrowedAmount;
  const totalInterest = Math.max(0, totalCost - borrowedAmount);

  const safePaidMonths = Math.min(Math.max(0, monthsPaidInput), totalMonths);
  const remainingMonths = Math.max(0, totalMonths - safePaidMonths);
  const capitalPaidSoFar = monthlyPayment * safePaidMonths;
  const remainingCapital = Math.max(0, totalCost - capitalPaidSoFar);

  return {
    borrowedAmount,
    totalMonths,
    durationYears,
    monthlyPayment,
    totalCost,
    totalInterest,
    startMonth,
    monthsPaid: safePaidMonths,
    remainingMonths,
    capitalPaidSoFar,
    remainingCapital,
    endDateText: computeEndDate(startMonth, totalMonths),
    remainingTimeText: computeRemainingTimeText(remainingMonths),
    method
  };
}

/**
 * Calculate categorized subtotals for LCL joint account
 * @param {Array} items 
 * @returns {{ creditSubtotal: number, taxeSubtotal: number, maintSubtotal: number, lclTotal: number, lclAnnual: number }}
 */
export function calculateLclSubtotals(items = []) {
  let creditSubtotal = 0;
  let taxeSubtotal = 0;
  let maintSubtotal = 0;

  for (const item of items) {
    const amt = Number(item.amount) || 0;
    const cat = item.category || 'credit';
    if (cat === 'taxe') {
      taxeSubtotal += amt;
    } else if (cat === 'maintenance') {
      maintSubtotal += amt;
    } else {
      creditSubtotal += amt;
    }
  }

  const lclTotal = creditSubtotal + taxeSubtotal + maintSubtotal;
  return {
    creditSubtotal,
    taxeSubtotal,
    maintSubtotal,
    lclTotal,
    lclAnnual: lclTotal * 12
  };
}

/**
 * Calculate split ratios between two partners
 * @param {Array} profiles 
 * @param {string} splitRule 
 * @returns {{ p1Ratio: number, p2Ratio: number }}
 */
export function calculateSplitRatios(profiles = [], splitRule = '50') {
  const p1 = profiles[0] || { income: 2500 };
  const p2 = profiles[1] || { income: 2500 };

  if (splitRule === 'income') {
    const totalIncome = (Number(p1.income) || 0) + (Number(p2.income) || 0);
    if (totalIncome > 0) {
      return {
        p1Ratio: (Number(p1.income) || 0) / totalIncome,
        p2Ratio: (Number(p2.income) || 0) / totalIncome
      };
    }
  }

  return { p1Ratio: 0.5, p2Ratio: 0.5 };
}

/**
 * Calculate net partner monthly amounts applying either offset or direct method
 */
export function calculateOffsetDistribution(p1Base, p2Base, monthlyPayment, isP1Borrower, method) {
  if (method === 'offset') {
    const paymentDelta = isP1Borrower ? monthlyPayment : -monthlyPayment;
    const p1Total = p1Base + paymentDelta;
    const p2Total = p2Base - paymentDelta;
    return {
      p1Total,
      p2Total,
      diffAmount: Math.abs(p1Total - p2Total)
    };
  }

  return {
    p1Total: p1Base + (isP1Borrower ? monthlyPayment : 0),
    p2Total: p2Base + (!isP1Borrower ? monthlyPayment : 0),
    diffAmount: monthlyPayment
  };
}

/**
 * Master household budget calculation engine
 * @param {Object} appState 
 * @returns {Object}
 */
export function calculateBudgets(appState = {}) {
  const lclMetrics = calculateLclSubtotals(appState.lclItems || []);
  const revolutEnvelopes = appState.revolutEnvelopes || [];
  const revolutBudgetTotal = revolutEnvelopes.reduce((acc, cat) => acc + (Number(cat.budget) || 0), 0);
  const revolutAnnual = revolutBudgetTotal * 12;

  const currentMonthPrefix = new Date().toISOString().slice(0, 7);
  const revolutSpentThisMonth = (appState.expenses || [])
    .filter(e => e.account === 'Revolut' && typeof e.date === 'string' && e.date.startsWith(currentMonthPrefix))
    .reduce((acc, e) => acc + (Number(e.amount) || 0), 0);

  const globalMonthlyBudget = lclMetrics.lclTotal + revolutBudgetTotal;
  const globalAnnualBudget = globalMonthlyBudget * 12;

  const loan = calculateLoanMetrics(appState.simulator || {});
  const { p1Ratio, p2Ratio } = calculateSplitRatios(appState.profiles || [], appState.splitRule || '50');

  const p1 = (appState.profiles && appState.profiles[0]) || { id: 'p1', name: 'Axel' };
  const p2 = (appState.profiles && appState.profiles[1]) || { id: 'p2', name: 'Ariane' };
  const isP1Borrower = ((appState.simulator && appState.simulator.borrowerId) === p1.id);

  const p1LclBase = lclMetrics.lclTotal * p1Ratio;
  const p2LclBase = lclMetrics.lclTotal * p2Ratio;
  const p1RevBase = revolutBudgetTotal * p1Ratio;
  const p2RevBase = revolutBudgetTotal * p2Ratio;

  const lclOffset = calculateOffsetDistribution(p1LclBase, p2LclBase, loan.monthlyPayment, isP1Borrower, loan.method);
  const p1Lcl = loan.method === 'offset' ? lclOffset.p1Total : p1LclBase;
  const p2Lcl = loan.method === 'offset' ? lclOffset.p2Total : p2LclBase;

  const p1Total = p1Lcl + p1RevBase + (loan.method === 'direct' && isP1Borrower ? loan.monthlyPayment : 0);
  const p2Total = p2Lcl + p2RevBase + (loan.method === 'direct' && !isP1Borrower ? loan.monthlyPayment : 0);
  const diffAmount = loan.method === 'offset' ? lclOffset.diffAmount : loan.monthlyPayment;

  return {
    ...lclMetrics,
    revolutBudgetTotal,
    revolutAnnual,
    revolutSpentThisMonth,
    globalMonthlyBudget,
    globalAnnualBudget,
    loan,
    p1Ratio,
    p2Ratio,
    split: {
      p1,
      p2,
      isP1Borrower,
      p1Total,
      p2Total,
      p1Lcl,
      p2Lcl,
      p1Rev: p1RevBase,
      p2Rev: p2RevBase,
      diffAmount
    }
  };
}

/**
 * Pure lot resolution for a travaux item (by ID or keyword fallback)
 * @param {Object} item 
 * @param {Array} lots 
 * @returns {Object|null}
 */
export function resolveItemLot(item, lots = []) {
  if (!item || lots.length === 0) return null;
  if (item.lotId) {
    const found = lots.find(l => l.id === item.lotId);
    if (found) return found;
  }

  const reason = (item.reason || '').toLowerCase();
  if (reason.includes('bouche') || reason.includes('repas') || reason.includes('logistique')) {
    return lots.find(l => l.id === 'lot-2') || lots[0];
  }
  if (reason.includes('toiture')) {
    return lots.find(l => l.id === 'lot-1') || lots[0];
  }
  if (reason.includes('vmc') || reason.includes('ventilation')) {
    return lots.find(l => l.id === 'lot-3') || lots[0];
  }
  return lots[0] || null;
}

/**
 * Compute aggregated metrics per renovation lot
 * @param {Array} rawLots 
 * @param {Array} items 
 * @returns {{ lotStats: Array, totalAllocated: number }}
 */
export function calculateLotStatistics(rawLots = [], items = []) {
  const lotStats = rawLots.map(lot => ({
    ...lot,
    allocatedBudget: Number(lot.allocatedBudget) || 0,
    totalSpent: 0,
    spentAxel: 0,
    spentAriane: 0,
    remaining: 0,
    percentConsumed: 0,
    isOverBudget: false,
    itemsCount: 0
  }));

  for (const item of items) {
    const ax = Number(item.amountAxel) || 0;
    const ar = Number(item.amountAriane) || 0;
    const itemTotal = ax + ar;
    const targetLot = resolveItemLot(item, lotStats);

    if (targetLot) {
      targetLot.totalSpent += itemTotal;
      targetLot.spentAxel += ax;
      targetLot.spentAriane += ar;
      targetLot.itemsCount += 1;
    }
  }

  let totalAllocated = 0;
  for (const lot of lotStats) {
    totalAllocated += lot.allocatedBudget;
    lot.remaining = lot.allocatedBudget - lot.totalSpent;
    lot.percentConsumed = lot.allocatedBudget > 0 ? (lot.totalSpent / lot.allocatedBudget) * 100 : 0;
    lot.isOverBudget = lot.totalSpent > lot.allocatedBudget;
  }

  return { lotStats, totalAllocated };
}

/**
 * Compute equalization and partner settlement metrics
 */
export function calculateEqualization(totalAxel, totalAriane, totalConsumed, profiles = [], splitRule = '50') {
  const target50Share = Math.round((totalConsumed / 2) * 100) / 100;
  const axelOwesAriane50 = Math.round((target50Share - totalAxel) * 100) / 100;

  const { p1Ratio, p2Ratio } = calculateSplitRatios(profiles, splitRule);
  const targetP1Share = Math.round((totalConsumed * p1Ratio) * 100) / 100;
  const axelOwesArianeProrata = Math.round((targetP1Share - totalAxel) * 100) / 100;

  return {
    advanceDelta: Math.round((totalAriane - totalAxel) * 100) / 100,
    target50Share,
    axelOwesAriane50,
    p1Ratio,
    p2Ratio,
    targetP1Share,
    axelOwesArianeProrata
  };
}

/**
 * Renovation fund calculations (baseline 50 000 €, lot metrics, balances)
 * @param {Object} fund 
 * @param {Array} profiles 
 * @param {string} splitRule 
 * @returns {Object}
 */
export function calculateTravauxMetrics(fund = {}, profiles = [], splitRule = '50') {
  const initialBudget = Number(fund.initialBudget) || 50000.00;
  const rawLots = fund.lots || [];
  const items = fund.items || [];

  let totalAxel = 0;
  let totalAriane = 0;
  let reimbursedAxel = 0;
  let reimbursedAriane = 0;
  let unreimbursedAxel = 0;
  let unreimbursedAriane = 0;

  for (const item of items) {
    const ax = Number(item.amountAxel) || 0;
    const ar = Number(item.amountAriane) || 0;
    totalAxel += ax;
    totalAriane += ar;

    if (item.reimbursed) {
      reimbursedAxel += ax;
      reimbursedAriane += ar;
    } else {
      unreimbursedAxel += ax;
      unreimbursedAriane += ar;
    }
  }

  const { lotStats, totalAllocated } = calculateLotStatistics(rawLots, items);
  const totalConsumed = totalAxel + totalAriane;
  const remainingBudget = initialBudget - totalConsumed;
  const percentageConsumed = initialBudget > 0 ? (totalConsumed / initialBudget) * 100 : 0;
  const percentageRemaining = Math.max(0, 100 - percentageConsumed);

  const axelShareOfConsumed = totalConsumed > 0 ? (totalAxel / totalConsumed) * 100 : 50;
  const arianeShareOfConsumed = totalConsumed > 0 ? (totalAriane / totalConsumed) * 100 : 50;

  const axelBudgetPct = initialBudget > 0 ? (totalAxel / initialBudget) * 100 : 0;
  const arianeBudgetPct = initialBudget > 0 ? (totalAriane / initialBudget) * 100 : 0;
  const remainingBudgetPct = initialBudget > 0 ? (remainingBudget / initialBudget) * 100 : 0;

  const eq = calculateEqualization(totalAxel, totalAriane, totalConsumed, profiles, splitRule);

  return {
    initialBudget,
    lots: lotStats,
    items,
    totalAllocatedLots: totalAllocated,
    totalAxel,
    totalAriane,
    totalConsumed,
    remainingBudget,
    percentageConsumed,
    percentageRemaining,
    axelShareOfConsumed,
    arianeShareOfConsumed,
    axelBudgetPct,
    arianeBudgetPct,
    remainingBudgetPct,
    reimbursedAxel,
    reimbursedAriane,
    unreimbursedAxel,
    unreimbursedAriane,
    totalUnreimbursed: unreimbursedAxel + unreimbursedAriane,
    ...eq
  };
}
