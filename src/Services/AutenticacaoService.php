<?php

namespace Controla\Services;

use Controla\Email\EmailNaoEnviadoException;
use Controla\Email\Modelo;
use Controla\Email\Remetente;
use Controla\Models\Usuario;
use Controla\Utils\Exceptions\DadosInvalidosException;
use Controla\Utils\Exceptions\EmailNaoConfirmadoException;
// use Cubo\Security;
use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */
final class AutenticacaoService
{
    public const MINUTOS_CODIGO = 15;

    public const MAX_TENTATIVAS = 5;

    public const MIN_SENHA = 8;

    /** password_hash('hash-falso-do-controla'): so existe para o password_verify ter com quem comparar */
    private const HASH_FALSO = '$2y$10$mxWGQ6uBZdn6/mvuaro.ceD4Pj6qJ67NdwrmdLVtUMEfSL9cLLcxi';

    private const LOGIN_RECUSADO = 'Email ou senha incorretos.';

    public function __construct(
        private readonly Remetente $remetente,
        private readonly Modelo $modelo = new Modelo(),
    ) {}

    public function cadastroAberto(): bool
    {
        return Usuario::query()->count() === 0;
    }

    /**
     * @param array<string,mixed> $dados nome, email, senha e senha_confirmacao
     * @throws DadosInvalidosException
     * @throws RuntimeException Se ja existe uma conta.
     */
    public function cadastrar(array $dados): Usuario
    {
        if (!$this->cadastroAberto()) {
            throw new RuntimeException('Ja existe uma conta neste sistema.');
        }

        $usuario = new Usuario();
        $usuario->fill($this->normalizar($dados));

        $senha = (string) ($dados['senha'] ?? '');

        $this->validarCadastro($usuario, $senha, (string) ($dados['senha_confirmacao'] ?? ''));

        $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
        $usuario->email_confirmado = false;
        $usuario->save();

        return $usuario;
    }

    /**
     * @throws EmailNaoEnviadoException
     */
    public function enviarCodigo(Usuario $usuario): void
    {
        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $usuario->codigo = password_hash($codigo, PASSWORD_DEFAULT);
        $usuario->data_codigo_expira = Carbon::now()->addMinutes(self::MINUTOS_CODIGO);
        $usuario->num_tentativas = 0;
        $usuario->save();

        $dados = [
            'nome' => (string) $usuario->nome,
            'codigo' => $codigo,
            'minutos' => self::MINUTOS_CODIGO,
        ];

        $this->remetente->enviar(
            (string) $usuario->email,
            "Seu codigo do Controla: {$codigo}",
            $this->modelo->html('codigo', $dados),
            $this->modelo->texto('codigo', $dados),
        );
    }

    /**
     * @throws DadosInvalidosException Codigo errado, vencido ou tentativas esgotadas.
     * @throws RuntimeException Se o id nao aponta para uma conta.
     */
    public function confirmar(int $id, string $codigo): Usuario
    {
        $usuario = $this->encontrar($id);

        if ($usuario->email_confirmado) {
            return $usuario;
        }

        $this->validarCodigo($usuario, preg_replace('/\D/', '', $codigo) ?? '');

        $usuario->email_confirmado = true;
        $usuario->codigo = null;
        $usuario->data_codigo_expira = null;
        $usuario->num_tentativas = 0;
        $usuario->save();

        return $usuario;
    }

    /**
     * @throws DadosInvalidosException A mesma mensagem para email que nao existe e senha errada.
     * @throws EmailNaoConfirmadoException
     */
    public function entrar(string $email, string $senha): Usuario
    {
        $email = $this->normalizarEmail($email);

        if ($email === '' || $senha === '') {
            throw DadosInvalidosException::com(array_filter([
                'email' => $email === '' ? 'Informe o email.' : '',
                'senha' => $senha === '' ? 'Informe a senha.' : '',
            ]));
        }

        $usuario = Usuario::query()->porEmail($email)->first();

        $confere = password_verify($senha, $usuario?->senha ?? self::HASH_FALSO);

        if ($usuario === null || !$confere) {
            throw DadosInvalidosException::com(['email' => self::LOGIN_RECUSADO]);
        }

        if (!$usuario->email_confirmado) {
            throw new EmailNaoConfirmadoException($usuario);
        }

        if (password_needs_rehash((string) $usuario->senha, PASSWORD_DEFAULT)) {
            $usuario->senha = password_hash($senha, PASSWORD_DEFAULT);
            $usuario->save();
        }

        return $usuario;
    }

    /**
     * @throws RuntimeException Se o id nao aponta para uma conta.
     */
    public function encontrar(?int $id): Usuario
    {
        $usuario = $id === null ? null : Usuario::findById($id);

        if ($usuario === null) {
            throw new RuntimeException('Conta ' . ($id ?? '?') . ' nao encontrada.');
        }

        return $usuario;
    }

    /**
     * @param array<string,mixed> $dados
     * @return array<string,string>
     */
    private function normalizar(array $dados): array
    {
        return [
            'nome' => trim((string) ($dados['nome'] ?? '')),
            'email' => $this->normalizarEmail((string) ($dados['email'] ?? '')),
        ];
    }

    private function normalizarEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    /**
     * @throws DadosInvalidosException
     */
    private function validarCadastro(Usuario $usuario, string $senha, string $confirmacao): void
    {
        $erros = [];

        if ((string) $usuario->nome === '') {
            $erros['nome'] = 'Informe o seu nome.';
        }

        if (filter_var((string) $usuario->email, FILTER_VALIDATE_EMAIL) === false) {
            $erros['email'] = 'Informe um email valido.';
        }

        if (mb_strlen($senha) < self::MIN_SENHA) {
            $erros['senha'] = 'A senha precisa ter pelo menos ' . self::MIN_SENHA . ' caracteres.';
        } elseif ($senha !== $confirmacao) {
            $erros['senha_confirmacao'] = 'As duas senhas nao sao iguais.';
        }

        if ($erros !== []) {
            throw DadosInvalidosException::com($erros);
        }
    }

    /**
     * @throws DadosInvalidosException
     */
    private function validarCodigo(Usuario $usuario, string $codigo): void
    {
        if ($usuario->codigo === null || $usuario->data_codigo_expira === null) {
            throw DadosInvalidosException::com(['codigo' => 'Peca um codigo novo.']);
        }

        if ($usuario->num_tentativas >= self::MAX_TENTATIVAS) {
            throw DadosInvalidosException::com(['codigo' => 'Foram muitas tentativas erradas. Peca um codigo novo.']);
        }

        if ($usuario->data_codigo_expira->isPast()) {
            throw DadosInvalidosException::com(['codigo' => 'Esse codigo venceu. Peca um codigo novo.']);
        }

        if (password_verify($codigo, (string) $usuario->codigo)) {
            return;
        }

        $usuario->num_tentativas++;
        $usuario->save();

        $restam = self::MAX_TENTATIVAS - $usuario->num_tentativas;

        throw DadosInvalidosException::com([
            'codigo' => $restam > 0
                ? "Codigo errado. Restam {$restam} tentativas."
                : 'Codigo errado. Peca um codigo novo.',
        ]);
    }

}
