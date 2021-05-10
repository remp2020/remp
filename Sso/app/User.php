<?php

namespace App;

/**
 * Fallback User class - former class was moved to \App\Models\User, but some User object
 * instances might have already been serialized to session (see AuthController)
 *
 * Therefore keep this object to correctly unserialize these objects
 * @package App
 */
class User extends \App\Models\User
{
}
