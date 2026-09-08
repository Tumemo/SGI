(function (root, factory) {
    if (typeof module === 'object' && module.exports) module.exports = factory();
    else root.SGICronometro = factory();
}(typeof globalThis !== 'undefined' ? globalThis : this, function () {
    'use strict';

    var STATUSES = ['Agendado', 'Iniciado', 'Pausado', 'Concluido', 'Finalizado'];

    function integer(value, field, positive) {
        var normalized;
        if (Number.isInteger(value)) normalized = value;
        else if (typeof value === 'string' && /^-?\d+$/.test(value.trim())) normalized = Number(value.trim());
        else throw new TypeError(field + ' deve ser um inteiro válido.');
        if ((positive && normalized <= 0) || (!positive && normalized < 0)) {
            throw new RangeError(field + ' deve ser não negativo' + (positive ? ' e positivo.' : '.'));
        }
        return normalized;
    }

    function instant(now) {
        return integer(now, 'O instante do cronômetro', false);
    }

    function normalize(state) {
        var status = String(state && state.status_jogo || '');
        if (STATUSES.indexOf(status) === -1) throw new TypeError('Status de cronômetro inválido.');
        return {
            status_jogo: status,
            duracao_jogo: integer(state.duracao_jogo, 'duracao_jogo', true),
            tempo_extra_jogo: integer(state.tempo_extra_jogo == null ? 0 : state.tempo_extra_jogo, 'tempo_extra_jogo', false),
            tempo_restante_jogo: state.tempo_restante_jogo == null ? null : integer(state.tempo_restante_jogo, 'tempo_restante_jogo', false),
            data_inicio_real: state.data_inicio_real == null ? null : integer(state.data_inicio_real, 'data_inicio_real', false),
        };
    }

    function baseBalance(state) {
        return state.tempo_restante_jogo == null
            ? state.duracao_jogo + state.tempo_extra_jogo
            : state.tempo_restante_jogo;
    }

    function saldoAtual(state, now) {
        now = instant(now);
        var normalized = normalize(state);
        var balance = baseBalance(normalized);
        if (normalized.status_jogo !== 'Iniciado' || normalized.data_inicio_real == null) return balance;
        return Math.max(0, balance - Math.max(0, now - normalized.data_inicio_real));
    }

    function updateReference(state, now) {
        state.data_inicio_real = state.status_jogo === 'Iniciado' ? now : null;
    }

    function start(state, now) {
        if (state.status_jogo === 'Agendado') {
            state.tempo_restante_jogo = state.duracao_jogo + state.tempo_extra_jogo;
            state.status_jogo = 'Iniciado';
            state.data_inicio_real = now;
        } else if (state.status_jogo === 'Pausado') {
            state.tempo_restante_jogo = baseBalance(state);
            state.status_jogo = 'Iniciado';
            state.data_inicio_real = now;
        }
        return state;
    }

    function pause(state, now) {
        if (state.status_jogo === 'Iniciado') state.tempo_restante_jogo = saldoAtual(state, now);
        else if (state.status_jogo === 'Agendado') state.tempo_restante_jogo = baseBalance(state);
        if (['Agendado', 'Iniciado', 'Pausado'].indexOf(state.status_jogo) !== -1) {
            state.status_jogo = 'Pausado';
            state.data_inicio_real = null;
        }
        return state;
    }

    function extra(state, now, data) {
        var nextExtra = integer(data.tempo_extra_jogo, 'tempo_extra_jogo', false);
        if (nextExtra < state.tempo_extra_jogo) throw new RangeError('O acréscimo do cronômetro não pode ser reduzido.');
        state.tempo_restante_jogo = saldoAtual(state, now) + nextExtra - state.tempo_extra_jogo;
        state.tempo_extra_jogo = nextExtra;
        updateReference(state, now);
        return state;
    }

    function duration(state, now, data) {
        var nextDuration = integer(data.duracao_jogo, 'duracao_jogo', true);
        state.tempo_restante_jogo = Math.max(0, saldoAtual(state, now) + nextDuration - state.duracao_jogo);
        state.duracao_jogo = nextDuration;
        updateReference(state, now);
        return state;
    }

    function balance(state, now, data) {
        state.tempo_restante_jogo = integer(data.tempo_restante_jogo, 'tempo_restante_jogo', false);
        updateReference(state, now);
        return state;
    }

    function conclude(state, now) {
        state.tempo_restante_jogo = saldoAtual(state, now);
        state.status_jogo = 'Concluido';
        state.data_inicio_real = null;
        return state;
    }

    function transicionar(state, operation, now, data) {
        now = instant(now);
        var normalized = normalize(state);
        data = data || {};
        if (operation === 'iniciar' || operation === 'retomar') return start(normalized, now);
        if (operation === 'pausar') return pause(normalized, now);
        if (operation === 'acrescentar') return extra(normalized, now, data);
        if (operation === 'duracao') return duration(normalized, now, data);
        if (operation === 'saldo') return balance(normalized, now, data);
        if (operation === 'concluir') return conclude(normalized, now);
        throw new TypeError('Operação de cronômetro inválida.');
    }

    function snapshot(state, now) {
        now = instant(now);
        var normalized = normalize(state);
        var saldo = saldoAtual(normalized, now);
        return {
            versao: 2,
            saldo_segundos: saldo,
            referencia_epoch_ms: now * 1000,
        };
    }

    function aplicarSnapshot(state, payload, status, now) {
        now = instant(now);
        if (!payload || typeof payload !== 'object' || integer(payload.versao, 'cronometro.versao', false) !== 2) {
            throw new TypeError('A versão do cronômetro deve ser 2.');
        }
        var saldo = integer(payload.saldo_segundos, 'cronometro.saldo_segundos', false);
        var referenciaMs = integer(payload.referencia_epoch_ms, 'cronometro.referencia_epoch_ms', false);
        var referencia = Math.floor(referenciaMs / 1000);
        var next = normalize(state);
        status = status == null ? next.status_jogo : String(status);
        if (status === 'Pausado' || status === 'Concluido') {
            next.status_jogo = status;
            next.tempo_restante_jogo = saldo;
            next.data_inicio_real = null;
            return next;
        }
        if (status === 'Iniciado') {
            next.status_jogo = 'Iniciado';
            next.tempo_restante_jogo = Math.max(0, saldo - Math.max(0, now - referencia));
            next.data_inicio_real = now;
            return next;
        }
        throw new TypeError('Snapshot v2 exige jogo iniciado, pausado ou concluído.');
    }

    return {
        saldoAtual: saldoAtual,
        transicionar: transicionar,
        snapshot: snapshot,
        aplicarSnapshot: aplicarSnapshot,
    };
}));
