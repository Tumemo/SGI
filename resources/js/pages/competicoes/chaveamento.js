window.SGIPage.mount("competicoes/chaveamento", function (pageConfig, pageScope) {

    const urlParams = new URLSearchParams(window.location.search);
    let idInterclasse = urlParams.get('id');
    let modalidadesCache = [];
    let jogosCache = [];
    const NIVEL_USUARIO = pageConfig.value3;

    function modalidadeEhIndividual(mod) {
        if (!mod) return false;
        if (mod.tipo_competicao === 'individual') return true;
        if (mod.tipo_competicao === 'mata_mata') return false;
        const nome = String(mod.nome_tipo_modalidade || '').trim().toLowerCase();
        if (nome === 'individual' || nome === 'prova individual') return true;
        if (nome === 'mata-mata' || nome === 'mata mata') return false;
        return !mod.tipo_competicao && Number(mod.id_tipo_modalidade || mod.tipos_modalidades_id_tipo_modalidade) === 2;
    }

    function jogoEhIndividual(jogo) {
        return modalidadeEhIndividual(jogo);
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    /* ── Select customizado (KVS): busca + grupos por categoria ── */
    let kvs_grupos = [];
    let kvs_instanciaAtiva = null;

    pageScope.listen(document, 'click', () => {
        if (kvs_instanciaAtiva) {
            kvs_instanciaAtiva.classList.remove('kvs--aberto');
            kvs_instanciaAtiva = null;
        }
    });

    function kvs_montarGrupos() {
        const grupos = {};
        modalidadesCache.forEach(mod => {
            const chave = mod.nome_categoria || 'Outras';
            if (!grupos[chave]) grupos[chave] = [];
            grupos[chave].push({
                valor: String(mod.id_modalidade),
                nome: mod.nome_modalidade,
                tipo: modalidadeEhIndividual(mod) ? 'Individual' : 'Coletiva'
            });
        });
        kvs_grupos = Object.keys(grupos).map(chave => ({ nome: chave, opcoes: grupos[chave] }));
    }

    function kvs_sincronizar(select, label, placeholder) {
        let opt = null;
        kvs_grupos.forEach(g => g.opcoes.forEach(o => {
            if (o.valor === String(select.value)) opt = o;
        }));
        label.textContent = opt ? opt.nome : placeholder;
        label.classList.toggle('kvs__trigger-label--preenchido', !!opt);
    }

    function kvs_montar(opts) {
        const wrap = document.getElementById(opts.wrapId);
        const select = document.getElementById(opts.selectId);
        if (!wrap || !select) return;

        wrap.innerHTML = `
            <div class="kvs">
                <button type="button" class="kvs__trigger">
                    <span class="kvs__trigger-label">${esc(opts.placeholder)}</span>
                    <i class="bi bi-chevron-down kvs__chevron"></i>
                </button>
                <div class="kvs__panel">
                    <div class="kvs__search-box">
                        <i class="bi bi-search kvs__search-icone"></i>
                        <input type="text" class="kvs__search" placeholder="Buscar modalidade..." autocomplete="off" spellcheck="false">
                    </div>
                    <div class="kvs__groups"></div>
                </div>
            </div>`;

        const root = wrap.querySelector('.kvs');
        const trigger = root.querySelector('.kvs__trigger');
        const label = root.querySelector('.kvs__trigger-label');
        const search = root.querySelector('.kvs__search');
        const groupsEl = root.querySelector('.kvs__groups');

        function renderizar(termo) {
            const t = (termo || '').toLowerCase().trim();
            let html = '';

            if (opts.incluirTodas) {
                const showTudo = !t || 'todas modalidades'.includes(t);
                if (showTudo) {
                    const ativa = select.value === '' ? ' kvs__opcao--ativa' : '';
                    html += `<button type="button" class="kvs__opcao${ativa}" data-value="">
                        <span class="kvs__opcao-nome">Todas modalidades</span>
                        <span class="kvs__opcao-tipo kvs__opcao-tipo--coletiva opacity-50" >Mostrar tudo</span>
                    </button>`;
                }
            }

            kvs_grupos.forEach(g => {
                const opcoes = g.opcoes.filter(o => !t || o.nome.toLowerCase().includes(t) || g.nome.toLowerCase().includes(t));
                if (!opcoes.length) return;
                html += `<div class="kvs__grupo">
                    <div class="kvs__grupo-titulo"><i class="bi bi-trophy-fill"></i>${esc(g.nome)}<span class="kvs__grupo-qtd">${opcoes.length}</span></div>`;
                opcoes.forEach(o => {
                    const ativa = String(select.value) === o.valor ? ' kvs__opcao--ativa' : '';
                    const tipoCls = o.tipo === 'Individual' ? 'kvs__opcao-tipo--individual' : 'kvs__opcao-tipo--coletiva';
                    html += `<button type="button" class="kvs__opcao${ativa}" data-value="${o.valor}">
                        <span class="kvs__opcao-nome">${esc(o.nome)}</span>
                        <span class="kvs__opcao-tipo ${tipoCls}">${o.tipo}</span>
                    </button>`;
                });
                html += '</div>';
            });

            groupsEl.innerHTML = html || '<div class="kvs__vazio">Nenhuma modalidade encontrada.</div>';
            groupsEl.querySelectorAll('.kvs__opcao').forEach(btn => {
                pageScope.listen(btn, 'click', () => {
                    select.value = btn.dataset.value;
                    kvs_sincronizar(select, label, opts.placeholder);
                    fechar();
                    select.dispatchEvent(new Event('change'));
                });
            });
        }

        function posicionarPainel() {
            const panel = root.querySelector('.kvs__panel');
            const trig = trigger.getBoundingClientRect();
            const W = Math.max(trig.width, 240);
            let left = Math.max(8, trig.left);
            if (left + W > window.innerWidth - 8) left = Math.max(8, window.innerWidth - W - 8);

            const espacoBaixo = window.innerHeight - trig.bottom - 8;
            const espacoCima = trig.top - 8;
            const paraCima = espacoBaixo < 200 && espacoCima > espacoBaixo;
            const altH = Math.max(180, Math.min(380, paraCima ? espacoCima : espacoBaixo));

            panel.style.position = 'fixed';
            panel.style.left = left + 'px';
            panel.style.width = W + 'px';
            panel.style.maxHeight = altH + 'px';
            panel.style.top = (paraCima ? trig.top - altH - 6 : trig.bottom + 6) + 'px';
            panel.style.bottom = 'auto';
        }

        function abrir() {
            search.value = '';
            renderizar('');
            root.classList.add('kvs--aberto');
            if (kvs_instanciaAtiva && kvs_instanciaAtiva !== root) {
                kvs_instanciaAtiva.classList.remove('kvs--aberto');
            }
            kvs_instanciaAtiva = root;
            posicionarPainel();
            try { search.focus({ preventScroll: true }); } catch (e) { search.focus(); }
        }

        function fechar() {
            root.classList.remove('kvs--aberto');
            if (kvs_instanciaAtiva === root) kvs_instanciaAtiva = null;
            const panel = root.querySelector('.kvs__panel');
            panel.style.position = '';
            panel.style.left = '';
            panel.style.width = '';
            panel.style.maxHeight = '';
            panel.style.top = '';
            panel.style.bottom = '';
        }

        pageScope.listen(trigger, 'click', (e) => {
            e.stopPropagation();
            if (root.classList.contains('kvs--aberto')) fechar();
            else abrir();
        });

        pageScope.listen(root, 'click', (e) => e.stopPropagation());

        pageScope.listen(search, 'input', () => renderizar(search.value));
        pageScope.listen(search, 'keydown', (e) => {
            if (e.key === 'Escape') { fechar(); trigger.focus(); }
            e.stopPropagation();
        });

        kvs_sincronizar(select, label, opts.placeholder);
    }

    function kvs_focus(idSelect) {
        const wrap = document.getElementById('kvs-wrap-' + idSelect);
        if (wrap) {
            const trigger = wrap.querySelector('.kvs__trigger');
            if (trigger) trigger.click();
        }
    }

    pageScope.listen(window, 'scroll', () => {
        if (kvs_instanciaAtiva) { kvs_instanciaAtiva.classList.remove('kvs--aberto'); kvs_instanciaAtiva = null; }
    }, { passive: true });
    pageScope.listen(window, 'resize', () => {
        if (kvs_instanciaAtiva) { kvs_instanciaAtiva.classList.remove('kvs--aberto'); kvs_instanciaAtiva = null; }
    });

    async function resolverInterclasse() {
        if (!idInterclasse) {
            const ativo = await window.SGIInterclasse.getActiveInterclasse();
            idInterclasse = ativo?.id_interclasse || null;
        }
        if (!idInterclasse) {
            alert("Nenhum interclasse ativo encontrado.");
            window.location.href = "/painel";
            return null;
        }
        const dados = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        ['nomeInterclasseChaveamento', 'nomeInterclasseChaveamentoMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.innerText = dados?.nome_interclasse || 'Interclasse';
        });
        ['btnVoltar', 'btnVoltarChaveamentoMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `/painel?id=${idInterclasse}`;
        });
        return idInterclasse;
    }

    function atualizarStats(jogos) {
        const total = modalidadesCache.length;
        const totalJogos = Array.isArray(jogos) ? jogos.length : 0;
        const concluidos = Array.isArray(jogos) ? jogos.filter(j => j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado').length : 0;
        const pendentes = totalJogos - concluidos;

        const modalidadesComCampeao = new Set();
        if (Array.isArray(jogos)) {
            jogos.forEach(j => {
                const status = (j.status_jogo || '').toLowerCase();
                const isConcluido = status === 'concluido' || status === 'finalizado';
                if (!isConcluido) return;
                const tag = j.nome_jogo || '';
                const isFinalMM = /^MM:2:/.test(tag);
                const isInd = jogoEhIndividual(j);
                if (isFinalMM || isInd) {
                    const idMod = j.modalidades_id_modalidade || j.id_modalidade;
                    if (idMod) modalidadesComCampeao.add(String(idMod));
                }
            });
        }
        const totalCampeoes = modalidadesComCampeao.size;

        ['statModalidades', 'statModalidadesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = total;
        });
        ['statJogos', 'statJogosMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = totalJogos;
        });
        ['statCampeoes', 'statCampeoesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = totalCampeoes;
        });
        ['statPendentes', 'statPendentesMob'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.textContent = Math.max(0, pendentes);
        });
    }

    function atualizarTimeline(niveis) {
        const timeline = document.getElementById('faseTimeline');
        if (!niveis || niveis.length === 0) {
            timeline.classList.add('d-none');
            return;
        }

        const fases = niveis.map((n, i) => ({
            nivel: n,
            label: (computarLabelsFases(niveis)[n]) || formatFase(n)
        }));
        const nivelAtual = fases[0]?.nivel || 1;

        let html = '<div class="d-flex align-items-center flex-nowrap">';
        fases.forEach((f, i) => {
            const isUltimo = i === 0;
            let cls = 'badge rounded-pill px-3 py-2 text-uppercase';
            if (isUltimo) cls += ' text-bg-danger';
            else if (i > 0) cls += ' text-bg-success';
            else cls += ' text-bg-secondary';

            html += `<span class="${cls}">${f.label}</span>`;
            if (i < fases.length - 1) {
                html += '<span class="text-body-tertiary px-2 flex-shrink-0"><i class="bi bi-arrow-right"></i></span>';
            }
        });
        html += '</div>';
        timeline.innerHTML = html;
        timeline.classList.remove('d-none');
    }

    async function carregarModalidades() {
        try {
            const resp = await fetch(`/api/v1/modalidades?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            modalidadesCache = Array.isArray(data) ? data : [];
            const select = document.getElementById('selectModalidade');
            if (select) select.innerHTML = '<option value="">Selecione uma modalidade</option>';
            const selectJogos = document.getElementById('filtroModalidadeJogos');
            selectJogos.innerHTML = '<option value="">Todas modalidades</option>';

            const selectMob = document.getElementById('selectModalidadeMob');
            if (selectMob) selectMob.innerHTML = '<option value="">Selecione uma modalidade</option>';
            const selectJogosMob = document.getElementById('filtroModalidadeJogosMob');
            if (selectJogosMob) selectJogosMob.innerHTML = '<option value="">Todas modalidades</option>';

            modalidadesCache.forEach(mod => {
                const genero = mod.genero_modalidade ? ` (${mod.genero_modalidade})` : '';
                const categoria = mod.nome_categoria ? ` [${mod.nome_categoria}]` : '';
                const label = `${mod.nome_modalidade}${genero}${categoria}`;
                if (select) select.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                selectJogos.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                if (selectMob) selectMob.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
                if (selectJogosMob) selectJogosMob.innerHTML += `<option value="${mod.id_modalidade}">${label}</option>`;
            });

            kvs_montarGrupos();
            kvs_montar({ wrapId: 'kvs-wrap-selectModalidade', selectId: 'selectModalidade', placeholder: 'Selecione uma modalidade', incluirTodas: false });
            kvs_montar({ wrapId: 'kvs-wrap-selectModalidadeMob', selectId: 'selectModalidadeMob', placeholder: 'Selecione uma modalidade', incluirTodas: false });
            kvs_montar({ wrapId: 'kvs-wrap-filtroModalidadeJogos', selectId: 'filtroModalidadeJogos', placeholder: 'Todas modalidades', incluirTodas: true });
            kvs_montar({ wrapId: 'kvs-wrap-filtroModalidadeJogosMob', selectId: 'filtroModalidadeJogosMob', placeholder: 'Todas modalidades', incluirTodas: true });

            atualizarStats(jogosCache);
        } catch (e) {
            console.error("Erro ao carregar modalidades:", e);
        }
    }

    async function carregarCategorias() {
        const select = document.getElementById('filtroCategoriaJogos');
        const selectMob = document.getElementById('filtroCategoriaJogosMob');
        try {
            const resp = await fetch(`/api/v1/categorias?id_interclasse=${idInterclasse}`);
            const data = await resp.json();
            const categorias = Array.isArray(data) ? data : [];
            select.innerHTML = '<option value="">Todas categorias</option>';
            if (selectMob) selectMob.innerHTML = '<option value="">Todas categorias</option>';
            categorias.forEach(c => {
                select.innerHTML += `<option value="${c.id_categoria}">${c.nome_categoria}</option>`;
                if (selectMob) selectMob.innerHTML += `<option value="${c.id_categoria}">${c.nome_categoria}</option>`;
            });
        } catch (e) {
            console.error("Erro ao carregar categorias:", e);
        }
    }

    function formatarNomePartida(jogo) {
        const tag = jogo.nome_jogo || '';
        if (jogoEhIndividual(jogo)) {
            const equipes = (jogo.equipes_nomes || '').trim();
            if (equipes) {
                return `Competição Individual: ${equipes}`;
            }
            return 'Competição Individual';
        }
        const mm = tag.match(/^MM:(\d+):(\d+):([NB])$/);
        if (mm) {
            const largura = parseInt(mm[1], 10);
            const slot = parseInt(mm[2], 10);
            const kind = mm[3];
            const fases = {
                16: 'Oitavas de final',
                8: 'Quartas de final',
                4: 'Semifinal',
                2: 'Final',
                1: 'Campeão'
            };
            const fase = fases[largura] || 'Fase ' + largura;
            const confronto = slot + 1;
            const equipes = (jogo.equipes_nomes || '').trim();
            const sufixo = kind === 'B' ? ' (bye)' : '';
            if (equipes) {
                return `${fase} — confronto ${confronto}: ${equipes}${sufixo}`;
            }
            return `${fase} — confronto ${confronto}${sufixo}`;
        }
        const pos = tag.match(/^POS:(\d+):(\d+):([NB])$/);
        if (pos) {
            const posicao = parseInt(pos[1], 10);
            const nomesPos = {
                3: 'Disputa de 3º lugar',
                5: 'Disputa de 5º lugar'
            };
            return nomesPos[posicao] || `Disputa de ${posicao}º lugar`;
        }
        return tag || '---';
    }

    let _editIdJogo = null;
    let _editJogoData = null;

    function _popularModalEdicao(jogo) {
        _editIdJogo = jogo.id_jogo;
        _editJogoData = jogo;

        document.getElementById('editIdJogo').value = jogo.id_jogo || '';
        document.getElementById('editNomePartida').textContent = formatarNomePartida(jogo);
        document.getElementById('editModalidadePartida').textContent = jogo.nome_modalidade || '';
        document.getElementById('editDataJogo').value = jogo.data_jogo || '';
        document.getElementById('editInicioJogo').value = jogo.inicio_jogo || '';
        document.getElementById('editTerminoJogo').value = jogo.termino_jogo || '';
        document.getElementById('editStatusJogo').value = jogo.status_jogo || 'Agendado';
        document.getElementById('msgEditarJogo').innerHTML = '';

        const teamsSection = document.getElementById('editTeamsSection');
        const teamsList = document.getElementById('editTeamsList');
        const winnerSection = document.getElementById('editWinnerSection');
        const winnerOptions = document.getElementById('editWinnerOptions');
        const eqs = jogo.equipes || [];

        if (eqs.length > 0 && !jogo.eh_bye) {
            teamsSection.classList.remove('d-none');
            let teamsHtml = '';
            eqs.forEach((eq, idx) => {
                const nome = eq.nome_equipe || eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                teamsHtml += `
                    <div class="team-row bg-body-tertiary border rounded-3 p-3 d-flex align-items-center gap-3 mb-2">
                        <div class="team-row__name flex-grow-1 fw-semibold text-body">${nome}</div>
                        <div class="team-row__score flex-shrink-0" style="width: 72px;">
                            <input type="number" min="0" class="form-control text-center fw-bold edit-score-input" data-equipe-id="${eq.id_equipe}" value="${eq.gols ?? 0}">
                        </div>
                    </div>`;
            });
            teamsList.innerHTML = teamsHtml;

            if (eqs.length === 2) {
                winnerSection.classList.remove('d-none');
                let winnerHtml = '';
                eqs.forEach(eq => {
                    const nome = eq.nome_equipe || eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                    const checked = jogo.equipe_vencedora_id == eq.id_equipe ? 'checked' : '';
                    winnerHtml += `
                        <div class="winner-radio form-check d-flex align-items-center gap-2">
                            <input class="form-check-input mt-0" type="radio" name="editWinner" id="winner_${eq.id_equipe}" value="${eq.id_equipe}" ${checked}>
                            <label class="form-check-label small fw-semibold" for="winner_${eq.id_equipe}">${nome}</label>
                        </div>`;
                });
                winnerOptions.innerHTML = winnerHtml;
            } else {
                winnerSection.classList.add('d-none');
                winnerOptions.innerHTML = '';
            }
        } else {
            teamsSection.classList.add('d-none');
            teamsList.innerHTML = '';
            winnerSection.classList.add('d-none');
            winnerOptions.innerHTML = '';
        }
    }

    function editarJogo(btn) {
        let jogo;
        try {
            jogo = JSON.parse(btn.dataset.jogo);
        } catch (e) {
            console.error('Erro ao parsear dados do jogo:', e);
            return;
        }

        _popularModalEdicao(jogo);

        var modalEl = document.getElementById('modalEditarJogo');
        var selectLocal = document.getElementById('editLocalJogo');
        selectLocal.innerHTML = '<option value="">Carregando...</option>';

        fetch('/api/v1/locais?id_interclasse=' + idInterclasse + '&disponivel=1')
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (_editIdJogo !== jogo.id_jogo) return;
                const locais = data.success && Array.isArray(data.data) ? data.data : [];
                selectLocal.innerHTML = '<option value="">A definir</option>';
                locais.forEach(function(l) {
                    var sel = Number(l.id_local) === Number(jogo.locais_id_local) ? 'selected' : '';
                    selectLocal.innerHTML += '<option value="' + l.id_local + '" ' + sel + '>' + l.nome_local + '</option>';
                });
            })
            .catch(function() {
                if (_editIdJogo !== jogo.id_jogo) return;
                selectLocal.innerHTML = '<option value="">Erro ao carregar locais</option>';
            });

        var modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    function editarJogoBracket(btn) {
        /* Partidas geradas localmente (offline) recebem id negativo temporário:
           ainda não existem no servidor, então não podem ser editadas aqui.
           Elas serão materializadas pelo PHP durante a sincronização. */
        try {
            var dados = JSON.parse(btn.getAttribute('data-jogo'));
            if (dados && Number(dados.id_jogo) < 0) {
                alert('Esta partida foi gerada offline e será criada no servidor após a sincronização.');
                return;
            }
        } catch (e) { /* segue o fluxo normal */ }
        editarJogo(btn);
    }

    async function salvarEdicaoJogo(e) {
        e.preventDefault();
        var btn = document.getElementById('btnSalvarJogo');
        var msgEl = document.getElementById('msgEditarJogo');
        msgEl.innerHTML = '';

        var id_jogo = document.getElementById('editIdJogo').value;
        var data_jogo = document.getElementById('editDataJogo').value;
        var inicio_jogo = document.getElementById('editInicioJogo').value;
        var termino_jogo = document.getElementById('editTerminoJogo').value;
        var locais_id_local = document.getElementById('editLocalJogo').value;
        var status_jogo = document.getElementById('editStatusJogo').value;
        var statusAtual = _editJogoData && _editJogoData.status_jogo ? String(_editJogoData.status_jogo) : '';

        if (status_jogo === 'Concluido') {
            const scoreInputsCheck = document.querySelectorAll('.edit-score-input');
            if (scoreInputsCheck.length > 0) {
                const totalGols = Array.from(scoreInputsCheck).reduce((sum, inp) => sum + (Number(inp.value) || 0), 0);
                if (totalGols === 0) {
                    msgEl.innerHTML = '<span class="text-danger fw-bold">Não é possível finalizar um jogo com placar 0x0. Registre o placar correto.</span>';
                    return;
                }
            }
        }

        var payload = {
            id_jogo: Number(id_jogo),
            data_jogo: data_jogo || null,
            inicio_jogo: inicio_jogo || null,
            termino_jogo: termino_jogo || null,
            locais_id_local: locais_id_local ? Number(locais_id_local) : null,
        };
        if (status_jogo !== statusAtual) payload.status_jogo = status_jogo;

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span> Salvando...';

        try {
            var resp = await fetch('/api/v1/jogos', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });
            var data = await resp.json();

            if (data.success) {
                const scoreInputs = document.querySelectorAll('.edit-score-input');
                const hasScores = scoreInputs.length > 0;
                let scoresSaved = true;

                if (hasScores) {
                    const resultados = [];
                    scoreInputs.forEach(inp => {
                        resultados.push({
                            id_equipe: Number(inp.dataset.equipeId),
                            gols: Number(inp.value) || 0
                        });
                    });

                    const scoreResp = await fetch('/api/v1/resultados', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            id_jogo: Number(id_jogo),
                            resultados: resultados
                        })
                    });
                    const scoreData = await scoreResp.json();
                    scoresSaved = scoreData.success;
                    if (!scoresSaved) {
                        msgEl.innerHTML = '<span class="text-warning fw-bold">Dados atualizados, mas erro ao salvar placar: ' + (scoreData.message || '') + '</span>';
                        setTimeout(function() {
                            var m = bootstrap.Modal.getInstance(document.getElementById('modalEditarJogo'));
                            if (m) m.hide();
                            const selectAtivo = document.getElementById('selectModalidade');
                            if (selectAtivo && selectAtivo.value) {
                                carregarArvore(selectAtivo.value);
                                carregarJogos();
                            }
                        }, 1500);
                        return;
                    }
                }

                msgEl.innerHTML = '<span class="text-success fw-bold">Jogo atualizado com sucesso!</span>';
                setTimeout(function() {
                    var m = bootstrap.Modal.getInstance(document.getElementById('modalEditarJogo'));
                    if (m) m.hide();
                    const selectAtivo = document.getElementById('selectModalidade');
                    if (selectAtivo && selectAtivo.value) {
                        carregarArvore(selectAtivo.value);
                        carregarJogos();
                    }
                }, 800);
            } else {
                msgEl.innerHTML = '<span class="text-danger fw-bold">' + (data.message || 'Erro ao atualizar jogo.') + '</span>';
            }
        } catch (err) {
            msgEl.innerHTML = '<span class="text-danger fw-bold">Erro de conexão.</span>';
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        }
    }

    function formatarDuracaoJogo(j) {
        if (j.status_jogo !== 'Concluido' && j.status_jogo !== 'Finalizado') return '---';
        if (j.data_inicio_real && j.termino_jogo) {
            try {
                const ini = new Date(j.data_jogo + 'T' + j.data_inicio_real);
                const fim = new Date(j.data_jogo + 'T' + j.termino_jogo);
                let diff = Math.max(0, Math.floor((fim - ini) / 60000));
                const h = Math.floor(diff / 60);
                const m = diff % 60;
                if (h > 0) return h + 'h' + (m > 0 ? m + 'min' : '');
                return m + 'min';
            } catch (_) {}
        }
        if (j.duracao_jogo) {
            const totalSec = parseInt(j.duracao_jogo, 10);
            const h = Math.floor(totalSec / 3600);
            const m = Math.floor((totalSec % 3600) / 60);
            if (h > 0) return h + 'h' + (m > 0 ? m + 'min' : '');
            return m + 'min';
        }
        return '---';
    }

    function formatarAcrescimosJogo(j) {
        if (j.status_jogo !== 'Concluido' && j.status_jogo !== 'Finalizado') return '---';
        const extraSec = parseInt(j.tempo_extra_jogo, 10);
        if (!extraSec || extraSec <= 0) return '---';
        const m = Math.floor(extraSec / 60);
        const s = extraSec % 60;
        if (m > 0) return '+' + m + 'min' + (s > 0 ? s + 's' : '');
        return '+' + s + 's';
    }

    function renderizarLinhaJogo(j, labelsLarguras) {
        let dataJogo = '---';
        if (j.data_jogo) {
            try {
                dataJogo = new Date(j.data_jogo + (j.inicio_jogo ? 'T' + j.inicio_jogo : '')).toLocaleString('pt-BR');
            } catch (_) {
                dataJogo = j.data_jogo;
            }
        }
        var nomePartida = formatarNomePartida(j);
        var m = (j.nome_jogo || '').match(/^MM:(\d+):/);
        if (m) {
            var largura = parseInt(m[1], 10);
            var labelCorreto = labelsLarguras[largura];
            if (labelCorreto) {
                var fases = {
                    16: 'Oitavas de final',
                    8: 'Quartas de final',
                    4: 'Semifinal',
                    2: 'Final',
                    1: 'Campeão'
                };
                var faseOriginal = fases[largura] || '';
                if (faseOriginal && faseOriginal !== labelCorreto) {
                    nomePartida = nomePartida.replace(faseOriginal, labelCorreto);
                }
            }
        }
        const statusLower = (j.status_jogo || '').toLowerCase();
        const statusLabel = j.status_jogo || '---';
        const tempoDecorrido = formatarDuracaoJogo(j);
        const acrescimos = formatarAcrescimosJogo(j);
        const destaque = j.artilheiro_nome || '---';
        const statusVariants = {
            agendado: 'warning',
            aguardando: 'info',
            andamento: 'primary',
            iniciado: 'primary',
            concluido: 'success',
            finalizado: 'success',
            cancelado: 'secondary'
        };
        const statusVariant = statusVariants[statusLower] || 'secondary';
        return `<tr>
            <td class="td-partida fw-semibold text-body">${nomePartida}</td>
            <td class="td-modalidade text-body-secondary fw-medium">${j.nome_modalidade || '---'}</td>
            <td class="td-data text-body-secondary text-nowrap">${dataJogo}</td>
            <td>${tempoDecorrido}</td>
            <td>${acrescimos}</td>
            <td>${destaque}</td>
            <td><span class="badge rounded-pill text-bg-${statusVariant}">${statusLabel}</span></td>
            <td>
                <div class="d-flex gap-2 justify-content-end">
                    <a href="/jogos/placar?id_jogo=${j.id_jogo}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center justify-content-center" title="Acessar Jogo" aria-label="Acessar Jogo">
                        <i class="bi bi-play-fill"></i>
                    </a>
                    <button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center" title="Editar Jogo" aria-label="Editar Jogo"
                        data-jogo='${JSON.stringify(j).replace(/'/g, "&#39;")}'
                        onclick="editarJogo(this)">
                        <i class="bi bi-pencil"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }

    async function carregarJogos() {
        const tbody = document.getElementById('tbodyJogos');
        const tbodyMob = document.getElementById('tbodyJogosMob');
        try {
            const idModalidade = document.getElementById('filtroModalidadeJogos').value;
            const idCategoria = document.getElementById('filtroCategoriaJogos').value;

            let statsUrl = `/api/v1/jogos?id_interclasse=${idInterclasse}`;
            const statsResp = await fetch(statsUrl);
            const statsData = await statsResp.json();
            let statsJogos = Array.isArray(statsData) ? statsData : [];
            statsJogos = statsJogos.filter(function(j) {
                var nomes = (j.equipes_nomes || '').trim();
                var isInd = jogoEhIndividual(j);
                return isInd || (nomes.indexOf(' vs ') !== -1);
            });
            atualizarStats(statsJogos);

            let url = `/api/v1/jogos?id_interclasse=${idInterclasse}`;
            if (idModalidade) url += `&id_modalidade=${idModalidade}`;
            if (idCategoria) url += `&id_categoria=${idCategoria}`;

            const resp = await fetch(url);
            const data = await resp.json();
            let jogos = Array.isArray(data) ? data : [];

            jogos = jogos.filter(function(j) {
                var nomes = (j.equipes_nomes || '').trim();
                var isInd = jogoEhIndividual(j);
                return isInd || (nomes.indexOf(' vs ') !== -1) || (nomes.length > 0);
            });

            jogosCache = jogos;

            if (!jogos.length) {
                const msg = '<tr><td colspan="8" class="text-center text-muted py-4">Nenhum jogo encontrado.</td></tr>';
                tbody.innerHTML = msg;
                if (tbodyMob) tbodyMob.innerHTML = msg;
                return;
            }

            var larguras = [];
            var largurasSet = {};
            jogos.forEach(function(j) {
                var m = (j.nome_jogo || '').match(/^MM:(\d+):/);
                if (m) {
                    var l = parseInt(m[1], 10);
                    if (l > 1 && !largurasSet[l]) {
                        largurasSet[l] = true;
                        larguras.push(l);
                    }
                }
            });
            larguras.sort(function(a, b) {
                return b - a;
            });
             var labelsLarguras = {};
            larguras.forEach(function(l) {
                labelsLarguras[l] = fasesLabel[l] || ('Fase ' + l);
            });

            const html = jogos.map(j => renderizarLinhaJogo(j, labelsLarguras)).join('');
            tbody.innerHTML = html;
            if (tbodyMob) tbodyMob.innerHTML = html;
        } catch (e) {
            console.error("Erro ao carregar jogos:", e);
            const msg = '<tr><td colspan="8" class="text-center text-danger py-4">Erro ao carregar jogos.</td></tr>';
            tbody.innerHTML = msg;
            if (tbodyMob) tbodyMob.innerHTML = msg;
        }
    }

    const fasesLabel = {
        1: 'Campeão',
        2: 'Final',
        4: 'Semifinal',
        8: 'Quartas de final',
        16: 'Oitavas de final'
    };

    function formatFase(faseNivel) {
        return fasesLabel[faseNivel] || `Fase ${faseNivel}`;
    }

    function computarLabelsFases(niveis) {
        const labels = {};
        niveis.forEach(n => { labels[n] = formatFase(n); });
        return labels;
    }

    function formatFaseFromNome(nomeJogo) {
        const pos = (nomeJogo || '').match(/^POS:(\d+):/);
        if (pos) {
            const p = parseInt(pos[1], 10);
            const nomesPos = {
                3: 'Disputa de 3º lugar',
                5: 'Disputa de 5º lugar'
            };
            return nomesPos[p] || `Disputa de ${p}º lugar`;
        }
        return null;
    }

    /* ── Bracket Tree Renderer ── */
    let _lastBracketData = null;
    let _pollingTimer = null;
    let _currentModalidade = null;
    let _fonteDadosAtual = null;

    /* Selo visual quando a árvore foi calculada localmente (modo offline). */
    function _badgeFonteLocal() {
        return `<div class="alert alert-info mt-3 d-flex align-items-center gap-2" >
            <i class="bi bi-wifi-off"></i> Offline: árvore avançada localmente com os resultados deste dispositivo.
            Será sincronizada automaticamente quando a conexão voltar.
        </div>`;
    }

    function _renderBracketMatch(jogo) {
        const eqs = jogo.equipes || [];
        const isBye = jogo.eh_bye;
        const isConcluido = jogo.status_jogo === 'Concluido' || jogo.status_jogo === 'Finalizado';
        const isIniciado = jogo.status_jogo === 'Iniciado' || jogo.status_jogo === 'Andamento';
        const isPosicao = jogo.eh_disputa_posicao;
        const vencId = jogo.equipe_vencedora_id;
        const jogoData = JSON.stringify(jogo).replace(/'/g, "&#39;").replace(/"/g, "&quot;");

        let cls = 'bkt-match card w-100 overflow-hidden position-relative mb-3';
        if (isConcluido) cls += ' bkt-match--concluido';
        if (isBye) cls += ' bkt-match--bye';
        if (isPosicao) cls += ' bkt-match--posicao';

        let teamsHtml = '';
        const teamBaseCls = 'bkt-team d-flex align-items-center gap-2 py-2 px-3';
        if (eqs.length === 0) {
            teamsHtml = `<div class="${teamBaseCls}"><span class="bkt-team__name text-body-tertiary fst-italic" >A definir</span><span class="bkt-team__score badge rounded-pill text-bg-light fs-6 fw-bold">-</span></div>
                         <div class="${teamBaseCls}"><span class="bkt-team__name text-body-tertiary fst-italic" >A definir</span><span class="bkt-team__score badge rounded-pill text-bg-light fs-6 fw-bold">-</span></div>`;
        } else {
            eqs.forEach(eq => {
                const nome = eq.nome_equipe || eq.nome_fantasia || eq.nome_turma || `Equipe #${eq.id_equipe}`;
                const isWinner = isConcluido && vencId && eq.id_equipe == vencId;
                const isLoser = isConcluido && vencId && eq.id_equipe != vencId && eqs.length > 1;
                let teamCls = teamBaseCls;
                if (isWinner) teamCls += ' bkt-team--winner bg-success-subtle';
                if (isLoser) teamCls += ' bkt-team--loser opacity-50';
                const nameCls = 'bkt-team__name flex-grow-1 text-truncate small fw-medium text-body' + (isWinner ? ' fw-bold text-success-emphasis' : '');
                const trophy = isWinner ? '<span class="bkt-team__trophy text-warning small ms-1"><i class="bi bi-trophy-fill"></i></span>' : '';
                const scoreCls = 'bkt-team__score badge rounded-pill text-bg-light fs-6 fw-bold' + (isWinner ? ' bg-success-subtle text-success-emphasis' : '');
                teamsHtml += `<div class="${teamCls}"><span class="${nameCls}">${nome}</span>${trophy}<span class="${scoreCls}">${eq.gols ?? 0}</span></div>`;
            });
        }

        let statusLabel = jogo.status_jogo || '---';
        let statusCls = 'bkt-match__status badge rounded-pill';
        if (isBye) {
            statusLabel = 'Bye';
            statusCls += ' text-bg-secondary';
        } else if (isConcluido) {
            statusCls += ' text-bg-success';
            statusLabel = 'Finalizado';
        } else if (isIniciado) {
            statusCls += ' text-bg-primary';
            statusLabel = 'Em andamento';
        } else if (jogo.status_jogo === 'Aguardando') {
            statusCls += ' text-bg-warning';
            statusLabel = 'Aguardando';
        } else {
            statusCls += ' text-bg-light border text-body-secondary';
        }

        const nomeModalidade = jogo.nome_modalidade || '';
        const nomeCategoria = jogo.nome_categoria || '';
        const dataJogo = jogo.data_jogo || '';
        const inicioJogo = jogo.inicio_jogo || '';
        const localJogo = jogo.nome_local || '';
        const faseLabel = isPosicao ? (formatFaseFromNome(jogo.nome_jogo) || 'Posição') : (jogo.nome_fase || '');

        let metaParts = [];
        if (faseLabel) metaParts.push(`<i class="bi bi-tag"></i><span>${faseLabel}</span>`);
        if (nomeModalidade) metaParts.push(`<span>${nomeModalidade}</span>`);
        metaParts.push(`<i class="bi bi-calendar3"></i><span>${dataJogo ? dataJogo + (inicioJogo ? ' ' + inicioJogo.substring(0,5) : '') : 'A definir'}</span>`);
        metaParts.push(`<i class="bi bi-geo-alt"></i><span>${localJogo || 'A definir'}</span>`);

        let actionsHtml = '';
        if (!isBye && !isConcluido && jogo.id_jogo) {
            actionsHtml += '<div class="bkt-match__actions d-flex gap-1 px-2 pb-2 justify-content-end">';
            if (jogo.status_jogo === 'Agendado' && jogo.data_jogo && jogo.inicio_jogo && jogo.termino_jogo && jogo.locais_id_local) {
                actionsHtml += `<a href="/jogos/placar?id_jogo=${jogo.id_jogo}" class="btn btn-sm btn-outline-success d-inline-flex align-items-center gap-1" title="Iniciar Jogo"><i class="bi bi-play-fill"></i>Iniciar</a>`;
            }
            actionsHtml += `<button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Editar Jogo" onclick="editarJogoBracket(this)" data-jogo='${jogoData}'><i class="bi bi-pencil"></i>Editar</button>`;
            actionsHtml += '</div>';
        }
        if (isConcluido && jogo.id_jogo) {
            actionsHtml += '<div class="bkt-match__actions d-flex gap-1 px-2 pb-2 justify-content-end">';
            actionsHtml += `<a href="/jogos/placar?id_jogo=${jogo.id_jogo}" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center gap-1" title="Ver resultado"><i class="bi bi-eye"></i>Ver resultado</a>`;
            actionsHtml += `<button class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1" title="Editar Jogo" data-jogo='${JSON.stringify(jogo).replace(/'/g, "&#39;")}' onclick="editarJogoBracket(this)"><i class="bi bi-pencil"></i>Editar</button>`;
            actionsHtml += '</div>';
        }

        return `<div class="${cls}" data-jogo-id="${jogo.id_jogo || ''}">
            ${teamsHtml}
            <div class="bkt-match__meta d-flex align-items-center justify-content-between gap-2 px-3 py-2 bg-body-tertiary border-top">
                <div class="bkt-match__info d-flex align-items-center gap-1 flex-grow-1 overflow-hidden small text-body-secondary">${metaParts.join(' ')}</div>
                <span class="${statusCls}">${statusLabel}</span>
            </div>
            ${actionsHtml}
        </div>`;
    }

    function _detectarCampeao(jogos) {
        // O campeão é o vencedor da grande final (MM:2).
        const final = jogos.find(j => j.fase_nivel === 2 && (j.status_jogo === 'Concluido' || j.status_jogo === 'Finalizado'));
        if (!final || !final.equipes || !final.equipe_vencedora_id) return null;
        const winner = final.equipes.find(eq => eq.id_equipe == final.equipe_vencedora_id);
        if (!winner) return null;
        const nome = winner.nome_equipe || winner.nome_fantasia || winner.nome_turma || `Equipe #${winner.id_equipe}`;
        const mod = modalidadesCache.find(m => String(m.id_modalidade) == _currentModalidade);
        const modName = mod ? `${mod.nome_modalidade}${mod.genero_modalidade ? ' ('+mod.genero_modalidade+')' : ''}${mod.nome_categoria ? ' ['+mod.nome_categoria+']' : ''}` : '';
        return {
            nome,
            modalidade: modName
        };
    }

    function _renderModernBracket(jogos) {
        const mmGames = jogos.filter(j => !j.eh_disputa_posicao);
        const posGames = jogos.filter(j => j.eh_disputa_posicao);

        const rounds = {};
        mmGames.forEach(j => {
            const nivel = j.fase_nivel || 0;
            if (!rounds[nivel]) rounds[nivel] = [];
            rounds[nivel].push(j);
        });

        const niveis = Object.keys(rounds).map(Number).sort((a, b) => b - a);
        const labelsFases = computarLabelsFases(niveis);

        const campeao = _detectarCampeao(jogos);

        let html = '<div class="bracket-tree">';

        niveis.forEach((nivel, nivelIdx) => {
            const roundGames = rounds[nivel].sort((a, b) => (a.posicao_na_chave || 0) - (b.posicao_na_chave || 0));
            const matchCount = roundGames.length;

            html += '<div class="bracket-round-col">';
            html += `<div class="bracket-round-header badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle px-3 py-2 mb-4 text-uppercase">${labelsFases[nivel] || formatFase(nivel)}</div>`;

            roundGames.forEach((jogo, idx) => {
                html += _renderBracketMatch(jogo);
            });

            html += '</div>';

            if (nivelIdx < niveis.length - 1) {
                const nextNivel = niveis[nivelIdx + 1];
                const nextCount = rounds[nextNivel]?.length || 1;
                const connectorHeight = matchCount * 140;
                html += '<div class="bkt-connector"></div>';
            }
        });

        if (posGames.length > 0) {
            html += '<div class="bracket-round-col">';
            html += '<div class="bracket-round-header badge rounded-pill bg-danger-subtle text-danger-emphasis border border-danger-subtle px-3 py-2 mb-4 text-uppercase" >Disputas de Posição</div>';
            posGames.forEach(j => {
                html += _renderBracketMatch(j);
            });
            html += '</div>';
        }

        if (campeao) {
            html += '<div class="bkt-connector sgi-u-h-120px" ></div>';
            html += '<div class="bracket-champion-col">';
            html += '<div class="bracket-champion-card card border-warning border-2 bg-warning-subtle shadow-sm text-center p-4 w-100">';
            html += '<div class="bracket-champion-card__icon fs-1 mb-2" aria-hidden="true">🏆</div>';
            html += '<div class="bracket-champion-card__label small text-uppercase fw-bold text-warning-emphasis mb-2">Campeão</div>';
            html += `<div class="bracket-champion-card__name h5 fw-bold text-warning-emphasis lh-sm mb-0">${campeao.nome}</div>`;
            if (campeao.modalidade) html += `<div class="bracket-champion-card__mod small text-warning-emphasis mt-2">${campeao.modalidade}</div>`;
            html += '</div></div>';
        }

        html += '</div>';
        return html;
    }

    function _drawConnectors(container) {
        const tree = container.querySelector('.bracket-tree');
        if (!tree) return;
        const cols = tree.querySelectorAll('.bracket-round-col');
        const connectors = tree.querySelectorAll('.bkt-connector');

        let prevMatches = [];
        cols.forEach((col, colIdx) => {
            if (colIdx === 0) {
                prevMatches = Array.from(col.querySelectorAll('.bkt-match'));
                return;
            }

            const currentMatches = Array.from(col.querySelectorAll('.bkt-match'));
            const connector = connectors[colIdx - 1];
            if (!connector || currentMatches.length === 0 || prevMatches.length === 0) {
                prevMatches = currentMatches;
                return;
            }

            const treeRect = tree.getBoundingClientRect();
            const connRect = connector.getBoundingClientRect();

            let svg = connector.querySelector('svg');
            if (!svg) {
                svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('width', '20');
                svg.style.position = 'absolute';
                svg.style.top = '0';
                svg.style.left = '0';
                svg.style.width = '100%';
                svg.style.height = '100%';
                svg.style.pointerEvents = 'none';
                connector.appendChild(svg);
            }

            const totalWidth = connector.offsetWidth;
            const totalHeight = connector.offsetHeight;
            svg.setAttribute('viewBox', `0 0 ${totalWidth} ${totalHeight}`);
            svg.innerHTML = '';

            const pairsPerParent = Math.max(1, Math.floor(prevMatches.length / currentMatches.length));

            currentMatches.forEach((match, mIdx) => {
                const matchRect = match.getBoundingClientRect();
                const matchY = matchRect.top - treeRect.top + matchRect.height / 2;

                const startIdx = mIdx * pairsPerParent;
                const endIdx = Math.min(startIdx + pairsPerParent, prevMatches.length);

                for (let i = startIdx; i < endIdx; i++) {
                    if (!prevMatches[i]) continue;
                    const prevRect = prevMatches[i].getBoundingClientRect();
                    const prevY = prevRect.top - treeRect.top + prevRect.height / 2;

                    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                    const midX = totalWidth / 2;
                    const d = `M 0 ${prevY - connRect.top + treeRect.top - connRect.top} H ${midX} V ${matchY - connRect.top} H ${totalWidth}`;
                    path.setAttribute('d', d);
                    path.setAttribute('fill', 'none');
                    path.setAttribute('stroke', '#d1d5db');
                    path.setAttribute('stroke-width', '1.5');
                    path.setAttribute('stroke-linecap', 'round');
                    svg.appendChild(path);
                }
            });

            prevMatches = currentMatches;
        });
    }

    let _jogoIndividualCache = null;

    async function editarJogoIndividual(e) {
        if (e) e.preventDefault();
        if (!_jogoIndividualCache) {
            alert('Nenhum jogo registrado para esta modalidade.');
            return;
        }
        const jogo = _jogoIndividualCache;
        _popularModalEdicao(jogo);

        var modalEl = document.getElementById('modalEditarJogo');
        var selectLocal = document.getElementById('editLocalJogo');
        selectLocal.innerHTML = '<option value="">Carregando...</option>';

        fetch('/api/v1/locais?id_interclasse=' + idInterclasse)
            .then(function(r) {
                return r.json();
            })
            .then(function(data) {
                if (_editIdJogo !== jogo.id_jogo) return;
                const locais = data.success && Array.isArray(data.data) ? data.data : [];
                selectLocal.innerHTML = '<option value="">Selecione um local</option>';
                locais.forEach(function(l) {
                    var sel = Number(l.id_local) === Number(jogo.locais_id_local) ? 'selected' : '';
                    selectLocal.innerHTML += '<option value="' + l.id_local + '" ' + sel + '>' + l.nome_local + '</option>';
                });
            })
            .catch(function() {
                if (_editIdJogo !== jogo.id_jogo) return;
                selectLocal.innerHTML = '<option value="">Erro ao carregar locais</option>';
            });

        var modal = bootstrap.Modal.getInstance(modalEl);
        if (!modal) modal = new bootstrap.Modal(modalEl);
        modal.show();
    }

    async function carregarArvore(idModalidade) {
        const area = document.getElementById('bracketArea');
        const areaMob = document.getElementById('bracketAreaMob');
        const linkArvore = document.getElementById('linkVerArvore');
        const timeline = document.getElementById('faseTimeline');

        if (linkArvore) linkArvore.classList.add('d-none');
        timeline.classList.add('d-none');

        if (!idModalidade) {
            const emptyHtml = `
                <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                    <div class="display-1 text-body-tertiary mb-4" >
                        <svg width="80" height="80" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="8" y="12" width="20" height="14" rx="3" stroke="#d1d5db" stroke-width="2" fill="#f9fafb"/>
                            <rect x="8" y="54" width="20" height="14" rx="3" stroke="#d1d5db" stroke-width="2" fill="#f9fafb"/>
                            <rect x="52" y="33" width="20" height="14" rx="3" stroke="#e30613" stroke-width="2" fill="#fef2f2"/>
                            <path d="M28 19 H40 V40 H52" stroke="#d1d5db" stroke-width="1.5" fill="none"/>
                            <path d="M28 61 H40 V40 H52" stroke="#d1d5db" stroke-width="1.5" fill="none"/>
                        </svg>
                    </div>
                    <div class="h4 fw-bold text-body mb-2" >Nenhum chaveamento gerado</div>
                    <div class="small text-body-secondary mb-4" >Selecione uma modalidade acima para gerar automaticamente o chaveamento do torneio.</div>
                    ${pageConfig.value0 ? `
                    <button class="btn btn-primary d-inline-flex align-items-center gap-2" onclick="kvs_focus('selectModalidade');" >
                        <i class="bi bi-diagram-3-fill"></i> Gerar Chaveamento
                    </button>
                    ` : ``}
                </div>`;
            area.innerHTML = emptyHtml;
            if (areaMob) areaMob.innerHTML = emptyHtml;
            return;
        }

        _currentModalidade = idModalidade;

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const isIndividual = modalidadeEhIndividual(mod);

        if (isIndividual) {
            const loadingHtml = `
                <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                    <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Carregando...</span></div>
                    <div class="small text-body-secondary">Carregando ranking individual...</div>
                </div>`;
            area.innerHTML = loadingHtml;
            if (areaMob) areaMob.innerHTML = loadingHtml;

            try {
                const resRank = await fetch(`/api/v1/chaveamentos?tipo_modalidade=individual&acao=ranking&id_modalidade=${idModalidade}`);
                const dadosRank = await resRank.json();
                const rankingAtual = (dadosRank.success && dadosRank.ranking) ? dadosRank.ranking : [];
                const jogoIndividual = dadosRank.jogo || null;

                let rankingDisplay = '';
                if (rankingAtual.length > 0) {
                    const posLabels = ['🥇 1º Lugar', '🥈 2º Lugar', '🥉 3º Lugar'];
                    const posBg = ['border-warning bg-warning-subtle', 'border-secondary bg-secondary-subtle', 'border-warning bg-warning-subtle'];
                    rankingDisplay = '<div class="row row-cols-1 row-cols-sm-3 g-3 mt-3" >';
                    rankingAtual.forEach((r, idx) => {
                        const nome = esc(r.nome_usuario || 'Desconhecido');
                        const turma = esc(r.nome_fantasia_turma || r.nome_turma || '');
                        rankingDisplay += `
                            <div class="col"><article class="card h-100 text-center p-4 shadow-sm border-2 ${posBg[idx] || 'border-light bg-body-tertiary'}">
                                <div class="fs-2 mb-2" aria-hidden="true">${idx === 0 ? '🥇' : idx === 1 ? '🥈' : '🥉'}</div>
                                <div class="small text-uppercase fw-semibold text-body-secondary mb-1">${posLabels[idx] || (idx+1)+'º Lugar'}</div>
                                <div class="fw-bold text-body">${nome}</div>
                                <div class="small text-body-secondary mt-1" >${turma}</div>
                            </article></div>`;
                    });
                    rankingDisplay += '</div>';
                } else {
                    rankingDisplay = '<div class="text-center text-body-secondary border rounded-3 bg-body-tertiary p-4" ><div class="display-6 mb-2"><i class="bi bi-award"></i></div><div class="fw-semibold">Nenhum ranking registrado</div><div class="small mt-1">Registre os colocados (1º, 2º e 3º lugar) na página do jogo.</div></div>';
                }

                const individualHtml = `
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="p-4 border-bottom d-flex justify-content-between align-items-center" >
                            <div class="h5 fw-bold text-body mb-0"><i class="bi bi-award-fill text-danger me-2"></i>Ranking Atual</div>
                            ${jogoIndividual ? `
                            <div class="dropdown">
                                <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Mais opções">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-sm" >
                                    <li><a class="dropdown-item small d-flex align-items-center gap-2" href="#" onclick="editarJogoIndividual(event)" ><i class="bi bi-pencil"></i> Editar Jogo</a></li>
                                </ul>
                            </div>` : ''}
                        </div>
                        <div class="p-4">
                            ${rankingDisplay}
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 d-flex align-items-center gap-2" >
                        <i class="bi bi-info-circle"></i> Os colocados (1º, 2º e 3º lugar) são registrados na página do jogo.
                    </div>`;

                _jogoIndividualCache = jogoIndividual;
                area.innerHTML = individualHtml;
                if (areaMob) areaMob.innerHTML = individualHtml;

            } catch (e) {
                console.error("Erro ao carregar ranking individual:", e);
                const errHtml = `<div class="card border-0 shadow-sm rounded-4 text-center p-5"><div class="display-5 text-warning mb-3"><i class="bi bi-exclamation-triangle"></i></div><div class="h5 fw-bold text-body mb-2">Erro</div><div class="small text-body-secondary">Erro ao carregar dados da modalidade individual.</div></div>`;
                area.innerHTML = errHtml;
                if (areaMob) areaMob.innerHTML = errHtml;
            }
            return;
        }

        const loadingHtml = `
            <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                <div class="spinner-border text-primary mb-3" role="status"><span class="visually-hidden">Carregando...</span></div>
                <div class="small text-body-secondary">Carregando chaveamento...</div>
            </div>`;
        area.innerHTML = loadingHtml;
        if (areaMob) areaMob.innerHTML = loadingHtml;

        try {
            /* Camada híbrida: 1º tenta a API PHP; sem conexão, usa o snapshot
               do IndexedDB e processa o avanço da árvore no frontend com os
               resultados gravados no banco JS temporário. */
            const resultado = await SGIChaveamento.carregarArvore(idModalidade);
            const jogos = resultado.jogos || [];
            _fonteDadosAtual = resultado.fonte;
            _lastBracketData = jogos;

            if (jogos.length === 0) {
                const emptyHtml = `
                    <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                        <div class="display-5 text-body-tertiary mb-3"><i class="bi bi-diagram-3"></i></div>
                        <div class="h5 fw-bold text-body mb-2">Nenhum chaveamento gerado</div>
                        <div class="small text-body-secondary">Clique em "Gerar Chaveamento" para criar o chaveamento desta modalidade.</div>
                    </div>`;
                area.innerHTML = emptyHtml;
                if (areaMob) areaMob.innerHTML = emptyHtml;
                return;
            }

            const modernHtml = _renderModernBracket(jogos) + (_fonteDadosAtual !== 'remota' ? _badgeFonteLocal() : '');
            area.innerHTML = modernHtml;
            if (areaMob) areaMob.innerHTML = modernHtml;

            requestAnimationFrame(() => {
                _drawConnectors(area);
                _drawConnectors(areaMob);
            });

            const niveis = [...new Set(jogos.filter(j => !j.eh_disputa_posicao).map(j => j.fase_nivel))].sort((a, b) => b - a);
            atualizarTimeline(niveis);
            iniciarPolling();

        } catch (e) {
            console.error("Erro ao carregar árvore:", e);
            const errHtml = `<div class="card border-0 shadow-sm rounded-4 text-center p-5"><div class="display-5 text-warning mb-3"><i class="bi bi-exclamation-triangle"></i></div><div class="h5 fw-bold text-body mb-2">Erro de conexão</div><div class="small text-body-secondary">Não foi possível conectar ao servidor.</div></div>`;
            area.innerHTML = errHtml;
            if (areaMob) areaMob.innerHTML = errHtml;
        }
    }

    /* ── Polling for Real-time Updates ── */
    function iniciarPolling() {
        if (_pollingTimer) clearInterval(_pollingTimer);
        _pollingTimer = setInterval(async () => {
            if (!_currentModalidade) return;
            try {
                /* Mesma camada híbrida do carregamento inicial: online consulta
                   o PHP; offline recalcula a árvore a partir do banco JS local. */
                const resultado = await SGIChaveamento.carregarArvore(_currentModalidade);
                if (!pageScope.active) return;
                const jogos = resultado.jogos || [];
                if (!jogos.length) return;

                const oldStatuses = (_lastBracketData || []).map(j => `${j.id_jogo}:${j.status_jogo}`).join(',');
                const newStatuses = jogos.map(j => `${j.id_jogo}:${j.status_jogo}`).join(',');

                if (oldStatuses !== newStatuses || _fonteDadosAtual !== resultado.fonte) {
                    _fonteDadosAtual = resultado.fonte;
                    _lastBracketData = jogos;
                    const modernHtml = _renderModernBracket(jogos) + (_fonteDadosAtual !== 'remota' ? _badgeFonteLocal() : '');
                    const area = document.getElementById('bracketArea');
                    const areaMob = document.getElementById('bracketAreaMob');
                    if (area) area.innerHTML = modernHtml;
                    if (areaMob) areaMob.innerHTML = modernHtml;
                    requestAnimationFrame(() => {
                        _drawConnectors(area);
                        _drawConnectors(areaMob);
                    });
                    carregarJogos();
                }
            } catch (e) {
                /* silent */ }
        }, 8000);
    }

    /* Sincronização de volta ao PHP: quando a conexão retorna e a árvore exibida
       foi calculada localmente, envia os dados pendentes do banco JS temporário
       (a fila reproduz os POSTs originais e o servidor refaz o avanço). */
    if (window.SGIOffline && typeof window.SGIOffline.onStateChange === 'function') {
        window.SGIOffline.onStateChange(function (estado) {
            if (!estado.online) return;
            if (_fonteDadosAtual && _fonteDadosAtual !== 'remota') {
                SGIChaveamento.sincronizar().catch(function () { /* noop */ }).then(function () {
                    if (_currentModalidade) carregarArvore(_currentModalidade);
                });
            } else if (SGIOffline.hasPending()) {
                SGIChaveamento.sincronizar();
            }
        });
    }

    function pararPolling() {
        if (_pollingTimer) {
            clearInterval(_pollingTimer);
            _pollingTimer = null;
        }
    }

    const selectDesk = document.getElementById('selectModalidade');
    if (selectDesk) pageScope.listen(selectDesk, 'change', function() {
        document.getElementById('msgChaveamento').innerHTML = '';
        document.getElementById('faseTimeline').classList.add('d-none');
        pararPolling();
        carregarArvore(this.value);
    });

    const selectMob = document.getElementById('selectModalidadeMob');
    if (selectMob) pageScope.listen(selectMob, 'change', function() {
        const msgMob = document.getElementById('msgChaveamentoMob');
        if (msgMob) msgMob.classList.add('d-none');
        pararPolling();
        carregarArvore(this.value);
    });

    pageScope.listen(document.getElementById('filtroModalidadeJogos'), 'change', carregarJogos);
    pageScope.listen(document.getElementById('filtroCategoriaJogos'), 'change', carregarJogos);

    pageScope.listen(document.getElementById('filtroModalidadeJogosMob'), 'change', carregarJogos);
    pageScope.listen(document.getElementById('filtroCategoriaJogosMob'), 'change', carregarJogos);

    const btnGerarDesk = document.getElementById('btnGerarChaveamento');
    if (btnGerarDesk) pageScope.listen(btnGerarDesk, 'click', async function() {
        await gerarChaveamento(this, 'msgChaveamento');
    });

    const btnGerarMob = document.getElementById('btnGerarChaveamentoMob');
    if (btnGerarMob) pageScope.listen(btnGerarMob, 'click', async function() {
        const msgEl = document.getElementById('msgChaveamentoMob');
        await gerarChaveamento(this, 'msgChaveamentoMob');
    });

    async function gerarChaveamento(btnEl, msgId) {
        const msgEl = document.getElementById(msgId);
        const btn = btnEl;
        if (msgEl) msgEl.classList.remove('d-none');

        if (NIVEL_USUARIO === 2 || NIVEL_USUARIO === 3) {
            if (msgEl) msgEl.innerHTML = '<div class="alert alert-danger">Você não tem permissão para gerar chaveamento.</div>';
            return;
        }

        const kvsMob = document.getElementById('kvs-wrap-selectModalidadeMob');
        const idModalidade = (kvsMob && kvsMob.offsetParent !== null)
            ? document.getElementById('selectModalidadeMob').value
            : document.getElementById('selectModalidade').value;

        if (!idModalidade) {
            msgEl.innerHTML = '<div class="alert alert-danger">Selecione uma modalidade primeiro.</div>';
            return;
        }

        const mod = modalidadesCache.find(m => String(m.id_modalidade) === idModalidade);
        const isIndividual = modalidadeEhIndividual(mod);
        const tipoModalidadeParam = isIndividual ? 'individual' : 'mata_mata';

        msgEl.innerHTML = '<div class="alert alert-info">' + (isIndividual ? 'Gerando jogo da modalidade para a agenda...' : 'Gerando chaveamento...') + '</div>';

        try {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Gerando...';
            const resp = await fetch('/api/v1/chaveamentos', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id_modalidade: Number(idModalidade), tipo_modalidade: tipoModalidadeParam, acao: 'gerar' })
            });
            const data = await resp.json();

            if (data.success === false) throw new Error(data.message || 'Erro ao gerar chaveamento.');

            const msgDet = data.jogos_criados ? ` (${data.jogos_criados} jogo(s) gerado(s))` : '';
            msgEl.innerHTML = `<div class="alert alert-success">${data.message}${msgDet}.</div>`;
            const linkArvore = document.getElementById('linkVerArvore');
            if (linkArvore) linkArvore.classList.remove('d-none');
            const btnArvore = document.getElementById('btnVerArvore');
            if (btnArvore) btnArvore.href = `/chaveamento?id=${idInterclasse}`;
            carregarArvore(idModalidade);
            carregarJogos();
        } catch (err) {
            msgEl.innerHTML = `<div class="alert alert-danger">${err.message}</div>`;
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-diagram-3-fill me-1"></i> Gerar Chaveamento';
        }
    }

    pageScope.listen(document.getElementById('inputBuscaJogo'), 'keyup', function() {
        var termo = this.value.toLowerCase().trim();
        var linhas = document.querySelectorAll('#tbodyJogos tr');
        linhas.forEach(function(tr) {
            if (termo === '') {
                tr.classList.remove('d-none');
                return;
            }
            var texto = tr.textContent.toLowerCase();
            if (texto.indexOf(termo) !== -1) {
                tr.classList.remove('d-none');
            } else {
                tr.classList.add('d-none');
            }
        });
    });

    pageScope.listen(document.getElementById('inputBuscaJogoMob'), 'keyup', function() {
        var termo = this.value.toLowerCase().trim();
        var linhas = document.querySelectorAll('#tbodyJogosMob tr');
        linhas.forEach(function(tr) {
            if (termo === '') {
                tr.classList.remove('d-none');
                return;
            }
            var texto = tr.textContent.toLowerCase();
            if (texto.indexOf(termo) !== -1) {
                tr.classList.remove('d-none');
            } else {
                tr.classList.add('d-none');
            }
        });
    });

    async function iniciarChaveamento() {
        const idOk = await resolverInterclasse();
        if (!idOk) return;
        await carregarModalidades();
        await carregarCategorias();
        await carregarJogos();
    }

    iniciarChaveamento();

    pageScope.onDeactivate(pararPolling);
    window.SGIPage.ready(function () { if (_currentModalidade) iniciarPolling(); });
    pageScope.listen(window, 'beforeunload', pararPolling);

return {esc, kvs_montarGrupos, kvs_sincronizar, kvs_montar, kvs_focus, resolverInterclasse, atualizarStats, atualizarTimeline, carregarModalidades, carregarCategorias, formatarNomePartida, _popularModalEdicao, editarJogo, editarJogoBracket, salvarEdicaoJogo, formatarDuracaoJogo, formatarAcrescimosJogo, renderizarLinhaJogo, carregarJogos, formatFase, computarLabelsFases, formatFaseFromNome, _badgeFonteLocal, _renderBracketMatch, _detectarCampeao, _renderModernBracket, _drawConnectors, editarJogoIndividual, carregarArvore, iniciarPolling, pararPolling, gerarChaveamento, iniciarChaveamento};
});
