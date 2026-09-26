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

**Logo depois do boot, crie a conta.** Com o banco vazio o `/cadastro` fica
aberto, e quem chegar primeiro vira a dona do sistema -- dominio com
certificado novo recebe visita de robo em minutos. Confira que so a sua existe:

```sh
./deploy/controla exec db sh -c 'mariadb -u"$MARIADB_USER" -p"$MARIADB_PASSWORD" "$MARIADB_DATABASE" -e "SELECT id, nome, email FROM usuario"'
```

O codigo de confirmacao sai pelo `[email]` do `config.ini` (host, porta, usuario,
senha de app codificada). Sem ele a conta nasce e o codigo nunca chega.

## VPS que ja tem nginx (outros projetos)

Se um nginx do host ja ocupa 80/443, o Caddy nao sobe ("address already in
use"). Nao derrube o nginx: ponha o Controla atras dele.

```
internet :443 -> nginx do host (TLS pelo certbot)
                   -> 127.0.0.1:8081 -> Caddy (so HTTP) -> php-fpm -> MariaDB
```

No `config/config.ini`, alem do resto:

```ini
host.wan = https://controla.SEU-DOMINIO.com/
proxy.wan = 127.0.0.1:8081

[app]
trusted_proxy = 1
```

O `trusted_proxy = 1` faz o PHP ler o ip real do `X-Real-IP` que o nginx manda.
Sem ele, todo mundo chega com o ip do proxy, e o limite de login bloqueia todos
juntos na primeira pessoa que errar a senha 5 vezes. So ligue com o nginx na
frente: sem proxy, qualquer um forja esse cabecalho.

Com o `proxy.wan` preenchido, o `./deploy/controla` troca o endereco do Caddy
por `:80` (sem certificado) e publica a porta so em `127.0.0.1`. Depois:

```sh
./deploy/controla up -d --build
curl -I -H 'Host: controla.SEU-DOMINIO.com' http://127.0.0.1:8081/login   # 200

sudo cp deploy/nginx-controla.conf /etc/nginx/sites-available/controla
sudo nano /etc/nginx/sites-available/controla     # server_name e porta
sudo ln -s /etc/nginx/sites-available/controla /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
sudo certbot --nginx -d controla.SEU-DOMINIO.com
```

A porta tem de ser livre: `sudo ss -ltnp | grep 8081` sem saida.

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
