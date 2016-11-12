<?php
session_start();
require_once("connect.php");
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
 <head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
  <meta http-equiv="Pragma" content="no-cache" />
  <meta name="author" content="Платонов А.">
  <title>Гостевая книга</title>
  <link rel="stylesheet" href="style.css" type="text/css">
  
  <script type="text/javascript" language="JavaScript">
	
	function replaceSelectedText(obj,cbFunc)
	{
	  obj.focus();

	  if (document.selection)
	  {
	    var s = document.selection.createRange();

	    eval("s.text="+cbFunc+"(s.text);");
	    s.select();
	    return true;
	  }
	  else if (typeof(obj.selectionStart)=="number")
	  {
	    if (obj.selectionStart!=obj.selectionEnd)
	    {
	       var start = obj.selectionStart;
	       var end = obj.selectionEnd;

		   eval("var rs = "+cbFunc+"(obj.value.substr(start,end-start));");
	       obj.value = obj.value.substr(0,start)+rs+obj.value.substr(end);
	       obj.setSelectionRange(end,end);
	    }
	    return true;
	  }
	  return false;
    }
	function b(s){return '[b]'+s+'[/b]';}
	function u(s){return '[u]'+s+'[/u]';}
	function i(s){return '[i]'+s+'[/i]';}
	function url(s){return '[url]'+s+'[/url]';}
	function quote(s){return '[quote]'+s+'[/quote]';}
	function code(s){return '[code]'+s+'[/code]';}
	
  </script>
 </head>
<div class="header"><?php require("header.php"); ?></div>
<div class="userpanel"><?php require("login.php"); ?></div>
<?php
if (isset($_GET['register']) and ($_GET['register'] == '1')) {
  require("register.php");
} else {
  require("gb.php");
  gb_log(8);
}
?>
  <br><br>
  <div class="footer"><?php require("footer.php"); ?></div>
 </body>
</html>