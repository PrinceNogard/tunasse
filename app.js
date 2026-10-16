/**
 * DuoBudget • Couple Finance Management & Real-time Loan Equalizer
 * Refactored Modular Architecture (Cyclomatic Complexity < 6)
 * Native ES Module running seamlessly in browser and Node test runner.
 */

import {
  formatCurrency,
  formatDateFR
} from './js/utils.js';

import {
  calculateLoanMetrics,
  calculateBudgets,
  calculateTravauxMetrics,
  resolveItemLot,
  computeElapsedMonths
} from './js/finance.js';

import {
  DEFAULT_STATE,
  STORAGE_KEY,
  loadState
} from './js/state.js';

import {
  getLotStatusConfig,
  getLotProgressBarConfig,
  getLotRemainingHtml,
  generateTravauxCsvContent
} from './js/travaux-ui.js';

// ============================================================
// 1. STATE & PERSISTENCE
// ============================================================

let appState = loadState(STORAGE_KEY, DEFAULT_STATE);
let saveTimeout = null;

function syncBackend() {
  if (typeof fetch === 'function' && typeof window !== 'undefined' && window.location && window.location.protocol.startsWith('http')) {
    fetch('api.php?action=import_json', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(appState)
    }).catch(err => console.warn('Backend sync warning:', err));
  }
}

function saveStateDebounced() {
  if (saveTimeout) clearTimeout(saveTimeout);
  saveTimeout = setTimeout(() => {
    saveStateImmediate();
  }, 300);
}

function saveStateImmediate() {
  try {
    if (typeof localStorage !== 'undefined') {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(appState));
    }
  } catch (e) {
    console.error('LocalStorage save failed:', e);
  }
  syncBackend();
}

function getBorrowerProfile() {
  return (appState.profiles || []).find(p => p.id === appState.simulator?.borrowerId) || appState.profiles[0];
}

function getLenderProfile() {
  return (appState.profiles || []).find(p => p.id === appState.simulator?.lenderId) || appState.profiles[1];
}

// Active filters for Travaux
let activeTravauxSearch = '';
let activeTravauxFilter = 'all'; // 'all', 'unreimbursed', 'reimbursed'

// ============================================================
// 2. SIMULATOR REAL-TIME SYNCHRONIZATION (REFACTORED)
// ============================================================

const SIMULATOR_INPUT_SYNC = [
  { id: 'num-loan-amount', prop: 'totalGift' },
  { id: 'slider-loan-amount', prop: 'totalGift' },
  { id: 'dash-slider-amount', prop: 'totalGift' },
  { id: 'num-user-share', prop: 'userShare' },
  { id: 'slider-user-share', prop: 'userShare' },
  { id: 'slider-loan-duration', prop: 'durationYears' },
  { id: 'num-loan-interest', prop: 'interestRate' },
  { id: 'slider-loan-interest', prop: 'interestRate' },
  { id: 'input-loan-start-month', prop: 'startMonth' },
  { id: 'num-months-paid', val: (loan) => loan.monthsPaid }
];

function syncSimulatorInputs(activeInputId, loan) {
  for (const item of SIMULATOR_INPUT_SYNC) {
    if (activeInputId === item.id) continue;
    const el = document.getElementById(item.id);
    if (el) {
      el.value = item.val ? item.val(loan) : appState.simulator[item.prop];
    }
  }

  const dispYears = document.getElementById('display-duration-years');
  const dispMonths = document.getElementById('display-duration-months');
  if (dispYears) dispYears.textContent = appState.simulator.durationYears;
  if (dispMonths) dispMonths.textContent = loan.totalMonths;

  const labelElapsed = document.getElementById('label-elapsed-months');
  if (labelElapsed) labelElapsed.textContent = `${loan.monthsPaid} mois`;

  const autoDisplay = document.getElementById('auto-months-paid-display');
  if (autoDisplay) autoDisplay.textContent = `${loan.monthsPaid} mois réglés`;

  const [sYear, sMonth] = (appState.simulator.startMonth || '2026-03').split('-');
  const calYear = document.getElementById('cal-selected-year');
  if (calYear && sYear) calYear.textContent = sYear;
  document.querySelectorAll('.cal-month-btn').forEach(btn => {
    btn.classList.toggle('active', btn.dataset.m === sMonth);
  });
}

function updateSimulatorMetricPills(loan, borrower, lender) {
  const fmtPayment = formatCurrency(loan.monthlyPayment);
  const simMonthly = document.getElementById('sim-monthly-payment');
  if (simMonthly) simMonthly.textContent = fmtPayment;

  const topRepayMonthly = document.getElementById('top-repayment-monthly');
  if (topRepayMonthly) topRepayMonthly.textContent = `+${fmtPayment}`;

  const topRepayTag = document.getElementById('top-repayment-tag');
  if (topRepayTag) topRepayTag.textContent = `${loan.durationYears} ans`;

  const topRepaySub = document.getElementById('top-repayment-subtext');
  if (topRepaySub) topRepaySub.textContent = `${borrower.name} remb. ${lender.name} (Mois ${loan.monthsPaid}/${loan.totalMonths})`;

  const dashQuickVal = document.getElementById('dash-quick-loan-val');
  if (dashQuickVal) dashQuickVal.textContent = `${formatCurrency(appState.simulator.totalGift)} • ${loan.durationYears} ans = ${fmtPayment}/m`;

  const simClarif = document.getElementById('sim-clarification-text');
  if (simClarif) {
    simClarif.innerHTML = `Montant exact de la mensualité due par <strong>${borrower.name}</strong> à <strong>${lender.name}</strong> pendant <strong>${loan.durationYears} ans</strong> (${loan.totalMonths} mois).`;
  }

  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  setEl('sim-total-borrowed', formatCurrency(loan.borrowedAmount));
  setEl('sim-borrowed-sub', `(${appState.simulator.userShare}% de ${formatCurrency(appState.simulator.totalGift)})`);
  setEl('sim-total-months', `${loan.totalMonths} mois`);
  setEl('sim-years-sub', `(${loan.durationYears} ans)`);
  setEl('sim-total-interests', formatCurrency(loan.totalInterest));
  setEl('sim-interest-sub', `(Taux à ${appState.simulator.interestRate.toFixed(2)}%)`);
  setEl('sim-grand-total', formatCurrency(loan.totalCost));
  setEl('sim-paid-sum-pill', formatCurrency(loan.capitalPaidSoFar));
  setEl('sim-remaining-sum-pill', formatCurrency(loan.remainingCapital));
  setEl('sim-end-date-pill', loan.endDateText);
}

function updateSimulatorMathExplainer(loan, borrower, lender) {
  const explainerBox = document.getElementById('offset-explainer-content');
  if (!explainerBox) return;

  const fmtPayment = formatCurrency(loan.monthlyPayment);
  if (loan.method === 'offset') {
    explainerBox.innerHTML = `
      Pour éviter tout virement bancaire privé tous les mois entre vous, vous équilibrez directement via le compte commun LCL :<br>
      • <strong>${borrower.name}</strong> : verse <strong>+${fmtPayment}</strong> en plus sur le compte commun.<br>
      • <strong>${lender.name}</strong> : déduit <strong>-${fmtPayment}</strong> de sa part sur le compte commun.<br>
      • <strong>Écart visible sur le compte joint :</strong> ${borrower.name} verse <strong>${formatCurrency(loan.monthlyPayment * 2)} de plus que ${lender.name}</strong> au total chaque mois (le double de la mensualité).
    `;
  } else {
    explainerBox.innerHTML = `
      Vous partagez équitablement le montant exigé sur le compte commun LCL et Revolut.<br>
      En parallèle, <strong>${borrower.name}</strong> effectue un virement mensuel permanent de <strong>${fmtPayment} / mois</strong> depuis son compte bancaire personnel vers le compte bancaire personnel de <strong>${lender.name}</strong>.
    `;
  }
}

function updateSimulatorAmortization(loan) {
  const trackMonths = document.getElementById('track-total-months');
  if (trackMonths) trackMonths.textContent = loan.totalMonths;

  const counterBadge = document.getElementById('sim-counter-badge');
  if (counterBadge) counterBadge.innerHTML = `Mois ${loan.monthsPaid} / <span id="track-total-months">${loan.totalMonths}</span>`;

  const progressPct = loan.totalCost > 0 ? (loan.capitalPaidSoFar / loan.totalCost) * 100 : 0;
  const progressFill = document.getElementById('sim-progress-fill');
  if (progressFill) progressFill.style.width = `${Math.max(1, progressPct)}%`;

  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('sim-paid-so-far', formatCurrency(loan.capitalPaidSoFar));
  setEl('sim-remaining-capital', formatCurrency(loan.remainingCapital));
  setEl('sim-time-remaining-label', loan.remainingTimeText);

  renderAmortizationTable(loan);
}

function updateSimulatorRealtime(activeInputId = null) {
  const data = calculateBudgets(appState);
  const { loan } = data;
  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();

  syncSimulatorInputs(activeInputId, loan);
  updateSimulatorMetricPills(loan, borrower, lender);
  updateSimulatorMathExplainer(loan, borrower, lender);
  updateDashboardCoupleCards(data);
  updateLclSplitDisplay(data);
  updateSimulatorAmortization(loan);
  updatePresetButtonStyles();

  saveStateDebounced();
}

function updatePresetButtonStyles() {
  const currentAmt = appState.simulator?.totalGift;
  const currentShare = appState.simulator?.userShare;
  const currentDur = appState.simulator?.durationYears;

  document.querySelectorAll('.preset-btn').forEach(btn => {
    const target = btn.dataset.target || (btn.dataset.amount ? 'amount' : null);
    const val = Number(btn.dataset.val || btn.dataset.amount);

    let isActive = false;
    if (target === 'amount' && val === currentAmt) isActive = true;
    if (target === 'share' && val === currentShare) isActive = true;
    if (target === 'duration' && val === currentDur) isActive = true;

    if (isActive) btn.classList.add('active');
    else if (target) btn.classList.remove('active');
  });
}

