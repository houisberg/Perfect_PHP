<?php

class Session
{
    protected static $sessionStarted = false;           // 2回以上session_start()を呼ばないよう、静的プロパティで設定する。
    protected static $sessionIdRegenerated = false;     // 2回以上regenerate()を呼ばないよう、静的プロパティで設定する。

    public function __construct()
    {
        if (!self::$sessionStarted) {
            session_start();
            self::$sessionStarted = true;
        }
    }

    public function set($name, $value)
    {
        $_SESSION[$name] = $value;
    }

    public function get($name, $default = null)
    {
        if (isset($_SESSION[$name])) {
            return $_SESSION[$name];
        }
        return $default;
    }

    public function remove($name)
    {
        unset($_SESSION[$name]);
    }

    // セッションをクリア
    public function clear()
    {
        $_SESSION = array();
    }

    // セッションIDを新しく発行する
    public function regenerate($destroy = true)
    {
        if (!self::$sessionIdRegenerated) {
            session_regenerate_id($destroy);
            self::$sessionIdRegenerated = true;
        }
    }

    // ログイン状態制御　ほんとは他クラスに置くのが望ましいが、今回は簡略化
    public function setAuthenticated($bool)
    {
        $this->set('_authenticated', (bool)$bool);
        $this->regenerate();
    }
    public function isAuthenticated()
    {
        return $this->get('_authenticated', false);
    }
}