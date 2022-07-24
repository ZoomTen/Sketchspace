<?php declare(strict_types = 1);

namespace Sketchspace\Library;
use Sketchspace\Exception\MissingParametersException;
use Sketchspace\Exception\RegisterException;
use Sketchspace\Exception\ValidationError;
use Sketchspace\Object\User;
use Sketchspace\Exception\TooManyRequestsException;
use Sketchspace\Exception\InvalidParameterException;

class Authentication
{
    // define array keys
    const SESSION_CURRENT_USER = 'user';
    const SESSION_IDENTIFIER = 'session_id';
    const SESSION_LASTLOGIN = 'last_login';

    /**
     * Set up sessions and secure headers
     */
    private static function init(): void
    {
        // prevent clickjacking
        header('X-Frame-Options: sameorigin');
        // prevent content sniffing (MIME sniffing)
        header('X-Content-Type-Options: nosniff');
        // disable caching of potentially sensitive data
        header('Cache-Control: no-store, no-cache, must-revalidate', true);
        header('Expires: Wed, 04 Apr 1984 00:00:00 GMT', true);
        header('Pragma: no-cache', true);

        // handle session validity
        if (session_status() == PHP_SESSION_ACTIVE) {
            if (isset($_SESSION[self::SESSION_CURRENT_USER])) {
                // check for correct identifier, prevents session ID hijacking
                if ($_SESSION[self::SESSION_IDENTIFIER] != self::getConnectionID()) {
                    self::unsetAll();
                }

                // automatically log out after SKETCHSPACE_MAX_LOGIN_TIME seconds
                if (time() > $_SESSION[self::SESSION_LASTLOGIN] + SKETCHSPACE_MAX_LOGIN_TIME) {
                    self::unsetAll();
                }
            }
        } else {
            session_start();
        }
    }

    /**
     * A unique connection ID calculated from a host's IP address and user agent
     * @return string md5'd hash of both
     */
    private static function getConnectionID(): string
    {
        return
            md5(
                $_SERVER['REMOTE_ADDR'] . ' ' .
                $_SERVER['HTTP_USER_AGENT']
            );
    }

    /**
     * Registers a given user
     * @param User $user
     * @return int status of database commit
     * @throws RegisterException if validation fails
     */
    public static function registerUser(User $user): int
    {
        self::init();

        // most validation functions handled by User now

        // validate user exists
        $existing = Database::$db->prepare('
            select id from '.Database::getTable('User').'
            where
                username=:username
                or email=:email
        ');
        $existing->execute([
            'username' => $user->getUsername(),
            'email' => $user->getEmail()
        ]);
        if (!empty($existing->fetch())) {
            throw new RegisterException('User already exists');
        }

        // sanitize full name
        $fname = trim( $user->getFullName() );
        $user->setFullName( Util::sanitize($fname) );

        // end validation, commit to database
        return $user->commitToDatabase();
    }

    public static function loggedIn(): bool
    {
        self::init();
        return isset($_SESSION[self::SESSION_CURRENT_USER]);
    }

    public static function logOut(): void
    {
        self::init();
        self::unsetAll();
    }

    private static function unsetAll(): void
    {
        unset($_SESSION[self::SESSION_CURRENT_USER]);
        unset($_SESSION[self::SESSION_LASTLOGIN]);
        unset($_SESSION[self::SESSION_IDENTIFIER]);
    }

    public static function getCurrentUser(): User | null
    {
        self::init();

        return $_SESSION[self::SESSION_CURRENT_USER] ?? null;
    }

    /**
     * @throws TooManyRequestsException
     * @throws InvalidParameterException
     * @throws MissingParametersException
     */
    public static function signInUser(string|null $username_or_email, string|null $password): User
    {
        self::init();

        // now
        $login_time = time();

        // do throttle
        ThrottleManagement::useResource(
            [ 'Sign in', $_SERVER['REMOTE_ADDR'] ], // resource
            500,                                    // # requests
            60*60*24,                               // 1 day interval
            75                                      // 75 maximum
        );

        // check empty parameters
        if (empty($username_or_email)) {
            throw new MissingParametersException('Username or e-mail required');
        }

        if (empty($password)) {
            throw new MissingParametersException('Password required');
        }

        // try to get user
        $q = Database::$db->prepare('
            select * from '.Database::getTable('User').'
            where email=? or username=?
        ');
        $q->execute([$username_or_email, $username_or_email]);
        $u = User::fromStatement($q);

        // if we don't have a user, call it quits
        if (empty($u)) {
            throw new InvalidParameterException(
                "Invalid username or password" // vagueness reduces attack vectors
            );
        }

        /**
         * otherwise, move on to validating the password
         * While we're at it, update the hash too, to keep up with PHP's
         * hash updates
         */
        $ph = $u->getPasswordHash();
        if (password_verify($password, $ph)) {
            if (password_needs_rehash($ph, PASSWORD_DEFAULT)) {
                $np = password_hash($password, PASSWORD_DEFAULT);
                $u->setPasswordHash($np);
                $u->commitToDatabase(true);
            }

            // user is authenticated

            // update last login details
            $q = Database::$db->prepare('
                update '.Database::getTable('User').'
                set
                    last_login = ?
                where
                    id = ?
            ');
            $q->execute([$login_time, $u->getId()]);

            // start the session
            session_regenerate_id(true);

            $_SESSION[self::SESSION_CURRENT_USER] = $u;
            $_SESSION[self::SESSION_IDENTIFIER] = self::getConnectionID();
            $_SESSION[self::SESSION_LASTLOGIN] = $login_time;
            return $u;
        }

        throw new InvalidParameterException('Invalid username or password');
    }
}
