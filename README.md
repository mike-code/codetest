# Heptapods

## Installation on debian-alike system

System dependencies
```
sudo apt-get install git php7.0 php7.0-cli php7.0-pdo composer
```

Get
```
cd ~
git clone https://github.com/miki-mouse/codetest.git
cd codetest
composer install
```

Run
```
php drones flight:add -- 52.229676 21.012229 UTC+2 51.507351 -0.127758 UTC+4
php drones flight:add -- 51.8860 0.2388 +0 52.2105 -0.9929 +7
```

## Known issues

* Missing PHP docs
* Not robust in all places (mostly in the DB)
* Windows CLI requires end-of-argument syntax as it interpretes negative floats as parameter names
* No unit test
* No deployment script
* No CI skills demonstration