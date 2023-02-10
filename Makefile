.PHONY: install

# Install dependencies
install:
	composer install

# quality

cs:
	PHP_CS_FIXER_IGNORE_ENV=1 vendor/bin/php-cs-fixer fix

test: ## `make test o="--group unit"`
	vendor/bin/phpunit --log-junit var/junit.xml $o

coverage: ## `make coverage o="--group unit"`
	XDEBUG_MODE=coverage php vendor/bin/phpunit --log-junit var/junit.xml --coverage-html var/coverage $o

profile-test: ## `make profile-test o="--group unit"`
	XDEBUG_MODE=profile php -d xdebug.output_dir="var/profiler" vendor/bin/phpunit $o

phpstan:
	vendor/bin/phpstan --memory-limit=-1 --xdebug

rector: ## `make rector o="--dry-run"`
	vendor/bin/rector process $o

phpmetrics: coverage
	php -d error_reporting="E_ALL & ~E_DEPRECATED" vendor/bin/phpmetrics --git --report-html=var/report src --junit=var/junit.xml

quality: rector cs phpstan phpmetrics
