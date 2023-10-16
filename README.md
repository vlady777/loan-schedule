# Loan schedule API homework implementation

It's my implementation for the [task](https://github.com/lande-finance/home-task-doc) using Docker, PHP 8.2, Mysql 8, 
Symfony 6.3, type hinting with enabled strict mode, DDD, etc.

Project directory structure is a fully standard.

## Setup and run

Clone project using terminal
```shell
git clone git@github.com:vlady777/loan-schedule.git
```
Enter in project directory
```shell
cd loan-schedule
```
Run shell script to create containers and prepare project
```shell
sh startup.sh
```
Wait until message `API is ready to use. You are inside the container.`.

That's it.

## API documentation

Open browser, go to the https://localhost:8010/ and confirm security exclusion. This is the main page of the API 
documentation. Here you can see all available API endpoints and send requests to any of them directly from UI 
using `Try it out` button. It's a convenient way to test API, to see requests format, etc.

As well, you can use any REST client or anything else to make http requests to https://localhost:8010/ host.

Example of request body to create new Loan:
```json
{
  "amount": 1000000,
  "term": 12,
  "interestRate": 400,
  "defaultEuriborRate": 394
}
```
Example of request body create new Euribor for Loan #1
```json
{
  "segmentNumber": 6,
  "rate": 410,
  "loan": "/loans/1"
}
```

## Testing, useful commands

Execute commands in terminal (inside container).

Run all test
```shell
php bin/phpunit
```
Run unit-tests only
```shell
php bin/phpunit tests/Unit/
```
Run functional-tests only
```shell
bin/phpunit tests/Functional/
```
Validate database schema and fields mapping
```shell
php bin/console doctrine:schema:validate
```
Display all routes
```shell
php bin/console debug:router
```

## If something went wrong during `startup.sh` script you can set up project manually.

1. Create containers 
```shell
docker compose up -d
```
2. Enter to the php container
```shell
docker exec -it loan_php bash
```
3. Install vendors
```shell
composer install
```
4. Prepare main database
```shell
php bin/console doc:mig:mig -n
```
5. Prepare database for tests
```shell
php bin/console doc:mig:mig -n -e test
```

That's it.
