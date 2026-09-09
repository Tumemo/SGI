<?php

declare (strict_types=1);

namespace App\Shared\Database;

final class SqlFilters
{
    public static function aplicarFiltrosArtilharia(array $filters): array
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        $ano = isset($filters['ano']) ? \intval($filters['ano']) : \date('Y');
        $sqlExtras .= " AND YEAR(jogos.data_jogo) = ?";
        $types .= "i";
        $params[] = $ano;
        if (!empty($filters['id_jogo'])) {
            $sqlExtras .= " AND jogos.id_jogo = ?";
            $types .= "i";
            $params[] = \intval($filters['id_jogo']);
        }
        if (!empty($filters['id_modalidade'])) {
            $sqlExtras .= " AND modalidades.id_modalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['id_modalidade']);
        }
        if (!empty($filters['genero'])) {
            $sqlExtras .= " AND usuarios.genero_usuario = ?";
            $types .= "s";
            $params[] = $filters['genero'];
        }
        if (!empty($filters['id_turma'])) {
            $sqlExtras .= " AND turmas.id_turma = ?";
            $types .= "i";
            $params[] = \intval($filters['id_turma']);
        }
        if (!empty($filters['turno'])) {
            $sqlExtras .= " AND turmas.turno_turma = ?";
            $types .= "s";
            $params[] = $filters['turno'];
        }
        if (!empty($filters['id_categoria'])) {
            $sqlExtras .= " AND categorias.id_categoria = ?";
            $types .= "i";
            $params[] = \intval($filters['id_categoria']);
        }
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND modalidades.interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosEquipes(array $filters): array
    {
        $sqlExtras = " AND equipes.status_equipe = '1'";
        $types = "";
        $params = [];
        if (!empty($filters['id_equipe'])) {
            $sqlExtras .= " AND equipes.id_equipe = ?";
            $types .= "i";
            $params[] = \intval($filters['id_equipe']);
        }
        if (!empty($filters['id_turma'])) {
            $sqlExtras .= " AND equipes.turmas_id_turma = ?";
            $types .= "i";
            $params[] = \intval($filters['id_turma']);
        }
        if (!empty($filters['id_modalidade'])) {
            $sqlExtras .= " AND equipes.modalidades_id_modalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['id_modalidade']);
        }
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND turmas.interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosInterclasse(array $filters): array
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        $idGet = !empty($filters['id_interclasse']) ? \intval($filters['id_interclasse']) : (!empty($filters['id']) ? \intval($filters['id']) : 0);
        if ($idGet > 0) {
            $sqlExtras .= " AND id_interclasse = ?";
            $types .= "i";
            $params[] = $idGet;
        }
        if (!empty($filters['ano'])) {
            $sqlExtras .= " AND YEAR(ano_interclasse) = ?";
            $types .= "i";
            $params[] = \intval($filters['ano']);
        }
        if (!empty($filters['busca'])) {
            $sqlExtras .= " AND nome_interclasse LIKE ?";
            $types .= "s";
            $params[] = "%" . $filters['busca'] . "%";
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosJogos(array $filters): array
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        if (!empty($filters['id_jogo'])) {
            $sqlExtras .= " AND jogos.id_jogo = ?";
            $types .= "i";
            $params[] = \intval($filters['id_jogo']);
        }
        if (!empty($filters['id_modalidade'])) {
            $sqlExtras .= " AND jogos.modalidades_id_modalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['id_modalidade']);
        }
        if (!empty($filters['id_local'])) {
            $sqlExtras .= " AND jogos.locais_id_local = ?";
            $types .= "i";
            $params[] = \intval($filters['id_local']);
        }
        if (!empty($filters['data'])) {
            $sqlExtras .= " AND jogos.data_jogo = ?";
            $types .= "s";
            $params[] = $filters['data'];
        }
        if (!empty($filters['status'])) {
            $sqlExtras .= " AND jogos.status_jogo = ?";
            $types .= "s";
            $params[] = $filters['status'];
        }
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND modalidades.interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        if (!empty($filters['id_categoria'])) {
            $sqlExtras .= " AND modalidades.categorias_id_categoria = ?";
            $types .= "i";
            $params[] = \intval($filters['id_categoria']);
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosModalidades(array $filters): array
    {
        $sqlExtras = " AND modalidades.status_modalidade = '1'";
        $types = "";
        $params = [];
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND modalidades.interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        if (!empty($filters['id_modalidade'])) {
            $sqlExtras .= " AND modalidades.id_modalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['id_modalidade']);
        }
        if (!empty($filters['id_categoria'])) {
            $sqlExtras .= " AND modalidades.categorias_id_categoria = ?";
            $types .= "i";
            $params[] = \intval($filters['id_categoria']);
        }
        if (!empty($filters['id_tipo_modalidade'])) {
            $sqlExtras .= " AND modalidades.tipos_modalidades_id_tipo_modalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['id_tipo_modalidade']);
        }
        if (!empty($filters['genero'])) {
            $sqlExtras .= " AND modalidades.genero_modalidade = ?";
            $types .= "s";
            $params[] = \strtoupper($filters['genero']);
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosCategorias(array $filters): array
    {
        $sqlExtras = " AND status_categoria = '1'";
        $types = "";
        $params = [];
        if (!empty($filters['id_categoria'])) {
            $sqlExtras .= " AND id_categoria = ?";
            $types .= "i";
            $params[] = \intval($filters['id_categoria']);
        }
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        if (!empty($filters['busca'])) {
            $sqlExtras .= " AND nome_categoria LIKE ?";
            $types .= "s";
            $params[] = "%" . $filters['busca'] . "%";
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosLocais(array $filters): array
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        if (!empty($filters['id_local'])) {
            $sqlExtras .= " AND id_local = ?";
            $types .= "i";
            $params[] = \intval($filters['id_local']);
        }
        if (isset($filters['disponivel'])) {
            $sqlExtras .= " AND disponivel_local = ?";
            $types .= "s";
            $params[] = $filters['disponivel'];
        }
        if (!empty($filters['busca'])) {
            $sqlExtras .= " AND nome_local LIKE ?";
            $types .= "s";
            $params[] = "%" . $filters['busca'] . "%";
        }
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosOcorrencias(array $filters): array
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        if (!empty($filters['id_usuario'])) {
            $sqlExtras .= " AND ocorrencias.usuarios_id_usuario = ?";
            $types .= "i";
            $params[] = \intval($filters['id_usuario']);
        }
        if (!empty($filters['data'])) {
            $sqlExtras .= " AND ocorrencias.data_ocorrencia = ?";
            $types .= "s";
            $params[] = $filters['data'];
        }
        if (!empty($filters['id_ocorrencia'])) {
            $sqlExtras .= " AND ocorrencias.id_ocorrencia = ?";
            $types .= "i";
            $params[] = \intval($filters['id_ocorrencia']);
        }
        if (array_key_exists('status_ocorrencia', $filters) && $filters['status_ocorrencia'] !== '') {
            $sqlExtras .= " AND ocorrencias.status_ocorrencia = ?";
            $types .= "s";
            $params[] = (string) $filters['status_ocorrencia'];
        }
        if (isset($filters['penalidade'])) {
            $sqlExtras .= " AND ocorrencias.penalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['penalidade']);
        }
        if (!empty($filters['busca'])) {
            $sqlExtras .= " AND (ocorrencias.titulo_ocorrencia LIKE ? OR ocorrencias.descricao_ocorrencia LIKE ?)";
            $types .= "ss";
            $params[] = "%" . $filters['busca'] . "%";
            $params[] = "%" . $filters['busca'] . "%";
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosPartidas(array $filters): array
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        if (!empty($filters['id_jogo'])) {
            $sqlExtras .= " AND p.jogos_id_jogo = ?";
            $types .= "i";
            $params[] = \intval($filters['id_jogo']);
        }
        if (!empty($filters['id_equipe'])) {
            $sqlExtras .= " AND p.equipes_id_equipe = ?";
            $types .= "i";
            $params[] = \intval($filters['id_equipe']);
        }
        if (isset($filters['resultado_min'])) {
            $sqlExtras .= " AND p.resultado_partida >= ?";
            $types .= "i";
            $params[] = \intval($filters['resultado_min']);
        }
        if (!empty($filters['id_interclasse'])) {
            $sqlExtras .= " AND m.interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosTiposModalidades(array $filters)
    {
        $sqlExtras = "";
        $types = "";
        $params = [];
        if (!empty($filters['id_tipo_modalidade'])) {
            $sqlExtras .= " AND id_tipo_modalidade = ?";
            $types .= "i";
            $params[] = \intval($filters['id_tipo_modalidade']);
        }
        if (!empty($filters['busca'])) {
            $sqlExtras .= " AND nome_tipo_modalidade LIKE ?";
            $types .= "s";
            $params[] = "%" . $filters['busca'] . "%";
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
    public static function aplicarFiltrosTurmas(array $filters)
    {
        $sqlExtras = " AND turmas.status_turma = '1'";
        $types = "";
        $params = [];
        // Filtro por ID da Turma
        if (isset($filters['id_turma']) && $filters['id_turma'] !== '') {
            $sqlExtras .= " AND turmas.id_turma = ?";
            $types .= "i";
            $params[] = \intval($filters['id_turma']);
        }
        // Filtro por Interclasse
        if (isset($filters['id_interclasse']) && $filters['id_interclasse'] !== '') {
            $sqlExtras .= " AND turmas.interclasses_id_interclasse = ?";
            $types .= "i";
            $params[] = \intval($filters['id_interclasse']);
        }
        // Filtro por Categoria
        if (isset($filters['id_categoria']) && $filters['id_categoria'] !== '') {
            $sqlExtras .= " AND turmas.categorias_id_categoria = ?";
            $types .= "i";
            $params[] = \intval($filters['id_categoria']);
        }
        // Filtro por Turno (Ex: Manhã, Tarde)
        if (!empty($filters['turno'])) {
            $sqlExtras .= " AND turmas.turno_turma = ?";
            $types .= "s";
            $params[] = $filters['turno'];
        }
        // Filtro de Busca Textual
        if (!empty($filters['busca'])) {
            $sqlExtras .= " AND (turmas.nome_turma LIKE ? OR turmas.nome_fantasia_turma LIKE ?)";
            $types .= "ss";
            $busca = "%" . $filters['busca'] . "%";
            $params[] = $busca;
            $params[] = $busca;
        }
        return ['sql' => $sqlExtras, 'types' => $types, 'params' => $params];
    }
}
