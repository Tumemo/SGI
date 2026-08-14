/* ============================================================
   SGI MESARIO OFFLINE — SPA + Preload para o mesario

   Transforma a dashboard do mesario numa casca fixa e baixa,
   enquanto houver conexao, as telas e os dados que ele precisa
   para operar 100% offline (sem Service Worker).

   Dependencias (carregadas antes, no head.php):
     - offline-core.js      (cache GET por URL + fila de mutacoes)
     - Comandooffline.js    (forms com data-sgi-offline)

   Esse arquivo so age quando:
     - window.SGI_SESSION_NIVEL === 2 (mesario); e
     - existe #conteudo-principal (dashboard-casca).
   ============================================================ */
(function () {
    'use strict';
    if (window.__SGI_MESARIO_SPA__) return;
    window.__SGI_MESARIO_SPA__ = true;

    var SESSION = (typeof window !== 'undefined' && window.SGI_SESSION_ID)
        ? String(window.SGI_SESSION_ID) : 'anon';
    var SEP = '\n/*__SGI_SEP__*/\n';
    var DB_NAME = 'sgi_pages';
    var DB_VERSION = 1;

    var ARQ_TELA = {
        'perfil.php': 'perfil',
        'edicao_agenda.php': 'agenda',
        'chaveamento_arvore.php': 'chaveamento',
        'ocorrencias.php': 'ocorrencias',
        'jogos_lista.php': 'jogoslista',
        'jogos.php': 'jogos',
        'dashboard.php': 'dashboard'
    };

    var TELA_TITULO = {
        perfil: 'Meu perfil',
        agenda: 'Agenda',
        chaveamento: 'Chaveamentos',
        ocorrencias: 'Ocorrências',
        jogoslista: 'Jogos',
        jogos: 'Placar',
        dashboard: 'Dashboard'
    };

    var state = {
        nivel: -1,
        temCasca: false,
        dashHtml: '',
        pronto: false,
        preloading: false,
        montadas: {},
        registros: {},
        pendentesInit: [],
        montando: null
    };

    /* ============================ Util ============================ */

    function runSafe(fn) {
        try { fn(); } catch (e) {
            if (window.console) console.error('[SGI Mesario SPA]', e);
        }
    }

    function apiBase() {
        var path = window.location.pathname || '';
        return path.replace(/\/views\/src\/pages\/[^/]*$/, '/api/');
    }

    function resolverAbs(urlRel) {
        try { return new URL(urlRel, window.location.href).href; }
        catch (e) { return urlRel; }
    }

    function fetchJson(url) {
        return fetch(url).then(function (r) { return r.text(); }).then(function (t) {
            try { return t ? JSON.parse(t) : {}; } catch (e) { return {}; }
        });
    }

    function obterIdAtivo() {
        var p = new URLSearchParams(window.location.search);
        var id = p.get('id');
        if (id) return Promise.resolve(id);
        if (window.SGIInterclasse && typeof window.SGIInterclasse.getActiveInterclasse === 'function') {
            return window.SGIInterclasse.getActiveInterclasse().then(function (a) {
                return (a && a.id_interclasse) ? String(a.id_interclasse) : null;
            }).catch(function () { return null; });
        }
        return Promise.resolve(null);
    }

    function chaveTela(tela, params) {
        params = params || {};
        if (tela === 'jogos') return 'jogos:' + (params.id_jogo || '');
        if (tela === 'ocorrencias') return 'ocorrencias:' + (params.id || '');
        if (tela === 'chaveamento') return 'chaveamento:' + (params.id || '');
        if (tela === 'jogoslista') return 'jogoslista:' + (params.id || '');
        if (tela === 'perfil') return 'perfil:' + (params.id || '');
        if (tela === 'agenda') return 'agenda:' + (params.id || '');
        return 'dashboard';
    }

    function construirUrl(tela, params) {
        params = params || {};
        switch (tela) {
            case 'agenda':
                return 'edicao_agenda.php' + (params.id ? '?id=' + params.id : '');
            case 'chaveamento':
                return 'chaveamento_arvore.php' + (params.id ? '?id=' + params.id : '');
            case 'ocorrencias':
                return 'ocorrencias.php' + (params.id ? '?id=' + params.id : '');
            case 'jogoslista':
                return 'jogos_lista.php' + (params.id ? '?id=' + params.id : '');
            case 'perfil':
                return 'perfil.php' + (params.id ? '?id=' + params.id : '');
            case 'jogos':
                return 'jogos.php?id_jogo=' + params.id_jogo +
                    (params.origem ? '&origem=' + encodeURIComponent(params.origem) : '');
            default:
                return 'dashboard.php' + (params.id ? '?id=' + params.id : '');
        }
    }

    function mapearTela(pathWithQuery) {
        var qIndex = pathWithQuery.indexOf('?');
        var path = qIndex > -1 ? pathWithQuery.slice(0, qIndex) : pathWithQuery;
        var nome = path.split('/').pop();
        var tela = ARQ_TELA[nome];
        if (!tela) return null;
        var params = {};
        if (qIndex > -1) {
            var up = new URLSearchParams(pathWithQuery.slice(qIndex + 1));
            if (up.has('id')) params.id = up.get('id');
            if (up.has('id_jogo')) params.id_jogo = up.get('id_jogo');
            if (up.has('origem')) params.origem = up.get('origem');
        }
        return { tela: tela, params: params };
    }

    /* ====================== IndexedDB (sgi_pages) ====================== */

    var dbPromise = null;

    function openDB() {
        if (dbPromise) return dbPromise;
        dbPromise = new Promise(function (resolve, reject) {
            try {
                var req = indexedDB.open(DB_NAME, DB_VERSION);
                req.onupgradeneeded = function (e) {
                    var d = e.target.result;
                    if (!d.objectStoreNames.contains('paginas')) {
                        d.createObjectStore('paginas', { keyPath: 'key' });
                    }
                };
                req.onsuccess = function () { resolve(req.result); };
                req.onerror = function () { reject(req.error); };
                req.onblocked = function () {};
            } catch (err) { reject(err); }
        });
        return dbPromise;
    }

    function idbGet(chave) {
        var key = SESSION + '|' + chave;
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('paginas', 'readonly');
                var r = tx.objectStore('paginas').get(key);
                r.onsuccess = function () { resolve(r.result || null); };
                r.onerror = function () { reject(r.error); };
            });
        });
    }

    function idbPut(chave, rec) {
        rec.key = SESSION + '|' + chave;
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('paginas', 'readwrite');
                tx.objectStore('paginas').put(rec);
                tx.oncomplete = resolve;
                tx.onerror = function () { reject(tx.error); };
            });
        });
    }

    // Quando a conexão cai antes de a edição ativa ser resolvida, ainda é
    // possível abrir a última cópia já baixada daquela tela para a sessão.
    function idbFindTela(tela) {
        return openDB().then(function (db) {
            return new Promise(function (resolve, reject) {
                var tx = db.transaction('paginas', 'readonly');
                var r = tx.objectStore('paginas').getAll();
                r.onsuccess = function () {
                    var prefixo = SESSION + '|';
                    var itens = (r.result || []).filter(function (item) {
                        return item && item.key.indexOf(prefixo) === 0 && item.tela === tela;
                    }).sort(function (a, b) { return (b.savedAt || 0) - (a.savedAt || 0); });
                    resolve(itens[0] || null);
                };
                r.onerror = function () { reject(r.error); };
            });
        });
    }

    /* ================ Transformador de script (reexecutavel) ================
       Objetivo: permitir re-executar o <script> de uma tela dentro da casca,
       sem quebrar por redeclaracao de const/let nem esperar eventos
       DOMContentLoaded/load (que ja ocorreram).

       1) document.addEventListener('DOMContentLoaded', cb);
          window.addEventListener('load', cb);
          -> registra cb no motor (rodado apos injetar a tela).
       2) const x = ... / let x = ...  no topo do script -> var x = ...
    */

    function tornarReexecutavel(src) {
        var n = src.length, i = 0, out = '';
        var depth = 0, quote = null, line = false, block = false, tpl = false;
        var reevalCount = 0;

        function pularEspacos(idx) {
            while (idx < n) {
                var ch = src[idx];
                if (ch === ' ' || ch === '\t' || ch === '\n' || ch === '\r') idx++;
                else break;
            }
            return idx;
        }

        // Acha o parentese que fecha a chamada iniciada em openIdx (o '('
        // de addEventListener). Ignora strings, comentarios e template literals.
        function acharFechaParen(openIdx) {
            var d = 0, j = openIdx, q = null, lc = false, bc = false, t = false;
            for (; j < n; j++) {
                var c = src[j], nx = src[j + 1];
                if (lc) { if (c === '\n') lc = false; continue; }
                if (bc) { if (c === '*' && nx === '/') bc = false; continue; }
                if (q) { if (c === '\\') { j++; continue; } if (c === q) q = null; continue; }
                if (t) { if (c === '\\') { j++; continue; } if (c === '`') t = false; continue; }
                if (c === '`') { t = true; continue; }
                if (c === '/' && nx === '/') { lc = true; j++; continue; }
                if (c === '/' && nx === '*') { bc = true; j++; continue; }
                if (c === "'" || c === '"') { q = c; continue; }
                if (c === '(') d++;
                else if (c === ')') { d--; if (d === 0) return j; }
            }
            return -1;
        }

        function emitirRegistro(cb) {
            reevalCount++;
            out += 'var __SGI_REEVAL_' + reevalCount + '__ = ' + cb + ';\n';
            out += 'if (window.__SGI_SPA__ && window.__SGI_SPA__.registrarInit) {' +
                ' window.__SGI_SPA__.registrarInit(__SGI_REEVAL_' + reevalCount + '__); }\n';
            out += 'else { __SGI_REEVAL_' + reevalCount + '__(); }\n';
        }

        while (i < n) {
            var c = src[i];
            var nx = src[i + 1];

            if (line) { if (c === '\n') { line = false; out += '\n'; } i++; continue; }
            if (block) { if (c === '*' && nx === '/') { block = false; i += 2; continue; } i++; continue; }
            if (quote) {
                if (c === '\\') { out += c + (nx || ''); i += 2; continue; }
                if (c === quote) quote = null;
                out += c; i++; continue;
            }
            if (tpl) {
                if (c === '\\') { out += c + (nx || ''); i += 2; continue; }
                if (c === '`') tpl = false;
                out += c; i++; continue;
            }
            // Remove comentários inteiros: manter apenas uma barra criava
            // expressões regulares inválidas no script reexecutado.
            if (c === '/' && nx === '/') { line = true; i += 2; continue; }
            if (c === '/' && nx === '*') { block = true; i += 2; continue; }
            if (c === "'" || c === '"') { quote = c; out += c; i++; continue; }
            if (c === '`') { tpl = true; out += c; i++; continue; }

            // document.addEventListener('DOMContentLoaded', cb);
            if (c === 'd' && src.substr(i, 26) === 'document.addEventListener(') {
                var j1 = i + 26;
                var j2 = pularEspacos(j1);
                var ev = src.substr(j2, 18);
                if (ev === "'DOMContentLoaded'" || ev === '"DOMContentLoaded"') {
                    var j3 = pularEspacos(j2 + 18);
                    if (src[j3] === ',') {
                        var fech = acharFechaParen(j1 - 1);
                        if (fech > -1) {
                            var stmtFim = fech + 1;
                            if (src[stmtFim] === ';') stmtFim++;
                            emitirRegistro(src.slice(j3 + 1, fech).trim());
                            i = stmtFim;
                            continue;
                        }
                    }
                }
            }

            // window.addEventListener('load', cb);
            if (c === 'w' && src.substr(i, 24) === 'window.addEventListener(') {
                var k1 = i + 24;
                var k2 = pularEspacos(k1);
                var ev2 = src.substr(k2, 6);
                if (ev2 === "'load'" || ev2 === '"load"') {
                    var k3 = pularEspacos(k2 + 6);
                    if (src[k3] === ',') {
                        var fech2 = acharFechaParen(k1 - 1);
                        if (fech2 > -1) {
                            var stmtFim2 = fech2 + 1;
                            if (src[stmtFim2] === ';') stmtFim2++;
                            emitirRegistro(src.slice(k3 + 1, fech2).trim());
                            i = stmtFim2;
                            continue;
                        }
                    }
                }
            }

            // let/const no topo -> var (evita redeclaracao ao re-montar)
            if (depth === 0) {
                if (c === 'c' && src.substr(i, 5) === 'const') {
                    var nc = src[i + 5];
                    var antC = i > 0 ? src[i - 1] : '';
                    if ((nc === ' ' || nc === '{' || nc === '[') && !/[A-Za-z0-9_$]/.test(antC)) {
                        out += 'var'; i += 5; continue;
                    }
                } else if (c === 'l' && src.substr(i, 4) === 'let ') {
                    var nl = src[i + 4];
                    var antL = i > 0 ? src[i - 1] : '';
                    if (/[A-Za-z_$]/.test(nl) && !/[A-Za-z0-9_$]/.test(antL)) {
                        out += 'var '; i += 4; continue;
                    }
                }
            }

            if (c === '(' || c === '{' || c === '[') depth++;
            else if (c === ')' || c === '}' || c === ']') depth = Math.max(0, depth - 1);

            out += c;
            i++;
        }
        return out;
    }

    /* ==================== Captura / download das telas ==================== */

    function extrairScreen(html) {
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var partes = [];
        var css = '';
        var scripts = [];

        doc.querySelectorAll('style').forEach(function (st) {
            css += '\n' + st.textContent;
        });

        doc.querySelectorAll('main').forEach(function (m) {
            partes.push(m.outerHTML);
        });

        doc.querySelectorAll('script').forEach(function (s) {
            if (s.src) return;
            var t = s.textContent || '';
            if (!t.trim()) return;
            if (t.indexOf('SGI_SESSION_ID') > -1) return;
            if (t.indexOf('__SGI_OFFLINE_CORE__') > -1) return;
            if (t.indexOf('__SGI_OFFLINE_FORM__') > -1) return;
            scripts.push(t);
        });

        // Perifericos fora do <main>: FAB e modais (ex.: jogos.php)
        var vistos = {};
        doc.body.querySelectorAll('[data-bs-toggle="modal"],[data-bs-target],[id*="modal" i],[class*="fab"]').forEach(function (el) {
            if (el.closest('main')) return;
            var tag = el.tagName;
            if (tag === 'NAV' || tag === 'FOOTER' || tag === 'SCRIPT' || tag === 'STYLE') return;
            var id = el.id || el.className || '';
            if (vistos[id]) return;
            vistos[id] = true;
            partes.push(el.outerHTML);
        });

        var scriptsConjunto = tornarReexecutavel(scripts.join(SEP));

        return {
            html: partes.join('\n'),
            css: css,
            script: scriptsConjunto,
            titulo: doc.title || 'SGI'
        };
    }

    function baixarTela(tela, params) {
        var url = construirUrl(tela, params || {});
        var abs = resolverAbs(url);
        return fetch(abs).then(function (res) {
            if (!res.ok) throw new Error('HTTP ' + res.status);
            return res.text();
        }).then(function (html) {
            var rec = extrairScreen(html);
            rec.url = url;
            rec.tela = tela;
            rec.titulo = TELA_TITULO[tela] || 'SGI';
            rec.savedAt = Date.now();
            var key = chaveTela(tela, params);
            return idbPut(key, rec).then(function () { return rec; });
        });
    }

    function obterRegistro(tela, params) {
        var key = chaveTela(tela, params);
        return idbGet(key).then(function (rec) {
            if (rec) return rec;
            function alternativaOuErro() {
                return idbFindTela(tela).then(function (alternativa) {
                    if (alternativa) return alternativa;
                    throw new Error('sem cache offline');
                });
            }
            // navigator.onLine pode continuar true quando o servidor local
            // está indisponível; nesse caso tenta rede e depois o cache.
            if (navigator.onLine !== false) return baixarTela(tela, params).catch(alternativaOuErro);
            return alternativaOuErro().then(function (alternativa) {
                if (alternativa) return alternativa;
                throw new Error('sem cache offline');
            });
        });
    }

    /* ============================ Navegacao SPA ============================ */

    function pushEstado(tela, key, url) {
        try {
            history.pushState({ sgi: { tela: tela, key: key } }, '', url);
        } catch (e) {
            try { history.pushState({ sgi: { tela: tela, key: key } }, ''); } catch (e2) {}
        }
    }

    function aplicarCss(css) {
        var el = document.getElementById('sgi-screen-css');
        if (!el) {
            el = document.createElement('style');
            el.id = 'sgi-screen-css';
            document.head.appendChild(el);
        }
        el.textContent = css || '';
    }

    function executarScripts(scriptConjunto) {
        if (!scriptConjunto) return;
        var partes = scriptConjunto.split(SEP);
        partes.forEach(function (code) {
            code = code.trim();
            if (!code) return;
            try {
                var s = document.createElement('script');
                s.textContent = code;
                document.body.appendChild(s);
                if (s.parentNode) s.parentNode.removeChild(s);
            } catch (e) {
                if (window.console) console.error('[SGI Mesario SPA] script da tela', e);
            }
        });
    }

    function registrarInit(fn) {
        if (typeof fn !== 'function') return;
        if (state.montando) {
            state.pendentesInit.push(fn);
        } else {
            runSafe(fn);
        }
    }

    function navegarPara(tela, params) {
        params = params || {};
        var precisaId = (tela === 'ocorrencias' || tela === 'jogoslista' || tela === 'agenda' || tela === 'chaveamento');
        if (precisaId && !params.id) {
            return obterIdAtivo().then(function (id) {
                if (id) params.id = id;
                navegarParaFinal(tela, params);
            });
        }
        navegarParaFinal(tela, params);
    }

    function navegarParaFinal(tela, params) {
        var conteudo = document.getElementById('conteudo-principal');
        if (!conteudo) return;
        var key = chaveTela(tela, params);

        if (state.montadas[key]) {
            ativarMontagem(key, tela, params);
            return;
        }

        if (tela === 'dashboard') {
            var raiz = document.createElement('div');
            raiz.setAttribute('data-sgi-screen', 'dashboard');
            raiz.innerHTML = state.dashHtml;
            state.montadas['dashboard'] = { root: raiz, inits: [] };
            state.registros['dashboard'] = { tela: 'dashboard', url: construirUrl('dashboard', params) };
            ativarMontagem('dashboard', 'dashboard', params);
            return;
        }

        obterRegistro(tela, params).then(function (rec) {
            montarTela(key, rec, tela, params);
        }).catch(function (e) {
            if (window.console) console.error('[SGI Mesario SPA] navegar', e);
            conteudo.innerHTML = '<div style="max-width:640px;margin:60px auto;text-align:center;padding:2rem;background:#fff;' +
                'border:1px solid #fecaca;border-radius:16px;color:#991b1b;">' +
                '<i class="bi bi-wifi-off fs-1 d-block mb-3"></i>' +
                '<strong style="font-size:1.05rem;">Esta tela não está disponível offline. ' +
                'Conecte-se à internet e atualize a página.</strong></div>';
        });
    }

    function montarTela(key, rec, tela, params) {
        var conteudo = document.getElementById('conteudo-principal');
        if (!conteudo) return;

        var raiz = document.createElement('div');
        raiz.setAttribute('data-sgi-screen', key);
        raiz.innerHTML = rec.html;

        conteudo.replaceChildren(raiz);

        state.registros[key] = { tela: tela, url: rec.url || construirUrl(tela, params) };

        if (rec.css) aplicarCss(rec.css);

        state.montando = key;
        state.pendentesInit = [];
        executarScripts(rec.script);
        var inits = state.pendentesInit.slice();
        state.pendentesInit = [];
        state.montando = null;

        state.montadas[key] = { root: raiz, inits: inits };
        ativarMontagem(key, tela, params);
    }

    function ativarMontagem(key, tela, params) {
        var m = state.montadas[key];
        var conteudo = document.getElementById('conteudo-principal');
        if (!m || !conteudo) return;
        conteudo.replaceChildren(m.root);

        var rec = state.registros[key] || {};
        tela = tela || rec.tela || 'dashboard';
        var url;
        if (params && (params.id_jogo != null || params.id != null || params.origem != null)) {
            url = construirUrl(tela, params);
        } else {
            url = rec.url || construirUrl(tela, {});
        }
        pushEstado(tela, key, url);
        document.title = TELA_TITULO[tela] || 'SGI';

        if (m.inits) m.inits.forEach(function (fn) { runSafe(fn); });
    }

    /* ==================== Interceptacao de links e popstate ==================== */

    function registrarInterceptacao() {
        document.addEventListener('click', function (e) {
            if (state.nivel !== 2) return;
            if (e.defaultPrevented || e.button !== 0) return;
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
            var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
            if (!a) return;
            if (a.target && a.target !== '_self') return;
            var href = a.getAttribute('href');
            if (!href || href.charAt(0) === '#') return;
            var abs;
            try { abs = new URL(a.href); } catch (err) { return; }
            if (abs.origin !== window.location.origin) return;
            var map = mapearTela(abs.pathname + abs.search);
            if (!map) return;
            e.preventDefault();
            navegarPara(map.tela, map.params);
        });

        window.addEventListener('popstate', function (e) {
            var s = e.state && e.state.sgi;
            if (s && s.tela && state.montadas[s.key]) {
                ativarMontagem(s.key, s.tela, null);
                return;
            }
            var m = mapearTela(window.location.pathname + window.location.search);
            if (m) {
                navegarPara(m.tela, m.params);
            } else {
                window.location.reload();
            }
        });
    }

    /* ==================== Preload de telas + dados ==================== */

    function dataUrls(id) {
        var b = apiBase();
        return [
            b + 'interclasse.php?regulamento=true',
            b + 'interclasse.php?id=' + id + '&regulamento=true',
            // A agenda consulta todas as modalidades e depois cada modalidade.
            b + 'modalidades.php',
            b + 'modalidades.php?id_interclasse=' + id,
            b + 'locais.php?id_interclasse=' + id + '&disponivel=1',
            b + 'locais.php?id_interclasse=' + id,
            b + 'categorias.php?id_interclasse=' + id,
            b + 'turmas.php?id_interclasse=' + id,
            b + 'jogos.php?id_interclasse=' + id,
            b + 'jogos.php?x=1&id_interclasse=' + id
        ];
    }

    function dadosPorJogo(j) {
        var b = apiBase();
        var urls = [
            b + 'jogos.php?id_jogo=' + j.id_jogo,
            b + 'partidas.php?id_jogo=' + j.id_jogo,
            b + 'artilheiro.php?id_jogo=' + j.id_jogo,
            b + 'ocorrencias.php?id_jogo=' + j.id_jogo + '&data=' + encodeURIComponent(j.data_jogo || '')
        ];
        return fetchJson(b + 'jogos.php?id_jogo=' + j.id_jogo).then(function (lista) {
            var jogo = (Array.isArray(lista) && lista[0]) || j;
            var ehIndividual = /^IND:/.test(jogo.nome_jogo || '') ||
                parseInt(jogo.tipos_modalidades_id_tipo_modalidade, 10) === 2;
            var idMod = jogo.modalidades_id_modalidade;
            if (ehIndividual && idMod) {
                urls.push(b + 'chaveamento.php?tipo_modalidade=individual&acao=participantes&id_modalidade=' + idMod);
                urls.push(b + 'chaveamento.php?tipo_modalidade=individual&acao=ranking&id_modalidade=' + idMod);
            }
            return fetchJson(b + 'partidas.php?id_jogo=' + j.id_jogo).then(function (partidas) {
                var vistas = {};
                (Array.isArray(partidas) ? partidas : []).forEach(function (p) {
                    var t = parseInt(p.id_turma, 10);
                    if (!t || vistas[t]) return;
                    vistas[t] = true;
                    urls.push(b + 'ocorrencias.php?acao=listar_atletas&id_jogo=' + j.id_jogo + '&id_turma=' + t);
                });
                return urls;
            });
        }).catch(function () { return urls; });
    }

    function aquecer(url) {
        return fetch(url).then(function (r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.text();
        });
    }

    function preload() {
        if (state.nivel !== 2 || !state.temCasca) return;
        if (state.preloading) return;
        if (navigator.onLine === false) return;

        state.preloading = true;
        ocultarBadge();
        mostrarProgresso(0, 'Iniciando download para uso offline...');

        var jobs = [];
        var contagem = { total: 0, feito: 0, falhas: 0 };
        var semInterclasse = false;
        // Perfil não depende da edição. As demais telas são adicionadas abaixo
        // já com o id correto, para a chave local ser a mesma da navegação.
        var telasBase = ['perfil'];
        telasBase.forEach(function (t) {
            jobs.push(function () { return baixarTela(t, {}); });
        });

        obterIdAtivo().then(function (id) {
            if (!id) {
                semInterclasse = true;
                return null;
            }
            ['agenda', 'chaveamento', 'ocorrencias', 'jogoslista'].forEach(function (tela) {
                jobs.push(function () { return baixarTela(tela, { id: id }); });
            });
            dataUrls(id).forEach(function (u) {
                jobs.push(function () { return aquecer(u); });
            });
            // Algumas telas usam a edição ativa, mesmo quando o Dashboard foi
            // aberto com ?id=. Baixa as consultas-base de todas as edições que
            // o mesário recebeu para não deixar a UI vazia nesse cenário.
            jobs.push(function () {
                return fetchJson(apiBase() + 'interclasse.php?regulamento=true').then(function (edicoes) {
                    return Promise.all((Array.isArray(edicoes) ? edicoes : []).map(function (edicao) {
                        var outroId = edicao && edicao.id_interclasse;
                        if (!outroId || String(outroId) === String(id)) return null;
                        return Promise.all(dataUrls(outroId).filter(function (url) {
                            return url.indexOf('interclasse.php') === -1;
                        }).map(function (url) { return aquecer(url); }));
                    }));
                });
            });
            return Promise.all([
                fetchJson(apiBase() + 'jogos.php?id_interclasse=' + id),
                fetchJson(apiBase() + 'modalidades.php'),
                fetchJson(apiBase() + 'turmas.php?id_interclasse=' + id),
                fetchJson(apiBase() + 'categorias.php?id_interclasse=' + id)
            ]).then(function (resultados) {
                var jogos = resultados[0];
                var modalidades = Array.isArray(resultados[1]) ? resultados[1] : [];
                var turmas = Array.isArray(resultados[2]) ? resultados[2] : [];
                var categorias = Array.isArray(resultados[3]) ? resultados[3] : [];
                var lista = Array.isArray(jogos) ? jogos : [];
                modalidades.filter(function (m) {
                    return String(m.interclasses_id_interclasse) === String(id);
                }).forEach(function (m) {
                    jobs.push(function () {
                        return aquecer(apiBase() + 'jogos.php?id_modalidade=' + encodeURIComponent(m.id_modalidade));
                    });
                    // A árvore de chaveamento consulta esta rota para cada
                    // modalidade, inclusive nas modalidades coletivas.
                    jobs.push(function () {
                        return aquecer(apiBase() + 'chaveamento.php?id_modalidade=' + encodeURIComponent(m.id_modalidade));
                    });
                });
                categorias.forEach(function (categoria) {
                    if (!categoria.id_categoria) return;
                    jobs.push(function () {
                        return aquecer(apiBase() + 'jogos.php?id_interclasse=' + id +
                            '&id_categoria=' + encodeURIComponent(categoria.id_categoria));
                    });
                });
                turmas.forEach(function (turma) {
                    if (!turma.id_turma) return;
                    jobs.push(function () {
                        return aquecer(apiBase() + 'ocorrencias_turmas.php?id_interclasse=' + id +
                            '&id_turma=' + encodeURIComponent(turma.id_turma));
                    });
                });
                lista.forEach(function (j) {
                    jobs.push(function () { return baixarTela('jogos', { id_jogo: j.id_jogo }); });
                });
                var chain = Promise.resolve();
                lista.forEach(function (j) {
                    chain = chain.then(function () { return dadosPorJogo(j); }).then(function (urls) {
                        urls.forEach(function (u) { jobs.push(function () { return aquecer(u); }); });
                    }).catch(function () {});
                });
                return chain;
            }).catch(function (e) {
                if (window.console) console.warn('[SGI Mesario SPA] lista de jogos', e);
                return null;
            });
        }).then(function () {
            if (semInterclasse) {
                terminarPreload(false, 'Nenhum interclasse ativo para baixar agora.');
                return null;
            }
            contagem.total = jobs.length;
            return jobs.reduce(function (p, job) {
                return p.then(function () {
                    return Promise.resolve(job()).catch(function (e) {
                        contagem.falhas++;
                        if (window.console) console.warn('[SGI Mesario SPA] preload', e);
                    }).then(function () {
                        contagem.feito++;
                        var pct = Math.round((contagem.feito / contagem.total) * 100);
                        mostrarProgresso(pct, 'Baixando dados para uso offline... ' + pct + '%');
                    });
                });
            }, Promise.resolve());
        }).then(function () {
            if (!semInterclasse) terminarPreload(contagem.falhas === 0);
        }).catch(function (e) {
            if (window.console) console.error('[SGI Mesario SPA] preload', e);
            terminarPreload(false);
        });
    }

    function terminarPreload(ok, mensagem) {
        state.preloading = false;
        ocultarProgresso();
        if (ok) {
            state.pronto = true;
            marcarPronto();
            mostrarBadge();
            aviso('Tudo pronto! Páginas e dados sincronizados. Você já pode usar offline. 🟢');
        } else {
            if (mensagem) {
                aviso(mensagem);
                return;
            }
            idbGet(chaveTela('agenda', {})).then(function (rec) {
                if (rec) {
                    state.pronto = true;
                    marcarPronto();
                    mostrarBadge();
                    aviso('Download parcial. Conecte-se para completar.');
                } else {
                    aviso('Não foi possível baixar as telas agora. Verifique a conexão.');
                }
            });
        }
    }

    function marcarPronto() {
        try {
            if (state.pronto) localStorage.setItem('sgi_pronto_' + SESSION, '1');
            else localStorage.removeItem('sgi_pronto_' + SESSION);
        } catch (e) {}
    }

    function verificarPronto() {
        var flag = false;
        try { flag = localStorage.getItem('sgi_pronto_' + SESSION) === '1'; } catch (e) {}
        return idbGet(chaveTela('agenda', {})).then(function (rec) {
            if (rec || flag) {
                state.pronto = true;
                mostrarBadge();
            }
            return state.pronto;
        }).catch(function () { return false; });
    }

    /* ============================ Interface ============================ */

    var PROGRESSO_ID = 'sgi-progresso';
    var BADGE_ID = 'sgi-offline-ok';

    var CSS_UI = '' +
        '#' + PROGRESSO_ID + '{position:fixed;top:58px;left:50%;transform:translateX(-50%);z-index:3000;' +
        'width:min(92vw,440px);background:#fff;border:1px solid #e5e7eb;border-radius:14px;' +
        'box-shadow:0 10px 30px rgba(0,0,0,.15);padding:12px 14px;display:none;}' +
        '#' + PROGRESSO_ID + ' .sp-label{font-size:.8rem;font-weight:600;color:#111827;}' +
        '#' + PROGRESSO_ID + ' .sp-bar{height:8px;background:#e5e7eb;border-radius:999px;margin-top:8px;overflow:hidden;}' +
        '#' + PROGRESSO_ID + ' .sp-bar>div{height:100%;width:0%;background:linear-gradient(90deg,#16a34a,#22c55e);' +
        'border-radius:999px;transition:width .25s;}' +
        '#' + BADGE_ID + '{position:fixed;top:58px;right:14px;z-index:3000;background:#ecfdf5;color:#065f46;' +
        'border:1px solid #a7f3d0;border-radius:999px;padding:8px 14px;font-size:.78rem;font-weight:600;' +
        'box-shadow:0 6px 18px rgba(6,95,70,.18);cursor:pointer;display:none;align-items:center;gap:6px;}' +
        '#sgi-aviso{position:fixed;left:50%;bottom:90px;transform:translateX(-50%);z-index:3100;background:#111827;' +
        'color:#fff;border-radius:999px;padding:10px 16px;font-size:.82rem;box-shadow:0 8px 24px rgba(0,0,0,.3);' +
        'opacity:0;transition:opacity .25s;pointer-events:none;white-space:nowrap;max-width:92vw;overflow:hidden;' +
        'text-overflow:ellipsis;font-family:inherit;}';

    function criarUi() {
        var st = document.getElementById('sgi-spa-estilos');
        if (!st) {
            st = document.createElement('style');
            st.id = 'sgi-spa-estilos';
            st.textContent = CSS_UI;
            document.head.appendChild(st);
        }
        if (!document.getElementById(PROGRESSO_ID)) {
            var p = document.createElement('div');
            p.id = PROGRESSO_ID;
            p.innerHTML = '<div class="sp-label">Baixando dados para uso offline...</div>' +
                '<div class="sp-bar"><div></div></div>';
            document.body.appendChild(p);
        }
        if (!document.getElementById(BADGE_ID)) {
            var b = document.createElement('div');
            b.id = BADGE_ID;
            b.title = 'Clique para baixar novamente';
            b.innerHTML = '<i class="bi bi-check2-circle"></i> Pronto para uso offline! 🟢';
            b.addEventListener('click', function () {
                if (state.preloading) return;
                preload();
            });
            document.body.appendChild(b);
        }
        if (!document.getElementById('sgi-aviso')) {
            var a = document.createElement('div');
            a.id = 'sgi-aviso';
            document.body.appendChild(a);
        }
    }

    function mostrarProgresso(pct, label) {
        var p = document.getElementById(PROGRESSO_ID);
        if (!p) return;
        p.style.display = 'block';
        var lab = p.querySelector('.sp-label');
        var bar = p.querySelector('.sp-bar > div');
        if (lab) lab.textContent = label || '';
        if (bar) bar.style.width = Math.max(2, Math.min(100, pct || 0)) + '%';
    }

    function ocultarProgresso() {
        var p = document.getElementById(PROGRESSO_ID);
        if (p) p.style.display = 'none';
    }

    function mostrarBadge() {
        var b = document.getElementById(BADGE_ID);
        if (b) { b.style.display = 'inline-flex'; }
    }

    function ocultarBadge() {
        var b = document.getElementById(BADGE_ID);
        if (b) b.style.display = 'none';
    }

    var avisoTimer = null;
    function aviso(msg) {
        var el = document.getElementById('sgi-aviso');
        if (!el) return;
        el.textContent = msg;
        el.style.opacity = '1';
        if (avisoTimer) clearTimeout(avisoTimer);
        avisoTimer = setTimeout(function () { el.style.opacity = '0'; }, 4200);
    }

    /* ============================ Inicializacao ============================ */

    function init() {
        state.nivel = parseInt(window.SGI_SESSION_NIVEL, 10);
        if (state.nivel !== 2) return;

        var conteudo = document.getElementById('conteudo-principal');
        if (conteudo) {
            state.temCasca = true;
            state.dashHtml = conteudo.innerHTML;
        }

        criarUi();
        registrarInterceptacao();

        if (!state.temCasca) return;

        // URL inicial da casca, para o botão "voltar" retornar à dashboard
        obterIdAtivo().then(function (id) {
            var url = construirUrl('dashboard', { id: id });
            try {
                history.replaceState({ sgi: { tela: 'dashboard', key: 'dashboard' } }, '', url);
            } catch (e) {}
        });

        verificarPronto().then(function () {
            preload();
        });
    }

    window.__SGI_SPA__ = {
        registrarInit: registrarInit,
        navegarPara: navegarPara,
        preload: preload,
        status: function () {
            return {
                nivel: state.nivel,
                temCasca: state.temCasca,
                pronto: state.pronto,
                preloading: state.preloading
            };
        }
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
