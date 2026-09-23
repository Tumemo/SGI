const test = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');

test('motor offline preserva tags PL e usa o compromisso publicado ao formar a próxima fase', async () => {
    const source = fs.readFileSync('resources/js/offline/chaveamento-engine.js', 'utf8');
    const modalityId = 71;
    const finalTag = `PL:${modalityId}:0:MM:2:0:N`;
    const plannedTree = [
        {
            id_jogo: 81,
            nome_jogo: `PL:${modalityId}:0:MM:4:0:N`,
            status_jogo: 'Concluido',
            modalidades_id_modalidade: modalityId,
            id_interclasse: 9,
            equipes: [
                { id_equipe: 11, nome_equipe: 'Equipe A', gols: 2 },
                { id_equipe: 12, nome_equipe: 'Equipe B', gols: 1 },
            ],
        },
        {
            id_jogo: -32,
            nome_jogo: `PL:${modalityId}:0:MM:4:1:B`,
            status_jogo: 'Concluido',
            eh_bye: true,
            modalidades_id_modalidade: modalityId,
            id_interclasse: 9,
            equipes: [{ id_equipe: 13, nome_equipe: 'Equipe C', gols: 0 }],
        },
        {
            id_jogo: -34,
            nome_jogo: finalTag,
            status_jogo: 'Aguardando',
            virtual_planejado: true,
            modalidades_id_modalidade: modalityId,
            id_interclasse: 9,
            equipes: [],
        },
    ];
    const cronograma = {
        cronograma_status: 'publicado',
        versao_publicada: 4,
        compromissos: [{
            id_compromisso: 402,
            id_modalidade: modalityId,
            chave_tag: finalTag,
            data_compromisso: '2030-10-03',
            inicio_compromisso: '10:20:00',
            termino_compromisso: '10:40:00',
            id_local: 5,
        }],
    };
    const window = {
        location: { href: 'http://sgi.test/' },
        SGIDataLayer: {
            read: async (store) => store === 'cronograma' ? cronograma : [],
        },
        SGIOffline: {
            getPendingList: async () => [{
                id: 'result-root',
                url: '/api/v1/resultados',
                method: 'POST',
                createdAt: 1,
                body: JSON.stringify({
                    id_jogo: 81,
                    resultados: [{ id_equipe: 11, gols: 2 }, { id_equipe: 12, gols: 1 }],
                }),
            }],
        },
        SGI_SESSION_INTERCLASSE_ATIVO: 9,
        addEventListener: () => {},
    };
    const document = { getElementsByTagName: () => [] };
    const context = {
        window,
        document,
        navigator: { onLine: true },
        URL,
        fetch: async (url) => url.includes('chaveamentos')
            ? { json: async () => ({ success: true, jogos: plannedTree }) }
            : { ok: true, json: async () => [] },
        indexedDB: null,
        console,
    };
    vm.runInNewContext(source, context);

    const result = await window.SGIChaveamento.carregarArvore(modalityId);
    const final = result.jogos.find((game) => game.nome_jogo === finalTag);

    assert.ok(final, 'a final deve manter a identidade PL do calendário');
    assert.equal(JSON.stringify(final.equipes.map((team) => Number(team.id_equipe)).sort()), JSON.stringify([11, 13]));
    assert.equal(final.data_jogo, '2030-10-03');
    assert.equal(final.inicio_jogo, '10:20:00');
    assert.equal(Number(final.locais_id_local), 5);
    assert.equal(final.modalidades_id_modalidade, modalityId);
});
