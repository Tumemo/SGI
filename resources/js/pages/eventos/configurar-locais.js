window.SGIPage.mount("eventos/configurar-locais", function (pageConfig, pageScope) {

    const APP_BASE = window.SGI_BASE_PATH || '';
    const API = (window.SGI_API_BASE || '/api/v1/').replace(/\/?$/, '/');
    const params = new URLSearchParams(window.location.search);
    let idInterclasse = params.get('id');

    // Função para buscar o ID do Interclasse Ativo caso não exista parâmetro na URL
    async function obterInterclasseAtivo() {
        if (idInterclasse) return idInterclasse;

        try {
            // Consulta a edição ativa pela API versionada.
            const res = await fetch(`${API}edicoes?status_interclasse=1`);
            const data = await res.json();

            const ativo = Array.isArray(data) ? data[0] : data;
            if (ativo && ativo.id_interclasse) {
                idInterclasse = ativo.id_interclasse;

                const btnVoltarDesk = document.getElementById('btnVoltarLocaisDesk');
                if (btnVoltarDesk) btnVoltarDesk.href = `/painel?id=${idInterclasse}`;
                if (ativo.nome_interclasse) {
                    ['nomeInterclasseLocais'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) el.textContent = ativo.nome_interclasse;
                    });
                }

                return idInterclasse;
            }
        } catch (e) {
            console.error('Erro ao obter interclasse ativo:', e);
        }
        return null;
    }

    if (idInterclasse) {
        ['btnVoltarLocaisDesk'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.href = `/painel?id=${idInterclasse}`;
        });
    }

    function esc(s) {
        const d = document.createElement('div');
        d.textContent = s == null ? '' : String(s);
        return d.innerHTML;
    }

    async function lerRespostaJson(response) {
        const body = await response.text();
        if (body.trim() === '') return {};

        try {
            return JSON.parse(body);
        } catch (_) {
            // PHP pode acrescentar um aviso HTML antes do JSON quando o
            // diretório temporário de upload está mal configurado. Recupera
            // o envelope para não transformar uma gravação concluída em erro.
            const inicio = body.indexOf('{');
            const fim = body.lastIndexOf('}');
            if (inicio >= 0 && fim > inicio) {
                try {
                    return JSON.parse(body.slice(inicio, fim + 1));
                } catch (_) {
                    // A resposta realmente não contém um envelope JSON.
                }
            }
            throw new Error(response.ok
                ? 'O servidor concluiu o envio, mas retornou uma resposta inválida.'
                : 'O servidor não conseguiu processar o regulamento.');
        }
    }

    // --- LÓGICA DO REGULAMENTO ---
    async function carregarRegulamento() {
        if (!idInterclasse) await obterInterclasseAtivo();
        if (!idInterclasse) return;

        try {
            // Consulta a edição selecionada pela API versionada.
            const res = await fetch(`${API}edicoes?id_interclasse=${idInterclasse}&regulamento=true`);
            const data = await res.json();

            const item = Array.isArray(data) ? data[0] : data;

            const pdfName = item?.regulamento_interclasse;
            const infoDesk = document.getElementById('infoRegulamentoDesk');
            const btnVerDesk = document.getElementById('btnVerPdfDesk');

            if (pdfName && pdfName.trim() !== '') {
                const pdfUrl = `${APP_BASE}/uploads/regulamentos/${encodeURIComponent(pdfName)}`;

                if (infoDesk) infoDesk.textContent = 'O regulamento em PDF está atualizado e disponível para consulta.';

                if (btnVerDesk) {
                    btnVerDesk.href = pdfUrl;
                    btnVerDesk.classList.remove('d-none');
                }
            } else {
                if (infoDesk) infoDesk.textContent = 'Nenhum arquivo de regulamento foi enviado até o momento.';
                if (btnVerDesk) btnVerDesk.classList.add('d-none');
            }
        } catch (e) {
            console.error('Erro ao buscar regulamento:', e);
        }
    }

    function cardLocal(loc) {
        const isDisponivel = Number(loc.disponivel_local) === 1;
        const disp = isDisponivel ? 'Disponível' : 'Indisponível';
        const carga = loc.carga_local != null && loc.carga_local !== '' ? `Capacidade: ${esc(loc.carga_local)}` : 'Capacidade não informada';
        return `
            <div class="col-12 col-md-6">
                <div class="card border-0 shadow-sm p-4 h-100 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                        <h5 class="fw-bold text-dark mb-0 text-truncate" title="${esc(loc.nome_local)}">${esc(loc.nome_local)}</h5>
                        <span class="badge rounded-pill border ${isDisponivel ? 'text-success border-success' : 'text-secondary border-secondary'}">${disp}</span>
                    </div>
                    <p class="text-muted small mb-3 mt-auto">${carga}</p>
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" 
                                class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center justify-content-center"
                                title="Editar local"
                                data-bs-toggle="modal" 
                                data-bs-target="#modalEditarLocal"
                                data-id="${loc.id_local}" 
                                data-nome="${esc(loc.nome_local)}" 
                                data-disponivel="${isDisponivel ? '1' : '0'}"
                                data-carga="${loc.carga_local || ''}">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center"
                                title="Excluir local"
                                onclick='excluirLocal(${loc.id_local}, "${esc(loc.nome_local)}")'>
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>`;
    }

    window.excluirLocal = async function(idLocal, nomeLocal) {
        if (!confirm(`Deseja excluir o local "${nomeLocal}"?\nEsta ação não pode ser desfeita.`)) {
            return;
        }
        try {
            const res = await fetch(`${API}locais?id_local=${parseInt(idLocal)}`, {
                method: 'DELETE'
            });
            const data = await res.json();
            if (!res.ok || data.success === false) {
                throw new Error(data.message || 'Não foi possível excluir o local.');
            }
            await carregarLocais();
        } catch (error) {
            alert(error.message);
        }
    };

    async function carregarLocais() {
        const desk = document.getElementById('listaLocaisDesktop');

        if (!idInterclasse) await obterInterclasseAtivo();

        try {
            // Envia o id_interclasse na Query String para filtrar só os do interclasse atual
            const q = idInterclasse ? `?id_interclasse=${encodeURIComponent(idInterclasse)}` : '';
            const res = await fetch(`${API}locais${q}`);
            const data = await res.json();
            const lista = (data && Array.isArray(data.data)) ? data.data : [];

            if (lista.length === 0) {
                const msg = '<p class="text-muted text-center w-100 mb-0">Nenhum local cadastrado. Toque em &quot;Novo local&quot;.</p>';
                desk.innerHTML = `<div class="col-12">${msg}</div>`;
                return;
            }
            desk.innerHTML = lista.map(cardLocal).join('');
        } catch (e) {
            console.error(e);
            desk.innerHTML = '<p class="text-danger">Erro ao carregar locais.</p>';
        }
    }

    window.SGIPage.ready( async () => {
        if (!idInterclasse) {
            await obterInterclasseAtivo();
        }

        if (idInterclasse) {
            try {
                if (window.SGIInterclasse?.getInterclasseById) {
                    const d = await window.SGIInterclasse.getInterclasseById(idInterclasse);
                        if (d?.nome_interclasse) {
                            ['nomeInterclasseLocais'].forEach(id => {
                                const el = document.getElementById(id);
                                if (el) el.textContent = d.nome_interclasse;
                            });
                            window.SGIInterclasse.updatePageTitle(d.nome_interclasse);
                        }
                }
            } catch (_) {
                /* ok */
            }
        }

        await carregarLocais();
        await carregarRegulamento();

        // Envio do FORMULÁRIO REGULAMENTO
        // Envio do FORMULÁRIO REGULAMENTO (Popup de Sucesso)
        pageScope.listen(document.getElementById('formRegulamento'), 'submit', async (e) => {
            e.preventDefault();
            const msg = document.getElementById('msgRegulamento');
            const btn = document.getElementById('btnSalvarRegulamento');
            const fileInput = document.getElementById('pdf_regulamento');
            const modalEl = document.getElementById('modalRegulamento');

            if (!idInterclasse) {
                await obterInterclasseAtivo();
            }

            if (!idInterclasse) {
                alert('Nenhuma edição do interclasse encontrada ou ativa.');
                return;
            }

            if (!fileInput.files || fileInput.files.length === 0) {
                alert('Por favor, selecione um arquivo PDF.');
                return;
            }

            const formData = new FormData();
            formData.append('pdf_regulamento', fileInput.files[0]);

            msg.textContent = '';
            btn.disabled = true;

            try {
                const res = await fetch(`${API}edicoes?id=${idInterclasse}`, {
                    method: 'POST',
                    body: formData
                });

                const js = await lerRespostaJson(res);
                if (!res.ok || js.success === false) {
                    throw new Error(js.message || 'Falha ao salvar o regulamento.');
                }

                // 1. Fecha o modal
                bootstrap.Modal.getInstance(modalEl)?.hide();

                // 2. Limpa o input de arquivo
                document.getElementById('formRegulamento').reset();

                // 3. Exibe o Popup de Sucesso 🎉
                alert('Regulamento enviado e atualizado com sucesso! :)');

                // 4. Recarrega as informações na tela
                await carregarRegulamento();

            } catch (err) {
                alert(err.message || 'Erro no envio do regulamento.');
            } finally {
                btn.disabled = false;
            }
        });;

        // Envio do formulário NOVO LOCAL
        pageScope.listen(document.getElementById('formNovoLocal'), 'submit', async (e) => {
            e.preventDefault();
            const nome = document.getElementById('inputNomeLocal').value.trim();
            const disponivel = document.getElementById('selectDisponivelLocal').value;
            const cargaVal = document.getElementById('inputCargaLocal').value;
            const carga = cargaVal === '' ? null : parseInt(cargaVal, 10);
            const msg = document.getElementById('msgNovoLocal');
            const btn = document.getElementById('btnSalvarLocal');
            const modalEl = document.getElementById('modalNovoLocal');

            msg.textContent = '';
            btn.disabled = true;
            try {
                if (!idInterclasse) {
                    await obterInterclasseAtivo();
                }
                if (!idInterclasse) {
                    throw new Error('Nenhuma edição do interclasse selecionada/ativa.');
                }
                const body = {
                    nome_local: nome,
                    disponivel_local: parseInt(disponivel, 10),
                    interclasses_id_interclasse: parseInt(idInterclasse, 10)
                };
                if (carga != null && !Number.isNaN(carga)) body.carga_local = carga;

                const res = await fetch(`${API}locais`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(body)
                });
                const js = await res.json();
                if (!res.ok || js.success === false) throw new Error(js.message || 'Não foi possível salvar.');

                bootstrap.Modal.getInstance(modalEl)?.hide();
                document.getElementById('formNovoLocal').reset();
                await carregarLocais();
            } catch (err) {
                msg.textContent = err.message || 'Erro.';
                msg.className = 'small text-center mb-2 text-danger';
            } finally {
                btn.disabled = false;
            }
        });

        // Configuração ao abrir o Modal EDITAR LOCAL
        const modalEditar = document.getElementById('modalEditarLocal');
        if (modalEditar) {
            pageScope.listen(modalEditar, 'show.bs.modal', function(event) {
                const botao = event.relatedTarget;

                const id = botao.getAttribute('data-id');
                const nome = botao.getAttribute('data-nome');
                const disponivel = botao.getAttribute('data-disponivel');
                const carga = botao.getAttribute('data-carga');

                modalEditar.querySelector('#edit-local-id').value = id;
                modalEditar.querySelector('#edit-local-nome').value = nome;
                modalEditar.querySelector('#edit-local-disponivel').value = disponivel;
                modalEditar.querySelector('#edit-local-carga').value = carga;

                document.getElementById('msgEditarLocal').textContent = '';
            });
        }

        // Envio do formulário EDITAR LOCAL
       pageScope.listen(document.getElementById('formEditarLocal'), 'submit', async function (e) {
    e.preventDefault();
    
    // Garante que temos o ID do interclasse atual antes de enviar
    if (!idInterclasse) {
        await obterInterclasseAtivo();
    }
    
    const id = document.getElementById('edit-local-id').value;
    const nome = document.getElementById('edit-local-nome').value.trim();
    const disponivel = document.getElementById('edit-local-disponivel').value;
    const cargaVal = document.getElementById('edit-local-carga').value;
    const carga = cargaVal === '' ? null : parseInt(cargaVal, 10);
    
    const msg = document.getElementById('msgEditarLocal');
    const btn = document.getElementById('btnAtualizarLocal');
    
    msg.textContent = '';
    btn.disabled = true;
    
    try {
        const body = { 
            id_local: parseInt(id, 10),
            nome_local: nome, 
            disponivel_local: parseInt(disponivel, 10),
            status_local: disponivel,
            interclasses_id_interclasse: parseInt(idInterclasse, 10) // VINCULA AO INTERCLASSE ATUAL
        };
        if (carga != null && !Number.isNaN(carga)) body.carga_local = carga;
        
        const res = await fetch(`${API}locais`, {
            method: 'PUT', 
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(body)
        });
        
        const js = await res.json();
        if (!res.ok || js.success === false) throw new Error(js.message || 'Não foi possível atualizar.');
        
        bootstrap.Modal.getInstance(modalEditar)?.hide();
        await carregarLocais();
    } catch (err) {
        msg.textContent = err.message || 'Erro ao atualizar.';
        msg.className = 'small text-center mb-2 text-danger';
    } finally {
        btn.disabled = false;
    }
});
    });

    // Função de Regulamento usando a rota exata da API já existente
    async function carregarRegulamentoModal() {
        const statusEl = document.getElementById('statusRegulamentoModal');
        const containerPdf = document.getElementById('containerPdfRegulamentoModal');
        const btnPdf = document.getElementById('btnBaixarPdfModal');

        try {
        const res = await fetch(`${API}edicoes?regulamento=true`);
            if (!res.ok) throw new Error('Erro na resposta da API');

            const data = await res.json();
            const lista = Array.isArray(data) ? data : (data?.data || [data]);
            const ativo = lista.find(i => String(i.status_interclasse) === '1') || lista[0];

            const regulamentoPath = ativo?.regulamento_interclasse || ativo?.regulamento;

            if (regulamentoPath && regulamentoPath.trim() !== '') {
                btnPdf.href = `${APP_BASE}/uploads/regulamentos/${encodeURIComponent(regulamentoPath)}`;
                statusEl.classList.add('d-none');
                containerPdf.classList.remove('d-none');
            } else {
                statusEl.textContent = 'Nenhum regulamento disponível no momento.';
                statusEl.className = 'text-muted mb-0 small';
            }
        } catch (error) {
            console.error("Erro ao carregar regulamento:", error);
            statusEl.textContent = 'Erro ao carregar regulamento. Tente novamente mais tarde.';
            statusEl.className = 'text-danger mb-0 small';
        }
    }

    pageScope.listen(document.getElementById('modalTermosColaborador'), 'show.bs.modal', carregarRegulamentoModal);

return {obterInterclasseAtivo, esc, lerRespostaJson, carregarRegulamento, cardLocal, carregarLocais, carregarRegulamentoModal};
});
