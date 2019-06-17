# Menu

- [启动Mysql](#启动Mysql)
    + [mysqld](#mysqld)
    + [设置root密码](#设置root密码)
    + [添加账户](#添加账户)

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
