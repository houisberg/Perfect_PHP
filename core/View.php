<?php

class View
{
    protected $base_dir;
    protected $defaults;
    protected $layout_variables = array();

    public function __construct($base_dir, $defaults = array()) {
        $this->base_dir = $base_dir;    // viewsディレクトリまでの絶対パス
        $this->defaults = $defaults;    // デフォルトでビューファイルに渡す変数
    }

    /**
     * プロパティ設定
     * ビューファイルから呼び出してレイアウト用のパラメータを設定できる
     */
    public function setLayoutVar($name, $value)
    {
        $this->layout_variables[$name] = $value;
    }

    /**
     * $_path: ビューファイルへのパス
     * $_variables: ビューファイルに渡すパラメータ
     * $_layout: レイアウトファイル名
     */
    public function render($_path, $_variables = array(), $_layout = false)
    {
        $_file = $this->base_dir . '/' . $_path . '.php';

        // パラメータ配列を変数展開
        extract(array_merge($this->defaults, $_variables));

        // アウトプットバッファリングを開始
        ob_start();
        //バッファの自動フラッシュ（バッファ容量を超えたときに自動的に出力する）を無効化
        ob_implicit_flush(0);

        // バッファリング中に出力された文字列は内部のバッファに格納される。
        require $_file;

        // バッファに格納された文字列を取得
        $content = ob_get_clean();

        // レイアウトファイルの指定があるときのみ、レイアウトの読込を行う
        if ($_layout) {
            // もう一度render()を実行
            // 先に読み込んだビューファイルの内容は'_content'キーで渡す
            // 　→ レイアウトファイル内で$_contentの内容を出力することで1つのHTMLになり、$contentに再度格納される。
            $content = $this->render($_layout, 
                array_merge($this->layout_variables, array(
                    '_content' => $content,
                )
            ));
        }

        return $content;

    }

    // HTML特殊文字エスケープ
    public function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}