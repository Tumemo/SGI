window.SGIPage.mount("eventos/lista", function (pageConfig, pageScope) {


function escaparHTML(string) {
    const mapa = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#x27;' };
    return String(string || '').replace(/[&<>"']/g, (s) => mapa[s]);
}



const estadoInterclasses = { lista: [] };

function anoInterclasse(item) {
    return item.ano_interclasse ? item.ano_interclasse.split('-')[0] : "N/A";
}

function statusAtivo(item) {
    return String(item.status_interclasse) === '1';
}



async function atualizarStatusInterclasse(idInterclasse, ativo) {
    const body = new FormData();
    body.append('status_interclasse', ativo ? '1' : '0');
    const response = await fetch(`/api/v1/edicoes?id=${idInterclasse}`, { method: 'POST', body });
    const raw = await response.text();
    let data;
    try { data = JSON.parse(raw); } catch { throw new Error('Erro no servidor ao atualizar status.'); }
    if (!response.ok || !data.success) throw new Error(data.message || 'Não foi possível atualizar o status.');
    await window.SGIInterclasse.refreshNavigation();
    return data;
}

async function ativarComExclusividade(idParaAtivar) {
    await atualizarStatusInterclasse(idParaAtivar, true);
}

function cardClassStatus(ativo) {
    return ativo ? '' : 'opacity-75';
}


async function listarInterclasses() {
    const listarMobile = document.getElementById('caixaListar');
    const listarDesktop = document.getElementById('listaDesktop');

    try {
        const res = await fetch('/api/v1/edicoes?regulamento=true');
        const data = await res.json();

        if (!Array.isArray(data) || data.length === 0) {
            const msgVazia = '<p class="text-center text-muted mt-5">Nenhum interclasse encontrado</p>';
            listarMobile.innerHTML = msgVazia;
            listarDesktop.innerHTML = msgVazia;
            return;
        }

        const listaOrdenada = [...data].sort((a, b) => Number(b.id_interclasse) - Number(a.id_interclasse));
if (pageConfig.value1) {
        if (typeof estadoInterclasses !== 'undefined') estadoInterclasses.lista = listaOrdenada;
}

        if (pageConfig.value3) {
        // Colaborador e Mesário: mostram apenas o ativo
        const ativo = listaOrdenada.find(item => String(item.status_interclasse) === '1');
        if (!ativo) {
            const msg = '<p class="text-center text-muted mt-5">Nenhum interclasse ativo no momento.</p>';
            listarMobile.innerHTML = msg;
            listarDesktop.innerHTML = msg;
            return;
        }
        var items = [ativo];
        } else {
        var items = listaOrdenada;
        }

        let htmlMobile = '';
        let htmlDesktop = '';

        items.forEach((item) => {
            const anoStr = item.ano_interclasse ? item.ano_interclasse.split('-')[0] : "N/A";
            const ativo = String(item.status_interclasse) === '1';
            const statusBadge = ativo
                ? '<span class="bg-danger rounded-3 text-white px-3 py-1 sgi-inline-93638ebd" >Ativo</span>'
                : '<span class="bg-secondary rounded-3 text-white px-3 py-1 sgi-inline-93638ebd" >Inativo</span>';
            if (pageConfig.value0) {
            var classeCard = ativo ? '' : 'opacity-75';
            } else {
            var classeCard = (pageConfig.value2 ? cardClassStatus(ativo) : "");
            }
            const nome = escaparHTML(item.nome_interclasse);

            htmlMobile += `
                <a href="/painel?id=${item.id_interclasse}" class="text-decoration-none text-dark">
                    <div class="m-auto shadow d-flex justify-content-between align-content-center px-3 py-3 rounded-3 my-3 border border-1 ${classeCard} sgi-inline-8dd04718" >
                        <div>
                            <h2 class="m-0 fs-4">${nome}</h2>
                            <p class="text-secondary m-0">${anoStr}</p>
                            ${pageConfig.value2 ? `
                            <label class="form-check form-switch mt-2 mb-0">
                              <input class="form-check-input status-switch" type="checkbox" data-id="${item.id_interclasse}" ${ativo ? 'checked' : ''}>
                              <span class="small text-muted">Interclasse ativo</span>
                            </label>
                            ` : ``}
                            ${pageConfig.value4 ? `
                            <span class="badge bg-danger mt-2">Ativo</span>
                            ` : ``}
                        </div>
                        <img src="${(window.SGI_ASSET_BASE || '/assets') + '/images/arrow-right.svg'}" alt="icone de seta">
                    </div>
                </a>
            `;

            htmlDesktop += `
                <div class="row bg-white shadow rounded-3 py-3 fs-5 mt-3 align-items-center px-2 border border-1 ${classeCard} sgi-inline-d9a72e84"
                     onmouseover="this.style.backgroundColor='#f8f9fa'"
                     onmouseout="this.style.backgroundColor='#ffffff'"
                     onclick="window.location.href='/painel?id=${item.id_interclasse}'">

                    <div class="col-4 fw-semibold text-dark text-truncate">${nome}</div>
                    <div class="col-4 text-center text-secondary">${anoStr}</div>
                    <div class="col-4 text-center">
                        ${statusBadge}
                        ${pageConfig.value2 ? `
                        <div class="form-check form-switch d-flex justify-content-center mt-2">
                            <input class="form-check-input status-switch" type="checkbox" data-id="${item.id_interclasse}" ${ativo ? 'checked' : ''}>
                        </div>
                        ` : ``}
                    </div>
                </div>
            `;
        });

        listarMobile.innerHTML = htmlMobile;
        listarDesktop.innerHTML = htmlDesktop;

        if (pageConfig.value2) {
        registrarEventosStatus();
        }

    } catch (error) {
        console.error(error);
        const msgErro = '<p class="mt-3 text-center text-danger">Erro ao carregar dados da API!</p>';
        listarMobile.innerHTML = msgErro;
        listarDesktop.innerHTML = msgErro;
    }
}


function registrarEventosStatus() {
    document.querySelectorAll('.status-switch').forEach((input) => {
        pageScope.listen(input, 'click', (event) => event.stopPropagation());
        pageScope.listen(input, 'change', async (event) => {
            const id = event.target.getAttribute('data-id');
            const checked = event.target.checked;
            event.target.disabled = true;
            try {
                if (checked) {
                    await ativarComExclusividade(id);
                } else {
                    await atualizarStatusInterclasse(id, false);
                }
                await listarInterclasses();
            } catch (error) {
                alert(error.message || 'Erro ao atualizar status do interclasse.');
                await listarInterclasses();
            } finally {
                event.target.disabled = false;
            }
        });
    });
}


if (pageConfig.value2) {
pageScope.listen(document.getElementById('formulario'), 'submit', async (event) => {
    event.preventDefault();
    const nome = document.getElementById('nomeNovaEdicao').value;
    const ano = document.getElementById('anoNovaEdicao').value;
    const dataAtual = new Date();
    const mes = String(dataAtual.getMonth() + 1).padStart(2, '0');
    const dia = String(dataAtual.getDate()).padStart(2, '0');
    const dataFormatada = `${ano}-${mes}-${dia}`;

    const novoInterclasse = { nome_interclasse: nome.trim(), ano_interclasse: dataFormatada };

    try {
        document.getElementById('btnCriar').disabled = true;
        document.getElementById('btnCriar').innerText = "Criando...";

        const res = await axios.post("/api/v1/edicoes", novoInterclasse);

        if (res.data && res.data.success) {
            document.getElementById('caixaMensagem').innerHTML = '<p class="text-success text-center mt-3 mb-0 fw-bold">Criado com sucesso!</p>';
            const idCriado = res.data.id;
            await window.SGIInterclasse.refreshNavigation();
            document.getElementById('formulario').reset();
            listarInterclasses();
            setTimeout(() => {
                window.location.href = `/painel?id=${idCriado}`;
            }, 800);
        } else {
            throw new Error(res.data ? res.data.message : "Erro interno no servidor ao salvar.");
        }
    } catch (error) {
        const msgErro = error.response?.data?.message || error.message || "Erro desconhecido";
        document.getElementById('caixaMensagem').innerHTML = `<p class="text-danger text-center mt-3 mb-0 fw-bold">Erro: ${msgErro}</p>`;
    } finally {
        document.getElementById('btnCriar').disabled = false;
        document.getElementById('btnCriar').innerText = "Criar";
    }
});
}

if (pageConfig.value0) {
async function redirecionarParaInterclasseAtivo() {
    const semAtivo = () => {
        const msg = '<p class="text-center text-muted mt-5">Nenhuma edição de interclasse está ativa no momento.</p>';
        document.getElementById('caixaListar').innerHTML = msg;
        document.getElementById('listaDesktop').innerHTML = msg;
    };
    try {
        const res = await fetch('/api/v1/edicoes?regulamento=true');
        const lista = await res.json();
        if (Array.isArray(lista)) {
            const ativos = lista.filter(item => String(item.status_interclasse) === '1');
            if (ativos.length > 0) {
                const ativo = ativos.sort((a, b) => Number(b.id_interclasse) - Number(a.id_interclasse))[0];
                window.location.replace('/painel?id=' + ativo.id_interclasse);
                return;
            }
        }
    } catch (e) {
        console.error('Erro ao redirecionar:', e);
        // Offline: usa o id ativo gravado na sessão no login, se houver.
        const idSessao = window.SGI_SESSION_INTERCLASSE_ATIVO;
        if (idSessao) {
            window.location.replace('/painel?id=' + idSessao);
            return;
        }
    }
    semAtivo();
}

window.SGIPage.ready( redirecionarParaInterclasseAtivo);
} else {
window.SGIPage.ready( listarInterclasses);
}

return {escaparHTML, anoInterclasse, statusAtivo, atualizarStatusInterclasse, ativarComExclusividade, cardClassStatus, listarInterclasses, registrarEventosStatus};
});
