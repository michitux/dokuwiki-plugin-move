<?php

use dokuwiki\Extension\ActionPlugin;
use dokuwiki\Extension\EventHandler;
use dokuwiki\Extension\Event;

/**
 * Move Plugin Page Rewrite Functionality
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Class action_plugin_move_rewrite
 */
class action_plugin_move_rewrite extends ActionPlugin
{
    /**
     * Register event handlers.
     *
     * @param EventHandler $controller The plugin controller
     */
    public function register(EventHandler $controller)
    {
        $controller->register_hook('IO_WIKIPAGE_READ', 'AFTER', $this, 'handle_read', []);
        $controller->register_hook('PARSER_CACHE_USE', 'BEFORE', $this, 'handle_cache', []);
    }

    /**
     * Rewrite pages when they are read and they need to be updated.
     *
     * @param Event $event The event object
     * @param mixed      $param Optional parameters (not used)
     */
    public function handle_read(Event $event, $param)
    {
        global $ACT, $conf;
        static $stack = [];
        // handle only reads of the current revision
        if ($event->data[3]) return;

        // only rewrite if not in move already
        $save = true;
        if (helper_plugin_move_rewrite::isLocked()) {
            $save = false;
        }

        $id = $event->data[2];
        if ($event->data[1]) $id = $event->data[1] . ':' . $id;

        if (!$id) {
            // try to reconstruct the id from the filename
            $path = $event->data[0][0];
            if (strpos($path, (string) $conf['datadir']) === 0) {
                $path = substr($path, strlen($conf['datadir']) + 1);
                $id   = pathID($path);
            }
        }

        if (isset($stack[$id])) return;

        // Don't change the page when the user is currently changing the page content or the page is locked
        $forbidden_actions = ['save', 'preview', 'recover', 'revert'];
        if (
            (isset($ACT) && (
                    in_array($ACT, $forbidden_actions) || (is_array($ACT) && in_array(key($ACT), $forbidden_actions)
                    )))
            // checklock checks if the page lock hasn't expired and the page hasn't been locked by another user
            // the file exists check checks if the page is reported unlocked if a lock exists which means that
            // the page is locked by the current user
            || checklock($id) !== false || @file_exists(wikiLockFN($id))
        ) return;

        /** @var helper_plugin_move_rewrite $helper */
        $helper = plugin_load('helper', 'move_rewrite', true);
        if (!is_null($helper)) {
            $stack[$id]    = true;
            $event->result = $helper->rewritePage($id, $event->result, $save);
            unset($stack[$id]);
        }
    }

    /**
     * Handle the cache events, it looks if a page needs to be rewritten so it can expire the cache of the page
     *
     * @param Event $event The even object
     * @param mixed      $param Optional parameters (not used)
     */
    public function handle_cache(Event $event, $param)
    {
        global $conf;
        /** @var $cache cache_parser */
        $cache = $event->data;
        $id    = $cache->page;
        if (!$id) {
            // try to reconstruct the id from the filename
            $path = $cache->file;
            if (strpos($path, (string) $conf['datadir']) === 0) {
                $path = substr($path, strlen($conf['datadir']) + 1);
                $id   = pathID($path);
            }
        }
        if ($id) {
            /** @var helper_plugin_move_rewrite $helper */
            $helper = $this->loadHelper('move_rewrite');
            if (!is_null($helper)) {
                $meta = $helper->getMoveMeta($id);
                if ($meta && ($meta['pages'] || $meta['media'])) {
                    $file = wikiFN($id, '', false);
                    if (is_writable($file))
                        $cache->depends['purge'] = true;
                    else // FIXME: print error here or fail silently?
                        msg('Error: Page ' . hsc($id) . ' needs to be rewritten because of page renames but is not writable.', -1);
                }
            }
        }
    }
}
