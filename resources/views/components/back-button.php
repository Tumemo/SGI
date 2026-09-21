<?php
/*
 * Botão padrão de navegação para trás.
 *
 * Utilizado pelo Header/TopBar das duas cascas (administração e portal do
 * aluno) e pelas páginas em desktop. Renderiza a seta seguida do rótulo
 * "Voltar" em uma pílula vermelha destacada. No mobile o botão permanece
 * compacto e exibe apenas a seta; abaixo de md o rótulo é ocultado e o
 * componente recebe dimensões reduzidas (ver shared.scss). O destino
 * continua sendo o $urlVoltar de cada página, preservando a navegação.
 *
 * Parâmetros aceitos (todos opcionais):
 *
 *   $mostrarVoltar  (bool)    controla a exibição; quando vazio/verdadeiro um,
 *                             nada é emitido apenas se for literalmente falso
 *   $sgiUrlVoltar   (string)  destino do link (default: $urlVoltar ou painel)
 *   $sgiIdVoltar    (string)  id do elemento (default: $idVoltar ou sgiBtnVoltar)
 *   $sgiClassVoltar (string)  classes adicionais (default: $classVoltar)
 *
 * Páginas que já definem $titulo/$mostrarVoltar/$urlVoltar ou usam o partial
 * mais de uma vez no mesmo fluxo não são afetadas: os parâmetros $sgi* têm
 * precedência e nenhuma variável de página é sobrescrita.
 */
if (empty($mostrarVoltar)) {
    return;
}
$urlVoltar = (string) (($sgiUrlVoltar ?? $urlVoltar) ?? \App\Shared\Http\Url::to('painel'));
$idVoltar = (string) (($sgiIdVoltar ?? $idVoltar) ?? 'sgiBtnVoltar');
$classVoltar = trim('sgi-back-button d-inline-flex align-items-center justify-content-center ' . (string) (($sgiClassVoltar ?? $classVoltar) ?? ''));
$hiddenVoltar = (bool) (($sgiHiddenVoltar ?? $hiddenVoltar) ?? false);
?>
<a href="<?= htmlspecialchars($urlVoltar, ENT_QUOTES, 'UTF-8') ?>"
   id="<?= htmlspecialchars($idVoltar, ENT_QUOTES, 'UTF-8') ?>"
   class="<?= htmlspecialchars($classVoltar, ENT_QUOTES, 'UTF-8') ?>"
   aria-label="Voltar"
   title="Voltar"<?= $hiddenVoltar ? ' hidden' : '' ?>>
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
    <span class="sgi-back-button-label d-none d-md-inline">Voltar</span>
</a>
