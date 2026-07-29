Requisitos Funcionais (RF) Gestão de Infraestrutura e Ciclo de Vida 

● RF01 - Inicialização de Edição e Importação de Dados: O sistema deve permitir a criação de uma nova edição do Interclasse (ex: "Interclasse de Inverno"). A base de dados de alunos e turmas deve ser gerada obrigatoriamente via importação de arquivo (Excel/CSV) extraído do sistema escolar, contendo: Nome, Turma, Turno, Número de Matrícula (RA) e Data de Nascimento. 

● RF02 - Manutenção Automática de Turmas: O sistema deve criar ou atualizar as turmas automaticamente com base na importação do RF01. As turmas permanecem no sistema como estruturas permanentes, mas seus vínculos com alunos e temas (ex: Os Simpsons) são renovados a cada nova inicialização de edição. 

● RF03 - Configuração de Modalidades e Regras de Pontuação: A professora deve definir para cada esporte se a pontuação é por "Ponto Direto" (Gols), "Lógica de Sets" (Vôlei) ou "Resultado Final Manual" (Xadrez/Atletismo), além de nomear a "Ação de Destaque" (ex: Cesta, Gol, Vitória). 

● RF04 - Painel Central de Regras e Pesos (Cálculo de Ranking): Interface única para configuração de: ○ Pontos de Pódio: Valores fixos para 1º, 2º e 3º lugar. ○ Multiplicador de Arrecadação: Valor em pontos por unidade/peso (ex: 1kg = 50 pts). ○ Lógica de Ranking Geral: Soma automática: (Pontos de Pódio) + (Total Arrecadação × Multiplicador) - (Penalidades). Inscrição e Operação de Jogos 

● RF05 - Validação de Inscrição via RA: O aluno não realiza um cadastro, mas sim uma validação. Ao informar o RA e a Data de Nascimento, o sistema cruza os dados com a base importada. Se validados, o sistema identifica o aluno e libera a escolha de até 3 modalidades. 

● RF06 - Gestão Administrativa e Disciplinar: Controle total da professora para realizar inscrições manuais em casos excepcionais, editar escolhas de modalidades ou aplicar suspensões que bloqueiam o aluno de participar de partidas específicas. 

● RF07 - Agendamento com Janelas de Horário: O gerador de agenda deve respeitar janelas de tempo e turnos (Manhã/Tarde), impedindo que turmas sejam escaladas para jogos fora de seu horário letivo sem autorização expressa. 

● RF08 - Painel do Mesário (Operação de Partida): Interface de placar em tempo real. Ao adicionar um ponto/gol, o sistema exibe apenas os alunos daquela turma validados no RF05. Possui função de incremento rápido e "Desfazer" para correções.

 ● RF09 - Perfil de Acesso Monitor/Mesário: Login restrito para alunos ajudantes operarem placares e lançarem arrecadações, sem permissão para alterar regras de pódio ou excluir dados oficiais. Visualização e Relatórios

 ● RF10 - Transmissão de Status e Placar em Tempo Real: Dashboard público que exibe placares ao vivo. Em caso de perda de conexão do operador, a tela deve exibir um aviso de "Sincronizando..." para manter a transparência com o público.

 ● RF11 - Mural de Classificação Dinâmico: Geração de ranking automatizado (esportivo + solidariedade) pronto para exibição em telas ou impressão para murais físicos.

 ● RF12 - Relatório de Destaques Individuais (Artilharia): Compilação automática dos dados do RF08 para identificar os alunos com maior desempenho em cada modalidade da edição ativa.

 ● RF13 - Encerramento e Exportação Histórica: Função para "Finalizar Edição", gerando PDF completo com súmulas e rankings, congelando os dados para consulta histórica e liberando o sistema para o próximo evento.

 ● RF14 - Sorteio e Chaveamento de Confrontos: O sistema deve permitir a geração automática das chaves de jogos. A professora poderá optar por Sorteio Aleatório (respeitando categorias/níveis de ensino) ou Definição Manual. O sistema deve suportar fases de "Grupos" e "Eliminatórias" (Mata-mata), gerando a árvore do torneio visualmente.

 Requisitos Não Funcionais (RNF)

 ● RNF01 - Sincronização Híbrida (Offline-First): O sistema deve priorizar o funcionamento local. Em quedas de internet, os dados (placares/arrecadação) ficam em cache e são enviados automaticamente ao servidor assim que a conexão retornar.

 ● RNF02 - Latência de Transmissão: Com internet estável, o tempo entre a ação do mesário e a atualização no Dashboard público não deve exceder 2 segundos. 

● RNF03 - Segurança e Privacidade (LGPD): Dados pessoais (RA e Data de Nascimento) devem ser protegidos e nunca exibidos integralmente. Em telas públicas, deve-se utilizar nomes sociais/abreviados e a turma para identificação
. 
● RNF04 - Usabilidade e Eficiência de UX: O processo de lançamento de pontos e seleção de atletas deve ser otimizado para o mínimo de cliques, garantindo agilidade durante o andamento frenético das partidas. 

● RNF05 - Responsividade e Performance: Interface leve, carregando em menos de 3s em dispositivos móveis, garantindo estabilidade mesmo em redes Wi-Fi escolares de alta demanda. 

● RNF06 - Integridade e Persistência: Dados de edições encerradas devem ser imutáveis e protegidos contra alterações acidentais, garantindo a fidelidade do histórico escolar.
