<?php
// модуль обработки логинов пользователей и управления аккаунтом
//session_start();

if (!session_id()) {
  echo "Если запустить этот файл, то будет не очень красиво";
  die();
}

//require_once("connect.php");

if($_POST['cancel'] == 'Отмена')
{
  echo "<script type=\"text/javascript\">location.href='index.php?page=$page';</script>";
}

//обрабатываем попытку входа пользователя
if($_POST['submit'] == 'Войти')
{
  $login = gb_textToMySQL($_POST['login']);

  # Убираем лишние пробелы и делаем шифрование
  $password = md5(trim($_POST['password']));

  $query = db_query("SELECT `id`, `fio`, `login`, `role`, `e_mail`, `blocked`, COUNT(`id`) FROM `users` WHERE `login`='".gb_textToMySQL($_POST[login])."' AND `passw`='".$password."'");
  if (($query[0]['COUNT(`id`)'] > 0) && ($query[0]['blocked'] == 0)) // найден пользователь и он не заблокирован
  {
    //Пользователь с таким логином и паролем существует в базе данных
    $_SESSION['user_id'] = $query[0]['id'];
    $_SESSION['fio'] = $query[0]['fio'];
    $_SESSION['login'] = $query[0]['login'];
    $_SESSION['e_mail'] = $query[0]['e_mail'];
    $_SESSION['role'] = $query[0]['role'];
	unset($_GET['register']);
	
	gb_log(7);
    //как зашли, обновим дату последнего посещения юзером ГБ
    $sql = "UPDATE `users` SET `date_time` = now() WHERE `id` = '{$_SESSION['user_id']}'";
    mysqli_query($GLOBALS['$connect'], $sql);
	//устанавливаем куки
	/*if ($_POST['remember'] == 'on') {
	  setcookie('login',gb_textToMySQL($_POST['login']),mktime(0,0,0,1,25,2020));
	  setcookie('password',$password,mktime(0,0,0,1,25,2020));
	} else {
	  setcookie('login',gb_textToMySQL($_POST['login']));
	  setcookie('password',$password);
	}*/

  } else 
  {
    $_SESSION['user_id'] = -1;
    unset($_SESSION['fio']);
    unset($_SESSION['login']);
    unset($_SESSION['e_mail']);
    unset($_SESSION['role']);
    unset($_SESSION['captcha']);
	gb_log(6);
	if (($query[0]['COUNT(`id`)'] > 0) && ($query[0]['blocked'] != 0)) {
	  echo "Пользователь заблокирован. Обратитесь к администратору.<br>";
	} else {
	  echo "Неправильные имя пользователя и пароль. Поробуйте еще раз.<br>";
	}
  }
}

//echo "<br>_SESSION: ".dump($_SESSION);
//echo "<br>_POST: ".dump($_POST);
//echo "<br>_GET: ".dump($_GET);
$query = db_query("SELECT count(`id`) as cnt FROM `posts` WHERE `deleted`=0");
$posts = $query[0]['cnt'];
$query = db_query("SELECT count(`id`) as cnt FROM `comments` WHERE `deleted`=0");
$comments = $query[0]['cnt'];

