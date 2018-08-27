# 安装

## server

### 预备条件

- nginx
- php
- php-fpm
- mysql | mariadb

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

## agent

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

# API

## host

- host.get

```json
/**
 * params:
 *	[]
 */
{
  "hostid": "10084",
  "proxy_hostid": "0",
  "host": "Zabbix server",
  "status": "0",
  "disable_until": "0",
  "error": "",
  "available": "1",
  "errors_from": "0",
  "lastaccess": "0",
  "ipmi_authtype": "-1",
  "ipmi_privilege": "2",
  "ipmi_username": "",
  "ipmi_password": "",
  "ipmi_disable_until": "0",
  "ipmi_available": "0",
  "snmp_disable_until": "0",
  "snmp_available": "0",
  "maintenanceid": "0",
  "maintenance_status": "0",
  "maintenance_type": "0",
  "maintenance_from": "0",
  "ipmi_errors_from": "0",
  "snmp_errors_from": "0",
  "ipmi_error": "",
  "snmp_error": "",
  "jmx_disable_until": "0",
  "jmx_available": "0",
  "jmx_errors_from": "0",
  "jmx_error": "",
  "name": "Zabbix server",
  "flags": "0",
  "templateid": "0",
  "description": "",
  "tls_connect": "1",
  "tls_accept": "1",
  "tls_issuer": "",
  "tls_subject": "",
  "tls_psk_identity": "",
  "tls_psk": ""
}
```

## graph

- graph.get

```json
/**
 * host.get:效果等同
 *	{
 *		"selectGraphs": "extend"
 *	}
 */
{
  "graphid": "517",
  "name": "Zabbix internal process busy %",
  "width": "900",
  "height": "200",
  "yaxismin": "0.0000",
  "yaxismax": "100.0000",
  "templateid": "406",
  "show_work_period": "1",
  "show_triggers": "1",
  "graphtype": "0",
  "show_legend": "1",
  "show_3d": "0",
  "percent_left": "0.0000",
  "percent_right": "0.0000",
  "ymin_type": "1",
  "ymax_type": "1",
  "ymin_itemid": "0",
  "ymax_itemid": "0",
  "flags": "0"
}
```

## items

```json
{
  "itemid": "23327",
  "type": "0",
  "snmp_community": "",
  "snmp_oid": "",
  "hostid": "10084",
  "name": "Host name of zabbix_agentd running",
  "key_": "agent.hostname",
  "delay": "3600",
  "history": "7",
  "trends": "0",
  "status": "0",
  "value_type": "1",
  "trapper_hosts": "",
  "units": "",
  "multiplier": "0",
  "delta": "0",
  "snmpv3_securityname": "",
  "snmpv3_securitylevel": "0",
  "snmpv3_authpassphrase": "",
  "snmpv3_privpassphrase": "",
  "formula": "1",
  "error": "",
  "lastlogsize": "0",
  "logtimefmt": "",
  "templateid": "23319",
  "valuemapid": "0",
  "delay_flex": "",
  "params": "",
  "ipmi_sensor": "",
  "data_type": "0",
  "authtype": "0",
  "username": "",
  "password": "",
  "publickey": "",
  "privatekey": "",
  "mtime": "0",
  "flags": "0",
  "interfaceid": "1",
  "port": "",
  "description": "",
  "inventory_link": "0",
  "lifetime": "30",
  "snmpv3_authprotocol": "0",
  "snmpv3_privprotocol": "0",
  "state": "0",
  "snmpv3_contextname": "",
  "evaltype": "0",
  "lastclock": "1498714127",
  "lastns": "102647224",
  "lastvalue": "Zabbix server",
  "prevvalue": "Zabbix server"
}
```

