<?php
if (date('d/m') == '29/04') {

    $pasta_json = __DIR__ . '/json_turmas/'; 
    $pasta_alunos = __DIR__ . '/../../docs/lista_alunos/';

    if (is_dir($pasta_alunos)) {
        $arquivos = array_diff(scandir($pasta_alunos), array('.', '..'));

        foreach ($arquivos as $arquivo) {
            $caminhoCompleto = $pasta_alunos . $arquivo;
            if (is_file($caminhoCompleto)) {
                unlink($caminhoCompleto);
            }
        }
    }

    if (is_dir($pasta_json)) {
        $itens = array_diff(scandir($pasta_json), array('.', '..'));

        foreach ($itens as $item) {
            $caminhoItem = $pasta_json . $item;
            is_dir($caminhoItem) ? rmdir($caminhoItem) : unlink($caminhoItem);
        }
        
        if (rmdir($pasta_json)) {
            echo "Success";
        }
    }
}
?>