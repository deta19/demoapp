# This is a Demo app

## installations:

- clone the repo to your local development medium ( wamp/xamp/vagrant/docker/etc)
- your medium needs to have: apache/nginx + PHP 8.0/ or latest + composer + nodejs with npm + mysql 8
- open a console and check that you are in the root of the project folder
- run following commands:
-- composer install
-- npm run build

### After you have the project files you need to create and populate the mysql Database
- run the following commands
-- php bin/console doctrine:migrations:migrate
-- php bin/comsole app:seed-database
