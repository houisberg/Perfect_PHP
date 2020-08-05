<?php

class Router
{
    protected $routes;

    public function __construct($definitions)
    {
        $this->routes = $this->compileRoutes($definitions);
    }

    // ルーティング定義変換メソッド
    public function compileRoutes($definitions)
    {
        $routes = array();

        foreach ($definitions as $url => $params) {
            $tokens = explode('/', ltrim($url, '/'));   // スラッシュごとに配列に分ける
            foreach ($tokens as $t => $token) {
                if (strpos($token, ':') === 0) {        //コロンを含む場合
                    $name = substr($token, 1);              // まず':'を取り除く
                    $token = '(?P<' . $name . '>[^/]+)';    // そして正規表現でキャプチャできる形式にする
                }
                $tokens[$t] = $token;
            }

            $pattern = '/' . implode('/', $tokens);
            $routes[$pattern] = $params;
        }

        return $routes;
    }

    // ルーティング定義（変換済）とPATH_INFOのマッチングを行うメソッド
    public function resolve($path_info)
    {
        // 頭にスラッシュがない場合は付ける
        if (substr($path_info, 0, 1) !== '/') {
            $path_info = '/' . $path_info;
        }

        foreach ($this->routes as $pattern => $params) {
            // 変換済のルーティング定義配列（$routes）と正規表現でマッチング
            if (preg_match('#^' . $pattern . '$#', $path_info, $matches)) {
                // 一致したら定義の値とキャプチャした値を連結
                $params = array_merge($params, $matches);
                return $params;
            }
        }

        // マッチせず終了
        return false;
    }

}