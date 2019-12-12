include .env

up:
	docker-compose up -d
down:
	docker-compose down
stop:
	docker-compose stop
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
reset:
	@echo "Dropping all tables. Drupal might not be installed, ignore errors."
	-${DC_DRUPAL} database:drop -y
	@echo "Stopping Docker so composer packages can be removed without sharing"
	make stop
	@echo "Removing all composer packages"