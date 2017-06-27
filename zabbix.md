# zabbix

## 安装

预备条件

- nginx
- php
- php-fpm
- mysql | mariadb

### zabbix-server

```shell
# 具体版本可以在http://repo.zabbix.com/zabbix/3.2/rhel/7/x86_64/中查找
sudo rpm -ivh http://repo.zabbix.com/zabbix/3.0/rhel/7/x86_64/zabbix-release-3.0-1.el7.noarch.rpm
sudo dnf install zabbix-server-mysql zabbix-web-mysql zabbix-get zabbix-agent
```

修改文件属性和属主

```shell
# 属主取决于php-fpm的group
sudo chown nginx:nginx /etc/zabbix/web/
sudo chown nginx /var/log/php-fpm
sudo chown root:nginx /var/lib/php/session/
sudo chown nginx:nginx -R /usr/share/zabbix
```

修改php配置

```ini
# /etc/php.d/zabbix.ini
date.timezone = Asia/Shanghai
post_max_size = 16M
max_execution_time = 300
max_input_time = 300
```

创建数据库

```mysql
# mysql -uroot -p
CREATE DATABASE zabbix DEFAULT CHARACTER SET utf8 COLLATE utf8_bin;
# GRANT ALL ON zabbix.* TO 'zabbix'@'%' IDENTIFIED BY 'zabbix';
GRANT ALL ON zabbix.* TO 'zabbix'@'localhost' IDENTIFIED BY 'zabbix';
```

导入数据

```shell
# web账号密码 Admin/zabbix
zcat /usr/share/doc/zabbix-server-mysql-3.0.3/create.sql.gz | mysql -uroot -p zabbix
```

修改nginx配置

```nginx
server {
    listen 8360;
    server_name vhost.vm.com;
    root /var/www/html;
    index index.php index.html;
    access_log   /var/log/nginx/access_vhost.log;
    error_log   /var/log/nginx/error_vhost.log;

    location ~ \.php$ {
        #fastcgi_pass 127.0.0.1:9000;
        fastcgi_pass php-fpm;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
}
```

启动服务

```shell
sudo systemctl start mariadb
sudo systemctl start php-fpm
sudo systemctl start nginx
sudo systemctl start zabbix-server
```

### zabbix-agent

```shell
sudo rpm -ivh http://repo.zabbix.com/zabbix/3.0/rhel/7/x86_64/zabbix-release-3.0-1.el7.noarch.rpm
sudo rpm --import /etc/pki/rpm-gpg/RPM-GPG-KEY-EPEL-7
sudo dnf install zabbix-agent
```

修改配置

```shell
# vim /etc/zabbix/zabbix_agentd.conf
Server=192.168.60.103
ServerActive=192.168.60.103
Hostname=client01
```

启动服务

```shell
sudo systemctl start zabbix-agent
```

