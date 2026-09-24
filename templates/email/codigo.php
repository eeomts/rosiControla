<?php

/**
 * Email com o codigo de confirmacao. Tabela e estilo inline: cliente de email
 * nao le var(), flex, box-shadow nem SVG.
 *
 * @var string $nome
 * @var string $codigo
 * @var int $minutos
 */

use Cubo\Security;

$tinta = '#2a2623';
$tintaSuave = '#675e54';
$sans = "'Sora', Arial, Helvetica, sans-serif";
$display = "'Idiqlat', Georgia, 'Times New Roman', serif";

?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seu codigo do Controla</title>
</head>

<body style="margin:0; padding:0; background:#fff1e8;">

    <!-- o que a caixa de entrada mostra embaixo do assunto -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0;">
        Seu codigo: <?= Security::escape($codigo) ?>. Vale por <?= (int) $minutos ?> minutos.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#fff1e8;">
        <tr>
            <td align="center" style="padding:40px 16px;">

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:480px;">

                    <!-- marca: o quadrado laranja faz as vezes do logo, que e SVG -->
                    <tr>
                        <td align="center" style="padding-bottom:24px;">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td width="28" height="28" style="width:28px; height:28px; background:#f77f00; border:3px solid <?= $tinta ?>;"></td>
                                    <td style="padding-left:12px; font-family:<?= $display ?>; font-size:30px; line-height:30px; color:<?= $tinta ?>;">
                                        Controla
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- cartao: 6px na direita e embaixo imitam a sombra dura do app -->
                    <tr>
                        <td style="background:#ffffff; border:3px solid <?= $tinta ?>; border-right-width:6px; border-bottom-width:6px; padding:32px;">

                            <p style="margin:0 0 8px; font-family:<?= $sans ?>; font-size:11px; line-height:14px; font-weight:700; letter-spacing:1px; text-transform:uppercase; color:<?= $tintaSuave ?>;">
                                Confirmacao de email
                            </p>

                            <h1 style="margin:0 0 16px; font-family:<?= $sans ?>; font-size:22px; line-height:28px; font-weight:700; color:<?= $tinta ?>;">
                                Ola, <?= Security::escape($nome) ?>!
                            </h1>

                            <p style="margin:0 0 24px; font-family:<?= $sans ?>; font-size:15px; line-height:22px; color:<?= $tinta ?>;">
                                Use o codigo abaixo para confirmar o seu email e entrar no Controla.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center" style="background:#ffe3c2; border:3px solid <?= $tinta ?>; padding:20px 12px; font-family:<?= $sans ?>; font-size:36px; line-height:40px; font-weight:700; letter-spacing:10px; color:<?= $tinta ?>;">
                                        <?= Security::escape($codigo) ?>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0; font-family:<?= $sans ?>; font-size:13px; line-height:18px; font-weight:600; color:#8a4b12;">
                                Vale por <?= (int) $minutos ?> minutos. Depois disso, peca outro na tela de confirmacao.
                            </p>

                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px 8px 0; font-family:<?= $sans ?>; font-size:12px; line-height:16px; color:<?= $tintaSuave ?>; text-align:center;">
                            Nao foi voce? Pode ignorar este email: sem a senha, ninguem entra na conta.
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>
