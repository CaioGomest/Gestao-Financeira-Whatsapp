<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../funcoes/admin_email.php';

function enviar_email($para, $assunto, $mensagem_html) {
    // Tenta enviar via SMTP configurado no admin
    try {
        global $database;
        if (isset($database)) {
            $cfg = obterConfigEmail();
            if (!empty($cfg['smtp_host']) && !empty($cfg['smtp_usuario'])) {
                $row = $database->select("SELECT valor FROM configuracoes_sistema WHERE chave = 'smtp_senha'");
                $cfg['smtp_senha'] = $row[0]['valor'] ?? '';
                $resultado = enviarSmtp($cfg, $para, $assunto, $mensagem_html);
                return $resultado['sucesso'];
            }
        }
    } catch (Exception $e) {
        // Fallback para mail() se SMTP falhar
    }

    // Fallback: PHP mail()
    $from = 'no-reply@local.test';
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from}\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();
    return @mail($para, $assunto, $mensagem_html, $headers);
}

function enviar_email_codigo($para, $codigo) {
    $assunto  = 'Código de verificação - Peak Finanças';
    $mensagem = '<div style="font-family:Segoe UI,Arial,sans-serif;font-size:16px;color:#111;">'
              . '<h2 style="color:#f59e0b;">Peak Finanças</h2>'
              . '<p>Seu código de verificação é:</p>'
              . '<h2 style="letter-spacing:6px;font-size:32px;color:#f59e0b;">' . htmlspecialchars($codigo) . '</h2>'
              . '<p>Ele expira em 15 minutos.</p>'
              . '<p style="color:#888;font-size:13px;">Se você não solicitou este código, ignore este e-mail.</p>'
              . '</div>';
    return enviar_email($para, $assunto, $mensagem);
}
?>
