<?
/* $Id: select_subdivision.php 5946 2012-01-17 10:44:36Z denis $ */
// выбор родительского раздела

require("./head.php");
?>

<body style='margin: 0'>
    <iframe name='treeIframe' id='treeIframe'
            src='<?=$ADMIN_PATH
?>tree_frame.php?mode=wizard_subdivision'
            height='100%' width='100%' frameborder='0'></iframe>
</body>
</html>