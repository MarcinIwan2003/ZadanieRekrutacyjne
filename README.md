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

curl -i "http://localhost:8000/api/banned" -H "Accept: application/json"
- 401 brak nagłówka

curl -i "http://localhost:8000/api/banned" \
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: zlyklucz"
- 403 zły klucz

curl -i "http://localhost:8000/api/banned" \
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: abc123"
- 200 zwraca liste

curl -i -X POST "http://localhost:8000/api/banned" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-SUPER-SECRET-KEY: abc123" \
  -d '{"pokemon_id":84,"pokemon_name":"pikachu"}'
- dodanie wpisu dla pokemona pikachu

curl -s "http://localhost:8000/api/banned?search=pika&sort=-created_at&per_page=10" \
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: abc123"
echo
- lista zablokowanych z sortowaniem i filterm i ilością na stronę

## testy curl /api/info

curl -i -X POST "http://localhost:8000/api/info" \ 
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"pokemons":["pikachu","charmander",999999]}'
echo
- pikachu według testów jest w tabeli banned_pokemons więć powinien być skipped , 999999 będzie skipped.not_found

## testy curl /api/custom-pokemons

curl -i -X POST "http://localhost:8000/api/custom-pokemons" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-SUPER-SECRET-KEY: Luty2026" \
  -d '{"pokemon_id":999999,"name":"rekrutacja","height":10,"weight":200,"types":["normal"]}'
echo
- dodawanie customowego pokemona

curl -i "http://localhost:8000/api/custom-pokemons" \
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: Luty2026"
echo
- lista customowych pokemonów

curl -i "http://localhost:8000/api/custom-pokemons/1" \
  -H "Accept: application/json" \
  -H "X-SUPER-SECRET-KEY: Luty2026"
echo
- przeglad customowych po pokemonów po rekordzie id z bazy

curl -i -X PUT "http://localhost:8000/api/custom-pokemons/1" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-SUPER-SECRET-KEY: Luty2026" \
  -d '{"weight":250,"types":["normal","steel"]}'
echo
- edycja customowego pokemona

curl -i -X DELETE "http://localhost:8000/api/custom-pokemons/1" \
  -H "X-SUPER-SECRET-KEY: Luty2026"
echo
- usuwanie customowego pokemona

curl -i -X POST "http://localhost:8000/api/info" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"pokemons":[999999]}'
echo
- /info z etapu 3 zwraca customowego pokemona po pokemon_id

curl -i -X POST "http://localhost:8000/api/info" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"pokemons":["rekrutacja"]}'
echo
- /info z etapu 3 zwraca customowego pokemona po nazwie

curl -i -X POST "http://localhost:8000/api/info" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"pokemons":[999999,"charmander",123456789]}'
echo
- /info z etapu 3 sprawdzenie bazy custom jak i PokeApi

curl -i -X POST "http://localhost:8000/api/custom-pokemons" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -H "X-SUPER-SECRET-KEY: Luty2026" \
  -d '{"pokemon_id":999998,"name":"pikachu"}'
echo
- test blokady dodawania customowego pokemona gdy nazwy istnieje w PokeAPI




