<?php

require_once '../app/config/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfRequestIsValid()) {
    http_response_code(403);
    exit('Invalid request.');
}

session_destroy();

header("Location: login.php");
exit;
