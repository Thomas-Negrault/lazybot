#LazyBot

Lazybot is a tool to automate subtitles downloading for TV Shows.

##How it works

Lazybot is a 2 part application. The first one takes a video file as argument, eventually the wanted language and it will check on [Addic7ed](http://www.addic7ed.com) if the subtitle is available.
Depending on the progress of the translation, lazybot will regularly check and download the subtitle when it's available.

The second part is an application that monitors a folder, when a new file is added to the folder, the first script will be launched in order to automatically download subtitles for your TV shows.

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

##Use

``` 
./lazybot subtitle:addic7ed -i Show.S01.E01.mkv 
./lazybot subtitle:addic7ed -i Show.S01.E01.mkv -l english
./lazybot subtitle:addic7ed -i Show.S01.E01.mkv -p /path/to/output/subtitle

./lazybot monitor /home/PATH/TO/DOWNLOAD/DIRECTORY/ -l french -l english
```

In zsh use:
```
 alias lazybot="/PATH/TO/PROJECT/lazybot subtitle:addic7ed -i"
```
and after that:

```
lazybot Show.S01.E01.mkv
```

##Disclaimer:
```
I am not in any way affiliated with Addic7ed. Please continue to visit the website because that's how they are remunerated.
I am not responsible and I do not encourage the use of this tool for any kind of piracy.
```
