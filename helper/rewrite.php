<?php
/**
 * Move Plugin Page Rewriter
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Hamann <michael@content-space.de>
 * @author     Gary Owen <gary@isection.co.uk>
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

use dokuwiki\Extension\Event;
use dokuwiki\Extension\Plugin;
use dokuwiki\Parsing\Parser;


/**
 * Class helper_plugin_move_rewrite
 *
 * This class handles the rewriting of wiki text to update the links
 */
class helper_plugin_move_rewrite extends Plugin {

    /**
     * Under what key is move data to be saved in metadata
     */
    const METAKEY = 'plugin_move';

    /**
     * What is they filename of the lockfile
     */
    const LOCKFILENAME = '_plugin_move.lock';

    /**
     * @var string symbol to make move operations easily recognizable in change log
     */
    public $symbol = '↷';

    /**
     * This function loads and returns the persistent metadata for the move plugin. If there is metadata for the
     * pagemove plugin (not the old one but the version that immediately preceeded the move plugin) it will be migrated.
     *
     * @param string $id The id of the page the metadata shall be loaded for
     * @return array|null The metadata of the page
     */
    public function getMoveMeta($id) {
        $all_meta = p_get_metadata($id, '', METADATA_DONT_RENDER);

        /* todo migrate old move data
        if(isset($all_meta['plugin_pagemove']) && !is_null($all_meta['plugin_pagemove'])) {
            if(isset($all_meta[self::METAKEY])) {
                $all_meta[self::METAKEY] = array_merge_recursive($all_meta['plugin_pagemove'], $all_meta[self::METAKEY]);
            } else {
                $all_meta[self::METAKEY] = $all_meta['plugin_pagemove'];
            }
            p_set_metadata($id, array(self::METAKEY => $all_meta[self::METAKEY], 'plugin_pagemove' => null), false, true);
        }
        */

        // discard missing or empty array or string
        $meta = !empty($all_meta[self::METAKEY]) ? $all_meta[self::METAKEY] : array();
        if(!isset($meta['origin'])) {
            $meta['origin'] = '';
        }
        if(!isset($meta['pages'])) {
            $meta['pages'] = array();
        }
        if(!isset($meta['media'])) {
            $meta['media'] = array();
        }

        return $meta;
    }

    /**
     * Remove any existing move meta data for the given page
     *
     * @param $id
     */
    public function unsetMoveMeta($id) {
        p_set_metadata($id, array(self::METAKEY => array()), false, true);
    }

    /**
     * Add info about a moved document to the metadata of an affected page
     *
     * @param string $id   affected page
     * @param string $src  moved document's original id
     * @param string $dst  moved document's new id
     * @param string $type 'media' or 'page'
     * @throws Exception on wrong argument
     */
    public function setMoveMeta($id, $src, $dst, $type) {
        $this->setMoveMetas($id, array($src => $dst), $type);
    }

    /**
     * Add info about several moved documents to the metadata of an affected page
     *
     * @param string $id    affected page
     * @param array  $moves list of moves (src is key, dst is value)
     * @param string $type  'media' or 'page'
     * @throws Exception
     */
    public function setMoveMetas($id, $moves, $type) {
        if($type != 'pages' && $type != 'media') {
            throw new Exception('wrong type specified');
        }
        if(!page_exists($id, '', false)) {
            return;
        }

        $meta = $this->getMoveMeta($id);
        foreach($moves as $src => $dst) {
            $meta[$type][] = array($src, $dst);
        }

        p_set_metadata($id, array(self::METAKEY => $meta), false, true);
    }

    /**
     * Store info about the move of a page in its own meta data
     *
     * This has to be called before the move is executed
     *
     * @param string $id moved page's original (and still current) id
     */
    public function setSelfMoveMeta($id) {
        $meta = $this->getMoveMeta($id);
        // was this page moved multiple times? keep the orignal name til rewriting occured
        if(isset($meta['origin']) && $meta['origin'] !== '') {
            return;
        }
        $meta['origin'] = $id;

        p_set_metadata($id, array(self::METAKEY => $meta), false, true);
    }

    /**
     * Check if rewrites may be executed within this process right now
     *
     * @return bool
     */
    public static function isLocked() {
        global $PLUGIN_MOVE_WORKING;
        global $conf;
        $lockfile = $conf['lockdir'] . '/' . self::LOCKFILENAME;
        return ((isset($PLUGIN_MOVE_WORKING) && $PLUGIN_MOVE_WORKING > 0) || file_exists($lockfile));
    }

    /**
     * Do not allow any rewrites in this process right now
     */
    public static function addLock() {
        global $PLUGIN_MOVE_WORKING;
        global $conf;
        $PLUGIN_MOVE_WORKING = $PLUGIN_MOVE_WORKING ? $PLUGIN_MOVE_WORKING + 1 : 1;
        $lockfile = $conf['lockdir'] . '/' . self::LOCKFILENAME;
        if (!file_exists($lockfile)) {
            io_savefile($lockfile, "1\n");
        } else {
            $stack = intval(file_get_contents($lockfile));
            ++$stack;
            io_savefile($lockfile, strval($stack));
        }
    }

