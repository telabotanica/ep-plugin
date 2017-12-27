<?php

namespace Migration\Api;

/**
 * Migrates data between the source and target DB.
 */
interface Migration {

  public function migrate();

}
