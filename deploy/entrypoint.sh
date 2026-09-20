#!/bin/sh
#
# Roda antes do php-fpm. Aplica as migrations pendentes e so entao entrega o
# processo. Migration que falha derruba o container de proposito: melhor nao
# subir do que servir com o schema errado -- o restart: always tenta de novo.

set -eu

cd /var/www/html

php bin/cubo migrate

exec "$@"
