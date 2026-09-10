#!/usr/bin/env sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
COMPOSE_FILE="$ROOT/compose.test.yml"
database=${SGI_TEST_DATABASE:-mariadb}
php_version=${SGI_PHP_VERSION:-8.4}
include_visual=0
skip_browser=0
keep=0

while [ "$#" -gt 0 ]; do
    case "$1" in
        --database)
            database=$2
            shift 2
            ;;
        --php-version)
            php_version=$2
            shift 2
            ;;
        --include-visual)
            include_visual=1
            shift
            ;;
        --skip-browser)
            skip_browser=1
            shift
            ;;
        --keep)
            keep=1
            shift
            ;;
        *)
            echo "Opção desconhecida: $1" >&2
            exit 2
            ;;
    esac
done

case "$database" in
    mariadb) database_image=mariadb:10.11 ;;
    mysql) database_image=mysql:8.4 ;;
    *) echo "Banco inválido: $database (use mariadb ou mysql)." >&2; exit 2 ;;
esac

case "$php_version" in
    8.2|8.4) ;;
    *) echo "PHP inválido: $php_version (use 8.2 ou 8.4)." >&2; exit 2 ;;
esac

: "${SGI_DB_PASSWORD:=sgi-test-only}"
: "${SGI_TEST_DB_NAME:=sgi_test}"
export SGI_DB_IMAGE="$database_image" SGI_PHP_VERSION="$php_version"
export SGI_DB_PASSWORD SGI_TEST_DB_NAME

compose() {
    docker compose -f "$COMPOSE_FILE" "$@"
}

cleanup() {
    status=$?
    if [ "$keep" -eq 0 ]; then
        compose down --volumes --remove-orphans || {
            if [ "$status" -eq 0 ]; then status=1; fi
            echo 'Não foi possível remover completamente o ambiente Docker de teste.' >&2
        }
    else
        echo 'Ambiente mantido conforme solicitado (--keep).'
    fi
    exit "$status"
}
trap cleanup EXIT

compose config --quiet
compose build app browser
compose up -d --wait db app
compose run --rm --no-deps quality
compose run --rm --no-deps integration

if [ "$skip_browser" -eq 0 ]; then
    compose run --rm --no-deps browser
fi

if [ "$include_visual" -eq 1 ]; then
    compose run --rm --no-deps visual
fi

echo 'Todos os serviços de teste concluíram com sucesso.'
