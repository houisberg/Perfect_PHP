<?php

// アプリケーション抽象クラス。
// core内の他のクラスのオブジェクトの管理や、コントローラ実行など全体の流れを司る。
abstract class Application
{
    protected $debug = false;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;

    protected $login_action = array();    // ログイン画面の指定

    public function __construct($debug = false)
    {
        $this->setDebugMode($debug);
        $this->initialize();
        $this->configure();
    }
    
    protected function setDebugMode($debug)
    {
        if ($debug) {
            $this->debug = true;
            ini_set('display_errors', 1);
            error_reporting(-1);     // エラーを全て表示するよう設定
        } else {
            $this->debug = false;
            ini_set('display_errors', 0);
        }
    }

    protected function initialize()
    {
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->db_manager = new DbManager();
        $this->router = new Router($this->registerRoutes());    //
    }

    // initialize後に呼び出されるメソッド
    protected function configure()
    {
        
    }

    abstract public function getRootDir();
    abstract public function registerRoutes();  // ルーティング定義配列を返す抽象メソッドを定義。個別アプリごとに定義させる形

    public function isDebugMode()
    {
        return $this->debug;
    }

    public function getRequest()
    {
        return $this->request;
    }

    public function getResponse()
    {
        return $this->response;
    }
    public function getSession()
    {
        return $this->session;
    }
    public function getDbManager()
    {
        return $this->db_manager;
    }
    public function getControllerDir()
    {
        return $this->getRootDir() . '/controllers';
    }
    public function getViewDir()
    {
        return $this->getRootDir() . '/views';
    }
    public function getModelDir()
    {
        return $this->getRootDir() . '/models';
    }
    public function getWebDir()
    {
        return $this->getRootDir() . '/web';
    }

    // 以下、コントローラの呼び出しと実行
    
    // コントローラを特定し、レスポンスを送信する
    // ユーザのリクエストに対するトリガーとなる
    public function run()
    {
        try {
            $params = $this->router->resolve($this->request->getPathInfo());
            if ($params === false) {
                throw new HttpNotFoundException('No route found for ' . $this->request->getPathInfo());
            }
            
            $controller = $params['controller'];
            $action = $params['action'];
            
            $this->runAction($controller, $action, $params);
            
            
        } catch (HttpNotFoundException $e) {
            $this->render404Page($e);
        } catch (UnauthorizedActionException $e) {
            list($controller, $action) = $this->login_action;   // ログイン画面のコントローラ名とアクション名を設定
            $this->runAction($controller, $action);             // ログイン画面を実行させる
        }
        
        $this->response->send();
    }


    public function runAction($controller_name, $action, $params = array())
    {
        $controller_class = ucfirst($controller_name) . 'Controller';

        $controller = $this->findController($controller_class);
        if ($controller === false) {
            throw new HttpNotFoundException($controller_class . ' controller is not found.');
        }

        // Controllerクラスのrunメソッドを実行　HTMLコンテンツを取得
        $content = $controller->run($action, $params);

        // HTMLをContentにセット
        $this->response->setContent($content);
    }

    protected function findController($controller_class)
    {
        
        // コントローラクラスが読み込まれていない場合
        if (!class_exists($controller_class)) {
            $controller_file = $this->getControllerDir() . '/' . $controller_class . '.php';

            if (!is_readable($controller_file)) {
                return false;
            } else {
                require_once $controller_file;
                if (!class_exists($controller_class)) {
                    return false;
                }
            }
        }
   

        return new $controller_class($this);
        
    }

    // 404画面をResponseクラスに渡す
    // 独自の404画面を表示させたい場合はこちらをオーバーライドすること。
    protected function render404Page($e) {
        $this->response->setStatusCode(404, 'Not Found');
        $message = $this->isDebugMode() ? $e->getMessage() : 'Page not found.'; // 三項演算子...「条件式 ? 式1 : 式2」
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $this->response->setContent(<<<EOF
        <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
        "http:www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
        <html>
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"
            <title>404</title>
        </head>
        <body>
            {$message}
        </body>
        </html>
        EOF
        );
    }

}