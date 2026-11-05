#!/usr/bin/env bash

# Based on Laravel Sail: https://github.com/laravel/sail/blob/1.x/database/mariadb/create-testing-database.sh
# Copyright (c) Taylor Otwell and Chris Fidao.
# Original testing-database implementation by Jess Archer, with subsequent
# contributions by Bram, Tom Coonen, and Hafez Divandari.
# Licensed under the MIT License; see LICENSE.md and the upstream repository.

/usr/bin/mariadb --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS testing;
EOSQL

if [ -n "$MYSQL_USER" ]; then
/usr/bin/mariadb --user=root --password="$MYSQL_ROOT_PASSWORD" <<-EOSQL
    GRANT ALL PRIVILEGES ON \`testing%\`.* TO '$MYSQL_USER'@'%';
EOSQL
fi
