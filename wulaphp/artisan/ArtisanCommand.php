<?php

namespace wulaphp\artisan;
/**
 * 命令.
 *
 * @author  leo <windywany@gmail.com>
 * @package wulaphp\artisan
 * @since   1.0
 */
abstract class ArtisanCommand {
    protected $pid  = '';
    protected $color;
    protected $argv = [];
    protected $arvc = 0;

    public function __construct() {
        $this->color = new Colors();
        @chdir(APPROOT);
    }

    public function help($message = '') {
        $color = $this->color;
        if ($message) {
            echo $color->str("ERROR:\n", 'red');
            echo "  " . wordwrap($message, 72, "\n  ") . "\n\n";
        }
        $opts  = $this->getOpts();
        $lopts = $this->getLongOpts();
        echo wordwrap($this->desc(), 72, "\n  ") . "\n\n";
        echo $color->str("USAGE:\n", 'green');
        echo "  #php artisan " . $this->cmd() . (($opts || $lopts) ? ' [options] ' : ' ') . $color->str($this->argDesc(), 'blue') . "\n\n";

        foreach ($opts as $opt => $msg) {
            $opss = explode(':', $opt);
            $l    = count($opss);
            $arg  = $opss[ $l - 1 ];
            $str  = str_pad($opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : '')), 24, ' ', STR_PAD_RIGHT);
            echo "    " . $color->str('-' . $str, 'green') . wordwrap($msg, 72, str_pad("\n", 28, ' ', STR_PAD_RIGHT)) . "\n";
        }

        foreach ($lopts as $opt => $msg) {
            $opss = explode(':', $opt);
            $l    = count($opss);
            $arg  = $opss[ $l - 1 ];
            $str  = str_pad($opss[0] . ($arg && $l == 2 ? " <$arg>" : ($arg && $l == 3 ? " [$arg]" : '')), 23, ' ', STR_PAD_RIGHT);
            echo "    " . $color->str('--' . $str, 'green') . wordwrap($msg, 72, str_pad("\n", 28, ' ', STR_PAD_RIGHT)) . "\n";
        }
        echo "\n";
        if ($message) {
            exit (1);
        } else {
            exit (0);
        }
    }

    protected function getOpts() {
        return [];
    }

    protected function getLongOpts() {
        return [];
    }

    protected final function getOptions() {
        static $options = null;
        global $argv, $argc;
        if ($options !== null) {
            return $options;
        }
        $options = [];
        $op      = [];
        $opts    = $this->getOpts();
        foreach ($opts as $opt => $msg) {
            $opss                 = explode(':', $opt);
            $l                    = count($opss);
            $op[ '-' . $opss[0] ] = $l;
        }
        $opts = $this->getLongOpts();
        foreach ($opts as $opt => $msg) {
            $opss                  = explode(':', $opt);
            $l                     = 10 + count($opss);
            $op[ '--' . $opss[0] ] = $l;
        }
        foreach ($op as $o => $r) {
            $key = trim($o, '-');
            for ($i = 2; $i < $argc; $i++) {
                if (strpos($argv[ $i ], $o) === 0) {
                    $ov         = $argv[ $i ];
                    $argv[ $i ] = null;
                    if ($r == 1 || $r == 11) {
                        $options[ $key ] = true;
                        break;
                    }
                    $v = str_replace($o, '', $ov);
                    if ($v || is_numeric($v)) {
                        if ($r < 10) {
                            $options[ $key ] = $v;
                            break;
                        } else if ($r == 11) {
                            $this->help('unknown option: ' . $this->color->str(trim($ov, '-'), 'red'));
                        }
                    }
                    for ($j = $i + 1; $j < $argc; $j++) {
                        $v = $argv[ $j ];
                        if ($v == '=') {
                            $argv[ $j ] = null;
                            continue;
                        } else if (strpos('-', $v) === 0) {
                            break;
                        } else {
                            $argv[ $j ]      = null;
                            $options[ $key ] = $v;
                            break;
                        }
                    }
                }
            }

            if (($r == 2 || $r == 12) && !isset($options[ $key ])) {
                $this->help('Missing option: ' . $this->color->str($o, 'red'));
            }
        }
        $this->argv[0] = $argv[0];
        $this->argv[1] = $argv[1];
        for ($i = 2; $i < $argc; $i++) {
            if ($argv[ $i ] && preg_match('#^(-([^-]*).*|--(.*))$#', $argv[ $i ], $ms)) {
                if ($ms[2]) {
                    $this->help('unknown option: ' . $this->color->str($ms[2], 'red'));
                } else {
                    $argv[ $i ] = null;
                }
            }
            if (!is_null($argv[ $i ])) {
                $this->argv[] = $argv[ $i ];
            }
        }
        $this->arvc = count($this->argv);

        return $options;
    }

    protected final function opt($index = -1, $default = '') {
        $argvv = $this->argv;
        $argcc = $this->arvc;
        if ($index < 0) {
            $index = $argcc + $index;
            if ($index < 2) {
                return $default;
            }
        } else {
            $index += 2;
        }

        if ($argcc > 2 && isset($argvv[ $index ])) {
            return $argvv[ $index ];
        }

        return $default;
    }

    protected final function log($message = '', $nl = true) {
        $msg = $message . ($nl ? "\n" : '');
        echo $msg;
        flush();
    }

    protected final function logd($message = '', $nl = true) {
        if (DEBUG < DEBUG_INFO) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [DEBUG] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function loge($message = '', $nl = true) {
        if (DEBUG < DEBUG_OFF) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [ERROR] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function logw($message = '', $nl = true) {
        if (DEBUG < DEBUG_ERROR) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [WARN] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function logi($message = '', $nl = true) {
        if (DEBUG < DEBUG_WARN) {
            $msg = ($nl ? '[' . date('Y-m-d H:i:s') . '] ' . $this->pid . ' [INFO] ' : '') . $message . ($nl ? "\n" : '');
            echo $msg;
            flush();
        }
    }

    protected final function error($message) {
        $color = $this->color;
        $msg   = $this->pid . $color->str("ERROR:\n", 'red') . $message . "\n";
        echo $msg;

        flush();
    }

    protected final function output($message, $rtn = true) {
        echo $message, $rtn ? "\n" : '';

        flush();
    }

    protected final function cell($messages, int $len = 0, string $pad = ' ') {
        if (!is_array($messages)) {
            $messages = [[$messages, $len]];
        }
        $msgs = [];
        foreach ($messages as $message) {
            $l = strlen($message[0]);
            if ($l < $message[1]) {
                $msgs[] = str_pad($message[0], $message[1], $pad);
            } else if ($l > $message[1]) {
                $msgs[] = substr($message[0], 0, $message[1]);
            } else {
                $msgs[] = $message[0];
            }
        }

        return implode('', $msgs);
    }

    protected final function success($message) {
        $color = $this->color;
        $msg   = $this->pid . $color->str("SUCCESS:\n", 'green') . $message . "\n";
        echo $msg;
        flush();
    }

    protected final function redText(string $message) {
        return $this->color->str($message, 'red');
    }

    protected final function greenText(string $message) {
        return $this->color->str($message, 'green');
    }

    protected final function blueText(string $message) {
        return $this->color->str($message, 'blue');
    }

    public function run() {
        $options = $this->getOptions();
        $argOk   = $this->argValid($options) && $this->paramValid($options);
        if (!$argOk) {
            exit(1);
        }

        return $this->execute($options);
    }

    protected function argDesc() {
        return '';
    }

    /**
     * 校验参数.
     *
     * @param array $options
     *
     * @return bool
     */
    protected function argValid(/** @noinspection PhpUnusedParameterInspection */
        $options) {
        return true;
    }

    /**
     * 校验param
     *
     * @param array $options
     *
     * @return bool
     */
    protected function paramValid(/** @noinspection PhpUnusedParameterInspection */
        $options) {
        return true;
    }

    public abstract function cmd();

    public abstract function desc();

    protected abstract function execute($options);
}