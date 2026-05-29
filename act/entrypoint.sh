#!/usr/bin/env bash

set -e

php artisan storage:link

php artisan octane:start --host=0.0.0.0 --port=9000 --server=swoole --workers=10 --max-requests=1000