<?php declare(strict_types = 1);

namespace Sketchspace\Enum;

/**
 * Describes possible roles the user can take.
 */
class UserRoles
{
    public const USER = 0;
    public const PRO  = 10;
    public const CURATOR = 20;
    public const ADMIN = 9999;
}
