<?php
// Страница регистрации нового пользователя

session_start();
require_once("connect.php");

if($_POST['cancel'] == 'Отмена')
{
  echo "<script type=\"text/javascript\">location.href='index.php?page=$page';</script>";
}

if($_POST['submit'] == 'Зарегистрироваться')
{
    $err = array();
	
	//проверяем ФИО
	$err = $err + gb_validateString($_POST['fio'], 50, 'Ошибка в поле ФИО: ');
    //проверям логин, проверяем, не сущестует ли пользователя с таким именем
	$err = $err + gb_validateLogin($_POST['login'],1);
	//проверяем пароль
	$err = $err + gb_validatePassword($_POST['password'], $_POST['password2']);
	//проверяем откуда
	$err = $err + gb_validateString($_POST['city'], 40, 'Ошибка в поле Откуда: ');
	//проверяем должность
	$err = $err + gb_validateString($_POST['post'], 50, 'Ошибка в поле должность: ');
	//проверяем мыло
	$err = $err + gb_validateEmail($_POST['e_mail'], 40);
	//проверяем капчу
	$err = $err + gb_validateCaptcha($_POST['captcha']);
	//проверяем аватарку
	$extension = strtolower(substr(strrchr($_FILES['avatarfile']['name'], '.'), 1));
	if (!in_array($extension, $avatarValidTypes)) {
		$err[] = 'Недопустимый тип файла аватарки.';
	}
    
    # Если нет ошибок, то добавляем в БД нового пользователя
    if(count($err) == 0)
    {
        $login = $_POST['login'];
        # Убираем лишние пробелы и делаем шифрование
        $password = md5(trim($_POST['password']));
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
        mysqli_query($GLOBALS['$connect'], "INSERT INTO `users` SET `login`='".$login.
						"', `passw`='".$password.
						"', `fio`='".gb_textToMySQL($_POST['fio']).
						"', `city`='".gb_textToMySQL($_POST['city']).
						"', `post`='".gb_textToMySQL($_POST['post']).
						"', `e_mail`='".gb_textToMySQL($_POST['e_mail']).
						"', `icq`='".gb_textToMySQL($_POST['icq']).
						"', `role`='user".
						"', `avatar`='".$filename . $extension.
						"', `date_time`=now()");
		
		$_SESSION['user_id'] = mysqli_insert_id($GLOBALS['$connect']); //так можно получить значение автоинкрементного поля только что вставленной записи
		$_SESSION['fio'] = $_POST['fio'];
		$_SESSION['login'] = $_POST['login'];
		$_SESSION['e_mail'] = $_POST['e_mail'];
		$_SESSION['role'] = 'user';
		gb_log(1);
		
		//header("Location:/index.php");
		echo "<meta http-equiv='refresh' content='0; url=index.php'>";
    }
    else
    {
        print "<b>При регистрации произошли следующие ошибки:</b><br>";
        foreach($err AS $error)
        {
            print $error."<br>";
        }
    }
}
?>

<div class="register"><b>Форма регистрации в гостевой книге</b><br>(звездочкой * помечены поля обязательные для заполнения)<form enctype="multipart/form-data" action="index.php?register=1" method="POST">
ФИО (обращение)<br><input class="input" maxlength="50" name="fio" type="text" value="<?php echo $_POST['fio']?>"><br><br>
Логин*<br><input  class="input" maxlength="20" name="login" type="text" value="<?php echo $_POST['login']?>"><br><br>
Откуда<br><input  class="input" maxlength="40" name="city" type="text" value="<?php echo $_POST['city']?>"><br><br>
Должность<br><input  class="input" maxlength="50" name="post" type="text" value="<?php echo $_POST['post']?>"><br><br>
E-mail<br><input  class="input" maxlength="40" name="e_mail" type="text" value="<?php echo $_POST['e_mail']?>"><br><br>
ICQ<br><input  class="input" name="icq" maxlength="20" type="text" value="<?php echo $_POST['icq']?>"><br><br>
<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $avatarMaxSize?>">
Аватарка (макс. <?php echo $avatarMaxSize?> байт)<br><input name="avatarfile" type="file"><br><br>
Пароль*<br><input  class="input" maxlength="100" name="password" type="password"><br><br>
Пароль еще раз*<br><input class="input" maxlength="100" name="password2" type="password"><br><br>
<?php
  //здесь реализован вывод капчи с возможностью перезагрузки картинки без перезагрузки формы
  echo "<img id=\"my_captcha\" src=captcha.php onClick=\"this.src=this.src+'\?'+Math.round(Math.random())\"><br>";
  gb_log(2);
?>
<a href="javascript:void(0);" onclick="document.getElementById('my_captcha').src='captcha.php?rid='+Math.random();">Обновить код</a><br>
Введите код с картинки*<br><input class="input" maxlength=\"10\" type="text" name="captcha"><br><br>
<input name="submit" type="submit" value="Зарегистрироваться">&nbsp;&nbsp;&nbsp;
<input type="submit" name="cancel" value="Отмена"><br>
</form></div>
