<?php declare(strict_types = 1);

namespace Sketchspace\Enum;

/**
 * Describes the names of relation tables.
 *
 * Object tables are prefixed with T_, relation tables are prefixed with R_.
 */
class Queries
{
    public const R_SUBMISSION_USER = '_submission_users';
    public const R_SUBMISSION_SUBCATEGORY = '_submission_subcategories';
    public const R_SUBCATEGORY_PARENT = '_subcategory_parents';
}
