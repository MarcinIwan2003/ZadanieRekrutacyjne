# Zadanie rekrutacyjne - Pokemon API (Laravel)

REST API w Laravelu. 
Etap 1: rejestr zakazanych pokemonów (`/api/banned`).  
Etap 2: autoryzacja żądań do `/api/banned` przez nagłówek `X-SUPER-SECRET-KEY` klucz zapisany w env np. SUPER_SECRET_KEY=Luty2026.

## Wymagania środowiskowe
- PHP 8.2+
- Composer
- MySQL/MariaDB

## Uruchomienie

git clone https://github.com/MarcinIwan2003/ZadanieRekrutacyjne.git
cd ZadanieRekrutacyjne
composer install
cp .env.example .env
php artisan key:generate

## Konfiguracja env
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=Zadanie
DB_USERNAME=root
DB_PASSWORD=

SUPER_SECRET_KEY=Luty2026

## migracja bazy danych
php artisan migrate

## uruchomienie lokalne
php artisan serve

api bedzie dostępne pod http://localhost:8000/api

## testy curl /api/banned

curl -i "http://localhost:8000/api/banned" -H "Accept: application/json"     - 401 brak nagłówka

curl -i "http://localhost:8000/api/banned" \                                 - 403 zły klucz
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: zlyklucz"

curl -i "http://localhost:8000/api/banned" \                                 - 200 zwraca liste
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: abc123"

curl -i -X POST "http://localhost:8000/api/banned" \                         - dodanie wpisu dla pokemona pikachu
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-SUPER-SECRET-KEY: abc123" \
  -d '{"pokemon_id":84,"pokemon_name":"pikachu"}'

curl -s "http://localhost:8000/api/banned?search=pika&sort=-created_at&per_page=10" \        - lista zablokowanych z sortowaniem i filterm i ilością na stronę
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: abc123"
echo

## testy curl /api/info

curl -i -X POST "http://localhost:8000/api/info" \             - pikachu według testów jest w tabeli banned_pokemons więć powinien być skipped , 999999 będzie skipped.not_found 
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"pokemons":["pikachu","charmander",999999]}'
echo

