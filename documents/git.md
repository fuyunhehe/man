# 基本用法

```shell
# 拉取分支
git clone git@github.com:fuyunhehe/man.git

# 打包
git archive --prefix=ConvertDb/ head -o ~/tmp1/ConvertDb_TEMP.tar.gz

# 删除没用的引用
git remote prune origin

# 删除远端分支
git push origin -d xxx

# 删除tag
git tag -d <tagname> # 删除本地tag
git push origin :refs/tags/<tagname> # 这是删除远程tag的方法，推送一个空tag到远程tag
```

