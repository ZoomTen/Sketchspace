<?php declare(strict_types = 1);

// Only for debugging
ini_set('display_errors', '1');

error_reporting(E_ALL);

if (session_status() == PHP_SESSION_ACTIVE) {
    session_regenerate_id();
}

const SKETCHSPACE_DATE_FMT = 'M-j-Y';
const SKETCHSPACE_TIME_FMT = 'H:i:s';
const SKETCHSPACE_DATETIME_FMT = 'M-j-Y, H:i:s';

// use libraries
require_once 'Library/Util.php';
require_once 'Library/Database.php';
require_once 'Library/SessionStorage.php';
require_once 'Library/ThrottleManagement.php';
require_once 'Library/ResponseCode.php';
require_once 'Library/Exceptions.php';
require_once 'Library/Authentication.php';
require_once 'Library/External/Route.php';
require_once 'Library/External/Template.php';
require_once 'Library/Enum/Queries.php';
require_once 'Library/Enum/UserRoles.php';
require_once 'Objects/_BasicObject.php';
require_once 'Objects/Submission.php';
require_once 'Objects/User.php';

use Sketchspace\Library\Database;
use Sketchspace\Library\SessionStorage;
use Sketchspace\Library\ThrottleManagement;

// init
Database::initDb(
    'mysql',
    'localhost',
    'sketchspace',
    'sketchspace',
    'sketchspace',
    'sk'
);

Database::setupAllTables();

// don't expose PHP version
header_remove('X-Powered-By');

require_once 'views.php';
