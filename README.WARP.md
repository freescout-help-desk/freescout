# FreeScout (WARP Onboarding)

Diese Datei beschreibt die lokale Dev-Umgebung im WARP-Portfolio.

## Ports (registriert)
- Web: ${FREESCOUT_WEB_PORT:-8087}
- MariaDB: ${FREESCOUT_DB_PORT:-3308}
- Redis: ${FREESCOUT_REDIS_PORT:-6384}
- MailHog: HTTP ${FREESCOUT_MAILHOG_HTTP_PORT:-8026}, SMTP ${FREESCOUT_MAILHOG_SMTP_PORT:-1027}

## Setup (lokal)
1) Docker starten
   docker compose up -d --build

2) Healthcheck
   - Web: curl -I http://localhost:${FREESCOUT_WEB_PORT:-8087}/health
   - DB:  nc -z localhost ${FREESCOUT_DB_PORT:-3308}
   - Redis: docker compose exec -T redis redis-cli ping
   - MailHog: http://localhost:${FREESCOUT_MAILHOG_HTTP_PORT:-8026}

3) (Optional) Abhängigkeiten installieren
   docker compose exec -T php composer install

Hinweis: Upstream empfiehlt Web-Installer (kein manuelles .env vorab). Für vollautomatische Provisionierung bitte melden, dann konfiguriere ich .env und führe artisan-Befehle aus.

## QA
- Code Style: .php-cs-fixer.php
- Static Analysis: phpstan.neon (Level 5; für Legacy Code moderate Strenge)
- Tests: phpunit.xml.dist (sqlite in-memory)

### Commands
- Composer: composer run qa:all (runs cs-check, phpstan, phpunit)
- In Docker: docker compose exec -T php composer run qa:all

## AI-Variablen
- OLLAMA_HOST (default: http://host.docker.internal:11434)
- OLLAMA_MODEL (default: tinyllama)

## CI
- Beispiel-Workflow: docs/ci.yml.example
- Läuft auf PHP 7.4 mit MariaDB 10.5 und Redis Services
- Führt composer install, artisan key:generate, migrations, PHPUnit, PHPStan und PHP-CS-Fixer (dry-run) aus
- Aktivierung: Datei nach .github/workflows/ci.yml kopieren (Token mit "workflow"-Scope nötig)

## Git-Hooks aktivieren
- Pre-Commit Hook (QA-Checks vor jedem Commit)
  git config core.hooksPath .githooks

