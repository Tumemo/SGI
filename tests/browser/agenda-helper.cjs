async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

function dataFutura(dias = 0) {
    const data = new Date(Date.now() + (dias * 24 * 60 * 60 * 1000));
    return data.toISOString().slice(0, 10);
}

/**
 * Agenda o fixture pela mesma API usada pela tela administrativa. Assim os
 * testes de mesário exercitam somente jogos que realmente estão liberados na
 * agenda operacional.
 */
async function agendarBloco(request, {
    idInterclasse,
    idModalidade,
    jogos = [],
    chaveTags = [],
    label = 'agendamento do fixture',
}) {
    const locais = await jsonOrThrow(
        await request.get(`api/v1/locais?id_interclasse=${idInterclasse}&disponivel=1`),
        'locais disponíveis',
    );
    const listaLocais = Array.isArray(locais) ? locais : (Array.isArray(locais?.data) ? locais.data : []);
    let local = listaLocais.find((item) => Number(item.id_local) > 0);
    if (!local) {
        const criado = await jsonOrThrow(await request.post('api/v1/locais', {
            data: {
                nome_local: `Local fixture ${idInterclasse} ${Date.now()}`,
                disponivel_local: '1',
                carga_local: 0,
                interclasses_id_interclasse: Number(idInterclasse),
            },
        }), 'criação do local do fixture');
        const idLocal = Number(criado.id_local);
        if (!idLocal) throw new Error(`A API não retornou o local criado: ${JSON.stringify(criado)}`);
        local = { id_local: idLocal };
    }

    const payload = {
        acao: 'confirmar',
        id_interclasse: Number(idInterclasse),
        id_modalidade: Number(idModalidade),
        jogos: jogos.map((item) => ({ id_jogo: Number(item.id_jogo ?? item) })),
        chave_tags: chaveTags.map((item) => ({
            id_modalidade: Number(idModalidade),
            chave_tag: String(item),
        })),
        janelas: [{
            data: dataFutura(),
            inicio: '08:00',
            fim: '23:00',
            locais: [Number(local.id_local)],
        }],
        opcoes: {
            duracao_min: 20,
            intervalo_troca_min: 5,
            descanso_min: 0,
        },
        idempotencia: `${label}-${idInterclasse}-${idModalidade}-${Date.now()}-${Math.random().toString(16).slice(2)}`,
    };
    const preview = await jsonOrThrow(
        await request.post('api/v1/agenda-blocos', { data: { ...payload, acao: 'simular' } }),
        `${label} (prévia)`,
    );
    if (Array.isArray(preview.pendencias) && preview.pendencias.length > 0) {
        throw new Error(`${label} possui pendências: ${JSON.stringify(preview.pendencias)}`);
    }
    return jsonOrThrow(await request.post('api/v1/agenda-blocos', {
        data: { ...payload, acao: 'confirmar', revisao: Number(preview.revisao || 0) },
    }), label);
}

module.exports = { agendarBloco, dataFutura, jsonOrThrow };
