# gping
guessing ping - trying to guess what you want..


this source code snippet probably explains it best: 

```php
if(count($args)<1){
    echo "usage: {$argv[0]} host (or you can replace host with http://host for port 80, or https://host for port 443, or ssh host for port 22, etc)\n";
    return 1;
}
```