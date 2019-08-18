#!/usr/bin/php
<?php
declare(strict_types=1);
// require_once ('hhb_.inc.php');
hhb_init();
if ($argc !== 3) {
    fprintf(STDERR, "usage: %s timestamp url\n", $argv [0]);
    fprintf(STDERR, "example: %s 20091012061648 http://www.p4w.se\n", $argv [0]);
    die (1);
}
// var_dump($argc, $argv) & die();
$timestamp = $argv [1];
if (!preg_match('/^[1-9][0-9]+$/', ( string )$timestamp)) {
    throw new \InvalidArgumentException ('invalid timestamp! (failed regex validation /^[1-9][0-9]+$/ )');
}
$timestamp = ( int )$timestamp;
if (!filter_var($argv [2], FILTER_VALIDATE_URL)) {
    throw new \InvalidArgumentException ('invalid url! (failed FILTER_VALIDATE_URL ) ');
}
$archive_url = 'https://web.archive.org/web/'.$timestamp.'id_/';
define("ARCHIVE_URL", $archive_url);
$url = $argv [2]; // https://web.archive.org/web/20091012061648id_/http://www.p4w.se
$master_dir = parse_url($url) ['host'];
define("MASTER_DIR", $master_dir);
system("rm -rfv ".escapeshellarg($master_dir));
mkdir($master_dir);
chdir($master_dir);
makeShittyMasterRedirect();
$downloaded = array();
$to_download = array(
    $url,
);
$total = 1;
$thus_far = 0;
$hc = new hhb_curl ('', true);
$hc->setopt_array(array(
    CURLOPT_TIMEOUT => 20,
    CURLOPT_CONNECTTIMEOUT => 10,
)); // sometimes archive.org lags for quite a bit, so account for that.
hhb_init();
while (!empty ($to_download)) {
    reset($to_download);
    $key = key($to_download);
    $url = $raw_url = $to_download [$key];
    unset ($to_download [$key]);
    ++$thus_far;
    $file = absolute_url_to_filepath($raw_url);
    $url = geturl($url);
    $downloaded [] = $raw_url;
    echo 'file: '.$file.' url: '.$raw_url.'..'.PHP_EOL;
    try {
        $headers = implode(" ", $hc->exec($url)->getResponseHeaders());
    } catch (Exception $ex) {
        try {
            $headers = implode(" ", $hc->exec($url)->getResponseHeaders());
        } catch (Exception $ex) {
            // sometimes connection fails for no good reason, and a retry (or 2) fixes it...
            // here we give up and deliberately not catch the 3rd exception (if any)
            $headers = implode(" ", $hc->exec($url)->getResponseHeaders());
        }
    }

    // var_dump($headers) & die();
    $response = $hc->getinfo(CURLINFO_RESPONSE_CODE);
    if ($response !== 200) {
        // ...
        fprintf(STDERR, "warning: because http response code %d, ignoring url %s\n", $response, $url);
        continue;
    }
    $html = $hc->getResponseBody();
    if (false !== stripos($headers, 'Content-Type: text/html')) {
        $domd = @DOMDocument::loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        if (!$domd) {
            // seriously malformed HTML, or not actually a HTML at all (like ico served with text/html content header... yes, that actually happened on http://www.gravatar.com/blavatar/2e776b945e937c41193b102c956044c8s=16&d=http:/s.wordpress.com/favicon.ico )
        } else {
            foreach ($domd->getElementsByTagName("*") as $ele) {
                if (!$ele->hasAttributes()) {
                    continue;
                }
                $tmpname = "src";
                $tmp = $ele->getAttribute($tmpname);
                if (empty ($tmp)) {
                    $tmpname = "href";
                    $tmp = $ele->getAttribute($tmpname);
                    if (empty ($tmp)) {
                        continue;
                    }
                }
                $tmp = relative_to_absolute($tmp, $raw_url);
                if (empty ($tmp)) {
                    // unsupported url (like mailto: or javascript )
                    continue;
                }
                $ele->setAttribute($tmpname, absolute_to_relaitve($tmp, $raw_url));
                if (in_array($tmp, $to_download, true)) {
                    // already queued to download
                    continue;
                }
                if (in_array($tmp, $downloaded, true)) {
                    // already downloaded.
                    continue;
                }
                echo 'adding '.$tmp, PHP_EOL;
                ++$total;
                $to_download [] = $tmp;
                continue;
            }
            $html = $domd->saveHTML();
        }
    }
    file_force_contents($file, $html);
    echo ' done. ('.$thus_far.'/'.$total.')', PHP_EOL;
}
function absolute_url_to_filepath(string $url): string
{
    $info = parse_url($url);
    $uri = preg_replace('/[\\\\\\/]+/u', DIRECTORY_SEPARATOR, '/'.$info ['host'].'/'.($info ['path'] ?? ''));
    // hhb_var_dump($uri, basename($uri));
    if (DIRECTORY_SEPARATOR === substr($uri, -1) || empty (basename($uri))) {
        if (DIRECTORY_SEPARATOR !== substr($uri, -1)) {
            $uri .= DIRECTORY_SEPARATOR;
        }
        $uri .= 'index.html';
    }

    // hhb_var_dump($uri);
    return /* getcwd() . */
        substr($uri, 1);
}

