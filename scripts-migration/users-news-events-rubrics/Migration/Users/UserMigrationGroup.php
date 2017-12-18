<?php

namespace Migration\Users;

use Migration\MigrationGroup;
use Migration\Users\UserDbResetter;

class UserMigrationGroup extends MigrationGroup {

  function __construct($wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress) {
    $userDbResetter = new UserDbResetter($bdWordpress, $wpTablePrefix);
    Parent::__construct([
      new UserMigration(
        $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress),
      new UserMetaMigration(
        $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress),
      new UserProfileMigration(
        $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress),
      new UserActivityMigration(
        $wpTablePrefix, $bdSpip, $bdTelaProd, $bdWordpress)
    ], $userDbResetter);

  }

}
