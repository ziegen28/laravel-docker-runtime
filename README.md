

🚀 Laravel Docker Runtime (Sail++)

A production-like Docker runtime for Laravel, simpler than Sail, more flexible, zero permission issues.

Why this exists

Laravel Sail is great — but:

Hard to customize

Permission issues on Linux

Heavy stack

This package solves that.

✨ Features

✅ Apache + PHP 8.2 / 8.3 / 8.4

✅ MySQL / PostgreSQL / SQLite

✅ Adminer (DB UI)

✅ UID / GID sync (NO permission errors)

✅ Interactive CLI wizard

✅ .env auto-sync

✅ Zero Docker knowledge required

📦 Installation
composer require ziegen28/laravel-docker-runtime

🐳 Create Docker Runtime
php artisan docker:install


You’ll be asked:

PHP version

Database

Optional services (Adminer, Redis, Mongo, Meili)

▶️ Run Containers
docker compose up -d --build

🌍 URLs
Service	URL
App	http://localhost:8000

Adminer	http://localhost:8081
🛑 Stop Containers
docker compose down -v

🧠 Presets (Coming)
php artisan docker:install --silent
php artisan docker:install --preset=team

🧩 Why better than Sail?
Feature	Sail	This
Permission safe	❌	✅
Apache	❌	✅
Interactive wizard	❌	✅
Beginner friendly	⚠️	✅
Custom stacks	❌	✅
🧑‍💻 Author

Built by Ziegen28
For developers who want control without pain.
