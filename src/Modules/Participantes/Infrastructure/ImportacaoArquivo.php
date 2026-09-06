<?php

declare (strict_types=1);

namespace App\Modules\Participantes\Infrastructure;

final class ImportacaoArquivo
{
    /**
     * Compatível com conversor_pdf.php e chamadas legadas.
     *
     * @param ?int $idInterclasse Opcional: edição alvo (sobrescreve "só ativo").
     * @param ?int $idCategoria Opcional: categoria para turmas novas do PDF.
     */
    public static function importarCompetidores(\mysqli $conn, ?int $idInterclasse = \null, ?int $idCategoria = \null, ?int $idTurma = \null): array
    {
        $imp = new \App\Modules\Participantes\Infrastructure\ImportadorCompetidores($conn);
        return $imp->importarDeArquivo(\null, $idInterclasse, $idCategoria, $idTurma);
    }
}