//вывод приглашения, приветствия и небольшого количества статистики в левую панель
if ((!isset($_SESSION['user_id'])) or ($_SESSION['user_id'] < 0)) {
  echo "<form action=\"\" method=\"POST\">";
  echo "Сообщений в книге: ".$posts."<br>";
  echo "Комментариев: ".$comments."<br><br>";
  echo "Логин <input class=\"input_pass\" name=\"login\" type=\"text\"><br>";
  echo "Пароль <input class=\"input_pass\" name=\"password\" type=\"password\"><br><br>";
  //echo "<input name=\"remember\" type=\"checkbox\"> Запомнить меня<br><br>";
  echo "<center><input class=\"buttons\" name=\"submit\" type=\"submit\" value=\"Войти\"></form><br>";
  echo "<br>Вы можете только оставлять сообщения.<br><br>";
  echo "Зарегистрированные пользователи:<br>";
  echo "- могут оставлять комментарии<br>";
  echo "- могут редактировать свои сообщения<br>";
  echo "- избавлены от ввода captch-а<br>";
  echo "- получают уведомления на e-mail<br>";
  echo "<br><a href=index.php?register=1>Регистрация</a><br>";
} else {
  //$_SESSION['user_id'] = -1;
  $query = db_query("SELECT `avatar` FROM `users` WHERE `id`='".$_SESSION['user_id']."'");
  if ($query[0]['avatar']) {
    $avatar = $path . '/' . $query[0]['avatar'];
  } else {
    $avatar = $path . '/default.png';
  }
  echo "<img class=\"welcome\" src={$avatar}><br><br>";
  echo "Здравствуйте, ".$_SESSION['fio']."!<br>";
  echo "[".$_SESSION['login']."]<br>";
  $query = db_query("SELECT count(`id`) as cnt FROM `posts` WHERE `deleted`=0 AND `user_id`='".$_SESSION['user_id']."'");
  $your_posts = $query[0]['cnt'];
  $query = db_query("SELECT count(`id`) as cnt FROM `comments` WHERE `deleted`=0 AND `user_id`='".$_SESSION['user_id']."'");
  $your_comments = $query[0]['cnt'];
  echo "Сообщений в книге: ".$posts." (".$your_posts.")<br>";
  echo "Комментариев: ".$comments." (".$your_comments.")<br><br>";
  echo "<a href='?act=exit'>Выйти</a><br>";
  echo "<a href='?act=chpass'>Смена пароля</a><br>";
  echo "<a href='?act=edit'>Редактировать аккаунт</a><br>";
  if ($_SESSION['role'] == 'admin')
    echo "<a href=admin.php>Администраторский раздел</a><br>";
}

//обрабатываем выход пользователя
if (isset($_GET['act']) and $_GET['act'] == 'exit') {
   //перед выходом надо обновить дату последнего выхода юзера из ГБ
   $sql = "UPDATE `users` SET `date_time` = now() WHERE `id` = '{$_SESSION['user_id']}'";
   mysqli_query($GLOBALS['$connect'], $sql);
   //уничтожаем сессионые переменные 
   $_SESSION['user_id'] = -1;
   unset($_SESSION['fio']);
   unset($_SESSION['login']);
   unset($_SESSION['e_mail']);
   unset($_SESSION['role']);
   unset($_SESSION['captcha']);
   gb_log(5);
   echo "<meta http-equiv='refresh' content='0; url=index.php'>";
   //die();
}

//обрабатываем смену пароля
if (isset($_GET['act']) and ($_GET['act'] == 'chpass')) {?>
	<form action="index.php?act=changepass" method="post"><br>
	<b>- Смена пароля -</b><br><br>
	Старый пароль: <input class="input_pass" type="password" name="oldpassword"><br>
	Новый пароль: <input class="input_pass" type="password" name="newpassword"><br>
	Повторите пароль: <input class="input_pass" type="password" name="newpassword2"><br><br>
	<input class="buttons" type="submit" name="submit" value="Изменить пароль"><br>
	<input class="buttons" type="submit" name="cancel" value="Отмена"><br>
	</form>
<?php 
}
if ((isset($_GET['act']) && ($_GET['act'] == 'changepass') && $_POST['submit'] == 'Изменить пароль')) {
  //обрабатываем форму смены пароля
  $err = array();
  //проверяем пароль
  $err = $err + gb_validatePassword(trim($_POST['newpassword']), trim($_POST['newpassword2']));
  //проверка правильно ли введен стары пароль
  $err = $err + gb_validateUserPassw($_SESSION['login'], trim($_POST['oldpassword']));

  if(count($err) == 0)
  {
	//Пользователь с таким логином и паролем существует в базе данных, обновляем пароль
	$newpassword = md5(trim($_POST['newpassword']));
    if (mysqli_query($GLOBALS['$connect'], "UPDATE `users` SET `passw`='".$newpassword."' WHERE `login`='".$_SESSION['login']."'")) {
	  gb_log(4);
	  echo("Пароль успешно изменен.");
	}
  } else
  {
	print "<b>При смене пароля произошли следующие ошибки:</b><br>";
	foreach($err AS $error)
	{
		print $error."<br>";
	}
  }
}

