# Menu

- [bash](#bash)
    + [.bashrc](#.bashrc)
- [vim](#vim)
    + [.vimrc](#.vimrc)
- [系统时间](#系统时间)
    + [网络较准时间](#网络较准时间)

## bash

### .bashrc

```shell
# svn 编辑器
export SVN_EDITOR=vim
# 语言显示变量
export LANG=zh_CN.UTF-8
export LC_ALL=zh_CN.UTF-8
# 终端配色
export TERM=xterm-256color

# 记录历史命令数
export HISTSIZE=100000

# 显示长路径
#export PS1="\[\e[32m\]\[\e[m\]\[\e[31m\]\u\[\e[m\]\[\e[33m\]@\[\e[m\]\[\e[32m\]\h\[\e[m\]:\[\e[36m\]\w\[\e[m\]\[\e[32m\]\[\e[m\]\\$ "
# 显示短路径
export PS1="\[\e[32m\]\[\e[m\]\[\e[31m\]\u\[\e[m\]\[\e[33m\]@\[\e[m\]\[\e[32m\]\h\[\e[m\]:\[\e[36m\]\W\[\e[m\]\[\e[32m\]\[\e[m\]\\$ "
```

## vim

### .vimrc

```shell
set tabstop=4
set softtabstop=4
set shiftwidth=4
set wildmode=longest:full
set wildmenu
set smartindent
set ignorecase smartcase "大小写不敏感，但是同时存在大小写时，则敏感
set hls "查找信息高亮
colorscheme desert "配色
syntax on "语法高亮显示
"set nu "显示行号
"set showmatch "设置匹配模式，类似当输入一个左括号时会匹配相应的右括号
```

## 系统参数

### 网络较准时间

`sudo ntpdate -u asia.pool.ntp.org`

### 字符集

`sudo locale-gen zh_CN.utf8`
