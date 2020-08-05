<?php

class ClassLoader
{
    protected $dirs;

    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    // ディレクトリをいくつも登録する場合に備えて実装？
    // bootstrap.phpから呼び出す
    public function registerDir($dir)
    {
        $this->dirs[] = $dir;

    }
    public function loadClass($class)
    {
        foreach ($this->dirs as $dir) {
            $file = $dir . '/' . '.php';
            if (is_readable($file)) {
                require $file;
                return;
            }
        }
    }

}