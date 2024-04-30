<?php

/**
 * DokuWiki Plugin move (Helper Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 */

class remote_plugin_move extends DokuWiki_Remote_Plugin
{
    /**
     * Rename a Wiki page using XMLRPC.
     *
     * @param string $fromId
     * @param string $toId
     * @return int
     */
    public function renamePage(string $fromId, string $toId)
    {
        // Other RPC methods also implicitly sanitze the name, so ...
        $fromId = cleanID($fromId);
        $toId = cleanID($toId);

        /** @var helper_plugin_move_op $MoveOperator */
        $moveOperator = plugin_load('helper', 'move_op');
        $moveAction = plugin_load('action', 'move_rename');

        if (!$moveAction->renameOkay($fromId)) {
            return 0;
        }

        if (!$moveOperator->movePage($fromId, $toId)) {
            return 0;
        }

        return 1;
    }

    /**
     * Rename a Wiki media file using XMLRPC.
     *
     * @param string $fromId
     * @param string $toId
     * @return int
     */
    public function renameMedia(string $fromId, string $toId)
    {
        // Other RPC methods also implicitly sanitze the name, so ...
        $fromId = cleanID($fromId);
        $toId = cleanID($toId);

        /** @var helper_plugin_move_op $MoveOperator */
        $moveOperator = plugin_load('helper', 'move_op');

        if (!$moveOperator->moveMedia($fromId, $toId)) {
            return 0;
        }

        return 1;
    }
}
