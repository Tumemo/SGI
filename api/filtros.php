<?php
// eu criei falando que ia facilitar, n sei c me arrependo desta decisão

// 14/04/2026 TUDO APROVADO, está criação é incrivel

function aplicarFiltrosArtilharia() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    $ano = isset($_GET['ano']) ? intval($_GET['ano']) : date('Y');
    $sqlExtras .= " AND YEAR(jogos.data_jogo) = ?";
    $types .= "i";
    $params[] = $ano;

    if (!empty($_GET['id_modalidade'])) {
        $sqlExtras .= " AND modalidades.id_modalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['id_modalidade']);
    }

    if (!empty($_GET['genero'])) {
        $sqlExtras .= " AND usuarios.genero_usuario = ?";
        $types .= "s";
        $params[] = $_GET['genero'];
    }

    if (!empty($_GET['id_turma'])) {
        $sqlExtras .= " AND turmas.id_turma = ?";
        $types .= "i";
        $params[] = intval($_GET['id_turma']);
    }

    if (!empty($_GET['turno'])) {
        $sqlExtras .= " AND turmas.turno_turma = ?";
        $types .= "s";
        $params[] = $_GET['turno'];
    }

    if (!empty($_GET['id_categoria'])) {
        $sqlExtras .= " AND categorias.id_categoria = ?";
        $types .= "i";
        $params[] = intval($_GET['id_categoria']);
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosEquipes() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_equipe'])) {
        $sqlExtras .= " AND equipes.id_equipe = ?";
        $types .= "i";
        $params[] = intval($_GET['id_equipe']);
    }

    if (!empty($_GET['id_turma'])) {
        $sqlExtras .= " AND equipes.turmas_id_turma = ?";
        $types .= "i";
        $params[] = intval($_GET['id_turma']);
    }

    if (!empty($_GET['ano'])) {
        $sqlExtras .= " AND YEAR(interclasses.ano_interclasse) = ?";
        $types .= "i";
        $params[] = intval($_GET['ano']);
    }

    if (!empty($_GET['id_modalidade'])) {
        $sqlExtras .= " AND equipes.modalidades_id_modalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['id_modalidade']);
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosInterclasse() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_interclasse'])) {
        $sqlExtras .= " AND id_interclasse = ?";
        $types .= "i";
        $params[] = intval($_GET['id_interclasse']);
    }

    if (!empty($_GET['ano'])) {
        $sqlExtras .= " AND YEAR(ano_interclasse) = ?";
        $types .= "i";
        $params[] = intval($_GET['ano']);
    }

    if (!empty($_GET['busca'])) {
        $sqlExtras .= " AND nome_interclasse LIKE ?";
        $types .= "s";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosJogos() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_jogo'])) {
        $sqlExtras .= " AND jogos.id_jogo = ?";
        $types .= "i";
        $params[] = intval($_GET['id_jogo']);
    }

    if (!empty($_GET['id_modalidade'])) {
        $sqlExtras .= " AND jogos.modalidades_id_modalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['id_modalidade']);
    }

    if (!empty($_GET['id_local'])) {
        $sqlExtras .= " AND jogos.locais_id_local = ?";
        $types .= "i";
        $params[] = intval($_GET['id_local']);
    }

    if (!empty($_GET['data'])) {
        $sqlExtras .= " AND jogos.data_jogo = ?";
        $types .= "s";
        $params[] = $_GET['data'];
    }

    if (!empty($_GET['status'])) {
        $sqlExtras .= " AND jogos.status_jogo = ?";
        $types .= "s";
        $params[] = $_GET['status'];
    }

    if (!empty($_GET['ano'])) {
        $sqlExtras .= " AND YEAR(interclasses.ano_interclasse) = ?";
        $types .= "i";
        $params[] = intval($_GET['ano']);
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosModalidades() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_modalidade'])) {
        $sqlExtras .= " AND modalidades.id_modalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['id_modalidade']);
    }

    if (!empty($_GET['id_categoria'])) {
        $sqlExtras .= " AND modalidades.categorias_id_categoria = ?";
        $types .= "i";
        $params[] = intval($_GET['id_categoria']);
    }

    if (!empty($_GET['id_tipo_modalidade'])) {
        $sqlExtras .= " AND modalidades.tipos_modalidades_id_tipo_modalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['id_tipo_modalidade']);
    }

    if (!empty($_GET['genero'])) {
        $sqlExtras .= " AND modalidades.genero_modalidade = ?";
        $types .= "s";
        $params[] = strtoupper($_GET['genero']);
    }

    if (!empty($_GET['ano'])) {
        $sqlExtras .= " AND YEAR(jogos.data_jogo) = ?";
        $types .= "i";
        $params[] = intval($_GET['ano']);
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosCategorias() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_categoria'])) {
        $sqlExtras .= " AND id_categoria = ?";
        $types .= "i";
        $params[] = intval($_GET['id_categoria']);
    }

    if (!empty($_GET['busca'])) {
        $sqlExtras .= " AND nome_categoria LIKE ?";
        $types .= "s";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosLocais() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_local'])) {
        $sqlExtras .= " AND id_local = ?";
        $types .= "i";
        $params[] = intval($_GET['id_local']);
    }

    if (isset($_GET['disponivel'])) {
        $sqlExtras .= " AND disponivel_local = ?";
        $types .= "s";
        $params[] = $_GET['disponivel'];
    }

    if (!empty($_GET['busca'])) {
        $sqlExtras .= " AND nome_local LIKE ?";
        $types .= "s";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosOcorrencias() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_usuario'])) {
        $sqlExtras .= " AND ocorrencias.usuarios_id_usuario = ?";
        $types .= "i";
        $params[] = intval($_GET['id_usuario']);
    }

    if (!empty($_GET['data'])) {
        $sqlExtras .= " AND ocorrencias.data_ocorrecia = ?";
        $types .= "s";
        $params[] = $_GET['data'];
    }

    if (isset($_GET['penalidade'])) {
        $sqlExtras .= " AND ocorrencias.penalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['penalidade']);
    }

    if (!empty($_GET['busca'])) {
        $sqlExtras .= " AND (ocorrencias.titulo_ocorrecia LIKE ? OR ocorrencias.descricao_ocorrecia LIKE ?)";
        $types .= "ss";
        $params[] = "%" . $_GET['busca'] . "%";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosPartidas() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_jogo'])) {
        $sqlExtras .= " AND p.jogos_id_jogo = ?";
        $types .= "i";
        $params[] = intval($_GET['id_jogo']);
    }

    if (!empty($_GET['id_equipe'])) {
        $sqlExtras .= " AND p.equipes_id_equipe = ?";
        $types .= "i";
        $params[] = intval($_GET['id_equipe']);
    }

    if (isset($_GET['resultado_min'])) {
        $sqlExtras .= " AND p.resultado_partida >= ?";
        $types .= "i";
        $params[] = intval($_GET['resultado_min']);
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosTiposModalidades() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_tipo_modalidade'])) {
        $sqlExtras .= " AND id_tipo_modalidade = ?";
        $types .= "i";
        $params[] = intval($_GET['id_tipo_modalidade']);
    }

    if (!empty($_GET['busca'])) {
        $sqlExtras .= " AND nome_tipo_modalidade LIKE ?";
        $types .= "s";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}

