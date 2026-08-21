-- Convencao do Cubo (app/Cubo2/src/Database/Model.php): fk_ / data_ / mon_ / num_,
-- created + updated + deleted em toda tabela, sufixo _aux no lugar de enum, _rel para relacao.

-- CREATE DATABASE controla CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- auxiliares

CREATE TABLE genero_aux (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(30) NOT NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE status_pagamento_aux (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(30) NOT NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE status_entrega_aux (
  id TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(30) NOT NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- acesso

CREATE TABLE usuario (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL,
  senha VARCHAR(255) NOT NULL,
  ativo TINYINT(1) NOT NULL DEFAULT 1,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_usuario_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- ciclo e pedido

CREATE TABLE ciclo (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(60) NOT NULL,
  num_ciclo TINYINT UNSIGNED NOT NULL,
  num_ano SMALLINT UNSIGNED NOT NULL,
  data_inicio DATE NOT NULL,
  data_termino DATE NOT NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_ciclo_num_ano (num_ciclo, num_ano)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE pedido (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fk_ciclo INT UNSIGNED NOT NULL,
  nome VARCHAR(40) NOT NULL,
  data_pedido DATE NOT NULL,
  mon_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  mon_lucro_estimado DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  mon_lucro_real DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_pedido_nome (nome),
  KEY ix_pedido_ciclo (fk_ciclo),
  CONSTRAINT fk_pedido_ciclo FOREIGN KEY (fk_ciclo) REFERENCES ciclo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- produto

-- a BASE: catalogo estavel, nao muda entre ciclos
CREATE TABLE produto (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(160) NOT NULL,
  codigo_produto VARCHAR(30) NULL,
  fk_genero TINYINT UNSIGNED NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_produto_nome (nome),
  KEY ix_produto_codigo (codigo_produto),
  CONSTRAINT fk_produto_genero FOREIGN KEY (fk_genero) REFERENCES genero_aux (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- UMA LINHA = UMA UNIDADE fisica. 3 batons iguais no mesmo pedido = 3 linhas.
CREATE TABLE variacao_produto (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fk_produto INT UNSIGNED NOT NULL,
  fk_pedido INT UNSIGNED NOT NULL,
  fk_ciclo INT UNSIGNED NOT NULL,
  data_validade DATE NULL,
  mon_custo DECIMAL(10,2) NOT NULL,
  mon_venda DECIMAL(10,2) NOT NULL,
  vendido TINYINT(1) NOT NULL DEFAULT 0,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_variacao_disponivel (fk_produto, vendido, deleted),
  KEY ix_variacao_agrupamento (fk_produto, fk_pedido, mon_custo, mon_venda, data_validade),
  KEY ix_variacao_pedido (fk_pedido),
  KEY ix_variacao_ciclo (fk_ciclo),
  CONSTRAINT fk_variacao_produto_produto FOREIGN KEY (fk_produto) REFERENCES produto (id),
  CONSTRAINT fk_variacao_produto_pedido FOREIGN KEY (fk_pedido) REFERENCES pedido (id),
  CONSTRAINT fk_variacao_produto_ciclo FOREIGN KEY (fk_ciclo) REFERENCES ciclo (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- venda

CREATE TABLE cliente (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  telefone VARCHAR(20) NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_cliente_nome (nome)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- pertence ao cliente, nunca a um ciclo ou pedido
CREATE TABLE venda (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fk_cliente INT UNSIGNED NOT NULL,
  fk_status_pagamento TINYINT UNSIGNED NOT NULL,
  fk_status_entrega TINYINT UNSIGNED NOT NULL,
  data_venda DATE NOT NULL,
  mon_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  mon_desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_venda_cliente (fk_cliente),
  KEY ix_venda_data (data_venda),
  CONSTRAINT fk_venda_cliente FOREIGN KEY (fk_cliente) REFERENCES cliente (id),
  CONSTRAINT fk_venda_status_pagamento FOREIGN KEY (fk_status_pagamento) REFERENCES status_pagamento_aux (id),
  CONSTRAINT fk_venda_status_entrega FOREIGN KEY (fk_status_entrega) REFERENCES status_entrega_aux (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- N produtos = N linhas
CREATE TABLE venda_variacao_rel (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fk_venda INT UNSIGNED NOT NULL,
  fk_variacao_produto INT UNSIGNED NOT NULL,
  mon_venda DECIMAL(10,2) NOT NULL,
  -- desconto que ela deu NESTE item (brinde = 100%)
  mon_desconto DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  -- a parte do desconto da venda inteira que coube a este item
  mon_desconto_rateio DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_venda_variacao_venda (fk_venda),
  KEY ix_venda_variacao_unidade (fk_variacao_produto, deleted),
  CONSTRAINT fk_venda_variacao_venda FOREIGN KEY (fk_venda) REFERENCES venda (id),
  CONSTRAINT fk_venda_variacao_variacao_produto FOREIGN KEY (fk_variacao_produto) REFERENCES variacao_produto (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- financeiro

CREATE TABLE conta_pagar (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fk_status_pagamento TINYINT UNSIGNED NOT NULL,
  descricao VARCHAR(160) NOT NULL,
  mon_valor DECIMAL(10,2) NOT NULL,
  data_vencimento DATE NOT NULL,
  data_pagamento DATE NULL,
  created DATETIME NULL,
  updated DATETIME NULL,
  deleted TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  KEY ix_conta_pagar_vencimento (data_vencimento),
  CONSTRAINT fk_conta_pagar_status_pagamento FOREIGN KEY (fk_status_pagamento) REFERENCES status_pagamento_aux (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------- carga inicial

INSERT INTO status_pagamento_aux (nome) VALUES ('Nao pago'), ('Pago');
INSERT INTO status_entrega_aux (nome) VALUES ('Nao entregue'), ('Entregue');
INSERT INTO genero_aux (nome) VALUES ('Feminino'), ('Masculino'), ('Unissex');
