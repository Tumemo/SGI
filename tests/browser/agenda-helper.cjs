async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

function dataFutura(dias = 0) {
    const data = new Date();
    data.setDate(data.getDate() + dias);
    const ano = data.getFullYear();
    const mes = String(data.getMonth() + 1).padStart(2, '0');
    const dia = String(data.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

async function trocarSenhaInicial(request, endpoint = 'api/v1/senha') {
    const novaSenha = 'SenhaFixture#2026';
    const resposta = await request.post(endpoint, {
        data: { nova_senha: novaSenha, confirmar_senha: novaSenha },
    });
    const payload = await jsonOrThrow(resposta, 'troca inicial de senha do aluno fixture');
    if (payload.success !== true) {
        throw new Error(`troca inicial de senha do aluno fixture: ${payload.message || JSON.stringify(payload)}`);
    }
    return payload;
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
    // Cada teste recebe um local próprio. Specs paralelas compartilham a
    // edição e o mesmo dia; reutilizar o primeiro local disponível faria os
    // fixtures disputarem horários reais da agenda.
    const criado = await jsonOrThrow(await request.post('api/v1/locais', {
        data: {
            nome_local: `Fixture ${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 9)}`,
            disponivel_local: '1',
            carga_local: 0,
            interclasses_id_interclasse: Number(idInterclasse),
        },
    }), 'criação do local do fixture');
    const idLocal = Number(criado.id_local);
    if (!idLocal) throw new Error(`A API não retornou o local criado: ${JSON.stringify(criado)}`);
    const local = { id_local: idLocal };

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

module.exports = { agendarBloco, dataFutura, jsonOrThrow, trocarSenhaInicial };
