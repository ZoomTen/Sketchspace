<?php declare(strict_types = 1);

use Steampixel\Route;

require_once 'Requests/Index.php';
require_once 'Requests/UserPage.php';
require_once 'Requests/Register.php';
require_once 'Requests/Login_Logout.php';
require_once 'Requests/Submission.php';

Route::run('/');
