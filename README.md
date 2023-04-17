# 平台功能

- GB28181 sip 网关
- 实时监控
- 视频录制

# Manual
https://www.workerman.net/doc/webman
# 环境要求
1. php >= 7.3
2. mysql5.7以上
3. redis
# 安装
- 1 安装依赖扩展包 composer install
- 2 进入项目目录，执行数据库迁移 `php bin/phpmig migrate`
- 3 系统初始化 `php console system:init`
- 4 `cp .env.example .env` (注意：.env文件需要将# 注释内容删除)

# 运行
```shell
# 帮助
php start.php -h 
# 启动
php start.php start
# 守护进程启动
php start.php start -d
# 重启
php start.php restart
# 停止
php start.php stop
# 重载
php start.php reload
# 状态
php start.php status
# 命令行查看
php console
```
# 业务
## biz 规范

1. dao 层 和service 层 命名注意区分单复数
2. service层以get 或find 命名的区别
   - `get{...}` 表示获取已某个条件获取单条数据得到一个索引数组
   - `find{...}`表示获取已某个条件获取单条数据得到一个关联数组，数组的键一般为id或者关联id
3. dao层以get 或find 命名的区别
   - `get{...}` 表示获取一数据，eg:`getByCode`,对应dao 实例方法是`return $this->getByFields()` 
   - `find{...}`表示获获取多条数据eg:`findAllByCode`,对应dao 实例方法是`return $this->findByFields()`
## 生成biz
   ```shell
     php console make:biz {服务名称} [{数据表名}]
```
## GB28181 常用命令

1. 合作方绑定设备

```shell
 php console thirdParty:bind-device 合作方key 类型【nvr、ipc】数量【一般填写：all】
```
2. 录像机绑定摄像头
``` shell
php console videoRecorder:bind-ipc 设备id 设备数量【一般填写：all】
```
# Docker安装运行

1. 参考官网的docker安装构建
2. 原始docker构建流程

   2.1 构建命令
  
```bash
        sudo docker build -t sip-media-manage:0.1 .
        # 查看镜像列表：sudo docker images
```
   
   2.2 运行容器命令（--build-arg 可以覆盖 dockerfile 里面的 ARG 构建参数）
    
```bash
        sudo docker run -d --name sipMediaManage -p 8787:8787 sip-media-manage:0.1
```

   2.3 ~~进入容器命令~~
     
```bash
         sudo docker exec -it containerID /bin/bash 
        # 可以通过 docker ps -a 或者 dokcer container ls -a 查看容器id
```  

   2.4 ~~启动webman~~
      
```bash
         php start.php start
         # 守护进程启动
         exit
         # 退出容器
```

   2.5 测试
   
```bash
      #宿主机里面找到映射端口号是否存在
      netstat -ntlp | grep port
      # or ps -ef 
```
      
# 日志说明
 
  已使用webman自带的log


### wiki

1. [博客园：curl参考](https://www.cnblogs.com/fan-gx/p/12321351.html) 

2. [docker入门实践](https://yeasy.gitbook.io/docker_practice) 

3. docker -v 挂载匿名盘

    <div style="text-indent: 4px;padding:10px 0px;color:#f69;line-height: 2em;">
    容器运行时应该尽量保持容器存储层不发生写操作，对于数据库类需要保存动态数据的应用，其数据库文件应该保存于卷(volume)中，后面的章节我们会进一步介绍 Docker 卷的概念。为了防止运行时用户忘记将动态文件所保存目录挂载为卷，在 Dockerfile 中，我们可以事先指定某些目录挂载为匿名卷，这样在运行时如果用户不指定挂载，其应用也可以正常运行，不会向容器存储层写入大量数据
</div>
 
 
