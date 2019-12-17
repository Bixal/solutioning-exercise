include .env

up:
	docker-compose up -d
down:
	docker-compose down
stop:
	docker-compose stop
installdrupal:
	docker-compose run --rm php drush si --root=/var/www/html/docroot --yes
	docker-compose run --rm php vendor/bin/drupal --root=/var/www/html/docroot entity:delete shortcut 1 --all --yes --no-interaction
	docker-compose run --rm php vendor/bin/drupal --root=/var/www/html/docroot config:import --yes --no-interaction
ally:
	@echo "Running Ally checks on the local network"
	docker run --rm --network="solutioning-exercise_default" frvge/pa11y http://apache/
phpcs:
	@echo "Running coding standards on custom code"
	docker-compose run --rm php vendor/bin/phpcs --standard=vendor/drupal/coder/coder_sniffer/Drupal docroot/modules/custom --ignore=*.min.js --ignore=*.min.css
	docker-compose run --rm php vendor/bin/phpcs --standard=vendor/drupal/coder/coder_sniffer/Drupal docroot/themes/custom --ignore=*.min.js --ignore=*.min.css
phpcbf:
	@echo "Beautifying custom code"
	docker-compose run --rm php vendor/bin/phpcbf --standard=vendor/drupal/coder/coder_sniffer/Drupal docroot/modules/custom --ignore=*.min.js --ignore=*.min.css
	docker-compose run --rm php vendor/bin/phpcbf --standard=vendor/drupal/coder/coder_sniffer/Drupal docroot/themes/custom --ignore=*.min.js --ignore=*.min.css
test:
	@echo "Running Unit Testing"
	docker-compose run --rm php vendor/bin/phpunit -c docroot/core/phpunit.xml.dist docroot/modules/custom/
codeck:
	@echo "Running code standards, beautifying, and running ally checks"
	make ally
	make phpcbf
	make phpcs
uli:
	docker-compose run --rm php vendor/bin/drupal --uri=https://$(PROJECT_BASE_URL):8000 --root=/var/www/html/docroot user:login:url 1
cr:
	docker-compose run --rm php vendor/bin/drupal cr --root=/var/www/html/docroot
cex:
	docker-compose run --rm php vendor/bin/drupal config:export --root=/var/www/html/docroot
cim:
	docker-compose run --rm php vendor/bin/drupal config:import --root=/var/www/html/docroot