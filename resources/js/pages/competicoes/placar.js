window.SGIPage.mount("competicoes/placar", function (pageConfig, pageScope) {

    // A SPA pode remontar esta tela várias vezes. Descarte a instância
    // anterior antes de registrar novos timers/listeners.
    if (typeof window.__SGI_TELA_CLEANUP__ === 'function') {
        try { window.__SGI_TELA_CLEANUP__(); } catch (_) {}
        window.__SGI_TELA_CLEANUP__ = null;
    }

    var params = new URLSearchParams(window.location.search);
    var idJogo = params.get('id_jogo') ? parseInt(params.get('id_jogo'), 10) : null;

    function obterIdJogoAtual() {
        var p = new URLSearchParams(window.location.search);
        var val = p.get('id_jogo');
        if (val != null && val !== '') {
            var parsed = parseInt(val, 10);
            if (!isNaN(parsed)) return parsed;
        }
        return (typeof idJogo !== 'undefined' && idJogo != null) ? idJogo : null;
    }

    function paginaOrigem() {
        try {
            const ref = new URL(document.referrer);
            return ref.pathname.split('/').pop() || '';
        } catch (_) {
            return '';
        }
    }

    function definirLinkVoltar() {
        const origem = params.get('origem');
        const refPagina = paginaOrigem();
        const refURL = (refPagina && document.referrer) ? document.referrer : null;
        let href = './edicao_agenda.php';

        if (origem === 'ranking' || refPagina === 'ranking.php') {
            href = refURL || './ranking.php';
        } else if (origem === 'agenda' || refPagina === 'agenda.php') {
            href = refURL || './agenda.php';
        } else if (origem === 'agenda_edit' || refPagina === 'edicao_agenda.php') {
            href = refURL || './edicao_agenda.php';
        }

        const btn = document.getElementById('btnVoltarPlacar');
        if (btn) btn.href = href;
        const seta = document.querySelector('section.position-relative > a.bi-arrow-left');
        if (seta) seta.href = href;
    }

    var API = window.API || (function() {
        var path = window.location.pathname;
        var idx = path.indexOf('/views/src/pages/');
        if (idx !== -1) {
            return path.substring(0, idx) + '/api/';
        }
        return '../../../api/';
    })();

    let estadoJogo = null;
    let partidasLista = [];
    let timerId = null;
    let tempoRestante = 0;
    let duracaoJogo = 20 * 60;
    let pausado = false;
    let saveChains = {};
    let tempoEsgotado = false;
    let equipesCache = {};
    let ehIndividual = false;
    let indParticipantes = [];
    let indRankingAtual = [];
    var __sgiPlacarCiclo = 0;
    var relogioOffsetMs = 0;
    var Cronometro = window.SGICronometro;

    var __sgiPlacarClickHandler = null;
    var __sgiPlacarCleanup = function() {
        // Invalida todas as leituras assíncronas da montagem que está saindo.
        // Uma resposta antiga nunca poderá pintar a próxima tela.
        __sgiPlacarCiclo++;
        pararTimer();
        if (window.__SGI_PLACAR_SYNC_UNSUB__) {
            try { window.__SGI_PLACAR_SYNC_UNSUB__(); } catch (_) {}
            window.__SGI_PLACAR_SYNC_UNSUB__ = null;
        }
        if (__sgiPlacarClickHandler) {
            document.removeEventListener('click', __sgiPlacarClickHandler);
            __sgiPlacarClickHandler = null;
        }
    };
    window.__SGI_TELA_CLEANUP__ = __sgiPlacarCleanup;

    function formatNomeJogo(nomeJogo) {
        const mm = (nomeJogo || '').match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = { 16: 'Oitavas de final', 8: 'Quartas de final', 4: 'Semifinal', 2: 'Final', 1: 'Campeão' };
            const fase = fases[largura] || 'Fase';
            if (largura === 1) return fase;
            return fase + ' — Confronto ' + (slot + 1) + (kind === 'B' ? ' (bye)' : '');
        }
        return nomeJogo || 'Jogo';
    }

    function nomeEquipe(p) {
        if (!p) return 'Equipe';
        // Duas equipes diferentes podem pertencer à mesma turma. O placar
        // precisa identificá-las pelo nome da equipe; a turma é apenas fallback.
        if (p.nome_equipe && String(p.nome_equipe).trim()) return p.nome_equipe;
        if (p.nome_fantasia_turma && String(p.nome_fantasia_turma).trim()) return p.nome_fantasia_turma;
        if (p.nome_turma && String(p.nome_turma).trim()) return p.nome_turma;
        return 'Equipe ' + (p.equipes_id_equipe || '');
    }

    async function enriquecerPartidasComTurmas() {
        var DL = window.SGIDataLayer;
        if (!DL || typeof DL.read !== 'function') return;
        try {
            var turmas = await DL.read('turmas').catch(function() { return []; });
            var equipes = await DL.read('equipes').catch(function() { return []; });
            var mapTurma = {};
            (Array.isArray(turmas) ? turmas : []).forEach(function(t) { if (t && t.id_turma) mapTurma[t.id_turma] = t; });
            var mapEquipe = {};
            (Array.isArray(equipes) ? equipes : []).forEach(function(e) { if (e && e.id_equipe) mapEquipe[e.id_equipe] = e; });

            (partidasLista || []).forEach(function(p) {
                var eq = mapEquipe[p.equipes_id_equipe];
                if (eq) {
                    if (!p.id_turma) p.id_turma = eq.turmas_id_turma || eq.id_turma;
                    if (!p.nome_equipe) p.nome_equipe = eq.nome_equipe;
                }
                var idT = p.id_turma || p.turmas_id_turma || (eq && (eq.turmas_id_turma || eq.id_turma));
                if (idT && mapTurma[idT]) {
                    var t = mapTurma[idT];
                    p.id_turma = idT;
                    if (!p.nome_turma) p.nome_turma = t.nome_turma;
                    if (!p.nome_fantasia_turma) p.nome_fantasia_turma = t.nome_fantasia_turma || t.nome_turma;
                }
            });

            if (!ehIndividual && Array.isArray(partidasLista) && partidasLista.length > 2) {
                var vistasEq = {};
                var filtradas = [];
                for (var i = partidasLista.length - 1; i >= 0; i--) {
                    var item = partidasLista[i];
                    var idEq = String(item.equipes_id_equipe || '');
                    if (idEq && !vistasEq[idEq]) {
                        vistasEq[idEq] = true;
                        filtradas.unshift(item);
                        if (filtradas.length === 2) break;
                    }
                }
                if (filtradas.length > 0) partidasLista = filtradas;
            }
        } catch (_) {}
    }

    function esc(s) {
        var d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    async function fetchJson(url, opts) {
        var r = await fetch(url, opts);
        var t = await r.text();
        var j;
        try { j = t ? JSON.parse(t) : {}; } catch (e) { j = {}; }
        if (!r.ok) throw new Error(j.message || 'Erro HTTP ' + r.status);
        return j;
    }

    function ajustarOffsetResposta(resposta) {
        var servidorMs = resposta && resposta.cronometro && Number(resposta.cronometro.servidor_epoch_ms);
        if (Number.isFinite(servidorMs) && servidorMs > 0) relogioOffsetMs = servidorMs - Date.now();
        return Number.isFinite(servidorMs) && servidorMs > 0 ? servidorMs : agoraServidorMs();
    }

    function normalizarJogoRecebido(jogo) {
        if (!jogo || typeof jogo !== 'object') return;
        var servidorMs = Number(jogo.servidor_epoch_ms);
        if (Number.isFinite(servidorMs) && servidorMs > 0) relogioOffsetMs = servidorMs - Date.now();
        if (jogo.tempo_restante_calculado != null) jogo.tempo_restante_jogo = jogo.tempo_restante_calculado;
        if (jogo.status_jogo === 'Iniciado' && jogo.tempo_restante_jogo != null && jogo.cronometro_referencia_epoch_ms == null) {
            jogo.cronometro_referencia_epoch_ms = Number.isFinite(servidorMs) && servidorMs > 0 ? servidorMs : agoraServidorMs();
        }
        if (jogo.status_jogo !== 'Iniciado') jogo.cronometro_referencia_epoch_ms = null;
    }

    function agoraServidorMs() {
        return Date.now() + relogioOffsetMs;
    }

    function estadoCronometroAtual() {
        var jogo = estadoJogo || {};
        var referencia = jogo.cronometro_referencia_epoch_ms;
        if (referencia == null && jogo.status_jogo === 'Iniciado' && jogo.servidor_epoch_ms != null) {
            referencia = Number(jogo.servidor_epoch_ms);
        }
        var saldo = jogo.tempo_restante_jogo;
        if (saldo == null && jogo.tempo_restante_calculado != null) saldo = jogo.tempo_restante_calculado;
        if (saldo == null) saldo = tempoRestante;
        return {
            status_jogo: jogo.status_jogo || 'Agendado',
            duracao_jogo: parseInt(jogo.duracao_jogo, 10) || duracaoJogo,
            tempo_extra_jogo: parseInt(jogo.tempo_extra_jogo, 10) || 0,
            tempo_restante_jogo: Math.max(0, parseInt(saldo, 10) || 0),
            data_inicio_real: referencia == null || !Number.isFinite(Number(referencia))
                ? null
                : Math.floor(Number(referencia) / 1000),
        };
    }

    function saldoCronometroAgora() {
        if (!Cronometro || !estadoJogo) return Math.max(0, tempoRestante);
        try {
            return Cronometro.saldoAtual(estadoCronometroAtual(), Math.floor(agoraServidorMs() / 1000));
        } catch (_) {
            return Math.max(0, tempoRestante);
        }
    }

    function blocoCronometro(status, saldo, referenciaMs) {
        return {
            versao: 2,
            saldo_segundos: Math.max(0, parseInt(saldo, 10) || 0),
            referencia_epoch_ms: Math.max(0, parseInt(referenciaMs, 10) || 0),
        };
    }

    function aplicarEstadoCronometro(next, servidorMs) {
        estadoJogo = Object.assign({}, estadoJogo || {}, next);
        tempoRestante = Math.max(0, next.tempo_restante_jogo == null ? 0 : Number(next.tempo_restante_jogo));
        duracaoJogo = Number(next.duracao_jogo) > 0 ? Number(next.duracao_jogo) : duracaoJogo;
        estadoJogo.tempo_restante_calculado = tempoRestante;
        estadoJogo.servidor_epoch_ms = servidorMs;
        estadoJogo.cronometro_referencia_epoch_ms = next.data_inicio_real == null ? null : Number(next.data_inicio_real) * 1000;
    }

    function atualizarTempoDoRelogio() {
        if (!estadoJogo || estadoJogo.status_jogo !== 'Iniciado') return;
        tempoRestante = saldoCronometroAgora();
        atualizarDisplayTimer();
        if (tempoRestante <= 0) bloquearPontuacao();
    }

    function pararTimer() {
        if (timerId) {
            clearInterval(timerId);
            timerId = null;
        }
    }

    function atualizarDisplayTimer() {
        var el = document.getElementById('timer-placar');
        if (!el) return;
        var m = String(Math.floor(Math.max(0, tempoRestante) / 60)).padStart(2, '0');
        var s = String(Math.max(0, tempoRestante) % 60).padStart(2, '0');
        el.textContent = m + ':' + s;
        el.classList.toggle('timer-expired', tempoRestante <= 0 && tempoEsgotado);
        var statTimer = document.getElementById('mc-duration-stat');
        if (statTimer) statTimer.textContent = m + ':' + s;
    }

    function tocarAlertaSonoro() {
        try {
            var ctx = new (window.AudioContext || window.webkitAudioContext)();
            var osc = ctx.createOscillator();
            var gain = ctx.createGain();
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.frequency.value = 880;
            osc.type = 'sine';
            gain.gain.setValueAtTime(0.4, ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 1.2);
            osc.start(ctx.currentTime);
            osc.stop(ctx.currentTime + 1.2);
        } catch (e) {}
    }

    function bloquearPontuacao() {
        tempoEsgotado = true;
        pararTimer();
        atualizarDisplayTimer();
        tocarAlertaSonoro();
        document.querySelectorAll('.btn-score-plus, .btn-score-minus').forEach(function(b) {
            b.disabled = true;
        });
        var grid = document.getElementById('placar-grid');
        if (grid) grid.classList.add('score-blocked');

        // Mostrar botões de tempo extra se o jogo não estiver encerrado
        var st = estadoJogo.status_jogo;
        if (st === 'Iniciado' || st === 'Pausado') {
            mostrarBotoesTempoExtra();
        }
    }

    function mostrarBotoesTempoExtra() {
        var acoes = document.getElementById('placar-acoes');
        if (!acoes) return;
        if (document.getElementById('mc-overtime-actions')) return;

        var container = document.createElement('div');
        container.id = 'mc-overtime-actions';
        container.className = 'mc-actions';
        container.style.marginTop = '1rem';
        container.style.display = 'flex';
        container.style.alignItems = 'center';
        container.style.gap = '.5rem';
        container.style.flexWrap = 'wrap';

        var label = document.createElement('span');
        label.className = 'fw-bold text-danger';
        label.style.fontSize = '.9rem';
        label.innerHTML = '<i class="bi bi-stopwatch me-1"></i>Tempo esgotado — Acréscimos:';
        container.appendChild(label);

        var inputGroup = document.createElement('div');
        inputGroup.style.display = 'flex';
        inputGroup.style.alignItems = 'center';
        inputGroup.style.gap = '.35rem';

        var input = document.createElement('input');
        input.type = 'number';
        input.id = 'mc-overtime-input';
        input.min = '1';
        input.max = '30';
        input.placeholder = 'min';
        input.style.width = '60px';
        input.style.padding = '.4rem .5rem';
        input.style.borderRadius = '8px';
        input.style.border = '1.5px solid #e5e7eb';
        input.style.fontSize = '.85rem';
        input.style.textAlign = 'center';
        pageScope.listen(input, 'keydown', function(e) {
            if (e.key === 'Enter') { btn.click(); }
        });
        inputGroup.appendChild(input);

        var minLabel = document.createElement('span');
        minLabel.style.fontSize = '.8rem';
        minLabel.style.color = '#6b7280';
        minLabel.textContent = 'min';
        inputGroup.appendChild(minLabel);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mc-action-btn mc-action-btn--start';
        btn.style.fontSize = '.8rem';
        btn.style.padding = '.5rem 1rem';
        btn.innerHTML = '<i class="bi bi-plus-circle me-1"></i>Adicionar';
        pageScope.listen(btn, 'click', function() {
            var val = parseInt(input.value, 10);
            if (!val || val < 1) {
                input.style.borderColor = '#e30613';
                input.focus();
                return;
            }
            adicionarTempoExtra(val * 60);
        });
        inputGroup.appendChild(btn);

        container.appendChild(inputGroup);
        acoes.appendChild(container);
        input.focus();
    }

    async function adicionarTempoExtra(segundos) {
        var agoraMs = agoraServidorMs();
        var estado = estadoCronometroAtual();
        var novoTotal = estado.tempo_extra_jogo + segundos;
        try {
            var next = Cronometro.transicionar(estado, 'acrescentar', Math.floor(agoraMs / 1000), {
                tempo_extra_jogo: novoTotal,
            });
            var response = await fetchJson(API + 'jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_jogo: idJogo,
                    status_jogo: next.status_jogo,
                    tempo_extra_jogo: novoTotal,
                    tempo_restante_jogo: next.tempo_restante_jogo,
                    cronometro: blocoCronometro(next.status_jogo, next.tempo_restante_jogo, agoraMs),
                })
            });
            aplicarEstadoCronometro(next, ajustarOffsetResposta(response));
            tempoEsgotado = false;

            // Remover bloqueio visual
            var grid = document.getElementById('placar-grid');
            if (grid) grid.classList.remove('score-blocked');
            document.querySelectorAll('.btn-score-plus, .btn-score-minus').forEach(function(b) {
                b.disabled = false;
            });

            // Remover botões de overtime
            var ov = document.getElementById('mc-overtime-actions');
            if (ov) ov.remove();

            // Reiniciar timer
            await persistirJogoLocal();
            renderTudo();
            iniciarTimerDisplay();
        } catch (e) {
            alert('Erro ao adicionar tempo extra: ' + (e.message || 'Erro de conexão'));
        }
    }

    function iniciarTimerDisplay() {
        var el = document.getElementById('timer-placar');
        if (!el) return;
        pararTimer();

        if (tempoRestante <= 0 && estadoJogo.status_jogo === 'Agendado') {
            tempoRestante = duracaoJogo;
        }

        if (estadoJogo.status_jogo === 'Iniciado') tempoRestante = saldoCronometroAgora();

        if (tempoRestante <= 0) {
            tempoEsgotado = true;
            atualizarDisplayTimer();
            // Se jogo em andamento, mostrar botões de tempo extra
            var st = estadoJogo.status_jogo;
            if (st === 'Iniciado' || st === 'Pausado') {
                document.querySelectorAll('.btn-score-plus, .btn-score-minus').forEach(function(b) {
                    b.disabled = true;
                });
                var grid = document.getElementById('placar-grid');
                if (grid) grid.classList.add('score-blocked');
                mostrarBotoesTempoExtra();
            }
            return;
        }

        tempoEsgotado = false;
        pausado = estadoJogo.status_jogo === 'Pausado';
        atualizarDisplayTimer();
        var btnPause = document.getElementById('btn-pausar');
        if (btnPause) btnPause.textContent = pausado ? 'Retomar' : 'Pausar';
        if (!pausado) {
            timerId = setInterval(atualizarTempoDoRelogio, 1000);
        }
    }

    async function togglePause() {
        if (!estadoJogo || !Cronometro) return;
        var target = estadoJogo.status_jogo === 'Pausado' ? 'Iniciado' : 'Pausado';
        var agoraMs = agoraServidorMs();
        var estado = estadoCronometroAtual();
        var next = Cronometro.aplicarSnapshot(
            estado,
            blocoCronometro(target, saldoCronometroAgora(), agoraMs),
            target,
            Math.floor(agoraMs / 1000),
        );
        try {
            var response = await fetchJson(API + 'jogos.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    id_jogo: idJogo,
                    status_jogo: target,
                    tempo_restante_jogo: next.tempo_restante_jogo,
                    tempo_extra_jogo: next.tempo_extra_jogo,
                    cronometro: blocoCronometro(target, next.tempo_restante_jogo, agoraMs),
                })
            });
            aplicarEstadoCronometro(next, ajustarOffsetResposta(response));
            pausado = target === 'Pausado';
            await persistirJogoLocal();
            renderTudo();
            iniciarTimerDisplay();
        } catch (e) {
            alert('Erro ao ' + (target === 'Pausado' ? 'pausar' : 'retomar') + ': ' + (e.message || 'Erro de conexão'));
        }
    }

    function mudarDuracao(segundos) {
        var st = estadoJogo.status_jogo;
        var diff = segundos - duracaoJogo;
        duracaoJogo = segundos;
        if (st === 'Iniciado' || st === 'Pausado') {
            tempoRestante = Math.max(1, tempoRestante + diff);
        } else {
            tempoRestante = segundos;
        }
        tempoEsgotado = false;
        atualizarDisplayTimer();
    }

    function agendarSalvarPartida(idPartida, gols) {
        var chave = String(idPartida);
        var partidaCapturada = (partidasLista || []).find(function(p) {
            return String(p.id_partida) === chave;
        });
        var jogoCapturado = estadoJogo ? Object.assign({}, estadoJogo) : null;
        var placarCapturado = Math.max(0, parseInt(gols, 10) || 0);
        var anterior = saveChains[chave] || Promise.resolve();
        var atual = anterior.catch(function() {}).then(function() {
            return salvarPartida(idPartida, placarCapturado, {
                jogo: jogoCapturado,
                partida: partidaCapturada ? Object.assign({}, partidaCapturada) : null,
            });
        });
        saveChains[chave] = atual;
        atual.then(function() {
            if (saveChains[chave] === atual) delete saveChains[chave];
        }, function(error) {
            if (saveChains[chave] === atual) delete saveChains[chave];
            alert('Erro ao salvar o placar: ' + ((error && error.message) || 'não foi possível gravar a alteração local.'));
        });
        return atual;
    }

    async function salvarPartida(idPartida, gols, capturado) {
        if (!idPartida || isNaN(Number(idPartida)) || Number(idPartida) <= 0 || String(idPartida).indexOf('mm_local_') === 0 || (estadoJogo && Number(estadoJogo.id_jogo) < 0)) {
            // Partida offline ou derivada local: grava APENAS no banco local IndexedDB
            if (window.SGIDataLayer && window.SGIDataLayer.upsert) {
                var rowPartida = capturado && capturado.partida
                    ? capturado.partida
                    : (partidasLista || []).find(function(p) { return String(p.id_partida) === String(idPartida); });
                if (!rowPartida) return;
                rowPartida.resultado_partida = gols;
                await window.SGIDataLayer.upsert('partidas', idPartida, rowPartida);
            }
            return;
        }
        if (!(window.SGIOffline && typeof window.SGIOffline.queueMutation === 'function')) {
            throw new Error('A fila offline do Mesário não está disponível.');
        }
        var urlAbsoluta;
        try { urlAbsoluta = new URL(API + 'partidas.php', location.href).href; }
        catch (_) { urlAbsoluta = API + 'partidas.php'; }
        var item = await window.SGIOffline.queueMutation(
            'PUT',
            urlAbsoluta,
            JSON.stringify({ id_partida: idPartida, resultado_partida: gols }),
            { 'Content-Type': 'application/json' }
        );
        if (item && item.projectionError) {
            throw new Error(item.projectionError);
        }
        if (typeof window.SGIOffline.sync === 'function' && window.SGIOffline.isOnline()) {
            await window.SGIOffline.sync();
        }
    }

    function aguardarGravacoesPartidas() {
        return Promise.all(Object.keys(saveChains).map(function(chave) {
            return saveChains[chave];
        }));
    }

    /* Espelha o estado do jogo (Iniciado/Pausado/duração) na store local de
       jogos. Sem isto, iniciar o jogo online nunca atualizava o banco JS e,
       recarregando o placar offline sem mutações pendentes, o snapshot por URL
       mostrava 'Agendado' e o botão de finalizar sumia. */
    function persistirJogoLocal() {
        if (!(window.SGIDataLayer && window.SGIDataLayer.upsert)) return Promise.resolve();
        var DL = window.SGIDataLayer;
        return Promise.resolve(DL.read ? DL.read('jogos') : []).then(function (rows) {
            var old = (rows || []).filter(function (row) { return String(row.id_jogo) === String(idJogo); })[0];
            return DL.upsert('jogos', idJogo, Object.assign({}, old || {}, estadoJogo, {
                _pendente: !!(old && old._pendente === true),
            }));
        });
    }

    function jogoEncerrado() {
        var st = estadoJogo ? estadoJogo.status_jogo : '';
        return st === 'Concluido' || st === 'Finalizado';
    }

    async function iniciarJogoServidor() {
        if (!idJogo) idJogo = obterIdJogoAtual();
        var selDur = document.getElementById('select-duracao');
        if (selDur && selDur.value) {
            var valM = parseInt(selDur.value, 10);
            if (!isNaN(valM) && valM > 0) duracaoJogo = valM * 60;
        }
        if (!duracaoJogo || isNaN(duracaoJogo)) duracaoJogo = 20 * 60;
        var agoraMs = agoraServidorMs();
        var estado = estadoCronometroAtual();
        estado.status_jogo = 'Agendado';
        estado.duracao_jogo = duracaoJogo;
        var next = Cronometro.transicionar(estado, 'retomar', Math.floor(agoraMs / 1000));
        var response = await fetchJson(API + 'jogos.php', {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                id_jogo: idJogo,
                status_jogo: 'Iniciado',
                duracao_jogo: duracaoJogo,
                tempo_restante_jogo: next.tempo_restante_jogo,
                tempo_extra_jogo: next.tempo_extra_jogo,
                cronometro: blocoCronometro('Iniciado', next.tempo_restante_jogo, agoraMs),
            })
        });
        aplicarEstadoCronometro(next, ajustarOffsetResposta(response));
        await persistirJogoLocal();
        renderTudo();
        iniciarTimerDisplay();
    }

    async function finalizarJogo() {
        if (!confirm('Encerrar o jogo e gravar o placar final no sistema?')) return;
        var resultados = partidasLista.map(function(p) {
            return {
                id_equipe: parseInt(p.equipes_id_equipe, 10),
                gols: Math.max(0, parseInt(p.resultado_partida, 10) || 0)
            };
        });
        if (resultados.length >= 2 && resultados[0].gols === resultados[1].gols) {
            alert('O jogo não pode terminar empatado! Registre o placar correto antes de finalizar.');
            return;
        }

        /* Híbrido: offline grava no banco temporário JS e libera a UI na hora;
           online envia à API PHP normalmente. */
        var offline = !navigator.onLine || (window.SGIOffline && typeof window.SGIOffline.isOnline === 'function' && !window.SGIOffline.isOnline());
        if (offline) {
            await finalizarLocalmente(resultados);
            return;
        }

        try {
            var payloadFin = {
                id_jogo: idJogo,
                nome_jogo: (estadoJogo && estadoJogo.nome_jogo) || null,
                id_modalidade: (estadoJogo && (estadoJogo.modalidades_id_modalidade || estadoJogo.id_modalidade)) || null,
                resultados: resultados
            };
            var res = await fetch(API + 'lancar_resultado.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payloadFin)
            });
            var js = await res.json();
            if (!res.ok || js.success === false) throw new Error(js.message || 'Falha ao finalizar');

            /* Soft-offline: o wrapper do offline-core enfileirou a requisição
               e devolveu sucesso local (offline:true). Aplica a finalização na
               UI SEM recarregar do servidor — o snapshot por URL estaria
               desatualizado e faria a tela "voltar" para Iniciado a cada
               clique, além de duplicar o POST na fila de sincronização. */
            if (js.offline === true) {
                aplicarFinalizacaoUI(resultados);
                if (window.SGIChaveamento && typeof window.SGIChaveamento.promoverVencedorLocal === 'function') {
                    window.SGIChaveamento.promoverVencedorLocal(idJogo).catch(function() {});
                }
                return;
            }
            estadoJogo.status_jogo = 'Concluido';
            pararTimer();
            await carregarDados();
        } catch (e) {
            alert(e.message || 'Erro ao finalizar.');
        }
    }

    /* ── Finalização OFFLINE ─────────────────────────────────────────────
       1) Aplica o término imediatamente na memória (status 'Concluido' +
          placar) e re-renderiza a tela, sem esperar servidor.
       2) Enfileira a MESMA requisição original (POST lancar_resultado.php)
          na mutation queue do offline-core. O hook SGIDataLayer.onQueued
          projeta o placar e o novo status no IndexedDB local, e quando a
          conexão voltar a fila reenvia tudo ao PHP, que refaz as validações
          e avança o chaveamento nativamente.
       3) Dispara o Bracket Engine local (SGIChaveamento.promoverVencedorLocal),
          que promove o vencedor na árvore do banco JS: cria/libera a próxima
          partida ou marca "aguardando adversário", recursivamente até o
          campeão — sem nenhuma chamada ao PHP. */
    /* Aplica o término do jogo na interface (placar final + status Concluido
       + timer parado), sem enfileirar nem consultar o servidor. Usada pelos
       caminhos offline e soft-offline. */
    function aplicarFinalizacaoUI(resultados) {
        resultados.forEach(function(r) {
            var p = partidasLista.filter(function(x) {
                return parseInt(x.equipes_id_equipe, 10) === r.id_equipe;
            })[0];
            if (p) p.resultado_partida = String(r.gols);
        });
        estadoJogo.status_jogo = 'Concluido';
        pararTimer();
        renderTudo();
    }

    async function finalizarLocalmente(resultados) {
        // --- Lock contra finalização duplicada (multi-abas / duplo-clique) ---
        var lockKey = 'sgi_finalizando_' + idJogo;
        var agora = Date.now();
        function liberarLock() { try { localStorage.removeItem(lockKey); } catch (e) {} }
        try {
            var lockData = localStorage.getItem(lockKey);
            if (lockData) {
                var lock = JSON.parse(lockData);
                if (agora - lock.ts < 60000) {
                    alert('Este jogo está sendo finalizado. Aguarde um momento.');
                    return;
                }
            }
        } catch (e) { /* localStorage indisponível, ignora lock */ }
        try { localStorage.setItem(lockKey, JSON.stringify({ ts: agora })); } catch (e) {}

        var totalGols = resultados.reduce(function(s, r) { return s + r.gols; }, 0);
        if (totalGols === 0) {
            liberarLock();
            alert('Não é possível finalizar um jogo com placar 0x0. Registre o placar correto.');
            return;
        }

        // A interface só pode anunciar "Encerrado" depois que a projeção no
        // IndexedDB terminou. Assim, voltar imediatamente para a agenda nunca
        // reabre a versão antiga Agendado/Iniciado do mesmo jogo.
        if (!(window.SGIOffline && typeof window.SGIOffline.queueMutation === 'function')) {
            liberarLock();
            alert('Sem conexão com o servidor. Tente novamente quando estiver online.');
            return;
        }

        // Aguarde as intenções de placar capturadas antes da finalização. A
        // desmontagem da SPA não pode deixar uma gravação anterior sobrescrever
        // o resultado final que será enviado agora.
        try {
            await aguardarGravacoesPartidas();
        } catch (e) {
            liberarLock();
            alert('Não foi possível salvar o placar antes de finalizar: ' + ((e && e.message) || 'tente novamente.'));
            return;
        }
        if (window.SGIDataLayer && typeof window.SGIDataLayer.upsert === 'function') {
            await Promise.all(resultados.map(function(r) {
                var partida = (partidasLista || []).find(function(p) {
                    return parseInt(p.equipes_id_equipe, 10) === Number(r.id_equipe);
                });
                if (!partida || partida.id_partida == null) return Promise.resolve();
                partida.resultado_partida = Number(r.gols) || 0;
                return window.SGIDataLayer.upsert('partidas', partida.id_partida,
                    Object.assign({}, partida, { resultado_partida: partida.resultado_partida, _pendente: true }));
            }));
        }
        var urlAbsoluta;
        try { urlAbsoluta = new URL(API + 'lancar_resultado.php', location.href).href; }
        catch (_) { urlAbsoluta = API + 'lancar_resultado.php'; }

        var payloadLocal = {
            id_jogo: idJogo,
            nome_jogo: (estadoJogo && estadoJogo.nome_jogo) || null,
            id_modalidade: (estadoJogo && (estadoJogo.modalidades_id_modalidade || estadoJogo.id_modalidade)) || null,
            _contexto_offline: estadoJogo ? {
                id_jogo: idJogo,
                nome_jogo: estadoJogo.nome_jogo || null,
                data_jogo: estadoJogo.data_jogo || null,
                inicio_jogo: estadoJogo.inicio_jogo || null,
                termino_jogo: estadoJogo.termino_jogo || estadoJogo.terminno_jogo || null,
                modalidades_id_modalidade: estadoJogo.modalidades_id_modalidade || estadoJogo.id_modalidade || null,
                id_interclasse: estadoJogo.id_interclasse || estadoJogo.interclasses_id_interclasse || null,
                locais_id_local: estadoJogo.locais_id_local || null,
                nome_modalidade: estadoJogo.nome_modalidade || null,
                nome_categoria: estadoJogo.nome_categoria || null,
                nome_local: estadoJogo.nome_local || null,
                equipes_nomes: estadoJogo.equipes_nomes || null,
                tipos_modalidades_id_tipo_modalidade: estadoJogo.tipos_modalidades_id_tipo_modalidade || null
            } : null,
            resultados: resultados
        };

        try {
            await window.SGIOffline.queueMutation(
                'POST',
                urlAbsoluta,
                JSON.stringify(payloadLocal),
                { 'Content-Type': 'application/json' }
            );

            // A mutação e sua projeção já estão persistidas. Agora é seguro
            // liberar a navegação e renderizar o estado final.
            aplicarFinalizacaoUI(resultados);
            liberarLock();

            // Avanço imediato da árvore no banco JS temporário.
            if (window.SGIChaveamento && typeof window.SGIChaveamento.promoverVencedorLocal === 'function') {
                try {
                    var r = await window.SGIChaveamento.promoverVencedorLocal(idJogo);
                    if (!r || !r.promoveu) {
                        alert('Jogo encerrado offline! Resultado salvo neste dispositivo e será enviado ao servidor quando a conexão voltar.');
                    } else if (r.encerrado) {
                        alert('Campeão definido offline: a final foi concluída neste dispositivo. Tudo será sincronizado com o servidor.');
                    } else if (r.pai.formada) {
                        alert('Vencedor avançou! Nova partida liberada: ' + r.pai.nome_display + '.');
                    } else {
                        alert('Vencedor aguardando adversário em: ' + r.pai.nome_display + '.');
                    }
                } catch (_) {
                    alert('Jogo encerrado offline! (Não foi possível calcular a próxima fase agora.)');
                }
                return;
            }
            alert('Jogo encerrado offline! O resultado foi salvo neste dispositivo e será enviado ao servidor automaticamente quando a conexão voltar.');
        } catch (_) {
            liberarLock();
            alert('Não foi possível registrar o resultado no armazenamento local. O jogo permanece em andamento para evitar perda de dados.');
        }
    }

    /* Carrega do banco JS temporário uma partida derivada offline (id < 0),
       tornando-a jogável no placar sem qualquer contato com o servidor. */
    function placarContinuaAtivo(ciclo) {
        var grid = document.getElementById('placar-grid');
        return ciclo === __sgiPlacarCiclo && !!(grid && grid.isConnected);
    }

    async function carregarJogoLocalTemporario(ciclo) {
        var DL = window.SGIDataLayer;
        if (!DL || typeof DL.read !== 'function') return false;
        try {
            var jogos = await DL.read('jogos');
            if (!placarContinuaAtivo(ciclo)) return false;
            var row = jogos.filter(function(j) { return Number(j.id_jogo) === idJogo; })[0];
            if (!row) return false;

            estadoJogo = Object.assign({}, row);
            normalizarJogoRecebido(estadoJogo);
            if (!estadoJogo.nome_modalidade) estadoJogo.nome_modalidade = '';

            var modalidades = await DL.read('modalidades').catch(function() { return []; });
            if (!placarContinuaAtivo(ciclo)) return false;
            if (Array.isArray(modalidades) && estadoJogo.modalidades_id_modalidade) {
                var mod = modalidades.find(function(m) { return Number(m.id_modalidade) === Number(estadoJogo.modalidades_id_modalidade); });
                if (mod) {
                    if (!estadoJogo.nome_modalidade) estadoJogo.nome_modalidade = mod.nome_modalidade;
                    if (!estadoJogo.tipos_modalidades_id_tipo_modalidade) estadoJogo.tipos_modalidades_id_tipo_modalidade = mod.tipos_modalidades_id_tipo_modalidade;
                }
            }
            var locais = await DL.read('locais').catch(function() { return []; });
            if (!placarContinuaAtivo(ciclo)) return false;
            if (Array.isArray(locais)) {
                if (estadoJogo.locais_id_local) {
                    var loc = locais.find(function(l) { return Number(l.id_local) === Number(estadoJogo.locais_id_local); });
                    if (loc && !estadoJogo.nome_local) estadoJogo.nome_local = loc.nome_local;
                } else if (locais.length > 0 && !estadoJogo.nome_local) {
                    estadoJogo.locais_id_local = locais[0].id_local;
                    estadoJogo.nome_local = locais[0].nome_local;
                }
            }

            var todasPartidas = await DL.read('partidas');
            if (!placarContinuaAtivo(ciclo)) return false;
            partidasLista = todasPartidas.filter(function(p) {
                return String(p.jogos_id_jogo) === String(idJogo);
            });

            if ((!partidasLista || partidasLista.length === 0) && Array.isArray(row.equipes) && row.equipes.length > 0) {
                partidasLista = row.equipes.map(function(eq, idx) {
                    return {
                        id_partida: eq.id_partida || ('mm_local_' + row.id_jogo + '_' + (eq.id_equipe || idx)),
                        jogos_id_jogo: row.id_jogo,
                        equipes_id_equipe: eq.id_equipe,
                        resultado_partida: eq.gols || 0,
                        id_turma: eq.id_turma || null,
                        nome_turma: eq.nome_turma || '',
                        nome_fantasia_turma: eq.nome_fantasia || eq.nome_fantasia_turma || eq.nome_equipe || '',
                        nome_equipe: eq.nome_equipe || ''
                    };
                });
            }

            await enriquecerPartidasComTurmas();
            if (!placarContinuaAtivo(ciclo)) return false;
            ehIndividual = false;
            duracaoJogo = parseInt(estadoJogo.duracao_jogo, 10) || (20 * 60);
            tempoRestante = estadoJogo.tempo_restante_jogo != null
                ? Math.max(0, parseInt(estadoJogo.tempo_restante_jogo, 10) || 0)
                : duracaoJogo;

            document.getElementById('placar-loading').classList.add('d-none');
            document.getElementById('placar-conteudo').classList.remove('d-none');
            renderTudo();
            return true;
        } catch (e) {
            return false;
        }
    }

    function renderTudo() {
        pararTimer();
        tempoEsgotado = false;

        var meta = document.getElementById('placar-meta');
        var acoes = document.getElementById('placar-acoes');
        var grid = document.getElementById('placar-grid');
        var titulo = document.getElementById('placar-titulo-jogo');
        var statusEl = document.getElementById('mc-status-badge');
        if (!meta || !acoes || !grid || !titulo) return;

        titulo.textContent = formatNomeJogo(estadoJogo ? estadoJogo.nome_jogo : '') || 'Placar';
        meta.textContent = [
            estadoJogo ? estadoJogo.nome_modalidade : '',
            estadoJogo ? estadoJogo.nome_local : '',
            estadoJogo ? estadoJogo.data_jogo : '',
            (estadoJogo && estadoJogo.inicio_jogo) ? String(estadoJogo.inicio_jogo).slice(0, 5) : ''
        ].filter(Boolean).join('  ·  ');

        var st = estadoJogo.status_jogo;
        if (statusEl) {
            var badgeClass = 'mc-badge--scheduled';
            var badgeLabel = 'Agendado';
            var dotPulse = '';
            if (st === 'Iniciado') { badgeClass = 'mc-badge--live'; badgeLabel = 'Em andamento'; dotPulse = ' mc-badge-dot--pulse'; }
            else if (st === 'Pausado') { badgeClass = 'mc-badge--paused'; badgeLabel = 'Pausado'; }
            else if (st === 'Concluido' || st === 'Finalizado') { badgeClass = 'mc-badge--finished'; badgeLabel = 'Encerrado'; }
            statusEl.innerHTML = '<span class="mc-badge ' + badgeClass + '"><span class="mc-badge-dot' + dotPulse + '"></span>' + badgeLabel + '</span>';
        }

        acoes.innerHTML = '';
        grid.innerHTML = '';
        grid.classList.remove('score-blocked');

        if (ehIndividual) {
            renderIndividual();
            return;
        }

        var emAndamento = st === 'Iniciado' || st === 'Pausado';
        var encerrado = st === 'Concluido' || st === 'Finalizado';

        var fab = document.getElementById('btnNovaOcorrencia');
        if (fab) fab.style.display = encerrado ? 'none' : '';

        if (st === 'Agendado') {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'mc-action-btn mc-action-btn--start';
            b.innerHTML = '<i class="bi bi-play-fill"></i> Iniciar jogo';
            pageScope.listen(b, 'click', function() {
                iniciarJogoServidor().catch(function(e) { alert(e.message); });
            });
            acoes.appendChild(b);
        }

        if (emAndamento && partidasLista.length >= 2) {
            var b2 = document.createElement('button');
            b2.type = 'button';
            b2.className = 'mc-action-btn mc-action-btn--finish';
            b2.innerHTML = '<i class="bi bi-stop-fill"></i> Finalizar jogo';
            pageScope.listen(b2, 'click', function() { finalizarJogo(); });
            acoes.appendChild(b2);
        }


        if (partidasLista.length === 0) {
            grid.innerHTML = '<div class="mc-empty"><i class="bi bi-inbox sgi-inline-c8c19351" ></i>Não há equipes vinculadas a este jogo. Cadastre as partidas no sistema.</div>';
            return;
        }

        var podeTimer = emAndamento || st === 'Agendado';
        var readonly = encerrado || st === 'Agendado' || tempoEsgotado;

        var html = '';

        // Timer
        if (podeTimer) {
            var selOpts = [5,10,15,20,25,30,40,45].map(function(v) {
                return '<option value="' + v + '"' + (duracaoJogo === v*60 ? ' selected' : '') + '>' + v + ' min</option>';
            }).join('');

            html += '<div class="mc-timer-section">';
            html += '<div class="mc-timer-time" id="timer-placar">' +
                String(Math.floor(duracaoJogo / 60)).padStart(2, '0') + ':' +
                String(duracaoJogo % 60).padStart(2, '0') + '</div>';
            html += '<div class="mc-timer-controls">';
            html += '<select id="select-duracao" class="mc-duration-select"' + (emAndamento ? ' disabled' : '') + '>' + selOpts + '</select>';
            if (emAndamento) {
                html += '<button type="button" class="mc-pause-btn" id="btn-pausar">' + (pausado ? 'Retomar' : 'Pausar') + '</button>';
            }
            html += '</div></div>';
        } else {
            html += '<div class="mc-timer-section"><div class="mc-timer-time mc-timer-time--idle" id="timer-placar">--:--</div></div>';
        }

        // Teams
        html += '<div class="mc-teams">';

        partidasLista.forEach(function(p, idx) {
            var gols = Math.max(0, parseInt(p.resultado_partida, 10) || 0);
            var btnMinus = readonly
                ? '<button type="button" class="btn-score btn-score-minus" disabled><i class="bi bi-dash-lg"></i></button>'
                : '<button type="button" class="btn-score btn-score-minus" data-idx="' + idx + '"><i class="bi bi-dash-lg"></i></button>';
            var btnPlus = readonly
                ? '<button type="button" class="btn-score btn-score-plus" disabled><i class="bi bi-plus-lg"></i></button>'
                : '<button type="button" class="btn-score btn-score-plus" data-idx="' + idx + '"><i class="bi bi-plus-lg"></i></button>';

            html += '<div class="mc-team" data-partida-idx="' + idx + '">';
            html += '<h3 class="mc-team-name">' + esc(nomeEquipe(p)) + '</h3>';
            html += '<div class="mc-score-row">';
            html += btnMinus;
            html += '<span class="mc-score score-number" data-gols="' + idx + '">' + String(gols).padStart(2, '0') + '</span>';
            html += btnPlus;
            html += '</div></div>';

            if (idx === 0 && partidasLista.length === 2) {
                html += '<div class="mc-vs"><span>VS</span></div>';
            }
        });

        html += '</div>';

        grid.innerHTML = html;

        // Bind events
        document.querySelectorAll('.btn-score-minus').forEach(function(btn) {
            pageScope.listen(btn, 'click', function() {
                if (!tempoEsgotado) ajustarGols(parseInt(btn.getAttribute('data-idx'), 10), -1);
            });
        });
        document.querySelectorAll('.btn-score-plus').forEach(function(btn) {
            pageScope.listen(btn, 'click', function() {
                if (!tempoEsgotado) ajustarGols(parseInt(btn.getAttribute('data-idx'), 10), 1);
            });
        });

        var selDuracao = document.getElementById('select-duracao');
        if (selDuracao) {
            pageScope.listen(selDuracao, 'change', function() {
                mudarDuracao(parseInt(selDuracao.value, 10) * 60);
            });
        }

        var btnPause = document.getElementById('btn-pausar');
        if (btnPause) {
            pageScope.listen(btnPause, 'click', function() { togglePause(); });
        }

        if (emAndamento) {
            iniciarTimerDisplay();
        }

        var durStat = document.getElementById('mc-duration-stat');
        if (durStat) durStat.textContent = Math.floor(duracaoJogo / 60) + ' min';
    }

    function ajustarGols(idx, delta) {
        var p = partidasLista[idx];
        var st = estadoJogo.status_jogo;
        if (!p || (st !== 'Iniciado' && st !== 'Pausado') || tempoEsgotado) return;
        var g = Math.max(0, (parseInt(p.resultado_partida, 10) || 0) + delta);
        p.resultado_partida = g;
        var el = document.querySelector('[data-gols="' + idx + '"]');
        if (el) {
            el.textContent = String(g).padStart(2, '0');
            el.classList.remove('score-animate');
            void el.offsetWidth;
            el.classList.add('score-animate');
        }
        // Partidas derivadas usam IDs textuais (mm_local_*). Preservar o
        // identificador original evita transformá-lo em NaN e perder a
        // alteração no IndexedDB.
        agendarSalvarPartida(p.id_partida, g);

        if (delta > 0 && ehFutsal()) {
            var idEquipe = parseInt(p.equipes_id_equipe, 10);
            abrirModalArtilheiro(idEquipe);
        }
    }

    function ehFutsal() {
        // O cache offline de instalações antigas pode não conter o tipo
        // numérico, embora ainda preserve o nome da modalidade.
        var tipo = parseInt(estadoJogo.tipos_modalidades_id_tipo_modalidade, 10);
        if (tipo === 1) return true;
        return /futsal/i.test(String(estadoJogo.nome_modalidade || ''));
    }

    async function carregarIndDados() {
        var idModalidade = estadoJogo ? estadoJogo.modalidades_id_modalidade : null;
        if (!idModalidade) {
            indParticipantes = [];
            indRankingAtual = [];
            return;
        }
        try {
            var resPart = await fetch(API + 'chaveamento.php?tipo_modalidade=individual&acao=participantes&id_modalidade=' + idModalidade);
            var resRank = await fetch(API + 'chaveamento.php?tipo_modalidade=individual&acao=ranking&id_modalidade=' + idModalidade);
            var dadosPart = await resPart.json();
            var dadosRank = await resRank.json();
            indParticipantes = (dadosPart.success && Array.isArray(dadosPart.participantes)) ? dadosPart.participantes : [];
            indRankingAtual = (dadosRank.success && Array.isArray(dadosRank.ranking)) ? dadosRank.ranking : [];
        } catch (e) {
            indParticipantes = [];
            indRankingAtual = [];
        }
    }

    function renderIndividual() {
        var grid = document.getElementById('placar-grid');
        if (indParticipantes.length === 0) {
            grid.innerHTML = '<div class="mc-empty"><i class="bi bi-inbox sgi-inline-c8c19351" ></i>Não há participantes vinculados a esta modalidade.</div>';
            return;
        }

        var gruposTurma = {};
        indParticipantes.forEach(function(p) {
            var turma = p.nome_fantasia_turma || p.nome_turma || 'Sem turma';
            if (!gruposTurma[turma]) gruposTurma[turma] = [];
            gruposTurma[turma].push(p);
        });
        var partOpts = '<option value="">Selecione...</option>' + Object.keys(gruposTurma).sort().map(function(turma) {
            return '<optgroup label="' + esc(turma) + '">' +
                gruposTurma[turma].sort(function(a, b) {
                    return (a.nome_usuario || '').localeCompare(b.nome_usuario || '');
                }).map(function(p) {
                    return '<option value="' + p.id_usuario + '">' + esc(p.nome_usuario) + '</option>';
                }).join('') +
                '</optgroup>';
        }).join('');

        var primeiroAtual = indRankingAtual.find(function(r) { return r.posicao === 1; });
        var segundoAtual = indRankingAtual.find(function(r) { return r.posicao === 2; });
        var terceiroAtual = indRankingAtual.find(function(r) { return r.posicao === 3; });

        var podiumHtml = '';
        if (indRankingAtual.length > 0) {
            var posLabels = ['1º Lugar', '2º Lugar', '3º Lugar'];
            var posIcons = ['🥇', '🥈', '🥉'];
            var posBg = ['#fef9c3', '#f3f4f6', '#fde8e8'];
            var posBd = ['#fde68a', '#e5e7eb', '#fecaca'];
            podiumHtml = '<div class="sgi-inline-f73315ee">';
            indRankingAtual.forEach(function(r, idx) {
                podiumHtml += '<div class="sgi-inline-972e2f0b">' +
                    '<div class="sgi-inline-b65dc436">' + (posIcons[idx] || '') + '</div>' +
                    '<div class="sgi-inline-cd92de86">' + (posLabels[idx] || '') + '</div>' +
                    '<div class="sgi-inline-c8d6e64b">' + esc(r.nome_usuario || 'Desconhecido') + '</div>' +
                    '<div class="sgi-inline-9ef3bed1">' + esc(r.nome_fantasia_turma || r.nome_turma || '') + '</div>' +
                '</div>';
            });
            podiumHtml += '</div>';
        } else {
            podiumHtml = '<div class="text-center py-4 text-muted sgi-inline-fea765bc" ><i class="bi bi-award d-block mb-2 sgi-inline-6a14bea4" ></i>Nenhum resultado registrado ainda.</div>';
        }

        grid.innerHTML =
            '<div class="sgi-inline-9b7b9ec4">' +
                '<div class="sgi-inline-62d7d349">' +
                    '<i class="bi bi-trophy-fill text-warning"></i> Registrar Resultado Individual' +
                '</div>' +
                '<div class="row g-3 mt-1">' +
                    '<div class="col-md-4">' +
                        '<label class="form-label fw-semibold sgi-inline-c3d0d941" >🥇 1º Lugar</label>' +
                        '<select class="form-select sgi-inline-72d7acfb" id="indSelectPrimeiro" >' + partOpts + '</select>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<label class="form-label fw-semibold sgi-inline-c3d0d941" >🥈 2º Lugar</label>' +
                        '<select class="form-select sgi-inline-72d7acfb" id="indSelectSegundo" >' + partOpts + '</select>' +
                    '</div>' +
                    '<div class="col-md-4">' +
                        '<label class="form-label fw-semibold sgi-inline-c3d0d941" >🥉 3º Lugar</label>' +
                        '<select class="form-select sgi-inline-72d7acfb" id="indSelectTerceiro" >' + partOpts + '</select>' +
                    '</div>' +
                '</div>' +
                '<div class="sgi-inline-a6ee76e1">' +
                    '<button type="button" class="mc-action-btn mc-action-btn--start" id="btnSalvarIndRanking"><i class="bi bi-check-lg"></i> Salvar Ranking</button>' +
                    '<span id="msgIndRanking" class="small"></span>' +
                '</div>' +
                '<div class="sgi-inline-c84e43b0">' +
                    '<div class="sgi-inline-f1b5cb0a"><i class="bi bi-award-fill me-1"></i>Ranking Atual</div>' +
                    podiumHtml +
                '</div>' +
            '</div>';

        if (primeiroAtual) document.getElementById('indSelectPrimeiro').value = primeiroAtual.id_usuario;
        if (segundoAtual) document.getElementById('indSelectSegundo').value = segundoAtual.id_usuario;
        if (terceiroAtual) document.getElementById('indSelectTerceiro').value = terceiroAtual.id_usuario;

        pageScope.listen(document.getElementById('btnSalvarIndRanking'), 'click', function() {
            salvarIndRanking();
        });
    }

    async function salvarIndRanking() {
        var primeiro = parseInt(document.getElementById('indSelectPrimeiro').value, 10);
        var segundo = parseInt(document.getElementById('indSelectSegundo').value, 10);
        var terceiro = parseInt(document.getElementById('indSelectTerceiro').value, 10);
        var msg = document.getElementById('msgIndRanking');
        var btn = document.getElementById('btnSalvarIndRanking');

        if (!primeiro || !segundo || !terceiro) {
            msg.innerHTML = '<span class="text-danger fw-bold">Selecione 1º, 2º e 3º lugar.</span>';
            return;
        }
        if (primeiro === segundo || primeiro === terceiro || segundo === terceiro) {
            msg.innerHTML = '<span class="text-danger fw-bold">Os participantes devem ser diferentes.</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Salvando...';
        msg.innerHTML = '';

        try {
            var resp = await fetch(API + 'chaveamento.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    tipo_modalidade: 'individual',
                    id_modalidade: parseInt(estadoJogo.modalidades_id_modalidade, 10),
                    ranking: { primeiro: primeiro, segundo: segundo, terceiro: terceiro }
                })
            });
            var data = await resp.json();
            if (!data.success) throw new Error(data.message || 'Erro ao salvar.');
            msg.innerHTML = '<span class="text-success fw-bold">Ranking salvo com sucesso!</span>';
            estadoJogo.status_jogo = 'Concluido';
            if (window.SGIDataLayer && window.SGIDataLayer.upsert) {
                await window.SGIDataLayer.upsert('jogos', estadoJogo.id_jogo, Object.assign({}, estadoJogo, { _pendente: true }));
            }
            await carregarIndDados();
            renderTudo();
        } catch (e) {
            msg.innerHTML = '<span class="text-danger fw-bold">' + esc(e.message) + '</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg"></i> Salvar Ranking';
        }
    }

    async function carregarDados(ciclo) {
        if (ciclo == null) ciclo = ++__sgiPlacarCiclo;
        pararTimer();
        idJogo = obterIdJogoAtual();
        var err = document.getElementById('placar-erro');
        var load = document.getElementById('placar-loading');
        var cont = document.getElementById('placar-conteudo');
        if (!err || !load || !cont || !placarContinuaAtivo(ciclo)) return;
        err.classList.add('d-none');
        load.classList.remove('d-none');
        cont.classList.add('d-none');

        if (!idJogo) {
            load.classList.add('d-none');
            err.textContent = 'Informe o jogo na URL (?id_jogo=…).';
            err.classList.remove('d-none');
            return;
        }

        /* ID temporário: partidas geradas OFFLINE pelo motor de chaveamento
           recebem id negativo provisório. Elas EXISTEM no banco JS temporário
           e podem ser jogadas normalmente — carregamos direto das tabelas
           locais. Só não há dados auxiliares (ocorrências/artilheiro). */
        if (idJogo < 0) {
            var carregou = await carregarJogoLocalTemporario(ciclo);
            if (!placarContinuaAtivo(ciclo)) return;
            if (!carregou) {
                load.classList.add('d-none');
                err.textContent = 'Esta partida foi gerada offline, mas ainda não está disponível neste dispositivo. Sincronize para receber o jogo definitivo.';
                err.classList.remove('d-none');
            }
            return;
        }

        try {
            var lista = await fetchJson(API + 'jogos.php?id_jogo=' + idJogo);
            if (!placarContinuaAtivo(ciclo)) return;
            if (!Array.isArray(lista) || lista.length === 0) throw new Error('Jogo não encontrado.');
            estadoJogo = lista[0];
            normalizarJogoRecebido(estadoJogo);
            if (!estadoJogo.nome_modalidade) estadoJogo.nome_modalidade = '';
            if (estadoJogo.status_jogo === 'Concluido' || estadoJogo.status_jogo === 'Finalizado') {
                tempoEsgotado = true;
            }

            partidasLista = await fetchJson(API + 'partidas.php?id_jogo=' + idJogo);
            if (!placarContinuaAtivo(ciclo)) return;
            if (!Array.isArray(partidasLista)) partidasLista = [];
            await enriquecerPartidasComTurmas();
            if (!placarContinuaAtivo(ciclo)) return;
            precarregarDadosOffline();

            ehIndividual = /^IND:\d+$/.test(estadoJogo.nome_jogo || '') || parseInt(estadoJogo.tipos_modalidades_id_tipo_modalidade, 10) === 2;
            if (ehIndividual) {
                await carregarIndDados();
                if (!placarContinuaAtivo(ciclo)) return;
            }

            // Restaurar duração do jogo do servidor
            if (estadoJogo.duracao_jogo) {
                var d = parseInt(estadoJogo.duracao_jogo, 10);
                if (!isNaN(d) && d > 0) duracaoJogo = d;
            }

            var tempoRestanteInformado = false;
            if (estadoJogo.tempo_restante_calculado != null) {
                var v = parseInt(estadoJogo.tempo_restante_calculado, 10);
                if (!isNaN(v) && v > 0) {
                    tempoRestante = v;
                    tempoRestanteInformado = true;
                } else if (v <= 0 && (estadoJogo.status_jogo === 'Iniciado' || estadoJogo.status_jogo === 'Pausado')) {
                    tempoRestante = 0;
                    tempoEsgotado = true;
                    tempoRestanteInformado = true;
                }
            } else if (estadoJogo.tempo_restante_jogo != null) {
                var v2 = parseInt(estadoJogo.tempo_restante_jogo, 10);
                if (!isNaN(v2) && v2 > 0) {
                    tempoRestante = v2;
                    tempoRestanteInformado = true;
                }
            }
            // Um jogo iniciado offline pela agenda pode ter sido projetado no
            // IndexedDB apenas com o novo status (bases antigas do cache). Sem
            // um tempo informado pelo servidor, ele ainda deve começar com a
            // duração padrão, e não aparecer imediatamente como esgotado.
            if (!tempoRestanteInformado &&
                (estadoJogo.status_jogo === 'Iniciado' || estadoJogo.status_jogo === 'Pausado') &&
                !estadoJogo.data_inicio_real) {
                tempoRestante = duracaoJogo;
                tempoEsgotado = false;
            }

            load.classList.add('d-none');
            cont.classList.remove('d-none');
            renderTudo();

            if (ehFutsal()) {
                iniciarArtilheiro(ciclo);
            }
            iniciarOcorrencias(ciclo);
            acompanharSincronizacaoPlacar();
        } catch (e) {
            if (!placarContinuaAtivo(ciclo)) return;
            var okLocal = await carregarJogoLocalTemporario(ciclo);
            if (!placarContinuaAtivo(ciclo)) return;
            if (!okLocal) {
                load.classList.add('d-none');
                err.textContent = e.message || 'Erro ao carregar.';
                err.classList.remove('d-none');
            }
        }
    }

    function iniciarOcorrencias(ciclo) {
        var section = document.getElementById('ocorrencias-section');
        if (!section || !section.isConnected) return;
        section.classList.remove('d-none');
        carregarOcorrencias(ciclo);
        carregarTurmasOcorrencia();
    }

    // Pré-carrega os dados que o mesário precisa OFFLINE: lista de atletas por
    // turma (usada no modal "quem fez o ponto" e no de ocorrências), artilharia
    // e ocorrências do dia. Enquanto online, cada GET é guardado no IndexedDB
    // pelo offline-core.js, ficando disponível quando a conexão cair.
    function precarregarDadosOffline() {
        if (!navigator.onLine) return;
        var urls = [];
        var vistas = {};
        partidasLista.forEach(function (p) {
            var idTurma = parseInt(p.id_turma, 10);
            if (!idTurma || vistas[idTurma]) return;
            vistas[idTurma] = true;
            urls.push(API + 'ocorrencias.php?acao=listar_atletas&id_jogo=' + idJogo + '&id_turma=' + idTurma);
        });
        urls.push(API + 'artilheiro.php?id_jogo=' + idJogo);
        urls.push(API + 'ocorrencias.php?id_jogo=' + idJogo + '&data=' + (estadoJogo.data_jogo || ''));
        urls.forEach(function (u) {
            fetch(u).then(function (r) { return r.text(); }).catch(function () { /* offline pre-cache é best-effort */ });
        });
    }

    function carregarTurmasOcorrencia() {
        var select = document.getElementById('filtroTurmaOcorrencia');
        if (!select || !select.isConnected) return;
        select.innerHTML = '<option value="">Selecione a turma</option>';
        var vistas = {};
        partidasLista.forEach(function(p) {
            var idTurma = parseInt(p.id_turma, 10);
            if (!idTurma) return;
            var nome = esc(nomeEquipe(p));
            if (!vistas[idTurma]) {
                vistas[idTurma] = true;
                select.innerHTML += '<option value="' + idTurma + '">' + nome + '</option>';
            }
        });
    }

    async function carregarAlunosOcorrencia(ciclo) {
        var cicloLocal = ciclo == null ? __sgiPlacarCiclo : ciclo;
        var select = document.getElementById('selectAlunoOcorrencia');
        if (!select || !select.isConnected || !placarContinuaAtivo(cicloLocal)) return;
        var idTurma = document.getElementById('filtroTurmaOcorrencia').value;
        if (!idTurma) {
            select.value = '';
            select.disabled = true;
            select.innerHTML = '<option value="">Selecione uma turma primeiro</option>';
            return;
        }
        select.disabled = true;
        select.innerHTML = '<option value="">Carregando...</option>';
        try {
            var data = await fetchJson(API + 'ocorrencias.php?acao=listar_atletas&id_jogo=' + idJogo + '&id_turma=' + idTurma);
            if (!placarContinuaAtivo(cicloLocal) || !select.isConnected || document.getElementById('selectAlunoOcorrencia') !== select) return;
            var alunos = data.success && Array.isArray(data.atletas) ? data.atletas : [];
            select.innerHTML = '<option value="">Selecione o(a) aluno(a)</option>';
            alunos.forEach(function(a) {
                select.innerHTML += '<option value="' + a.id_usuario + '">' + esc(a.nome_usuario) + '</option>';
            });
            select.disabled = false;
        } catch (e) {
            if (placarContinuaAtivo(cicloLocal) && select && select.isConnected) {
                select.innerHTML = '<option value="">Erro ao carregar alunos</option>';
                select.disabled = false;
            }
        }
    }

    function limparDescricaoOcorrencia(desc) {
        return (desc || '').replace(/\[JOGO:\d+\]/g, '').replace(/\[TURMA:\d+\]/g, '').trim();
    }

    async function carregarOcorrencias(ciclo) {
        var cicloLocal = ciclo == null ? __sgiPlacarCiclo : ciclo;
        var container = document.getElementById('lista-ocorrencias');
        if (!container || !container.isConnected || !placarContinuaAtivo(cicloLocal)) return;
        try {
            var data = await fetchJson(API + 'ocorrencias.php?id_jogo=' + idJogo + '&data=' + (estadoJogo.data_jogo || ''));
            if (!placarContinuaAtivo(cicloLocal) || !container.isConnected || document.getElementById('lista-ocorrencias') !== container) return;
            var lista = Array.isArray(data) ? data : [];
            var countEl = document.getElementById('mc-occ-count');
            if (countEl) countEl.textContent = lista.length;
            if (!lista.length) {
                container.innerHTML = '<div class="mc-timeline-empty"><i class="bi bi-clock-history"></i><p>Nenhuma ocorrência registrada.</p></div>';
                return;
            }
            container.innerHTML = lista.map(function(o, i) {
                var tipo = (o.titulo_ocorrencia || '').toLowerCase();
                var isAmarelo = tipo === 'amarelo';
                var isVermelho = tipo.indexOf('vermelho') !== -1;
                var isSuspensao = tipo.indexOf('suspensao') !== -1 || tipo.indexOf('suspensão') !== -1;

                var cls = isAmarelo ? 'amarelo' : (isVermelho ? 'vermelho' : 'suspensao');
                var icon = isAmarelo ? 'bi-square-fill' : (isVermelho ? 'bi-x-octagon-fill' : 'bi-pause-circle-fill');
                var label = isAmarelo ? 'Cartão Amarelo' : (isVermelho ? 'Cartão Vermelho' : 'Suspensão');

                var pts = parseInt(o.penalidade, 10);
                var ptsHtml = pts > 0 ? '<span class="tl-badge">-' + pts + ' pts</span>' : '';
                var isLast = i === lista.length - 1;

                var acoesHtml = '';
                if (!jogoEncerrado()) {
                    var idOcorrenciaHtml = String(o.id_ocorrencia == null ? '' : o.id_ocorrencia)
                        .replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                    acoesHtml = '<div class="tl-event-actions">' +
                        '<button type="button" class="tl-action-btn tl-action-btn--edit" onclick="editarOcorrencia(\'' + idOcorrenciaHtml + '\')" title="Editar"><i class="bi bi-pencil-square"></i></button>' +
                        '<button type="button" class="tl-action-btn tl-action-btn--delete" onclick="excluirOcorrencia(\'' + idOcorrenciaHtml + '\')" title="Excluir"><i class="bi bi-trash3"></i></button>' +
                        '</div>';
                }

                return '<div class="tl-event tl-event--' + cls + (isLast ? ' tl-event--last' : '') + '">' +
                    '<div class="tl-event-track">' +
                        '<div class="tl-event-dot"></div>' +
                        '<div class="tl-event-line"></div>' +
                    '</div>' +
                    '<div class="tl-event-body">' +
                        '<div class="tl-event-top">' +
                            '<span class="tl-event-icon"><i class="bi ' + icon + '"></i></span>' +
                            '<span class="tl-event-label">' + label + '</span>' +
                            ptsHtml +
                        '</div>' +
                        '<div class="tl-event-player">' + esc(o.nome_usuario) + '</div>' +
                        '<div class="tl-event-desc">' + esc(limparDescricaoOcorrencia(o.descricao_ocorrencia)) + '</div>' +
                        acoesHtml +
                    '</div>' +
                '</div>';
            }).join('');
        } catch (e) {
            if (placarContinuaAtivo(cicloLocal) && container && container.isConnected) {
                container.innerHTML = '<div class="mc-timeline-empty mc-timeline-empty--error"><i class="bi bi-exclamation-circle"></i><p>Erro ao carregar ocorrências.</p></div>';
            }
        }
    }

    var _editandoOcorrenciaId = null;
    var _ocorrenciaModalHideTimer = null;

    function cancelarFechamentoOcorrenciaPendente() {
        if (_ocorrenciaModalHideTimer !== null) {
            clearTimeout(_ocorrenciaModalHideTimer);
            _ocorrenciaModalHideTimer = null;
        }
    }

    function abrirModalOcorrencia() {
        if (jogoEncerrado()) {
            alert('O jogo já foi encerrado. Não é possível registrar ocorrências.');
            return;
        }
        cancelarFechamentoOcorrenciaPendente();
        _editandoOcorrenciaId = null;
        document.getElementById('formOcorrencia').reset();
        document.getElementById('msgOcorrencia').innerHTML = '';
        document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
            el.classList.remove('active');
        });
        var selAluno = document.getElementById('selectAlunoOcorrencia');
        selAluno.disabled = true;
        selAluno.innerHTML = '<option value="">Selecione uma turma primeiro</option>';
        document.getElementById('btnSalvarOcorrencia').disabled = false;
        document.getElementById('btnSalvarOcorrencia').innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar';
        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOcorrencia'));
        modal.show();
    }

    async function editarOcorrencia(id) {
        if (jogoEncerrado()) {
            alert('O jogo já foi encerrado. Não é possível editar ocorrências.');
            return;
        }
        cancelarFechamentoOcorrenciaPendente();
        var idSolicitado = String(id);
        _editandoOcorrenciaId = idSolicitado;
        document.getElementById('formOcorrencia').reset();
        document.getElementById('msgOcorrencia').innerHTML = '';
        document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
            el.classList.remove('active');
        });
        var selAluno = document.getElementById('selectAlunoOcorrencia');
        selAluno.disabled = true;
        selAluno.innerHTML = '<option value="">Carregando...</option>';
        document.getElementById('btnSalvarOcorrencia').disabled = false;
        document.getElementById('btnSalvarOcorrencia').innerHTML = '<i class="bi bi-check-lg me-1"></i>Atualizar';

        try {
            var lista = await fetchJson(API + 'ocorrencias.php?id_ocorrencia=' + encodeURIComponent(idSolicitado));
            var o = Array.isArray(lista) ? lista.find(function (item) {
                return item && String(item.id_ocorrencia) === idSolicitado;
            }) : null;
            if (!o) throw new Error('Ocorrência não disponível neste dispositivo.');
            document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
                if (el.getAttribute('data-tipo') === o.titulo_ocorrencia) {
                    el.classList.add('active');
                    var radio = el.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                }
            });
            document.getElementById('descricaoOcorrencia').value = limparDescricaoOcorrencia(o.descricao_ocorrencia);
            document.getElementById('penalidadeOcorrencia').value = o.penalidade || 0;
            var idTurma = o.turmas_id_turma || 0;
            if (idTurma) {
                document.getElementById('filtroTurmaOcorrencia').value = idTurma;
                try {
                    await carregarAlunosOcorrencia();
                } catch (e) {}
                var sel = document.getElementById('selectAlunoOcorrencia');
                sel.value = o.id_usuario;
                if (sel.value !== String(o.id_usuario)) {
                    var opt = document.createElement('option');
                    opt.value = o.id_usuario;
                    opt.textContent = esc(o.nome_usuario);
                    sel.appendChild(opt);
                    sel.value = o.id_usuario;
                }
            }
            var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalOcorrencia'));
            modal.show();
        } catch (e) {
            _editandoOcorrenciaId = null;
            document.getElementById('btnSalvarOcorrencia').innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar';
            alert(e && e.message ? e.message : 'Ocorrência não disponível neste dispositivo.');
        }
    }

    async function excluirOcorrencia(id) {
        if (jogoEncerrado()) {
            alert('O jogo já foi encerrado. Não é possível excluir ocorrências.');
            return;
        }
        if (!confirm('Tem certeza que deseja excluir esta ocorrência?')) return;
        try {
            await fetchJson(API + 'ocorrencias.php', {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_ocorrencia: id, status_ocorrencia: '0' })
            });
            carregarOcorrencias();
        } catch (e) {
            alert('Erro ao excluir ocorrência.');
        }
    }

    async function salvarOcorrencia(e) {
        e.preventDefault();
        if (jogoEncerrado()) {
            alert('O jogo já foi encerrado. Não é possível salvar ocorrências.');
            return;
        }
        var btn = document.getElementById('btnSalvarOcorrencia');
        var msg = document.getElementById('msgOcorrencia');
        msg.innerHTML = '';

        var editando = _editandoOcorrenciaId;

        var tipoEl = document.querySelector('.ocorrencia-tipo-option.active');
        if (!tipoEl) {
            msg.innerHTML = '<span class="text-danger">Selecione o tipo de ocorrência.</span>';
            return;
        }
        var tipo = tipoEl.getAttribute('data-tipo');

        var idTurma = document.getElementById('filtroTurmaOcorrencia').value;
        if (!idTurma) {
            msg.innerHTML = '<span class="text-danger">Selecione a turma.</span>';
            return;
        }

        var idAluno = document.getElementById('selectAlunoOcorrencia').value;
        if (!idAluno) {
            msg.innerHTML = '<span class="text-danger">Selecione o(a) aluno(a).</span>';
            return;
        }
        var descricao = document.getElementById('descricaoOcorrencia').value.trim();
        if (!descricao) {
            msg.innerHTML = '<span class="text-danger">Informe a descrição.</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        var isUpdate = !!editando;
        var payload = {
            titulo_ocorrencia: tipo,
            descricao_ocorrencia: descricao,
            data_ocorrencia: estadoJogo.data_jogo || new Date().toISOString().slice(0, 10),
            usuarios_id_usuario: parseInt(idAluno, 10),
            penalidade: parseInt(document.getElementById('penalidadeOcorrencia').value, 10)
        };

        if (isUpdate) {
            payload.id_ocorrencia = editando;
        } else {
            payload.id_jogo = idJogo;
            payload.id_turma = parseInt(idTurma, 10);
            // Necessário para que uma ocorrência de jogo criado localmente
            // seja vinculada ao confronto definitivo durante a sincronização.
            payload.nome_jogo = (estadoJogo && estadoJogo.nome_jogo) || null;
            payload.id_modalidade = (estadoJogo && (estadoJogo.modalidades_id_modalidade || estadoJogo.id_modalidade)) || null;
        }

        try {
            var resp = await fetch(API + 'ocorrencias.php', {
                method: isUpdate ? 'PUT' : 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            var result = await resp.json();
            if (result.success) {
                msg.innerHTML = '<span class="text-success">Ocorrência ' + (isUpdate ? 'atualizada' : 'registrada') + '!</span>';
                if (!isUpdate && result.evento === 'segundo_amarelo') {
                    var selAluno = document.getElementById('selectAlunoOcorrencia');
                    var nomeAluno = selAluno.options[selAluno.selectedIndex] ? selAluno.options[selAluno.selectedIndex].text : '';
                    carregarAlunosOcorrencia();
                    mostrarAlertaSegundoAmarelo(nomeAluno);
                } else if (!isUpdate && (tipo === 'Vermelho' || tipo === 'Suspensao')) {
                    carregarAlunosOcorrencia();
                }
                cancelarFechamentoOcorrenciaPendente();
                _ocorrenciaModalHideTimer = setTimeout(function() {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalOcorrencia'));
                    if (m) m.hide();
                    _editandoOcorrenciaId = null;
                    _ocorrenciaModalHideTimer = null;
                    carregarOcorrencias();
                }, 600);
            } else {
                msg.innerHTML = '<span class="text-danger">' + (result.message || 'Erro ao salvar.') + '</span>';
                btn.disabled = false;
                btn.innerHTML = isUpdate ? '<i class="bi bi-check-lg me-1"></i>Atualizar' : '<i class="bi bi-check-lg me-1"></i>Registrar';
            }
        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
            btn.disabled = false;
            btn.innerHTML = isUpdate ? '<i class="bi bi-check-lg me-1"></i>Atualizar' : '<i class="bi bi-check-lg me-1"></i>Registrar';
        }
    }

    function iniciarArtilheiro(ciclo) {
        var section = document.getElementById('artilheiro-section');
        if (!section || !section.isConnected) return;
        section.classList.remove('d-none');
        carregarEquipesArtilheiro();
        carregarArtilheiros(ciclo);
    }

    // Quando uma mutação offline é confirmada pelo servidor, atualiza as
    // listas que exibem dados agregados (artilharia e timeline). Sem essa
    // atualização a fila ficava vazia, mas a tela permanecia com o registro
    // temporário "Desconhecido / 0 gol" até um reload manual.
    function acompanharSincronizacaoPlacar() {
        if (!window.SGIOffline || typeof window.SGIOffline.onStateChange !== 'function') return;
        if (window.__SGI_PLACAR_SYNC_UNSUB__) {
            try { window.__SGI_PLACAR_SYNC_UNSUB__(); } catch (_) {}
        }
        var pendentesAntes = Number(window.SGIOffline.getState && window.SGIOffline.getState().pending) || 0;
        window.__SGI_PLACAR_SYNC_UNSUB__ = window.SGIOffline.onStateChange(function (snapshot) {
            var pendentesAgora = Number(snapshot && snapshot.pending) || 0;
            if (snapshot && snapshot.online && pendentesAntes > 0 && pendentesAgora === 0) {
                carregarArtilheiros();
                carregarOcorrencias();
            }
            pendentesAntes = pendentesAgora;
        });
    }

    async function carregarArtilheiros(ciclo) {
        var cicloLocal = ciclo == null ? __sgiPlacarCiclo : ciclo;
        var cards = document.getElementById('artilheiro-cards');
        if (!cards || !cards.isConnected || !placarContinuaAtivo(cicloLocal)) return;
        try {
            var data = await fetchJson(API + 'artilheiro.php?id_jogo=' + idJogo);
            if (!placarContinuaAtivo(cicloLocal) || !cards.isConnected || document.getElementById('artilheiro-cards') !== cards) return;
            if (!Array.isArray(data) || data.length === 0) {
                cards.innerHTML = '<div class="mc-artilheiro-empty"><i class="bi bi-trophy"></i><p>Nenhum gol registrado ainda.</p></div>';
                return;
            }
            var html = '';
            data.forEach(function(a) {
                var nome = esc(a.nome_usuario || 'Desconhecido');
                var turma = esc(a.nome_fantasia_turma || a.nome_turma || '');
                var gols = parseInt(a.total_gols, 10) || 0;
                var icon = gols >= 3 ? 'bi-star-fill text-warning' : gols >= 2 ? 'bi-fire text-danger' : 'bi-circle-fill text-success';
                html += '<div class="mc-artilheiro-card">' +
                    '<div class="mc-artilheiro-card-icon"><i class="bi ' + icon + '"></i></div>' +
                    '<div class="mc-artilheiro-card-info">' +
                    '<div class="mc-artilheiro-card-nome">' + nome + '</div>' +
                    '<div class="mc-artilheiro-card-turma">' + turma + '</div>' +
                    '</div>' +
                    '<div class="mc-artilheiro-card-gols">' + gols + ' gol' + (gols > 1 ? 's' : '') + '</div>' +
                    '</div>';
            });
            cards.innerHTML = html;
        } catch (e) {
            if (placarContinuaAtivo(cicloLocal) && cards && cards.isConnected) {
                cards.innerHTML = '<div class="mc-artilheiro-empty"><i class="bi bi-exclamation-circle"></i><p>Erro ao carregar artilharia.</p></div>';
            }
        }
    }

    async function carregarEquipesArtilheiro() {
        var select = document.getElementById('selectEquipeArtilheiro');
        if (!select || !select.isConnected) return;
        select.innerHTML = '<option value="">Selecione a equipe</option>';
        partidasLista.forEach(function(p) {
            select.innerHTML += '<option value="' + p.equipes_id_equipe + '">' + esc(nomeEquipe(p)) + '</option>';
        });
    }

    async function carregarAlunosArtilheiro(ciclo) {
        var cicloLocal = ciclo == null ? __sgiPlacarCiclo : ciclo;
        var select = document.getElementById('selectAlunoArtilheiro');
        if (!select || !select.isConnected || !placarContinuaAtivo(cicloLocal)) return;
        var idEquipe = document.getElementById('selectEquipeArtilheiro').value;
        if (!idEquipe) {
            select.innerHTML = '<option value="">Selecione uma equipe primeiro</option>';
            return;
        }
        select.innerHTML = '<option value="">Carregando...</option>';
        try {
            var p = partidasLista.find(function(p) { return p.equipes_id_equipe == idEquipe; });
            if (!p) throw new Error('Equipe não encontrada');
            var idTurma = p.id_turma;
            var data = await fetchJson(API + 'ocorrencias.php?acao=listar_atletas&id_jogo=' + idJogo + '&id_turma=' + idTurma);
            if (!placarContinuaAtivo(cicloLocal) || !select.isConnected || document.getElementById('selectAlunoArtilheiro') !== select) return;
            var alunos = data.success && Array.isArray(data.atletas) ? data.atletas : [];
            select.innerHTML = '<option value="">Selecione o(a) jogador(a)</option>';
            alunos.forEach(function(a) {
                select.innerHTML += '<option value="' + a.id_usuario + '">' + esc(a.nome_usuario) + '</option>';
            });
            if (!alunos.length) {
                select.innerHTML = '<option value="">Nenhum jogador disponível</option>';
            }
        } catch (e) {
            if (placarContinuaAtivo(cicloLocal) && select && select.isConnected) {
                select.innerHTML = '<option value="">Erro ao carregar jogadores</option>';
            }
        }
    }

    var _artilheiroEquipeAtual = null;

    function abrirModalArtilheiro(idEquipe) {
        if (jogoEncerrado()) {
            alert('O jogo já foi encerrado. Não é possível registrar artilharia.');
            return;
        }
        _artilheiroEquipeAtual = idEquipe;
        document.getElementById('formArtilheiro').reset();
        document.getElementById('msgArtilheiro').innerHTML = '';

        var selectEquipe = document.getElementById('selectEquipeArtilheiro');
        selectEquipe.value = idEquipe;
        carregarAlunosArtilheiro();

        var modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalArtilheiro'));
        modal.show();
    }

    async function salvarArtilheiro(e) {
        e.preventDefault();
        if (jogoEncerrado()) {
            alert('O jogo já foi encerrado. Não é possível salvar artilharia.');
            return;
        }
        var btn = document.getElementById('btnSalvarArtilheiro');
        var msg = document.getElementById('msgArtilheiro');
        msg.innerHTML = '';

        var idAluno = document.getElementById('selectAlunoArtilheiro').value;
        if (!idAluno) {
            msg.innerHTML = '<span class="text-danger">Selecione o(a) jogador(a).</span>';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Salvando...';

        try {
            var resp = await fetch(API + 'artilheiro.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    usuarios_id_usuario: parseInt(idAluno, 10),
                    jogos_id_jogo: idJogo,
                    // Em jogos derivados offline, o ID ainda é negativo. A tag
                    // e a modalidade permitem resolver o jogo definitivo na
                    // sincronização, depois que o resultado materializá-lo.
                    nome_jogo: (estadoJogo && estadoJogo.nome_jogo) || null,
                    id_modalidade: (estadoJogo && (estadoJogo.modalidades_id_modalidade || estadoJogo.id_modalidade)) || null,
                    num_gol: 1
                })
            });
            var result = await resp.json();
            if (result.success) {
                msg.innerHTML = '<span class="text-success">Gol registrado!</span>';
                carregarArtilheiros();
                setTimeout(function() {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar Gol';
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalArtilheiro'));
                    if (m) m.hide();
                }, 600);
            } else {
                msg.innerHTML = '<span class="text-danger">' + (result.message || 'Erro ao registrar.') + '</span>';
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar Gol';
            }
        } catch (err) {
            msg.innerHTML = '<span class="text-danger">Erro de conexão.</span>';
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Registrar Gol';
        }
    }

    function mostrarAlertaSegundoAmarelo(nomeAluno) {
        var container = document.getElementById('alerta-segundo-amarelo');
        if (!container) return;
        var nome = nomeAluno || 'Jogador(a)';
        container.innerHTML =
            '<div class="alert d-flex align-items-center gap-3 py-3 px-4 mb-0 rounded-3 shadow-sm border-0 sgi-inline-cfb95997" role="alert" >' +
                '<span class="sgi-inline-89d782b9">V</span>' +
                '<div class="flex-grow-1">' +
                    '<strong class="d-block mb-1 sgi-inline-abc60b0c" >SEGUNDO CARTÃO AMARELO</strong>' +
                    '<span class="sgi-inline-499ae08d">' + esc(nome) + ' recebeu o segundo amarelo e foi expulso(a) da partida (Cartão Vermelho automático).</span>' +
                '</div>' +
                '<button type="button" class="btn-close sgi-inline-df84ea67" data-bs-dismiss="alert" aria-label="Fechar" ></button>' +
            '</div>';
        setTimeout(function() {
            var alert = container.querySelector('.alert');
            if (alert) {
                alert.style.transition = 'opacity 0.3s, transform 0.3s';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-8px)';
                setTimeout(function() { if (alert.parentNode) alert.remove(); }, 350);
            }
        }, 8000);
    }

    function prepararFechamentoAcessivelModais() {
        ['modalArtilheiro', 'modalOcorrencia'].forEach(function(idModal) {
            var modal = document.getElementById(idModal);
            if (!modal || modal.dataset.sgiFocoSeguro === '1') return;
            modal.dataset.sgiFocoSeguro = '1';
            window.SGIPage.prepareModal(modal);
            pageScope.listen(modal, 'hide.bs.modal', function() {
                var foco = document.activeElement;
                if (foco && modal.contains(foco) && typeof foco.blur === 'function') {
                    foco.blur();
                }
            });
        });
    }

    function ativarTelaPlacar() {
        prepararFechamentoAcessivelModais();

        // A tela pode voltar de uma montagem já existente. Nesse caso o
        // script não é reinjetado; reanexar o listener e registrar a limpeza
        // aqui evita que a segunda saída deixe timers/callbacks ativos.
        if (!__sgiPlacarClickHandler) {
            __sgiPlacarClickHandler = function(e) {
                var opt = e.target.closest('.ocorrencia-tipo-option');
                if (opt) {
                    document.querySelectorAll('.ocorrencia-tipo-option').forEach(function(el) {
                        el.classList.remove('active');
                    });
                    opt.classList.add('active');
                    var radio = opt.querySelector('input[type="radio"]');
                    if (radio) radio.checked = true;
                }
            };
            pageScope.listen(document, 'click', __sgiPlacarClickHandler);
        }
        window.__SGI_TELA_CLEANUP__ = __sgiPlacarCleanup;
        var ciclo = ++__sgiPlacarCiclo;
        carregarDados(ciclo);
    }

    window.SGIPage.ready( ativarTelaPlacar);

