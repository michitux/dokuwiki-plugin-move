<?php

/**
 * DokuWiki Plugin move (Remote Component)
 *
 * @license GPL 2 http://www.gnu.org/licenses/gpl-2.0.html
 * @author Claus-Justus Heine <himself@claus-justus-heine.de>
 */
class remote_plugin_move extends DokuWiki_Remote_Plugin
{
    /**
     * Rename/move a given page
     *
     * @param string $fromId The original page ID
     * @param string $toId The new page ID
     * @return true Always true when no error occured
     * @throws \dokuwiki\Remote\RemoteException when renaming fails
     */
    public function renamePage(string $fromId, string $toId)
    {
        $fromId = cleanID($fromId);
        $toId = cleanID($toId);

        /** @var helper_plugin_move_op $MoveOperator */
        $MoveOperator = plugin_load('helper', 'move_op');

        global $MSG;
        $MSG = [];
        if (!$MoveOperator->movePage($fromId, $toId)) {
            throw $this->msgToException($MSG);
        }

        return true;
    }

    /**
     * Rename/move a given media file
     *
     * @param string $fromId The original media ID
     * @param string $toId The new media ID
     * @return true Always true when no error occured
     * @throws \dokuwiki\Remote\RemoteException when renaming fails
     */
    public function renameMedia(string $fromId, string $toId)
    {
        $fromId = cleanID($fromId);
        $toId = cleanID($toId);

        /** @var helper_plugin_move_op $MoveOperator */
        $MoveOperator = plugin_load('helper', 'move_op');

        global $MSG;
        $MSG = [];
        if (!$MoveOperator->moveMedia($fromId, $toId)) {
            throw $this->msgToException($MSG);
        }

        return true;
    }

    /**
     * Get an exception for the first error message found in the DokuWiki message array.
     *
     * Ideally the move operation should throw an exception, but currently only a return code is available.
     *
     * @param array $messages The DokuWiki message array
     * @return \dokuwiki\Remote\RemoteException
     */
    protected function msgToException($messages)
    {
        foreach ($messages as $msg) {
            if ($msg['lvl'] === -1) {
                // error found return it
                return new \dokuwiki\Remote\RemoteException($msg['msg'], 100);
            }
        }
        // If we reach this point, no error was found
        return new \dokuwiki\Remote\RemoteException('Unknown error', 100);
    }
}
