# gping
guessing ping - trying to guess what you want..

this source code snippet probably explains it best: 

```php
if(count($args)<1){
    echo "usage: {$argv[0]} host (or you can replace host with http://host for port 80, or https://host for port 443, or ssh host for port 22, etc)\n";
    return 1;
}
```

example installation: 
```
# probably need to execute as root (unless you're on Cygwin)
rm -rfv /usr/local/bin/gping.php /usr/local/bin/gping;
wget -O /usr/local/bin/gping.php https://raw.githubusercontent.com/divinity76/gping/master/src/gping.php;
ln -s /usr/local/bin/gping.php /usr/local/bin/gping;
chmod 0555 /usr/local/bin/gping.php;
chmod 0555 /usr/local/bin/gping;
```

requirements: php-cli >= 7.0.0
