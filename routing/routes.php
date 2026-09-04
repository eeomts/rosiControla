<?php

/**
 * @package Controla
 * @author Mateus - github.com/eeomts
 */

declare(strict_types=1);

use Controla\Controllers\CicloController;
use Controla\Controllers\ClienteController;
use Controla\Controllers\PedidoController;
use Controla\Controllers\ProdutoController;
use Controla\Controllers\VendaController;
use Cubo\Routing\RouteCollection;

$rotas = new RouteCollection();

// A raiz ainda e a lista de ciclos, enquanto nao existe dashboard.
$rotas->get('/', CicloController::class, 'index')->name('home');

$rotas->get('/ciclo', CicloController::class, 'index')->name('ciclo.lista');
$rotas->get('/ciclo/form', CicloController::class, 'form')->name('ciclo.novo');
$rotas->get('/ciclo/form/{id}', CicloController::class, 'form')->name('ciclo.editar');
$rotas->post('/ciclo/salvar', CicloController::class, 'salvar')->name('ciclo.salvar');
$rotas->post('/ciclo/excluir', CicloController::class, 'excluir')->name('ciclo.excluir');

$rotas->get('/cliente', ClienteController::class, 'index')->name('cliente.lista');
$rotas->get('/cliente/form', ClienteController::class, 'form')->name('cliente.novo');
$rotas->get('/cliente/form/{id}', ClienteController::class, 'form')->name('cliente.editar');
$rotas->post('/cliente/salvar', ClienteController::class, 'salvar')->name('cliente.salvar');
$rotas->post('/cliente/excluir', ClienteController::class, 'excluir')->name('cliente.excluir');

$rotas->get('/produto', ProdutoController::class, 'index')->name('produto.lista');
$rotas->get('/produto/form', ProdutoController::class, 'form')->name('produto.novo');
$rotas->get('/produto/form/{id}', ProdutoController::class, 'form')->name('produto.editar');
$rotas->post('/produto/salvar', ProdutoController::class, 'salvar')->name('produto.salvar');
$rotas->post('/produto/excluir', ProdutoController::class, 'excluir')->name('produto.excluir');

$rotas->get('/pedido', PedidoController::class, 'index')->name('pedido.lista');
$rotas->get('/pedido/form', PedidoController::class, 'form')->name('pedido.novo');
$rotas->get('/pedido/form/{id}', PedidoController::class, 'form')->name('pedido.editar');
$rotas->post('/pedido/salvar', PedidoController::class, 'salvar')->name('pedido.salvar');
// as duas do passo 2: mexem nas unidades de um pedido que ja existe
$rotas->post('/pedido/adicionar', PedidoController::class, 'adicionar')->name('pedido.adicionar');
$rotas->post('/pedido/remover', PedidoController::class, 'remover')->name('pedido.remover');
$rotas->post('/pedido/excluir', PedidoController::class, 'excluir')->name('pedido.excluir');

$rotas->get('/venda', VendaController::class, 'index')->name('venda.lista');
$rotas->get('/venda/form', VendaController::class, 'form')->name('venda.novo');
$rotas->get('/venda/form/{id}', VendaController::class, 'form')->name('venda.editar');
$rotas->post('/venda/salvar', VendaController::class, 'salvar')->name('venda.salvar');
$rotas->post('/venda/excluir', VendaController::class, 'excluir')->name('venda.excluir');

// /conta nao esta aqui de proposito: o menu ja tem o link, e ate a feature
// existir ele cai no 404 do NaoEncontradoMiddleware.

return $rotas;
