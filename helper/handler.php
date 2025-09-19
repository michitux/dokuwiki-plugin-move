<?php
/**
 * Move Plugin Rewriting Handler
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Hamann <michael@content-space.de>
 */

use dokuwiki\Extension\Plugin;
use dokuwiki\File\MediaResolver;
use dokuwiki\File\PageResolver;
use dokuwiki\Logger;
use dokuwiki\plugin\move\MoveResolver;

/**
 * Handler class for move. It does the actual rewriting of the content.
 *
 * It is not really a helper plugin.
 *
 * This should theoretically be a descendant of Doku_Handler. This would require to implement and overwrite
 * all the mode handler methods, which would probably be the cleaner approach. For now we pretend to be a
 * Doku_Handler, implement only the methods we need and use __call() to catch all the rest.
 *
 * Since Parser does not like that and expects a Doku_Handler in the constructor, we use reflection to
 * inject our fake handler into the Parser in helper_plugin_move_rewrite:rewrite()
 *
 * This is not really nice, but it works. It has at leas the advantage that we would not need to update this
 * class when a new handler is introduced - though that hasn't happened in literally decades.
 *
 * The other hack we're doing here is to directly append (rewritten) matches to $wikitext. This makes it easy
 * to basically keep the syntax as is and only change the IDs. A more proper approach would be create
 * calls, then reverse-render them in the end. This can lead to slight differences between the original syntax
 * and the reverse-rendered one, e.g. where syntax is somewhat ambiguous
 *
 * @todo At least do the right thing an make this a proper Doku_Handler descendant even when we keep the wikitext
 */
class helper_plugin_move_handler extends Plugin {
    public $calls = [];

    /**
     * @var string This handler does not create calls, but rather recreates wiki text in one go.
     */
    public $wikitext = '';

    protected $id;
    protected $ns;
    protected $origID;
    protected $origNS;
    protected $page_moves;
    protected $media_moves;
    protected $handlers;


    /**
     * Initialize the move handler.
     *
     * @param string $id          The id of the text that is passed to the handler
     * @param string $original    The name of the original ID of this page. Same as $id if this page wasn't moved
     * @param array  $page_moves  Moves that shall be considered in the form [[$old,$new],...] ($old can be $original)
     * @param array  $media_moves Moves of media files that shall be considered in the form $old => $new
     * @param array  $handlers    Handlers for plugin content in the form $plugin_name => $callback
     */
    public function init($id, $original, $page_moves, $media_moves, $handlers) {
        $this->id          = $id;
        $this->ns          = getNS($id);
        $this->origID      = $original;
        $this->origNS      = getNS($original);
        $this->page_moves  = $page_moves;
        $this->media_moves = $media_moves;
        $this->handlers    = $handlers;
    }

    /**
     * Go through the list of moves and find the new value for the given old ID
     *
     * @param string $old  the old, full qualified ID
     * @param string $type 'media' or 'page'
     * @throws \Exception on bad argument
     * @return string the new full qualified ID
     */
    public function resolveMoves($old, $type) {
        global $conf;

        if($type != 'media' && $type != 'page') throw new \Exception('Not a valid type');

        $resolver = new MoveResolver($this->origID);
        $old = $resolver->resolveId($old);

        if($type == 'page') {
            // FIXME this simply assumes that the link pointed to :$conf['start'], but it could also point to another page
            // resolve_pageid does a lot more here, but we can't really assume this as the original pages might have been
            // deleted already
            if(substr($old, -1) === ':' || $old === '') $old .= $conf['start'];

            $moves = $this->page_moves;
        } else {
            $moves = $this->media_moves;
        }

        $old = cleanID($old);

        foreach($moves as $move) {
            if($move[0] == $old) {
                $old = $move[1];
            }
        }

        return $old; // this is now new
    }

    /**
     * if the old link ended with a colon and the new one is a start page, adjust
     *
     * @param $relold string the old, possibly relative ID
     * @param $new    string the new, full qualified ID
     * @param $type   'media' or 'page'
     * @return string
     */
    protected function _nsStartCheck($relold, $new, $type) {
        global $conf;
        if($type == 'page' && substr($relold, -1) == ':') {
            $len = strlen($conf['start']);
            if($new == $conf['start']) {
                $new = '.:';
            } else if(substr($new, -1 * ($len + 1)) == ':' . $conf['start']) {
                $new = substr($new, 0, -1 * $len);
            }
        }
        return $new;
    }

