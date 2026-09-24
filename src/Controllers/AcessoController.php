<?php

namespace Controla\Controllers;

use Controla\Controllers\Base\FeatureController;
use Controla\Email\EmailNaoEnviadoException;
use Controla\Email\SmtpRemetente;
use Controla\Middleware\AutenticacaoMiddleware;
use Controla\Models\Usuario;
use Controla\Services\AutenticacaoService;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\EmailNaoConfirmadoException;
use Controla\Utils\Redirecionamento;
use Controla\Utils\Sessao;
use Controla\Views\DefaultView;
use RuntimeException;

/**
 * Login, cadastro da primeira conta e confirmacao do email.
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class AcessoController extends FeatureController
{
    private const URL_INICIO = '/';

    private const URL_CADASTRO = '/cadastro';

    private const URL_CONFIRMACAO = '/confirmacao';

    private AutenticacaoService $service;

    private Sessao $sessao;

    protected function iniciar(): void
    {
        $this->service = new AutenticacaoService(SmtpRemetente::daApp());
        $this->sessao = Sessao::daGlobal();

        $this->setView(new DefaultView(DefaultView::LAYOUT_ACESSO));
    }

    # ------------------------------------------------------------------ LOGIN

    public function login(): void
    {
        $this->sairSeJaEntrou();

        // trocado pelo botao "Criar conta" na propria tela
        // if ($this->service->cadastroAberto()) {
        //     Redirecionamento::para(self::URL_CADASTRO)->enviar();
        // }

        $this->telaLogin('', []);
    }

    public function entrar(): void
    {
        $email = $this->request->texto('email');

        try {
            $usuario = $this->service->entrar($email, $this->request->texto('senha'));
        } catch (DadosInvalidosException $e) {
            $this->telaLogin($email, $e->erros());

            return;
        } catch (EmailNaoConfirmadoException $e) {
            $this->sessao->aguardarConfirmacao($e->usuario);
            $this->mandarCodigo($e->usuario);

            Redirecionamento::para(self::URL_CONFIRMACAO)->enviar();
        }

        $this->sessao->entrar($usuario);

        Redirecionamento::para(self::URL_INICIO)->enviar();
    }

    public function sair(): void
    {
        $this->sessao->sair();

        $this->flash->sucesso('Voce saiu do sistema.');
        Redirecionamento::para(AutenticacaoMiddleware::URL_LOGIN)->enviar();
    }

    # --------------------------------------------------------------- CADASTRO

    public function cadastro(): void
    {
        $this->sairSeJaEntrou();
        $this->sairSeCadastroFechou();

        $this->telaCadastro(['nome' => '', 'email' => ''], []);
    }

    public function cadastrar(): void
    {
        $this->sairSeCadastroFechou();

        try {
            $usuario = $this->service->cadastrar($this->request->corpo());
        } catch (DadosInvalidosException $e) {
            // a senha nao volta para o form: ela digita de novo
            $this->telaCadastro([
                'nome' => $this->request->texto('nome'),
                'email' => $this->request->texto('email'),
            ], $e->erros());

            return;
        } catch (RuntimeException) {
            // outra aba cadastrou primeiro
            Redirecionamento::para(AutenticacaoMiddleware::URL_LOGIN)->enviar();
        }

        $this->sessao->aguardarConfirmacao($usuario);
        $this->mandarCodigo($usuario);

        Redirecionamento::para(self::URL_CONFIRMACAO)->enviar();
    }

    # ------------------------------------------------------------ CONFIRMACAO

    public function confirmacao(): void
    {
        $this->telaConfirmacao($this->pendente(), []);
    }

    public function confirmar(): void
    {
        $usuario = $this->pendente();

        try {
            $usuario = $this->service->confirmar((int) $usuario->id, $this->request->texto('codigo'));
        } catch (DadosInvalidosException $e) {
            $this->telaConfirmacao($usuario, $e->erros());

            return;
        }

        $this->sessao->entrar($usuario);

        $this->flash->sucesso("Email confirmado. Boas-vindas, {$usuario->nome}!");
        Redirecionamento::para(self::URL_INICIO)->enviar();
    }

    public function reenviar(): void
    {
        $this->mandarCodigo($this->pendente());

        Redirecionamento::para(self::URL_CONFIRMACAO)->enviar();
    }

    # ---------------------------------------------------------------- APOIO

    /** Quem chega em /confirmacao sem ter cadastrado ou tentado entrar volta para o login. */
    private function pendente(): Usuario
    {
        try {
            return $this->service->encontrar($this->sessao->pendente());
        } catch (RuntimeException) {
            Redirecionamento::para(AutenticacaoMiddleware::URL_LOGIN)->enviar();
        }
    }

    /** O recado vai no flash: quem chama sempre redireciona para a confirmacao. */
    private function mandarCodigo(Usuario $usuario): void
    {
        try {
            $this->service->enviarCodigo($usuario);
            $this->flash->sucesso("Mandei um codigo de 6 digitos para {$usuario->emailMascarado()}.");
        } catch (EmailNaoEnviadoException $e) {
            error_log('[Controla] ' . $e->getMessage());
            $this->flash->erro('Nao consegui mandar o email com o codigo. Tente reenviar daqui a pouco.');
        }
    }

    private function sairSeJaEntrou(): void
    {
        if ($this->sessao->logada()) {
            Redirecionamento::para(self::URL_INICIO)->enviar();
        }
    }

    private function sairSeCadastroFechou(): void
    {
        if (!$this->service->cadastroAberto()) {
            Redirecionamento::para(AutenticacaoMiddleware::URL_LOGIN)->enviar();
        }
    }

    /**
     * @param array<string,string> $erros campo => mensagem
     */
    private function telaLogin(string $email, array $erros): void
    {
        $this->pagina('Entrar', 'acesso/login.php', [
            'valores' => ['email' => $email],
            'erros' => $erros,
            // o botao some junto com a trava: depois da primeira conta, /cadastro nao abre
            'cadastro_aberto' => $this->service->cadastroAberto(),
        ]);
    }

    /**
     * @param array<string,string> $valores
     * @param array<string,string> $erros campo => mensagem
     */
    private function telaCadastro(array $valores, array $erros): void
    {
        $this->pagina('Criar a conta', 'acesso/cadastro.php', [
            'valores' => $valores,
            'erros' => $erros,
            'min_senha' => AutenticacaoService::MIN_SENHA,
        ]);
    }

    /**
     * @param array<string,string> $erros campo => mensagem
     */
    private function telaConfirmacao(Usuario $usuario, array $erros): void
    {
        $this->pagina('Confirme o email', 'acesso/confirmacao.php', [
            'email' => $usuario->emailMascarado(),
            'erros' => $erros,
            'minutos' => AutenticacaoService::MINUTOS_CODIGO,
        ]);
    }
}
