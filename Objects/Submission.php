<?php declare(strict_types = 1);

namespace Sketchspace\Object;

use PDO;
use PDOStatement;
use PDOException;
use DateTime;
use Sketchspace\Library\Database;
use Sketchspace\Library\Util;
use Sketchspace\Enum\Queries;
use Sketchspace\Object\BasicObject;
use Sketchspace\Exception\SubmissionException;

// external objects
require_once 'Objects/User.php';

/**
 * Corresponds to <Prefix>_submissions
 */
class Submission implements BasicObject
{
    public const TABLE_NAME = '_submissions';

    private int $id;
    private string $subject;
    private string|null $description = null;
    
    public string|null $file = null;
    public string|null $thumbnail = null;

    /**
     * Corresponds to <Prefix>_submissions.add_timestamp
     */
    private int $added;

    private bool $in_sync = false;

    public static function initTables(): int
    {
      $tables_init = 0;

      // create submissions table
      if (!Database::tableExists(Database::getTable('Submission'))){
          Database::$db->query('
              create table '. Database::getTable('Submission') . ' (
                  id int primary key auto_increment,
                  subject varchar(80) not null,
                  description text,
                  file text not null,
                  thumbnail text,
                  add_timestamp int default 0
              )
          ');
          $tables_init += 1;
      }

      // create relations table
      if (!Database::tableExists(Database::$prefix . Queries::R_SUBMISSION_USER)){
          Database::$db->query('
              create table '. Database::$prefix . Queries::R_SUBMISSION_USER . ' (
                  submission_id int,
                  user_id int,

                  foreign key (submission_id) references '. Database::getTable('Submission') .'(id),
                  foreign key (user_id) references '. Database::getTable('User') .'(id)
              )
          ');
          $tables_init += 1;
      }

      if (!Database::tableExists(Database::$prefix . Queries::R_SUBMISSION_SUBCATEGORY)){
          Database::$db->query('
              create table '. Database::$prefix . Queries::R_SUBMISSION_SUBCATEGORY . ' (
                  submission_id int,
                  subcategory_id int,

                  foreign key (submission_id) references '. Database::getTable('Submission') .'(id),
                  foreign key (subcategory_id) references '. Database::getTable('Subcategory') .'(id)
              )
          ');
          $tables_init += 1;
      }

      if ($tables_init > 0) {
          return self::INITIALIZED;
      }

      return self::NOT_MODIFIED;
    }

    /**
     * Create a Submission from scratch
     *
     * @param string $subject
     * @param string|null $description
     * @param string $filename
     * @param string $thumbnail
     * @return Submission
     */
    public static function newSubmission(string $subject, string $filename, string $thumbnail, string $description = null): Submission
    {
        $submission = new Submission();

        $submission->setSubject($subject);
        $submission->setDescription($description);
        $submission->setFilename($filename);
        $submission->setThumbnailLocation($thumbnail);
        $submission->markAddDate();

        return $submission;
    }

    public static function fromId(int $id): Submission|bool
    {
        $q = Database::$db->prepare('
            select * from '.Database::getTable('Submission').'
            where id = :id
        ');

        $q->execute(['id'=>$id]);

        return Submission::fromStatement($q);
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

    public function getAddedTimestamp(): DateTime
    {
        return DateTime::createFromFormat( 'U', strval($this->added) );
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
        if (!isset($this->id)){
            throw new SubmissionException('Submission not in database?');;
        }
        if (!$user->getId()){
            throw new SubmissionException('User not in database?');;
        }

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
     * Link a submission to a subcategory.
     *
     * @param Subcategory $subcat
     */
    public function attachToSubcategory(Subcategory $subcat): void
    {
        /**
         * If we don't have an ID yet (no equiv. in database)
         * then bail out
         */
        if (!isset($this->id)){
            throw new SubmissionException('Submission not in database?');
        }
        if (!$subcat->getId()){
            throw new SubmissionException('Subcategory not in database?');
        }

        $q = Database::$db->prepare('
            select * from '.Database::$prefix.Queries::R_SUBMISSION_SUBCATEGORY.'
            where submission_id=:id
        ');

        $q->execute(['id' => $this->id]);

        $result = $q->fetch();

        if ($result) {
            $q = Database::$db->prepare('
                update '.Database::$prefix.Queries::R_SUBMISSION_SUBCATEGORY.'
                set
                    subcategory_id = :subcategory_id
                where
                    submission_id = :submission_id
            ');
        } else {
            $q = Database::$db->prepare('
                insert into '.Database::$prefix.Queries::R_SUBMISSION_SUBCATEGORY.'
                (submission_id, subcategory_id)
                values
                    (:submission_id, :subcategory_id)
            ');
        }
        $q->execute([
            'submission_id' => $this->id,
            'subcategory_id' => $subcat->getId()
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

    /**
     * [Database]
     * @return Subcategory|bool
     */
    public function getSubcategory()
    {
        $q = Database::$db->prepare('
           select * from '.Database::$prefix.Queries::R_SUBMISSION_SUBCATEGORY.'
           where submission_id = :id
        ');
        $q->execute(['id' => $this->id]);

        $result = $q->fetch();

        if ($result) {
            $q = Database::$db->prepare('
               select * from '.Database::getTable('Subcategory').'
               where id = :id
            ');
            $q->execute(['id' => $result['subcategory_id']]);

            return Subcategory::fromStatement($q);
        }

        return false;
    }

    public function commitToDatabase(): int
    {
        /**
         * If the submission already exists in the database
         * simply update it
         */
        if (isset($this->id)) {
            try {
                $q = Database::$db->prepare('
                    update '.Database::getTable('Submission').'
                    set
                        subject = :subject,
                        description = :description
                        file = :file,
                        thumbnail = :thumbnail
                    where
                        id = :id
                ');
                $q->execute([
                    'subject' => $this->subject,
                    'description' => $this->description,
                    'file' => $this->file,
                    'thumbnail' => $this->thumbnail,
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
                (subject, description, file, thumbnail, add_timestamp)
                values
                    (:subject, :description, :file, :thumbnail, :add_timestamp)
            ');

            $q->execute([
                'subject' => $this->subject,
                'description' => $this->description,
                'file' => $this->file,
                'thumbnail' => $this->thumbnail,
                'add_timestamp' => $this->added
            ]);

            $this->id = (int) Database::$db->lastInsertId();
            $this->in_sync = true;
            return self::ADDED;
        } catch (PDOException $e) {
            return self::FAILED;
        }
    }

    public function getSubject(): string|null
    {
        return $this->subject;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }
    
    public function getFilename(): string|null
    {
        return $this->file;
    }
    
    public function getThumbnailLocation(): string|null
    {
        return $this->thumbnail;
    }

    public function setSubject(string $subject): void
    {
        $this->in_sync = false;
        $this->subject = Util::sanitize(trim($subject));
    }

    public function setDescription(string $description): void
    {
        $this->in_sync = false;
        $this->description = Util::sanitize(trim($description));
    }
    
    public function setFilename(string $filename): void
    {
        if (!is_file($filename)) {
            throw new SubmissionException('File not found');
        }
        $finfo = new \finfo();
        $image_mime = $finfo->file($filename, FILEINFO_MIME_TYPE);
        
        if (!in_array($image_mime, SKETCHSPACE_SUPPORTED_MIMETYPES)) {
            throw new SubmissionException('Unsupported MIME type');
        }
        $this->file = $filename;
    }
    
    public function setThumbnailLocation(string $filename): void
    {
        if (!is_file($filename)) {
            throw new SubmissionException('File not found');
        }
        $finfo = new \finfo();
        $image_mime = $finfo->file($filename, FILEINFO_MIME_TYPE);
        
        if ($image_mime != 'image/jpeg') {
            throw new SubmissionException('Thumbnail is not a JPEG');
        }
        $this->thumbnail = $filename;
    }
}
