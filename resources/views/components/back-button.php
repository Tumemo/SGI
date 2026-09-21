<?php
/*
 * Botão padrão de navegação para trás.
 *
 * Utilizado pelo Header/TopBar das duas cascas (administração e portal do
 * aluno) e pelas páginas em desktop. Mantém apenas o ícone de seta, com
 * rótulo acessível para leitores de tela; o destino continua sendo o
 * $urlVoltar de cada página, preservando a navegação existente.
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
$classVoltar = trim('sgi-back-button ' . (string) (($sgiClassVoltar ?? $classVoltar) ?? ''));
?>
<a href="<?= htmlspecialchars($urlVoltar, ENT_QUOTES, 'UTF-8') ?>"
   id="<?= htmlspecialchars($idVoltar, ENT_QUOTES, 'UTF-8') ?>"
   class="<?= htmlspecialchars($classVoltar, ENT_QUOTES, 'UTF-8') ?>"
   aria-label="Voltar"
   title="Voltar" style="margin-top: 40px; margin-left: 10px ;"></style>
    <i class="bi bi-arrow-left" aria-hidden="true"></i>
</a>
