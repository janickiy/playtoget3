<p align="center">
    <h1 align="center">Laravel 13 adminpanel</h1>
</p>

# Модули проекта
- Docker
- php:8.3-fpm
- nginx:alpine
- mysql
- PostgreSQL
- redis
- memchached
- phpMyAdmin

# Доступ к сервисам

    Frontend: http://localhost:8080
    phpMyAdmin: http://localhost:8081


# Как запустить

- Поместите файлы в корень проекта.
- Выполните команду:

bash

cp .env.example .env и заполнить необходимыми данными

docker-compose up -d --build


# Установка

1. `composer install`
2. `php artisan migrate`
3. `php artisan db:seed`
4. `php artisan key:generate`

После выполнить команду docker-compose down && docker-compose up -d

# Панель администратора

http://localhost:8080/cp

логин admin
пароль 1234567
