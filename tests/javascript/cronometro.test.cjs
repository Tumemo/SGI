const { test } = require('node:test');
const assert = require('node:assert/strict');
const CronometroRules = require('../../resources/js/shared/cronometro.js');

function state(duration, extra, remaining, reference, status = 'Iniciado') {
    return {
        status_jogo: status,
        duracao_jogo: duration,
        tempo_extra_jogo: extra,
        tempo_restante_jogo: remaining,
        data_inicio_real: reference,
    };
}

test('calculates a running balance from its persisted reference', () => {
    assert.equal(CronometroRules.saldoAtual(state(1200, 0, 1200, 1000), 1030), 1170);
});

test('pause freezes the balance and resume preserves it', () => {
    const paused = CronometroRules.transicionar(state(1200, 0, 1200, 1000), 'pausar', 1030);
    assert.equal(paused.status_jogo, 'Pausado');
    assert.equal(paused.tempo_restante_jogo, 1170);
    assert.equal(paused.data_inicio_real, null);
    assert.equal(CronometroRules.saldoAtual(paused, 1120), 1170);
    const resumed = CronometroRules.transicionar(paused, 'retomar', 1120);
    assert.equal(resumed.data_inicio_real, 1120);
    assert.equal(CronometroRules.saldoAtual(resumed, 1130), 1160);
});

test('repeated resume does not reset its reference', () => {
    const resumed = CronometroRules.transicionar(state(1200, 0, 1170, 1120), 'retomar', 1200);
    assert.equal(resumed.data_inicio_real, 1120);
    assert.equal(CronometroRules.saldoAtual(resumed, 1210), 1080);
});

test('absolute extra is applied once to the materialized balance', () => {
    const updated = CronometroRules.transicionar(state(1200, 0, 1170, null, 'Pausado'), 'acrescentar', 2000, { tempo_extra_jogo: 60 });
    assert.equal(updated.tempo_restante_jogo, 1230);
    const replayed = CronometroRules.transicionar(updated, 'acrescentar', 2100, { tempo_extra_jogo: 60 });
    assert.equal(replayed.tempo_restante_jogo, 1230);
});

test('zero remains zero until a valid extra is added', () => {
    const zero = state(1200, 0, 0, null, 'Pausado');
    assert.equal(CronometroRules.saldoAtual(zero, 2000), 0);
    assert.equal(CronometroRules.transicionar(zero, 'acrescentar', 2000, { tempo_extra_jogo: 60 }).tempo_restante_jogo, 60);
});

test('null balance uses duration and extra but zero does not fall back', () => {
    assert.equal(CronometroRules.saldoAtual(state(1200, 60, null, null, 'Pausado'), 2000), 1260);
    assert.equal(CronometroRules.saldoAtual(state(1200, 60, 0, null, 'Pausado'), 2000), 0);
});

test('duration changes add only their difference', () => {
    const updated = CronometroRules.transicionar(state(1200, 0, 1170, 1000), 'duracao', 1030, { duracao_jogo: 1500 });
    assert.equal(updated.duracao_jogo, 1500);
    assert.equal(updated.tempo_restante_jogo, 1440);
});

test('explicit balance starts a new reference and conclusion freezes it', () => {
    const saved = CronometroRules.transicionar(state(1200, 0, 1000, 900), 'saldo', 2000, { tempo_restante_jogo: 700 });
    assert.equal(CronometroRules.saldoAtual(saved, 2010), 690);
    const closed = CronometroRules.transicionar(saved, 'concluir', 2010);
    assert.equal(closed.status_jogo, 'Concluido');
    assert.equal(closed.tempo_restante_jogo, 690);
    assert.equal(closed.data_inicio_real, null);
});

test('future references do not increase balance and impossible values are rejected', () => {
    assert.equal(CronometroRules.saldoAtual(state(1200, 0, 1000, 2000), 1000), 1000);
    assert.throws(() => CronometroRules.transicionar(state(1200, 0, 1000, 2000), 'acrescentar', 2000, { tempo_extra_jogo: -1 }));
});
