<?php

namespace Migration\App\Users;

use Migration\Api\MigrationGroup;
use Migration\App\Users\UserDbResetter;
use Migration\App\Config\DbNamesEnum;

/**
 * Migrates user data (users, activities, profiles, metas)
 * from Tela DB to WP/BP DB.
 */
class UserMigrationGroup extends MigrationGroup {

  function __construct() {
    $userDbResetter = new UserDbResetter(DbNamesEnum::Wp);
    Parent::__construct([
      new UserMigration(),
      new UserMetaMigration(),
      new UserProfileMigration(),
      new UserActivityMigration()
    ], $userDbResetter);

  }

}