function updateDashboardCoupleCards(data) {
  const { split, loan } = data;
  const p1 = split.p1;
  const p2 = split.p2;
  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();

  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  setEl('name-p1-label', `Contribution de ${p1.name}`);
  setEl('contrib-p1-total', formatCurrency(split.p1Total));
  setEl('contrib-p1-lcl', formatCurrency(split.p1Lcl));
  setEl('contrib-p1-rev', formatCurrency(split.p1Rev));

  setEl('name-p2-label', `Contribution de ${p2.name}`);
  setEl('contrib-p2-total', formatCurrency(split.p2Total));
  setEl('contrib-p2-lcl', formatCurrency(split.p2Lcl));
  setEl('contrib-p2-rev', formatCurrency(split.p2Rev));

  const fmtLoan = formatCurrency(loan.monthlyPayment);
  if (split.isP1Borrower) {
    setEl('contrib-p1-breakdown', `dont +${fmtLoan} prêt (${loan.monthsPaid}/${loan.totalMonths} m.)`);
    setEl('contrib-p1-loan', `+${fmtLoan}`);
    setEl('contrib-p2-breakdown', loan.method === 'offset' ? `compensation prêt déduite (-${fmtLoan})` : `reçoit ${fmtLoan} en direct`);
    setEl('contrib-p2-loan', `-${fmtLoan}`);
  } else {
    setEl('contrib-p2-breakdown', `dont +${fmtLoan} prêt (${loan.monthsPaid}/${loan.totalMonths} m.)`);
    setEl('contrib-p2-loan', `+${fmtLoan}`);
    setEl('contrib-p1-breakdown', loan.method === 'offset' ? `compensation prêt déduite (-${fmtLoan})` : `reçoit ${fmtLoan} en direct`);
    setEl('contrib-p1-loan', `-${fmtLoan}`);
  }

  const sumBoth = split.p1Total + split.p2Total;
  const p1Pct = sumBoth > 0 ? (split.p1Total / sumBoth) * 100 : 50;
  const p2Pct = 100 - p1Pct;
  const barP1 = document.getElementById('bar-p1');
  const barP2 = document.getElementById('bar-p2');
  if (barP1) barP1.style.width = `${p1Pct.toFixed(1)}%`;
  if (barP2) barP2.style.width = `${p2Pct.toFixed(1)}%`;

  const noticeEl = document.getElementById('equalizer-notice-text');
  if (!noticeEl) return;

  if (loan.method === 'offset') {
    noticeEl.innerHTML = `
      <strong>Équilibrage automatique sur les comptes :</strong> 
      ${p1.name} verse <strong>${formatCurrency(split.p1Total)}</strong> et ${p2.name} verse <strong>${formatCurrency(split.p2Total)}</strong>.<br>
      ${borrower.name} verse <strong>${formatCurrency(split.diffAmount)} de plus que ${lender.name}</strong> sur le compte commun LCL pour compenser exactement la mensualité de <strong>${fmtLoan}</strong>.<br>
      <small style="color:#94a3b8;">📅 Progression : <strong>${loan.monthsPaid}</strong> mensualités déjà réglées (${formatCurrency(loan.capitalPaidSoFar)}), reste <strong>${formatCurrency(loan.remainingCapital)}</strong>.</small>
    `;
  } else {
    noticeEl.innerHTML = `
      <strong>Équilibrage par virement direct :</strong> 
      Vous alimentez tous les deux les comptes communs selon la clé (${appState.splitRule === '50' ? '50/50' : 'prorata'}), et ${borrower.name} verse séparément <strong>${fmtLoan}</strong> sur le compte personnel de ${lender.name}.
    `;
  }
}

function updateLclSplitDisplay(data) {
  const { split, loan, lclTotal } = data;
  const p1 = split.p1;
  const p2 = split.p2;

  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  setEl('lcl-p1-name', `Part mensuelle de ${p1.name}`);
  setEl('lcl-p1-amount', formatCurrency(split.p1Lcl));
  setEl('lcl-p2-name', `Part mensuelle de ${p2.name}`);
  setEl('lcl-p2-amount', formatCurrency(split.p2Lcl));

  const fmtLoan = formatCurrency(loan.monthlyPayment);
  if (loan.method === 'offset') {
    if (split.isP1Borrower) {
      setEl('lcl-p1-sub', `Base (${formatCurrency(lclTotal * data.p1Ratio)}) + Prêt (+${fmtLoan})`);
      setEl('lcl-p2-sub', `Base (${formatCurrency(lclTotal * data.p2Ratio)}) - Prêt (-${fmtLoan})`);
    } else {
      setEl('lcl-p1-sub', `Base (${formatCurrency(lclTotal * data.p1Ratio)}) - Prêt (-${fmtLoan})`);
      setEl('lcl-p2-sub', `Base (${formatCurrency(lclTotal * data.p2Ratio)}) + Prêt (+${fmtLoan})`);
    }
  } else {
    setEl('lcl-p1-sub', `Quote-part exacte (${(data.p1Ratio * 100).toFixed(0)}%)`);
    setEl('lcl-p2-sub', `Quote-part exacte (${(data.p2Ratio * 100).toFixed(0)}%)`);
  }
}

