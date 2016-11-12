<?php

// Выключение протоколирования ошибок
error_reporting(0);
//error_reporting(E_ALL ^ E_NOTICE);

if ($_SERVER['SERVER_ADDR'] == '127.0.0.1') {
	// параметры соединения с сервером БД на ЛОКАЛЬНОЙ МАШИНЕ
	$server = 'localhost';
	$username = 'root';
	$userpassw = 'root';
	$database = 'firstdb';
} else {
	// параметры соединения с сервером БД на хостинге https://guestbook.000webhostapp.com
	/*$server = 'localhost';
	$username = 'id85344_root';
	$userpassw = '123456';
	$database = 'id85344_newdb7';*/
	// параметры соединения с сервером БД на хостинге mediasoft
	$server = 'localhost';
	$username = 'learningtest17';
	$userpassw = 'zRRtxONo';
	$database = 'learningtest17';
}


//параметры разбиения на страницы
$page_size = 5;

if ((isset($_GET['page'])) && (ctype_digit($_GET['page']))) {
  $page = $_GET['page'];
} else {
  $page = 1;
}

//почта вебмастера
$admin_mail = 'a432974@yandex.ru';

//аватарки
$path = 'uploads'; //папка на сервере
$avatarMaxSize = 50000; //максимальный размер файла аватарки
$avatarValidTypes = array("", "gif","jpg", "png", "jpeg"); //допустимые типы файлов для аватарки

?>
