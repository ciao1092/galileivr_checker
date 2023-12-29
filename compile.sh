#!/bin/sh
./vendor/bin/box compile || exit $?
mv index.phar galileivr_checker
