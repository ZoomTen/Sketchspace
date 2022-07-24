.PHONY: lint

DIRECTORIES := Main Library Requests Objects

lint:
	vendor/bin/phpstan analyse $(DIRECTORIES) --level 9
