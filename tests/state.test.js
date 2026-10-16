import test, { describe, it } from 'node:test';
import assert from 'node:assert/strict';
import { DEFAULT_STATE, validateAndMigrateState } from '../js/state.js';

describe('State Management & Migration Tests', () => {
  it('DEFAULT_STATE has all required couple, loan, lcl, revolut, and travaux sections', () => {
    assert.ok(Array.isArray(DEFAULT_STATE.profiles));
    assert.equal(DEFAULT_STATE.profiles.length, 2);
    assert.equal(DEFAULT_STATE.profiles[0].name, 'Axel');
    assert.equal(DEFAULT_STATE.profiles[1].name, 'Ariane');

    assert.ok(DEFAULT_STATE.simulator);
    assert.equal(DEFAULT_STATE.simulator.totalGift, 25000);
    assert.equal(DEFAULT_STATE.simulator.durationYears, 15);

    assert.ok(DEFAULT_STATE.travauxFund);
    assert.equal(DEFAULT_STATE.travauxFund.initialBudget, 50000);
    assert.equal(DEFAULT_STATE.travauxFund.lots.length, 7);
    assert.equal(DEFAULT_STATE.travauxFund.items.length, 4);

    assert.ok(Array.isArray(DEFAULT_STATE.lclItems));
    assert.ok(Array.isArray(DEFAULT_STATE.revolutEnvelopes));
    assert.ok(Array.isArray(DEFAULT_STATE.expenses));
  });

  it('restores default state if parsed state is null or non-object', () => {
    const res1 = validateAndMigrateState(null);
    assert.equal(res1.profiles[0].name, 'Axel');
    assert.equal(res1.travauxFund.initialBudget, 50000);

    const res2 = validateAndMigrateState('corrupted string');
    assert.equal(res2.profiles[1].name, 'Ariane');
  });

  it('migrates legacy stored state that lacked renovation lots', () => {
    const legacyState = {
      profiles: DEFAULT_STATE.profiles,
      splitRule: '50',
      simulator: DEFAULT_STATE.simulator,
      travauxFund: {
        initialBudget: 50000,
        items: [
          { id: 'trv-1', reason: 'Toiture', amountAxel: 4302, amountAriane: 4302 }
        ]
        // lots is missing!
      }
    };

    const migrated = validateAndMigrateState(legacyState);
    assert.ok(Array.isArray(migrated.travauxFund.lots));
    assert.equal(migrated.travauxFund.lots.length, 7);
    assert.equal(migrated.travauxFund.lots[0].name, 'Toiture & Couverture (Chantier Roman)');
  });
});