function absolute_to_relaitve(string $absolute, string $source): string
{
    $absolute_ = $absolute;
    $source_ = $source;
    //
    $source = parse_url($source);
    $source = $source ['host'].((isset ($source ['port'])) ? (':'.$source ['port']) : '').'/'.($source ['path'] ?? ''); // . ($source['query'] ?? '');
    $source = preg_replace('/[\\/]+/', '/', $source);
    // hhb_var_dump($absolute, $source) & die();
    $absolute = parse_url($absolute);
    // hhb_var_dump($absolute, $source) & die();
    $absolute = $absolute ['host'].((isset ($absolute ['port'])) ? (':'.$absolute ['port']) : '').'/'.($absolute ['path'] ?? '').($absolute ['query'] ?? '');
    $absolute = preg_replace('/[\\/]+/', '/', $absolute);
    // hhb_var_dump($absolute, $source) & die();
    if (0 === strpos($absolute, $source)) {
        $absolute = substr($absolute, strlen($source));
        if (substr($absolute, -1) === '/') {
            // .........$absolute = substr ( $absolute, 0, - 1 );
        } elseif (false === strpos($absolute, '.') || (false !== strpos($absolute, '/') && substr($absolute,
                    -1) !== '/' && false === strpos(basename($absolute), '.'))) {
            // for foo containing .. vs foo/ containing .. , makes a subtle but page-breaking difference
            $absolute .= '/';
        }
    } else {
        if (0 === stripos($absolute, MASTER_DIR.'/')) {
            // EWIQJWQI
            // $absolute = substr ( $absolute, strlen ( MASTER_DIR ) + 1 );
        }
        $amt = substr_count($source, '/');
        $str = '';
        // EWIQJWQI
        for ($i = 0; $i < $amt; ++$i) {
            $str .= '../';
        }
        if (false === strpos($absolute, '.') || (false !== strpos($absolute, '/') && substr($absolute,
                    -1) !== '/' && false === strpos(basename($absolute), '.'))) {
            // for foo containing .. vs foo/ containing .. , makes a subtle but page-breaking difference
            if (substr($absolute, -1) !== '/') {
                $absolute .= '/';
            }
        }
        $absolute = $str.$absolute;
    }

    // hhb_var_dump($absolute_, $absolute, $source, $source_) & (rand(2, 30) === 1 ? die() : 1);
    return $absolute;
}

function relative_to_absolute(string $relative, string $source): string
{
    if (empty ($relative)) {
        return "";
    }
    $info = parse_url($relative);
    $whitelist = array(
        'http',
        'https',
        'ftp',
    );
    if (!empty ($info ['scheme']) && !in_array(strtolower($info ['scheme']), $whitelist, true)) {
        // like mailto: or tlf: or whatever, unsupported
        return "";
    }
    if ($relative [0] === '#') {
        return "";
    }
    if (false !== strpos($relative, '(') && false !== strpos($relative, ')')) {
        // PROBABLY javascript (otherwise these characters would be urlencoded to %whatever)
        return "";
    }
    $sourceinfo = parse_url($source);
    $relainfo = parse_url($relative);
    $ret = '';
    if (empty ($relainfo ['host'])) {
        $ret = $sourceinfo ['host'].dirname($sourceinfo ['path'] ?? '').'/'.($relainfo ['path'] ?? '');
        again:
        $ret = explode("/", $ret);
        for ($i = 0, $count = count($ret); $i < $count; ++$i) {
            if ($ret [$i] === '.') {
                unset ($ret [$i]);
                $ret = implode('/', $ret);
                goto again;
            }
            if ($ret [$i] === '..') {
                unset ($ret [$i]);
                unset ($ret [$i - 1]);
                $ret = implode('/', $ret);
                goto again;
            }
            continue;
        }
        $ret = implode('/', $ret);
        $ret = preg_replace('/[\\/]+/', '/', $ret);
        $ret = $sourceinfo ['scheme'].'://'.$ret;
        unset ($i, $count);
    } else {
        $ret = ($relainfo ['host'] ?? $sourceinfo ['host']).((isset ($relainfo ['port']) || isset ($sourceinfo ['port'])) ? (':'.($relainfo ['port'] ?? $sourceinfo ['port'])) : '').'/'.($relainfo ['path'] ?? $sourceinfo ['path'] ?? '').($relainfo ['query'] ?? '');
        $ret = preg_replace('/[\\/]+/', '/', $ret);

        $ret = ($relainfo ['scheme'] ?? $sourceinfo ['scheme']).'://'.$ret;
    }

    // hhb_var_dump($sourceinfo, $relainfo, $ret) & die(); //

    return $ret;
}

function geturl(string $url): string
{
    return ARCHIVE_URL.$url;
}

function makeShittyMasterRedirect()
{
    $foo = MASTER_DIR.'/index.html';
    $html = <<<HTML
<!DOCTYPE HTML>
<html lang="en-US">
<head>
<meta charset="UTF-8">
<meta http-equiv="refresh" content="1; url=$foo">
<script type="text/javascript">
	window.location.href = "$foo";
			</script>
			<title>Page Redirection</title>
			</head>
			<body>
			<a href='$foo'>uhh, due to a bug, click here. this is a demo, ofc there is some bugs.</a>
        </body>
        </html>
HTML;
    file_put_contents('index.html', $html);
}

function file_force_contents(string $filename, string $data, int $flags = 0)
{
    if (0 === strpos($filename, MASTER_DIR.DIRECTORY_SEPARATOR)) {
        // EWIQJWQI
        // $filename = substr ( $filename, strlen ( MASTER_DIR ) + 1 );
    }
    $tmp = dirname($filename);
    if (!is_dir($tmp)) {
        if (file_exists($tmp)) {
            $tmpf = file_get_contents($tmp);
            unlink($tmp);
            mkdir($tmp.DIRECTORY_SEPARATOR, 0777, true);
            file_put_contents($tmp.DIRECTORY_SEPARATOR.'index.html', $tmpf);
        } else {
            mkdir($tmp.DIRECTORY_SEPARATOR, 0777, true);
        }
    }

    return file_put_contents($filename, $data, $flags);
}

// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
// ///////////////////////////////////// - below here is stuff from hhb_.inc.php
/**
 * enhanced var_dump
 *
 * @param  mixed  $mixed  ...
 * @return void
 */
