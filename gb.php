<?php
//основной модуль, отвечающий за вывод и управление записями гостевой книги
 if (!session_id()) {
   echo "Если запустить этот файл, то будет не очень красиво";
   die();
 }

 require_once("connect.php");

 //echo "<br>_GET: ".dump($_GET);
 //echo "<br>_POST: ".dump($_POST);
 
 //обработка пометки записи на удаление (доступно только админу)
  if (($_GET['act'] == 'delete') && ($_SESSION['role'] == 'admin'))
  {
	if (!empty($_GET['cid'])) //если передан id комментария, то работаем только с ним иначе со всем сообщением
	{
      $sql = "UPDATE `comments` SET `deleted` = 1 WHERE `id` = {$_GET['cid']}"; 
      mysqli_query($GLOBALS['$connect'], $sql);
      gb_log(21);
	} 
	else
	{
		$sql = "UPDATE `posts` SET `deleted` = 1 WHERE `id` = {$_GET['mid']}"; 
		mysqli_query($GLOBALS['$connect'], $sql);
		$sql = "UPDATE `comments` SET `deleted` = 1 WHERE `post_id` = {$_GET['mid']}"; 
		mysqli_query($GLOBALS['$connect'], $sql);
 	    gb_log(20);
	}
  }

 //обработка окончательного удаления (доступно только админу)
  if (($_GET['act'] == 'erase') && ($_SESSION['role'] == 'admin'))
  {
	if (!empty($_GET['cid']))
	{
      $sql = "DELETE FROM `comments` WHERE `id` = {$_GET['cid']}"; 
      mysqli_query($GLOBALS['$connect'], $sql);
	  gb_log(19);
	} 
	else
	{
		$sql = "DELETE FROM `posts` WHERE `id` = {$_GET['mid']}"; 
		mysqli_query($GLOBALS['$connect'], $sql);
		$sql = "DELETE FROM `comments` WHERE `post_id` = {$_GET['mid']}"; 
		mysqli_query($GLOBALS['$connect'], $sql);
		gb_log(18);
	}
  }

 //обработка восстановления записи (доступно только админу)
  if (($_GET['act'] == 'restore') && ($_SESSION['role'] == 'admin'))
  {
	if (!empty($_GET['cid']))
	{
      $sql = "UPDATE `comments` SET `deleted` = 0 WHERE `id` = {$_GET['cid']}"; 
      mysqli_query($GLOBALS['$connect'], $sql);
	  gb_log(17);
	}
	else
	{
		$sql = "UPDATE `posts` SET `deleted` = 0 WHERE `id` = {$_GET['mid']}"; 
		mysqli_query($GLOBALS['$connect'], $sql);
		$sql = "UPDATE `comments` SET `deleted` = 0 WHERE `post_id` = {$_GET['mid']}"; 
		mysqli_query($GLOBALS['$connect'], $sql);
		gb_log(16);
	}
  }

 //добавление комментария
  if ((($_GET['act'] == 'comment') || ($_POST['submit'] == 'Оставить комментарий')) && ($_SESSION['user_id'] != -1)) //добавлять комментарии можно только зарег. юзерам и админу
  {
	if ((!empty($_GET['mid'])) && (ctype_digit($_GET['mid']))) //проверяем "цифровость" переданных параметров, далее подобно этому
	{
		$sql = "SELECT `id`, `user_id`, `user_name`, `e_mail`, `message`, `date_time` FROM `posts` WHERE `id` = {$_GET['mid']}"; 
		if (is_array(db_query($sql))) {
			$res = db_query($sql);
			$message = $res[0];
			//отдельно показываем комментируемое сообщение
			echo "<br>Комментировать сообщение<br> ";
			echo "<table class=\"post\"><tr class=\"posthead\">"; 
			echo "<td class=\"messagehead\" colspan=\"2\">{$message['user_name']} ({$message['date_time']})</td>";
			echo "<td class=\"id\" width=\"50px\">#{$message['id']}</td>";
			echo "</tr><tr>";
			echo "<td class=\"message\" colspan=\"3\">".nl2br(bbCodesToHtml($message['message']))."</td>";
			echo "</tr></table>";
				
			echo "<form action=\"index.php?page=$page\" method=\"POST\" name=\"comment\"><br>";
			echo "<b>АВТОР</b><br><input class=\"input_form\" name=\"fio\" type=\"text\" value=\"{$_SESSION['fio']}\"><br>";
			echo "<input name=\"mid\" type=\"hidden\" value=\"{$res[0]['id']}\">"; //скрыто передаем id сообщения
			echo "<input name=\"user_id\" type=\"hidden\" value=\"{$_SESSION['user_id']}\">"; //скрыто передаем user_id пользователя, который отправил комментарий
			echo "<input name=\"e_mail\" type=\"hidden\" value=\"{$res[0]['e_mail']}\">"; //скрыто передаем e_mail пользователя сообщения
			echo "<br><b>КОММЕНТАРИЙ</b>";
			echo "<br>В комментариях допускаются bbCodes:<br>";
			echo "<input class=\"btnbbcode\" type=\"button\" value=\"B\" onClick=\"replaceSelectedText(document.comment.message,'b');\">&nbsp;<b>[b][/b]</b> - жирный;<br>";
			echo "<input class=\"btnbbcode\" type=\"button\" value=\"I\" onClick=\"replaceSelectedText(document.comment.message,'i');\">&nbsp;<b>[i][/i]</b> - курсив;<br>";
			echo "<input class=\"btnbbcode\" type=\"button\" value=\"U\" onClick=\"replaceSelectedText(document.comment.message,'u');\">&nbsp;<b>[u][/u]</b> - подчеркнутый;<br>";
			echo "<input class=\"btnbbcode\" type=\"button\" value=\"URL\" onClick=\"replaceSelectedText(document.comment.message,'url');\">&nbsp;<b>[url][/url]</b> - гиперссылка;<br>";
			echo "<input class=\"btnbbcode\" type=\"button\" value=\"quote\" onClick=\"replaceSelectedText(document.comment.message,'quote');\">&nbsp;<b>[quote][/quote]</b> - цитата;<br>";
			echo "<input class=\"btnbbcode\" type=\"button\" value=\"code\" onClick=\"replaceSelectedText(document.comment.message,'code');\">&nbsp;<b>[code][/code]</b> - отформатированный текст (моноширинный шрифт).<br>";
			echo "<textarea class=\"input_form\" rows=\"10\" cols=\"100\" wrap=\"soft\" name=\"message\"></textarea><br>";
			echo "<input class=\"buttons\" name=\"submit\" type=\"submit\" value=\"Оставить комментарий\">&nbsp;&nbsp;&nbsp;";
			echo "<input class=\"buttons\" type=\"submit\" name=\"cancel\" value=\"Отмена\"><br><hr>";

			echo "</form>";
			gb_log(15);
		}
	}
	if (($_POST['submit'] == 'Оставить комментарий') && ($_POST['user_id'] == $_SESSION['user_id'])) //если пришел POST от формы редактирования, то проверяем пользователя и пишем в БД
	{
	  //обрабатываем форму комментирования
	  $err = array();
	  //проверяем ФИО сообщение
	  $err = $err + gb_validateString($_POST['fio'], 50, 'Ошибка в поле ФИО: ');
	  $err = $err + gb_validateTextarea($_POST['message'], 'Ошибка в поле сообщения: ');

	  if(count($err) == 0)
	  {
		$sql = "INSERT INTO `comments` SET 
			`post_id` = '{$_POST['mid']}', 
			`user_id` = '{$_POST['user_id']}',
			`user_name` = '".gb_textToMySQL($_POST['fio'])."', 
			`deleted` = 0, 
			`date_time` = now(), 
			`message` = '".gb_textToMySQL($_POST['message'])."'"; 
		if (mysqli_query($GLOBALS['$connect'], $sql)) {
		  //пошлем письмецо пользователю создавшему сообщение, что на его сообщение ответили
			$to      = "'".$_POST['e_mail']."'";
			$subject = 'Ваше сообщение прокомментировано';
			$message = "'".gb_textToMySQL(wordwrap($_POST['message'],70))."'";
			$headers = 'From: '.$admin_mail . "\r\n" .
			'Reply-To: '.$admin_mail . "\r\n" .
			'X-Mailer: PHP/' . phpversion();

			mail($to, $subject, $message, $headers);
			
			gb_log(14);

			//echo "<script type=\"text/javascript\">location.href='index.php?page=".$page."#".$_POST['mid']."';</script>";
			echo "<meta http-equiv='refresh' content='0; url=index.php?page=".$page."#".$_POST['mid']."'>";
			//header("Location: index.php?page=$page");
		    echo "Комментарий #{$_GET['cid']} успешно добавлен."; //НЕ ВЫВОДИТСЯ!
		}
	  } else {
		  print "<br><b>При комментировании произошли следующие ошибки:</b><br>";
	      foreach($err AS $error)
		{
		  print $error."<br>";
		}
	  }
	}
  }
    
 //редактирование сообщения или комментария
  if ((($_GET['act'] == 'modify') || ($_POST['submit'] == 'Сохранить сообщение') || ($_POST['submit'] == 'Сохранить комментарий')) && ($_SESSION['user_id'] != -1)) //анонимам недоступно
  {  //дальше редактирование разрешаем хозяину сообщения или админу
	if ($_GET['act'] == 'modify')
	{
		if (empty($_GET['cid']))
		{ //тут редактируем сообщение
		  $sql = "SELECT `id`, `user_id`, `user_name`, `e_mail`, `message` FROM `posts` WHERE `id` = {$_GET['mid']}"; 
		  if (ctype_digit($_GET['mid'])) {
			//формируем форму для редактирования
		    $res = db_query($sql);
		    if (($_SESSION['role'] == 'admin') or ($_SESSION['user_id'] == $res[0]['user_id'])) {
				echo "<br>Редактировать СООБЩЕНИЕ<br> ";
				echo "<form action=\"index.php?page=$page\" method=\"POST\" name=\"editpost\">";
				echo "<b>АВТОР</b><br><input class=\"input_form\" name=\"fio\" type=\"text\" value=\"{$res[0]['user_name']}\"><br>";
				echo "E-mail<br><input class=\"input_form\" name=\"e_mail\" type=\"text\" value=\"{$res[0]['e_mail']}\"><br>";
				echo "<input name=\"mid\" type=\"hidden\" value=\"{$res[0]['id']}\">"; //скрыто передаем id сообщения
				echo "<input name=\"editor_id\" type=\"hidden\" value=\"{$_SESSION['user_id']}\">"; //скрыто передаем user_id пользователя, который отредактировал сообщение 
				echo "<br><b>СООБЩЕНИЕ</b>";
				echo "<br>В сообщениях допускаются bbCodes:<br>";
				echo "<input class=\"btnbbcode\" type=\"button\" value=\"B\" onClick=\"replaceSelectedText(document.editpost.message,'b');\">&nbsp;<b>[b][/b]</b> - жирный;<br>";
				echo "<input class=\"btnbbcode\" type=\"button\" value=\"I\" onClick=\"replaceSelectedText(document.editpost.message,'i');\">&nbsp;<b>[i][/i]</b> - курсив;<br>";
				echo "<input class=\"btnbbcode\" type=\"button\" value=\"U\" onClick=\"replaceSelectedText(document.editpost.message,'u');\">&nbsp;<b>[u][/u]</b> - подчеркнутый;<br>";
				echo "<input class=\"btnbbcode\" type=\"button\" value=\"URL\" onClick=\"replaceSelectedText(document.editpost.message,'url');\">&nbsp;<b>[url][/url]</b> - гиперссылка;<br>";
				echo "<input class=\"btnbbcode\" type=\"button\" value=\"quote\" onClick=\"replaceSelectedText(document.editpost.message,'quote');\">&nbsp;<b>[quote][/quote]</b> - цитата;<br>";
				echo "<input class=\"btnbbcode\" type=\"button\" value=\"code\" onClick=\"replaceSelectedText(document.editpost.message,'code');\">&nbsp;<b>[code][/code]</b> - отформатированный текст (моноширинный шрифт).<br>";
				echo "<textarea class=\"input_form\" rows=\"10\" cols=\"100\" wrap=\"soft\" name=\"message\">{$res[0]['message']}</textarea><br>";
				echo "<input class=\"buttons\" name=\"submit\" type=\"submit\" value=\"Сохранить сообщение\">&nbsp;&nbsp;&nbsp;";
				echo "<input class=\"buttons\" type=\"submit\" name=\"cancel\" value=\"Отмена\"><br><hr>";
				echo "</form>";
				gb_log(13);
		    }
		  }
		}
		else
		{ //тут редактируем комментарий, надо показать и сообщение на которое дается комментарий
		    if ((ctype_digit($_GET['mid'])) && (ctype_digit($_GET['cid']))) { //параметры id записей должны быть числовыми
			  $sql = "SELECT `id`, `post_id`, `user_id`, `user_name`, `message` FROM `comments` WHERE `id` = {$_GET['cid']}"; 
			  $sql2 = "SELECT `id`, `user_name`, `message`, `date_time` FROM `posts` WHERE `id` = {$_GET['mid']}"; 
			  if ((is_array(db_query($sql))) && (is_array(db_query($sql2)))) { // запросы должны вернуть записи
				  $res = db_query($sql);
				  $message = db_query($sql2);
				  $message = $message[0];
				  if (($_SESSION['role'] == 'admin') or ($_SESSION['user_id'] == $res[0]['user_id'])) {
					echo "Сообщение<br> ";
					echo "<table class=\"post\"><tr class=\"posthead\">"; 
					echo "<td class=\"messagehead\" colspan=\"2\">{$message['user_name']} ({$message['date_time']})</td>";
					echo "<td class=\"id\" width=\"50px\">#{$message['id']}</td>";
					echo "</tr><tr>";
					echo "<td class=\"message\" colspan=\"3\">".nl2br(bbCodesToHtml($message['message']))."</td>";
					echo "</tr></table>";

					echo "<br>Редактировать КОММЕНТАРИЙ<br> ";
					echo "<form action=\"index.php?page=$page\" method=\"POST\" name=\"editcomment\">";
					echo "<b>АВТОР</b><br><input class=\"input_form\" name=\"fio\" type=\"text\" value=\"{$res[0]['user_name']}\"><br>";
					echo "<input name=\"cid\" type=\"hidden\" value=\"{$res[0]['id']}\">"; //скрыто передаем id комментария
					echo "<input name=\"mid\" type=\"hidden\" value=\"{$message['id']}\">"; //скрыто передаем id сообщения
					echo "<input name=\"editor_id\" type=\"hidden\" value=\"{$_SESSION['user_id']}\">"; //скрыто передаем user_id пользователя, который отредактировал комментарий
					echo "<br><b>КОММЕНТАРИЙ</b>";
					echo "<br>В комментариях допускаются bbCodes:<br>";
					echo "<input class=\"btnbbcode\" type=\"button\" value=\"B\" onClick=\"replaceSelectedText(document.editcomment.message,'b');\">&nbsp;<b>[b][/b]</b> - жирный;<br>";
					echo "<input class=\"btnbbcode\" type=\"button\" value=\"I\" onClick=\"replaceSelectedText(document.editcomment.message,'i');\">&nbsp;<b>[i][/i]</b> - курсив;<br>";
					echo "<input class=\"btnbbcode\" type=\"button\" value=\"U\" onClick=\"replaceSelectedText(document.editcomment.message,'u');\">&nbsp;<b>[u][/u]</b> - подчеркнутый;<br>";
					echo "<input class=\"btnbbcode\" type=\"button\" value=\"URL\" onClick=\"replaceSelectedText(document.editcomment.message,'url');\">&nbsp;<b>[url][/url]</b> - гиперссылка;<br>";
					echo "<input class=\"btnbbcode\" type=\"button\" value=\"quote\" onClick=\"replaceSelectedText(document.editcomment.message,'quote');\">&nbsp;<b>[quote][/quote]</b> - цитата;<br>";
					echo "<input class=\"btnbbcode\" type=\"button\" value=\"code\" onClick=\"replaceSelectedText(document.editcomment.message,'code');\">&nbsp;<b>[code][/code]</b> - отформатированный текст (моноширинный шрифт).<br>";
					echo "<textarea class=\"input_form\" rows=\"10\" cols=\"100\" wrap=\"soft\" name=\"message\">{$res[0]['message']}</textarea><br>";
					echo "<input class=\"buttons200\" width=\"200\" name=\"submit\" type=\"submit\" value=\"Сохранить комментарий\">&nbsp;&nbsp;&nbsp;";
					echo "<input class=\"buttons\" type=\"submit\" name=\"cancel\" value=\"Отмена\"><br><hr>";

					echo "</form>";
					gb_log(12);
				  }
				}
			}
		}
	}
	//если пришел POST от формы редактирования сообщения и пользователь проверен, то пишем в БД
	if (($_POST['submit'] == 'Сохранить сообщение') && ($_POST['editor_id'] == $_SESSION['user_id'])) 
	{
	  $err = array();
	  //проверяем ФИО mail сообщение
	  $err = $err + gb_validateString($_POST['fio'], 50, 'Ошибка в поле ФИО: ');
	  $err = $err + gb_validateEmail($_POST['e_mail'], 40);
	  $err = $err + gb_validateTextarea($_POST['message'], 'Ошибка в поле сообщения: ');
	  if(count($err) == 0)
	  {
		$sql = "UPDATE `posts` SET 
			`user_name` = '".gb_textToMySQL($_POST['fio'])."', 
			`e_mail` = '".gb_textToMySQL($_POST['e_mail'])."', 
			`message` = '".gb_textToMySQL($_POST['message'])."' 
			WHERE `id` = {$_POST['mid']}"; 
		//строчка, если надо добавлять автора правки	
		//`message` = '".gb_textToMySQL($_POST['message']).'<p class="edited">Отредактировано '.$_SESSION['fio'].' '.date("Y-m-d").' в '.date("H:i:s").'</p>'."' 
		if (mysqli_query($GLOBALS['$connect'], $sql)) {
		  gb_log(11);
		  //echo "<script type=\"text/javascript\">location.href='index.php?page=$page';</script>";
		  echo "<meta http-equiv='refresh' content='0; url=index.php?page=".$page."#".$_POST['mid']."'>";
		  echo "Сообщение #{$_POST['mid']} успешно сохранено."; //НЕ ВЫВОДИТСЯ!
		}
	  } else {
		  print "<br><b>При редактировании сообщения произошли следующие ошибки:</b><br>";
	      foreach($err AS $error)
		{
		  print $error."<br>";
		}
		//die();
	  }
	}
	//если пришел POST от формы редактирования комментария, и пользователь проверен , пишем в БД
	if (($_POST['submit'] == 'Сохранить комментарий') && ($_POST['editor_id'] == $_SESSION['user_id'])) 
	{
	  //проверяем ФИО комментарий
	  $err = array();
	  $err = $err + gb_validateString($_POST['fio'], 50, 'Ошибка в поле ФИО: ');
	  $err = $err + gb_validateTextarea($_POST['message'], 'Ошибка в поле комментария: ');

	  if(count($err) == 0)
	  {
		$sql = "UPDATE `comments` SET 
			`user_name` = '".gb_textToMySQL($_POST['fio'])."', 
			`message` = '".gb_textToMySQL($_POST['message'])."' 
			WHERE `id` = {$_POST['cid']}"; 
		if (mysqli_query($GLOBALS['$connect'], $sql)) {
		  gb_log(10);
		  //echo "<script type=\"text/javascript\">location.href='index.php?page=$page';</script>";
		  echo "<meta http-equiv='refresh' content='0; url=index.php?page=".$page."#".$_POST['mid']."'>";
		  echo "Комментарий #{$_POST['cid']} успешно сохранен."; //НЕ ВЫВОДИТСЯ!
		}
	  } else {
		  print "<br><b>При редактировании комментария произошли следующие ошибки:</b><br>";
	      foreach($err AS $error)
		{
		  print $error."<br>";
		}
	  }
	}

  }
  
 if ($_POST['submit'] == 'Отправить')
 {
   $err = array();
   //капчу проверяем только у незарегистрированных пользователей
   if ($_SESSION['user_id'] == -1) {
     $err = $err + gb_validateCaptcha($_POST['captcha']);
   }
   //проверяем ФИО mail сообщение
   $err = $err + gb_validateString($_POST['fio'], 50, 'Ошибка в поле ФИО: ');
   $err = $err + gb_validateEmail($_POST['e_mail'], 40);
   $err = $err + gb_validateTextarea($_POST['newmessage'], 'Ошибка в поле сообщения: ');
   
   if(count($err) == 0)
   {
	   mysqli_query($GLOBALS['$connect'], "INSERT INTO `posts` SET `user_id`='".$_SESSION['user_id'].
							"', `user_name`='".gb_textToMySQL($_POST['fio']).
							"', `e_mail`='".gb_textToMySQL($_POST['e_mail']).
							"', `message`='".gb_textToMySQL($_POST['newmessage']).
							"', `date_time`=now()");
	   mysqli_query($GLOBALS['$connect'], $sql);
	   gb_log(9);
	   //после добавления записи перекидываем на первую страницу
	   echo "<meta http-equiv='refresh' content='0; url=index.php?page=1'>";
	   //echo "<meta http-equiv='refresh' content='0; url=index.php?page=$page'>";
	   //echo "<script type=\"text/javascript\">location.href='index.php?page=$page';</script>";
	} else 
	{
		print "<b>При отправке сообщения произошли следующие ошибки:</b><br>";
        foreach($err AS $error)
        {
            print $error."<br>";
        }
	}
   //header("Location:/index.php");
 }

 // отбор записей гостевухи
 //покажем админу и записи помеченные на удаление
 if ($_SESSION['role'] != 'admin') { 
   $wheresql = "`deleted`=0";
 } 	else {
   $wheresql = 1;
 }
 //готовимся к разбиению на страницы
 $limit='';
 if ($_GET['page'] > 0)
	$page = $_GET['page'];

 if ($page)
 {
   $start_limit = ($page-1)*$page_size;
   $limit = "LIMIT $start_limit, $page_size";
 }

 //получим количество записей
 $sql = "SELECT count(`id`) as cnt FROM `posts` ".
   "WHERE $wheresql ".
   "ORDER BY `date_time` desc";
 $total = db_query($sql);
 $total = $total[0]['cnt'];
 $pages = ceil($total/$page_size);
 //создадим строчку навигации по страницам
 $pagenavi = 'Страницы: ';
 for ($i = 1; $i <= $pages; $i++) 
 {
    if($i !=$page)
	{
	  $pagenavi = $pagenavi . "&nbsp;<a href=\"index.php?page=$i\">".$i."</a>&nbsp;";
	} else
	{
	  $pagenavi = $pagenavi . "&nbsp;".$i."&nbsp;";
	}
 }
   
 $sql = "SELECT `posts`.`id`, `posts`.`user_id`, `posts`.`user_name`, `posts`.`e_mail`, `posts`.`date_time`, `posts`.`message`, `posts`.`deleted`, `users`.`role`, `users`.`avatar` FROM `posts` LEFT JOIN `users` ON `posts`.`user_id`=`users`.`id` ".
   "WHERE $wheresql ".
   "ORDER BY `posts`.`date_time` desc $limit";
 $res = db_query($sql);  

 //вывод записей гостевой в таблицу
 if (is_array($res))
 {   
	 echo "<br>$pagenavi<br><br>";
	 foreach ($res as $message)
	 {
		//покажем иконки в зависимости от прав пользователя
		$comment = "<a href=\"index.php?page=$page&act=comment&mid={$message['id']}\"><img src=\"images/answer.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Комментировать\" title=\"Комментировать\"></a>";
		$edit = "<a href=\"index.php?page=$page&act=modify&mid={$message['id']}\"><img src=\"images/edit.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Редактировать\" title=\"Редактировать\"></a>";
		//удаленные сообщения у нас будут иметь другой стиль шапки
		if ($message['deleted'] == 0) 
		{
		  $delete = "<a href=\"index.php?page=$page&act=delete&mid={$message['id']}\"><img src=\"images/del.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Удалить сообщение\" title=\"Удалить сообщение\"></a>";
		  $restore = '';
		  $class = 'posthead';
		}
		else 
		{
		  $delete = "<a href=\"index.php?page=$page&act=erase&mid={$message['id']}\"><img src=\"images/del_fin.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Окончательно удалить сообщение\" title=\"Окончательно удалить сообщение\"></a>";
		  $restore = "<a href=\"index.php?page=$page&act=restore&mid={$message['id']}\"><img src=\"images/restore.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Восстановить сообщение\" title=\"Восстановить сообщение\"></a>";
		  $class = 'deleted_posthead';
		}
		$icons = 'вам недоступно управление записями гостевой книги';
		if ($message['avatar']) {
		  $avatar = $path . '/' . $message['avatar'];
		} else {
		  $avatar = $path . '/default.png';
		}
		echo "<div align=\"center\">";
		//проякорим наши сообщения
		echo "<a name=\"{$message['id']}\"></a>";
		echo "<table class=\"post\"><tr class=\"{$class}\">"; 
		//вставим ссылку на адрес е-майла, если сообщение оставил аноним
		if (($message['user_id'] == -1)) {
		  if ($message['e_mail']) {
		    echo "<td class=\"messagehead\" colspan=\"2\"><img class=\"avatar\" src={$avatar}><a href=\"mailto:{$message['e_mail']}\">{$message['user_name']}</a> <i>(анонимно)</i> ({$message['date_time']})</td>";
		  } else {
		    echo "<td class=\"messagehead\" colspan=\"2\"><img class=\"avatar\" src={$avatar}>{$message['user_name']} <i>(анонимно)</i> ({$message['date_time']})</td>";
		  }
		} else {
			if ($message['role'] == 'admin') {
		      echo "<td class=\"messagehead\" colspan=\"2\"><img class=\"avatar\" src={$avatar}>{$message['user_name']} <i>(АДМИН)</i> ({$message['date_time']})</td>";
			} else {
			  echo "<td class=\"messagehead\" colspan=\"2\"><img class=\"avatar\" src={$avatar}>{$message['user_name']} ({$message['date_time']})</td>";
			}
		  }
		  
		echo "<td class=\"id\" width=\"50px\">#{$message['id']}</td>";
		echo "</tr><tr>";
		echo "<td class=\"message\" colspan=\"3\">".nl2br(bbCodesToHtml(replaceMat($message['message'])))."</td>";
		echo "</tr><tr><td colspan=\"3\">";
		if ($_SESSION['role'] == 'admin') 
		   $icons = $comment.'&nbsp;'.$edit.'&nbsp;'.$restore.'&nbsp;'.$delete;
		if (($_SESSION['role'] == 'user') && ($_SESSION['user_id'] > 0)) 
		   $icons = $comment;
		if (($_SESSION['role'] == 'user') && ($_SESSION['user_id'] == $message['user_id'])) 
		   $icons = $comment.'&nbsp;'.$edit;

		
		echo "<div class=\"icons\">$icons&nbsp;</div>";  //тут будут ссылки на управление записями в зависимости от прав
		echo "</td></tr>";
		$sql = "SELECT `comments`.`id`, `comments`.`post_id`, `comments`.`user_id`, `comments`.`user_name`, `comments`.`date_time`, `comments`.`message`, `comments`.`deleted`, `users`.`role`, `users`.`avatar` FROM
			  `comments` LEFT JOIN `users` ON `comments`.`user_id`=`users`.`id` WHERE $wheresql AND `comments`.`post_id`='".$message['id']."' ORDER BY `comments`.`date_time`";
		$resc = db_query($sql);
		if (is_array($resc))	{ //строку с комментариями вставляем только если они были
			echo "<tr><td width=\"5%\">&nbsp;</td>";
			echo "<td colspan=\"2\">";
			foreach ($resc as $comment) 
			{
				//добавим иконки
				//админу можно удалять/редактировать комментарий
				$icons = 'вам недоступно управление комментариями в гостевой книге';
				$edit = "<a href=\"index.php?page=$page&act=modify&mid={$message['id']}&cid={$comment['id']}\"><img src=\"images/edit.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Редактировать\" title=\"Редактировать\"></a>";
				if ($comment['deleted'] == 0) 
				{
				  $delete = "<a href=\"index.php?page=$page&act=delete&mid={$message['id']}&cid={$comment['id']}\"><img src=\"images/del.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Удалить комментарий\" title=\"Удалить комментарий\"></a>";
				  $restore = '';
				  $class = 'commenthead';
				} 
				else 
				{
				  $delete = "<a href=\"index.php?page=$page&act=erase&mid={$message['id']}&cid={$comment['id']}\"><img src=\"images/del_fin.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Окончательно удалить комментарий\" title=\"Окончательно удалить комментарий\"></a>";
				  $restore = "<a href=\"index.php?page=$page&act=restore&mid={$message['id']}&cid={$comment['id']}\"><img src=\"images/restore.png\" border=\"0\" width=\"16\" height=\"16\" alt=\"Восстановить комментарий\" title=\"Восстановить комментарий\"></a>";
				  $class = 'deleted_commenthead';
				}
				if ($_SESSION['role'] == 'admin')
				{
				  $icons = $edit.'&nbsp;'.$restore.'&nbsp;'.$delete;
				}
				//юзеру можно только редактировать свои комментарии  
				if (($_SESSION['user_id'] == $comment['user_id']) && ($_SESSION['role'] != 'admin'))
				{
				  $icons = $edit;			
				}
				if ($comment['avatar']) {
				  $avatar = $path . '/' . $comment['avatar'];
				} else {
				  $avatar = $path . '/default.png';
				}
				//тут начались комментарии к сообщению
				echo "<table class=\"comment\"><tr class=\"{$class}\">"; 
				if ($comment['role'] == 'admin') {
				  echo "<td class=\"commenthead\" width=\"93%\"><img class=\"avatar\" src={$avatar}>{$comment['user_name']} <i>(АДМИН)</i> ({$comment['date_time']})</td>";
				} else {
				  echo "<td class=\"commenthead\" width=\"93%\"><img class=\"avatar\" src={$avatar}>{$comment['user_name']} ({$comment['date_time']})</td>";
				}
				echo "<td class=\"id\" width=\"50px\">@{$comment['id']}</td>";
				echo "</tr><tr>"; 
				echo "<td class=\"comment\" colspan=\"2\">".nl2br(bbCodesToHtml(replaceMat($comment['message'])))."</td>";
				echo "</tr><tr><td colspan=\"2\">";
				echo "<div class=\"icons\">$icons&nbsp;</div>";
				echo "</td></tr></table>";
				//кончилась таблица комментариев
			}
			echo "</td>";
			echo "</tr>";
		}
		echo "</table><br></div>";
	}
	echo "<br>$pagenavi<br><br>";
} else
{
//пустой запрос
}

