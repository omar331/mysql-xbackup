#!/usr/bin/env bash

cd /opt/app

composer install

source ./envvars

php restore-run.php

