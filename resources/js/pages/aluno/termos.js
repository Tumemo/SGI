window.SGIPage.mount("aluno/termos", function (pageConfig, pageScope) {

        async function carregarRegulamento() {
            const statusEl = document.getElementById('statusRegulamento');
            const containerPdf = document.getElementById('containerPdfRegulamento');
            const btnPdf = document.getElementById('btnBaixarPdf');

            try {
                // Busca a lista de interclasses com regulamento atrelado
                const res = await fetch('../../../../api/interclasse.php?regulamento=true');
                if (!res.ok) throw new Error('Erro na resposta da API');

                const data = await res.json();

                // Trata retorno caso venha um array ou objeto único
                const lista = Array.isArray(data) ? data : [data];

                // Busca o interclasse ativo (status_interclasse === '1' ou 1)
                const ativo = lista.find(i => String(i.status_interclasse) === '1') || lista[0];

                if (ativo && ativo.regulamento_interclasse && ativo.regulamento_interclasse.trim() !== '') {
                    btnPdf.href = '../../../../uploads/regulamentos/' + ativo.regulamento_interclasse;

                    statusEl.classList.add('d-none');
                    containerPdf.classList.remove('d-none');
                } else {
                    statusEl.textContent = 'Nenhum regulamento disponível no momento.';
                    statusEl.className = 'text-muted mb-0';
                }
            } catch (error) {
                console.error("Erro ao carregar regulamento:", error);
                statusEl.textContent = 'Erro ao carregar regulamento. Tente novamente mais tarde.';
                statusEl.className = 'text-danger mb-0';
            }
        }

        window.SGIPage.ready( carregarRegulamento);

return {carregarRegulamento};
});
