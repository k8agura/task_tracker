# Task Tracker

Веб-приложение для управления задачами, общения внутри задач и аналитических отчетов.

## Содержание

- [О проекте](#о-проекте)
- [Стек и версии](#стек-и-версии)
- [Системные требования](#системные-требования)
- [Быстрый старт](#быстрый-старт)
- [Запуск в режиме разработки](#запуск-в-режиме-разработки)
- [Тесты и сборка](#тесты-и-сборка)
- [Данные для входа по умолчанию](#данные-для-входа-по-умолчанию)
- [Структура маршрутов](#структура-маршрутов)
- [Примечания](#примечания)

## О проекте

Task Tracker включает:
- управление пользователями, ролями и правами;
- создание и ведение задач;
- комментарии и вложения в задачах;
- отчеты и аналитические срезы.

## Стек и версии

### Backend

| Технология | Версия |
|---|---|
| PHP | `^8.2` |
| Laravel Framework | `^12.0` |
| Laravel Sanctum | `^4.0` |
| Laravel Reverb | `^1.0` |
| spatie/laravel-permission | `^7.2` |
| PHPUnit (dev) | `^11.5.50` |

### Frontend

| Технология | Версия |
|---|---|
| Vue | `^3.5.30` |
| Vue Router | `^5.0.3` |
| Bootstrap | `^5.3.8` |
| Chart.js | `^4.5.1` |
| Axios | `^1.13.6` |
| Vite | `^7.0.7` |
| laravel-vite-plugin | `^2.0.0` |

## Системные требования

- PHP `8.2+`
- Composer `2+`
- Node.js `20+` (LTS рекомендуется)
- npm `10+`
- MySQL `8+`

## Быстрый старт

### 1) Клонирование репозитория

```bash
git clone https://github.com/k8agura/task_tracker.git
cd task_tracker
```

### 2) Установка зависимостей

```bash
composer install
npm install
```

### 3) Создание `.env`

Linux/macOS/Git Bash:

```bash
cp .env.example .env
```

Windows PowerShell:

```powershell
Copy-Item .env.example .env
```

### 4) Настройка БД в `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_manager
DB_USERNAME=root
DB_PASSWORD=
```

### 5) Генерация ключа и подготовка базы

```bash
php artisan key:generate
php artisan migrate --seed
```

## Запуск в режиме разработки

### Вариант A: одной командой

Запускает веб-сервер, очередь, логи и Vite:

```bash
composer dev
```

### Вариант B: по отдельности (в разных терминалах)

```bash
php artisan serve
php artisan queue:listen --tries=1 --timeout=0
npm run dev
```

Приложение будет доступно по адресу:

- `http://127.0.0.1:8000`

## Тесты и сборка

Запуск тестов:

```bash
php artisan test
```

Production-сборка фронтенда:

```bash
npm run build
```

Форматирование кода (Laravel Pint):

```bash
./vendor/bin/pint
```

## Данные для входа по умолчанию

После `php artisan migrate --seed` создается администратор:

- Email: `admin@example.com`
- Пароль: `admin12345`

## Структура маршрутов

- SPA-роутинг: `routes/web.php`
- API-роутинг: `routes/api.php`
- API-префикс: `/api/*`

## Примечания

- Для OSPanel/OpenServer убедитесь, что MySQL запущен и проект подключен к корректному домену/пути.
- При первом запуске проверьте, что в БД есть таблицы `jobs`, `cache`, `personal_access_tokens` и основные бизнес-таблицы (создаются миграциями).
