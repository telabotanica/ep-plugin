<?php

namespace Migration\Api;

/**
 * Resets the target DB between migrations.
 */
interface DbResetter {

  /**
   * Resets the target DB between migrations.
   */
  public function resetDb();

}
