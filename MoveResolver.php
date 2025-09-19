<?php

namespace dokuwiki\plugin\move;

use dokuwiki\File\Resolver;

/**
 * We need the "pure" resolver without any special handling for startpages or autoplurals
 */
class MoveResolver extends Resolver {
}
