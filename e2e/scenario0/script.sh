#!/usr/bin/env bash

set -Eeuo pipefail

rm -rf vendor-bin/*/vendor || true

composer bin all update 2>&1 | tee > actual.txt
