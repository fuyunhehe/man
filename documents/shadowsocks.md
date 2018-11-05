### 服务端

```shell
wget --no-check-certificate -O shadowsocks-all.sh https://raw.githubusercontent.com/teddysun/shadowsocks_install/master/shadowsocks-all.sh
chmod +x shadowsocks-all.sh
./shadowsocks-all.sh 2>&1 | tee shadowsocks-all.log
```



### Linux客户端

```shell
# 配置 shadowsocks源
sudo curl -k 'https://copr.fedorainfracloud.org/coprs/librehat/shadowsocks/repo/epel-7/librehat-shadowsocks-epel-7.repo' > /etc/yum.repo.d/shadowsocks.repo

sudo dnf install python2-pip.noarch
sudo pip install shadowsocks
sudo dnf install shadowsocks-libev.x86_64
#sudo dnf install perl-CPAN.noarch
#sudo cpan Net::Shadowsocks
# 配置config文件
sudo vim /etc/shadowsocks-libev/config.json
sudo ss-local -c /etc/shadowsocks-libev/config.json

# 验证
curl 'https://www.google.com.hk' --socks5 127.0.0.1:1080
```