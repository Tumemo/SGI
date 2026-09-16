#!/usr/bin/env sh
set -eu

session_dir="${SGI_SESSION_DIR-/app/test-results/sessions}"
upload_tmp_dir="${SGI_UPLOAD_TMP_DIR-/app/test-results/upload-tmp}"
upload_dir="${SGI_UPLOAD_DIR-/app/test-results/uploads}"
regulamentos_dir="${SGI_REGULAMENTOS_DIR-"$upload_dir/regulamentos"}"
fotos_dir="${SGI_FOTOS_DIR-"$upload_dir/fotos"}"
import_dir="${SGI_IMPORT_DIR-/app/test-results/imports}"

# O comando pode informar um caminho diferente do padrão, como ocorre na
# homologação com /app/storage. O PHP precisa encontrar esse diretório antes
# de aceitar qualquer multipart; caso contrário, um aviso de startup pode
# contaminar a resposta JSON da API.
for argument in "$@"; do
    case "$argument" in
        upload_tmp_dir=*) upload_tmp_dir="${argument#upload_tmp_dir=}" ;;
    esac
done

if [ -n "$session_dir" ]; then
    mkdir -p "$session_dir"
fi

mkdir -p \
    "$upload_tmp_dir" \
    "$upload_dir" \
    "$regulamentos_dir" \
    "$fotos_dir" \
    "$import_dir" \
    /app/tests/browser/test-results \
    /app/tests/browser/playwright-report

exec "$@"
