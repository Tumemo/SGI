<?php

declare (strict_types=1);

namespace App\Modules\Eventos\Domain;

final class EdicaoRules
{
    /**
     * Retorna erro padrão quando não há interclasse ativo.
     */
    public static function erroSemInterclasseAtivo(): array
    {
        return ['status' => 'erro', 'mensagem' => 'Nenhuma edição de interclasse está ativa. Ative uma edição antes de realizar esta operação.'];
    }
    /**
     * Retorna erro padrão quando tentam editar dados de edição encerrada.
     */
    public static function erroInterclasseEncerrado(): array
    {
        return ['status' => 'erro', 'mensagem' => 'Esta edição de interclasse já foi encerrada. Os dados não podem ser alterados.'];
    }
}
