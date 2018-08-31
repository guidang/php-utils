## 利用VPS离线下载文件

### 安装使用
- 将 download.php 和 download.exp 上传到同一目录，并使其支持网站
- 移除禁用的函数(php.ini)，display_function 中移除 exec,shell_exec
- 安装 expect (yum install expect -y)
- 修改 download.exp 中的 basepath 为文件将要保存到的目录