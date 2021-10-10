## Awesome Skeleton for modern development on PHP 7.1+ and RoadRunner

## Installation (via composer)

```bash
composer create-project sunrise/awesome-skeleton-roadrunner app
```

## Run application

```bash
rr serve -d -v
```

## Documentation

[Open](https://github.com/sunrise-php/awesome-skeleton/wiki)

## How to install RoadRunner

[Read](https://github.com/sunrise-php/awesome-skeleton/wiki/How-to-install-RoadRunner)

## Based on the following packages

* https://github.com/doctrine/orm
* https://github.com/middlewares
* https://github.com/PHP-DI/PHP-DI
* https://github.com/Seldaek/monolog
* https://github.com/spiral/roadrunner
* https://github.com/sunrise-php/http-router
* https://github.com/symfony/validator
* https://www.php-fig.org/

## Deploy

### Database

#### Configuration

```bash
cp config/environment.php.example config/environment.php
```

#### Fill the database

```bash
php vendor/bin/doctrine orm:schema-tool:update --force
```

#### Apply migrations

```bash
php vendor/bin/doctrine-migrations migrate --all-or-nothing
```

### Create systemd service

#### Generate an unit file for the systemd service manager

```bash
php app app:generate-systemd-unit-file
```

#### Register the unit file on the systemd service manager

```bash
cp app.suppliers.service /etc/systemd/system/
```

```bash
systemctl enable app.suppliers
```

```bash
systemctl daemon-reload
```

#### Check that everything is done correctly

```bash
systemctl status app.suppliers
```

#### Run the application

```bash
systemctl start app.suppliers
```

#### Show the application journal

```bash
journalctl -u app.suppliers
```

#### Advanced features of the application journal

```bash
journalctl -u app.suppliers -a --no-pager --follow --since "$(date --date="5 minutes ago" +%Y-%m-%d\ %H:%M:%S)"
```

#### Userful links

* https://wiki.debian.org/systemd
* https://wiki.debian.org/systemd/Services
* https://manpages.debian.org/stretch/systemd/journalctl.1.en.html
* https://manpages.debian.org/stretch/systemd/systemctl.1.en.html

## Update

#### Pull the latest updates for the application

```bash
git pull origin master
```

#### Restart the application

```bash
systemctl restart app.suppliers
```
