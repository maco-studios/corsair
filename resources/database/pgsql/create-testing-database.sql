-- Based on Laravel Sail: https://github.com/laravel/sail/blob/1.x/database/pgsql/create-testing-database.sql
-- Copyright (c) Taylor Otwell and Chris Fidao.
-- Original testing-database implementation by Jess Archer.
-- Licensed under the MIT License; see LICENSE.md and the upstream repository.

SELECT 'CREATE DATABASE testing'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = 'testing')\gexec