function hhb_var_dump()
{
    // informative wrapper for var_dump
    // <changelog>
    // version 5 ( 1372510379573 )
    // v5, fixed warnings on PHP < 5.0.2 (PHP_EOL not defined),
    // also we can use xdebug_var_dump when available now. tested working with 5.0.0 to 5.5.0beta2 (thanks to http://viper-7.com and http://3v4l.org )
    // and fixed a (corner-case) bug with "0" (empty() considders string("0") to be empty, this caused a bug in sourcecode analyze)
    // v4, now (tries to) tell you the source code that lead to the variables
    // v3, HHB_VAR_DUMP_START and HHB_VAR_DUMP_END .
    // v2, now compat with.. PHP5.0 + i think? tested down to 5.2.17 (previously only 5.4.0+ worked)
    // </changelog>
    // <settings>
    $settings = array();
    $PHP_EOL = "\n";
    if (defined('PHP_EOL')) { // for PHP >=5.0.2 ...
        $PHP_EOL = PHP_EOL;
    }

    $settings ['debug_hhb_var_dump'] = false; // if true, may throw exceptions on errors..
    $settings ['use_xdebug_var_dump'] = true; // try to use xdebug_var_dump (instead of var_dump) if available?
    $settings ['analyze_sourcecode'] = true; // false to disable the source code analyze stuff.
    // (it will fallback to making $settings['analyze_sourcecode']=false, if it fail to analyze the code, anyway..)
    $settings ['hhb_var_dump_prepend'] = 'HHB_VAR_DUMP_START'.$PHP_EOL;
    $settings ['hhb_var_dump_append'] = 'HHB_VAR_DUMP_END'.$PHP_EOL;
    // </settings>

    $settings ['use_xdebug_var_dump'] = ($settings ['use_xdebug_var_dump'] && is_callable("xdebug_var_dump"));
    $argv = func_get_args();
    $argc = count($argv, COUNT_NORMAL);
    if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
    } else {
        if (version_compare(PHP_VERSION, '5.3.6', '>=')) {
            $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        } else {
            if (version_compare(PHP_VERSION, '5.2.5', '>=')) {
                $bt = debug_backtrace(false);
            } else {
                $bt = debug_backtrace();
            }
        }
    };
    $analyze_sourcecode = $settings ['analyze_sourcecode'];
    // later, $analyze_sourcecode will be compared with $config['analyze_sourcecode']
    // to determine if the reason was an error analyzing, or if it was disabled..
    $bt = $bt [0];
    // <analyzeSourceCode>
    if ($analyze_sourcecode) {
        $argvSourceCode = array(
            0 => 'ignore [0]...',
        );
        try {
            if (version_compare(PHP_VERSION, '5.2.2', '<')) {
                throw new Exception ("PHP version is <5.2.2 .. see token_get_all changelog..");
            };
            $xsource = file_get_contents($bt ['file']);
            if (empty ($xsource)) {
                throw new Exception ('cant get the source of '.$bt ['file']);
            };
            $xsource .= "\n<".'?'.'php ignore_this_hhb_var_dump_workaround();'; // workaround, making sure that at least 1 token is an array, and has the $tok[2] >= line of hhb_var_dump
            $xTokenArray = token_get_all($xsource);
            // <trim$xTokenArray>
            $tmpstr = '';
            $tmpUnsetKeyArray = array();
            ForEach ($xTokenArray as $xKey => $xToken) {
                if (is_array($xToken)) {
                    if (!array_key_exists(1, $xToken)) {
                        throw new LogicException ('Impossible situation? $xToken is_array, but does not have $xToken[1] ...');
                    }
                    $tmpstr = trim($xToken [1]);
                    if (empty ($tmpstr) && $tmpstr !== '0' /*string("0") is considered "empty" -.-*/) {
                        $tmpUnsetKeyArray [] = $xKey;
                        continue;
                    };
                    switch ($xToken [0]) {
                        case T_COMMENT :
                        case T_DOC_COMMENT : // T_ML_COMMENT in PHP4 -.-
                        case T_INLINE_HTML :
                            {
                                $tmpUnsetKeyArray [] = $xKey;
                                continue;
                            };
                        default :
                        {
                            continue;
                        }
                    }
                } else {
                    if (is_string($xToken)) {
                        $tmpstr = trim($xToken);
                        if (empty ($tmpstr) && $tmpstr !== '0' /*string("0") is considered "empty" -.-*/) {
                            $tmpUnsetKeyArray [] = $xKey;
                        };
                        continue;
                    } else {
                        // should be unreachable..
                        // failed both is_array() and is_string() ???
                        throw new LogicException ('Impossible! $xToken fails both is_array() and is_string() !! .. ');
                    }
                };
            };
            ForEach ($tmpUnsetKeyArray as $toUnset) {
                unset ($xTokenArray [$toUnset]);
            };
            $xTokenArray = array_values($xTokenArray); // fixing the keys..
            // die(var_dump('die(var_dump(...)) in '.__FILE__.':'.__LINE__,'before:',count(token_get_all($xsource),COUNT_NORMAL),'after',count($xTokenArray,COUNT_NORMAL)));
            unset ($tmpstr, $xKey, $xToken, $toUnset, $tmpUnsetKeyArray);
            // </trim$xTokenArray>
            $firstInterestingLineTokenKey = -1;
            $lastInterestingLineTokenKey = -1;
            // <find$lastInterestingLineTokenKey>
            ForEach ($xTokenArray as $xKey => $xToken) {
                if (!is_array($xToken) || !array_key_exists(2,
                        $xToken) || !is_integer($xToken [2]) || $xToken [2] < $bt ['line']) {
                    continue;
                }
                $tmpkey = $xKey; // we don't got what we want yet..
                while (true) {
                    if (!array_key_exists($tmpkey, $xTokenArray)) {
                        throw new Exception ('1unable to find $lastInterestingLineTokenKey !');
                    };
                    if ($xTokenArray [$tmpkey] === ';') {
                        // var_dump(__LINE__.":SUCCESS WITH",$tmpkey,$xTokenArray[$tmpkey]);
                        $lastInterestingLineTokenKey = $tmpkey;
                        break;
                    }
                    // var_dump(__LINE__.":FAIL WITH ",$tmpkey,$xTokenArray[$tmpkey]);

                    // if $xTokenArray has >=PHP_INT_MAX keys, we don't want an infinite loop, do we? ;p
                    // i wonder how much memory that would require though.. over-engineering, err, time-wasting, ftw?
                    if ($tmpkey >= PHP_INT_MAX) {
                        throw new Exception ('2unable to find $lastIntperestingLineTokenKey ! (PHP_INT_MAX reached without finding ";"...)');
                    };
                    ++$tmpkey;
                }
                break;
            };
            if ($lastInterestingLineTokenKey <= -1) {
                throw new Exception ('3unable to find $lastInterestingLineTokenKey !');
            };
            unset ($xKey, $xToken, $tmpkey);
            // </find$lastInterestingLineTokenKey>
            // <find$firstInterestingLineTokenKey>
            // now work ourselves backwards from $lastInterestingLineTokenKey to the first token where $xTokenArray[$tmpi][1] == "hhb_var_dump"
            // i doubt this is fool-proof but.. cant think of a better way (in userland, anyway) atm..
            $tmpi = $lastInterestingLineTokenKey;
            do {
                if (array_key_exists($tmpi, $xTokenArray) && is_array($xTokenArray [$tmpi]) && array_key_exists(1,
                        $xTokenArray [$tmpi]) && is_string($xTokenArray [$tmpi] [1]) && strcasecmp($xTokenArray [$tmpi] [1],
                        $bt ['function']) === 0) {
                    // var_dump(__LINE__."SUCCESS WITH",$tmpi,$xTokenArray[$tmpi]);
                    if (!array_key_exists($tmpi + 2,
                        $xTokenArray)) { // +2 because [0] is (or should be) "hhb_var_dump" and [1] is (or should be) "("
                        throw new Exception ('1unable to find the $firstInterestingLineTokenKey...');
                    };
                    $firstInterestingLineTokenKey = $tmpi + 2;
                    break;
                    /* */
                };
                // var_dump(__LINE__."FAIL WITH ",$tmpi,$xTokenArray[$tmpi]);
                --$tmpi;
            } while (-1 < $tmpi);
            // die(var_dump('die(var_dump(...)) in '.__FILE__.':'.__LINE__,$tmpi));
            if ($firstInterestingLineTokenKey <= -1) {
                throw new Exception ('2unable to find the $firstInterestingLineTokenKey...');
            };
            unset ($tmpi);
            // Note: $lastInterestingLineTokeyKey is likely to contain more stuff than only the stuff we want..
            // </find$firstInterestingLineTokenKey>
            // <rebuildInterestingSourceCode>
            // ok, now we have $firstInterestingLineTokenKey and $lastInterestingLineTokenKey....
            $interestingTokensArray = array_slice($xTokenArray, $firstInterestingLineTokenKey,
                (($lastInterestingLineTokenKey - $firstInterestingLineTokenKey) + 1));
            unset ($addUntil, $tmpi, $tmpstr, $tmpi, $argvsourcestr, $tmpkey, $xTokenKey, $xToken);
            $addUntil = array();
            $tmpi = 0;
            $tmpstr = "";
            $tmpkey = "";
            $argvsourcestr = "";
            // $argvSourceCode[X]='source code..';
            ForEach ($interestingTokensArray as $xTokenKey => $xToken) {
                if (is_array($xToken)) {
                    $tmpstr = $xToken [1];
                    // var_dump($xToken[1]);
                } else {
                    if (is_string($xToken)) {
                        $tmpstr = $xToken;
                        // var_dump($xToken);
                    } else {
                        /* should never reach this */
                        throw new LogicException ('Impossible situation? $xToken fails is_array() and fails is_string() ...');
                    }
                };
                $argvsourcestr .= $tmpstr;

                if ($xToken === '(') {
                    $addUntil [] = ')';
                    continue;
                } else {
                    if ($xToken === '[') {
                        $addUntil [] = ']';
                        continue;
                    }
                };

                if ($xToken === ')' || $xToken === ']') {
                    if (false === ($tmpkey = array_search($xToken, $addUntil, false))) {
                        $argvSourceCode [] = substr($argvsourcestr, 0, -1); // -1 is to strip the ")"
                        if (count($argvSourceCode,
                                COUNT_NORMAL) - 1 === $argc) /*-1 because $argvSourceCode[0] is bullshit*/ {
                            break;
                            /* We read em all! :D (.. i hope) */
                        };
                        /* else... oh crap */
                        throw new Exception ('failed to read source code of (what i think is) argv['.count($argvSourceCode,
                                COUNT_NORMAL).'] ! sorry..');
                    }
                    unset ($addUntil [$tmpkey]);
                    continue;
                }

                if (empty ($addUntil) && $xToken === ',') {
                    $argvSourceCode [] = substr($argvsourcestr, 0, -1); // -1 is to strip the comma
                    $argvsourcestr = "";
                };
            };
            // die(var_dump('die(var_dump(...)) in '.__FILE__.':'.__LINE__,
            // $firstInterestingLineTokenKey,$lastInterestingLineTokenKey,$interestingTokensArray,$tmpstr
            // $argvSourceCode));
            if (count($argvSourceCode, COUNT_NORMAL) - 1 != $argc) /*-1 because $argvSourceCode[0] is bullshit*/ {
                throw new Exception ('failed to read source code of all the arguments! (and idk which ones i missed)! sorry..');
            };
            // </rebuildInterestingSourceCode>
        } catch (Exception $ex) {
            $argvSourceCode = array(); // clear it
            // TODO: failed to read source code
            // die("TODO N STUFF..".__FILE__.__LINE__);
            $analyze_sourcecode = false; // ERROR..
            if ($settings ['debug_hhb_var_dump']) {
                throw $ex;
            } else {
                /* exception ignored, continue as normal without $analyze_sourcecode */
            };
        }
        unset ($xsource, $xToken, $xTokenArray, $firstInterestingLineTokenKey, $lastInterestingLineTokenKey, $xTokenKey, $tmpi, $tmpkey, $argvsourcestr);
    };
    // </analyzeSourceCode>
    $msg = $settings ['hhb_var_dump_prepend'];
    if ($analyze_sourcecode != $settings ['analyze_sourcecode']) {
        $msg .= ' (PS: some error analyzing source code)'.$PHP_EOL;
    };
    $msg .= 'in "'.$bt ['file'].'": on line "'.$bt ['line'].'": '.$argc.' variable'.($argc === 1 ? '' : 's').$PHP_EOL; // because over-engineering ftw?
    if ($analyze_sourcecode) {
        $msg .= ' hhb_var_dump(';
        $msg .= implode(",", array_slice($argvSourceCode, 1)); // $argvSourceCode[0] is bullshit.
        $msg .= ')'.$PHP_EOL;
    }
    // array_unshift($bt,$msg);
    echo $msg;
    $i = 0;
    foreach ($argv as &$val) {
        echo 'argv['.++$i.']';
        if ($analyze_sourcecode) {
            echo ' >>>'.$argvSourceCode [$i].'<<<';
        }
        echo ':';
        if ($settings ['use_xdebug_var_dump']) {
            xdebug_var_dump($val);
        } else {
            var_dump($val);
        };
    }

    echo $settings ['hhb_var_dump_append'];
    // call_user_func_array("var_dump",$args);
}

