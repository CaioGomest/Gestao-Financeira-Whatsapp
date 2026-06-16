<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../funcoes/usuario.php';

if (!function_exists('obterConfigEmail')) {
function obterConfigEmail() {
    global $database;
    $chaves = ['smtp_host','smtp_port','smtp_encryption','smtp_usuario','smtp_from','smtp_from_nome'];
    $rows = $database->select(
        "SELECT chave, valor FROM configuracoes_sistema WHERE chave IN ('" . implode("','", $chaves) . "')"
    );
    $cfg = [];
    foreach ($rows as $r) { $cfg[$r['chave']] = $r['valor']; }
    return $cfg;
}
}

if (!function_exists('salvarConfigEmail')) {
function salvarConfigEmail($dados) {
    global $database;
    $permitidos = ['smtp_host','smtp_port','smtp_encryption','smtp_usuario','smtp_senha','smtp_from','smtp_from_nome'];
    foreach ($permitidos as $k) {
        if (!isset($dados[$k])) continue;
        // Não sobrescrever senha se vier vazia (campo deixado em branco)
        if ($k === 'smtp_senha' && $dados[$k] === '') continue;
        $exist = $database->select("SELECT 1 FROM configuracoes_sistema WHERE chave = ?", [$k]);
        if (empty($exist)) {
            $database->insert("INSERT INTO configuracoes_sistema (chave, valor) VALUES (?, ?)", [$k, $dados[$k]]);
        } else {
            $database->update("UPDATE configuracoes_sistema SET valor = ?, atualizado_em = NOW() WHERE chave = ?", [$dados[$k], $k]);
        }
    }
    return true;
}
}

if (!function_exists('enviarSmtp')) {
/**
 * Envia e-mail via SMTP usando sockets PHP nativos.
 * Suporta TLS (STARTTLS), SSL e sem criptografia.
 */
function enviarSmtp($cfg, $para, $assunto, $corpo_html) {
    $host       = $cfg['smtp_host']       ?? '';
    $port       = intval($cfg['smtp_port'] ?? 587);
    $enc        = $cfg['smtp_encryption'] ?? 'tls';
    $usuario    = $cfg['smtp_usuario']    ?? '';
    $senha      = $cfg['smtp_senha']      ?? '';
    $from       = $cfg['smtp_from']       ?? $usuario;
    $from_nome  = $cfg['smtp_from_nome']  ?? 'Peak Finanças';

    if (!$host || !$usuario) {
        return ['sucesso' => false, 'erro' => 'SMTP não configurado'];
    }

    $timeout = 15;
    $errno = 0; $errstr = '';

    if ($enc === 'ssl') {
        $socket = @fsockopen("ssl://{$host}", $port, $errno, $errstr, $timeout);
    } else {
        $socket = @fsockopen($host, $port, $errno, $errstr, $timeout);
    }

    if (!$socket) {
        return ['sucesso' => false, 'erro' => "Não foi possível conectar ao servidor SMTP ({$host}:{$port}): {$errstr}"];
    }

    stream_set_timeout($socket, $timeout);

    $ler = function() use ($socket) {
        $resp = '';
        while ($linha = fgets($socket, 515)) {
            $resp .= $linha;
            if (substr($linha, 3, 1) === ' ') break;
        }
        return $resp;
    };

    $enviar = function($cmd) use ($socket) {
        fputs($socket, $cmd . "\r\n");
    };

    $resp = $ler();
    if (substr($resp, 0, 3) !== '220') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "Saudação SMTP inesperada: {$resp}"];
    }

    $enviar("EHLO " . (gethostname() ?: 'localhost'));
    $resp = $ler();

    // STARTTLS para TLS
    if ($enc === 'tls') {
        $enviar("STARTTLS");
        $resp = $ler();
        if (substr($resp, 0, 3) !== '220') {
            fclose($socket);
            return ['sucesso' => false, 'erro' => "STARTTLS falhou: {$resp}"];
        }
        if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($socket);
            return ['sucesso' => false, 'erro' => 'Falha ao ativar TLS'];
        }
        $enviar("EHLO " . (gethostname() ?: 'localhost'));
        $resp = $ler();
    }

    // Autenticação LOGIN
    $enviar("AUTH LOGIN");
    $resp = $ler();
    if (substr($resp, 0, 3) !== '334') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "AUTH LOGIN não suportado: {$resp}"];
    }
    $enviar(base64_encode($usuario));
    $resp = $ler();
    if (substr($resp, 0, 3) !== '334') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "Usuário rejeitado: {$resp}"];
    }
    $enviar(base64_encode($senha));
    $resp = $ler();
    if (substr($resp, 0, 3) !== '235') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "Autenticação falhou. Verifique usuário/senha: {$resp}"];
    }

    // Envelope
    $enviar("MAIL FROM:<{$from}>");
    $resp = $ler();
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "MAIL FROM rejeitado: {$resp}"];
    }

    $enviar("RCPT TO:<{$para}>");
    $resp = $ler();
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "RCPT TO rejeitado: {$resp}"];
    }

    $enviar("DATA");
    $resp = $ler();
    if (substr($resp, 0, 3) !== '354') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "DATA rejeitado: {$resp}"];
    }

    $nomeEnc  = '=?UTF-8?B?' . base64_encode($from_nome) . '?=';
    $assuntoEnc = '=?UTF-8?B?' . base64_encode($assunto) . '?=';
    $boundary = uniqid('peak_');
    $headers  = "From: {$nomeEnc} <{$from}>\r\n";
    $headers .= "To: {$para}\r\n";
    $headers .= "Subject: {$assuntoEnc}\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "Content-Transfer-Encoding: base64\r\n";
    $headers .= "X-Mailer: Peak/1.0\r\n";

    $corpoCodificado = chunk_split(base64_encode($corpo_html));

    $enviar($headers . "\r\n" . $corpoCodificado . "\r\n.");
    $resp = $ler();
    if (substr($resp, 0, 3) !== '250') {
        fclose($socket);
        return ['sucesso' => false, 'erro' => "Mensagem rejeitada: {$resp}"];
    }

    $enviar("QUIT");
    fclose($socket);
    return ['sucesso' => true];
}
}

