install_node_deps:
	docker run --rm -i -t -v .:/app -w /app devasdf/phpswoole:8.3-node-dev sh -c "npm i"


install_deps: install_node_deps
	docker run --rm -i -t -v .:/app -w /app devasdf/phpswoole:8.3-node-dev sh -c "composer install"