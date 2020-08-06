<?php

// 抽象クラス
abstract class DbRepository
{
    protected $con;

    public function __construct($con)
    {
        $this->setConnection($con);
    }

    public function setConnection($con)
    {
        $this->con = $con;
    }

    // SQL実行
    public function execute($sql, $params = array())
    {
        $statement = $this->con->prepare($sql); // PDOStatementクラスのインスタンスを取得
        $stmt->execute($params);                // SQL発行（$paramsはプレースホルダ部分を置き換えるパラメータ）

        return $statement;
    }

    // 一行のみ取得
    public function fetch($sql, $params = array())
    {
        return $this->execute($sql, $params)->fetch(PDO::FETCH_ASSOC);
    }
    
    //全行取得
    public function fetchAll($sql, $params = array())
    {
        return $this->execute($sql, $params)->fetchAll(PDO::FETCH_ASSOC);
    }
}