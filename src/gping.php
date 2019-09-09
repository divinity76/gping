#!/usr/bin/env php
<?php
declare(strict_types = 1);
init();
// eclipse linter bug apparently..
/** @var array $argv */
$args = $argv;
unset($args[0]);
$args = array_values($args);
// var_dump($args);
// die();
if (count($args) < 1) {
    echo "usage: {$argv[0]} host (or you can replace host with http://host for port 80, or https://host for port 443, or ssh host for port 22, etc)\n";
    return 1;
}
$host = null;

for ($i = 0, $count = count($args); $i < $count; ++ $i) {
    $arg = strtolower(trim($args[$i]));
    if (starts_with($arg, "ssh")) {
        portManager(22, 2, "ssh");
    } elseif (starts_with($arg, "https")) {
        $info = parse_url($arg);
        $port = $info['port'] ?? 443;
        $why = (isset($info['port']) ? $arg : 'https');
        $host = $info['host'];
        portManager($port, 2, $why);
    } elseif (starts_with($arg, "http")) {
        $info = parse_url($arg);
        $port = $info['port'] ?? 80;
        $why = (isset($info['port']) ? $arg : 'http');
        $host = $info['host'];
        portManager($port, 2, $why);
    } elseif (starts_with($arg, "ftp")) {
        $info = parse_url($arg);
        $port = $info['port'] ?? 20;
        $why = (isset($info['port']) ? $arg : 'ftp');
        $host = $info['host'];
        portManager($port, 2, $why);
    } elseif ($arg === "-p" || $arg === "--port" || $arg === "port") {
        if (false !== strpos($arg, "=")) {
            $port = trim(explode("=", $arg, 2)[1]);
            if (false !== ($port = filter_var($port, FILTER_VALIDATE_INT))) {
                portManager($port, 3, $arg);
            }
        } else {
            if ($i < (count($args) - 1)) {
                $port = trim($args[$i + 1]);
                if (false !== ($port = filter_var($port, FILTER_VALIDATE_INT))) {
                    portManager($port, 3, $arg . " " . $port);
                    ++$i;
                }
            }
        }
    } else {
        // ...
        if ((false !== strpos($arg, ":") || false !== strpos($arg, ".")) && (false !== filter_var($arg, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6) || false !== filter_var($arg, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME))) {
            // $host = $arg;
            $info = parse_url($arg);
            $port = $info['port'] ?? 0;
            $why = (isset($info['port']) ? $arg : $arg);
            $host = $info['host'] ?? $arg;
            portManager($port, (isset($info['port']) ? 2 : 0), $why);
        } elseif (false !== strpos($arg, '@')) {
            // retry by trimming ssh foo@bar.com to just "bar.com" ...
            $args[$i] = trim(explode("@", $arg, 2)[1]);
            -- $i;
            continue;
        }
    }
}
// var_dump($host, portManager());
if (! $host) {
    die("error: unable to guess the hostname you want.\n");
}
if (portManager(null)->port === 0) {
    // could not guess port, default to ICMP ping.
}
runtime(); // init
$time_between_pings = 2;
$timeout = 2;
$errstr = "";
$response_time = null;
echo "will ping \"{$host}\" ";
if (portManager()->port === 0) {
    echo "with ICMP ping.";
} else {
    echo "port " . portManager()->port . ".";
}
echo " time between pings: {$time_between_pings}s..";
$first = pingPort($host, portManager()->port, $timeout, $response_time, $errstr);
if ($first) {
    echo ". success!\n";
    for (;;) {
        $starttime = microtime(true);
        if (pingPort($host, portManager()->port, $timeout, $response_time, $errstr)) {
            echo runtime() . ": success! " . number_format($response_time, 3) . "s\n";
        } else {
            echo runtime() . ": fail! \"{$errstr}\" " . number_format($response_time, 3) . "s\n";
        }
        $remaining = $time_between_pings - (microtime(true) - $starttime);
        if ($remaining > 0.001) {
            @time_sleep_until(microtime(true) + $remaining);
        }
    }
} else {
    echo "first ping failed, will start beeping on success..\n";
    for (;;) {
        $starttime = microtime(true);
        if (pingPort($host, portManager()->port, $timeout, $response_time, $errstr)) {
            echo runtime() . ": success! " . number_format($response_time, 3) . "s\n";
            for ($i = 0; $i < 5; ++ $i) {
                cli_beep();
                sleep(1);
            }
        } else {
            echo runtime() . ": fail! \"{$errstr}\" " . number_format($response_time, 3) . "s\n";
        }
        $remaining = $time_between_pings - (microtime(true) - $starttime);
        if ($remaining > 0.001) {
            @time_sleep_until(microtime(true) + $remaining);
        }
    }
}