function aplicarFiltrosTurmas() {
    $sqlExtras = "";
    $types = "";
    $params = [];

    if (!empty($_GET['id_turma'])) {
        $sqlExtras .= " AND turmas.id_turma = ?";
        $types .= "i";
        $params[] = intval($_GET['id_turma']);
    }

    if (!empty($_GET['id_interclasse'])) {
        $sqlExtras .= " AND turmas.interclasses_id_interclasse = ?";
        $types .= "i";
        $params[] = intval($_GET['id_interclasse']);
    }

    if (!empty($_GET['id_categoria'])) {
        $sqlExtras .= " AND turmas.categorias_id_categoria = ?";
        $types .= "i";
        $params[] = intval($_GET['id_categoria']);
    }

    if (!empty($_GET['turno'])) {
        $sqlExtras .= " AND turmas.turno_turma = ?";
        $types .= "s";
        $params[] = $_GET['turno'];
    }

    if (!empty($_GET['busca'])) {
        $sqlExtras .= " AND (turmas.nome_turma LIKE ? OR turmas.nome_fantasia_turma LIKE ?)";
        $types .= "ss";
        $params[] = "%" . $_GET['busca'] . "%";
        $params[] = "%" . $_GET['busca'] . "%";
    }

    return [
        'sql' => $sqlExtras,
        'types' => $types,
        'params' => $params
    ];
}