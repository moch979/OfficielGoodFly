<?php

require_once dirname(__DIR__) . '/includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) {
    redirect('index.php');
}
$_SESSION = [];
session_destroy();
redirect('login.php');
