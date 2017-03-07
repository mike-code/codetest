# Heptapods

## Installation on debian-alike system

System dependencies
```
apt-get install git php7.0 php7.0-cli
```

Ebin
```
cd ~
git clone https://github.com/miki-mouse/codetest.git
cd codetest
composer install
```

Run
```
php index.php flight:add -v -- 52.229676 21.012229 UTC+2 51.507351 -0.127758 UTC+4
php index.php flight:add -v -- 51.8860 0.2388 +0 52.2105 -0.9929 +7
```