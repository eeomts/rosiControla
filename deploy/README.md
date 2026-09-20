# Deploy do Controla

```
internet :80/:443
  -> web   Caddy: termina o TLS, serve public/, manda o resto por fastcgi
       -> app   php-fpm: aplica as migrations e roda a aplicacao
            -> db    MariaDB, so na rede interna do compose
```

Tres regras que explicam o resto:

1. **`config/config.ini` e a unica configuracao.** Nao ha `.env`. O dominio, o
   nome do banco, o usuario e a senha saem dele.
2. **Quem sobe e o `./deploy/controla`.** O compose so le variavel de ambiente;
   o script le o ini e preenche. `docker compose` cru para com erro de proposito.
3. **O schema e as migrations.** O container aplica as pendentes antes de
   servir. Nao existe `schema.sql`.

## Primeira subida na VPS

Precisa de: docker, e o dominio ja apontando para o IP da VPS (o Caddy pede o
certificado sozinho, mas so consegue se o DNS ja resolver).

```sh
git clone <repo> ~/controla && cd ~/controla

cp config/config.ini.example config/config.ini
```

No `config/config.ini`:

```ini
location = wan
host.wan = https://controla.SEU-DOMINIO.com/
enviroment = production
servidor = VPS
```

E as credenciais do banco, na secao `[database.wan]`. Elas vao ofuscadas -- o
`Db` desfaz o `cuboEncode` na conexao, e o `./deploy/controla` desfaz para
entregar ao MariaDB. E a mesma senha nos dois lados porque e o mesmo arquivo:

```sh
docker run --rm -v "$PWD":/app -w /app composer:2 install   # se nao houver vendor/
php bin/encode "controla"     # -> user
php bin/encode "a-senha"      # -> pass
```

> Se o encode avisar que o valor nao volta igual, troque a senha: o
> `cuboEncode` troca caractere especial por `_`. Letras e numeros passam limpo.

Entao:

```sh
./deploy/controla up -d --build
./deploy/controla logs -f
```

O boot demora ~30s na primeira vez: o MariaDB inicializa, o `app` espera o
healthcheck, aplica as migrations e so entao aceita requisicao.

## Atualizar

```sh
cd ~/controla && git pull && ./deploy/controla up -d --build
```

E so. Migration nova entra sozinha no start. O banco nao e tocado.

> `opcache.validate_timestamps = 0`: editar arquivo dentro do container nao muda
> nada. Toda atualizacao passa por `--build`.

## Testar em container antes da VPS

Mesma imagem, mesmo compose -- muda so a `location` do ini:

```ini
location = docker
host.docker = http://localhost/     ; ou http://localhost:8080/ se a 80 estiver ocupada
```

```sh
./deploy/controla up -d --build
```

A porta declarada no `host.docker` e a que o compose publica.

## Dia a dia

```sh
./deploy/controla ps                        # o que esta de pe
./deploy/controla logs -f app               # log da aplicacao (erro de PHP cai aqui)
./deploy/controla logs -f web               # acesso e TLS
./deploy/controla restart app
./deploy/controla down                      # derruba; os dados ficam no volume
./deploy/controla exec app php bin/cubo migrate:status
./deploy/controla exec db mariadb -u controla -p rosi_controla
```

Backup (nao ha nenhum configurado ainda):

```sh
./deploy/controla exec -T db mariadb-dump -u controla -pSENHA \
    --single-transaction rosi_controla | gzip > controla-$(date +%F).sql.gz
```

## Armadilhas

1. **`./deploy/controla down -v` apaga o banco.** E o unico comando que mexe nos
   volumes. Nenhum outro.
2. **Crie o `config/config.ini` ANTES do primeiro `up`.** O script confere e
   avisa; se voce driblar e subir sem ele, o docker cria um *diretorio* com esse
   nome no lugar do arquivo e a aplicacao quebra de um jeito confuso.
3. **`enviroment` != `development` na VPS**, senao a excecao aparece na tela da
   usuaria em vez de ir para o log.
4. **O certificado vive no volume `caddy`.** Nao apague esse volume a toa: o
   Let's Encrypt limita quantos certificados voce pode pedir por semana.
5. **O `host.<location>` precisa da barra final.** E dela que sai o caminho base
   das rotas.