?> 

<br>ДОБАВИТЬ СООБЩЕНИЕ<br><br>
<form action="" method="POST" name="sendmessage">
<b>ОТ КОГО</b><br><input class="input_form" maxlength="50" name="fio" type="text" value='<?php echo(isset($_SESSION['fio'])) ? $_SESSION['fio'] : $_POST['fio']?>'><br>
<b>E-mail</b><br><input class="input_form" maxlength="40" name="e_mail" type="text" value='<?php echo(isset($_SESSION['e_mail'])) ? $_SESSION['e_mail'] : $_POST['e_mail']?>'><br><br>
<b>СООБЩЕНИЕ</b><br>
<b>В сообщениях допускаются bbCodes</b> <i>(выделите текст и нажмите соответствующую кнопку)</i>:<br>
<input class="btnbbcode" type="button" value="B" onClick="replaceSelectedText(document.sendmessage.newmessage,'b');">&nbsp;<b>[b][/b]</b> - жирный;<br>
<input class="btnbbcode" type="button" value="I" onClick="replaceSelectedText(document.sendmessage.newmessage,'i');">&nbsp;<b>[i][/i]</b> - курсив;<br>
<input class="btnbbcode" type="button" value="U" onClick="replaceSelectedText(document.sendmessage.newmessage,'u');">&nbsp;<b>[u][/u]</b> - подчеркнутый;<br>
<input class="btnbbcode" type="button" value="URL" onClick="replaceSelectedText(document.sendmessage.newmessage,'url');">&nbsp;<b>[url][/url]</b> - гиперссылка;<br>
<input class="btnbbcode" type="button" value="quote" onClick="replaceSelectedText(document.sendmessage.newmessage,'quote');">&nbsp;<b>[quote][/quote]</b> - цитата;<br>
<input class="btnbbcode" type="button" value="code" onClick="replaceSelectedText(document.sendmessage.newmessage,'code');">&nbsp;<b>[code][/code]</b> - отформатированный текст (моноширинный шрифт).<br>
<textarea class="input_form" rows="10" cols="100" wrap="soft" name="newmessage"><?php echo($_POST['newmessage'])?></textarea><br>
<?php
  //здесь реализован вывод капчи с возможностью перезагрузки картинки без перезагрузки формы
  //капчу показываем только незарегистрированным юзерам
  if ((!isset($_SESSION['user_id'])) or ($_SESSION['user_id'] < 0))
  {
	  echo "<img id=\"my_captcha\" src=captcha.php onClick=\"this.src=this.src+'\?'+Math.round(Math.random())\"><br>";
	  echo "<a href=\"javascript:void(0);\" onclick=\"document.getElementById('my_captcha').src='captcha.php?rid='+Math.random();\">Обновить код</a><br>";
	  echo "Введите код с картинки: <br><input class=\"input_form\" maxlength=\"10\" type=\"text\" name=\"captcha\"><br><br>";
  }
?>
<input class="buttons" name="submit" type="submit" value="Отправить">
</form>
