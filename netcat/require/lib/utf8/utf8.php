<?
/* $Id: utf8.php 718 2006-12-18 11:17:12Z kx $ */

// cp1251 to utf
function nc_win2utf($str)
{
   if (extension_loaded('mbstring')) return mb_convert_encoding($str, "UTF-8", "cp1251");
   if (extension_loaded('iconv'))    return iconv('cp1251','UTF-8', $str);

   global $_UTFConverter;
   if (!$_UTFConverter)
   {
      require_once("utf8/utf8.class.php");
      $_UTFConverter = new utf8(CP1251);
   }
   return $_UTFConverter->strToUtf8($str);
}


// utf to cp1251
function nc_utf2win($str)
{
   if (extension_loaded('mbstring')) return mb_convert_encoding($str, "cp1251", "UTF-8");
   if (extension_loaded('iconv'))    return iconv('UTF-8','cp1251', $str);

   global $_UTFConverter;
   if (!$_UTFConverter)
   {
      require_once("utf8/utf8.class.php");
      $_UTFConverter = new utf8(CP1251);
   }
   return $_UTFConverter->utf8ToStr($str);
}



?>