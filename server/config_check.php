<?php
/** 
 ** This script check for PHP configuration.
 **/
echo "Configuration checking :<br>\r\n";
if (ini_get('short_open_tag') !== '1') 
{
		echo "Enable short_open_tag.<br>\r\n";
}
echo "<br>\r\nDone.\r\n";