if (isset($_GET['api']) && $_GET['api'] === 'admin_email') {
    verificarLogin();
    if (!isset($_SESSION['perfil']) || $_SESSION['perfil'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['erro' => 'acesso_negado']);
        exit;
    }

    header('Content-Type: application/json');
    $acao = $_GET['acao'] ?? '';

    switch ($acao) {
        case 'obter':
            echo json_encode(obterConfigEmail());
            break;

        case 'salvar':
            $dados = [
                'smtp_host'       => trim($_POST['smtp_host']       ?? ''),
                'smtp_port'       => trim($_POST['smtp_port']       ?? '587'),
                'smtp_encryption' => trim($_POST['smtp_encryption'] ?? 'tls'),
                'smtp_usuario'    => trim($_POST['smtp_usuario']    ?? ''),
                'smtp_senha'      => $_POST['smtp_senha']           ?? '',
                'smtp_from'       => trim($_POST['smtp_from']       ?? ''),
                'smtp_from_nome'  => trim($_POST['smtp_from_nome']  ?? ''),
            ];
            echo json_encode(['sucesso' => salvarConfigEmail($dados)]);
            break;

        case 'testar':
            $destino = trim($_POST['destino'] ?? '');
            if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) {
                echo json_encode(['sucesso' => false, 'erro' => 'E-mail de destino inválido']);
                break;
            }
            $cfg = obterConfigEmail();
            // Buscar senha do banco (não é retornada no obter por segurança)
            global $database;
            $row = $database->select("SELECT valor FROM configuracoes_sistema WHERE chave = 'smtp_senha'");
            $cfg['smtp_senha'] = $row[0]['valor'] ?? '';

            $assunto = 'Teste de e-mail - Peak Finanças';
            $corpo   = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:15px;color:#111;">'
                     . '<h2 style="color:#f59e0b;">Peak Finanças</h2>'
                     . '<p>Este é um e-mail de teste enviado pelo painel administrativo.</p>'
                     . '<p>Se você recebeu esta mensagem, o SMTP está configurado corretamente.</p>'
                     . '</div>';

            $resultado = enviarSmtp($cfg, $destino, $assunto, $corpo);
            echo json_encode($resultado);
            break;

        default:
            http_response_code(400);
            echo json_encode(['erro' => 'acao_invalida']);
    }
    exit;
}
?>
