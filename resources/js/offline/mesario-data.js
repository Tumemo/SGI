/* SGI Mesário — dados estruturados locais (IndexedDB, sem dependência CDN).
   As stores equivalem às tabelas Dexie: jogos, partidas, atletas, turmas,
   modalidades, categorias, locais, ocorrencias, chaveamentos e fila_sincronizacao. */
(function () {
    'use strict';
    if (window.__SGI_MESARIO_DATA__) return;
    window.__SGI_MESARIO_DATA__ = true;

    var DB = 'sgi_mesario_dados', VERSION = 2;
    var STORES = ['jogos', 'partidas', 'atletas', 'turmas', 'modalidades', 'categorias', 'locais', 'equipes',
        'ocorrencias', 'ocorrencias_turmas', 'chaveamentos', 'pontos', 'fila_sincronizacao'];
    // Namespace opaco por usuário; não usar o ID persistente diretamente.
    var session = String(window.SGI_CACHE_KEY || 'anon');
    var dbPromise;

    function open() {
        if (dbPromise) return dbPromise;
        dbPromise = new Promise(function (resolve, reject) {
            var request = indexedDB.open(DB, VERSION);
            request.onupgradeneeded = function (e) {
                var db = e.target.result;
                STORES.forEach(function (name) {
                    if (!db.objectStoreNames.contains(name)) db.createObjectStore(name, { keyPath: 'key' });
                });
            };
            request.onsuccess = function () { resolve(request.result); };
            request.onerror = function () { reject(request.error); };
        });
        return dbPromise;
    }
    function key(store, id) { return session + '|' + store + '|' + String(id); }
    function put(store, id, value) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var tx = db.transaction(store, 'readwrite');
            tx.objectStore(store).put({ key: key(store, id), value: value, savedAt: Date.now() });
            tx.oncomplete = resolve; tx.onerror = function () { reject(tx.error); };
        }); });
    }
    function get(store, id) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var r = db.transaction(store, 'readonly').objectStore(store).get(key(store, id));
            r.onsuccess = function () { resolve(r.result && r.result.value); }; r.onerror = function () { reject(r.error); };
        }); });
    }
    function remove(store, id) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var tx = db.transaction(store, 'readwrite'); tx.objectStore(store).delete(key(store, id));
            tx.oncomplete = resolve; tx.onerror = function () { reject(tx.error); };
        }); });
    }
    function all(store) {
        return open().then(function (db) { return new Promise(function (resolve, reject) {
            var r = db.transaction(store, 'readonly').objectStore(store).getAll();
            r.onsuccess = function () { resolve((r.result || []).filter(function (x) { return x.key.indexOf(session + '|') === 0; }).map(function (x) { return x.value; })); };
            r.onerror = function () { reject(r.error); };
        }); });
    }
    function urlInfo(url) {
        try {
            var u = new URL(url, location.href);
            var file = u.pathname.replace(/\/+$/, '').split('/').pop();
            // As rotas versionadas usam os nomes canônicos dos recursos.
            var recursos = {
                'jogos': 'jogos',
                'resultados': 'resultados',
                'turmas': 'turmas',
                'modalidades': 'modalidades',
                'categorias': 'categorias',
                'locais': 'locais',
                'equipes': 'equipes',
                'partidas': 'partidas',
                'ocorrencias': 'ocorrencias',
                'ocorrencias-turmas': 'ocorrencias_turmas',
                'artilheiros': 'artilheiros',
                'pontos': 'pontos',
                'chaveamentos': 'chaveamentos'
            };
            return { file: recursos[file] || file, q: u.searchParams };
        } catch (_) { return {}; }
    }
    function idFor(file, row, fallback) {
        var fields = { jogos: 'id_jogo', partidas: 'id_partida', turmas: 'id_turma', modalidades: 'id_modalidade', categorias: 'id_categoria', locais: 'id_local', equipes: 'id_equipe', artilheiros: 'id_artilheiro', pontos: 'id_ponto', ocorrencias: 'id_ocorrencia', ocorrencias_turmas: 'id_ocorrencia_turma' };
        return row && (row[fields[file]] || row.id || fallback);
    }
    function capture(url, text) {
        var info = urlInfo(url), file = info.file, data;
        try { data = JSON.parse(text); } catch (_) { return Promise.resolve(); }
        // Algumas APIs do SGI (por exemplo locais) encapsulam a lista em
        // { success: true, data: [...] }. Sem reconhecer `data`, os locais
        // nunca entravam no IndexedDB e as partidas derivadas offline perdiam
        // o nome da quadra/local no placar.
        var action = info.q && info.q.get('acao');
        var rows = Array.isArray(data) ? data : (data && (data.dados || data.data || data.ranking || data.participantes || data.atletas || data.pontos));
        var store = file === 'pontos' && action === 'atletas'
            ? 'atletas'
            : ({ 'jogos': 'jogos', 'partidas': 'partidas', 'turmas': 'turmas', 'modalidades': 'modalidades', 'categorias': 'categorias', 'locais': 'locais', 'equipes': 'equipes', 'artilheiros': 'atletas', 'pontos': 'pontos', 'ocorrencias': 'ocorrencias', 'ocorrencias_turmas': 'ocorrencias_turmas', 'chaveamentos': 'chaveamentos' }[file]);
        if (!store) return Promise.resolve();
        if (!Array.isArray(rows)) rows = [data];
        return Promise.all(rows.filter(function (r) { return r && typeof r === 'object'; }).map(function (r, i) {
            var identity = file === 'pontos' && action === 'atletas'
                // O mesmo aluno pode estar em equipes diferentes da mesma
                // modalidade/turma. O elenco offline precisa preservar cada
                // vínculo para não deixar uma equipe sobrescrever a outra.
                ? ((r.equipes_id_equipe || info.q.get('id_equipe') || 'equipe') + '|' + (r.id_usuario || url + '#' + i))
                : idFor(file, r, url + '#' + i);
            if (file === 'chaveamentos' && action) {
                r = Object.assign({}, r, {
                    _sgi_chaveamento_url: url,
                    _sgi_chaveamento_action: action,
                    _sgi_chaveamento_modality: info.q.get('id_modalidade') || null,
                });
            }
            return put(store, identity, r);
        }));
    }
    function bodyOf(item) { try { return typeof item.body === 'string' ? JSON.parse(item.body || '{}') : (item.body || {}); } catch (_) { return {}; } }
    function projetarCronometro(jogo, dados) {
        var bloco = dados && dados.cronometro;
        if (!bloco || typeof bloco !== 'object' || Number(bloco.versao) !== 2) return jogo;
        var saldo = Number(bloco.saldo_segundos);
        var referencia = bloco.referencia_epoch_ms == null ? null : Number(bloco.referencia_epoch_ms);
        if (!Number.isInteger(saldo) || saldo < 0 || (referencia !== null && (!Number.isInteger(referencia) || referencia < 0))) return jogo;
        jogo.tempo_restante_jogo = saldo;
        jogo.tempo_restante_calculado = saldo;
        jogo.cronometro_referencia_epoch_ms = referencia;
        if (dados.status_jogo) jogo.status_jogo = dados.status_jogo;
        if (dados.duracao_jogo != null) jogo.duracao_jogo = dados.duracao_jogo;
        if (dados.tempo_extra_jogo != null) jogo.tempo_extra_jogo = dados.tempo_extra_jogo;
        return jogo;
    }
    function aplicarRespostaCronometro(jogo, resposta) {
        var bloco = resposta && resposta.cronometro;
        if (!bloco || typeof bloco !== 'object' || Number(bloco.versao) !== 2) return jogo;
        if (bloco.status_jogo) jogo.status_jogo = bloco.status_jogo;
        if (bloco.duracao_jogo != null) jogo.duracao_jogo = bloco.duracao_jogo;
        if (bloco.tempo_extra_jogo != null) jogo.tempo_extra_jogo = bloco.tempo_extra_jogo;
        if (bloco.saldo_segundos != null) {
            jogo.tempo_restante_jogo = bloco.saldo_segundos;
            jogo.tempo_restante_calculado = bloco.saldo_segundos;
        }
        jogo.cronometro_referencia_epoch_ms = bloco.referencia_epoch_ms == null ? null : bloco.referencia_epoch_ms;
        if (bloco.servidor_epoch_ms != null) jogo.servidor_epoch_ms = bloco.servidor_epoch_ms;
        return jogo;
    }
    function preservarReferenciasOcorrencia(old, merged) {
        if (!old || !old.descricao_ocorrencia || !merged.descricao_ocorrencia) return merged;
        var refs = String(old.descricao_ocorrencia).match(/^(?:\[JOGO:-?\d+\])?(?:\[TURMA:-?\d+\])?/)[0];
        if (refs && String(merged.descricao_ocorrencia).indexOf('[JOGO:') !== 0 &&
            String(merged.descricao_ocorrencia).indexOf('[TURMA:') !== 0) {
            merged.descricao_ocorrencia = refs + String(merged.descricao_ocorrencia);
        }
        return merged;
    }

    function project(item) {
        var info = urlInfo(item.url), data = bodyOf(item), file = info.file, temporary = 'temp_' + item.id;
        if (file === 'jogos' && data.id_jogo) return get('jogos', data.id_jogo).then(function (old) {
            // O PUT de início só pode enviar ao servidor os campos permitidos
            // ao mesário. Os dados usados pela agenda/placar seguem dentro de
            // `_contexto_offline` e são aplicados apenas ao IndexedDB.
            var contexto = data._contexto_offline && typeof data._contexto_offline === 'object'
                ? data._contexto_offline
                : {};
            var dadosMutacao = Object.assign({}, data);
            delete dadosMutacao._contexto_offline;
            var merged = Object.assign({}, contexto, old || {}, dadosMutacao, { _pendente: true });
            projetarCronometro(merged, data);
            if (old) {
                if (!merged.nome_jogo && old.nome_jogo) merged.nome_jogo = old.nome_jogo;
                if (!merged.modalidades_id_modalidade && old.modalidades_id_modalidade) merged.modalidades_id_modalidade = old.modalidades_id_modalidade;
                if (!merged.nome_modalidade && old.nome_modalidade) merged.nome_modalidade = old.nome_modalidade;
                if (!merged.tipos_modalidades_id_tipo_modalidade && old.tipos_modalidades_id_tipo_modalidade) merged.tipos_modalidades_id_tipo_modalidade = old.tipos_modalidades_id_tipo_modalidade;
                if (!merged.locais_id_local && old.locais_id_local) merged.locais_id_local = old.locais_id_local;
                if (!merged.nome_local && old.nome_local) merged.nome_local = old.nome_local;
            }
            return put('jogos', data.id_jogo, merged);
        });
        // Placar ao vivo (botões +/- do placar): atualiza a partida no banco
        // local, inclusive em partidas criadas offline (id "mm_local_…").
        if (file === 'partidas' && item.method === 'PUT' && data.id_partida != null) return all('partidas').then(function (ps) {
            var old = ps.filter(function (p) { return String(p.id_partida) === String(data.id_partida); })[0];
            return put('partidas', data.id_partida, Object.assign({}, old || { id_partida: data.id_partida }, data, { _pendente: true }));
        });
        if (file === 'pontos' && item.method === 'POST') return Promise.all([
            put('pontos', temporary, Object.assign({ id_ponto: temporary }, data, {
                _pendente: true,
                status_artilheiro: 'ativo',
                conta_no_placar: 1,
            })),
            all('partidas').then(function (ps) {
                var old = ps.filter(function (p) { return String(p.id_partida) === String(data.id_partida); })[0];
                if (!old) return null;
                return put('partidas', old.id_partida, Object.assign({}, old, {
                    resultado_partida: Math.max(0, parseInt(old.resultado_partida, 10) || 0) + 1,
                    _pendente: true,
                }));
            })
        ]);
        if (file === 'pontos' && item.method === 'PUT' && data.id_ponto != null) return all('pontos').then(function (pontos) {
            var old = pontos.filter(function (p) { return String(p.id_ponto) === String(data.id_ponto); })[0];
            if (!old) return null;
            var tarefas = [put('pontos', data.id_ponto, Object.assign({}, old, {
                status_artilheiro: 'anulado', conta_no_placar: 0, _pendente: true,
            }))];
            if (Number(old.conta_no_placar) === 1 && old.id_partida != null) {
                tarefas.push(all('partidas').then(function (ps) {
                    var partida = ps.filter(function (p) { return String(p.id_partida) === String(old.id_partida); })[0];
                    if (!partida) return null;
                    return put('partidas', partida.id_partida, Object.assign({}, partida, {
                        resultado_partida: Math.max(0, (parseInt(partida.resultado_partida, 10) || 0) - 1),
                        _pendente: true,
                    }));
                }));
            }
            return Promise.all(tarefas);
        });
        if (file === 'ocorrencias' && item.method === 'PUT' && data.id_ocorrencia != null) return all('ocorrencias').then(function (ocorrencias) {
            var old = ocorrencias.filter(function (ocorrencia) {
                return String(ocorrencia.id_ocorrencia) === String(data.id_ocorrencia);
            })[0];
            var merged = preservarReferenciasOcorrencia(old, Object.assign({}, old || { id_ocorrencia: data.id_ocorrencia }, data, { _pendente: true }));
            return put('ocorrencias', data.id_ocorrencia, merged);
        });
        if (file === 'resultados' && data.id_jogo) return Promise.all([
            all('partidas').then(function (partidas) {
                return Promise.all((data.resultados || []).map(function (r, i) {
                    var old = partidas.filter(function (p) { return String(p.jogos_id_jogo) === String(data.id_jogo) && String(p.equipes_id_equipe) === String(r.id_equipe); })[0];
                    return put('partidas', (old && old.id_partida) || ('temp_partida_' + item.id + '_' + i), Object.assign({}, old || {}, { jogos_id_jogo: data.id_jogo, equipes_id_equipe: r.id_equipe, resultado_partida: r.gols, _pendente: true }));
                }));
            }),
            // Espelha resultados: ao concluir um jogo, o status passa
            // para 'Concluido' TAMBÉM no banco temporário JS. Sem isto, a tela
            // do placar recarregava offline com o status antigo ("Iniciado") e
            // o mesário não conseguia finalizar a partida.
            get('jogos', data.id_jogo).then(function (jogo) {
                var rs = data.resultados || [];
                var total = rs.reduce(function (s, r) { return s + (parseInt(r.gols, 10) || 0); }, 0);
                var empate = rs.length >= 2 && (parseInt(rs[0].gols, 10) || 0) === (parseInt(rs[1].gols, 10) || 0);
                var contexto = data._contexto_offline && typeof data._contexto_offline === 'object'
                    ? data._contexto_offline
                    : {};
                jogo = Object.assign({}, contexto, jogo || { id_jogo: data.id_jogo });
                // `resultados` recebe id_modalidade para resolver
                // jogos temporários. Espelhamos o mesmo valor no nome de
                // coluna usado pelos filtros locais da agenda.
                if (!jogo.modalidades_id_modalidade && data.id_modalidade) {
                    jogo.modalidades_id_modalidade = data.id_modalidade;
                }
                if (!jogo.nome_jogo && data.nome_jogo) jogo.nome_jogo = data.nome_jogo;
                if (total > 0 && !empate) jogo.status_jogo = 'Concluido';
                jogo._pendente = true;
                return put('jogos', data.id_jogo, jogo);
            })
        ]);
        if (file === 'artilheiros' && item.method === 'POST') return put('atletas', temporary, Object.assign({ id_artilheiro: temporary, _pendente: true }, data));
        if (file === 'ocorrencias' && item.method === 'POST') return put('ocorrencias', temporary, Object.assign({
            id_ocorrencia: temporary,
            _pendente: true,
            turmas_id_turma: data.id_turma || data.turmas_id_turma || 0,
            id_usuario: data.usuarios_id_usuario || data.id_usuario || 0,
            jogos_id_jogo: data.id_jogo || data.jogos_id_jogo || 0,
        }, data));
        if (file === 'ocorrencias_turmas' && item.method === 'POST') return put('ocorrencias_turmas', temporary, Object.assign({ id_ocorrencia: temporary, _pendente: true }, data));
        if (file === 'chaveamentos' && item.method === 'POST' && data.tipo_modalidade === 'individual' && data.ranking && data.id_modalidade) {
            var tagInd = 'IND:' + data.id_modalidade;
            var rankingUrl = apiBaseForChaveamento(data.id_modalidade, 'ranking');
            return Promise.all([all('jogos'), all('chaveamentos')]).then(function (state) {
                var jogos = state[0], chaveamentos = state[1];
                var indJogo = data.id_jogo
                    ? jogos.filter(function (j) { return String(j.id_jogo) === String(data.id_jogo); })[0]
                    : jogos.filter(function (j) { return j.nome_jogo === tagInd; })[0];
                if (!indJogo) {
                    indJogo = { id_jogo: tagInd, nome_jogo: tagInd, modalidades_id_modalidade: data.id_modalidade };
                }
                indJogo.status_jogo = 'Concluido';
                indJogo._pendente = true;
                var pendente = {
                    _sgi_chaveamento_pending: true,
                    _sgi_chaveamento_url: rankingUrl,
                    _sgi_chaveamento_action: 'ranking',
                    _sgi_chaveamento_modality: String(data.id_modalidade),
                    _sgi_chaveamento_mutation_id: String(item.id),
                    ranking: [
                        { posicao: 1, id_usuario: Number(data.ranking.primeiro) },
                        { posicao: 2, id_usuario: Number(data.ranking.segundo) },
                        { posicao: 3, id_usuario: Number(data.ranking.terceiro) },
                    ],
                    _pendente: true,
                };
                return Promise.all([
                    put('jogos', indJogo.id_jogo, indJogo),
                    put('chaveamentos', 'pending|' + data.id_modalidade + '|' + item.id, Object.assign({}, pendente, { _sgi_chaveamento_shadow: true })),
                    put('chaveamentos', 'pending|' + data.id_modalidade, pendente),
                ]);
            });
        }
        return Promise.resolve();
    }
    function apiBaseForChaveamento(idModalidade, action) {
        var base = window.SGI_API_BASE || '/api/v1/';
        base = String(base).replace(/\/?$/, '/');
        return base + 'chaveamentos?tipo_modalidade=individual&acao=' + encodeURIComponent(action) + '&id_modalidade=' + encodeURIComponent(idModalidade);
    }
    function localGet(url) {
        var info = urlInfo(url), file = info.file, store = { 'jogos': 'jogos', 'partidas': 'partidas', 'turmas': 'turmas', 'modalidades': 'modalidades', 'categorias': 'categorias', 'locais': 'locais', 'equipes': 'equipes', 'artilheiros': 'atletas', 'pontos': 'pontos', 'ocorrencias': 'ocorrencias', 'ocorrencias_turmas': 'ocorrencias_turmas', 'chaveamentos': 'chaveamentos' }[file];
        // Endpoints com "acao" possuem formatos especiais; o cache por URL
        // da camada base preserva exatamente a resposta original nesses casos.
        if (!store) return Promise.resolve(null);
        if (file === 'chaveamentos' && ['participantes', 'ranking'].indexOf(info.q.get('acao')) !== -1) {
            var action = info.q.get('acao');
            var modality = String(info.q.get('id_modalidade') || '');
            return all('chaveamentos').then(function (rows) {
                var matching = rows.filter(function (row) {
                    return String(row._sgi_chaveamento_modality || '') === modality
                        && String(row._sgi_chaveamento_action || '') === action;
                });
                var pending = matching.filter(function (row) { return row._sgi_chaveamento_pending && row._sgi_chaveamento_shadow !== true; }).pop();
                var confirmed = matching.filter(function (row) { return row._sgi_chaveamento_pending === false && Array.isArray(row.ranking); }).pop();
                var base = matching.filter(function (row) { return !row._sgi_chaveamento_pending; });
                if (action === 'participantes') {
                    return new Response(JSON.stringify({ success: true, participantes: base.map(function (row) {
                        var copy = Object.assign({}, row);
                        delete copy._sgi_chaveamento_url; delete copy._sgi_chaveamento_action; delete copy._sgi_chaveamento_modality;
                        return copy;
                    }) }), { status: 200, headers: { 'Content-Type': 'application/json' } });
                }
                var participants = rows.filter(function (row) {
                    return String(row._sgi_chaveamento_modality || '') === modality && row._sgi_chaveamento_action === 'participantes';
                });
                var ranking = pending && Array.isArray(pending.ranking)
                    ? pending.ranking
                    : (confirmed && Array.isArray(confirmed.ranking)
                        ? confirmed.ranking
                        : base.filter(function (row) { return row.posicao != null; }));
                ranking = ranking.map(function (row) {
                    var participant = participants.filter(function (candidate) { return String(candidate.id_usuario) === String(row.id_usuario); })[0] || {};
                    return Object.assign({}, participant, row);
                });
                return new Response(JSON.stringify({ success: true, ranking: ranking, jogo: null, offline: Boolean(pending), queued: Boolean(pending) }), { status: 200, headers: { 'Content-Type': 'application/json' } });
            });
        }
        if (file === 'chaveamentos' && info.q.get('acao')) return Promise.resolve(null);
        return all(file === 'pontos' && info.q.get('acao') === 'atletas' ? 'atletas' : store).then(function (rows) {
            if (file === 'pontos' && info.q.get('acao') === 'atletas') {
                var teamId = info.q.get('id_equipe');
                var atletas = teamId ? rows.filter(function (r) { return String(r.equipes_id_equipe || '') === String(teamId); }) : rows;
                return new Response(JSON.stringify({ success: true, atletas: atletas }), { status: 200, headers: { 'Content-Type': 'application/json' } });
            }
            var idOcorrencia = file === 'ocorrencias' ? info.q.get('id_ocorrencia') : null;
            var idOcorrenciaTurma = file === 'ocorrencias_turmas' ? info.q.get('id_ocorrencia_turma') : null;
            var statusOcorrencia = file === 'ocorrencias' ? info.q.get('status_ocorrencia') : null;
            var idJogo = info.q.get('id_jogo'), idInter = info.q.get('id_interclasse'), idMod = info.q.get('id_modalidade');
            if (idOcorrencia !== null && idOcorrencia !== '') {
                rows = rows.filter(function (r) { return String(r.id_ocorrencia) === String(idOcorrencia); });
            }
            if (idOcorrenciaTurma !== null && idOcorrenciaTurma !== '') {
                rows = rows.filter(function (r) { return String(r.id_ocorrencia_turma) === String(idOcorrenciaTurma); });
            }
            if (statusOcorrencia !== null && statusOcorrencia !== '') {
                rows = rows.filter(function (r) { return String(r.status_ocorrencia) === String(statusOcorrencia); });
            }
            if (idJogo) rows = rows.filter(function (r) { return String(r.jogos_id_jogo || r.id_jogo) === String(idJogo); });
            if (idInter) rows = rows.filter(function (r) {
                var rInter = r.interclasses_id_interclasse || r.id_interclasse;
                if (rInter != null) return String(rInter) === String(idInter);
                return Number(r.id_jogo) < 0;
            });
            if (idMod) rows = rows.filter(function (r) { return String(r.modalidades_id_modalidade || r.id_modalidade) === String(idMod); });
            if (file === 'pontos') return new Response(JSON.stringify({ success: true, pontos: rows }), { status: 200, headers: { 'Content-Type': 'application/json' } });
            return new Response(JSON.stringify(rows), { status: 200, headers: { 'Content-Type': 'application/json' } });
        });
    }

    function itemAfetaLeitura(item, info, partidasLocais) {
        var mutacao = urlInfo(item && item.url), dados = bodyOf(item || {});
        var arquivo = mutacao.file;
        var idJogoConsulta = info.q && info.q.get('id_jogo');
        var idOcorrenciaConsulta = info.q && info.q.get('id_ocorrencia');
        var idOcorrenciaTurmaConsulta = info.q && info.q.get('id_ocorrencia_turma');
        var idJogoMutacao = dados.id_jogo != null ? dados.id_jogo : dados.jogos_id_jogo;

        if (info.file === 'jogos') {
            if (arquivo !== 'jogos' && arquivo !== 'partidas' && arquivo !== 'resultados') return false;
        } else if (info.file === 'partidas') {
            // Pontos vinculados também alteram o placar projetado da partida.
            // Sem esta relação, a remontagem da tela offline aceitava o
            // snapshot antigo da API e fazia o placar voltar temporariamente.
            if (arquivo !== 'partidas' && arquivo !== 'resultados' && arquivo !== 'pontos') return false;
        } else if (info.file === 'artilheiros') {
            if (arquivo !== 'artilheiros') return false;
        } else if (info.file === 'pontos') {
            if (arquivo !== 'pontos') return false;
        } else if (info.file === 'ocorrencias') {
            if (arquivo !== 'ocorrencias') return false;
        } else if (info.file === 'ocorrencias_turmas') {
            if (arquivo !== 'ocorrencias_turmas') return false;
        } else if (info.file === 'chaveamentos') {
            if (arquivo !== 'chaveamentos') return false;
            var modalidadeConsulta = info.q && info.q.get('id_modalidade');
            return modalidadeConsulta !== null && String(dados.id_modalidade || dados._sgi_chaveamento_modality || '') === String(modalidadeConsulta);
        } else {
            return false;
        }

        if (info.file === 'ocorrencias' && idOcorrenciaConsulta !== null && idOcorrenciaConsulta !== '') {
            return arquivo === 'ocorrencias' && dados.id_ocorrencia != null &&
                String(idOcorrenciaConsulta) === String(dados.id_ocorrencia);
        }

        if (info.file === 'ocorrencias_turmas' && idOcorrenciaTurmaConsulta !== null && idOcorrenciaTurmaConsulta !== '') {
            return arquivo === 'ocorrencias_turmas' && dados.id_ocorrencia_turma != null &&
                String(idOcorrenciaTurmaConsulta) === String(dados.id_ocorrencia_turma);
        }

        if (info.file === 'partidas' && arquivo === 'partidas' && dados.id_partida != null) {
            var partidaLocal = (partidasLocais || []).filter(function (partida) {
                return String(partida.id_partida) === String(dados.id_partida);
            })[0];
            if (partidaLocal) {
                var idJogoLocal = partidaLocal.jogos_id_jogo != null
                    ? partidaLocal.jogos_id_jogo
                    : partidaLocal.id_jogo;
                return !idJogoConsulta || String(idJogoConsulta) === String(idJogoLocal);
            }
        }

        return !idJogoConsulta || String(idJogoConsulta) === String(idJogoMutacao);
    }

    function temPendenciaRelevante(url) {
        var info = urlInfo(url);
        if (!window.SGIOffline || !window.SGIOffline.getPendingList ||
            !(window.SGIOffline.hasPending && window.SGIOffline.hasPending())) {
            return Promise.resolve(false);
        }
        var partidasLocais = info.file === 'partidas' ? all('partidas') : Promise.resolve(null);
        return Promise.all([window.SGIOffline.getPendingList(), partidasLocais]).then(function (resultado) {
            var fila = resultado[0];
            var partidas = resultado[1];
            return (fila || []).some(function (item) { return itemAfetaLeitura(item, info, partidas); });
        }).catch(function () { return false; });
    }

    function respostaLocalComDados(url) {
        var info = urlInfo(url);
        var consultaPorId = (info.file === 'ocorrencias' && info.q && info.q.get('id_ocorrencia') !== null) ||
            (info.file === 'ocorrencias_turmas' && info.q && info.q.get('id_ocorrencia_turma') !== null);
        return localGet(url).then(function (resposta) {
            if (!resposta) return null;
            return resposta.clone().text().then(function (texto) {
                var dados = null;
                try { dados = JSON.parse(texto); } catch (_) {}
                if (Array.isArray(dados) && dados.length === 0 && !consultaPorId) return null;
                if (dados == null) return null;
                return resposta;
            }).catch(function () { return null; });
        });
    }

    function capturarResposta(url, resposta) {
        if (!resposta || !resposta.ok) return Promise.resolve(resposta);
        return resposta.clone().text().then(function (texto) {
            return capture(url, texto);
        }).catch(function () {}).then(function () {
            return resposta;
        });
    }

    // A UI existente continua usando fetch; esta ponte faz as leituras offline
    // virem das tabelas locais e espelha cada GET online nelas.
    var baseFetch = window.fetch && window.fetch.bind(window);
    if (baseFetch) window.fetch = function (input, init) {
        var url = typeof input === 'string' ? input : input.url;
        var method = String((init && init.method) || (input && input.method) || 'GET').toUpperCase();
        if (method === 'GET') {
            var info = urlInfo(url);
            // O snapshot exato por URL é a fonte padrão no modo offline. As
            // tabelas estruturadas só assumem a leitura quando existe uma
            // pendência para a mesma entidade; isso evita que um store ainda
            // vazio esconda uma resposta válida em cache com uma lista vazia.
            var priorizarLocal = navigator.onLine === false ||
                info.file === 'jogos' || info.file === 'partidas' ||
                info.file === 'artilheiros' || info.file === 'pontos' || info.file === 'ocorrencias' ||
                info.file === 'ocorrencias_turmas' || info.file === 'chaveamentos';
            if (priorizarLocal) {
                return temPendenciaRelevante(url).then(function (haPendencia) {
                    if (!haPendencia) {
                        // Jogos e partidas também precisam alimentar as
                        // tabelas estruturadas enquanto a rede está disponível.
                        // Antes este ramo devolvia a resposta sem capturá-la;
                        // a primeira mutação criava então um registro parcial,
                        // sem modalidade, equipes, local ou interclasse.
                        return baseFetch(input, init).then(function (resposta) {
                            return navigator.onLine === false
                                ? resposta
                                : capturarResposta(url, resposta);
                        });
                    }
                    return respostaLocalComDados(url);
                }).then(function (respostaLocal) {
                    if (respostaLocal) return respostaLocal;
                    return baseFetch(input, init);
                }).catch(function (erro) {
                    // Sem snapshot HTTP, a tabela local ainda é uma última
                    // alternativa útil para uma tela já usada pelo mesário.
                    return respostaLocalComDados(url).then(function (respostaLocal) {
                        if (respostaLocal) return respostaLocal;
                        throw erro;
                    });
                });
            }
            if (navigator.onLine === false) {
                // A resposta completa por URL é a fonte principal: ela preserva
                // exatamente o formato esperado por cada tela. Só recorremos às
                // tabelas estruturadas quando a rota ainda não possui snapshot.
                return baseFetch(input, init).catch(function () {
                    return localGet(url).then(function (res) {
                        if (res) return res;
                        throw new Error('Dados não disponíveis offline para esta consulta.');
                    });
                });
            }
            return baseFetch(input, init).then(function (res) { return capturarResposta(url, res); });
        }
        return baseFetch(input, init);
    };

    window.SGIDataLayer = {
        onQueued: function (item) { return Promise.all([project(item), put('fila_sincronizacao', item.id, item)]); },
        onSynced: function (item, text) {
            var info = urlInfo(item.url), resposta = {};
            try { resposta = JSON.parse(text || '{}'); } catch (_) {}
            var dados = bodyOf(item);
            if (info.file === 'chaveamentos' && item.method === 'POST'
                && dados.tipo_modalidade === 'individual' && dados.id_modalidade) {
                var modalidade = String(dados.id_modalidade);
                return remove('chaveamentos', 'pending|' + modalidade + '|' + item.id)
                    .then(function () { return all('chaveamentos'); })
                    .then(function (rows) {
                        rows = rows || [];
                        var restantes = rows.filter(function (row) {
                            return row && row._sgi_chaveamento_pending &&
                                String(row._sgi_chaveamento_modality || '') === modalidade &&
                                row._sgi_chaveamento_shadow;
                        });
                        var proxima = restantes[restantes.length - 1] || null;
                        var tarefas = [remove('fila_sincronizacao', item.id)];
                        if (proxima) {
                            tarefas.push(put('chaveamentos', 'pending|' + modalidade, Object.assign({}, proxima, { _sgi_chaveamento_shadow: false })));
                        } else {
                            tarefas.push(remove('chaveamentos', 'pending|' + modalidade));
                            var rankingConfirmado = Array.isArray(dados.ranking)
                                ? dados.ranking
                                : [
                                    { posicao: 1, id_usuario: Number(dados.ranking && dados.ranking.primeiro) },
                                    { posicao: 2, id_usuario: Number(dados.ranking && dados.ranking.segundo) },
                                    { posicao: 3, id_usuario: Number(dados.ranking && dados.ranking.terceiro) },
                                ];
                            tarefas.push(put('chaveamentos', 'confirmed|' + modalidade, {
                                _sgi_chaveamento_pending: false,
                                _sgi_chaveamento_modality: modalidade,
                                _sgi_chaveamento_action: 'ranking',
                                ranking: rankingConfirmado,
                                _pendente: false,
                            }));
                        }
                        return Promise.all(tarefas);
                    });
            }
            if (info.file === 'jogos' && item.method === 'PUT' && dados.id_jogo != null) {
                return get('jogos', dados.id_jogo).then(function (jogo) {
                    if (!jogo) return remove('fila_sincronizacao', item.id);
                    aplicarRespostaCronometro(jogo, resposta);
                    return Promise.all([
                        put('jogos', dados.id_jogo, Object.assign({}, jogo, { _pendente: false })),
                        remove('fila_sincronizacao', item.id)
                    ]);
                });
            }
            if (info.file === 'ocorrencias' && item.method === 'PUT' && dados.id_ocorrencia != null && !item.dependsOn) {
                return get('ocorrencias', dados.id_ocorrencia).then(function (ocorrencia) {
                    if (!ocorrencia) return remove('fila_sincronizacao', item.id);
                    return Promise.all([
                        put('ocorrencias', dados.id_ocorrencia, Object.assign({}, ocorrencia, { _pendente: false })),
                        remove('fila_sincronizacao', item.id)
                    ]);
                });
            }
            var store = info.file === 'artilheiros' ? 'atletas' : (info.file === 'pontos' ? 'pontos' : (info.file === 'ocorrencias' ? 'ocorrencias' : (info.file === 'ocorrencias_turmas' ? 'ocorrencias_turmas' : null)));
            var temp = 'temp_' + item.id;
            if (store && item.method === 'POST' && resposta.id) {
                return get(store, temp).then(function (row) {
                    if (!row) return remove('fila_sincronizacao', item.id);
                    var idReal = resposta.id;
                    var identidade = store === 'ocorrencias'
                        ? { id_ocorrencia: idReal }
                        : (store === 'atletas' ? { id_artilheiro: idReal } : (store === 'pontos' ? { id_ponto: idReal } : { id_ocorrencia_turma: idReal }));
                    return Promise.all([put(store, idReal, Object.assign({}, row, identidade, { _pendente: false })), remove(store, temp), remove('fila_sincronizacao', item.id)]);
                });
            }
            if (info.file === 'ocorrencias' && item.method === 'PUT' && dados.id_ocorrencia != null && item.dependsOn && item.dependsOn.resolvedId != null) {
                return get('ocorrencias', dados.id_ocorrencia).then(function (ocorrencia) {
                    if (!ocorrencia) return remove('fila_sincronizacao', item.id);
                    var idReal = item.dependsOn.resolvedId;
                    return Promise.all([
                        put('ocorrencias', idReal, Object.assign({}, ocorrencia, { id_ocorrencia: idReal, _pendente: false })),
                        remove('ocorrencias', dados.id_ocorrencia),
                        remove('fila_sincronizacao', item.id)
                    ]);
                });
            }
            return remove('fila_sincronizacao', item.id);
        },
        capture: capture,
        read: function (store) { return all(store); },
        localGet: localGet,
        /* Primitivas usadas pelo motor de chaveamento local:
           upsert grava/mergeia uma linha em qualquer store; removeRecord
           exclui uma linha por id (ex.: limpeza de derivados locais após a
           sincronização, quando o servidor passa a ser a fonte da verdade). */
        upsert: put,
        removeRecord: remove
    };
})();
