<?php
  //формируем картинку капчу и код кидаем в $_SESSION['captcha']
  session_start();
  Header("Pragma: no-cache"); //сообщает браузеру обновить страницу в кэше
  $length = 4; //длина кода. рекомендуется 4 символа. нормально работает при длине от 1 до 10 символов
  $code="";
  for ($i=1; $i<=$length; $i++)
    $code .= rand(0,9);
  $_SESSION['captcha'] = $code;
  $code = $_SESSION['captcha']; //читаем записанную переменную из сессии, чтобы уж наверняка совпадало
  $pic = ImageCreateFromgif("images/antispam.gif"); //подложка для цифр
  Header("Content-type: image/gif");
  $color=imagecolorclosest($pic, rand(0,100), rand(0,100), rand(0,100)); //rgb соответственно
  
  $font = 'fonts/ARCARTER.ttf'; //нравится мне этот шрифт
  
  for ($i=1; $i<=$length; $i++) {
    imagettftext($pic, rand(25,35), rand(-20,20), 25+($i-1)*(140/$length), rand(32,43), $color, $font, $code[$i-1]); //картинка, размер шрифта, угол, поз_х, поз_у, цвет, шрифт, текст(символ)
  }

  Imagegif($pic);

  ImageDestroy($pic);
?>