    /**
     * Construct a new ID relative to the current page's location
     *
     * Uses a relative link only if the original was relative, too. This function is for
     * pages and media files.
     *
     * @param string $relold the old, possibly relative ID
     * @param string $new    the new, full qualified ID
     * @param string $type 'media' or 'page'
     * @throws \Exception on bad argument
     * @return string
     */
    public function relativeLink($relold, $new, $type) {
        global $conf;
        if($type != 'media' && $type != 'page') throw new \Exception('Not a valid type');

        // first check if the old link still resolves
        $old    = $relold;
        if($type == 'page') {
            $resolver = new PageResolver($this->id);
        } else {
            $resolver = new MediaResolver($this->id);
        }
        $old = $resolver->resolveId($old);
        if($old == $new) {
            return $relold; // old link still resolves, keep as is
        }

        if($conf['useslash']) $relold = str_replace('/', ':', $relold);

        // check if the link was relative
        if(strpos($relold, ':') === false ||$relold[0] == '.') {
            $wasrel = true;
        } else {
            $wasrel = false;
        }

        // if it wasn't relative then, leave it absolute now, too
        if(!$wasrel) {
            if($this->ns && !getNS($new)) $new = ':' . $new;
            $new = $this->_nsStartCheck($relold, $new, $type);
            return $new;
        }

        // split the paths and see how much common parts there are
        $selfpath = explode(':', $this->ns);
        $goalpath = explode(':', getNS($new));
        $min      = min(count($selfpath), count($goalpath));
        for($common = 0; $common < $min; $common++) {
            if($selfpath[$common] != $goalpath[$common]) break;
        }

        // we now have the non-common part and a number of uppers
        $ups       = max(count($selfpath) - $common, 0);
        $remainder = array_slice($goalpath, $common);
        $upper     = $ups ? array_fill(0, $ups, '..:') : array();

        // build the new relative path
        $newrel = join(':', $upper);
        if($remainder) $newrel .= join(':', $remainder) . ':';
        $newrel .= noNS($new);
        $newrel = str_replace('::', ':', trim($newrel, ':'));
        if($newrel[0] != '.' && $this->ns && getNS($newrel)) $newrel = '.' . $newrel;

        // if the old link ended with a colon and the new one is a start page, adjust
        $newrel = $this->_nsStartCheck($relold,$newrel,$type);

        // don't use relative paths if it is ridicoulus:
        if(strlen($newrel) > strlen($new)) {
            $newrel = $new;
            if($this->ns && !getNS($new)) $newrel = ':' . $newrel;
            $newrel = $this->_nsStartCheck($relold,$newrel,$type);
        }

        return $newrel;
    }

    /**
     * Handle camelcase links
     *
     * @param string $match The text match
     * @param string $state The starte of the parser
     * @param int    $pos   The position in the input
     * @return bool If parsing should be continued
     */
    public function camelcaselink($match, $state, $pos) {
        $oldID = cleanID($this->origNS . ':' . $match);
        $newID = $this->resolveMoves($oldID, 'page');
        $newNS = getNS($newID);

        if($oldID == $newID || $this->origNS == $newNS) {
            // link is still valid as is
            $this->wikitext .= $match;
        } else {
            if(noNS($oldID) == noNS($newID)) {
                // only namespace changed, keep CamelCase in link
                $this->wikitext .= "[[$newNS:$match]]";
            } else {
                // all new, keep CamelCase in title
                $this->wikitext .= "[[$newID|$match]]";
            }
        }
        return true;
    }

