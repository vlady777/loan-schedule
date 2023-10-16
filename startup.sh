#! /usr/bin/bash

docker compose up -d
until [ "$(docker inspect -f \{\{.State.Running\}\} loan_php)" = "true" ]; do
    sleep 0.1;
done;
docker exec loan_php composer install
if [ "$(docker inspect -f \{\{.State.Health.Status\}\} loan_mysql)" != "healthy" ]; then
    echo "Waiting for MySQL is ready..."
    until [ "$(docker inspect -f \{\{.State.Health.Status\}\} loan_mysql)" = "healthy" ]; do
        sleep 0.1;
    done;
fi
docker exec loan_php php bin/console doc:mig:mig -n
docker exec loan_php php bin/console doc:mig:mig -n -e test
docker exec loan_php php bin/console doc:sch:val

GREEN='\033[0;32m'
NC='\033[0m' # No Color
echo "${GREEN}API is ready to use. You are inside the container.${NC}"
docker exec -it loan_php bash
exit
