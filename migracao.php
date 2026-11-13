<?php
/**
 * SISTEMA RENESITO - SCRIPT DE MIGRAÇÃO E CARGA NO BANCO DE DADOS
 * Host: localhost
 * Banco: u879188518_enesito1
 * Usuário: u879188518_enesito1
 * Analista: ExcelentE, Roberto Cardoso
 */

header('Content-Type: text/html; charset=utf-8');

$db_host = 'localhost';
$db_name = 'u879188518_enesito1';
$db_user = 'u879188518_enesito1';
$db_pass = 'Flamengo@2029';

$status_log = [];
$success = false;

try {
    $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);

    $status_log[] = ["tipo" => "ok", "msg" => "Conexão estabelecida com sucesso no banco '{$db_name}' em '{$db_host}'."];

    // Ler e executar o arquivo SQL
    $sql_file = __DIR__ . '/banco_renesito.sql';
    if (!file_exists($sql_file)) {
        $sql_file = __DIR__ . '/banco_enesito.sql';
    }
    if (!file_exists($sql_file)) {
        throw new Exception("Arquivo banco_renesito.sql não encontrado na mesma pasta do script.");
    }

    $sql_content = file_get_contents($sql_file);
    
    // Execução em lotes / multi-query
    $pdo->exec($sql_content);

    // Contagem de verificação
    $stmt1 = $pdo->query("SELECT COUNT(*) as total FROM convenios");
    $total_convenios = $stmt1->fetch()['total'];

    $stmt2 = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $total_usuarios = $stmt2->fetch()['total'];

    $stmt3 = $pdo->query("SELECT SUM(valor_global) as soma_global, SUM(valor_repasse) as soma_repasse, SUM(valor_desembolsado) as soma_desemb FROM convenios");
    $fin = $stmt3->fetch();

    $status_log[] = ["tipo" => "ok", "msg" => "Tabelas 'usuarios' e 'convenios' criadas com sucesso!"];
    $status_log[] = ["tipo" => "ok", "msg" => "Total de instrumentos inseridos em 'convenios': <strong>{$total_convenios}</strong>"];
    $status_log[] = ["tipo" => "ok", "msg" => "Total de usuários administrativos configurados: <strong>{$total_usuarios}</strong>"];
    $status_log[] = ["tipo" => "ok", "msg" => "Volume Global Consolidado no Banco: <strong>R$ " . number_format($fin['soma_global'], 2, ',', '.') . "</strong>"];
    $status_log[] = ["tipo" => "ok", "msg" => "Repasse Federal Acordado no Banco: <strong>R$ " . number_format($fin['soma_repasse'], 2, ',', '.') . "</strong>"];
    $status_log[] = ["tipo" => "ok", "msg" => "Recursos Desembolsados no Banco: <strong>R$ " . number_format($fin['soma_desemb'], 2, ',', '.') . "</strong>"];

    $success = true;

} catch (Exception $e) {
    $status_log[] = ["tipo" => "erro", "msg" => "Erro na execução da migração: " . $e->getMessage()];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Migração de Banco de Dados | Sistema Renesito</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8fafc;
      color: #0f172a;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      margin: 0;
      padding: 20px;
    }
    .card {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      max-width: 680px;
      width: 100%;
      padding: 32px;
      box-shadow: 0 10px 15px -3px rgba(0,0,0,0.06);
    }
    .badge {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 9999px;
      font-size: 0.75rem;
      font-weight: 700;
      margin-bottom: 12px;
    }
    .badge-ok { background: #dcfce7; color: #166534; }
    .badge-erro { background: #fee2e2; color: #991b1b; }
    h1 { font-size: 1.35rem; color: #1e3a8a; margin-bottom: 6px; }
    p { font-size: 0.88rem; color: #64748b; margin-bottom: 20px; }
    .log-list {
      list-style: none;
      padding: 0;
      margin: 0 0 24px 0;
      border-top: 1px solid #e2e8f0;
    }
    .log-item {
      padding: 10px 0;
      border-bottom: 1px solid #f1f5f9;
      font-size: 0.85rem;
      display: flex;
      align-items: flex-start;
      gap: 10px;
    }
    .log-item.ok { color: #0f172a; }
    .log-item.erro { color: #b91c1c; font-weight: 600; }
    .btn {
      display: inline-block;
      background: #1e3a8a;
      color: #fff;
      padding: 10px 20px;
      text-decoration: none;
      border-radius: 6px;
      font-weight: 600;
      font-size: 0.86rem;
    }
    .footer-wm {
      margin-top: 24px;
      padding-top: 16px;
      border-top: 1px solid #e2e8f0;
      font-size: 0.78rem;
      color: #94a3b8;
      display: flex;
      justify-content: space-between;
    }
  </style>
</head>
<body>
  <div class="card">
    <span class="badge <?= $success ? 'badge-ok' : 'badge-erro' ?>">
      <?= $success ? 'MIGRAÇÃO EXECUTADA COM SUCESSO' : 'ATENÇÃO NA MIGRAÇÃO' ?>
    </span>
    <h1>Migração do Banco de Dados — Sistema Renesito</h1>
    <p>Execução de estruturação de tabelas e carga dos 70 instrumentos da base oficial.</p>

    <ul class="log-list">
      <?php foreach ($status_log as $log): ?>
        <li class="log-item <?= $log['tipo'] ?>">
          <span><?= $log['tipo'] === 'ok' ? '✅' : '❌' ?></span>
          <div><?= $log['msg'] ?></div>
        </li>
      <?php endforeach; ?>
    </ul>

    <?php if ($success): ?>
      <a href="index.html" class="btn">Abrir Painel Sistema Renesito &rarr;</a>
    <?php endif; ?>

    <div class="footer-wm">
      <span>Banco: <code><?= htmlspecialchars($db_name) ?></code></span>
      <span>Responsável: <strong>Roberto Cardoso</strong></span>
    </div>
  </div>
</body>
</html>
