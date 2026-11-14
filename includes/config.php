<?php
if (!defined('ALLOWED_ACCESS')) exit('Direct access not allowed.');

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

$db_auth_host = 'localhost';
$db_auth_port = '3306';
$db_auth_user = '';
$db_auth_pass = '';
$db_auth_name = 'acore_auth';

$db_world_host = 'localhost';
$db_world_port = '3306';
$db_world_user = '';
$db_world_pass = '';
$db_world_name = 'acore_world';

$db_char_host = 'localhost';
$db_char_port = '3306';
$db_char_user = '';
$db_char_pass = '';
$db_char_name = 'acore_characters';

$db_site_host = 'localhost';
$db_site_port = '3306';
$db_site_user = '';
$db_site_pass = '';
$db_site_name = 'sahtout_site';

try {
    $auth_db  = new mysqli($db_auth_host,  $db_auth_user,  $db_auth_pass,  $db_auth_name,  (int)$db_auth_port);
    $world_db = new mysqli($db_world_host, $db_world_user, $db_world_pass, $db_world_name, (int)$db_world_port);
    $char_db  = new mysqli($db_char_host,  $db_char_user,  $db_char_pass,  $db_char_name,  (int)$db_char_port);
    $site_db  = new mysqli($db_site_host,  $db_site_user,  $db_site_pass,  $db_site_name,  (int)$db_site_port);
} catch (mysqli_sql_exception $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    exit('Database connection error.');
}

if ($auth_db->connect_error || $world_db->connect_error || $char_db->connect_error || $site_db->connect_error) {
    error_log('Database connection error detected.');
    if (!headers_sent()) {
        http_response_code(500);
    }
    exit('Database connection error.');
}
?>
