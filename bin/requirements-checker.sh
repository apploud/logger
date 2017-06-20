#!/usr/bin/env bash

if ! hash composer 2>/dev/null; then
	echo 'Composer required to run this checker'
	exit 127
fi

COMPOSER_HOME=$(composer config --list --global | grep -w home | cut -d" " -f2)

if [[ ! -f "$COMPOSER_HOME/vendor/bin/composer-require-checker" ]]; then
	echo 'Install composer requirements checker:'
	echo
	echo 'composer global require maglnet/composer-require-checker'
	echo
	exit 127
fi

"$COMPOSER_HOME/vendor/bin/composer-require-checker" check composer.json
