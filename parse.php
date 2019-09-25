<?php

define('PER_BLOCK' , '10');
//db.class connect

define('DB_HOST' , 'localhost');
define('DB_USER' , 'root');
define('DB_PASSWORD' , '');
define('DB_NAME' , 'simple');

//install library
require_once "simple_html_dom.php";
require_once "db.class.php";

$url = 'http://ananaska.com/vse-novosti/';
//connect to base
$db = new DB(DB_HOST, DB_USER, DB_PASSWORD , DB_NAME);

//get params from cli
if(isset($argv[1])){
    $action = $argv[1];
    echo $action;
}
else{
    echo 'No action';
    exit;
}

// get links to articls
if($action == 'catalog'){
    getArticlesLinksFromCatalog($url);

}elseif($action == 'articles')
{
    while (true){
        //get random hash
        $tmp_uniq = md5(uniqid().time());
        $db->query("update articles set tmp_uniq = '{$tmp_uniq}' where tmp_uniq is null limit 10".PER_BLOCK);
        //get marked articles
        $articles = $db->query("select url from articles where tmp_uniq = '{$tmp_uniq}'");
        //if no exit
        if(!$articles){
            echo PHP_EOL."All done";
            exit;
        }
        //proceed each of marked articles
        foreach ($articles as $article)
        {
            getArticleData($article['url']);
        }
    }
}


function getArticleData($url)
{
    global $db;
    echo PHP_EOL.$url.PHP_EOL;
    $article = file_get_html($url);
    $h1 = $db->escape($article->find('h1' , 0)->innertext);
    $content = $db->escape($article->find('article' , 0)->innertext);
    $data = compact('h1' , 'content');
    $sql = "
        update articles
            set h1 = '{$h1}',
                content = '{$content}',
                dataparsing = NOW()
            where url = '{$url}'    
    ";
    $db->query($sql);
    return $data;
}


function getArticlesLinksFromCatalog($url) {
    global $db;

    echo PHP_EOL.$url.PHP_EOL.PHP_EOL;

    //get page
    $html = file_get_html($url);
    // get article link
    foreach ($html->find('a.read-more-link') as $link_to_article) {
        //add articles link to db
        $article_url = $db->escape($link_to_article->href);
        $sql = "
            insert ignore into articles
                set url = '{$article_url}'
        ";
        $db->query($sql);
        //parse and save current article data
        getArticleData($link_to_article->href);
        echo $link_to_article->href . PHP_EOL;
    }
    //recursion of next page
    if($next_link = $html->find('a.next' , 0)){
        getArticlesLinksFromCatalog($next_link->href);
    }
}

