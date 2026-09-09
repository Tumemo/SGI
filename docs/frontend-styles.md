# Organização dos estilos do SGI

Os estilos-fonte ficam em `resources/css/source/` e são compostos pelo build em três folhas públicas:

- `public/assets/css/admin.css`: telas administrativas e casca do mesário;
- `public/assets/css/aluno.css`: portal do aluno;
- `public/assets/css/login.css`: tela de acesso.

`tools/css-bundles.json` define a ordem da cascata. O build publica somente esses bundles; os fragments-fonte não são copiados para `public/assets/css`. Bootstrap, Bootstrap Icons e Font Awesome continuam sendo servidos como dependências locais e carregados antes dos estilos do SGI.

Após a limpeza, os seis fontes próprios somam 9.228 linhas e 270.363 bytes; o tamanho efetivo por contexto fica concentrado em três downloads públicos, com o login separado do pacote administrativo.

## Fontes

| Arquivo | Responsabilidade |
| --- | --- |
| `source/admin.css` | Base, tema administrativo, navegação e telas administrativas/mesário. |
| `source/aluno-shared.css` | Tokens e regras compartilhadas do portal do aluno. |
| `source/aluno-pages.css` | Jogos, modalidades e componentes do portal reaproveitados por telas relacionadas. |
| `source/aluno-home.css` | Home, ranking, perfil e termos do portal do aluno. |
| `source/utilities.css` | Utilitários visuais migrados de templates, com nomes `sgi-u-*`; cada declaração é mantida uma única vez e é incluída apenas nos bundles que possuem consumidores. |
| `source/login.css` | Reset mínimo, estados de autenticação, banners e campos exclusivos do acesso. |

As classes `sgi-u-*` substituíram os nomes hash `sgi-inline-*`. Elas continuam sendo utilitários porque vários templates e trechos de JavaScript geram esses elementos. Ao alterar um utilitário, procure os consumidores em `resources/views` e `resources/js` antes de removê-lo.

## Regras para novas alterações

Use Bootstrap quando a necessidade for atendida por um componente ou utilitário existente. Para comportamento ou identidade específica do SGI, use uma classe semântica `sgi-*` ou uma classe restrita ao bloco da tela. Não adicione regras globais para corrigir uma única tela.

Preserve IDs, classes usadas pelo JavaScript, atributos `data-bs-*`, breakpoints e estados de foco. O portal do aluno e o administrativo têm tokens e tipografia próprios; não copie variáveis de um contexto para o outro sem conferir os valores computados.

O mesário recebe o pacote administrativo completo no carregamento da casca. A navegação offline pode restaurar o conteúdo de um `<main>` e blocos de modais; por isso, estilos necessários a elementos fora do `main` devem permanecer no bundle administrativo ou em um componente global. A casca ainda pode aplicar CSS de telas capturado em blocos `<style>`; não dependa de um novo `<link>` por tela sem alterar e testar o contrato de preload.

## Build e verificação

Execute `npm run build` após alterar uma fonte. O manifesto em `public/assets/manifest.json` recebe o hash dos bundles, e `Assets::url()` acrescenta a versão automaticamente. Não edite os arquivos gerados manualmente.

Antes de remover uma regra, procure seu seletor em PHP, JavaScript, HTML gerado e CSS. Classes produzidas dinamicamente, modais, estados vazios/carregando/erro e telas offline precisam entrar na verificação. A aprovação visual deve usar dados determinísticos nos viewports de 390×844 e 1440×900, além de conferir 360 px, 768 px e modais longos.
