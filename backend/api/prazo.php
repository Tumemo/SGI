<?php
if (date('d/m') == '25/02') {

    $pasta = __DIR__ . '/../../docs/lista_alunos/';

    if (is_dir($pasta)) {

        $arquivos = scandir($pasta);

        foreach ($arquivos as $arquivo) {

            if ($arquivo != "." && $arquivo != "..") {

                $caminhoCompleto = $pasta . $arquivo;

                if (is_file($caminhoCompleto)) {
                    unlink($caminhoCompleto);
                }
            }
        }
        echo "Succuess";
    }
}
?>