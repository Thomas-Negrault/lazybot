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

- Install optionals packages:
    - PushBullet:
        - sudo apt-get install php5-curl
       
    - [MKVToolNix]([https://www.bunkus.org/videotools/mkvtoolnix/downloads.html):
        - wget -q -O - https://www.bunkus.org/gpg-pub-moritzbunkus.txt | sudo apt-key add -
	Add the appropriate lines to your /etc/apt/sources.list:
		- Ubuntu
			- 15.04 "Vivid Vervet"
				  - deb http://www.bunkus.org/ubuntu/vivid/ ./
				  - deb-src http://www.bunkus.org/ubuntu/vivid/ ./
			- 14.10 "Utopic Unicorn"
				- deb http://www.bunkus.org/ubuntu/utopic/ ./
				- deb-src http://www.bunkus.org/ubuntu/utopic/ ./
			- 14.04 "Trusty Tahr"
				- deb http://www.bunkus.org/ubuntu/trusty/ ./
				- deb-src http://www.bunkus.org/ubuntu/trusty/ ./
		- Debian
			- Debian 7 wheezy
				        - deb http://www.bunkus.org/debian/wheezy/ ./
				        - deb-src http://www.bunkus.org/debian/wheezy/ ./
			- Debian 8 (aka »jessie«)
				        - deb http://www.bunkus.org/debian/jessie/ ./
				        - deb-src http://www.bunkus.org/debian/jessie/ ./
        - sudo apt-get update && sudo apt-get install mkvtoolnix



##Utilisation

``` 
./lazybot subtitle:addic7ed -i Show.S01.E01.mkv -p /path/to/output/subtitle
./lazybot monitor /home/PATH/TO/DOWNLOAD/DIRECTORY/

```

##Tests:

```
./vendor/bin/phpunit
```
