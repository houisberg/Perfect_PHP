<?php

// HTTPヘッダの情報はすべて当クラス内で処理すること。
class Response
{
    protected $content;
    protected $status_code = 200;
    protected $status_text = 'OK';
    protected $http_headers = array();

    // クライアントに返す内容をプロパティに格納
    public function setContent($content)
    {
        $this->content = $content;
    }

    // HTTPステータスコードをプロパティに格納
    public function setStatusCode($status_code, $status_text = '')
    {
        $this->status_code = $status_code;
        $this->status_text = $status_text;
    }

    // HTTPヘッダをプロパティに格納
    public function setHttpHeader($name, $value)
    {
        $this->http_headers[$name] = $value;
    }

    // レスポンスを送信
    public function send()
    {
        // ステータスコードを指定して送信
        header('HTTP/1.1' . $this->status_code . ' ' . $this->status_text);

        //HTTPレスポンスヘッダの指定があればそれも送信
        foreach ($this->http_headers as $name => $value) {
            header($name . ': ' . $value);
        }

        // レスポンス内容を送信（echoで送信ができる）
        echo $this->content;
    }

}