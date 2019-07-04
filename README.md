## 🔗PHP单文件短链接

本程序只有一个文件，极其方便部署。

#### 体验预览

待补充
![preview](<https://raw.githubusercontent.com/ellermister/shorturl/master/preview.png>)


### 支持功能

- 🌵API快速生成短链接
- 🌱在线网页生成短链接（开发中）
- 🍄支持Redis、File缓存控制（开发中）
- 🍀支持一次性生成访问（开发中）
- 🍁支持无追踪访问（开发中）



#### 安装

##### 下载本程序到网站根目录

```bash
php -S 127.0.0.1:12138
```

##### 访问浏览

http://127.0.0.1:12138

##### 

#### API

##### 生成短链接

```bash
curl -s http://127.0.0.1:12138/api/link?url=https://map.baidu.com/poi/%E4%B9%9D%E9%BE%99%E5%85%AC%E5%9B%AD/@12713897.395906774,2531599.1717763273,15.45z
```

Response

```json
{"msg":"ok","code":200,"data":"http://127.0.0.1:12138/s/aFdlm"}
```

