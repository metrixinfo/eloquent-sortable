#!/usr/bin/env bash

echo "PHP Lint"
vendor/bin/parallel-lint --blame src

echo "Fixing code sniffs"
vendor/bin/phpcbf -p -s --standard=build/phpcs.xml

echo "Running code sniffer"
vendor/bin/phpcs -p -s --standard=build/phpcs.xml

echo "Running Mess Detector"
vendor/bin/phpmd app text build/phpmd.xml

echo "Running Tests"
vendor/bin/phpunit -d memory_limit=512M
