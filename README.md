#LazyBot

Lazybot is a tool to automate subtitle downloading for TV Shows


##Installation
- Clone the repository
    - git clone https://github.com/Thomas-Negrault/lazybot.git
- Install composer
    - curl -sS https://getcomposer.org/installer | php
    - sudo mv composer.phar /usr/local/bin/composer
- Install requirements
    - sudo apt-get update && sudo apt-get install php5-dev php-pear libcurl3-openssl-dev
    - sudo pecl install inotify
    - Add the extension to your *CLI* php.ini `extension=inotify.so;` (/etc/php5/cli/php.ini)
    - cd lazybot
    - composer update
    - composer install

##Utilisation

``` 
./lazybot subtitle:addic7ed -i Show.S01.E01.mkv -p /path/to/output/subtitle
./lazybot monitor /home/PATH/TO/DOWNLOAD/DIRECTORY/

```

##Tests:

```
./vendor/bin/phpunit
```
