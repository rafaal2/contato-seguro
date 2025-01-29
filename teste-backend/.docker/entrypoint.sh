#!/bin/bash

composer install

cd public && php -S 0.0.0.0:8000
