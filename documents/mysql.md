# Menu

- [启动Mysql](#启动Mysql)
    + [mysqld](#mysqld)
    + [设置root密码](#设置root密码)
    + [添加账户](#添加账户)
- [优化mysql性能]
    + [设置cache](#设置cache)

# 启动Mysql

## mysqld

```shell
#!/bin/sh

DATA_DIR='/data/soft/mysql'
LOG_DIR='/data/logs/mysql'
if [ ! -d $DATA_DIR ]; then
    sudo mkdir -p -m 777 $DATA_DIR
    INIT='--initialize' #初始化，首次运行时需要
fi

if [ ! -d $LOG_DIR ]; then
    sudo mkdir -p -m 777 $LOG_DIR
fi

$MYSQL_HOME/bin/mysqld --basedir=$MYSQL_HOME $INIT \
    --datadir=$DATA_DIR \
    --user=mysql \
    --log-error=$LOG_DIR/mysqld.log \
    --open-files-limit=1024 \
    --pid-file=$DATA_DIR/localhost.pid \
    --port=3306 &

[ $? -eq 0 ] && echo 'Success!' || echo 'Failed!'

#$MYSQL_HOME/bin/mysql_ssl_rsa_setup --datadir=$DATA_DIR

#$MYSQL_HOME/bin/mysqld_safe --datadir=$DATA_DIR --pid-file=$DATA_DIR/localhost.pid

#grep 'temporary password' $LOG_DIR/mysqld.log
#mysql -h localhost -uroot -p'xxxxx' #登陆mysql的root账户
```

## 设置root密码

```mysql
ALTER USER 'root'@'localhost' IDENTIFIED BY 'xxxxx'; #设置root密码
```

## 添加账户

```mysql
CREATE USER 'thief'@'localhost' IDENTIFIED BY ''; #创建不需要密码的mysql账户
SHOW GRANTS FOR 'thief'@'localhost'; #显示thief的权限
create database test; #创建test数据库
GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,DROP,ALTER,REFERENCES ON test.* TO 'thief'@'localhost'; #给thief操作test数据库的权限
```

[Menu](#menu)

## 设置cache

> 当MySQL开启了缓存模式（query_cache_type=1）后，mysql会把查询语句和查询结果保存在一张hash表中，下一次用同样的sql语句查询时，mysql会先从这张hash表中获取数据，如果缓存没有命中，则解析sql语句，查询数据库。 当缓存的数据达到最大值（query_cache_size） 后，mysql会把老的数据删除掉，写入信的数据。

通过以下命令查看设置结果
```mysql
MariaDB [(none)]> show variables like '%query_cache%';
+------------------------------+---------+
| Variable_name                | Value   |
+------------------------------+---------+
| have_query_cache             | YES     |
| query_cache_limit            | 1048576 |
| query_cache_min_res_unit     | 4096    |
| query_cache_size             | 1048576 |
| query_cache_strip_comments   | OFF     |
| query_cache_type             | DEMAND  |
| query_cache_wlock_invalidate | OFF     |
+------------------------------+---------+

MariaDB [(none)]> show status like '%Qcache%';
+-------------------------+---------+
| Variable_name           | Value   |
+-------------------------+---------+
| Qcache_free_blocks      | 1       |
| Qcache_free_memory      | 1031320 |
| Qcache_hits             | 0       |
| Qcache_inserts          | 0       |
| Qcache_lowmem_prunes    | 0       |
| Qcache_not_cached       | 1106663 |
| Qcache_queries_in_cache | 0       |
| Qcache_total_blocks     | 1       |
+-------------------------+---------+
```

[Menu](#menu)
