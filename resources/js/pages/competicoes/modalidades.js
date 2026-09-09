window.SGIPage.mount("competicoes/modalidades", function (pageConfig, pageScope) {

    const nivelUsuario = pageConfig.value2;
    let idInterclasse = null;

    async function carregarModalidades() {
        const divMobile = document.getElementById('listaModalidadesMobile');
        const divDesktop = document.getElementById('listaModalidadesDesktop');

        try {
            const response = await axios.get('/api/v1/modalidades?x=1');
            let modalidades = response.data.data || response.data;
            if (!Array.isArray(modalidades)) modalidades = [];
            modalidades = modalidades.filter((item) => String(item.interclasses_id_interclasse) === String(idInterclasse));

            if (divMobile) divMobile.innerHTML = '';
            if (divDesktop) divDesktop.innerHTML = '';

            if (!Array.isArray(modalidades) || modalidades.length === 0) {
                const msgVazia = '<p class="text-muted mt-4 text-center w-100">Nenhuma modalidade encontrada.</p>';
                if (divMobile) divMobile.innerHTML = msgVazia;
                if (divDesktop) divDesktop.innerHTML = msgVazia;
                return;
            }

            const modalidadesPorCategoria = {};
            modalidades.forEach((modalidade) => {
                const categoria = modalidade.nome_categoria || 'Sem Categoria';
                if (!modalidadesPorCategoria[categoria]) {
                    modalidadesPorCategoria[categoria] = [];
                }
                modalidadesPorCategoria[categoria].push(modalidade);
            });

            Object.keys(modalidadesPorCategoria).forEach((categoria) => {
                const mods = modalidadesPorCategoria[categoria];

                if (divMobile) {
                    divMobile.innerHTML += '<h5 class="mt-4 mb-3 text-muted px-3">' + esc(categoria) + '</h5>';
                    mods.forEach((modalidade) => {
                        const botoesAdmin = nivelUsuario === 0
                            ? '<div class="d-flex gap-1 ms-2">'
                                + '<a class="btn btn-sm btn-outline-primary" href="/modalidades/detalhes?id=' + idInterclasse + '&id_modalidade=' + modalidade.id_modalidade + '" title="Editar"><i class="bi bi-pencil"></i></a>'
                                + '<button class="btn btn-sm btn-outline-danger" onclick="excluirModalidade(' + modalidade.id_modalidade + ')" title="Excluir"><i class="bi bi-trash"></i></button>'
                                + '</div>'
                            : '';
                        divMobile.innerHTML +=
                            '<div class="bg-white d-flex align-items-center shadow py-3 px-4 mb-3 border border-1 rounded-3 w-100 sgi-inline-d29e2b0b" >'
                                + '<i class="bi bi-trophy fs-4"></i>'
                                + '<div class="text-start px-3 w-100">'
                                    + '<h2 class="m-0 fs-5 text-truncate">' + esc(modalidade.nome_modalidade) + '</h2>'
                                + '</div>'
                                + botoesAdmin
                            + '</div>';
                    });
                }

                if (divDesktop) {
                    divDesktop.innerHTML += '<h4 class="mt-4 mb-3 text-muted">' + esc(categoria) + '</h4><div class="row g-4">';
                    mods.forEach((modalidade) => {
                        const botoesAdmin = nivelUsuario === 0
                            ? '<div class="d-flex gap-1 ms-2">'
                                + '<a class="btn btn-sm btn-outline-primary" href="/modalidades/detalhes?id=' + idInterclasse + '&id_modalidade=' + modalidade.id_modalidade + '" title="Editar"><i class="bi bi-pencil"></i></a>'
                                + '<button class="btn btn-sm btn-outline-danger" onclick="excluirModalidade(' + modalidade.id_modalidade + ')" title="Excluir"><i class="bi bi-trash"></i></button>'
                                + '</div>'
                            : '';
                        divDesktop.innerHTML +=
                            '<div class="col-12 col-md-6 col-lg-4">'
                                + '<div class="card border border-light-subtle shadow-sm h-100 py-4 px-4 d-flex flex-row align-items-center sgi-inline-f70b441c" >'
                                    + '<div class="d-flex align-items-center gap-3 flex-grow-1">'
                                        + '<i class="bi bi-trophy fs-4 text-dark"></i>'
                                        + '<div>'
                                            + '<h5 class="m-0 fw-bold fs-6">' + esc(modalidade.nome_modalidade) + '</h5>'
                                        + '</div>'
                                    + '</div>'
                                    + botoesAdmin
                                + '</div>'
                            + '</div>';
                    });
                    divDesktop.innerHTML += '</div>';
                }
            });
        } catch (error) {
            console.error('Erro ao carregar lista:', error);
        }
    }

    async function carregarTiposModalidades() {
        const selectTipo = document.getElementById('inputTipoModalidade');
        if (!selectTipo) return;

        try {
            const response = await axios.get('/api/v1/tipos-modalidade');
            const tipos = response.data;

            selectTipo.innerHTML = '<option value="" disabled selected>Selecione um tipo...</option>';
            tipos.forEach(tipo => {
                selectTipo.innerHTML += '<option value="' + esc(tipo.id_tipo_modalidade) + '">' + esc(tipo.nome_tipo_modalidade) + '</option>';
            });
        } catch (error) {
            console.error('Erro ao carregar tipos:', error);
            selectTipo.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    async function carregarCategoriasModalidades() {
        const selectCat = document.getElementById('inputCategoriaModalidade');
        if (!selectCat) return;

        try {
            const response = await axios.get('/api/v1/categorias?id_interclasse=' + idInterclasse);
            const categorias = response.data;

            selectCat.innerHTML = '<option value="" disabled selected>Selecione uma categoria...</option>';
            categorias.forEach((cat) => {
                selectCat.innerHTML += '<option value="' + esc(cat.id_categoria) + '">' + esc(cat.nome_categoria) + '</option>';
            });
        } catch (error) {
            console.error('Erro ao carregar categorias:', error);
            selectCat.innerHTML = '<option value="" disabled selected>Erro ao carregar</option>';
        }
    }

    async function excluirModalidade(id) {
        if (!confirm('Tem certeza que deseja excluir esta modalidade?')) return;

        try {
            const res = await axios.put('/api/v1/modalidades', {
                id_modalidade: id,
                status_modalidade: '0'
            });
            if (res.data.success) {
                carregarModalidades();
            } else {
                alert('Erro ao excluir modalidade.');
            }
        } catch (error) {
            alert('Erro ao excluir modalidade.');
            console.error(error);
        }
    }

    pageScope.listen(document.getElementById('formNovaModalidade'), 'submit', async (e) => {
        e.preventDefault();
        const btnSalvar = document.getElementById('btnSalvarModalidade');
        const caixaMensagem = document.getElementById('caixaMensagemModalidade');

        const dados = {
            interclasses_id_interclasse: parseInt(idInterclasse),
            nome_modalidade: document.getElementById('inputNomeModalidade').value.trim(),
            genero_modalidade: document.getElementById('inputGeneroModalidade').value,
            tipos_modalidades_id_tipo_modalidade: document.getElementById('inputTipoModalidade').value,
            categorias_id_categoria: document.getElementById('inputCategoriaModalidade').value
        };

        const maxInscritos = document.getElementById('inputMaxInscritos');
        if (maxInscritos) {
            dados.max_inscrito_modalidade = parseInt(maxInscritos.value) || 0;
        }

        try {
            btnSalvar.disabled = true;
            btnSalvar.innerHTML = 'Salvando...';
            const res = await axios.post('/api/v1/modalidades', dados);

            if (res.data.success) {
                caixaMensagem.innerHTML = '<p class="text-success text-center fw-bold">Criada com sucesso!</p>';
                document.getElementById('formNovaModalidade').reset();
                carregarModalidades();
                setTimeout(() => {
                    bootstrap.Modal.getInstance(document.getElementById('modalCriarModalidade')).hide();
                    caixaMensagem.innerHTML = '';
                }, 1000);
            }
        } catch (error) {
            caixaMensagem.innerHTML = '<p class="text-danger text-center fw-bold">Erro ao salvar.</p>';
        } finally {
            btnSalvar.disabled = false;
            btnSalvar.innerHTML = 'Criar';
        }
    });

    window.SGIPage.ready( async () => {
        idInterclasse = await window.SGIInterclasse.resolveId();
        if (!idInterclasse) {
            alert('Nenhum interclasse ativo encontrado.');
            window.location.href = '/edicoes';
            return;
        }
        const ic = await window.SGIInterclasse.getInterclasseById(idInterclasse);
        const nomeEl = document.getElementById('nomeInterclasseModalidades');
        if (nomeEl) nomeEl.innerText = ic?.nome_interclasse || 'Interclasse';
        const btnEl = document.getElementById('btnVoltarModalidades');
        if (btnEl) btnEl.href = `/painel?id=${idInterclasse}`;
        await Promise.all([
            carregarModalidades(),
            carregarTiposModalidades(),
            carregarCategoriasModalidades()
        ]);
    });

return {carregarModalidades, carregarTiposModalidades, carregarCategoriasModalidades, excluirModalidade};
});
