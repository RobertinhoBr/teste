<?php
/**
 * SISTEMA ENESITO - API REST PARA CARGA DOS DADOS DO MYSQL
 * Host: localhost | Banco: u879188518_enesito1
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');

$db_host = 'localhost';
$db_name = 'u879188518_enesito1';
$db_user = 'u879188518_enesito1';
$db_pass = 'Flamengo@2029';

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    $action = $_GET['action'] ?? 'list_convenios';

    if ($action === 'get_session') {
        session_start();
        if (!empty($_SESSION['usuario_logado'])) {
            echo json_encode([
                "success" => true,
                "authenticated" => true,
                "user" => $_SESSION['usuario_logado']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                "success" => true,
                "authenticated" => false
            ]);
        }
        exit;
    }

    if ($action === 'list_convenios') {
        $stmt = $pdo->query("SELECT * FROM convenios ORDER BY valor_global DESC");
        $dados = $stmt->fetchAll();
        echo json_encode([
            "success" => true,
            "total" => count($dados),
            "data" => $dados
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($action === 'check_db') {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM convenios");
        $count = $stmt->fetch()['total'];
        echo json_encode([
            "success" => true,
            "connected" => true,
            "total_convenios" => (int)$count
        ]);
        exit;
    }

    if ($action === 'update_convenio' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['convenio'])) {
            echo json_encode(["success" => false, "message" => "Dados incompletos."]);
            exit;
        }

        $stmt = $pdo->prepare("UPDATE convenios SET 
            objeto = :objeto,
            status = :status,
            valor_global = :valor_global,
            valor_repasse = :valor_repasse,
            valor_contrapartida = :valor_contrapartida,
            valor_desembolsado = :valor_desembolsado,
            saldo_a_desembolsar = :saldo_a_desembolsar,
            saldo_em_conta = :saldo_em_conta,
            fim_vigencia = :fim_vigencia
            WHERE nr_convenio = :convenio");

        $stmt->execute([
            ':objeto' => $input['objeto'] ?? '',
            ':status' => $input['status'] ?? '',
            ':valor_global' => (float)($input['valorGlobal'] ?? 0),
            ':valor_repasse' => (float)($input['valorRepasse'] ?? 0),
            ':valor_contrapartida' => (float)($input['contrapartida'] ?? 0),
            ':valor_desembolsado' => (float)($input['valorDesembolsado'] ?? 0),
            ':saldo_a_desembolsar' => (float)($input['saldoADesembolsar'] ?? 0),
            ':saldo_em_conta' => (float)($input['saldoEmConta'] ?? 0),
            ':fim_vigencia' => !empty($input['fimVigencia']) ? substr($input['fimVigencia'], 0, 10) : null,
            ':convenio' => $input['convenio']
        ]);

        echo json_encode(["success" => true, "message" => "Convênio atualizado com sucesso."]);
        exit;
    }

    if ($action === 'save_user' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['login'])) {
            echo json_encode(["success" => false, "message" => "Login obrigatório."]);
            exit;
        }

        $pdo->exec("CREATE TABLE IF NOT EXISTS `usuarios` (
          `id` INT(11) NOT NULL AUTO_INCREMENT,
          `nome` VARCHAR(150) NOT NULL,
          `login` VARCHAR(100) NOT NULL,
          `senha` VARCHAR(255) NOT NULL,
          `perfil` VARCHAR(50) NOT NULL DEFAULT 'Administrador',
          `foto` TEXT DEFAULT NULL,
          `ativo` TINYINT(1) NOT NULL DEFAULT 1,
          `criado_em` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_login` (`login`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, login, senha, perfil, foto, ativo) 
            VALUES (:nome, :login, :senha, :perfil, :foto, 1)
            ON DUPLICATE KEY UPDATE nome=VALUES(nome), senha=VALUES(senha), perfil=VALUES(perfil), foto=VALUES(foto)");
        $stmt->execute([
            ':nome' => $input['nome'] ?? 'Usuário',
            ':login' => strtolower(trim($input['login'])),
            ':senha' => $input['senha'] ?? '123456',
            ':perfil' => $input['perfil'] ?? 'Administrador',
            ':foto' => $input['foto'] ?? ''
        ]);

        echo json_encode(["success" => true, "message" => "Usuário registrado com sucesso no banco de dados."]);
        exit;
    }

    if ($action === 'list_users') {
        try {
            $stmt = $pdo->query("SELECT id, nome, login, perfil, foto, criado_em FROM usuarios ORDER BY id ASC");
            echo json_encode(["success" => true, "users" => $stmt->fetchAll()]);
            exit;
        } catch (Exception $ex) {
            echo json_encode(["success" => true, "users" => []]);
            exit;
        }
    }

    echo json_encode(["success" => false, "message" => "Ação não reconhecida."]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
