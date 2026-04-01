<?php
require_once '../config/db.php';
header('Content-Type: application/json');

// Headers CORS recomendados
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

$method = $_SERVER['REQUEST_METHOD'];

// Lida com requisições preflight do CORS
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit();
}

switch ($method) {
    case 'GET':
        $id = isset($_GET['id_modalidade']) ? intval($_GET['id_modalidade']) : null;
        $ano = isset($_GET['ano']) ? intval($_GET['ano']) : null;

        // Adicionada a coluna categorias_id_categoria ao SELECT para refletir a estrutura real do banco
        $sql = "SELECT DISTINCT 
                    modalidades.id_modalidade, 
                    modalidades.nome_modalidade, 
                    modalidades.genero_modalidade,
                    modalidades.max_inscrito_modalidade, 
                    modalidades.categorias_id_categoria,
                    tipos_modalidades.nome_tipo_modalidade,
                    tipos_modalidades.id_tipo_modalidade
                FROM modalidades
                INNER JOIN tipos_modalidades ON tipos_modalidades.id_tipo_modalidade = modalidades.tipos_modalidades_id_tipo_modalidade";

        // Se houver filtro por ano, faz a junção com a tabela de jogos
        if ($ano) {
            $sql .= " INNER JOIN jogos ON jogos.modalidades_id_modalidade = modalidades.id_modalidade";
        }

        $conditions = [];
        if ($id) {
            $conditions[] = "modalidades.id_modalidade = $id";
        }
        if ($ano) {
            $conditions[] = "YEAR(jogos.data_jogo) = $ano";
        }

        if (count($conditions) > 0) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        try {
            $res = $conn->query($sql);
            $modalidades = [];

            if ($res) {
                while ($row = $res->fetch_assoc()) {
                    // Tipagem correta para JSON
                    $row['id_modalidade'] = intval($row['id_modalidade']);
                    $row['max_inscrito_modalidade'] = intval($row['max_inscrito_modalidade']);
                    $row['id_tipo_modalidade'] = intval($row['id_tipo_modalidade']);
                    if(isset($row['categorias_id_categoria'])) {
                        $row['categorias_id_categoria'] = intval($row['categorias_id_categoria']);
                    }
                    $modalidades[] = $row;
                }
            }
            echo json_encode($modalidades);

        } catch (mysqli_sql_exception $e) {
            // Captura de erro para evitar quebra do JSON em caso de falha SQL
            http_response_code(500);
            echo json_encode([
                "success" => false, 
                "message" => "Erro na query GET.",
                "error_details" => $e->getMessage()
            ]);
        }
        break;

    case 'POST':
        $data = json_decode(file_get_contents("php://input"));

        // CORREÇÃO: Exigindo agora o campo categorias_id_categoria que é obrigatório no banco
        if (!isset($data->nome_modalidade) || !isset($data->genero_modalidade) || !isset($data->tipos_modalidades_id_tipo_modalidade) || !isset($data->categorias_id_categoria)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "Os campos nome_modalidade, genero_modalidade, tipos_modalidades_id_tipo_modalidade e categorias_id_categoria são obrigatórios."
            ]);
            break;
        }

        $id_tipo_modalidade = intval($data->tipos_modalidades_id_tipo_modalidade);
        $id_categoria = intval($data->categorias_id_categoria);

        // VALIDAÇÃO (DEBUG): Verifica se os IDs recebidos são válidos (maiores que 0)
        if ($id_tipo_modalidade <= 0 || $id_categoria <= 0) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O ID do tipo de modalidade ou o ID da categoria são inválidos ou iguais a zero.",
                "valores_recebidos" => [
                    "tipos_modalidades_id_tipo_modalidade" => $data->tipos_modalidades_id_tipo_modalidade,
                    "categorias_id_categoria" => $data->categorias_id_categoria
                ]
            ]);
            break;
        }

        // VALIDAÇÃO: O banco de dados exige que genero_modalidade seja 'MASC', 'FEM' ou 'MISTO'
        $generos_permitidos = ['MASC', 'FEM', 'MISTO'];
        $genero_recebido = strtoupper(trim($data->genero_modalidade));
        
        if (!in_array($genero_recebido, $generos_permitidos)) {
            http_response_code(400);
            echo json_encode([
                "success" => false,
                "message" => "O gênero da modalidade deve ser 'MASC', 'FEM' ou 'MISTO'."
            ]);
            break;
        }

        $nome_modalidade = "'" . $conn->real_escape_string($data->nome_modalidade) . "'";
        $genero_modalidade = "'" . $conn->real_escape_string($genero_recebido) . "'";
        
        if (isset($data->max_inscrito_modalidade) && is_numeric($data->max_inscrito_modalidade)) {
            $max_inscrito_modalidade = intval($data->max_inscrito_modalidade);
        } else {
            $max_inscrito_modalidade = "NULL"; 
        }

        // CORREÇÃO: Incluída a coluna categorias_id_categoria e a sua respectiva variável no INSERT
        $sql = "INSERT INTO modalidades (nome_modalidade, genero_modalidade, max_inscrito_modalidade, tipos_modalidades_id_tipo_modalidade, categorias_id_categoria) 
                VALUES ($nome_modalidade, $genero_modalidade, $max_inscrito_modalidade, $id_tipo_modalidade, $id_categoria)";

        try {
            if ($conn->query($sql) === TRUE) {
                http_response_code(201); // 201 Created
                echo json_encode([
                    "success" => true,
                    "message" => "Modalidade cadastrada com sucesso",
                    "id_modalidade" => $conn->insert_id
                ]);
            }
        } catch (mysqli_sql_exception $e) {
            // Analisamos o erro para saber qual Foreign Key falhou (Categoria ou Tipo Modalidade)
            if (strpos($e->getMessage(), 'foreign key constraint fails') !== false) {
                http_response_code(400); 
                
                $msgErro = "Erro de integridade referencial. Verifique os IDs informados.";
                if (strpos($e->getMessage(), 'fk_modalidades_categorias1') !== false) {
                    $msgErro = "A Categoria informada (ID $id_categoria) não existe no banco de dados.";
                } elseif (strpos($e->getMessage(), 'fk_modalidades_tipos_modalidades1') !== false) {
                    $msgErro = "O Tipo de Modalidade informado (ID $id_tipo_modalidade) não existe no banco de dados.";
                }

                echo json_encode([
                    "success" => false,
                    "message" => $msgErro,
                    "query_executada" => $sql,
                    "erro_mysql" => $e->getMessage() 
                ]);
            } else {
                http_response_code(500); 
                echo json_encode([
                    "success" => false,
                    "message" => "Erro na execução da query.",
                    "query_executada" => $sql,
                    "erro_mysql" => $e->getMessage()
                ]);
            }
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(["success" => false, "message" => "Método HTTP não permitido."]);
        break;
}