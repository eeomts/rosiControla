# Como ligar o Controla

Dois jeitos. O que manda qual deles esta valendo e a `location` do
`config/config.ini`.

Se o `config/config.ini` nao existir, copie o exemplo e preencha:

```sh
cp config/config.ini.example config/config.ini
php bin/encode   # gera o user/pass codificados que vao no ini
```

## Jeito 1: Docker (o mesmo da VPS)

1. Abra o **Docker Desktop** e espere ele ficar verde.
2. No `config/config.ini`:

   ```ini
   location = docker
   host.docker = http://localhost:9080/
   ```

3. Na raiz do projeto (Git Bash):

   ```sh
   ./deploy/controla up -d --build
   ```

4. Abra http://localhost:9080

- As migrations rodam sozinhas quando o container sobe.
- **Nao ha bind mount**: mudou codigo, rode o `up -d --build` de novo.
- Parar: `./deploy/controla down`
- Ver erro do PHP: `./deploy/controla logs -f app`
- Se o Git Bash disser `docker: command not found`, o Docker Desktop esta
  fechado ou fora do PATH. Abrir o Docker Desktop costuma resolver.

## Jeito 2: PHP direto (mais rapido pra mexer no codigo)

Precisa do PHP 8.2 e do MariaDB local ligado (o nosso esta na porta **3307**).

1. No `config/config.ini`:

   ```ini
   location = local
   host.local = http://localhost:9123/
   ```

2. Na raiz do projeto:

   ```sh
   php -S localhost:9123 router-dev.php
   ```

3. Abra http://localhost:9123. `Ctrl+C` no terminal desliga.

Aqui a alteracao aparece na hora, sem build.

## Armadilhas

- **"tentativa de acesso a um soquete ... proibida" / porta que nao abre**: o
  Windows (Hyper-V/WSL) reserva faixas de porta a cada boot. Hoje reserva de
  7316 a 8315 (inclui 8080 e 8123). Para ver a lista:

  ```sh
  netsh interface ipv4 show excludedportrange protocol=tcp
  ```

  Escolha uma porta fora dela e troque no `host.*` do ini (por isso a 9080 e a
  9123).
- **`getaddrinfo for db failed`**: a `location` esta `docker`, mas voce subiu
  com `php -S`. O host `db` so existe dentro do compose.
- **`SQLSTATE[HY000] [2054] auth_gssapi_client`**: porta errada do banco no
  `[database.local]`. Tem que ser 3307, nao 3306.
- **`php bin/cubo migrate` no banco local diz que a tabela ja existe**: o banco
  local foi criado pelo `database/schema.sql`, nao pelas migrations. Para so
  rodar o app, nao precisa migrar.
