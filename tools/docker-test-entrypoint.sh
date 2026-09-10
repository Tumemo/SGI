#!/usr/bin/env sh
set -eu

mkdir -p \
    /app/test-results/sessions \
    /app/test-results/upload-tmp \
    /app/test-results/uploads/regulamentos \
    /app/test-results/uploads/fotos \
    /app/test-results/imports \
    /app/tests/browser/test-results \
    /app/tests/browser/playwright-report

exec "$@"
