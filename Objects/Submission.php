<?php declare(strict_types = 1);

namespace Sketchspace\Object;

use PDO;
use PDOStatement;
use PDOException;
use Sketchspace\Library\Database;
use Sketchspace\Enum\Queries;
use Sketchspace\Object\BasicObject;

// external objects
require_once 'Objects/User.php';

/**
 * Corresponds to <Prefix>_submissions
 */
class Submission implements BasicObject
{
    public const TABLE_NAME = '_submissions';

    private int $id;
    public string $subject;
    public string $description;

    /**
     * Corresponds to <Prefix>_submissions.add_timestamp
     */
    private int $added;

    private bool $in_sync = false;

    /**
     * Create a Submission from scratch
     */
    public static function newSubmission(string $subject, string $description = ''): Submission
    {
        $user = new Submission();

        $user->subject = $subject;
        $user->description = $description;
        $user->markAddDate();

        return $user;
    }

    public static function fromStatement(PDOStatement $statement): Submission|bool
    {
        return
            $statement->fetchObject(__NAMESPACE__.'\Submission');
    }

    public function __set(mixed $name, mixed $value): void
    {
        switch ($name) {
            case 'add_timestamp':
                $this->added = (int) $value;
                $this->in_sync = true;
                break;
            default:
                break;
        }
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function isObjectInSync(): bool
    {
        return $this->in_sync;
    }

    /**
     * Marks the submission's add date. If the add date is set,
     * it won't reset it
     */
    public function markAddDate(): void
    {
        if (!isset($this->added)) {
            $this->added = time();
        }
    }

    /**
     * [Database]
     * Link a submission to a user in the database
     *
     * @param User $user
     */
    public function associateWithUser(User $user): void
    {
        /**
         * If we don't have an ID yet (no equiv. in database)
         * then bail out
         */
        if (!isset($this->id)) return;

        $q = Database::$db->prepare('
            select * from '.Database::$prefix.Queries::R_SUBMISSION_USER.'
            where submission_id=:id
        ');

        $q->execute(['id' => $this->id]);

        $result = $q->fetch();

        if ($result) {
            $q = Database::$db->prepare('
                update '.Database::$prefix.Queries::R_SUBMISSION_USER.'
                set
                    user_id = :user_id
                where
                    submission_id = :submission_id
            ');
        } else {
            $q = Database::$db->prepare('
                insert into '.Database::$prefix.Queries::R_SUBMISSION_USER.'
                (submission_id, user_id)
                values
                    (:submission_id, :user_id)
            ');
        }
        $q->execute([
            'submission_id' => $this->id,
            'user_id' => $user->getId()
        ]);
    }

    /**
     * [Database]
     * @return User|bool [description]
     */
    public function getAssociatedUser(): User|bool
    {
        $q = Database::$db->prepare('
           select * from '.Database::$prefix.Queries::R_SUBMISSION_USER.'
           where submission_id = :id
        ');
        $q->execute(['id' => $this->id]);

        $result = $q->fetch();

        if ($result) {
            $q = Database::$db->prepare('
               select * from '.Database::getTable('User').'
               where id = :id
            ');
            $q->execute(['id' => $result['user_id']]);

            return User::fromStatement($q);
        }

        return false;
    }

    public function commitToDatabase(): int
    {
        /**
         * If the submission already exists in the database
         * simply update it
         */
        if ($this->id) {
            try {
                $q = Database::$db->prepare('
                    update '.Database::getTable('Submission').'
                    set
                        subject = :subject,
                        description = :description
                    where
                        id = :id
                ');
                $q->execute([
                    'subject' => $this->subject,
                    'description' => $this->description,
                    'id' => $this->id
                ]);

                $this->in_sync = true;
                return self::UPDATED;
            } catch (PDOException $e) {
                return self::FAILED;
            }
        }
        // either updated or failed

        /**
         * Otherwise, create it
         */
        try {

            $q = Database::$db->prepare('
                insert into '.Database::getTable('Submission').'
                (subject, description)
                values
                    (:subject, :description)
            ');

            $q->execute([
                'subject' => $this->subject,
                'description' => $this->description
            ]);

            $this->id = (int) Database::$db->lastInsertId();
            $this->in_sync = true;
            return self::ADDED;
        } catch (PDOException $e) {
            return self::FAILED;
        }
    }
}
