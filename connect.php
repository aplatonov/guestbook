<?php

require_once("params.php");

$GLOBALS['$connect'] = mysqli_connect($server, $username, $userpassw, $database) or die('Не удалось подключиться к серверу!');

mysqli_set_charset ($GLOBALS['$connect'],'utf8');

// запрос к БД и возврат результата в виде массива
function db_query($sql) {
  $result = mysqli_query($GLOBALS['$connect'], $sql);
  while ($arr = mysqli_fetch_array($result, MYSQL_ASSOC))
    {
      $new[] = $arr;
    }
  return $new;
}

//вспомогательная функция для вывода переменных
function dump($var)
{
	echo "<pre>";
	print_r($var);
	echo "</pre>";
}

//функция для записи лога. в качества параметра принимает словесное описание происходящего действия
function gb_log($action) {
	$visitip   = $_SERVER['REMOTE_ADDR'];
	$frompage  = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : "";
	$request = isset($_SERVER['REQUEST_URI'])?$_SERVER['REQUEST_URI'] : ""; 

	$sql_buf = "INSERT INTO `gb_log` VALUES(".
				"'".date("Y.m.d")."',".
				"'".date("H:i:s")."',".
				"'".$action."',".
				"'".$_SESSION['user_id']."',".
				"'".$_SESSION['login']."',".
				"'".$visitip."',".
				"'".$frompage."',".
				"'".$request."','";
	foreach($_GET as $key => $value){
	  $sql_buf .= "&".$key."=".$value;
	}
	$sql_buf .= "','";
	//да, в лог пишем значение POST это очень небезопасно
	//т.к.  можно увидеть пароли в открытом виде, но на этапе обучения сойдет
	foreach($_POST as $key => $value){
	  $sql_buf .= "&".$key."=".$value;
	}
	$sql_buf .= "')";
	
	mysqli_query($GLOBALS['$connect'], $sql_buf);
}

//массив с bb-кодами и их html-эквивалентом для регулярных выражений
			
function bbCodesToHtml($source) {
$bb_patterns = array(
			"#\[b\](.*?)\[/b\]#si",
			"#\[i\](.*?)\[/i\]#si",
			"#\[u\](.*?)\[/u\]#si",
			"#\[url\](.*?)\[/url\]#si",
			"#\[code\](.*?)\[/code\]#si",
			"#\[quote\](.*?)\[/quote\]#si"
			);

$bb_replacements = array(
			"<b>\\1</b>",
			"<i>\\1</i>",
			"<u>\\1</u>",
			"<a href=\"\\1\">\\1</a>",
			"<pre>\\1</pre>",
			"<blockquote>\\1</blockquote>"
			);

  $source = preg_replace ($bb_patterns, $bb_replacements, $source);
  return $source;
}
			
//функция для сверки паролей
function gb_validatePassword($par, $par2) {
  $msg = array();
  if(strlen($par) < 4)
  {
    $msg[] = "Пароль должен быть не меньше 4-х символов";
  }
  //если второй переданный параметр не пустой, то сравниваем две копии пароля
  if (!empty($par2))
  {
	  if($par != $par2)
	  {
		$msg[] = "Две копии пароля не совпадают";
	  }
  }
  return $msg;
}

//проверка логина, включая на существование его в БД, если второй параметр = 1
function gb_validateLogin($par, $check_in_db) {
  $msg = array();
  if(!preg_match("/^[a-zA-Z0-9]+$/",$par))
    {
      $msg[] = "Логин может состоять только из букв английского алфавита и цифр";
    }
  if(strlen($par) < 3 or strlen($par) > 20)
    {
      $msg[] = "Логин должен быть не меньше 3-х символов и не больше 20";
    }
  //если второй переданный параметр "1", то сверяем нет ли такого пользователя в БД
  if ($check_in_db == 1)
  {
    $query = db_query("SELECT COUNT(id) as cnt FROM users WHERE login='".mysqli_real_escape_string($GLOBALS['$connect'], $par)."'");
    if($query[0]['cnt'] > 0)
    {
        $msg[] = "Пользователь с таким логином уже существует в базе данных";
    }
  }
  return $msg;
}	

