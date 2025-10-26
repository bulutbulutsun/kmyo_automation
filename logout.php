<?php
require_once 'config.php';

Session::start();
$auth = new Auth();
$auth->logout();
?>