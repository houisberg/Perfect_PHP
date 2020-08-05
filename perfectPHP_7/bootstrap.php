<?php

require 'core/ClassLoader.php';

$loader = new ClassLoader();
// オートロードの対象ディレクトリを設定。今回はcoreとmodels
$loader->registerDir(dirname(__FILE__).'/core');
$loader->registerDir(dirname(__FILE__).'/models');
// オートロード登録
$loader->register();