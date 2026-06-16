all: clean test

test:
	vendor_google_microsoft/bin/phpunit

coverage:
	vendor_google_microsoft/bin/phpunit --coverage-html=artifacts/coverage

view-coverage:
	open artifacts/coverage/index.html

clean:
	rm -rf artifacts/*
