#!/usr/bin/env sh
set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
COMPOSE_FILE="$ROOT/compose.test.yml"
database=${SGI_TEST_DATABASE:-mariadb}
php_version=${SGI_PHP_VERSION:-8.4}
skip_quality=0
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
        --skip-quality)
            skip_quality=1
            shift
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

run_id="$(date -u +%Y%m%d_%H%M%S)-$$"
run_id_safe="$(printf '%s' "$run_id" | tr '-' '_')"
project_name="sgi-test-$run_id"
database_name="sgi_test_$run_id_safe"
results_directory="$ROOT/test-results/docker-$run_id"
browser_results_directory="$ROOT/tests/browser/test-results/docker-$run_id"
browser_report_directory="$ROOT/tests/browser/playwright-report/docker-$run_id"
mkdir -p "$results_directory" "$browser_results_directory" "$browser_report_directory"

export COMPOSE_PROJECT_NAME="$project_name"
export SGI_DB_IMAGE="$database_image" SGI_PHP_VERSION="$php_version"
export SGI_DB_PASSWORD=sgi-test-only SGI_TEST_DB_NAME="$database_name"
export SGI_TEST_RUN_ID="$run_id"
export SGI_TEST_HOST_PORT="${SGI_TEST_HOST_PORT:-0}"
export SGI_TEST_RESULTS_DIR="$results_directory"
export SGI_TEST_BROWSER_RESULTS_DIR="$browser_results_directory"
export SGI_TEST_BROWSER_REPORT_DIR="$browser_report_directory"

compose() {
    docker compose --project-name "$project_name" -f "$COMPOSE_FILE" "$@"
}

cleanup() {
    status=$?
    if [ "$keep" -eq 0 ]; then
        compose logs --no-color app db > "$results_directory/docker-compose.log" || true
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
build_services="app"
if [ "$skip_browser" -eq 0 ] || [ "$include_visual" -eq 1 ]; then
    build_services="$build_services browser"
fi
# shellcheck disable=SC2086
compose build $build_services
if [ "$skip_quality" -eq 0 ]; then
    compose run --rm --no-deps quality
fi
compose up -d --wait db app
compose run --rm --no-deps integration

if [ "$skip_browser" -eq 0 ]; then
    compose run --rm --no-deps browser
fi

if [ "$include_visual" -eq 1 ]; then
    compose run --rm --no-deps visual
fi

echo 'Todos os serviços de teste concluíram com sucesso.'
