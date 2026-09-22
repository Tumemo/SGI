async function jsonOrThrow(response, label) {
    if (!response.ok()) {
        throw new Error(`${label}: HTTP ${response.status()} ${await response.text()}`);
    }
    return response.json();
}

function dataHoraUtc(offsetMinutes) {
    const value = new Date(Date.now() + offsetMinutes * 60_000);
    return value.toISOString().slice(0, 16);
}

/**
 * The browser fixture uses the same published/reviewed contract as the UI.
 * Existing suites may leave the active edition in review, so the next fixture
 * must close that cycle through the canonical cronograma API before enrolling.
 */
async function garantirCronogramaPublicado(request, idInterclasse) {
    let state = await jsonOrThrow(
        await request.get(`api/v1/cronograma?id_interclasse=${idInterclasse}`),
        'estado do cronograma do fixture',
    );

    if (String(state.cronograma_status) !== 'publicado') {
        const locais = await jsonOrThrow(
            await request.get(`api/v1/locais?id_interclasse=${idInterclasse}&disponivel=1`),
            'locais do cronograma do fixture',
        );
        let idLocal = Number((Array.isArray(locais) ? locais[0] : null)?.id_local || 0);
        if (!idLocal) {
            const criado = await jsonOrThrow(
                await request.post('api/v1/locais', {
                    data: {
                        nome_local: `Fixture cronograma ${Date.now().toString(36)}`,
                        disponivel_local: '1',
                        carga_local: 0,
                        interclasses_id_interclasse: Number(idInterclasse),
                    },
                }),
                'criação do local do cronograma do fixture',
            );
            idLocal = Number(criado.id_local || 0);
        }
        if (!idLocal) throw new Error('O fixture não encontrou local disponível para o cronograma.');

        const modalidades = await jsonOrThrow(
            await request.get(`api/v1/modalidades?id_interclasse=${idInterclasse}`),
            'modalidades do cronograma do fixture',
        );
        for (const modalidade of (Array.isArray(modalidades) ? modalidades : [])) {
            if (String(modalidade.status_modalidade) !== '1') continue;
            await jsonOrThrow(
                await request.put('api/v1/modalidades', {
                    data: {
                        id_modalidade: Number(modalidade.id_modalidade),
                        equipes_planejadas: Math.max(1, Number(modalidade.equipes_planejadas || modalidade.max_equipes || 2)),
                        min_inscritos_equipe: Math.max(1, Number(modalidade.min_inscritos_equipe || 1)),
                        max_inscritos_equipe: Math.max(1, Number(modalidade.max_inscritos_equipe || 10)),
                        formato_participacao: String(modalidade.formato_participacao || 'equipe'),
                        duracao_prevista_min: Math.max(1, Number(modalidade.duracao_prevista_min || 5)),
                        descanso_min: Math.max(0, Number(modalidade.descanso_min || 0)),
                    },
                }),
                `planejamento da modalidade ${modalidade.id_modalidade} do fixture`,
            );
        }

        await jsonOrThrow(
            await request.post('api/v1/cronograma', {
                data: { acao: 'preparar_equipes', id_interclasse: idInterclasse },
            }),
            'preparação das equipes do cronograma do fixture',
        );
        const draft = await jsonOrThrow(
            await request.post('api/v1/cronograma', {
                data: {
                    acao: 'gerar_rascunho',
                    id_interclasse: idInterclasse,
                    data_inicio: '2030-01-01',
                    data_fim: '2030-01-01',
                    hora_inicio: '00:00',
                    hora_fim: '23:59',
                    duracao_min: 5,
                    intervalo_min: 0,
                    id_locais: [idLocal],
                },
            }),
            'geração do cronograma do fixture',
        );
        try {
            await jsonOrThrow(
                await request.post('api/v1/cronograma', {
                    data: {
                        acao: 'publicar',
                        id_interclasse: idInterclasse,
                        cronograma_versao: Number(draft.cronograma_versao || 0),
                        nos: draft.nos,
                        compromissos: draft.compromissos,
                    },
                }),
                'publicação do cronograma do fixture',
            );
        } catch (error) {
            // A edição ativa pode ter inscrições de outro cenário. O contrato
            // final recusa republicá-la para preservar essas inscrições; um
            // fixture de navegador deve então usar uma edição descartável nova.
            if (!String(error.message || error).includes('conflita inscrições existentes')) throw error;
            const criada = await jsonOrThrow(
                await request.post('api/v1/edicoes', {
                    data: {
                        nome_interclasse: `Fixture cronograma ${Date.now()}`,
                        ano_interclasse: '2030-01-01 00:00:00',
                    },
                }),
                'criação da edição isolada do fixture',
            );
            const novoId = Number(criada.id_interclasse || criada.id || 0);
            if (!novoId) throw new Error('A API não retornou a edição isolada do fixture.');
            return garantirCronogramaPublicado(request, novoId);
        }
        state = await jsonOrThrow(
            await request.get(`api/v1/cronograma?id_interclasse=${idInterclasse}`),
            'revisão publicada do cronograma do fixture',
        );
    }

    if (String(state.inscricoes_status) !== 'abertas') {
        await jsonOrThrow(
            await request.post('api/v1/cronograma', {
                data: {
                    acao: 'abrir_inscricoes',
                    id_interclasse: idInterclasse,
                    cronograma_versao: Number(state.cronograma_versao || 0),
                    inscricoes_abertura: dataHoraUtc(-5),
                    inscricoes_encerramento: dataHoraUtc(24 * 60),
                },
            }),
            'abertura das inscrições do cronograma do fixture',
        );
    }

    return state;
}

module.exports = { garantirCronogramaPublicado };
