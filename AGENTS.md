# Guia de Arquitetura e Engenharia do SGI (Sistema de Gestão de Interclasses)

Este documento foi elaborado para orientar **Agentes de IA** e **Desenvolvedores** na manutenção, extensão e operação do ecossistema SGI.

---

## 1. Visão Geral da Aplicação

O **SGI (Sistema de Gestão de Interclasses)** é uma aplicação web monolítica em **PHP / Vanilla JavaScript** desenvolvida para gerenciar competições esportivas e culturais escolares (Interclasses) no SESI.

### Principais Funcionalidades:
- **Gestão de Edições (Interclasses):** Criação e alternância de edições anuais com geração automática de turmas, modalidades e equipes padrão.
- **Importação de Alunos via PDF:** Extração automática de dados de alunos (Nome, RM/RA, Data de Nascimento e Gênero) a partir de listas em PDF e distribuição em turmas.
- **Inscrição de Competidores:** Painel para alunos escolherem suas modalidades esportivas (com validação de regras de gênero, limites e categorias).
- **Chaveamento e Mata-Mata:** Gerador e visualizador interativo de chaves com suporte a modalidades individuais e mata-mata.
- **Placar e Operação Offline (Mesário):** Operação de partidas em tempo real (cronômetro, gols, cartões, ocorrências e avanço de chaves) funcionando **100% offline via IndexedDB e Service Worker/SPA shell** com sincronização bidirecional na reconexão.
- **Ranking Geral e Arrecadações:** Pontuação de turmas por pódios esportivos, arrecadação de alimentos e desconto automático por ocorrências disciplinares.

---

## 2. Níveis de Acesso e Permissões (`nivel_usuario`)

| Nível | Papel | Descrição e Permissões |
| :---: | :--- | :--- |
| **`0`** | **Administrador** | Acesso total. Cria edições, gerencia usuários, configura pontuações, turmas, locais e modalidades, além de visualizar todos os relatórios. |
| **`1`** | **Colaborador** | Acesso operacional. Lança pontuações, agenda jogos, gerencia ocorrências e visualiza ranking e chaveamento. Não pode excluir edições ou resetar credenciais mestras. |
| **`2`** | **Mesário** | Operador de campo/quadra. Executa em Single Page Application com cache local. Opera exclusivamente sobre a edição **ativa**, controlando cronômetro, placar, artilharia, ocorrências e avanço de chaves (online e offline). |
| **`3`** | **Competidor / Aluno** | Acesso restrito via RM/RA e senha. Aceita termos de participação, visualiza agenda de jogos, histórico da sua turma e se inscreve em até 3 modalidades. |

---

## 3. Arquitetura do Banco de Dados (MySQL)

### Tabelas Principais:
1. `interclasses`: Edições do evento (`id_interclasse`, `nome_interclasse`, `ano_interclasse`, `status_interclasse`, `ponto_1_lugar`, `ponto_2_lugar`, `ponto_3_lugar`, `valor_item_arrecadacao`).
2. `categorias`: Segmentação escolar (`id_categoria`, `nome_categoria`, `interclasses_id_interclasse`). Ex: Categoria I (6º ao 8º) e Categoria II (9º ao 3º Médio).
3. `turmas`: Turmas escolares (`id_turma`, `nome_turma`, `turno_turma`, `pontuacao_turma`, `qtd_itens_arrecadados`).
4. `modalidades`: Esportes/provas (`id_modalidade`, `nome_modalidade`, `genero_modalidade`, `max_inscrito_modalidade`, `max_equipes`, `tipos_modalidades_id_tipo_modalidade`).
5. `equipes`: Vínculo turma x modalidade (`id_equipe`, `nome_equipe`, `modalidades_id_modalidade`, `turmas_id_turma`).
6. `usuarios`: Contas de acesso (`id_usuario`, `matricula_usuario`, `senha_usuario`, `nivel_usuario`, `turmas_id_turma`, `interclasses_id_interclasse`, `chave_usuario_edicao`).
7. `equipes_has_usuarios`: Inscrição de competidores nas equipes (`equipes_id_equipe`, `usuarios_id_usuario`).
8. `jogos`: Partidas agendadas e ao vivo (`id_jogo`, `nome_jogo`, `data_jogo`, `inicio_jogo`, `termino_jogo`, `status_jogo`, `duracao_jogo`, `tempo_restante_jogo`, `tempo_extra_jogo`).
9. `partidas`: Times participantes em cada jogo e placar (`id_partida`, `jogos_id_jogo`, `equipes_id_equipe`, `resultado_partida`).
10. `artilheiros`: Registro de gols e pontuadores individuais (`id_artilheiro`, `usuarios_id_usuario`, `jogos_id_jogo`, `num_gol`).
11. `ocorrencias`: Ocorrências disciplinares individuais com perda de pontos.
12. `ocorrencias_turmas`: Ocorrências disciplinares atribuídas diretamente à turma.
13. `historico_arrecadacoes`: Registro detalhado de doações e arrecadação de itens por turma.
14. `tipos_modalidades`: 'Mata-Mata' ou 'Individual'.
15. `locais`: Quadras, campos e salas (`id_local`, `nome_local`, `disponivel_local`, `carga_local`).

