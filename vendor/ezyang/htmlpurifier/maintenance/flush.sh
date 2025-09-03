#!/bin/bash
set -ex
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" >/dev/null && pwd )"
php "$DIR/generate-includes.php"
php "$DIR/generate-schema-cache.php"
php "$DIR/flush-definition-cache.php"
php "$DIR/generate-standalone.php"
php "$DIR/config-scanner.php"