/**
 * works like var_dump, but returns a string instead of priting it (ob_ based)
 *
 * @param  mixed  $args  ...
 * @return string
 */
function hhb_return_var_dump(): string // works like var_dump, but returns a string instead of printing it.
{
    $args = func_get_args(); // for <5.3.0 support ...
    ob_start();
    call_user_func_array('var_dump', $args);

    return ob_get_clean();
}

/**
 * enables hhb_exception_handler and hhb_assert_handler and sets error_reporting to max
 */
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
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting
        return;
    }
    throw new ErrorException ($errstr, 0, $errno, $errfile, $errline);
}

function hhb_assert_handler($file, $line, $code, $desc = null)
{
    $errstr = 'Assertion failed at '.$file.':'.$line.' '.$desc.' code: '.$code;
    throw new ErrorException ($errstr, 0, 1, $file, $line);
}

class hhb_curl
{
    protected $curlh;
    protected $curloptions = [];
    protected $response_body_file_handle;

    // CURLOPT_FILE
    protected $response_headers_file_handle;

    // CURLOPT_WRITEHEADER
    protected $request_body_file_handle;

    // CURLOPT_INFILE
    protected $stderr_file_handle;

    // CURLOPT_STDERR
    protected function truncateFileHandles()
    {
        $trun = ftruncate($this->response_body_file_handle, 0);
        assert(true === $trun);
        $trun = ftruncate($this->response_headers_file_handle, 0);
        assert(true === $trun);
        // $trun = ftruncate ( $this->request_body_file_handle, 0 );
        // assert ( true === $trun );
        $trun = ftruncate($this->stderr_file_handle, 0);
        assert(true === $trun);

        return /*true*/ ;
    }

