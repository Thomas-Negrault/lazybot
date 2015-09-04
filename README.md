#LazyBot

Lazybot is a tool to automate subtitle downloading for TV Shows


##Installation
- Clone the repository
    - git clone https://github.com/Thomas-Negrault/lazybot.git
- Install composer
    - curl -sS https://getcomposer.org/installer | php
    - sudo mv composer.phar /usr/local/bin/composer
- Install requirements
    - PHP ([Ubuntu lamp page](http://doc.ubuntu-fr.org/lamp))
    - sudo apt-get update && sudo apt-get install  php-pear
    - sudo pecl install inotify
    - Add the extension to your *CLI* php.ini `extension=inotify.so;` (/etc/php5/cli/php.ini)
    - cd lazybot
    - composer update
    - Copy `app/config/userConfig.yml.example` to `app/config/userConfig.yml` and fill it with your informations

- Install optionals packages:
    - PushBullet:
        - sudo apt-get install php5-curl
        - Create a PushBullet account and get your Access Token on [your account page](https://www.pushbullet.com/#settings/account)
        - Add your Access Token in `app/config/userConfig.yml`

##Utilisation

``` 
./lazybot subtitle:addic7ed -i Show.S01.E01.mkv -p /path/to/output/subtitle
./lazybot monitor /home/PATH/TO/DOWNLOAD/DIRECTORY/
```

In zsh use:
```
 alias lazybot="/PATH/TO/PROJECT/lazybot subtitle:addic7ed -i"
```
and after that:

```
lazybot Show.S01.E01.mkv
```

##Tests:

```
./vendor/bin/phpunit
```
