include .env

up:
	docker-compose up -d
down:
	docker-compose down
stop:
	docker-compose stop
installdrupal:
	docker-compose run --rm php docroot/vendor/bin/drupal --root=/var/www/html/docroot site:install standard --yes --force --no-interaction
	docker-compose run --rm php docroot/vendor/bin/drupal --root=/var/www/html/docroot config:import --yes --no-interaction
	docker-compose run --rm php docroot/vendor/bin/drupal --root=/var/www/html/docroot module:uninstall shortcut --yes --force --no-interaction
ally:
	@echo "Running Ally checks on the local network"
	docker run --rm --network="solutioning-exercise_default" frvge/pa11y http://apache/
phpcs:
	@echo "Running coding standards on custom code"
	docker-compose run --rm php docroot/vendor/bin/phpcs --standard=docroot/vendor/drupal/coder/coder_sniffer/Drupal docroot/modules/custom --ignore=*.min.js --ignore=*.min.css

phpcbf:
	@echo "Beautifying custom code"
	docker-compose run --rm php docroot/vendor/bin/phpcbf --standard=docroot/vendor/drupal/coder/coder_sniffer/Drupal docroot/modules/custom --ignore=*.min.js --ignore=*.min.css
codeck:
	@echo "Running code standards, beautifying, and running ally checks"
	make ally
	make phpcbf
	make phpcs
uli:
	docker-compose run --rm php docroot/vendor/bin/drupal --uri=https://$(PROJECT_BASE_URL):8000 --root=/var/www/html/docroot user:login:url 1