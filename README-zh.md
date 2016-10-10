# 安装
下文以 Ubuntu 安装为例。

## 1. 安装 php7
```bash
add-apt-repository ppa:ondrej/php
apt-get update
apt-get install php7.0-fpm php7.0-gd php7.0-cli php7.0-curl php7.0-dev php7.0-json php7.0-mbstring php7.0-mcrypt php7.0-mysql php7.0-xml php7.0-zip
```

## 2. 安装 phalcon
```bash
curl -s https://packagecloud.io/install/repositories/phalcon/stable/script.deb.sh | bash
apt-get install php7.0-phalcon
```

## 3. 安装 swoole
```bash
pecl install swoole
vim /etc/php/7.0/mods-available/swoole.ini
```

```ini
[swoole]
extension = swoole.so
```

```bash
ln -s /etc/php/7.0/mods-available/swoole.ini /etc/php/7.0/cli/conf.d/20-swoole.ini
ln -s /etc/php/7.0/mods-available/swoole.ini /etc/php/7.0/fpm/conf.d/20-swoole.ini
```

## 4. 安装 composer
```bash
wget -c https://getcomposer.org/composer.phar -O /usr/bin/composer
chmod +x /usr/bin/composer
```

## 5. 安装 nginx
（略）

## 5.1. 增加 nginx upstream php7
```bash
vim /etc/nginx/conf.d/upstream.conf
```

```conf
upstream php7 {
    #this should match value of "listen" directive in php-fpm pool
    server unix:/run/php/php7.0-fpm.sock;
}
```

## 5.2. 配置 nginx 入口
```bash
vim /etc/nginx/sites-available/yoursite.dev
```

```conf
server {
    listen 80;
    server_name     yoursite.dev;
    root /srv/http/yoursite.dev/public;
    index  index.php index.html index.htm;

    access_log /var/log/nginx/yoursite.dev_access.log;
    error_log /var/log/nginx/yoursite.dev_error.log;
        
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        try_files $uri =404;
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass php7;
        fastcgi_index index.php;
        fastcgi_param REDIRECT_STATUS 200;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_param USE_SERVICE 1;
    }

    location ~* \.(js|css|swf|eot|ttf|otf|woff|woff2)$ {
        add_header 'Cache-Control' 'public';
        add_header 'X-Frame-Options' 'ALLOW-FROM *';
        add_header 'Access-Control-Allow-Origin' '*';
        add_header 'Access-Control-Allow-Credentials' 'true';
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS';
        add_header 'Access-Control-Allow-Headers' 'DNT,X-CustomHeader,Keep-Alive,User-Agent,X-Requested-With,If-Modified-Since,Cache-Control,Content-Type';
        expires +1w;
    }
}
```

## 5.3. 启用新入口
```bash
ln -s /etc/nginx/sites-available/yoursite.dev /etc/nginx/sites-enabled/yoursite.dev
nginx -t
nginx -s reload
```

## 6. 安装 phwoolcon 框架
```bash
cd /srv/http
composer create-project phwoolcon/bootstrap:dev-master yoursite.dev
```

## 7. 安装 phwoolcon/demo 模块
```bash
cd yoursite.dev
vim composer.local.json
```

```json
{
    "require": {
        "phwoolcon/demo": "~1.0"
    }
}
```

```bash
composer up
```

## 8. 安装数据库
```bash
apt-get install mysql-server-5.6 mysql-client-5.6
```

### 8.1. 创建数据库和用户
```bash
mysql -uroot -p
create database your_db_name;
GRANT ALL PRIVILEGES ON your_db_name.*  To 'your_db_user'@'%' IDENTIFIED BY 'your_db_pass';
```

## 9. 修改项目配置
```bash
vim app/config/production/app.php
```

```php
<?php
return [
    'debug' => false,
    'name' => 'Your Site',
    'version' => '1.0.x-dev',
    'cache_config' => true,
    'enable_https' => false,
    'timezone' => 'Asia/Shanghai',
    'url' => 'http://yoursite.dev',
    'log' => [
        'adapter' => 'file',
        'file' => 'phwoolcon.log',
    ],
];
```

```bash
vim app/config/production/database.php
```

```php
<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'adapter'    => 'Phwoolcon\Db\Adapter\Pdo\Mysql',
            'host'       => 'localhost',
            'dbname'     => 'your_db_name',
            'username'   => 'your_db_user',
            'password'   => 'your_db_pass',
            'charset'    => 'utf8mb4',
            'default_table_charset' => 'utf8_unicode_ci',
            'options'    => [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES "utf8mb4" COLLATE "utf8mb4_unicode_ci"',
            ],
            'persistent' => false,
        ],

    ],
    'distributed' => [
        'node_id' => '001',
        'start_time' => 1362931200,
    ],
    'query_log' => false,
];
```

```bash
vim app/config/production/payment.php
```

```php
<?php
return [
    'gateways' => [
        'alipay' => [
            // 填写真实商户资料
            'partner' => 'PARTNER_ID',
            'seller_id' => 'seller@phwoolcon.com',
            'private_key' => '-----BEGIN RSA PRIVATE KEY-----
YOUR_PRIVATE_KEY_HERE
-----END RSA PRIVATE KEY-----',
            'ali_public_key' => '-----BEGIN PUBLIC KEY-----
ALI_PUBLIC_KEY_HERE
-----END PUBLIC KEY-----',
        ],
    ],
];
```

## 10. 安装项目数据库
```bash
bin/cli migrate:up
bin/cli clear:cache
```
