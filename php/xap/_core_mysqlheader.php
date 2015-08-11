
<?php
$_gbl_mysql_host="localhost";
$_gbl_user_id="scalphunter";
$_gbl_password="skpark001";
$_gbl_db_name="fx_pricer";
$_gbl_debug=false;

$conn=new mysqli($_gbl_mysql_host,$_gbl_user_id,$_gbl_password,$_gbl_db_name);
$conn->query("set session character_set_connection=utf8;");
$conn->query("set session character_set_results=utf8;");
$conn->query("set session character_set_client=utf8;");
$conn->close();

?>

