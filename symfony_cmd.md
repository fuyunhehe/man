create bundle:
```shell
php bin/console generate:bundle --namespace=Manager/AuthorityBundle --format=yml
```
clear cache:
```shell
php bin/console cache:clear --env=prod --no-debug
```
create entity:
```shell
php bin/console doctrine:generate:entity
```