function runtime(): int
{
    static $first = null;
    if ($first === null) {
        $first = microtime(true);
    }
    return (int) (microtime(true) - $first);
}

function pingPort(string $host, int $port, int $timeout = 2, float &$response_time_ms = null, string &$errstr = null): bool
{
    $errstr = "";
    if ($port < 0) {
        throw new LogicException('port<0');
    }
    if ($timeout < 0) {
        throw new LogicException('port<0');
    }
    // magic number for ICMP Ping (which doesn't really have a number because the icmp protocol)
    if ($port === 0) {
        // ping -c 1 -W 2 -q 127.0.0.1
        $cmd = 'ping -c 1 -W ' . ((int) $timeout) . ' -q ' . escapeshellarg((string) $host) . " 2>&1";
        $ret = null;
        $output = [];
        $start = microtime(true);
        exec($cmd, $output, $ret);
        $end = microtime(true);
        $response_time_ms = $end - $start;
        $errstr = implode("\n", $output);
        return ($ret === 0);
    } else {
        $errstr = "";
        $errno = 0;
        $start = microtime(true);
        $sock = @fsockopen($host, $port, $errno, $errstr, $timeout);
        $end = microtime(true);
        $response_time_ms = $end - $start;
        if (! $sock) {
            return false;
        } else {
            fclose($sock);
            return true;
        }
    }
}

class PortManagerObject
{

    public $port = 0;

    public $confidence = 0;

    public $why = "could not guess port, default to ICMP ping.";
}

function portManager(int $port = null, int $confidence = null, string $why = null): PortManagerObject
{
    static $stored = null;
    if ($stored === null) {
        $stored = new PortManagerObject();
    }
    if ($port === null) {
        return $stored;
    } else {
        // throw new \Exception();
    }
    if ($confidence >= $stored->confidence) {
        $stored->port = $port;
        $stored->confidence = $confidence;
        $stored->why = $why;
    } else {
        // insufficient confidence, ignored...
    }
    return $stored;
}

function starts_with(string $haystack, string $needle)
{
    return (0 === strpos($haystack, $needle));
}

function init()
{
    hhb_init();
}

function hhb_init()
{
    static $firstrun = true;
    if ($firstrun !== true) {
        return;
    }
    $firstrun = false;
    error_reporting(E_ALL);
    set_error_handler("hhb_exception_error_handler");
    // ini_set("log_errors",'On');
    // ini_set("display_errors",'On');
    // ini_set("log_errors_max_len",'0');
    // ini_set("error_prepend_string",'<error>');
    // ini_set("error_append_string",'</error>'.PHP_EOL);
    // ini_set("error_log",__DIR__.DIRECTORY_SEPARATOR.'error_log.php.txt');
    assert_options(ASSERT_ACTIVE, 1);
    assert_options(ASSERT_WARNING, 0);
    assert_options(ASSERT_QUIET_EVAL, 1);
    assert_options(ASSERT_CALLBACK, 'hhb_assert_handler');
}

function hhb_exception_error_handler($errno, $errstr, $errfile, $errline)
{
    if (! (error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function hhb_assert_handler($file, $line, $code, $desc = null)
{
    $errstr = 'Assertion failed at ' . $file . ':' . $line . ' ' . $desc . ' code: ' . $code;
    throw new ErrorException($errstr, 0, 1, $file, $line);
}

function cli_beep()
{
    if (is_callable('ncurses_beep')) {
        ncurses_beep();
        ncurses_flash();
    } else {
        fprintf(STDOUT, "%s", "\x07");
    }
}
