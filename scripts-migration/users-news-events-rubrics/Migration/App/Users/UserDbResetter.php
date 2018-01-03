<?php

namespace Migration\App\Users;

use Migration\Api\QueryDbResetter;
use \Exception;

// vide la table users, sauf le n°1 (admin)
// vide la table usermeta (si user_id != 1) (mais)
// vide les tables bp_activity, bp_xprofile_data
class UserDbResetter extends QueryDbResetter {

    public function __construct($dbName) {
        parent::__construct($dbName);
        parent::setQueries([
          'DELETE FROM users WHERE ID != 1',
          'DELETE FROM usermeta WHERE user_id != 1',
          'DELETE FROM bp_activity WHERE user_id != 1',
          'DELETE FROM bp_xprofile_data WHERE user_id != 1'
        ]);
    }

}