    /**
     * Handle rewriting of internal links
     *
     * @param string $match The text match
     * @param string $state The starte of the parser
     * @param int    $pos   The position in the input
     * @return bool If parsing should be continued
     */
    public function internallink($match, $state, $pos) {
        // Strip the opening and closing markup
        $link = preg_replace(array('/^\[\[/', '/\]\]$/u'), '', $match);

        // Split title from URL
        $link = explode('|', $link, 2);
        if(!isset($link[1])) {
            $link[1] = null;
        } else if(preg_match('/^\{\{[^\}]+\}\}$/', $link[1])) {
            // If the title is an image, rewrite it
            $old_title = $link[1];
            $link[1]   = $this->rewrite_media($link[1]);
            // do a simple replace of the first match so really only the id is changed and not e.g. the alignment
            $oldpos = strpos($match, $old_title);
            $oldlen = strlen($old_title);
            $match  = substr_replace($match, $link[1], $oldpos, $oldlen);
        }
        $link[0] = trim($link[0]);

        //decide which kind of link it is

        if(preg_match('/^[a-zA-Z0-9\.]+>{1}.*$/u', $link[0])) {
            // Interwiki
            $this->wikitext .= $match;
        } elseif(preg_match('/^\\\\\\\\[^\\\\]+?\\\\/u', $link[0])) {
            // Windows Share
            $this->wikitext .= $match;
        } elseif(preg_match('#^([a-z0-9\-\.+]+?)://#i', $link[0])) {
            // external link (accepts all protocols)
            $this->wikitext .= $match;
        } elseif(preg_match('<' . PREG_PATTERN_VALID_EMAIL . '>', $link[0])) {
            // E-Mail (pattern above is defined in inc/mail.php)
            $this->wikitext .= $match;
        } elseif(preg_match('!^#.+!', $link[0])) {
            // local hash link
            $this->wikitext .= $match;
        } else {
            $id = $link[0];

            $hash  = '';
            $parts = explode('#', $id, 2);
            if(count($parts) === 2) {
                $id   = $parts[0];
                $hash = $parts[1];
            }

            $params = '';
            $parts  = explode('?', $id, 2);
            if(count($parts) === 2) {
                $id     = $parts[0];
                $params = $parts[1];
            }

            $new_id = $this->resolveMoves($id, 'page');
            $new_id = $this->relativeLink($id, $new_id, 'page');

            if($id == $new_id) {
                $this->wikitext .= $match;
            } else {
                if($params !== '') {
                    $new_id .= '?' . $params;
                }

                if($hash !== '') {
                    $new_id .= '#' . $hash;
                }

                if($link[1] != null) {
                    $new_id .= '|' . $link[1];
                }

                $this->wikitext .= '[[' . $new_id . ']]';
            }

        }

        return true;
    }

    /**
     * Handle rewriting of media links
     *
     * @param string $match The text match
     * @param string $state The starte of the parser
     * @param int    $pos   The position in the input
     * @return bool If parsing should be continued
     */
    public function media($match, $state, $pos) {
        $this->wikitext .= $this->rewrite_media($match);
        return true;
    }

    /**
     * Rewrite a media syntax
     *
     * @param string $match The text match of the media syntax
     * @return string The rewritten syntax
     */
    protected function rewrite_media($match) {
        $p = Doku_Handler_Parse_Media($match);
        if($p['type'] == 'internalmedia') { // else: external media

            $new_src = $this->resolveMoves($p['src'], 'media');
            $new_src = $this->relativeLink($p['src'], $new_src, 'media');

            if($new_src !== $p['src']) {
                // do a simple replace of the first match so really only the id is changed and not e.g. the alignment
                $srcpos = strpos($match, $p['src']);
                $srclen = strlen($p['src']);
                return substr_replace($match, $new_src, $srcpos, $srclen);
            }
        }
        return $match;
    }

    /**
     * Handle rewriting of plugin syntax, calls the registered handlers
     *
     * @param string $match      The text match
     * @param string $state      The starte of the parser
     * @param int    $pos        The position in the input
     * @param string $pluginname The name of the plugin
     * @return bool If parsing should be continued
     */
    public function plugin($match, $state, $pos, $pluginname) {
        if(isset($this->handlers[$pluginname])) {
            $this->wikitext .= call_user_func($this->handlers[$pluginname], $match, $state, $pos, $pluginname, $this);
        } else {
            $this->wikitext .= $match;
        }
        return true;
    }

    /**
     * Catchall handler for the remaining syntax
     *
     * @param string $name   Function name that was called
     * @param array  $params Original parameters
     * @return bool If parsing should be continue
     */
    public function __call($name, $params) {
        if(count($params) == 3) {
            $this->wikitext .= $params[0];
            return true;
        } else {
            Logger::error(
                'Error, handler function ' . hsc($name) . ' with ' . count($params) .
                ' parameters called which isn\'t implemented'
            );
            return false;
        }
    }

    public function finalize() {
        // remove padding that is added by the parser in parse()
        $this->wikitext = substr($this->wikitext, 1, -1);
    }

    /**
     * Get the rewritten wiki text
     *
     * @return string The rewritten wiki text
     */
    public function getWikiText()
    {
        return $this->wikitext;
    }
}
