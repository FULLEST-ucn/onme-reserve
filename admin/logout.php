<?php
require dirname(__DIR__) . '/config/bootstrap.php';
session_destroy();
redirect('/admin/login.php');
