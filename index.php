<?php declare(strict_types = 1);

// Only for debugging
ini_set('display_errors', '1');

// set session vars
ini_set('session.use_cookies', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.name', 'SS_SESSION');
ini_set('session.use_strict_mode', '1');

error_reporting(E_ALL);

if (session_status() == PHP_SESSION_ACTIVE) {
    session_regenerate_id();
}

const SKETCHSPACE_DATE_FMT = 'M-j-Y';
const SKETCHSPACE_TIME_FMT = 'H:i:s';
const SKETCHSPACE_DATETIME_FMT = 'M-j-Y, H:i:s';
const SKETCHSPACE_USERNAME_REGEX = '/^[a-z0-9\-_]{4,32}$/';
const SKETCHSPACE_SUPPORTED_MIMETYPES = [
    'image/png',
    'image/jpeg',
    'image/gif',
    'image/x-ms-bmp',
    'image/bmp'
];

const SKETCHSPACE_UPLOAD_DIR = 'assets/submissions';
const SKETCHSPACE_THUMB_DIR  = 'assets/thumbs';

// max login time: 1hr
const SKETCHSPACE_MAX_LOGIN_TIME = 60 * 60; // seconds

// use libraries
require_once 'Library/Util.php';
require_once 'Library/Database.php';
require_once 'Library/SessionStorage.php';
require_once 'Library/ThrottleManagement.php';
require_once 'Library/Exceptions.php';
require_once 'Library/Authentication.php';
require_once 'Library/Image.php';

require_once 'Library/External/Route.php';
require_once 'Library/External/Template.php';

require_once 'Library/Enum/Queries.php';
require_once 'Library/Enum/UserRoles.php';
require_once 'Library/Enum/ResponseCode.php';

require_once 'Objects/_BasicObject.php';
require_once 'Objects/Submission.php';
require_once 'Objects/User.php';
require_once 'Objects/Categories.php';

use Sketchspace\Library\Database;

use Sketchspace\Object\User;
use Sketchspace\Object\Submission;
use Sketchspace\Object\Category;
use Sketchspace\Object\Subcategory;

(function(){ // iife begin

    // init
    Database::initDb(
        'mysql',        // type
        'localhost',    // host
        'sketchspace',  // db
        'sketchspace',  // user
        'sketchspace',  // password
        'sk'            // prefix
    );

    User::initTables();

    $categories_initialized = Category::initTables();

    // init default categories
    $default_categories = [
        0 => Category::newCategory('Desktop Customization'),
        1 => Category::newCategory('Digital Art'),
        2 => Category::newCategory('Traditional Art'),
        3 => Category::newCategory('Wallpapers'),
    ];

    if ($categories_initialized == Category::INITIALIZED) {
      // first initialized
      foreach ($default_categories as $category_name => $category) {
          $category->commitToDatabase();
      }
    }

    $subcategories_initialized = Subcategory::initTables();

    $default_subcategories = [
          'Desktops - Windows (DOS, NT)' => 0,
          'Desktops - DOS' => 0,
          'Desktops - Mac OS Classic (System 1 - Mac OS 9)' => 0,
          'Desktops - macOS (10.0+)' => 0,
          'Desktops - Linux' => 0,
          'Desktops - BSD' => 0,
          'Desktops - Other' => 0,

          'Digital - Fanart' => 1,
          'Digital - Paintings' => 1,
          'Digital - Sketches & Oekaki' => 1,
          'Digital - Comics' => 1,
          'Digital - Miscellaneous' => 1,
          'Digital - Graphics & Design' => 1,

          'Traditional - Drawings' => 2,
          'Traditional - Comics' => 2,
          'Traditional - Mixed Media' => 2,
          'Traditional - Miscellaneous' => 2,

          'Wallpapers - Bright' => 3,
          'Wallpapers - Dark' => 3,
          'Wallpapers - Miscellaneous' => 3,
    ];

    if ($subcategories_initialized == Category::INITIALIZED) {
      // first initialized
      foreach ($default_subcategories as $subcategory_name => $category_id) {
          $subcategory = Subcategory::newSubcategory($subcategory_name);
          $subcategory->commitToDatabase();
          $subcategory->makeChildOf($default_categories[$category_id]);
      }
    }

    Submission::initTables();

    // don't expose PHP version
    header_remove('X-Powered-By');

require_once 'views.php';

})(); // iife end