    /**
     * returns the internal curl handle
     *
     * its probably a bad idea to mess with it, you'll probably never want to use this function.
     *
     * @return resource_curl
     */
    public function _getCurlHandle()/*: curlresource*/
    {
        return $this->curlh;
    }

    /**
     * replace the internal curl handle with another one...
     *
     * its probably a bad idea. you'll probably never want to use this function.
     *
     * @param  resource_curl  $newcurl
     * @param  bool  $closeold
     * @return void
     * @throws InvalidArgumentsException
     *
     */
    public function _replaceCurl($newcurl, bool $closeold = true)
    {
        if (!is_resource($newcurl)) {
            throw new InvalidArgumentsException ('parameter 1 must be a curl resource!');
        }
        if (get_resource_type($newcurl) !== 'curl') {
            throw new InvalidArgumentsException ('parameter 1 must be a curl resource!');
        }
        if ($closeold) {
            curl_close($this->curlh);
        }
        $this->curlh = $newcurl;
        $this->_prepare_curl();
    }

    /**
     * mimics curl_init, using hhb_curl::__construct
     *
     * @param  string  $url
     * @param  bool  $insecureAndComfortableByDefault
     * @return hhb_curl
     */
    public static function init(string $url = null, bool $insecureAndComfortableByDefault = false): hhb_curl
    {
        return new hhb_curl ($url, $insecureAndComfortableByDefault);
    }

    /**
     *
     * @param  string  $url
     * @param  bool  $insecureAndComfortableByDefault
     * @throws RuntimeException
     */
    function __construct(string $url = null, bool $insecureAndComfortableByDefault = false)
    {
        $this->curlh = curl_init(''); // why empty string? PHP Fatal error: Uncaught TypeError: curl_init() expects parameter 1 to be string, null given
        if (!$this->curlh) {
            throw new RuntimeException ('curl_init failed!');
        }
        if ($url !== null) {
            $this->_setopt(CURLOPT_URL, $url);
        }
        $fhandles = [];
        $tmph = null;
        for ($i = 0; $i < 4; ++$i) {
            $tmph = tmpfile();
            if ($tmph === false) {
                // for($ii = 0; $ii < $i; ++ $ii) {
                // // @fclose($fhandles[$ii]);//yay, potentially overwriting last error to fuck your debugging efforts!
                // }
                throw new RuntimeException ('tmpfile() failed to create 4 file handles!');
            }
            $fhandles [] = $tmph;
        }
        unset ($tmph);
        $this->response_body_file_handle = $fhandles [0]; // CURLOPT_FILE
        $this->response_headers_file_handle = $fhandles [1]; // CURLOPT_WRITEHEADER
        $this->request_body_file_handle = $fhandles [2]; // CURLOPT_INFILE
        $this->stderr_file_handle = $fhandles [3]; // CURLOPT_STDERR
        unset ($fhandles);
        $this->_prepare_curl();
        if ($insecureAndComfortableByDefault) {
            $this->_setComfortableOptions();
        }
    }

    function __destruct()
    {
        curl_close($this->curlh);
        fclose($this->response_body_file_handle); // CURLOPT_FILE
        fclose($this->response_headers_file_handle); // CURLOPT_WRITEHEADER
        fclose($this->request_body_file_handle); // CURLOPT_INFILE
        fclose($this->stderr_file_handle); // CURLOPT_STDERR
    }

