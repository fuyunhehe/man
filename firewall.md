

## 简述

> firewall是centos7中用来替代iptables的功能模块

### 相关概念

- 配置路径

  ```shell
  #系统firewall配置文件模板，该目录下文件通常不需要修改
  /usr/lib/firewalld/
  #个人配置
  /etc/firewalld/
  ```

- 目录结构

  - zones 防火墙适用范围，默认public

    ```shell
    -rw-r--r--. 1 root root 299 Mar  3 10:41 block.xml
    -rw-r--r--. 1 root root 293 Mar  3 10:41 dmz.xml
    -rw-r--r--. 1 root root 291 Mar  3 10:41 drop.xml
    -rw-r--r--. 1 root root 304 Mar  3 10:41 external.xml
    -rw-r--r--. 1 root root 369 Mar  3 10:41 home.xml
    -rw-r--r--. 1 root root 384 Mar  3 10:41 internal.xml
    -rw-r--r--. 1 root root 315 Mar  3 10:41 public.xml
    -rw-r--r--. 1 root root 162 Mar  3 10:41 trusted.xml
    -rw-r--r--. 1 root root 311 Mar  3 10:41 work.xml
    ```

  - services  服务模板

    ```shell
    -rw-r--r--. 1 root root 412 Mar  3 10:41 amanda-client.xml
    -rw-r--r--. 1 root root 447 Mar  3 10:41 amanda-k5-client.xml
    -rw-r--r--. 1 root root 320 Mar  3 10:41 bacula-client.xml
    -rw-r--r--. 1 root root 346 Mar  3 10:41 bacula.xml
    -rw-r--r--. 1 root root 294 Mar  3 10:41 ceph-mon.xml
    -rw-r--r--. 1 root root 305 Mar  3 10:41 ceph.xml
    -rw-r--r--. 1 root root 305 Mar  3 10:41 dhcpv6-client.xml
    -rw-r--r--. 1 root root 234 Mar  3 10:41 dhcpv6.xml
    -rw-r--r--. 1 root root 227 Mar  3 10:41 dhcp.xml
    -rw-r--r--. 1 root root 346 Mar  3 10:41 dns.xml
    -rw-r--r--. 1 root root 374 Mar  3 10:41 docker-registry.xml
    -rw-r--r--. 1 root root 228 Mar  3 10:41 dropbox-lansync.xml
    -rw-r--r--. 1 root root 836 Mar  3 10:41 freeipa-ldaps.xml
    -rw-r--r--. 1 root root 836 Mar  3 10:41 freeipa-ldap.xml
    -rw-r--r--. 1 root root 315 Mar  3 10:41 freeipa-replication.xml
    -rw-r--r--. 1 root root 374 Mar  3 10:41 ftp.xml
    -rw-r--r--. 1 root root 529 Mar  3 10:41 high-availability.xml
    -rw-r--r--. 1 root root 448 Mar  3 10:41 https.xml
    -rw-r--r--. 1 root root 353 Mar  3 10:41 http.xml
    -rw-r--r--. 1 root root 372 Mar  3 10:41 imaps.xml
    -rw-r--r--. 1 root root 327 Mar  3 10:41 imap.xml
    -rw-r--r--. 1 root root 454 Mar  3 10:41 ipp-client.xml
    -rw-r--r--. 1 root root 427 Mar  3 10:41 ipp.xml
    -rw-r--r--. 1 root root 554 Mar  3 10:41 ipsec.xml
    -rw-r--r--. 1 root root 264 Mar  3 10:41 iscsi-target.xml
    -rw-r--r--. 1 root root 182 Mar  3 10:41 kadmin.xml
    -rw-r--r--. 1 root root 233 Mar  3 10:41 kerberos.xml
    -rw-r--r--. 1 root root 221 Mar  3 10:41 kpasswd.xml
    -rw-r--r--. 1 root root 232 Mar  3 10:41 ldaps.xml
    -rw-r--r--. 1 root root 199 Mar  3 10:41 ldap.xml
    -rw-r--r--. 1 root root 385 Mar  3 10:41 libvirt-tls.xml
    -rw-r--r--. 1 root root 389 Mar  3 10:41 libvirt.xml
    -rw-r--r--. 1 root root 424 Mar  3 10:41 mdns.xml
    -rw-r--r--. 1 root root 473 Mar  3 10:41 mosh.xml
    -rw-r--r--. 1 root root 211 Mar  3 10:41 mountd.xml
    -rw-r--r--. 1 root root 190 Mar  3 10:41 ms-wbt.xml
    -rw-r--r--. 1 root root 171 Mar  3 10:41 mysql.xml
    -rw-r--r--. 1 root root 324 Mar  3 10:41 nfs.xml
    -rw-r--r--. 1 root root 389 Mar  3 10:41 ntp.xml
    -rw-r--r--. 1 root root 335 Mar  3 10:41 openvpn.xml
    -rw-r--r--. 1 root root 433 Mar  3 10:41 pmcd.xml
    -rw-r--r--. 1 root root 474 Mar  3 10:41 pmproxy.xml
    -rw-r--r--. 1 root root 544 Mar  3 10:41 pmwebapis.xml
    -rw-r--r--. 1 root root 460 Mar  3 10:41 pmwebapi.xml
    -rw-r--r--. 1 root root 357 Mar  3 10:41 pop3s.xml
    -rw-r--r--. 1 root root 348 Mar  3 10:41 pop3.xml
    -rw-r--r--. 1 root root 181 Mar  3 10:41 postgresql.xml
    -rw-r--r--. 1 root root 509 Mar  3 10:41 privoxy.xml
    -rw-r--r--. 1 root root 261 Mar  3 10:41 proxy-dhcp.xml
    -rw-r--r--. 1 root root 424 Mar  3 10:41 ptp.xml
    -rw-r--r--. 1 root root 414 Mar  3 10:41 pulseaudio.xml
    -rw-r--r--. 1 root root 297 Mar  3 10:41 puppetmaster.xml
    -rw-r--r--. 1 root root 520 Mar  3 10:41 radius.xml
    -rw-r--r--. 1 root root 559 Mar  3 10:41 RH-Satellite-6.xml
    -rw-r--r--. 1 root root 214 Mar  3 10:41 rpc-bind.xml
    -rw-r--r--. 1 root root 311 Mar  3 10:41 rsyncd.xml
    -rw-r--r--. 1 root root 384 Mar  3 10:41 samba-client.xml
    -rw-r--r--. 1 root root 461 Mar  3 10:41 samba.xml
    -rw-r--r--. 1 root root 337 Mar  3 10:41 sane.xml
    -rw-r--r--. 1 root root 577 Mar  3 10:41 smtps.xml
    -rw-r--r--. 1 root root 550 Mar  3 10:41 smtp.xml
    -rw-r--r--. 1 root root 308 Mar  3 10:41 snmptrap.xml
    -rw-r--r--. 1 root root 342 Mar  3 10:41 snmp.xml
    -rw-r--r--. 1 root root 173 Mar  3 10:41 squid.xml
    -rw-r--r--. 1 root root 463 Mar  3 10:41 ssh.xml
    -rw-r--r--. 1 root root 496 Mar  3 10:41 synergy.xml
    -rw-r--r--. 1 root root 444 Mar  3 10:41 syslog-tls.xml
    -rw-r--r--. 1 root root 329 Mar  3 10:41 syslog.xml
    -rw-r--r--. 1 root root 393 Mar  3 10:41 telnet.xml
    -rw-r--r--. 1 root root 301 Mar  3 10:41 tftp-client.xml
    -rw-r--r--. 1 root root 437 Mar  3 10:41 tftp.xml
    -rw-r--r--. 1 root root 336 Mar  3 10:41 tinc.xml
    -rw-r--r--. 1 root root 771 Mar  3 10:41 tor-socks.xml
    -rw-r--r--. 1 root root 211 Mar  3 10:41 transmission-client.xml
    -rw-r--r--. 1 root root 593 Mar  3 10:41 vdsm.xml
    -rw-r--r--. 1 root root 475 Mar  3 10:41 vnc-server.xml
    -rw-r--r--. 1 root root 310 Mar  3 10:41 wbem-https.xml
    -rw-r--r--. 1 root root 509 Mar  3 10:41 xmpp-bosh.xml
    -rw-r--r--. 1 root root 488 Mar  3 10:41 xmpp-client.xml
    -rw-r--r--. 1 root root 264 Mar  3 10:41 xmpp-local.xml
    -rw-r--r--. 1 root root 545 Mar  3 10:41 xmpp-server.xml
    ```

