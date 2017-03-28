# Phwoolcon Demo

# Installation
Let's take the installation on Ubuntu as an example.

## 1. Install php7
```bash
add-apt-repository ppa:ondrej/php
apt-get update
apt-get install php7.0-fpm php7.0-gd php7.0-cli php7.0-curl php7.0-dev php7.0-json php7.0-mbstring php7.0-mcrypt php7.0-mysql php7.0-xml php7.0-zip php-redis
```

## 2. Install phalcon
```bash
curl -s https://packagecloud.io/install/repositories/phalcon/stable/script.deb.sh | bash
apt-get install php7.0-phalcon
```

## 3. Install swoole
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

## 4. Install composer
```bash
wget -c https://getcomposer.org/composer.phar -O /usr/bin/composer
chmod +x /usr/bin/composer
```

## 5. Install nginx
```bash
add-apt-repository ppa:nginx/stable
apt-get update
apt-get install nginx
```

## 5.1. Add nginx upstream php7
```bash
vim /etc/nginx/conf.d/upstream.conf
```

```conf
upstream php7 {
    #this should match value of "listen" directive in php-fpm pool
    server unix:/run/php/php7.0-fpm.sock;
}
```

## 5.2. Configure nginx entrance
```bash
vim /etc/nginx/sites-available/yoursite.dev
```

```conf
server {
    listen 80;
    server_name     yoursite.dev;
    root /srv/http/yoursite.dev/public;
    index  index.php index.html index.htm;

    access_log off;
    error_log /var/log/nginx/yoursite.dev_error.log;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass php7;
        access_log /var/log/nginx/yoursite.dev_access.log;
        fastcgi_param USE_SERVICE 1;
    }

    location ~ /^\. { deny all; }

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

## 5.3. Enable site entrance
```bash
ln -s /etc/nginx/sites-available/yoursite.dev /etc/nginx/sites-enabled/yoursite.dev
nginx -t
nginx -s reload
```

## 6. Install Phwoolcon framework
```bash
cd /srv/http
git clone git@github.com:phwoolcon/bootstrap.git yoursite.dev
```

## 7. Install phwoolcon/demo
```bash
cd yoursite.dev
bin/import-package phwoolcon/demo
composer update
```

## 8. Install MySQL
```bash
apt-get install mysql-server-5.6 mysql-client-5.6
```

### 8.1. Create database and DB user
```bash
mysql -uroot -p
create database your_db_name;
GRANT ALL PRIVILEGES ON your_db_name.*  To 'your_db_user'@'%' IDENTIFIED BY 'your_db_pass';
```

## 9. Modify project configurations

```bash
vim app/config/production/database.php
```

```php
<?php
return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host'       => '127.0.0.1',    // Use real server
            'username'   => 'your_db_user', // Use real username
            'password'   => 'your_db_pass', // Use real password
            'dbname'     => 'your_db_name', // Use real db name
        ],
    ],
    'distributed' => [
        'node_id' => '001',
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
            // Fill real merchant info here
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

## 10. Install project DB stuff
```bash
bin/dump-autoload
bin/cli migrate:up
bin/dump-autoload
```