    /**
     * sets some insecure, but comfortable settings..
     *
     * @return self
     */
    public function _setComfortableOptions(): self
    {
        $this->setopt_array(array(
            CURLOPT_AUTOREFERER => true,
            CURLOPT_BINARYTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPGET => true,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_COOKIEFILE => "",
            // <<makes curl save/load cookies across requests..
            CURLOPT_ENCODING => "",
            // << makes curl post all supported encodings, gzip/deflate/etc, makes transfers faster
            CURLOPT_USERAGENT => 'hhb_curl_php; curl/'.$this->version() ['version'].' ('.$this->version() ['host'].'); php/'.PHP_VERSION,
        )); //

        return $this;
    }

    /**
     * curl_errno — Return the last error number
     *
     * @return int
     */
    public function errno(): int
    {
        return curl_errno($this->curlh);
    }

    /**
     * curl_error — Return a string containing the last error
     *
     * @return string
     */
    public function error(): string
    {
        return curl_error($this->curlh);
    }

    /**
     * curl_escape — URL encodes the given string
     *
     * @param  string  $str
     * @return string
     */
    public function escape(string $str): string
    {
        return curl_escape($this->curlh, $str);
    }

    /**
     * curl_unescape — Decodes the given URL encoded string
     *
     * @param  string  $str
     * @return string
     */
    public function unescape(string $str): string
    {
        return curl_unescape($this->curlh, $str);
    }

    /**
     * executes the curl request (curl_exec)
     *
     * @param  string  $url
     * @return self
     * @throws RuntimeException
     */
    public function exec(string $url = null): self
    {
        $this->truncateFileHandles();
        // WARNING: some weird error where curl will fill up the file again with 00's when the file has been truncated
        // until it is the same size as it was before truncating, then keep appending...
        // hopefully this _prepare_curl() call will fix that.. (seen on debian 8 on btrfs with curl/7.38.0)
        $this->_prepare_curl();
        if (is_string($url) && strlen($url) > 0) {
            $this->setopt(CURLOPT_URL, $url);
        }
        $ret = curl_exec($this->curlh);
        if ($this->errno()) {
            throw new RuntimeException ('curl_exec failed. errno: '.var_export($this->errno(),
                    true).' error: '.var_export($this->error(), true));
        }

        return $this;
    }

    /**
     * Create a CURLFile object for use with CURLOPT_POSTFIELDS
     *
     * @param  string  $filename
     * @param  string  $mimetype
     * @param  string  $postname
     * @return CURLFile
     */
    public function file_create(string $filename, string $mimetype = null, string $postname = null): CURLFile
    {
        return curl_file_create($filename, $mimetype, $postname);
    }

    /**
     * Get information regarding the last transfer
     *
     * @param  int  $opt
     * @return mixed
     */
    public function getinfo(int $opt)
    {
        return curl_getinfo($this->curlh, $opt);
    }

    // pause is explicitly undocumented for now, but it pauses a running transfer
    public function pause(int $bitmask): int
    {
        return curl_pause($this->curlh, $bitmask);
    }

    /**
     * Reset all options
     */
    public function reset(): self
    {
        curl_reset($this->curlh);
        $this->curloptions = [];
        $this->_prepare_curl();

        return $this;
    }

    /**
     * curl_setopt_array — Set multiple options for a cURL transfer
     *
     * @param  array  $options
     * @return self
     * @throws InvalidArgumentException
     */
    public function setopt_array(array $options): self
    {
        foreach ($options as $option => $value) {
            $this->setopt($option, $value);
        }

        return $this;
    }

    /**
     * gets the last response body
     *
     * @return string
     */
    public function getResponseBody(): string
    {
        return file_get_contents(stream_get_meta_data($this->response_body_file_handle) ['uri']);
    }

    /**
     * returns the response headers of the last request (when auto-following Location-redirect, only the last headers are returned)
     *
     * @return string[]
     */
    public function getResponseHeaders(): array
    {
        $text = file_get_contents(stream_get_meta_data($this->response_headers_file_handle) ['uri']);

        // ...
        return $this->splitHeaders($text);
    }

    /**
     * gets the response headers of all the requets for the last execution (including any Location-redirect autofollow headers)
     *
     * @return string[][]
     */
    public function getResponsesHeaders(): array
    {
        // var_dump($this->getStdErr());die();
        // CONSIDER https://bugs.php.net/bug.php?id=65348
        $Cr = "\x0d";
        $Lf = "\x0a";
        $CrLf = "\x0d\x0a";
        $stderr = $this->getStdErr();
        $responses = [];
        while (false !== ($startPos = strpos($stderr, $Lf.'<'))) {
            $stderr = substr($stderr, $startPos + strlen($Lf));
            $endPos = strpos($stderr, $CrLf."<\x20".$CrLf);
            if ($endPos === false) {
                // ofc, curl has ths quirk where the specific message "* HTTP error before end of send, stop sending" gets appended with LF instead of the usual CRLF for other messages...
                $endPos = strpos($stderr, $Lf."<\x20".$CrLf);
            }
            // var_dump(bin2hex(substr($stderr,279,30)),$endPos);die("HEX");
            // var_dump($stderr,$endPos);die("PAIN");
            assert($endPos !== false); // should always be more after this with CURLOPT_VERBOSE.. (connection left intact / connecton dropped /whatever)
            $headers = substr($stderr, 0, $endPos);
            // $headerscpy=$headers;
            $stderr = substr($stderr, $endPos + strlen($CrLf.$CrLf));
            $headers = preg_split("/((\r?\n)|(\r\n?))/",
                $headers); // i can NOT explode($CrLf,$headers); because sometimes, in the middle of recieving headers, it will spout stuff like "\n* Added cookie reg_ext_ref="deleted" for domain facebook.com, path /, expire 1457503459"
            // if(strpos($headerscpy,"report-uri=")!==false){
            // //var_dump($headerscpy);die("DIEDS");
            // var_dump($headers);
            // //var_dump($this->getStdErr());die("DIEDS");
            // }
            foreach ($headers as $key => &$val) {
                $val = trim($val);
                if (!strlen($val)) {
                    unset ($headers [$key]);
                    continue;
                }
                if ($val [0] !== '<') {
                    // static $r=0;++$r;var_dump('removing',$val);if($r>1)die();
                    unset ($headers [$key]); // sometimes, in the middle of recieving headers, it will spout stuff like "\n* Added cookie reg_ext_ref="deleted" for domain facebook.com, path /, expire 1457503459"
                    continue;
                }
                $val = trim(substr($val, 1));
            }
            unset ($val); // references can be scary..
            $responses [] = $headers;
        }
        unset ($headers, $key, $val, $endPos, $startPos);

        return $responses;
    }

