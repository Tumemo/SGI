import Dexie from '../../../node_modules/dexie/dist/dexie.mjs';

// 1. Inicializa o banco IndexedDB via Dexie
const db = new Dexie("SgiDB");
db.version(1).stores({
    // Armazena a rota PHP (action), o método e os dados do formulário
    pendencias: '++id, action, method, status, criadoEm'
});

// 2. INTERCEPTADOR GLOBAL DE FORMULÁRIOS
document.addEventListener('submit', async function(e) {
    const form = e.target;

    // Se o formulário tiver a classe 'no-offline', deixa o navegador processar normalmente
    if (form.classList.contains('no-offline')) return;

    // Cancela o envio padrão do formulário HTML para usarmos Fetch JSON
    e.preventDefault();

    // Pega todos os campos e transforma num Objeto JS simples
    const formData = new FormData(form);
    const dadosFormulario = {};

    formData.forEach((value, key) => {
        // Se houver múltiplos campos com o mesmo nome (ex: checkboxes), trata como array
        if (dadosFormulario[key] !== undefined) {
            if (!Array.isArray(dadosFormulario[key])) {
                dadosFormulario[key] = [dadosFormulario[key]];
            }
            dadosFormulario[key].push(value);
        } else {
            dadosFormulario[key] = value;
        }
    });

    const destinationUrl = form.getAttribute('action') || window.location.href;
    const formMethod = (form.getAttribute('method') || 'POST').toUpperCase();

    // Se estiver ONLINE, envia direto o JSON via Fetch
    if (navigator.onLine) {
        try {
            await enviarJSONParaPHP(destinationUrl, formMethod, dadosFormulario);
            alert("Dados enviados com sucesso!");
            form.reset();
        } catch (erro) {
            console.warn("Servidor instável. Guardando formulário offline...", erro);
            await salvarOffline(destinationUrl, formMethod, dadosFormulario, form);
        }
    } else {
        // Se estiver OFFLINE, guarda direto no Dexie.js
        await salvarOffline(destinationUrl, formMethod, dadosFormulario, form);
    }
});

// Função auxiliar para enviar requisições JSON ao PHP
async function enviarJSONParaPHP(url, method, dados) {
    const resposta = await fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(dados)
    });

    if (!resposta.ok) {
        throw new Error(`Erro na resposta do servidor: ${resposta.status}`);
    }

    return await resposta.json();
}

// Função para salvar no Dexie (IndexedDB)
async function salvarOffline(url, method, dados, formElement) {
    try {
        await db.pendencias.add({
            action: url,
            method: method,
            payload: dados,
            status: 'pendente',
            criadoEm: new Date().toISOString()
        });

        alert("⚠️ Sem conexão! Formulário salvo localmente. Os dados serão enviados automaticamente assim que a rede voltar.");
        if (formElement) formElement.reset();
    } catch (err) {
        console.error("Erro ao salvar offline no Dexie:", err);
        alert("Falha ao salvar dados localmente.");
    }
}

// 3. SINCRONIZADOR AUTOMÁTICO GLOBAL
async function sincronizarTudo() {
    if (!navigator.onLine) return;

    const pendentes = await db.pendencias.where('status').equals('pendente').toArray();
    if (pendentes.length === 0) return;

    console.log(`Encontrados ${pendentes.length} formulários pendentes. Sincronizando...`);

    for (const item of pendentes) {
        try {
            // Envia o JSON exatamente do jeito que o PHP espera
            await enviarJSONParaPHP(item.action, item.method, item.payload);
            
            // Apaga da fila local após o PHP processar com sucesso
            await db.pendencias.delete(item.id);
            console.log(`Formulário sincronizado com sucesso para: ${item.action}`);
        } catch (erro) {
            console.error(`Falha ao sincronizar item ${item.id} para ${item.action}. Tentará novamente depois.`, erro);
            break; // Interrompe o loop se a conexão falhar no meio
        }
    }
}

// Ouve o evento de reconexão
window.addEventListener('online', sincronizarTudo);

// Tenta sincronizar se houver algo pendente assim que a página carrega online
document.addEventListener('DOMContentLoaded', () => {
    if (navigator.onLine) sincronizarTudo();
});