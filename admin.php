<?php
// Страница регистрации нового пользователя

session_start();

require_once("connect.php");

if ((!session_id()) || ($_SESSION['role'] != 'admin')) {
  echo "Вы не имеете прав на доступ к этой странице!";
  die();
}

if($_POST['cancel'] == 'Выйти из раздела')
{
  echo "<script type=\"text/javascript\">location.href='index.php';</script>";
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="Pragma" content="no-cache" />
  <title>Администраторский раздел гостевой книги</title>
  <style type="text/css">
		.admin { /* форма регистрации */
			background: #e6e6fa; /* Цвет фона */
			padding: 10px; /* Поля */
			margin-left: 10px;
			margin-right: 10px;
			text-align: center;
			font-size: 16px;
			border-width: 1px;
			border-color: #99CCCC;
			border-style: dashed;
			border-radius: 10px;
			-webkit-border-radius: 10px;
			-moz-border-radius: 10px;
		}
		.input { /* поля для вdода пароля данных в левой панели */
			border: 1px solid;
		}
		.tr {
		  font-size: 10px;
		}
		img.welcome { 
			height: 60px;
			width: 60px;
			margin-left: 3px;
			margin-top: 3px;
			margin-right: 5px;
			margin-bottom: 3px;
			border-radius: 10px;
			box-shadow: 0 0 0 2px #666; 
		}


  </style>
</head><body>
<div class="admin"><b>АДМИНИСТРАТИВНЫЙ РАЗДЕЛ</b><hr size="1"><form action="admin.php" method="POST"><br>
<b>Параметры просмотра лога:</b><br><br>
Период с <input class="input" maxlength="10" name="from" size="12" type="text" value='<?php echo(empty($_POST['from'])) ? date("Y-m-d") : $_POST['from'] ?>'>&nbsp; 
по &nbsp;<input class="input" maxlength="10" size="12" name="to" type="text" value='<?php echo($_POST['to'])?>'> &nbsp;
<!--login пользователя <input class="input" maxlength="40" size="20" name="login" type="text" value='<?php echo($_POST['login'])?>'> &nbsp;-->
<?php
  echo "Пользователь &nbsp;";
  echo "<select name=\"login\">";
  echo "<option>все пользователи</option>";
  $query = db_query("SELECT `login` FROM `users` order by `login`");
  dump($query);
  foreach ($query as $user) {
    if ($user['login'] == $_POST['login']) {
	  echo "<option selected>{$user['login']}</option>";
	} else {
	  echo "<option>{$user['login']}</option>";
	}
  }
  echo "</select>";
?>
<!--&nbsp;&nbsp;Тип действия <input class="input" name="action" type="text" value='<?php echo($_POST['action'])?>'>-->
<?php
  echo "Тип действия &nbsp;";
  echo "<select name=\"action\">";
  echo "<option>все действия</option>";
  $query = db_query("SELECT `action` FROM `gb_actions` order by `action`");
  dump($query);
  foreach ($query as $actions) {
    if ($actions['action'] == $_POST['action']) {
	  echo "<option selected>{$actions['action']}</option>";
	} else {
	  echo "<option>{$actions['action']}</option>";
	}
  }
  echo "</select>";
?>
<br><br>
<input name="submit" type="submit" value="Показать лог">
</form>
<hr size="1">

<form action="admin.php" method="POST"><br>
<input name="submit" type="submit" value="Показать список пользователей">
</form>
<hr size="1">

<b>Управление административной ролью</b><br>
<form action="admin.php" method="POST"><br>
<?php
  echo "Пользователь с правами user &nbsp;";
  echo "<select name=\"grant_to\">";
  $query = db_query("SELECT `login` FROM `users` where role='user' order by `login`");
  dump($query);
  foreach ($query as $user) {
    if ($user['login'] == $_POST['grant_to']) {
	  echo "<option selected>{$user['login']}</option>";
	} else {
	  echo "<option>{$user['login']}</option>";
	}
  }
  echo "</select>";
?>
&nbsp;<input name="submit" type="submit" value="Дать права администратора">
</form>
<?php
  if($_POST['submit'] == 'Дать права администратора') 
  {
    $sql = "UPDATE `users` SET `role` = 'admin' WHERE `login` = '{$_POST['grant_to']}'";
    if (mysqli_query($GLOBALS['$connect'], $sql)) {
	  echo "<meta http-equiv='refresh' content='0; url=admin.php'>";
	}
  }
?>
<form action="admin.php" method="POST"><br>
<?php
  echo "Пользователь с правами admin &nbsp;";
  echo "<select name=\"revoke_from\">";
  $query = db_query("SELECT `login` FROM `users` where `role`='admin' order by `login`");
  dump($query);
  foreach ($query as $user) {
    if ($user['login'] == $_POST['revoke_from']) {
	  echo "<option selected>{$user['login']}</option>";
	} else {
	  echo "<option>{$user['login']}</option>";
	}
  }
  echo "</select>";
?>
&nbsp;<input name="submit" type="submit" value="Снять права администратора">
<?php 
  if($_POST['submit'] == 'Снять права администратора') 
  {
    $sql = "UPDATE `users` SET `role` = 'user' WHERE `login` = '{$_POST['revoke_from']}'";
    if (mysqli_query($GLOBALS['$connect'], $sql)) {
	  echo "<meta http-equiv='refresh' content='0; url=admin.php'>";
	}
  }
?>
</form>
<hr size="1">
<b>Управление блокировкой пользователей</b><br>
<form action="admin.php" method="POST"><br>
<?php
  echo "Пользователи &nbsp;";
  echo "<select name=\"block_to\">";
  $query = db_query("SELECT `login` FROM `users` where `blocked`=0 order by `login`");
  dump($query);
  foreach ($query as $user) {
    if ($user['login'] == $_POST['block_to']) {
	  echo "<option selected>{$user['login']}</option>";
	} else {
	  echo "<option>{$user['login']}</option>";
	}
  }
  echo "</select>";
?>
&nbsp;<input name="submit" type="submit" value="Заблокировать пользователя">
</form>
<?php
  if($_POST['submit'] == 'Заблокировать пользователя') 
  {
    $sql = "UPDATE `users` SET `blocked` = 1 WHERE `login` = '{$_POST['block_to']}'";
    if (mysqli_query($GLOBALS['$connect'], $sql)) {
	  echo "<meta http-equiv='refresh' content='0; url=admin.php'>";
	}
  }
?>
<form action="admin.php" method="POST"><br>
<?php
  echo "Заблокированные пользователи &nbsp;";
  echo "<select name=\"revoke_block\">";
  $query = db_query("SELECT `login` FROM `users` where `blocked`=1 order by `login`");
  dump($query);
  foreach ($query as $user) {
    if ($user['login'] == $_POST['revoke_block']) {
	  echo "<option selected>{$user['login']}</option>";
	} else {
	  echo "<option>{$user['login']}</option>";
	}
  }
  echo "</select>";
?>
&nbsp;<input name="submit" type="submit" value="Снять блокировку пользователя">
<?php 
  if($_POST['submit'] == 'Снять блокировку пользователя') 
  {
    $sql = "UPDATE `users` SET `blocked` = 0 WHERE `login` = '{$_POST['revoke_block']}'";
    if (mysqli_query($GLOBALS['$connect'], $sql)) {
	  echo "<meta http-equiv='refresh' content='0; url=admin.php'>";
	}
  }
?>
</form>

<br><hr size="1">
<form action="admin.php" method="POST"><br>
<input type="submit" name="cancel" value="Выйти из раздела"><br>
</form><br>


<?php

if($_POST['submit'] == 'Показать список пользователей')
{
  $sql = "SELECT `users`.`id`, `login`, `fio`, `city`, `post`, `users`.`e_mail`, `icq`, `role`, `users`.`date_time`, `avatar`, `blocked`, count(`user_id`) as `posts_cnt` \n"
    . "FROM `users` LEFT JOIN `posts`\n"
    . "ON `users`.`id` = `posts`.`user_id`\n"
    . "GROUP BY `id`, `login`, `fio`, `city`, `post`, `e_mail`, `icq`, `role`, `date_time`\n"
    . "ORDER by `role`, `login`";
  $query = db_query($sql);
  echo "Список пользователей<br><table width=\"90%\" border=\"1\" bordercolor=\"black\" rules=\"all\" align=\"center\">";
  echo "<tr align=\"center\" bgcolor=\"#99CCCC\"><td>id</td><td>login</td><td></td><td>Имя</td><td>Откуда</td><td>Должность</td><td>e-mail</td><td>icq</td><td>Роль</td><td>Время последнего посещения</td><td>Кол-во сообщ.</td><td>Кол-во комментов</td><td>Заблоки-рован</td></tr>";
  foreach ($query as $user) {
	echo "<tr>";
	echo "<td>".$user['id']."</td>";
	echo "<td><b>".$user['login']."</b></td>";
	if ($user['avatar']) {
      $avatar = $path . '/' . $user['avatar'];
    } else {
    $avatar = $path . '/default.png';
    }
	echo "<td><img class=\"welcome\" src={$avatar}></td>";
	echo "<td>".$user['fio']."</td>";
	echo "<td>".$user['city']."</td>";
	echo "<td>".$user['post']."</td>";
	echo "<td>".$user['e_mail']."</td>";
	echo "<td>".$user['icq']."</td>";
	echo "<td>".$user['role']."</td>";
	echo "<td>".$user['date_time']."</td>";
	echo "<td>".$user['posts_cnt']."</td>";
	$query2 = db_query("select count(`id`) as comment_cnt from `comments` where `user_id` ='".$user['id']."'");
	echo "<td>".$query2[0]['comment_cnt']."</td>";
	echo "<td>".$user['blocked']."</td>";
	echo "</tr>";
  }
  echo "</table>";
}

if($_POST['submit'] == 'Показать лог')
{ 
  $where = array();
  if (!empty($_POST['from']))
	$where[] = "`log_date` >= '{$_POST['from']}'";
  if (!empty($_POST['to']))
	$where[] = "`log_date` <= '{$_POST['to']}'";
  if ($_POST['login'] != 'все пользователи')
	$where[] = " `log_login` = '". $_POST['login']."'";
  if ($_POST['action'] != 'все действия')
	$where[] = " `action` = '". $_POST['action']."'";
 
  $wheresql = implode(" AND ",$where);
  if (empty($wheresql))
   $wheresql = 1;
   
  $query = db_query("SELECT `log_date`, `log_time`, `log_userid`, `log_login`, `log_visitip`, `log_frompage`, `log_requesturi`, `log_get`, `log_post`, `gb_actions`.`action` FROM `gb_log`, `gb_actions` WHERE `gb_log`.`log_action`=`gb_actions`.`id` and ".$wheresql." order by `log_date`, `log_time`");
  if (is_array($query)) {
	  echo "Лог работы<br><table width=\"90%\" border=\"1\" bordercolor=\"black\" rules=\"all\" align=\"center\">";
	  echo "<tr align=\"center\" bgcolor=\"#99CCCC\"><td>Дата</td><td>Время</td><td>Действие</td><td>id</td><td>Логин</td><td>IP</td><td>Откуда</td><td>Страница</td><td>GET-запрос</td></tr>";
	  foreach ($query as $log) {
		echo "<tr>";
		echo "<td>&nbsp;".$log['log_date']."&nbsp;</td>";
		echo "<td>".$log['log_time']."</td>";
		echo "<td>".$log['action']."</td>";
		echo "<td>".$log['log_userid']."</td>";
		echo "<td>".$log['log_login']."</td>";
		echo "<td>".$log['log_visitip']."</td>";
		echo "<td>".$log['log_frompage']."</td>";
		echo "<td>".$log['log_requesturi']."</td>";
		echo "<td>".$log['log_get']."</td>";
		echo "</tr>";
	  }
	  echo "</table>";
   } else {
	  echo "Лог работы<br>Записей, удовлетворяющих запросу ".$wheresql." не найдено!";
   }
}


?>
</div>
</body>
