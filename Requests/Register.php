<?php declare(strict_types = 1);

use Sketchspace\Exception\RegisterException;
use Sketchspace\Exception\ValidationError;
use Sketchspace\Library\Authentication;
use Steampixel\Route;
use CodeShack\Template;

use Sketchspace\Library\ThrottleManagement;
use Sketchspace\Exception\TooManyRequestsException;

use Sketchspace\Object\User;

Route::add('/register', function()
{
    $messages = [];
    
    $username = null;
    $full_name = null;
    $email = null;
    $url = null;

    $succeeded = false;

    session_start();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        do {
            // first, check if the user has made many requests
            try {
                ThrottleManagement::useResource(
                    [ 'Register', $_SERVER['REMOTE_ADDR'] ], // resource
                    1,                                      // one request
                    60*60,                                  // 1 hr interval
                    75                                      // 75 maximum
                );
            } catch (TooManyRequestsException $e) {
                array_push($messages, ['error', $e->getMessage()]);
                break;
            }

            // check empties
            if (empty(trim($_POST['u']))) {
                array_push($messages, ['error', 'Username required...']);
                break;
            }
            $username = $_POST['u'];

            if (empty(trim($_POST['fn']))) {
                array_push($messages, ['error', 'Full name required...']);
                break;
            }
            $full_name = $_POST['fn'];

            if (empty(trim($_POST['e']))) {
                array_push($messages, ['error', 'E-mail?']);
                break;
            }
            $email = $_POST['e'];

            if (empty(trim($_POST['pw']))) {
                array_push($messages, ['error', 'Password?']);
                break;
            }
            if (empty(trim($_POST['cpw']))) {
                array_push($messages, ['error', 'Confirm password?']);
                break;
            }
            if (trim($_POST['pw']) != trim($_POST['cpw'])) {
                array_push($messages, ['error', 'Password not matching?']);
                break;
            }
            $password = $_POST['pw'];

            $url = trim($_POST['w']);

            // anti-CSRF
            if (empty($_POST['anticsrf'])) {
                array_push($messages, ['error', 'No token']);
                break;
            }
            if ($_POST['anticsrf'] != $_SESSION['acsrf']) {
                array_push($messages, ['error', 'Token mismatch']);
                break;
            }

            try {
                $status = Authentication::registerUser(User::newUser(
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $full_name,
                    $email,
                    $url
                ));
            } catch (ValidationError | RegisterException $e) {
                array_push($messages, ['error', 'Error: ' . $e->getMessage()]);
                break;
            }
            switch ($status) {
                case User::FAILED:
                    array_push($messages, ['error', 'An error occurred, please try again in a few moments.']);
                    break;
                case User::ADDED:
                    array_push($messages, ['success', 'Registered! Log in using the form in the menu.']);
                    $succeeded = true;
                    break;
                default:
                    array_push($messages, ['warning', 'This should not happen...']);
                    break;
            }
        } while (false);
    }

    if ($succeeded) {
        Template::view('Views/_layout.html',[
            'logged_in_user' => Authentication::getCurrentUser(),
            'messages' => $messages
        ]);
    } else {
        $_SESSION['acsrf'] = bin2hex(random_bytes(32));
        Template::view('Views/register.html',[
            'logged_in_user' => Authentication::getCurrentUser(),
            'messages' => $messages,
            'form_save' => [
                'u'  => $username,
                'fn' => $full_name,
                'e'  => $email
            ],
            'hide_login_form' => true,
            'acsrf' => $_SESSION['acsrf']
        ]);
    }
}, ['get', 'post']);
