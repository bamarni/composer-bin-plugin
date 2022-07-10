#!/usr/bin/env bash

set -Eeuo pipefail

# Set env envariables in order to experience a behaviour closer to what happens
# in the CI locally. It should not hurt to set those in the CI as the CI should
# contain those values.
export CI=1
export COMPOSER_NO_INTERACTION=1

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

# For some reasons using the --no-dev flag in the CI is resulting in the
# (required) dependency bamarni/composer-bin-plugin to not be installed which
# obviously messes up the whole test.
composer update --no-plugins

composer update --no-dev

# Actual command to execute the test itself
composer bin ns1 show --direct --name-only 2>&1 | tee >> actual.txt || true