function renderAmortizationTable(loan) {
  const tbody = document.getElementById('sim-amort-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  const years = appState.simulator?.durationYears || 15;
  const startY = appState.simulator?.startMonth ? parseInt(appState.simulator.startMonth.split('-')[0]) : 2026;

  for (let y = 1; y <= years; y++) {
    const calendarYear = startY + (y - 1);
    const monthsPaidInYear = Math.max(0, Math.min(12, loan.monthsPaid - ((y - 1) * 12)));
    const monthsCumulativeAtYearEnd = Math.min(loan.totalMonths, y * 12);
    const paidAtYearEnd = loan.monthlyPayment * monthsCumulativeAtYearEnd;
    const remainingAtYearEnd = Math.max(0, loan.totalCost - paidAtYearEnd);

    let statusHtml = '';
    if (loan.monthsPaid >= y * 12) {
      statusHtml = `<span class="status-paid">✓ Déjà payé</span>`;
    } else if (loan.monthsPaid > (y - 1) * 12) {
      statusHtml = `<span class="status-current">En cours (${monthsPaidInYear}/12 m)</span>`;
    } else {
      statusHtml = `<span class="status-future">À venir</span>`;
    }

    if (y <= 4 || y === Math.round(years / 2) || y === years) {
      const tr = document.createElement('tr');
      tr.innerHTML = `
        <td><strong>Année ${y} <span style="font-size:0.75rem; color:var(--text-dim);">(${calendarYear})</span></strong></td>
        <td>${formatCurrency(loan.monthlyPayment)}/m</td>
        <td>${formatCurrency(paidAtYearEnd)}</td>
        <td class="font-mono text-amber">${formatCurrency(remainingAtYearEnd)}</td>
        <td class="text-center">${statusHtml}</td>
      `;
      tbody.appendChild(tr);
    }
  }
}

// ============================================================
// 3. RENOVATION (TRAVAUX) SECTION (REFACTORED)
// ============================================================

function updateLotsKpiStrip(m) {
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('lots-kpi-total-budget', formatCurrency(m.initialBudget));
  setEl('lots-kpi-total-spent', formatCurrency(m.totalConsumed));
  setEl('lots-kpi-total-spent-pct', `(${m.percentageConsumed.toFixed(1)}%)`);
  setEl('lots-kpi-total-remaining', formatCurrency(m.remainingBudget));
  setEl('lots-kpi-total-rem-pct', `(${m.percentageRemaining.toFixed(1)}%)`);
  setEl('lots-kpi-axel', formatCurrency(m.totalAxel));
  setEl('lots-kpi-ariane', formatCurrency(m.totalAriane));
}

function createLotRowElement(lot) {
  const tr = document.createElement('tr');
  tr.className = 'lot-row';

  const { label: statusLabel, cssClass: statusClass } = getLotStatusConfig(lot.status);
  const remainingHtml = getLotRemainingHtml(lot.remaining, lot.allocatedBudget);
  const { barColor, clampedPct } = getLotProgressBarConfig(lot.percentConsumed, lot.isOverBudget);
  const metaText = lot.notes || (lot.itemsCount > 0 ? `${lot.itemsCount} dépense${lot.itemsCount > 1 ? 's' : ''} notée${lot.itemsCount > 1 ? 's' : ''}` : 'Aucune dépense notée');

  tr.innerHTML = `
    <td>
      <div class="lot-cell">
        <div class="lot-icon-wrap">${lot.icon || '🔨'}</div>
        <div>
          <div class="lot-name">${lot.name}</div>
          <div class="lot-meta">${metaText}</div>
        </div>
      </div>
    </td>
    <td class="text-center">
      <span class="lot-status-pill ${statusClass}">${statusLabel}</span>
    </td>
    <td class="text-right font-mono" style="font-weight: 700;">${formatCurrency(lot.allocatedBudget)}</td>
    <td class="text-right font-mono ${lot.totalSpent > 0 ? 'text-amber' : 'text-dim'}" style="font-weight: 700;">
      ${lot.totalSpent > 0 ? formatCurrency(lot.totalSpent) : '—'}
    </td>
    <td class="text-right font-mono text-cyan" style="font-weight: 600;">
      ${lot.spentAxel > 0 ? formatCurrency(lot.spentAxel) : '—'}
    </td>
    <td class="text-right font-mono text-violet" style="font-weight: 600;">
      ${lot.spentAriane > 0 ? formatCurrency(lot.spentAriane) : '—'}
    </td>
    <td class="text-right">${remainingHtml}</td>
    <td>
      <div class="lot-progress-cell">
        <div class="lot-progress-bar">
          <div class="lot-progress-fill" style="width: ${clampedPct.toFixed(1)}%; background-color: ${barColor};"></div>
        </div>
        <span class="lot-progress-label ${lot.isOverBudget ? 'text-rose' : ''}">
          ${lot.percentConsumed.toFixed(1)}%
        </span>
      </div>
    </td>
    <td class="text-center">
      <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
        <button type="button" class="lcl-actions-btn btn-edit-lot" data-id="${lot.id}" title="Modifier ce lot">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </button>
        <button type="button" class="delete-btn btn-delete-lot" data-id="${lot.id}" title="Supprimer ce lot">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
      </div>
    </td>
  `;
  return tr;
}

function updateLotsTableFooter(m) {
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('tfoot-lots-budget', formatCurrency(m.initialBudget));
  setEl('tfoot-lots-spent', formatCurrency(m.totalConsumed));
  setEl('tfoot-lots-axel', formatCurrency(m.totalAxel));
  setEl('tfoot-lots-ariane', formatCurrency(m.totalAriane));

  const footRem = document.getElementById('tfoot-lots-remaining');
  if (footRem) {
    const isNeg = m.remainingBudget < 0;
    footRem.textContent = `${isNeg ? '-' : '+'}${formatCurrency(Math.abs(m.remainingBudget))}`;
    footRem.className = `text-right font-mono ${isNeg ? 'text-rose' : 'text-emerald'}`;
  }

  const footFill = document.getElementById('tfoot-lots-progress-fill');
  if (footFill) footFill.style.width = `${Math.min(100, m.percentageConsumed).toFixed(2)}%`;
  setEl('tfoot-lots-progress-pct', `${m.percentageConsumed.toFixed(1)}%`);
}

function renderTravauxLotsTable(m) {
  const tbody = document.getElementById('travaux-lots-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  updateLotsKpiStrip(m);
  for (const lot of (m.lots || [])) {
    tbody.appendChild(createLotRowElement(lot));
  }
  updateLotsTableFooter(m);
}

function exportTravauxCsv() {
  const m = calculateTravauxMetrics(appState.travauxFund, appState.profiles, appState.splitRule);
  const csv = generateTravauxCsvContent(m);
  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const url = URL.createObjectURL(blob);
  const dlAnchor = document.createElement('a');
  dlAnchor.setAttribute('href', url);
  dlAnchor.setAttribute('download', `Suivi_Travaux_Renovation_50k_${new Date().toISOString().slice(0, 10)}.csv`);
  document.body.appendChild(dlAnchor);
  dlAnchor.click();
  dlAnchor.remove();
  URL.revokeObjectURL(url);
}

function filterTravauxItems(items, query, filterStatus) {
  const q = (query || '').toLowerCase().trim();
  return (items || []).filter(item => {
    if (filterStatus === 'unreimbursed' && item.reimbursed) return false;
    if (filterStatus === 'reimbursed' && !item.reimbursed) return false;
    if (!q) return true;
    const matchReason = (item.reason || '').toLowerCase().includes(q);
    const matchNotes = (item.notes || '').toLowerCase().includes(q);
    return matchReason || matchNotes;
  });
}

function createTravauxItemRow(item, lot) {
  const totalItem = (Number(item.amountAxel) || 0) + (Number(item.amountAriane) || 0);
  const lotTagHtml = lot ? `<span class="badge-tag" style="background: rgba(14, 165, 233, 0.12); color: #38bdf8; font-size: 0.72rem; margin-left: 6px; padding: 2px 7px; border-radius: 6px;">${lot.icon || '🔨'} ${lot.name.split('(')[0].trim()}</span>` : '';

  const tr = document.createElement('tr');
  tr.innerHTML = `
    <td style="white-space: nowrap; font-size:0.85rem;">${formatDateFR(item.date)}</td>
    <td>
      <div style="font-weight: 700; color: #ffffff; display: flex; align-items: center; flex-wrap: wrap; gap: 4px;">
        <span>${item.reason}</span>
        ${lotTagHtml}
      </div>
      ${item.notes ? `<div style="font-size:0.75rem; color:var(--text-dim); margin-top:2px;">${item.notes}</div>` : ''}
    </td>
    <td class="text-right font-mono" style="font-weight: 700; color: ${item.amountAxel > 0 ? '#38bdf8' : 'var(--text-dim)'};">
      ${item.amountAxel > 0 ? formatCurrency(item.amountAxel) : '—'}
    </td>
    <td class="text-right font-mono" style="font-weight: 700; color: ${item.amountAriane > 0 ? '#c084fc' : 'var(--text-dim)'};">
      ${item.amountAriane > 0 ? formatCurrency(item.amountAriane) : '—'}
    </td>
    <td class="text-right font-mono" style="font-weight: 800; font-size: 0.95rem; color: #ffffff;">
      ${formatCurrency(totalItem)}
    </td>
    <td class="text-center">
      <button type="button" class="reimbursed-toggle-btn ${item.reimbursed ? 'status-yes' : 'status-no'} btn-toggle-reimbursed" data-id="${item.id}" title="Changer le statut de remboursement">
        ${item.reimbursed ? '✓ Remboursé' : 'Non remboursé'}
      </button>
    </td>
    <td class="text-center">
      <div style="display:flex; align-items:center; justify-content:center; gap:6px;">
        <button type="button" class="lcl-actions-btn btn-edit-trv" data-id="${item.id}" title="Modifier">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
        </button>
        <button type="button" class="delete-btn btn-delete-trv" data-id="${item.id}" title="Supprimer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
      </div>
    </td>
  `;
  return tr;
}

function updateTravauxFilterCounters(m) {
  const unreimbursedCount = m.items.filter(x => !x.reimbursed).length;
  const reimbursedCount = m.items.filter(x => x.reimbursed).length;

  const countAll = document.getElementById('count-trv-all');
  const countUnreimbursed = document.getElementById('count-trv-unreimbursed');
  const countReimbursed = document.getElementById('count-trv-reimbursed');

  if (countAll) countAll.textContent = m.items.length;
  if (countUnreimbursed) countUnreimbursed.textContent = unreimbursedCount;
  if (countReimbursed) countReimbursed.textContent = reimbursedCount;

  return { unreimbursedCount, reimbursedCount };
}

function updateTravauxFooter(m, unreimbursedCount) {
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('tfoot-axel-total', formatCurrency(m.totalAxel));
  setEl('tfoot-ariane-total', formatCurrency(m.totalAriane));
  setEl('tfoot-consumed-total', formatCurrency(m.totalConsumed));
  setEl('tfoot-remaining-total', formatCurrency(m.remainingBudget));
  setEl('tfoot-initial-display', formatCurrency(m.initialBudget));
  setEl('tfoot-consumed-display', formatCurrency(m.totalConsumed));

  const footStatus = document.getElementById('tfoot-status-summary');
  if (footStatus) {
    footStatus.textContent = `${unreimbursedCount} non remboursé${unreimbursedCount > 1 ? 's' : ''}`;
  }
}

function renderTravauxTable(m) {
  const tbody = document.getElementById('travaux-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  const { unreimbursedCount } = updateTravauxFilterCounters(m);
  const filtered = filterTravauxItems(m.items, activeTravauxSearch, activeTravauxFilter);

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-dim" style="padding: 28px;">Aucune dépense travaux ne correspond aux critères de recherche.</td></tr>`;
  } else {
    for (const item of filtered) {
      const lot = resolveItemLot(item, m.lots);
      tbody.appendChild(createTravauxItemRow(item, lot));
    }
  }

  updateTravauxFooter(m, unreimbursedCount);
}

function renderTravauxDashboardWidget(m) {
  const dashBudget = document.getElementById('dash-trv-budget');
  if (!dashBudget) return;

  dashBudget.textContent = formatCurrency(m.initialBudget);
  document.getElementById('dash-trv-consumed').textContent = formatCurrency(m.totalConsumed);
  document.getElementById('dash-trv-consumed-pct').textContent = `${m.percentageConsumed.toFixed(1)}% du budget`;
  document.getElementById('dash-trv-remaining').textContent = formatCurrency(m.remainingBudget);
  document.getElementById('dash-trv-remaining-pct').textContent = `${m.percentageRemaining.toFixed(1)}% restant`;

  document.getElementById('dash-trv-p1').textContent = `Axel : ${formatCurrency(m.totalAxel)}`;
  document.getElementById('dash-trv-p2').textContent = `Ariane : ${formatCurrency(m.totalAriane)}`;

  const deltaEl = document.getElementById('dash-trv-delta');
  if (deltaEl) {
    if (m.advanceDelta >= 0) {
      deltaEl.textContent = `Ariane a avancé +${formatCurrency(m.advanceDelta)}`;
      deltaEl.className = 'stat-subtext text-emerald';
    } else {
      deltaEl.textContent = `Axel a avancé +${formatCurrency(Math.abs(m.advanceDelta))}`;
      deltaEl.className = 'stat-subtext text-cyan';
    }
  }

  const barAxel = document.getElementById('dash-trv-bar-axel');
  const barAriane = document.getElementById('dash-trv-bar-ariane');
  if (barAxel) barAxel.style.width = `${m.axelBudgetPct.toFixed(2)}%`;
  if (barAriane) barAriane.style.width = `${m.arianeBudgetPct.toFixed(2)}%`;

  document.getElementById('dash-trv-legend-axel').textContent = formatCurrency(m.totalAxel);
  document.getElementById('dash-trv-legend-ariane').textContent = formatCurrency(m.totalAriane);
  document.getElementById('dash-trv-legend-rem').textContent = formatCurrency(m.remainingBudget);

  const topTrvTag = document.getElementById('top-travaux-tag');
  if (topTrvTag) topTrvTag.textContent = `Reste ${formatCurrency(m.remainingBudget)}`;
  const topTrvCons = document.getElementById('top-travaux-consumed');
  if (topTrvCons) topTrvCons.textContent = formatCurrency(m.totalConsumed);
  const topTrvSub = document.getElementById('top-travaux-subtext');
  if (topTrvSub) topTrvSub.textContent = `consommé sur ${formatCurrency(m.initialBudget)}`;
}

function renderTravauxKpiAndBars(m) {
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  setEl('trv-kpi-total-budget', formatCurrency(m.initialBudget));
  setEl('trv-kpi-consumed', formatCurrency(m.totalConsumed));
  setEl('trv-kpi-consumed-badge', `${m.percentageConsumed.toFixed(1)}% engagé`);
  setEl('trv-kpi-items-count', `Sur ${m.items.length} factures / acomptes`);
  setEl('trv-kpi-remaining', formatCurrency(m.remainingBudget));
  setEl('trv-kpi-remaining-badge', `${m.percentageRemaining.toFixed(1)}% dispo`);

  const deltaValEl = document.getElementById('trv-kpi-delta');
  const deltaSubEl = document.getElementById('trv-kpi-delta-sub');
  if (deltaValEl && deltaSubEl) {
    const isArianeLeading = m.advanceDelta >= 0;
    deltaValEl.textContent = `+${formatCurrency(Math.abs(m.advanceDelta))}`;
    deltaValEl.className = `kpi-value font-mono ${isArianeLeading ? 'text-violet' : 'text-cyan'}`;
    deltaSubEl.textContent = isArianeLeading ? "Ariane a avancé plus qu'Axel" : "Axel a avancé plus qu'Ariane";
  }

  setEl('trv-visual-status', `${formatCurrency(m.totalConsumed)} consommés • ${formatCurrency(m.remainingBudget)} disponibles`);

  const barAxel = document.getElementById('trv-bar-axel');
  const barAriane = document.getElementById('trv-bar-ariane');
  const barRem = document.getElementById('trv-bar-remaining');
  if (barAxel) barAxel.style.width = `${m.axelBudgetPct.toFixed(2)}%`;
  if (barAriane) barAriane.style.width = `${m.arianeBudgetPct.toFixed(2)}%`;
  if (barRem) barRem.style.width = `${Math.max(0, m.remainingBudgetPct).toFixed(2)}%`;

  setEl('trv-legend-axel-val', formatCurrency(m.totalAxel));
  setEl('trv-legend-axel-pct', `${m.axelShareOfConsumed.toFixed(1)}% du consommé • ${m.axelBudgetPct.toFixed(1)}% du budget`);
  setEl('trv-legend-ariane-val', formatCurrency(m.totalAriane));
  setEl('trv-legend-ariane-pct', `${m.arianeShareOfConsumed.toFixed(1)}% du consommé • ${m.arianeBudgetPct.toFixed(1)}% du budget`);
  setEl('trv-legend-rem-val', formatCurrency(m.remainingBudget));
  setEl('trv-legend-rem-pct', `${m.percentageRemaining.toFixed(1)}% du budget restant`);
}

function renderTravauxSettlement(m) {
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  setEl('trv-reimburse-axel', formatCurrency(m.totalAxel));
  setEl('trv-reimburse-axel-status', m.unreimbursedAxel > 0 ? `À percevoir : ${formatCurrency(m.unreimbursedAxel)}` : '✓ Entièrement remboursé');
  setEl('trv-reimburse-ariane', formatCurrency(m.totalAriane));
  setEl('trv-reimburse-ariane-status', m.unreimbursedAriane > 0 ? `À percevoir : ${formatCurrency(m.unreimbursedAriane)}` : '✓ Entièrement remboursé');
  setEl('trv-reimburse-total', formatCurrency(m.totalConsumed));
  setEl('trv-reimburse-after-rem', formatCurrency(m.remainingBudget));

  setEl('trv-eq-target-50', formatCurrency(m.target50Share));
  const diffAxel50 = m.axelOwesAriane50;
  const eqAxelEl = document.getElementById('trv-eq-paid-axel');
  const eqArianeEl = document.getElementById('trv-eq-paid-ariane');
  const eqVerdictEl = document.getElementById('trv-eq-verdict-text');

  if (!eqAxelEl || !eqArianeEl || !eqVerdictEl) return;

  if (diffAxel50 > 0.005) {
    eqAxelEl.innerHTML = `${formatCurrency(m.totalAxel)} <small class="text-rose">(-${formatCurrency(diffAxel50)})</small>`;
    eqArianeEl.innerHTML = `${formatCurrency(m.totalAriane)} <small class="text-emerald">(+${formatCurrency(diffAxel50)})</small>`;
    eqVerdictEl.innerHTML = `<strong>Axel doit virer ${formatCurrency(diffAxel50)} à Ariane</strong> pour atteindre une égalité parfaite (${formatCurrency(m.target50Share)} chacun).`;
  } else if (diffAxel50 < -0.005) {
    const oweAriane = Math.abs(diffAxel50);
    eqAxelEl.innerHTML = `${formatCurrency(m.totalAxel)} <small class="text-emerald">(+${formatCurrency(oweAriane)})</small>`;
    eqArianeEl.innerHTML = `${formatCurrency(m.totalAriane)} <small class="text-rose">(-${formatCurrency(oweAriane)})</small>`;
    eqVerdictEl.innerHTML = `<strong>Ariane doit virer ${formatCurrency(oweAriane)} à Axel</strong> pour atteindre une égalité parfaite (${formatCurrency(m.target50Share)} chacun).`;
  } else {
    eqAxelEl.innerHTML = `${formatCurrency(m.totalAxel)} <small class="text-emerald">(Égalité)</small>`;
    eqArianeEl.innerHTML = `${formatCurrency(m.totalAriane)} <small class="text-emerald">(Égalité)</small>`;
    eqVerdictEl.innerHTML = `<strong>Parité parfaite !</strong> Axel et Ariane ont chacun avancé exactement la même somme (${formatCurrency(m.target50Share)}).`;
  }
}

function renderTravauxTab(m) {
  const trvPane = document.getElementById('pane-travaux');
  if (!trvPane) return;

  renderTravauxKpiAndBars(m);
  renderTravauxSettlement(m);
  renderTravauxLotsTable(m);
  renderTravauxTable(m);
}

// ============================================================
// 4. LCL & REVOLUT VIEW RENDERERS
// ============================================================

function getItemIcon(label) {
  const l = (label || '').toLowerCase();
  if (l.includes('prêt') || l.includes('immo')) return '🏠';
  if (l.includes('assurance')) return '🛡️';
  if (l.includes('taxe')) return '📋';
  if (l.includes('travaux') || l.includes('entretien')) return '🔨';
  return '🏦';
}

function createLclItemRow(item) {
  const annualVal = item.amount * 12;
  const row = document.createElement('div');
  row.className = 'lcl-item-row';
  row.innerHTML = `
    <div class="lcl-item-left">
      <div class="lcl-item-icon">
        ${getItemIcon(item.label)}
      </div>
      <div class="lcl-item-info">
        <h4>${item.label}</h4>
        <p>${item.notes || ''}</p>
      </div>
    </div>
    <div class="lcl-item-right">
      <div>
        <div class="lcl-item-price">${formatCurrency(item.amount)} / mois</div>
        <span class="lcl-item-annual">${formatCurrency(annualVal)} / an</span>
      </div>
      <button class="lcl-actions-btn delete-btn btn-delete-lcl" data-id="${item.id}" title="Supprimer">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
      </button>
    </div>
  `;
  return row;
}

function renderLclStackedBar(total) {
  const stack = document.getElementById('lcl-progress-stack');
  const legend = document.getElementById('lcl-stack-legend');
  if (!stack || !legend) return;
  stack.innerHTML = '';
  legend.innerHTML = '';

  if (total <= 0) return;
  const colors = ['#0ea5e9', '#6366f1', '#8b5cf6', '#f59e0b', '#10b981', '#ec4899'];

  (appState.lclItems || []).forEach((item, idx) => {
    const color = colors[idx % colors.length];
    const pct = (item.amount / total) * 100;

    const seg = document.createElement('div');
    seg.className = 'stack-segment';
    seg.style.width = `${pct}%`;
    seg.style.backgroundColor = color;
    seg.title = `${item.label}: ${formatCurrency(item.amount)} (${pct.toFixed(1)}%)`;
    stack.appendChild(seg);

    const leg = document.createElement('div');
    leg.className = 'legend-item';
    leg.innerHTML = `
      <div class="legend-label-wrap">
        <span class="legend-color-dot" style="background: ${color};"></span>
        <span style="font-size:0.75rem;">${item.label} (${pct.toFixed(0)}%)</span>
      </div>
    `;
    legend.appendChild(leg);
  });
}

function renderLclTab(data) {
  const { lclTotal, lclAnnual, creditSubtotal, taxeSubtotal, maintSubtotal } = data;

  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('lcl-banner-total', formatCurrency(lclTotal));
  setEl('lcl-banner-annual', `${formatCurrency(lclAnnual)} / an`);
  setEl('subtotal-credit-pack', formatCurrency(creditSubtotal));
  setEl('subtotal-taxe-pack', formatCurrency(taxeSubtotal));
  setEl('subtotal-maintenance-pack', formatCurrency(maintSubtotal));
  setEl('ps-credit-val', formatCurrency(creditSubtotal));
  setEl('ps-taxe-val', formatCurrency(taxeSubtotal));
  setEl('ps-maint-val', formatCurrency(maintSubtotal));
  setEl('ps-total-val', formatCurrency(lclTotal));
  setEl('lcl-pack-count-tag', `${appState.lclItems.length} postes fixes`);

  const tfAnnualInput = document.getElementById('input-tf-annual');
  const tfMonthlyInput = document.getElementById('input-tf-monthly');
  if (tfAnnualInput) tfAnnualInput.value = Math.round(taxeSubtotal * 12);
  if (tfMonthlyInput) tfMonthlyInput.value = Math.round(taxeSubtotal);

  const creditContainer = document.getElementById('lcl-credit-items');
  if (creditContainer) {
    creditContainer.innerHTML = '';
    appState.lclItems.filter(i => (i.category === 'credit' || !i.category)).forEach(item => {
      creditContainer.appendChild(createLclItemRow(item));
    });
  }

  const maintContainer = document.getElementById('lcl-maintenance-items');
  if (maintContainer) {
    maintContainer.innerHTML = '';
    appState.lclItems.filter(i => i.category === 'maintenance').forEach(item => {
      maintContainer.appendChild(createLclItemRow(item));
    });
  }

  updateLclSplitDisplay(data);
  renderLclStackedBar(lclTotal);
}

function renderRevolutExpensesTable() {
  const tbody = document.getElementById('revolut-expenses-tbody');
  if (!tbody) return;
  const catFilter = document.getElementById('filter-rev-cat')?.value || 'all';
  const payerFilter = document.getElementById('filter-rev-payer')?.value || 'all';

  tbody.innerHTML = '';

  const filtered = (appState.expenses || [])
    .filter(e => e.account === 'Revolut')
    .filter(e => catFilter === 'all' || e.category === catFilter)
    .filter(e => {
      if (payerFilter === 'all') return true;
      if (payerFilter.startsWith('Avancé par ')) {
        const pName = payerFilter.replace('Avancé par ', '');
        return e.payer === pName;
      }
      return e.payer === payerFilter;
    })
    .sort((a, b) => b.date.localeCompare(a.date));

  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="6" class="text-center text-dim" style="padding: 20px;">Aucune dépense trouvée avec ces filtres</td></tr>`;
    return;
  }

  filtered.forEach(e => {
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${formatDateFR(e.date)}</td>
      <td><strong>${e.desc}</strong></td>
      <td><span class="badge-tag" style="background: rgba(139, 92, 246, 0.15); color: #c084fc;">${e.category}</span></td>
      <td><span class="badge-tag" style="background: rgba(255,255,255,0.06);">${e.payer}</span></td>
      <td class="text-right font-mono" style="font-weight: 700;">${formatCurrency(e.amount)}</td>
      <td class="text-center">
        <button class="delete-btn btn-delete-expense" data-id="${e.id}" title="Supprimer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function createEnvelopeCardElement(env, currentMonthPrefix, expenses) {
  const spent = expenses
    .filter(e => e.account === 'Revolut' && e.category === env.name && e.date.startsWith(currentMonthPrefix))
    .reduce((sum, e) => sum + (Number(e.amount) || 0), 0);

  const pct = env.budget > 0 ? (spent / env.budget) * 100 : 0;
  const remaining = Math.max(0, env.budget - spent);
  const isOver = spent > env.budget;

  const item = document.createElement('div');
  item.className = 'envelope-item';
  item.innerHTML = `
    <div class="envelope-top">
      <div class="envelope-name-wrap">
        <span class="envelope-icon">${env.icon || '📦'}</span>
        <span class="envelope-name">${env.name}</span>
      </div>
      <div class="envelope-numbers">
        <strong>${formatCurrency(spent)}</strong> / ${formatCurrency(env.budget)}
      </div>
    </div>
    <div class="envelope-progress-bg">
      <div class="envelope-progress-bar" style="width: ${Math.min(100, pct)}%; background-color: ${isOver ? '#f43f5e' : (env.color || '#8b5cf6')};"></div>
    </div>
    <div class="envelope-footer">
      <span>${pct.toFixed(0)}% consommé</span>
      <span style="color: ${isOver ? '#f43f5e' : '#10b981'}; font-weight: 600;">
        ${isOver ? `Dépassement de +${formatCurrency(spent - env.budget)}` : `Reste ${formatCurrency(remaining)}`}
      </span>
    </div>
  `;
  return item;
}

function renderRevolutTab(data) {
  const { revolutBudgetTotal, revolutSpentThisMonth } = data;
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('rev-banner-total', formatCurrency(revolutBudgetTotal));
  setEl('rev-banner-spent', `Dépensé : ${formatCurrency(revolutSpentThisMonth)} ce mois`);

  const payerFilter = document.getElementById('filter-rev-payer');
  if (payerFilter) {
    const curPayerVal = payerFilter.value || 'all';
    payerFilter.innerHTML = `
      <option value="all">Payeur : Tous</option>
      <option value="Carte Revolut Commune">Carte Revolut Commune</option>
      ${appState.profiles.map(p => `<option value="${p.name}">Avancé par ${p.name}</option>`).join('')}
    `;
    payerFilter.value = curPayerVal;
  }

  const container = document.getElementById('revolut-envelopes-list');
  if (!container) return;
  container.innerHTML = '';
  const currentMonthPrefix = new Date().toISOString().slice(0, 7);

  (appState.revolutEnvelopes || []).forEach(env => {
    container.appendChild(createEnvelopeCardElement(env, currentMonthPrefix, appState.expenses || []));
  });

  renderRevolutExpensesTable();
}

function renderRecentExpensesTable() {
  const tbody = document.getElementById('recent-expenses-tbody');
  if (!tbody) return;
  tbody.innerHTML = '';

  const recent = [...(appState.expenses || [])]
    .sort((a, b) => b.date.localeCompare(a.date))
    .slice(0, 5);

  recent.forEach(e => {
    const isLcl = e.account === 'LCL';
    const tr = document.createElement('tr');
    tr.innerHTML = `
      <td>${formatDateFR(e.date)}</td>
      <td><strong>${e.desc}</strong></td>
      <td>
        <span class="bank-tag ${isLcl ? 'lcl-tag' : 'rev-tag'}">${e.account}</span>
      </td>
      <td><span class="badge-tag" style="background: rgba(255,255,255,0.06);">${e.category}</span></td>
      <td>${e.payer}</td>
      <td class="text-right font-mono" style="font-weight: 700; color: ${isLcl ? '#38bdf8' : '#ffffff'};">${formatCurrency(e.amount)}</td>
      <td class="text-center">
        <button class="delete-btn btn-delete-expense" data-id="${e.id}" title="Supprimer">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
        </button>
      </td>
    `;
    tbody.appendChild(tr);
  });
}

function renderProfilesTab() {
  const container = document.getElementById('profiles-container');
  if (!container) return;
  container.innerHTML = '';

  (appState.profiles || []).forEach((p, idx) => {
    const card = document.createElement('div');
    card.className = 'profile-edit-card';
    card.innerHTML = `
      <div class="profile-edit-header">
        <div style="display:flex; align-items:center; gap:12px;">
          <div class="profile-avatar-large" style="background:${p.color};">
            ${p.initials || p.name.slice(0, 2).toUpperCase()}
          </div>
          <div>
            <h4 style="font-size:0.95rem; font-weight:700;">Profil ${idx + 1} : ${p.name}</h4>
            <span class="profile-role-badge">${p.role || 'Membre'}</span>
          </div>
        </div>
      </div>
      <div class="form-group-row">
        <div class="form-field">
          <label>Prénom / Nom</label>
          <input type="text" class="custom-input profile-name-input" data-id="${p.id}" value="${p.name}">
        </div>
        <div class="form-field">
          <label>Revenu Net Mensuel (€)</label>
          <input type="number" step="50" class="custom-input profile-income-input" data-id="${p.id}" value="${p.income || 2500}">
        </div>
      </div>
    `;
    container.appendChild(card);
  });

  const r50 = document.getElementById('rule-50-50');
  const rIncome = document.getElementById('rule-income');
  if (r50) r50.checked = (appState.splitRule === '50');
  if (rIncome) rIncome.checked = (appState.splitRule === 'income');

  updateBankingStatusUI();
}

function updateBankingStatusUI() {
  const banking = appState.banking || {};
  const accounts = banking.accounts || [];
  const lclAcc = accounts.find(a => (a.institution_id || '').includes('LCL') || (a.institution || '') === 'lcl');
  const revAcc = accounts.find(a => (a.institution_id || '').includes('REVOLUT') || (a.institution || '') === 'revolut');

  const lclStatus = document.getElementById('banking-status-lcl');
  if (lclStatus) {
    if (lclAcc && lclAcc.status === 'connected') {
      lclStatus.innerHTML = `<span class="text-emerald" style="font-weight:600;">● Connecté</span> (${formatCurrency(lclAcc.last_balance || 0)})`;
    } else {
      lclStatus.textContent = 'Non connecté';
    }
  }

  const revStatus = document.getElementById('banking-status-revolut');
  if (revStatus) {
    if (revAcc && revAcc.status === 'connected') {
      revStatus.innerHTML = `<span class="text-emerald" style="font-weight:600;">● Connecté</span> (${formatCurrency(revAcc.last_balance || 0)})`;
    } else {
      revStatus.textContent = 'Non connecté';
    }
  }

  const lastSyncEl = document.getElementById('banking-last-sync-display');
  if (lastSyncEl) {
    lastSyncEl.textContent = banking.last_global_sync || 'Jamais';
  }
}


function renderSummaryStrip(data) {
  const { globalMonthlyBudget, globalAnnualBudget, lclTotal, revolutBudgetTotal, revolutSpentThisMonth } = data;
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };

  setEl('top-total-budget', formatCurrency(globalMonthlyBudget));
  setEl('top-total-annual', `${formatCurrency(globalAnnualBudget)} / an`);
  setEl('top-lcl-budget', formatCurrency(lclTotal));
  setEl('top-revolut-budget', formatCurrency(revolutBudgetTotal));
  setEl('top-revolut-spent', `${formatCurrency(revolutSpentThisMonth || 0)} dépensés ce mois`);

  // Update Automatic Transfers Table cells
  setEl('auto-lcl-p1', formatCurrency(data.p1Contrib?.lcl || 0));
  setEl('auto-lcl-p2', formatCurrency(data.p2Contrib?.lcl || 0));
  setEl('auto-rev-p1', formatCurrency(data.p1Contrib?.rev || 0));
  setEl('auto-rev-p2', formatCurrency(data.p2Contrib?.rev || 0));
  setEl('auto-total-p1', formatCurrency(data.p1Contrib?.total || 0));
  setEl('auto-total-p2', formatCurrency(data.p2Contrib?.total || 0));

  const p1 = appState.profiles[0];
  const p2 = appState.profiles[1];
  const strip = document.getElementById('header-profiles-display');
  if (strip && p1 && p2) {
    strip.innerHTML = `
      <div class="profile-chip">
        <span class="chip-avatar" style="background:${p1.color}">${p1.initials || 'P1'}</span>
        <span>${p1.name}</span>
      </div>
      <span class="chip-sep">&</span>
      <div class="profile-chip">
        <span class="chip-avatar" style="background:${p2.color}">${p2.initials || 'P2'}</span>
        <span>${p2.name}</span>
      </div>
    `;
  }
}

function renderLclVsRevolutDonut(lcl, rev) {
  const svg = document.getElementById('global-donut-svg') || document.getElementById('donut-svg');
  const legend = document.getElementById('global-donut-legend') || document.getElementById('donut-legend');
  const centerVal = document.getElementById('donut-center-total');
  
  const total = lcl + rev;
  if (centerVal) {
    centerVal.textContent = formatCurrency(total);
  }

  // Update annual projections
  const setEl = (id, val) => {
    const el = document.getElementById(id);
    if (el) el.textContent = val;
  };
  setEl('forecast-lcl-annual', formatCurrency(lcl * 12));
  setEl('forecast-revolut-annual', formatCurrency(rev * 12));
  setEl('forecast-total-annual', formatCurrency(total * 12));

  if (!svg || !legend) return;

  if (total <= 0) {
    svg.innerHTML = '';
    legend.innerHTML = '<span class="text-dim">Aucune donnée</span>';
    return;
  }

  const lclPct = (lcl / total) * 100;
  const revPct = (rev / total) * 100;
  const lclDash = `${lclPct.toFixed(2)} ${(100 - lclPct).toFixed(2)}`;
  const revDash = `${revPct.toFixed(2)} ${(100 - revPct).toFixed(2)}`;
  const revOffset = -lclPct;

  svg.innerHTML = `
    <circle cx="50" cy="50" r="38" pathLength="100" fill="transparent" stroke="rgba(255,255,255,0.06)" stroke-width="8"></circle>
    <circle cx="50" cy="50" r="38" pathLength="100" fill="transparent" stroke="#38bdf8" stroke-width="8" stroke-dasharray="${lclDash}" stroke-dashoffset="0" transform="rotate(-90 50 50)"></circle>
    <circle cx="50" cy="50" r="38" pathLength="100" fill="transparent" stroke="#818cf8" stroke-width="8" stroke-dasharray="${revDash}" stroke-dashoffset="${revOffset.toFixed(2)}" transform="rotate(-90 50 50)"></circle>
  `;

  legend.innerHTML = `
    <div class="legend-item">
      <div class="legend-label-wrap">
        <span class="legend-color-dot" style="background: #38bdf8;"></span>
        <span>LCL Package Immo Statique (${lclPct.toFixed(0)}%)</span>
      </div>
      <span class="legend-val text-cyan">${formatCurrency(lcl)}</span>
    </div>
    <div class="legend-item">
      <div class="legend-label-wrap">
        <span class="legend-color-dot" style="background: #818cf8;"></span>
        <span>Revolut Vie Commune (${revPct.toFixed(0)}%)</span>
      </div>
      <span class="legend-val text-violet">${formatCurrency(rev)}</span>
    </div>
  `;
}

function renderSimulatorView(data) {
  const borrower = getBorrowerProfile();
  const lender = getLenderProfile();

  const bSelect = document.getElementById('select-loan-borrower');
  const lSelect = document.getElementById('select-loan-lender');

  if (bSelect) {
    bSelect.innerHTML = appState.profiles.map(p => 
      `<option value="${p.id}" ${p.id === appState.simulator?.borrowerId ? 'selected' : ''}>${p.name}</option>`
    ).join('');
  }

  if (lSelect) {
    lSelect.innerHTML = appState.profiles.map(p => 
      `<option value="${p.id}" ${p.id === appState.simulator?.lenderId ? 'selected' : ''}>${p.name}</option>`
    ).join('');
  }

  const heroDesc = document.getElementById('sim-hero-description');
  if (heroDesc) {
    heroDesc.innerHTML = `
      Prêt familial de <strong>${formatCurrency(appState.simulator?.totalGift)}</strong> consenti par <strong>${lender.name}</strong>. <strong>${borrower.name}</strong> rembourse sa part (${appState.simulator?.userShare}% = ${formatCurrency(appState.simulator?.totalGift * (appState.simulator?.userShare / 100))}) sur <strong>${appState.simulator?.durationYears} ans</strong> (${appState.simulator?.durationYears * 12} mois). Le nombre de mensualités déjà versées est calculé automatiquement depuis la date de départ jusqu'au mois actuel.
    `;
  }

  const directRadio = document.getElementById('method-direct');
  const offsetRadio = document.getElementById('method-offset');
  if (directRadio) directRadio.checked = (appState.simulator?.method === 'direct');
  if (offsetRadio) offsetRadio.checked = (appState.simulator?.method === 'offset');

  const fmtPayment = formatCurrency(data.loan.monthlyPayment);
  const descOffset = document.getElementById('method-desc-offset');
  const descDirect = document.getElementById('method-desc-direct');

  if (descOffset) {
    descOffset.innerHTML = `Zéro virement privé ! ${borrower.name} verse <strong>+${fmtPayment}</strong> de plus au compte joint LCL et ${lender.name} verse <strong>-${fmtPayment}</strong> de moins.`;
  }
  if (descDirect) {
    descDirect.innerHTML = `Vous alimentez selon la clé convenue le compte joint, et ${borrower.name} effectue un virement mensuel séparé de <strong>${fmtPayment}</strong> sur le compte perso de ${lender.name}.`;
  }

  updateSimulatorRealtime();
}

function renderAll() {
  const data = calculateBudgets(appState);
  const travauxMetrics = calculateTravauxMetrics(appState.travauxFund, appState.profiles, appState.splitRule);

  renderSummaryStrip(data);
  renderLclVsRevolutDonut(data.lclTotal, data.revolutBudgetTotal);
  renderSimulatorView(data);
  renderLclTab(data);
  renderRevolutTab(data);
  renderRecentExpensesTable();
  renderProfilesTab();
  renderTravauxDashboardWidget(travauxMetrics);
  renderTravauxTab(travauxMetrics);
}

// ============================================================
// 5. EVENT LISTENERS & ACTION DISPATCHER (REFACTORED)
// ============================================================

// Declarative click actions to eliminate high cyclomatic complexity
function handleToggleReimbursed(target) {
  const trvId = target.dataset.id;
  const item = (appState.travauxFund?.items || []).find(x => x.id === trvId);
  if (item) {
    item.reimbursed = !item.reimbursed;
    saveStateImmediate();
    renderAll();
  }
}

function handleEditTrv(target) {
  const trvId = target.dataset.id;
  const item = (appState.travauxFund?.items || []).find(x => x.id === trvId);
  if (item) openTravauxModal(item);
}

function handleDeleteTrv(target) {
  const trvId = target.dataset.id;
  if (confirm('Voulez-vous supprimer cette ligne de travaux ?')) {
    appState.travauxFund.items = appState.travauxFund.items.filter(x => x.id !== trvId);
    saveStateImmediate();
    renderAll();
  }
}

function handleEditLot(target) {
  const lotId = target.dataset.id;
  const lot = (appState.travauxFund?.lots || []).find(l => l.id === lotId);
  if (lot) openLotModal(lot);
}

function handleDeleteLot(target) {
  const lotId = target.dataset.id;
  if (confirm('Voulez-vous supprimer ce lot de travaux ?')) {
    appState.travauxFund.lots = (appState.travauxFund?.lots || []).filter(l => l.id !== lotId);
    saveStateImmediate();
    renderAll();
  }
}

function handleDeleteLcl(target) {
  const lclId = target.dataset.id;
  if (confirm('Supprimer cette charge fixe LCL ?')) {
    appState.lclItems = (appState.lclItems || []).filter(x => x.id !== lclId);
    saveStateImmediate();
    renderAll();
  }
}

function handleDeleteExpense(target) {
  const expId = target.dataset.id;
  if (confirm('Voulez-vous supprimer cette dépense ?')) {
    appState.expenses = (appState.expenses || []).filter(x => x.id !== expId);
    saveStateImmediate();
    renderAll();
  }
}

const CLICK_DISPATCHERS = [
  { selector: '.btn-toggle-reimbursed', handler: handleToggleReimbursed },
  { selector: '.btn-edit-trv', handler: handleEditTrv },
  { selector: '.btn-delete-trv', handler: handleDeleteTrv },
  { selector: '.btn-edit-lot', handler: handleEditLot },
  { selector: '.btn-delete-lot', handler: handleDeleteLot },
  { selector: '.btn-delete-lcl', handler: handleDeleteLcl },
  { selector: '.btn-delete-expense', handler: handleDeleteExpense }
];

// Travaux expense modal
const travauxModal = document.getElementById('travaux-modal');
const travauxForm = document.getElementById('travaux-form');

function populateTravauxModal(item, modalTitle, lotSelect) {
  if (item) {
    if (modalTitle) modalTitle.textContent = 'Modifier la dépense travaux';
    document.getElementById('modal-trv-id').value = item.id;
    document.getElementById('modal-trv-reason').value = item.reason || '';
    if (lotSelect && item.lotId) lotSelect.value = item.lotId;
    document.getElementById('modal-trv-date').value = item.date || new Date().toISOString().slice(0, 10);
    document.getElementById('modal-trv-reimbursed').value = item.reimbursed ? 'true' : 'false';
    document.getElementById('modal-trv-axel').value = (Number(item.amountAxel) || 0).toFixed(2);
    document.getElementById('modal-trv-ariane').value = (Number(item.amountAriane) || 0).toFixed(2);
    document.getElementById('modal-trv-notes').value = item.notes || '';
  } else {
    if (modalTitle) modalTitle.textContent = 'Ajouter une dépense travaux';
    document.getElementById('modal-trv-id').value = '';
    document.getElementById('modal-trv-date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('modal-trv-reimbursed').value = 'false';
    document.getElementById('modal-trv-axel').value = '0.00';
    document.getElementById('modal-trv-ariane').value = '0.00';
    document.getElementById('modal-trv-notes').value = '';
  }
}

function openTravauxModal(item = null) {
  if (!travauxModal || !travauxForm) return;
  travauxForm.reset();
  const modalTitle = document.getElementById('travaux-modal-title');
  const p1 = appState.profiles[0];
  const p2 = appState.profiles[1];

  const setLabel = (id, text) => {
    const el = document.getElementById(id);
    if (el) el.textContent = text;
  };
  setLabel('modal-trv-axel-label', `Avance ${p1.name} (€)`);
  setLabel('modal-trv-ariane-label', `Avance ${p2.name} (€)`);
  setLabel('btn-split-100-axel', `100% ${p1.name}`);
  setLabel('btn-split-100-ariane', `100% ${p2.name}`);

  const lotSelect = document.getElementById('modal-trv-lot');
  if (lotSelect) {
    const lots = appState.travauxFund?.lots || DEFAULT_STATE.travauxFund.lots;
    lotSelect.innerHTML = lots.map(l => 
      `<option value="${l.id}">${l.icon || '🔨'} ${l.name} (${formatCurrency(l.allocatedBudget)})</option>`
    ).join('');
  }

  populateTravauxModal(item, modalTitle, lotSelect);
  travauxModal.style.display = 'flex';
}

function closeTravauxModal() {
  if (travauxModal) travauxModal.style.display = 'none';
}

// Lot modal
const lotModal = document.getElementById('travaux-lot-modal');
const lotForm = document.getElementById('travaux-lot-form');

function openLotModal(lot = null) {
  if (!lotModal || !lotForm) return;
  lotForm.reset();
  const modalTitle = document.getElementById('travaux-lot-modal-title');
  if (lot) {
    if (modalTitle) modalTitle.textContent = 'Modifier le lot de travaux';
    document.getElementById('modal-lot-id').value = lot.id;
    document.getElementById('modal-lot-name').value = lot.name || '';
    document.getElementById('modal-lot-budget').value = lot.allocatedBudget || 0;
    document.getElementById('modal-lot-status').value = lot.status || 'ongoing';
    document.getElementById('modal-lot-icon').value = lot.icon || '🔨';
    document.getElementById('modal-lot-notes').value = lot.notes || '';
  } else {
    if (modalTitle) modalTitle.textContent = 'Nouveau lot de travaux';
    document.getElementById('modal-lot-id').value = '';
    document.getElementById('modal-lot-name').value = '';
    document.getElementById('modal-lot-budget').value = '2000';
    document.getElementById('modal-lot-status').value = 'ongoing';
    document.getElementById('modal-lot-icon').value = '🔨';
    document.getElementById('modal-lot-notes').value = '';
  }
  lotModal.style.display = 'flex';
}

function closeLotModal() {
  if (lotModal) lotModal.style.display = 'none';
}

// Initialization
document.addEventListener('DOMContentLoaded', () => {
  renderAll();

  // Navigation Tabs
  document.querySelectorAll('.nav-tab').forEach(tabBtn => {
    tabBtn.addEventListener('click', () => {
      document.querySelectorAll('.nav-tab').forEach(b => {
        b.classList.remove('active');
        b.setAttribute('aria-selected', 'false');
      });
      document.querySelectorAll('.tab-pane').forEach(p => p.classList.remove('active'));

      tabBtn.classList.add('active');
      tabBtn.setAttribute('aria-selected', 'true');
      const targetPane = document.getElementById(`pane-${tabBtn.dataset.tab}`);
      if (targetPane) targetPane.classList.add('active');
      if (window.location.hash !== `#${tabBtn.dataset.tab}`) {
        window.history.replaceState(null, '', `#${tabBtn.dataset.tab}`);
      }
    });
  });

  // Hash-based tab routing
  function handleHashNavigation() {
    const tabName = (window.location.hash || '').replace('#', '');
    if (tabName) {
      const targetBtn = document.querySelector(`.nav-tab[data-tab="${tabName}"]`);
      if (targetBtn) targetBtn.click();
    }
  }
  handleHashNavigation();
  window.addEventListener('hashchange', handleHashNavigation);

  // Quick jump buttons
  const jumpBtn = document.getElementById('btn-jump-to-simulator');
  if (jumpBtn) {
    jumpBtn.addEventListener('click', () => {
      const simTab = document.getElementById('tab-btn-simulator');
      if (simTab) simTab.click();
    });
  }
  const jumpSim = document.getElementById('card-quick-equalizer');
  if (jumpSim) jumpSim.addEventListener('click', () => document.getElementById('tab-btn-simulator')?.click());
  const jumpTrv = document.getElementById('card-quick-travaux');
  if (jumpTrv) jumpTrv.addEventListener('click', () => document.getElementById('tab-btn-travaux')?.click());
  const jumpTrv2 = document.getElementById('btn-jump-to-travaux');
  if (jumpTrv2) jumpTrv2.addEventListener('click', () => document.getElementById('tab-btn-travaux')?.click());
  const jumpTrv3 = document.getElementById('dash-travaux-card');
  if (jumpTrv3) jumpTrv3.addEventListener('click', () => document.getElementById('tab-btn-travaux')?.click());

  // Profile Net Incomes & Split Rule
  document.addEventListener('input', (e) => {
    if (e.target.classList.contains('profile-name-input')) {
      const p = appState.profiles.find(x => x.id === e.target.dataset.id);
      if (p) {
        p.name = e.target.value.trim() || p.name;
        saveStateImmediate();
        renderAll();
      }
    }
    if (e.target.classList.contains('profile-income-input')) {
      const p = appState.profiles.find(x => x.id === e.target.dataset.id);
      if (p) {
        p.income = parseFloat(e.target.value) || 0;
        saveStateImmediate();
        renderAll();
      }
    }
  });

  document.querySelectorAll('input[name="split-rule"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      appState.splitRule = e.target.value;
      saveStateImmediate();
      renderAll();
    });
  });

  // Simulator Sliders & Inputs
  const bindRealtime = (id, prop, isFloat = false) => {
    const el = document.getElementById(id);
    if (!el) return;
    el.addEventListener('input', (e) => {
      const val = isFloat ? parseFloat(e.target.value) : parseInt(e.target.value, 10);
      if (!isNaN(val)) {
        appState.simulator[prop] = val;
        updateSimulatorRealtime(id);
      }
    });
  };

  bindRealtime('slider-loan-amount', 'totalGift', true);
  bindRealtime('num-loan-amount', 'totalGift', true);
  bindRealtime('dash-slider-amount', 'totalGift', true);
  bindRealtime('slider-user-share', 'userShare', true);
  bindRealtime('num-user-share', 'userShare', true);
  bindRealtime('slider-loan-duration', 'durationYears', false);
  bindRealtime('slider-loan-interest', 'interestRate', true);
  bindRealtime('num-loan-interest', 'interestRate', true);

  const startMonthInput = document.getElementById('input-loan-start-month');
  if (startMonthInput) {
    startMonthInput.addEventListener('change', (e) => {
      appState.simulator.startMonth = e.target.value;
      delete appState.simulator.monthsPaid;
      updateSimulatorRealtime('input-loan-start-month');
    });
  }

  // Interactive Month Calendar Selector handlers
  document.querySelectorAll('.cal-month-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const year = document.getElementById('cal-selected-year')?.textContent.trim() || '2026';
      const m = btn.dataset.m;
      const startMonth = `${year}-${m}`;
      appState.simulator.startMonth = startMonth;
      delete appState.simulator.monthsPaid;
      if (startMonthInput) startMonthInput.value = startMonth;
      updateSimulatorRealtime('input-loan-start-month');
    });
  });

  const prevYearBtn = document.getElementById('btn-cal-year-prev');
  const nextYearBtn = document.getElementById('btn-cal-year-next');
  if (prevYearBtn) {
    prevYearBtn.addEventListener('click', () => {
      const calYear = document.getElementById('cal-selected-year');
      if (!calYear) return;
      const newYear = parseInt(calYear.textContent, 10) - 1;
      calYear.textContent = newYear;
      const [_, sMonth] = (appState.simulator.startMonth || '2026-03').split('-');
      const startMonth = `${newYear}-${sMonth || '03'}`;
      appState.simulator.startMonth = startMonth;
      delete appState.simulator.monthsPaid;
      if (startMonthInput) startMonthInput.value = startMonth;
      updateSimulatorRealtime('input-loan-start-month');
    });
  }
  if (nextYearBtn) {
    nextYearBtn.addEventListener('click', () => {
      const calYear = document.getElementById('cal-selected-year');
      if (!calYear) return;
      const newYear = parseInt(calYear.textContent, 10) + 1;
      calYear.textContent = newYear;
      const [_, sMonth] = (appState.simulator.startMonth || '2026-03').split('-');
      const startMonth = `${newYear}-${sMonth || '03'}`;
      appState.simulator.startMonth = startMonth;
      delete appState.simulator.monthsPaid;
      if (startMonthInput) startMonthInput.value = startMonth;
      updateSimulatorRealtime('input-loan-start-month');
    });
  }

  // Preset buttons (Amount, Share, Duration, Start Month)
  document.querySelectorAll('.preset-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      if (btn.dataset.start) {
        const now = new Date();
        const curY = now.getFullYear();
        const curM = String(now.getMonth() + 1).padStart(2, '0');
        if (btn.dataset.start === 'current') {
          appState.simulator.startMonth = `${curY}-${curM}`;
        } else if (btn.dataset.start === '6m') {
          const d = new Date(now.getFullYear(), now.getMonth() - 5, 1);
          appState.simulator.startMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        } else if (btn.dataset.start === '8m') {
          appState.simulator.startMonth = '2026-03';
        } else if (btn.dataset.start === '1y') {
          const d = new Date(now.getFullYear() - 1, now.getMonth() + 1, 1);
          appState.simulator.startMonth = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}`;
        } else if (btn.dataset.start === 'jan24') {
          appState.simulator.startMonth = '2024-01';
        }
        delete appState.simulator.monthsPaid;
        if (startMonthInput) startMonthInput.value = appState.simulator.startMonth;
        updateSimulatorRealtime('input-loan-start-month');
        return;
      }

      const target = btn.dataset.target || (btn.dataset.amount ? 'amount' : null);
      const val = Number(btn.dataset.val || btn.dataset.amount);

      if (target === 'amount') appState.simulator.totalGift = val;
      if (target === 'share') appState.simulator.userShare = val;
      if (target === 'duration') appState.simulator.durationYears = val;

      updateSimulatorRealtime();
    });
  });

  // Repayment method radio selector
  document.querySelectorAll('input[name="repay-method"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      appState.simulator.method = e.target.value;
      updateSimulatorRealtime();
    });
  });

  // Partner Roles
  const borrowerSelect = document.getElementById('select-loan-borrower');
  if (borrowerSelect) {
    borrowerSelect.addEventListener('change', (e) => {
      appState.simulator.borrowerId = e.target.value;
      updateSimulatorRealtime();
    });
  }

  const lenderSelect = document.getElementById('select-loan-lender');
  if (lenderSelect) {
    lenderSelect.addEventListener('change', (e) => {
      appState.simulator.lenderId = e.target.value;
      updateSimulatorRealtime();
    });
  }

  // Quick Expense Modal
  const expenseModal = document.getElementById('expense-modal');
  const btnQuickExpense = document.getElementById('btn-quick-expense');
  const btnAddRevExpense = document.getElementById('btn-add-revolut-expense');
  const btnCloseExpenseModal = document.getElementById('btn-close-modal');
  const btnCancelModal = document.getElementById('btn-cancel-modal');
  const expenseForm = document.getElementById('expense-form');

  function openExpenseModal(defaultAccount = 'Revolut') {
    if (!expenseModal || !expenseForm) return;
    expenseForm.reset();
    document.getElementById('modal-expense-date').value = new Date().toISOString().slice(0, 10);
    document.getElementById('modal-expense-account').value = defaultAccount;

    const payerSelect = document.getElementById('modal-expense-payer');
    if (payerSelect) {
      payerSelect.innerHTML = `
        <option value="Carte Revolut Commune">Carte Revolut Commune</option>
        <option value="Compte LCL">Compte LCL (Prélèvement)</option>
        ${appState.profiles.map(p => `<option value="${p.name}">Avancé par ${p.name}</option>`).join('')}
      `;
    }
    expenseModal.style.display = 'flex';
  }

  function closeExpenseModal() {
    if (expenseModal) expenseModal.style.display = 'none';
  }

  const btnBannerExpense = document.getElementById('btn-quick-expense-banner');
  if (btnBannerExpense) btnBannerExpense.addEventListener('click', () => openExpenseModal());
  if (btnQuickExpense) btnQuickExpense.addEventListener('click', () => openExpenseModal());
  if (btnAddRevExpense) btnAddRevExpense.addEventListener('click', () => openExpenseModal('Revolut'));
  if (btnCloseExpenseModal) btnCloseExpenseModal.addEventListener('click', closeExpenseModal);
  if (btnCancelModal) btnCancelModal.addEventListener('click', closeExpenseModal);

  if (expenseForm) {
    expenseForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const newExp = {
        id: 'exp-' + Date.now(),
        desc: document.getElementById('modal-expense-desc').value.trim(),
        amount: parseFloat(document.getElementById('modal-expense-amount').value) || 0,
        date: document.getElementById('modal-expense-date').value,
        account: document.getElementById('modal-expense-account').value,
        category: document.getElementById('modal-expense-cat').value,
        payer: document.getElementById('modal-expense-payer').value
      };
      appState.expenses.unshift(newExp);
      saveStateImmediate();
      closeExpenseModal();
      renderAll();
    });
  }

  // Travaux Modal Open/Close/Submit
  const btnOpenTravauxModal = document.getElementById('btn-open-travaux-modal');
  const btnAddTravauxRow = document.getElementById('btn-add-travaux-row');
  const btnCloseTravauxModal = document.getElementById('btn-close-travaux-modal');
  const btnCancelTravauxModal = document.getElementById('btn-cancel-travaux-modal');

  if (btnOpenTravauxModal) btnOpenTravauxModal.addEventListener('click', () => openTravauxModal());
  if (btnAddTravauxRow) btnAddTravauxRow.addEventListener('click', () => openTravauxModal());
  if (btnCloseTravauxModal) btnCloseTravauxModal.addEventListener('click', closeTravauxModal);
  if (btnCancelTravauxModal) btnCancelTravauxModal.addEventListener('click', closeTravauxModal);

  document.getElementById('btn-split-50-trv')?.addEventListener('click', () => {
    const ax = parseFloat(document.getElementById('modal-trv-axel').value) || 0;
    const ar = parseFloat(document.getElementById('modal-trv-ariane').value) || 0;
    let sum = ax + ar;
    if (sum === 0) {
      const asked = prompt('Montant total de la dépense travaux à diviser 50/50 (€) :', '1000');
      sum = parseFloat(asked) || 0;
    }
    const half = (sum / 2).toFixed(2);
    document.getElementById('modal-trv-axel').value = half;
    document.getElementById('modal-trv-ariane').value = half;
  });

  document.getElementById('btn-split-100-axel')?.addEventListener('click', () => {
    const ax = parseFloat(document.getElementById('modal-trv-axel').value) || 0;
    const ar = parseFloat(document.getElementById('modal-trv-ariane').value) || 0;
    const sum = ax + ar;
    document.getElementById('modal-trv-axel').value = sum.toFixed(2);
    document.getElementById('modal-trv-ariane').value = '0.00';
  });

  document.getElementById('btn-split-100-ariane')?.addEventListener('click', () => {
    const ax = parseFloat(document.getElementById('modal-trv-axel').value) || 0;
    const ar = parseFloat(document.getElementById('modal-trv-ariane').value) || 0;
    const sum = ax + ar;
    document.getElementById('modal-trv-axel').value = '0.00';
    document.getElementById('modal-trv-ariane').value = sum.toFixed(2);
  });

  if (travauxForm) {
    travauxForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const trvId = document.getElementById('modal-trv-id').value;
      const reasonVal = document.getElementById('modal-trv-reason').value.trim();
      const lotIdVal = document.getElementById('modal-trv-lot') ? document.getElementById('modal-trv-lot').value : '';
      const dateVal = document.getElementById('modal-trv-date').value;
      const reimbursedVal = (document.getElementById('modal-trv-reimbursed').value === 'true');
      const axelVal = parseFloat(document.getElementById('modal-trv-axel').value) || 0;
      const arianeVal = parseFloat(document.getElementById('modal-trv-ariane').value) || 0;
      const notesVal = document.getElementById('modal-trv-notes').value.trim();

      if (!appState.travauxFund) {
        appState.travauxFund = JSON.parse(JSON.stringify(DEFAULT_STATE.travauxFund));
      }

      if (trvId) {
        const target = appState.travauxFund.items.find(x => x.id === trvId);
        if (target) {
          target.reason = reasonVal;
          target.lotId = lotIdVal;
          target.date = dateVal;
          target.reimbursed = reimbursedVal;
          target.amountAxel = axelVal;
          target.amountAriane = arianeVal;
          target.notes = notesVal;
        }
      } else {
        const newItem = {
          id: 'trv-' + Date.now(),
          reason: reasonVal,
          lotId: lotIdVal,
          date: dateVal,
          reimbursed: reimbursedVal,
          amountAxel: axelVal,
          amountAriane: arianeVal,
          notes: notesVal
        };
        appState.travauxFund.items.push(newItem);
      }

      saveStateImmediate();
      closeTravauxModal();
      renderAll();
    });
  }

  // Travaux Lots Modal
  const btnAddTravauxLot = document.getElementById('btn-add-travaux-lot');
  const btnCloseLotModal = document.getElementById('btn-close-lot-modal');
  const btnCancelLotModal = document.getElementById('btn-cancel-lot-modal');

  if (btnAddTravauxLot) btnAddTravauxLot.addEventListener('click', () => openLotModal());
  if (btnCloseLotModal) btnCloseLotModal.addEventListener('click', closeLotModal);
  if (btnCancelLotModal) btnCancelLotModal.addEventListener('click', closeLotModal);

  if (lotForm) {
    lotForm.addEventListener('submit', (e) => {
      e.preventDefault();
      const lotId = document.getElementById('modal-lot-id').value;
      const nameVal = document.getElementById('modal-lot-name').value.trim();
      const budgetVal = parseFloat(document.getElementById('modal-lot-budget').value) || 0;
      const statusVal = document.getElementById('modal-lot-status').value;
      const iconVal = document.getElementById('modal-lot-icon').value.trim() || '🔨';
      const notesVal = document.getElementById('modal-lot-notes').value.trim();

      if (!appState.travauxFund.lots) {
        appState.travauxFund.lots = JSON.parse(JSON.stringify(DEFAULT_STATE.travauxFund.lots));
      }

      if (lotId) {
        const target = appState.travauxFund.lots.find(l => l.id === lotId);
        if (target) {
          target.name = nameVal;
          target.allocatedBudget = budgetVal;
          target.status = statusVal;
          target.icon = iconVal;
          target.notes = notesVal;
        }
      } else {
        const newLot = {
          id: 'lot-' + Date.now(),
          name: nameVal,
          allocatedBudget: budgetVal,
          status: statusVal,
          icon: iconVal,
          notes: notesVal
        };
        appState.travauxFund.lots.push(newLot);
      }

      saveStateImmediate();
      closeLotModal();
      renderAll();
    });
  }

  // Export CSV & Print
  const btnExportCsv = document.getElementById('btn-export-travaux-csv');
  if (btnExportCsv) btnExportCsv.addEventListener('click', exportTravauxCsv);

  const btnPrint = document.getElementById('btn-print-travaux');
  if (btnPrint) btnPrint.addEventListener('click', () => window.print());

  // Search & Filters
  const trvSearchInput = document.getElementById('travaux-search-input');
  if (trvSearchInput) {
    trvSearchInput.addEventListener('input', (e) => {
      activeTravauxSearch = e.target.value;
      renderTravauxTable(calculateTravauxMetrics(appState.travauxFund, appState.profiles, appState.splitRule));
    });
  }

  const filterPills = [
    document.getElementById('filter-trv-all'),
    document.getElementById('filter-trv-unreimbursed'),
    document.getElementById('filter-trv-reimbursed')
  ].filter(Boolean);

  filterPills.forEach(pill => {
    pill.addEventListener('click', () => {
      filterPills.forEach(p => p.classList.remove('active'));
      pill.classList.add('active');
      activeTravauxFilter = pill.dataset.filter || 'all';
      renderTravauxTable(calculateTravauxMetrics(appState.travauxFund, appState.profiles, appState.splitRule));
    });
  });

  // Action dispatcher on click
  document.addEventListener('click', (e) => {
    for (const { selector, handler } of CLICK_DISPATCHERS) {
      const target = e.target.closest(selector);
      if (target) {
        handler(target);
        return;
      }
    }
  });

  // JSON Export / Import
  const exportBtn1 = document.getElementById('btn-export-data');
  const exportBtn2 = document.getElementById('btn-export-json');
  const exportJsonHandler = () => {
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(appState, null, 2));
    const dlAnchor = document.createElement('a');
    dlAnchor.setAttribute("href", dataStr);
    dlAnchor.setAttribute("download", `DuoBudget_${appState.profiles[0].name}_${appState.profiles[1].name}_${new Date().toISOString().slice(0, 10)}.json`);
    document.body.appendChild(dlAnchor);
    dlAnchor.click();
    dlAnchor.remove();
  };
  if (exportBtn1) exportBtn1.addEventListener('click', exportJsonHandler);
  if (exportBtn2) exportBtn2.addEventListener('click', exportJsonHandler);

  const importFileInput = document.getElementById('import-json-file');
  if (importFileInput) {
    importFileInput.addEventListener('change', (e) => {
      const file = e.target.files[0];
      if (!file) return;
      const reader = new FileReader();
      reader.onload = (event) => {
        try {
          const imported = JSON.parse(event.target.result);
          if (imported.profiles && imported.simulator && imported.lclItems && imported.revolutEnvelopes) {
            appState = imported;
            saveStateImmediate();
            renderAll();
            alert('Données importées avec succès !');
          } else {
            alert('Fichier JSON incompatible avec DuoBudget.');
          }
        } catch (err) {
          alert('Erreur lors de la lecture du fichier JSON : ' + err.message);
        }
      };
      reader.readAsText(file);
    });
  }

  const resetBtn = document.getElementById('btn-reset-all-data');
  if (resetBtn) {
    resetBtn.addEventListener('click', () => {
      if (confirm('Attention : réinitialiser toutes les données aux valeurs par défaut pour Axel et Ariane ?')) {
        appState = JSON.parse(JSON.stringify(DEFAULT_STATE));
        saveStateImmediate();
        renderAll();
        alert('Application réinitialisée avec succès !');
      }
    });
  }

  // --- Open Banking (DSP2) Event Listeners ---
  const btnOpenBanking = document.getElementById('btn-open-banking-config');
  const modalBanking = document.getElementById('banking-modal');
  const btnCloseBanking = document.getElementById('btn-close-banking-modal');
  const btnCancelBanking = document.getElementById('btn-cancel-banking-modal');

  if (btnOpenBanking) {
    btnOpenBanking.addEventListener('click', () => {
      const secretIdInput = document.getElementById('banking-secret-id');
      const secretKeyInput = document.getElementById('banking-secret-key');
      if (secretIdInput) secretIdInput.value = appState.banking?.secret_id || '';
      if (secretKeyInput) secretKeyInput.value = appState.banking?.secret_key || '';
      if (modalBanking) modalBanking.style.display = 'flex';
    });
  }

  if (btnCloseBanking) {
    btnCloseBanking.addEventListener('click', () => {
      if (modalBanking) modalBanking.style.display = 'none';
    });
  }

  if (btnCancelBanking) {
    btnCancelBanking.addEventListener('click', () => {
      if (modalBanking) modalBanking.style.display = 'none';
    });
  }

  const bankingForm = document.getElementById('banking-config-form');
  if (bankingForm) {
    bankingForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const sId = document.getElementById('banking-secret-id').value.trim();
      const sKey = document.getElementById('banking-secret-key').value.trim();
      if (!appState.banking) appState.banking = {};
      appState.banking.secret_id = sId;
      appState.banking.secret_key = sKey;
      saveStateImmediate();
      try {
        await fetch('api.php?action=banking_configure', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ secret_id: sId, secret_key: sKey })
        });
        alert('Clés Open Banking enregistrées avec succès !');
        if (modalBanking) modalBanking.style.display = 'none';
        renderAll();
      } catch (err) {
        alert('Erreur enregistrement clés : ' + err.message);
      }
    });
  }

  const connectBank = async (inst) => {
    try {
      const res = await fetch('api.php?action=banking_connect', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ institution: inst })
      });
      const data = await res.json();
      if (data.success && data.link) {
        window.location.href = data.link;
      } else {
        alert('Erreur connexion ' + inst.toUpperCase() + ' : ' + (data.error || 'Lien indisponible'));
      }
    } catch (err) {
      alert('Erreur réseau: ' + err.message);
    }
  };

  const btnConnLcl = document.getElementById('btn-connect-lcl');
  if (btnConnLcl) btnConnLcl.addEventListener('click', () => connectBank('lcl'));

  const btnConnRev = document.getElementById('btn-connect-revolut');
  if (btnConnRev) btnConnRev.addEventListener('click', () => connectBank('revolut'));

  const btnTriggerSync = document.getElementById('btn-trigger-bank-sync');
  if (btnTriggerSync) {
    btnTriggerSync.addEventListener('click', async () => {
      btnTriggerSync.disabled = true;
      btnTriggerSync.textContent = '⟳ Synchronisation en cours...';
      try {
        const res = await fetch('api.php?action=banking_sync', { method: 'POST' });
        const data = await res.json();
        if (data.success) {
          if (data.banking) appState.banking = data.banking;
          saveStateImmediate();
          renderAll();
          alert('Synchronisation bancaire terminée avec succès !');
        } else {
          alert('Erreur de synchronisation: ' + (data.error || 'Échec'));
        }
      } catch (err) {
        alert('Erreur réseau: ' + err.message);
      } finally {
        btnTriggerSync.disabled = false;
        btnTriggerSync.textContent = '⟳ Synchroniser maintenant';
      }
    });
  }

  const btnDemo = document.getElementById('btn-banking-demo-mode');
  if (btnDemo) {
    btnDemo.addEventListener('click', () => {
      if (!appState.banking) appState.banking = {};
      appState.banking.accounts = [
        { id: 'sim_acc_lcl_01', name: 'Compte Courant LCL', institution: 'lcl', institution_id: 'LCL_FR_CL42', status: 'connected', last_balance: 1450.20, last_sync: new Date().toISOString().replace('T', ' ').slice(0, 19) },
        { id: 'sim_acc_rev_02', name: 'Carte Commune Revolut', institution: 'revolut', institution_id: 'REVOLUT_REVOFR22', status: 'connected', last_balance: 620.40, last_sync: new Date().toISOString().replace('T', ' ').slice(0, 19) }
      ];
      appState.banking.last_global_sync = new Date().toISOString().replace('T', ' ').slice(0, 19);
      saveStateImmediate();
      if (modalBanking) modalBanking.style.display = 'none';
      renderAll();
      alert('Mode Démo activé : Les comptes LCL (1 450,20 €) et Revolut (620,40 €) sont maintenant connectés et synchronisés !');
    });
  }
});
