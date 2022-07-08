#!/usr/bin/env bash

set -Eeuo pipefail

rm -rf vendor-bin/*/vendor || true

composer bin all update --verbose 2>&1 | tee > actual.txt
