# This is a Demo app

## installations:

- clone the repo to your local development medium ( wamp/xamp/vagrant/docker/etc)
- your medium needs to have: apache/nginx + PHP 8.0/ or latest + composer + nodejs with npm + mysql 8
- open a console and check that you are in the root of the project folder
- the you need to make a copy of the .envDemo file as .env
```
cp .envDemo  .env
```
- uncommment the line  
DATABASE_URL="mysql://app:!ChangeMe!@127.0.0.1:3306/app?serverVersion=8.0.32&charset=utf8mb4"
and replace the app and !ChangeMe! with you mysql user and password
then replace /app?  with /your_database_name?
- run following commands:
```
composer install
npm install
npm run build
```

### After you have the project files you need to create and populate the mysql Database
- run the following commands
```
php bin/console doctrine:migrations:migrate
php bin/console app:seed-database
```
