# Menu

- [server端](#server端)
    + [部署镜像服务器](#部署镜像服务器)
- [client端](#client端)
    + [配置非安全仓库](#配置非安全仓库)
    + [构建镜像](#构建镜像)
    + [上传镜像](#上传镜像)
    + [下载镜像](#下载镜像)

# server端

## 部署镜像服务器

`docker run -d -p 5000:5000 --restart=always --name registry registry:2`

# client端

## 配置非安全仓库

1. 编辑配置文件
```json
// 编辑/etc/docker/daemon.json
{
  "insecure-registries": [
    "59.153.75.150:6000"
  ]
}
```

2. 重启服务进程
`systemctl restart docker`

## 构建镜像

可以采用两种方式
- 从docker hub直接下载
`docker pull centos`
- 编写docker file，build镜像

## 上传镜像

1. 打tag
`docker tag  centos:latest  IP:5000/tulong/centos:标签`
> 备注：IP:5000是固定格式，指定使用的仓库的地址和端口。tulong是docker registry的namespace，可以是公司名称。
2. 上传
`docker push IP:5000/tulong/centos:标签`
3. 验证
使用下列命令，验证上传的镜像，会显示存在的镜像
`curl  -XGET  http://IP:5000/v2/_catalog`

## 下载镜像

`docker pull IP:5000/tulong/centos:标签`

[Menu](#menu)
