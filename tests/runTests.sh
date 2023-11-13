#!/bin/bash

set -e -o pipefail

cd "$(dirname "$0")"

../vendor/bin/phpunit $@
