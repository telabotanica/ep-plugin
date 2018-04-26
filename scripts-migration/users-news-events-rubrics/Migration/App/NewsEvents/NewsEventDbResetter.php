<?php

namespace Migration\App\NewsEvents;

use Migration\Api\AbstractDbResetter;
use Migration\Api\QueryDbResetter;
use \Exception;

/**
 * Deletes all posts (along with metas and slug histories)
 * corresponding to previously imported news and events.
 */
class NewsEventDbResetter extends QueryDbResetter {

    public function __construct($dbName) {
        parent::__construct($dbName);
        parent::setQueries([
          'DELETE postmeta FROM postmeta JOIN posts ON (posts.ID = postmeta.post_id AND posts.post_type = "post")',
          'DELETE slug_history FROM slug_history JOIN posts ON (posts.ID = slug_history.post_id AND posts.post_type = "post")',
          'DELETE icl_translations FROM icl_translations JOIN posts ON (posts.ID = icl_translations.element_id AND posts.post_type = "post")',
          'DELETE posts FROM posts p1 JOIN posts p2 ON (p1.post_parent = p2.ID AND p2.post_type = "post")',
        ]);
    }

}
