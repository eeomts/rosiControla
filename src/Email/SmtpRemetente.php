<?php

namespace Controla\Email;

use Closure;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use PHPMailer\PHPMailer\PHPMailer;

/**
 * Entrega pelo SMTP da secao [email], com o PHPMailer.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class SmtpRemetente implements Remetente
{
    private const TIMEOUT = 15;

    private ?ConfiguracaoSmtp $config = null;

    /**
     * @param Closure(): ConfiguracaoSmtp $carregar so roda no primeiro envio, entao a tela
     *        de login abre mesmo com o [email] vazio
     */
    public function __construct(private readonly Closure $carregar) {}

    public static function daApp(): self
    {
        return new self(static fn (): ConfiguracaoSmtp => ConfiguracaoSmtp::daApp());
    }

    public function enviar(string $para, string $assunto, string $html, string $texto): void
    {
        $config = $this->config ??= ($this->carregar)();

        if (!$config->completa()) {
            throw EmailNaoEnviadoException::configuracaoIncompleta();
        }

        // true: o PHPMailer lanca excecao em vez de so devolver false
        $email = new PHPMailer(true);

        try {
            $email->isSMTP();
            $email->Host = $config->host;
            $email->Port = $config->porta;
            $email->SMTPAuth = true;
            $email->SMTPSecure = $config->seguranca === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $email->Username = $config->usuario;
            $email->Password = $config->senha;
            $email->CharSet = PHPMailer::CHARSET_UTF8;
            // o padrao e 300s: com o servidor fora, a tela ficaria 5 minutos girando
            $email->Timeout = self::TIMEOUT;

            $email->setFrom($config->remetente, $config->nomeRemetente);
            $email->addAddress($para);

            $email->isHTML(true);
            $email->Subject = $assunto;
            $email->Body = $html;
            $email->AltBody = $texto;

            $email->send();
        } catch (PHPMailerException $e) {
            throw EmailNaoEnviadoException::porCausaDe($e);
        }
    }
}