    /**
     * Allow rerites in this process again, unless some other lock exists
     */
    public static function removeLock() {
        global $PLUGIN_MOVE_WORKING;
        global $conf;
        $PLUGIN_MOVE_WORKING = $PLUGIN_MOVE_WORKING ? $PLUGIN_MOVE_WORKING - 1 : 0;
        $lockfile = $conf['lockdir'] . '/' .self::LOCKFILENAME;
        if (!file_exists($lockfile)) {
            throw new Exception("removeLock failed: lockfile missing");
        } else {
            $stack = intval(file_get_contents($lockfile));
            if($stack === 1) {
                unlink($lockfile);
            } else {
                --$stack;
                io_savefile($lockfile, strval($stack));
            }
        }
    }

    /**
     * Allow rewrites in this process again.
     *
     * @author Michael Große <grosse@cosmocode.de>
     */
    public static function removeAllLocks() {
        global $conf;
        $lockfile = $conf['lockdir'] . '/' . self::LOCKFILENAME;
        if (file_exists($lockfile)) {
            unlink($lockfile);
        }
        unset($GLOBALS['PLUGIN_MOVE_WORKING']);
    }


    /**
     * Rewrite a text in order to fix the content after the given moves.
     *
     * @param string $id   The id of the wiki page, if the page itself was moved the old id
     * @param string $text The text to be rewritten
     * @return string        The rewritten wiki text
     */
    public function rewrite($id, $text) {
        $meta = $this->getMoveMeta($id);

        $handlers = array();
        $pages    = $meta['pages'];
        $media    = $meta['media'];
        $origin   = $meta['origin'];
        if($origin == '') $origin = $id;

        $data = array(
            'id'          => $id,
            'origin'      => &$origin,
            'pages'       => &$pages,
            'media_moves' => &$media,
            'handlers'    => &$handlers
        );

        /*
         * PLUGIN_MOVE_HANDLERS REGISTER event:
         *
         * Plugin handlers can be registered in the $handlers array, the key is the plugin name as it is given to the handler
         * The handler needs to be a valid callback, it will get the following parameters:
         * $match, $state, $pos, $pluginname, $handler. The first three parameters are equivalent to the parameters
         * of the handle()-function of syntax plugins, the $pluginname is just the plugin name again so handler functions
         * that handle multiple plugins can distinguish for which the match is. The last parameter is the handler object
         * which is an instance of helper_plugin_move_handle
         */
        Event::createAndTrigger('PLUGIN_MOVE_HANDLERS_REGISTER', $data);



        // Create the parser
        $Parser = new Parser(new Doku_Handler());
        $Handler = new helper_plugin_move_handler();
        $Handler->init($id, $origin, $pages, $media, $handlers);

        // Use reflectiion to actually use our own handler (see docs at MoveHandler)
        $reflectParser = new ReflectionClass(Parser::class);
        $handlerProperty = $reflectParser->getProperty('handler');
        $handlerProperty->setAccessible(true);
        $handlerProperty->setValue($Parser, $Handler);


        //add modes to parser
        $modes = p_get_parsermodes();
        foreach($modes as $mode) {
            $Parser->addMode($mode['mode'], $mode['obj']);
        }

        $Parser->parse($text);
        $new = $Handler->getWikiText();
        return $new;
    }

    /**
     * Rewrite the text of a page according to the recorded moves, the rewritten text is saved
     *
     * @param string      $id   The id of the page that shall be rewritten
     * @param string|null $text Old content of the page. When null is given the content is loaded from disk
     * @return string|bool The rewritten content, false on error
     */
    public function rewritePage($id, $text = null, $save = true) {
        $meta = $this->getMoveMeta($id);
        if(is_null($text)) {
            $text = rawWiki($id);
        }

        if($meta['pages'] || $meta['media']) {
            $old_text = $text;
            $text     = $this->rewrite($id, $text);

            $changed = ($old_text != $text);
            $file    = wikiFN($id, '', false);
            if ($save === true) {
                if(is_writable($file) || !$changed) {
                    if($changed) {
                        // Wait a second when the page has just been rewritten
                        $oldRev = filemtime(wikiFN($id));
                        if($oldRev == time()) sleep(1);

                        saveWikiText($id, $text, $this->symbol . ' ' . $this->getLang('linkchange'), $this->getConf('minor'));
                    }
                    $this->unsetMoveMeta($id);
                } else {
                    // FIXME: print error here or fail silently?
                    msg('Error: Page ' . hsc($id) . ' needs to be rewritten because of page renames but is not writable.', -1);
                    return false;
                }
            }
        }

        return $text;
    }

}