- 示例

  工作环境下，开放http服务8360端口

  1. 将模板work.xml拷贝到zones目录下

     `sudo cp /usr/lib/firewalld/zones/work.xml /etc/firewalld/zones/.`

  2. 编辑work.xml

     ```xml
     <?xml version="1.0" encoding="utf-8"?>
     <zone>
       <short>Work</short>
       <description>For use in work areas. You mostly trust the other computers on networks to not harm your computer. Only selected incoming connections are accepted.</description>
       <service name="ssh"/>
       <service name="dhcpv6-client"/>
       <service name="http"/> <!--使用services下http.xml配置规则-->
     </zone>
     ```

  3. 将模板http.xml拷贝到services目录下

     `sudo cp /usr/lib/firewalld/services/http.xml /etc/firewalld/services/.`

  4. 编辑http.xml

     ```xml
     <?xml version="1.0" encoding="utf-8"?>
     <service>
       <short>WWW (HTTP)</short>
       <description>HTTP is the protocol used to serve Web pages. If you plan to make your Web server publicly available, enable this option. This option is not required for viewing pages locally or developing Web pages.</description>
       <port protocol="tcp" port="80"/>
       <port protocol="tcp" port="8360"/> <!--对外开放8360端口-->
     </service>
     ```

  5. 重载firewall服务

     `sudo systemctl reload firewalld.service`

