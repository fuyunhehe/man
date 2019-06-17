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
```

