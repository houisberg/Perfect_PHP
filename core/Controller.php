<?php

// コントローラ抽象クラス。
// アプリケーションごとに子クラスを作成し、アクションを定義すること。
abstract class Controller
{
    protected $controller_name;
    protected $action_name;
    protected $application;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;

    protected $auth_actions = array(); // ログインが必要なアクションのリスト。すべてのアクションでログイン必須ならtrueを指定

    public function __construct($application) {

        // 自身のクラス名を取得し'Controller'を取り除き(-10文字)、頭を小文字にする
        // 例：UserController → user
        $this->controller_name = strtolower(substr(get_class($this), 0, -10));


        $this->application  = $application;
        $this->request      = $application->getRequest();
        $this->response     = $application->getResponse();
        $this->session      = $application->getSession();
        $this->db_manager   = $application->getDbManager();
    }

    // アクションの実行
    public function run($action, $params = array())
    {
        $this->action_name = $action;

        // アクションメソッドの命名規則：「アクション名 + Action()」
        $action_method = $action . 'Action';
        if (!method_exists($this, $action_method)) {
            $this->forward404();
        }

        // ログインが必要かつ未ログインならUnauthorizedActionExceptionを投げる　→　呼び出し元のApplicationクラスでキャッチ
        if ($this->needsAuthentication($action) && !$this->session->isAuthenticated()) {
            throw new UnauthorizedActionException();
        }

        // 可変関数を使用し、アクション実行
        $content = $this->$action_method($params);

        return $content;
    }

    // ログインが必要か判定
    protected function needsAuthentication($action)
    {
        if ($this->auth_actions === true ||
            (is_array($this->auth_actions) && in_array($action, $this->auth_actions))) {
                return true;
        }
        return false;
    }

    /**
     * Viewクラスのrender()メソッドをラッピングする。
     */
    protected function render($variables = array(), $template = null, $layout = 'layout')
    {
        // デフォルト値の設定
        $defaults = array(
            'request'   => $this->request,
            'base_url'  => $this->request->getBaseUrl(),
            'session'   => $this->session,
        );

        // Viewインスタンス作成
        $view = new View($this->application->getViewDir(), $defaults);

        // テンプレート名の指定がない場合はアクション名をファイル名として利用
        if (is_null($template)) {
            $template = $this->action_name;
        }

        // コントローラ名を頭に付ける
        $path = $this->controller_name . '/' . $template;

        return $view->render($path, $variables, $layout);
    }

    protected function forward404()
    {
        throw new HttpNotFoundException('Forwarded 404 page from '
            . $this->controller_name . '/' . $this->action_name);
    }

    /**
     * 受け取ったURLをResponseオブジェクトにリダイレクトするように設定
     */
    protected function redirect($url)
    {
        // 絶対パスの形に整形
        if (!preg_match('#https?://#', $url)) {
            $protocol = $this->request->isSsl() ? 'https://' : 'http://';
            $host = $this->request->getHost();
            $base_url = $this->request->getBaseUrl();

            $url = $protocol . $host . $base_url . $url;
        }

        // 明示的にステータスコードを設定（なくても可らしい）
        $this->response->setStatusCode(302, 'Found');
        // Locationヘッダを指定
        $this->response->setHttpHeader('Location', $url);

    }

    /**
     * CSRF対策
     * トークン生成メソッド
     */
    protected function generateCsrfToken($form_name)
    {
        $key = 'csrf_tokens/' . $form_name;
        $tokens = $this->session->get($key, array());
        if (count($tokens) >= 10) {
            array_shift($tokens);   // 10個以上なら古いもの（先頭）を削除
        }

        // SHA1ハッシュでトークン生成
        $token = sha1($form_name . session_id() . microtime());
        $tokens[] = $token;

        // $_SESSIONにセット
        $this->session->set($key, $tokens);
    }

    /**
     * CSRF対策
     * POSTされたトークンと一致するものをセッション上のトークンから探す
     */
    protected function checkCsrfToken($form_name, $token)
    {
        $key = 'csrf_tokens/' . $form_name;
        $tokens = $this->session->get($key, array());

        if (($pos = array_search($token, $tokens, true) !== false)) {
            unset($tokens[$pos]);   // 不要なトークンを削除する
            $this->session->set($key, $tokens);

            return true;
        }

        return false;
    }
}