    // we COULD have a getResponsesCookies too...
    /*
     * get last response cookies
     *
     * @return string[]
     */
    public function getResponseCookies(): array
    {
        $headers = $this->getResponsesHeaders();
        $headers_merged = array();
        foreach ($headers as $headers2) {
            foreach ($headers2 as $header) {
                $headers_merged [] = $header;
            }
        }

        return $this->parseCookies($headers_merged);
    }

    // explicitly undocumented for now..
    public function getRequestBody(): string
    {
        return file_get_contents(stream_get_meta_data($this->request_body_file_handle) ['uri']);
    }

    /**
     * return headers of last execution
     *
     * @return string[]
     */
    public function getRequestHeaders(): array
    {
        $requestsHeaders = $this->getRequestsHeaders();
        $requestCount = count($requestsHeaders);
        if ($requestCount === 0) {
            return array();
        }

        return $requestsHeaders [$requestCount - 1];
    }

    // array(0=>array(request1_headers),1=>array(requst2_headers),2=>array(request3_headers))~

    /**
     * get last execution request headers
     *
     * @return string[]
     */
    public function getRequestsHeaders(): array
    {
        // CONSIDER https://bugs.php.net/bug.php?id=65348
        $Cr = "\x0d";
        $Lf = "\x0a";
        $CrLf = "\x0d\x0a";
        $stderr = $this->getStdErr();
        $requests = [];
        while (false !== ($startPos = strpos($stderr, $Lf.'>'))) {
            $stderr = substr($stderr, $startPos + strlen($Lf.'>'));
            $endPos = strpos($stderr, $CrLf.$CrLf);
            if ($endPos === false) {
                // ofc, curl has ths quirk where the specific message "* HTTP error before end of send, stop sending" gets appended with LF instead of the usual CRLF for other messages...
                $endPos = strpos($stderr, $Lf.$CrLf);
            }
            assert($endPos !== false); // should always be more after this with CURLOPT_VERBOSE.. (connection left intact / connecton dropped /whatever)
            $headers = substr($stderr, 0, $endPos);
            $stderr = substr($stderr, $endPos + strlen($CrLf.$CrLf));
            $headers = explode($CrLf, $headers);
            foreach ($headers as $key => &$val) {
                $val = trim($val);
                if (!strlen($val)) {
                    unset ($headers [$key]);
                }
            }
            unset ($val); // references can be scary..
            $requests [] = $headers;
        }
        unset ($headers, $key, $val, $endPos, $startPos);

        return $requests;
    }

    /**
     * return last execution request cookies
     *
     * @return string[]
     */
    public function getRequestCookies(): array
    {
        return $this->parseCookies($this->getRequestHeaders());
    }

    /**
     * get everything curl wrote to stderr of the last execution
     *
     * @return string
     */
    public function getStdErr(): string
    {
        return file_get_contents(stream_get_meta_data($this->stderr_file_handle) ['uri']);
    }

    /**
     * alias of getResponseBody
     *
     * @return string
     */
    public function getStdOut(): string
    {
        return $this->getResponseBody();
    }

    protected function splitHeaders(string $headerstring): array
    {
        $headers = preg_split("/((\r?\n)|(\r\n?))/", $headerstring);
        foreach ($headers as $key => $val) {
            if (!strlen(trim($val))) {
                unset ($headers [$key]);
            }
        }

        return $headers;
    }

    protected function parseCookies(array $headers): array
    {
        $returnCookies = [];
        $grabCookieName = function ($str, &$len) {
            $len = 0;
            $ret = "";
            $i = 0;
            for ($i = 0; $i < strlen($str); ++$i) {
                ++$len;
                if ($str [$i] === ' ') {
                    continue;
                }
                if ($str [$i] === '=' || $str [$i] === ';') {
                    --$len;
                    break;
                }
                $ret .= $str [$i];
            }

            return urldecode($ret);
        };
        foreach ($headers as $header) {
            // Set-Cookie: crlfcoookielol=crlf+is%0D%0A+and+newline+is+%0D%0A+and+semicolon+is%3B+and+not+sure+what+else
            /*
             * Set-Cookie:ci_spill=a%3A4%3A%7Bs%3A10%3A%22session_id%22%3Bs%3A32%3A%22305d3d67b8016ca9661c3b032d4319df%22%3Bs%3A10%3A%22ip_address%22%3Bs%3A14%3A%2285.164.158.128%22%3Bs%3A10%3A%22user_agent%22%3Bs%3A109%3A%22Mozilla%2F5.0+%28Windows+NT+6.1%3B+WOW64%29+AppleWebKit%2F537.36+%28KHTML%2C+like+Gecko%29+Chrome%2F43.0.2357.132+Safari%2F537.36%22%3Bs%3A13%3A%22last_activity%22%3Bi%3A1436874639%3B%7Dcab1dd09f4eca466660e8a767856d013; expires=Tue, 14-Jul-2015 13:50:39 GMT; path=/
             * Set-Cookie: sessionToken=abc123; Expires=Wed, 09 Jun 2021 10:18:14 GMT;
             * //Cookie names cannot contain any of the following '=,; \t\r\n\013\014'
             * //
             */
            if (stripos($header, "Set-Cookie:") !== 0) {
                continue;
                /* */
            }
            $header = trim(substr($header, strlen("Set-Cookie:")));
            $len = 0;
            while (strlen($header) > 0) {
                $cookiename = $grabCookieName ($header, $len);
                $returnCookies [$cookiename] = '';
                $header = substr($header, $len);
                if (strlen($header) < 1) {
                    break;
                }
                if ($header [0] === '=') {
                    $header = substr($header, 1);
                }
                $thepos = strpos($header, ';');
                if ($thepos === false) { // last cookie in this Set-Cookie.
                    $returnCookies [$cookiename] = urldecode($header);
                    break;
                }
                $returnCookies [$cookiename] = urldecode(substr($header, 0, $thepos));
                $header = trim(substr($header, $thepos + 1)); // also remove the ;
            }
        }
        unset ($header, $cookiename, $thepos);

        return $returnCookies;
    }