//проверка сочетания имя пользователя/пароль по БД
function gb_validateUserPassw($u, $p) {  //проверка сочетания юзер/пароль на правильность в БД
  $msg = array();
  $pass = md5($p);

  $query = db_query("SELECT COUNT(id) as cnt FROM users WHERE login='".mysqli_real_escape_string($GLOBALS['$connect'], $u)."' AND passw='".$pass."'");
  if ($query[0]['cnt'] == 0) // не найден пользователь
  {
    $msg[] = "Имя пользователя или пароль неверные";
  }  
  return $msg;
}	

// сверка капчи с записанным в сессионную переменную значением
function gb_validateCaptcha ($par) {
  $msg = array();
  if($par != $_SESSION['captcha'])
  {
	$msg[] = "Неправильный код с картинки";
  }
  return $msg;
}

//проверка строковых значений ввода порльзователя
function gb_validateString ($par, $length, $descr) {
  $msg = array();
  if (strlen($par)>$length) {
    $msg[] = $descr."cлишком длинная строка";
  }
  return $msg;
}

//проверка e-mail
function gb_validateEmail ($par, $length) {
  $msg = array();
  if (strlen($par) != 0) {
	  if (!preg_match("/[0-9a-z_]+@[0-9a-z_^\.]+\.[a-z]{2,3}/i", $par)) {
		$msg[] = "Неправильный адрес электронной почты";
	  }
	  if (strlen($par)>$length) {
		$msg[] = "Слишком длинный адрес электронной почты";
	  }
	}
  return $msg;
}

//функция проверки числового ввода пользователя
function gb_validateNumber ($par, $descr) {
  $msg = array();
  if (!is_numeric($par)) {
    if (strlen($par) != 0) {
      $msg[] = $descr."нечисловое и непустое значение";
	}
  }

  return $msg;
}

//проверка текста, введенного в текстовое поле
function gb_validateTextarea ($par, $descr) {
  $msg = array();
  $par = trim($par);
  if (strlen($par) == 0) {
    $msg[] = $descr.'пустое сообщение не допускается';
  }
  if (strlen(htmlspecialchars($par, ENT_QUOTES)) == 0) {
    $msg[] = $descr.'в сообщении отсутствуют допустимые символы';
  }
  if (strlen(htmlspecialchars($par, ENT_QUOTES)) > 10000) {
    $msg[] = $descr.'разбейте сообщение на несколько частей';
  }
  return $msg;
}

//преобразование пользовательского ввода перед записью в БД:
//экранирование управляющих символов, замена спец-символов html
function gb_textToMySQL ($par) {
  if (get_magic_quotes_gpc()) {
	$res = stripslashes($par);
  }
  else {
	$res = $par;
  }
  // If using MySQL
  $res = mysqli_real_escape_string($GLOBALS['$connect'], $res);
  $res = htmlspecialchars($res, ENT_QUOTES);
  return $res;
}

//генерация уникального имени файла для аватарки
function getRandomFileName($path, $extension='')
  {
    //$extension = $extension ? '.' . $extension : '';
    $path = $path ? $path . '/' : '';
       do { //имя файла генерируется до тех пор, пока не получится уникальным
          $name = uniqid();
          $file = $path . $name . $extension;
    } while (file_exists($file));
    return $name;
  }

//функция для регистронезависимой замены подстроки на кириллице в utf8
function utf8_replace( $ptr , $replace , $str , $cr = false ) {
    if ( is_array($ptr) ) {
        foreach($ptr as $p) {
            $str = utf8_replace($p , $replace , $str , $cr);
        }
        return $str;
	}
	return preg_replace( '/'.preg_quote( $ptr , '/' ).'/su' . ($cr?'i':''), $replace , $str );
}

//функция антимат
function replaceMat($text)
  {
	$pattern = file('antimat.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	$text = utf8_replace ( $pattern , "[censored]" , $text , true);
    return $text;
  }

  
?>




