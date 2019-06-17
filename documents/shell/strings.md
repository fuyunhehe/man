# 字符串处理

- [字符串提取](#字符串提取)
    + [sed](#sed)
    + [grep](#grep)
    + [awk](#awk)

## 字符串提取

文件a.html中内容为：
`<html><head><meta>xxx</meta><link href=//xxx.demo.css><script type=text/javascript src=//xxx.demo.js></script></head></html>`

### sed

若想提取`<head>`与`</head>`中间的内容，可以使用sed方法进行处理，如：`sed 's/.*<head>\(.*\)<\/head>.*/\1/g' a.html`
**sed**主要功能为字符串替换，上述语句中，是使用正则匹配到的`(.*)`中的项即`\1`来替换整个匹配到的内容，从而提取结果

### grep

若想提取css与js文件信息，则可以使用命令：`grep -Eo '//[/a-zA-Z0-9\.]+demo\.\w+\.(js|css)' a.html`
**grep**主要功能为字符串查找，上述语句中，使用正则查找到需要匹配内容，并且仅输出匹配内容
`-E` 使用正则查找字符串
`-o` 仅输出匹配内容

### awk

**awk**主要用于字符串切割，也可进行编程，功能强大，最常用功能为：`-F`参数指定分隔符，将每行根据分隔符切分成多个字符串

[Top](#字符串处理)