---

## 4. Arquitetura do Modo Offline (Perfil Mesário)

O subsistema offline está localizado em `views/src/componentes/` e opera em conjunto com `api/lancar_resultado.php` e `views/src/pages/jogos.php`:

1. **`offline-core.js`:**
   - Intercepta chamadas de rede (`fetch`, `XMLHttpRequest`, `axios`).
   - Se offline ou em falha de conexão ("soft-offline"), enfileira mutações POST/PUT/DELETE no IndexedDB (`fila_sincronizacao`).
   - Monitora eventos `window.addEventListener('online', ...)` e dispara `syncQueue()` para descarregar a fila automaticamente.

2. **`mesario-data.js`:**
   - Banco de dados local IndexedDB (`sgi_mesario_dados`) contendo stores para `jogos`, `partidas`, `turmas`, `modalidades`, `categorias`, `locais`, `atletas`, `ocorrencias`, `ocorrencias_turmas` e `chaveamentos`.
   - Projeta alterações otimistas imediatamente na UI para garantir fluidez.

3. **`mesario-offline.js`:**
   - SPA Shell para Mesário: pré-carrega as páginas HTML e scripts no login (`preload`).
   - Utiliza `tornarReexecutavel(src)` com analisador léxico para reinjetar o DOM e reexecutar scripts sem recarregar a página (com suporte a regex literals e variáveis de escopo).

4. **`chaveamento-engine.js`:**
   - Motor híbrido em JavaScript: quando um jogo é concluído offline, `promoverVencedorLocal(idJogo)` avança o vencedor para a próxima fase localmente, gerando partidas derivadas com IDs temporários negativos (`id_jogo < 0`).

5. **`api/lancar_resultado.php`:**
   - Recebe as mutações sincronizadas.
   - Quando `$idJogo <= 0` (partida gerada offline), resolve o jogo real criado no MySQL utilizando a tag mata-mata (`nome_jogo`, ex.: `MM:2:0:N`) ou as equipes participantes, aplicando o placar e avançando o chaveamento no servidor.

---

## 5. Convenções e Diretrizes para Modificações

- **Compatibilidade MySQL / MariaDB:** Nunca adicione triggers que executem `UPDATE` na mesma tabela que disparou o evento (evita Erro 1442).
- **Unicidade de Matrículas:** Alunos utilizam a chave composta `uk_matricula_interclasse` (`matricula_usuario`, `interclasses_id_interclasse`), permitindo que a mesma matrícula participe em anos diferentes.
- **Senhas:** Sempre utilize `password_hash($senha, PASSWORD_DEFAULT)` e `password_verify($senha, $hash)`.
- **Rotas Relativas:** Mantenha os caminhos relativos consistentes com a profundidade da pasta (ex: `views/src/pages/alunos/` está a 4 níveis da raiz `/SGI/`).
