<?php

class DbManager
{
    protected $connections = array();
    
    protected $repository_connection_map = array(); // Repositoryクラスでどの接続を扱うかの情報

    protected $repositories = array();  // Repositoryクラスのインスタンスの格納先

    // 接続を行う
    public function connect($name, $params)
    {
        $params = array_merge(array(
            'dsn'   => null,
            'user'  => '',
            'password'  => '',
            'options'   => array(),
        ), $params);

        $con = new PDO(
            $params['dsn'],
            $params['user'],
            $params['password'],
            $params['options']
        );

        // DBハンドルの属性を設定
        $cons->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->connections[$name] = $con;
    }

    // 接続した情報を取得
    public function getConnection($name = null)
    {
        if (is_null($name)) {
            // current()は配列の内部ポインタが示す値を取得する。ここでは、指定がなければPDOインスタンスをつっこむようにしている
            return current($this->connections);
        }

        return $this->connections[$name];
    }

    public function setRepositoryConnectionMap($repository_name, $name)
    {
        $this->repository_connection_map[$repository_name] = $name;
    }
    
    public function getConnectionForRepository($repository_name)
    {
        // 接続情報を取得
        if (isset($this->repository_connection_map[$repository_name])) {
            $name = $this->repository_connection_map[$repository_name];
            $con = $this->getConnection($name);
        } else {
            $con = $this->getConnection();
        }

        return $con;
    }

    // インスタンス生成メソッド
    public function get($repository_name)
    {
        // 指定したレポジトリのインスタンスがプロパティに入っていない場合のみ、インスタンスを生成する
        if (!isset($this->repositories[$repository_name])) {
            $repository_class = $repository_name . 'Repository';
            $con = $this->getConnectionForRepository($repository_name);

            // クラス名に応じて動的にインスタンス作成
            $repository = new $repository_class($con);

            // 作成したインスタンスを格納
            $this->repositories[$repository_name] = $repository;
        }
        return $this->repositories[$repository_name];
    }

    // DB接続の開放
    public function __destruct()
    {
        // Repositoryクラス内で接続情報を参照しているため、こちらから先に削除する。
        foreach ($this->repositories as $repository) {
            unset($repository);
        }
        foreach ($this->connections as $con) {
            unset($con);
        }
    }

}