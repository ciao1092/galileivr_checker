#!/bin/sh

if [ ! -f "./vendor/bin/box" ]; then
	composer require --dev bamarni/composer-bin-plugin
	composer bin box require --dev humbug/box
fi

./vendor/bin/box compile || exit $?
mv index.phar galileivr_checker
