<?php
require_once __DIR__ . '/db.php';
start_session_once();
session_destroy();
header('Location: login.php');
exit;
