<?php declare(strict_types = 1);

namespace Sketchspace\Object;

use PDO;
use PDOStatement;
use PDOException;
use DateTime;
use Sketchspace\Library\Database;
use Sketchspace\Enum\Queries;
use Sketchspace\Object\BasicObject;

/**
 * Corresponds to <Prefix>_category
 */
class Category implements BasicObject
{
    public const TABLE_NAME = '_category';

    private int $id;
    public string $name;
    public string|null $description;

    private bool $in_sync = false;

    public static function initTables(): int
    {
      // create categories table
      if (!Database::tableExists(Database::getTable('Category'))){
          Database::$db->query('
              create table '. Database::getTable('Category') . ' (
                  id int primary key auto_increment,
                  name varchar(80) not null,
                  description text
              )
          ');
          return self::INITIALIZED;
      }
      return self::NOT_MODIFIED;
    }

    public static function newCategory(string $name, string $description = null): Category
    {
        $category = new Category();

        $category->name = $name;
        $category->description = $description;
        return $category;
    }

    public static function fromId(int $id): Category|bool
    {
        $q = Database::$db->prepare('
            select * from '.Database::getTable('Category').'
            where id = :id
        ');

        $q->execute(['id'=>$id]);

        return Category::fromStatement($q);
    }

    public static function fromStatement(PDOStatement $statement): Category|bool
    {
        return
            $statement->fetchObject(__NAMESPACE__.'\Category');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function __set(mixed $name, mixed $value): void
    {
    }

    public function isObjectInSync(): bool
    {
        return $this->in_sync;
    }

    public function commitToDatabase(): int
    {
        if (isset($this->id)) {
            try {
                $q = Database::$db->prepare('
                    update '.Database::getTable('Category').'
                    set
                        name = :name,
                        description = :description
                    where
                        id = :id
                ');
                $q->execute([
                    'name' => $this->username,
                    'description' => $this->description
                ]);

                $this->in_sync = true;
                return self::UPDATED;
            } catch (PDOException $e) {
                return self::FAILED;
            }
        }

        try {
            $q = Database::$db->prepare('
                insert into '.Database::getTable('Category').'
                (name, description)
                values
                    (:name, :description)
            ');
            $q->execute([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->id = (int) Database::$db->lastInsertId();
            $this->in_sync = true;
            return self::ADDED;
        } catch (PDOException $e) {
            return self::FAILED;
        }
    }
}

/**
 * Corresponds to <Prefix>_subcategory
 */
class Subcategory implements BasicObject
{
    public const TABLE_NAME = '_subcategory';

    private int $id;
    public string $name;
    public string|null $description;

    private bool $in_sync = false;

    public static function initTables(): int
    {
      $tables_init = 0;
      // create subcategory table
      if (!Database::tableExists(Database::getTable('Subcategory'))){
          Database::$db->query('
              create table '. Database::getTable('Subcategory') . ' (
                  id int primary key auto_increment,
                  name varchar(80) not null,
                  description text
              )
          ');
          $tables_init += 1;
      }

      // create relations table
      if (!Database::tableExists(Database::$prefix . Queries::R_SUBCATEGORY_PARENT)){
          Database::$db->query('
              create table '. Database::$prefix . Queries::R_SUBCATEGORY_PARENT . ' (
                  subcategory_id int,
                  parent_category_id int,

                  foreign key (subcategory_id) references '. Database::getTable('Subcategory') .'(id),
                  foreign key (parent_category_id) references '. Database::getTable('Category') .'(id)
              )
          ');
          $tables_init += 1;
      }

      if ($tables_init > 0) {
          return self::INITIALIZED;
      }

      return self::NOT_MODIFIED;
    }

    public static function newSubcategory(string $name, string $description = null): Subcategory
    {
        $subcategory = new Subcategory();

        $subcategory->name = $name;
        $subcategory->description = $description;
        return $subcategory;
    }

    public static function fromId(int $id): Subcategory|bool
    {
        $q = Database::$db->prepare('
            select * from '.Database::getTable('Subcategory').'
            where id = :id
        ');

        $q->execute(['id'=>$id]);

        return Subcategory::fromStatement($q);
    }

    public static function fromStatement(PDOStatement $statement): Subcategory|bool
    {
        return
            $statement->fetchObject(__NAMESPACE__.'\Subcategory');
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function __set(mixed $name, mixed $value): void
    {
    }

    public function isObjectInSync(): bool
    {
        return $this->in_sync;
    }

    public function commitToDatabase(): int
    {
        if (isset($this->id)) {
            try {
                $q = Database::$db->prepare('
                    update '.Database::getTable('Subcategory').'
                    set
                        name = :name,
                        description = :description
                    where
                        id = :id
                ');
                $q->execute([
                    'name' => $this->username,
                    'description' => $this->description
                ]);

                $this->in_sync = true;
                return self::UPDATED;
            } catch (PDOException $e) {
                return self::FAILED;
            }
        }

        try {
            $q = Database::$db->prepare('
                insert into '.Database::getTable('Subcategory').'
                (name, description)
                values
                    (:name, :description)
            ');
            $q->execute([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            $this->id = (int) Database::$db->lastInsertId();
            $this->in_sync = true;
            return self::ADDED;
        } catch (PDOException $e) {
            return self::FAILED;
        }
    }

    public function makeChildOf(Category $category): void
    {
        /**
         * If we don't have an ID yet (no equiv. in database)
         * then bail out
         */
        if (!isset($this->id)) return;

        $q = Database::$db->prepare('
            select * from '.Database::$prefix.Queries::R_SUBCATEGORY_PARENT.'
            where subcategory_id=:id
        ');

        $q->execute(['id' => $this->id]);
        $relation_exists = $q->fetch();

        if ($relation_exists) { // update
            $q = Database::$db->prepare('
                update '.Database::$prefix.Queries::R_SUBCATEGORY_PARENT.'
                set
                    parent_category_id = :parent_category_id
                where
                    subcategory_id = :subcategory_id
            ');
        } else { // make new relation
            $q = Database::$db->prepare('
                insert into '.Database::$prefix.Queries::R_SUBCATEGORY_PARENT.'
                (subcategory_id, parent_category_id)
                values
                    (:subcategory_id, :parent_category_id)
            ');
        }
        $q->execute([
            'subcategory_id' => $this->id,
            'parent_category_id' => $category->getId()
        ]);
    }

    public function isChildOf(): Category|null
    {
        // bail out if we don't have an ID
        if (!isset($this->id)) return null;

        $q = Database::$db->prepare('
            select * from '.Database::$prefix.Queries::R_SUBCATEGORY_PARENT.'
            where subcategory_id=:id
        ');

        $q->execute(['id' => $this->id]);
        $relation_exists = $q->fetch();

        if ($relation_exists) { // update
            $q = Database::$db->prepare('
                select * from '.Database::getTable('Category').'
                where id=:id
            ');

            $q->execute([
                'id' => $relation_exists['parent_category_id']
            ]);

            return Category::fromStatement($q);
        }
        return null;
    }
}