return {obterIdJogoAtual, paginaOrigem, definirLinkVoltar, formatNomeJogo, nomeEquipe, enriquecerPartidasComTurmas, esc, fetchJson, pararTimer, atualizarDisplayTimer, tocarAlertaSonoro, bloquearPontuacao, mostrarBotoesTempoExtra, adicionarTempoExtra, iniciarTimerDisplay, togglePause, mudarDuracao, agendarSalvarPartida, salvarPartida, persistirJogoLocal, jogoEncerrado, iniciarJogoServidor, finalizarJogo, aplicarFinalizacaoUI, finalizarLocalmente, placarContinuaAtivo, carregarJogoLocalTemporario, renderTudo, ajustarGols, ehFutsal, carregarIndDados, renderIndividual, salvarIndRanking, carregarDados, iniciarOcorrencias, precarregarDadosOffline, carregarTurmasOcorrencia, carregarAlunosOcorrencia, limparDescricaoOcorrencia, carregarOcorrencias, abrirModalOcorrencia, editarOcorrencia, excluirOcorrencia, salvarOcorrencia, iniciarArtilheiro, acompanharSincronizacaoPlacar, carregarArtilheiros, carregarEquipesArtilheiro, carregarAlunosArtilheiro, abrirModalArtilheiro, salvarArtilheiro, mostrarAlertaSegundoAmarelo, prepararFechamentoAcessivelModais, ativarTelaPlacar};
});