    /**
     * Set an option for curl
     *
     * @param  int  $option
     * @param  mixed  $value
     * @return self
     * @throws InvalidArgumentException
     */
    public function setopt(int $option, $value): self
    {
        switch ($option) {
            case CURLOPT_VERBOSE :
            {
                trigger_error('you should NOT change CURLOPT_VERBOSE. use getStdErr() instead. we are working around https://bugs.php.net/bug.php?id=65348 using CURLOPT_VERBOSE.',
                    E_USER_WARNING);
                break;
            }
            case CURLOPT_RETURNTRANSFER :
            {
                trigger_error('you should NOT use CURLOPT_RETURNTRANSFER. use getResponseBody() instead. expect problems now.',
                    E_USER_WARNING);
                break;
            }
            case CURLOPT_FILE :
            {
                trigger_error('you should NOT use CURLOPT_FILE. use getResponseBody() instead. expect problems now.',
                    E_USER_WARNING);
                break;
            }
            case CURLOPT_WRITEHEADER :
            {
                trigger_error('you should NOT use CURLOPT_WRITEHEADER. use getResponseHeaders() instead. expect problems now.',
                    E_USER_WARNING);
                break;
            }
            case CURLOPT_INFILE :
            {
                trigger_error('you should NOT use CURLOPT_INFILE. use setRequestBody() instead. expect problems now.',
                    E_USER_WARNING);
                break;
            }
            case CURLOPT_STDERR :
            {
                trigger_error('you should NOT use CURLOPT_STDERR. use getStdErr() instead. expect problems now.',
                    E_USER_WARNING);
                break;
            }
            case CURLOPT_HEADER :
            {
                trigger_error('you NOT use CURLOPT_HEADER. use  getResponsesHeaders() instead. expect problems now. we are working around https://bugs.php.net/bug.php?id=65348 using CURLOPT_VERBOSE, which is, until the bug is fixed, is incompatible with CURLOPT_HEADER.',
                    E_USER_WARNING);
                break;
            }
            case CURLINFO_HEADER_OUT :
            {
                trigger_error('you should NOT use CURLINFO_HEADER_OUT. use  getRequestHeaders() instead. expect problems now.',
                    E_USER_WARNING);
                break;
            }

            default :
            {
            }
        }

        return $this->_setopt($option, $value);
    }

    /**
     *
     * @param  int  $option
     * @param  unknown  $value
     * @return self
     * @throws InvalidArgumentException
     */
    private function _setopt(int $option, $value): self
    {
        $ret = curl_setopt($this->curlh, $option, $value);
        if (!$ret) {
            throw new InvalidArgumentException ('curl_setopt failed. errno: '.$this->errno().'. error: '.$this->error().'. option: '.var_export($this->_curlopt_name($option),
                    true).' ('.var_export($option, true).'). value: '.var_export($value, true));
        }
        $this->curloptions [$option] = $value;

        return $this;
    }

    /**
     * return an option previously given to setopt(_array)
     *
     * @param  int  $option
     * @param  bool  $isset
     * @return mixed|NULL
     */
    public function getopt(int $option, bool &$isset = null)
    {
        if (array_key_exists($option, $this->curloptions)) {
            $isset = true;

            return $this->curloptions [$option];
        } else {
            $isset = false;

            return null;
        }
    }

    /**
     * return a string representation of the given curl error code
     *
     * (ps, most of the time you'll probably want to use error() instead of strerror())
     *
     * @param  int  $errornum
     * @return string
     */
    public function strerror(int $errornum): string
    {
        return curl_strerror($errornum);
    }

    /**
     * gets cURL version information
     *
     * @param  int  $age
     * @return array
     */
    public function version(int $age = CURLVERSION_NOW): array
    {
        return curl_version($age);
    }

    private function _prepare_curl()
    {
        $this->truncateFileHandles();
        $this->_setopt(CURLOPT_FILE, $this->response_body_file_handle); // CURLOPT_FILE
        $this->_setopt(CURLOPT_WRITEHEADER, $this->response_headers_file_handle); // CURLOPT_WRITEHEADER
        $this->_setopt(CURLOPT_INFILE, $this->request_body_file_handle); // CURLOPT_INFILE
        $this->_setopt(CURLOPT_STDERR, $this->stderr_file_handle); // CURLOPT_STDERR
        $this->_setopt(CURLOPT_VERBOSE, true);
    }

    /**
     * gets the constants name of the given curl options
     *
     * useful for error messages (instead of "FAILED TO SET CURLOPT 21387" , you can say "FAILED TO SET CURLOPT_VERBOSE" )
     *
     * @param  int  $option
     * @return mixed|boolean
     */
    public function _curlopt_name(int $option)/*:mixed(string|false)*/
    {
        // thanks to TML for the get_defined_constants trick..
        // <TML> If you had some specific reason for doing it with your current approach (which is, to me, approaching the problem completely backwards - "I dug a hole! How do I get out!"), it seems that your entire function there could be replaced with: return array_flip(get_defined_constants(true)['curl']);
        $curldefs = array_flip(get_defined_constants(true) ['curl']);
        if (isset ($curldefs [$option])) {
            return $curldefs [$option];
        } else {
            return false;
        }
    }

    /**
     * gets the constant number of the given constant name
     *
     * (what was i thinking!?)
     *
     * @param  string  $option
     * @return int|boolean
     */
    public function _curlopt_number(string $option)/*:mixed(int|false)*/
    {
        // thanks to TML for the get_defined_constants trick..
        $curldefs = get_defined_constants(true) ['curl'];
        if (isset ($curldefs [$option])) {
            return $curldefs [$option];
        } else {
            return false;
        }
    }
}
