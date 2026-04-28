.DEFAULT_GOAL := test
.PHONY: test

test:
	php bin/phpunit

default: test
