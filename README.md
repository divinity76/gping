# gping
guessing ping - trying to guess what you want..

if the first ping fails, it will start beeping on successful pings
(why? it guesses you're waiting for a system to restart, and want to be notified when it's up again)

if you're giving it a ssh command, it will parse out the ip/host and ping port 22 (if you're using a non-standard port, it will parse out the custom -p X or --port=X as well, and ping the custom port)

if you give it a https link, it will ping port 443, a http link is port 80, a ftp link is port 21, a url with a custom port like `https://example.org:9999` will parse out the custom port and ping that, etc.

if it can't guess what port you want to ping, it will default to an ICMP ping.

if it fails to guess something, feel free to submit a bugreport

example usage: 
```sh
root@x-foo-net:~#
root@x-foo-net:~# reboot now
Connection to foo.net closed by remote host.
Connection to foo.net closed.

hans@hans-lp17 ~
$ gping ssh hans@foo.net
will ping "foo.net" port 22. time between pings: 2s..first ping failed, will start beeping on success..
4: fail! "Connection timed out" 2.001s
6: fail! "Connection timed out" 2.001s
8: fail! "Connection timed out" 2.001s
(...)
252: success! 0.038s
```


## installation: 
```
# probably need to execute as root (unless you're on Cygwin)
rm -rfv /usr/local/bin/gping.php /usr/local/bin/gping;
wget -O /usr/local/bin/gping.php https://raw.githubusercontent.com/divinity76/gping/master/src/gping.php;
ln -s /usr/local/bin/gping.php /usr/local/bin/gping;
chmod 0555 /usr/local/bin/gping.php;
chmod 0555 /usr/local/bin/gping;
```

## requirements
php-cli >= 7.0.0
