#!/usr/bin/env bash

set -Eeuo pipefail

readonly ORIGINAL_WORKING_DIR=$(pwd)

trap "cd ${ORIGINAL_WORKING_DIR}" err exit

# Change to script directory
cd "$(dirname "$0")"

# Ensure we have a clean state
rm -rf actual.txt || true
rm -rf composer.lock || true
rm -rf vendor || true
rm -rf vendor-bin/*/composer.lock || true
rm -rf vendor-bin/*/vendor || true

# Actual command to execute the test itself
composer update --dev 2>&1 | tee > actual.txt || true
echo "––––––––––––––" >> actual.txt
composer bin ns1 show --direct --name-only 2>&1 | tee >> actual.txt || true
