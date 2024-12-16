# Application installation for development

- Create database in MySQL or MariaDB
- Create `.env` file from `.env.example`
- Configure `.env`:
    - `DB_` keys with database access values
    - `MAIL_` keys with mail server values
    - `APP_URL` with app url
    - `FRONTEND_URL` with front (nuxt app) url
- Execute:
    - `composer update --lock`
    - `php artisan key:generate`
    - `php artisan storage:link`
    - `php artisan jwt:secret`
    - `php artisan migrate:fresh --seed`
- If not using a third party HTTP server, run `php artisan serve` to launch app at `http://127.0.0.1:8000`