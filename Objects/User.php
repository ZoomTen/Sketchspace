<?php declare(strict_types = 1);

namespace Sketchspace\Object;

use DateTime;
use PDO;
use PDOStatement;
use PDOException;
use Sketchspace\Exception\ValidationError;
use Sketchspace\Library\Database;
use Sketchspace\Object\BasicObject;
/**
 * Corresponds to <Prefix>_users
 */
class User implements BasicObject
{
    public const TABLE_NAME = '_users';

    private int $id;
    
    private string $username;
    private string $password;
    private int $joined;
    private string $full_name;
    private string $email;
    private string $url = '';

    private bool $in_sync = false;

    /**
     * Create a User from scratch
     *
     * I can't make a proper constructor because this class
     * is used with PDOStatement::fetchObject, so use this
     * instead of new User()
     *
     * @param string $username Username
     * @param string $password_hash
     * @param string $full_name
     * @param string $email
     * @param string|null $url
     * @return User
     */
    public static function newUser(string $username, string $password_hash, string $full_name, string $email, string $url = null): User
    {
        $user = new User();

        $user->setUsername($username);
        $user->setPasswordHash($password_hash);
        $user->setFullName($full_name);
        $user->setEmail($email);
        $user->setURL($url);

        $user->markJoinDate();

        return $user;
    }

    public static function fromStatement(PDOStatement $statement): User|bool
    {
        return
            $statement->fetchObject(__NAMESPACE__.'\User');
    }

    public function __set(mixed $name, mixed $value): void
    {
        switch ($name) {
            case 'join_timestamp':
                $this->joined = (int) $value;
                $this->in_sync = true;
                break;
            default:
                break;
        }
    }

    public function isObjectInSync(): bool
    {
        return $this->in_sync;
    }

    public function markJoinDate(): void
    {
        if (!isset($this->joined)) {
            $this->joined = time();
        }
    }

    public function commitToDatabase(bool $can_update = false): int
    {
        /**
         * If the user already exists in the database
         * simply update it
         */
        if ($can_update) {
            if (isset($this->id)) {
                try {
                    $q = Database::$db->prepare('
                        update '.Database::getTable('User').'
                        set
                            username = :username,
                            password = :password,
                            full_name = :full_name,
                            email = :email,
                            url = :url
                        where
                            id = :id
                    ');
                    $q->execute([
                        'username' => $this->username,
                        'password' => $this->password,
                        'full_name' => $this->full_name,
                        'email' => $this->email,
                        'url' => $this->url,
                        'id' => $this->id
                    ]);

                    $this->in_sync = true;
                    return self::UPDATED;
                } catch (PDOException $e) {
                    return self::FAILED;
                }
            }
        }
        // either updated or failed

        /**
         * Otherwise, create it
         */
        try {
            $q = Database::$db->prepare('
                insert into '.Database::getTable('User').'
                (username, password, join_timestamp, full_name, email, url)
                values
                    (:username, :password, :joined, :full_name, :email, :url)
            ');
            $q->execute([
                'username' => $this->username,
                'password' => $this->password,
                'joined' => $this->joined,
                'full_name' => $this->full_name,
                'email' => $this->email,
                'url' => $this->url,
            ]);

            $this->id = (int) Database::$db->lastInsertId();
            $this->in_sync = true;
            return self::ADDED;
        } catch (PDOException $e) {
            return self::FAILED;
        }
    }

// ------ Here comes the getters and setters.... oh no. ------

    public function setUsername(string $username): void
    {
        if (preg_match(SKETCHSPACE_USERNAME_REGEX, $username)) {
            $this->in_sync = false;
            $this->username = $username;
            return;
        }
        throw new ValidationError('Username must only consist of lowercase letters, numbers, -, _ between 4 and 32 characters long.');
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function setFullName(string $name): void
    {
        $name = trim($name);
        $this->in_sync = false;
        $this->full_name = $name;
    }

    public function getFullName(): string
    {
        return $this->full_name;
    }

    public function setEmail(string $email): void
    {
        $email = trim($email);
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->in_sync = false;
            $this->email = $email;
            return;
        }
        throw new ValidationError('Invalid e-mail address');
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setURL(string $url): void
    {
        $url = trim($url);
        if (empty($url)) return;
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $this->in_sync = false;
            $this->url = $url;
            return;
        }
        throw new ValidationError('Invalid URL?');
    }

    public function getURL(): string
    {
        return $this->url;
    }

    public function setPasswordHash(string $hash): void
    {
        $this->in_sync = false;
        if (!empty(password_get_info($hash)['algo'])) {
        // If this is a valid password that is already hashed
            $this->password = $hash;
        } else {
        // otherwise error out
            throw new ValidationError('Invalid password');
        }

        if (password_verify('', $hash)) {
            throw new ValidationError('Password cannot be empty');
        }
    }

    public function getPasswordHash(): string
    {
        return $this->password;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getJoinDate(): DateTime
    {
        return DateTime::createFromFormat( 'U', strval($this->joined) );
    }

    public function formatJoinDate(string $format): string
    {
        return $this->getJoinDate()->format($format);
    }
}