//обрабатываем редактирование аккаунта
if (isset($_GET['act']) && (($_GET['act'] == 'edit') || ($_GET['act'] == 'editacc'))) {  //так правильнее, но не помещается в блок
//if (isset($_GET['act']) && ($_GET['act'] == 'edit')) {
  $query = db_query("SELECT `fio`, `city`, `post`, `e_mail`, `icq`, `avatar` FROM `users` WHERE `id`='".$_SESSION['user_id']."'");
  $query = $query[0];
?>
	<form enctype="multipart/form-data" action="index.php?act=editacc" method="post"><br>
	<b>- Редактирование аккаунта -</b><br><br>
	ФИО <input class="input_pass" maxlength="50" name="fio" type="text" value='<?php echo(isset($_POST['fio'])) ? $_POST['fio'] : $query['fio']?>'><br>
	Откуда <input class="input_pass" maxlength="40" name="city" type="text" value='<?php echo(isset($_POST['city'])) ? $_POST['city'] : $query['city']?>'><br>
	Должность <input class="input_pass" maxlength="50" name="post" type="text" value='<?php echo(isset($_POST['post'])) ? $_POST['post'] : $query['post']?>'><br>
	E-mail <input class="input_pass" maxlength="40" name="e_mail" type="text" value='<?php echo(isset($_POST['e_mail'])) ? $_POST['e_mail'] : $query['e_mail']?>'><br>
	ICQ <input class="input_pass" maxlength="20" name="icq" type="text" value='<?php echo(isset($_POST['icq'])) ? $_POST['icq'] : $query['icq']?>'><br>
	<input type="hidden" name="MAX_FILE_SIZE" value='<?php echo($avatarMaxSize)?>'>
	Аватарка (макс. <?php echo($avatarMaxSize)?> б)<br><input class="input_pass" name="avatarfile" type="file"><br><br>
	<input class="buttons" type="submit" name="submit" value="Сохранить изменения"><br>
	<input class="buttons" type="submit" name="cancel" value="Отмена"><br>
	</form>
<?php 
}
if (isset($_GET['act']) && ($_GET['act'] == 'editacc') && ($_POST['submit'] == 'Сохранить изменения')) {
  //обрабатываем форму редактирования аккаунта
  $err = array();
  //проверяем ФИО откуда должность mail icq
  $err = $err + gb_validateString($_POST['fio'], 50, 'Ошибка в поле ФИО: ') 
			  + gb_validateString($_POST['city'], 40, 'Ошибка в поле Откуда: ') 
			  + gb_validateString($_POST['post'], 50, 'Ошибка в поле должность: ')
			  + gb_validateEmail($_POST['e_mail'], 40) 
			  + gb_validateNumber($_POST['icq'], 'Ошибка в поле ICQ: ');
  //проверяем аватарку
  $extension = strtolower(substr(strrchr($_FILES['avatarfile']['name'], '.'), 1));
  if (!in_array($extension, $avatarValidTypes)) {
	$err[] = 'Недопустимый тип файла аватарки.';
  }
  if(count($err) == 0)
  {
	# Загружаем файл аватарки
	$extension = '.' . strtolower(substr(strrchr($_FILES['avatarfile']['name'], '.'), 1));
	if ($_FILES['avatarfile']['name']) {
	  $filename = getRandomFileName($path, $extension);
	  $target = $path . '/' . $filename . $extension;
      if (move_uploaded_file($_FILES['avatarfile']['tmp_name'], $target)) {
	   //файл успешно загружен
	  } else {
	    $filename = '';
	    $extension = '';
	  }
	} else {
	  $filename = '';
	  $extension = '';
	}
  // если нет ошибок, обновляем данные
    if ($query['avatar']) {
      $old_avatar = $query['avatar'];
	} else {
      $old_avatar = '';
	}
    if (mysqli_query($GLOBALS['$connect'], "UPDATE `users` SET `fio`='".gb_textToMySQL($_POST['fio'])."',
		`city`='".gb_textToMySQL($_POST['city'])."',
		`post`='".gb_textToMySQL($_POST['post'])."',
		`e_mail`='".gb_textToMySQL($_POST['e_mail'])."',
		`avatar`='".$filename . $extension."',
		`icq`='".gb_textToMySQL($_POST['icq'])."'
		WHERE `login`='{$_SESSION['login']}'")) 
	{
	  $_SESSION['fio'] = gb_textToMySQL($_POST['fio']);
	  $_SESSION['e_mail'] = gb_textToMySQL($_POST['e_mail']);
	  //тут надо бы удалить старую картинку
	  if ($old_avatar) {
	    unlink($path . '/' . $old_avatar);
	  }
	  gb_log(3);
	  echo "<script type=\"text/javascript\">location.href='index.php?page=$page';</script>";
	  echo("Данные пользователя успешно изменены.");
	}
  } else
  {
	print "<br><b>При изменении данных произошли следующие ошибки:</b><br>";
	foreach($err AS $error)
	{
		print $error."<br>";
	}
  }
}

?>

