<?php

use dokuwiki\Extension\Plugin;

/**
 * Move Plugin File Mover
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Hamann <michael@content-space.de>
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Class helper_plugin_move_file
 *
 * This helps with moving files from one folder to another. It simply matches files and moves them
 * arround. No fancy rewriting happens here.
 */
class helper_plugin_move_file extends Plugin
{
    /**
     * Move the meta files of a page
     *
     * @param string $src_ns   The original namespace
     * @param string $src_name The original basename of the moved doc (empty for namespace moves)
     * @param string $dst_ns   The namespace after the move
     * @param string $dst_name The basename after the move (empty for namespace moves)
     * @return bool If the meta files were moved successfully
     */
    public function movePageMeta($src_ns, $src_name, $dst_ns, $dst_name)
    {
        global $conf;

        $regex = '\.[^.]+';
        return $this->execute($conf['metadir'], $src_ns, $src_name, $dst_ns, $dst_name, $regex);
    }

    /**
     * Move the old revisions of a page
     *
     * @param string $src_ns   The original namespace
     * @param string $src_name The original basename of the moved doc (empty for namespace moves)
     * @param string $dst_ns   The namespace after the move
     * @param string $dst_name The basename after the move (empty for namespace moves)
     * @return bool If the attic files were moved successfully
     */
    public function movePageAttic($src_ns, $src_name, $dst_ns, $dst_name)
    {
        global $conf;

        $regex = '\.\d+\.txt(?:\.gz|\.bz2)?';
        return $this->execute($conf['olddir'], $src_ns, $src_name, $dst_ns, $dst_name, $regex);
    }

    /**
     * Move the meta files of the page that is specified in the options.
     *
     * @param string $src_ns   The original namespace
     * @param string $src_name The original basename of the moved doc (empty for namespace moves)
     * @param string $dst_ns   The namespace after the move
     * @param string $dst_name The basename after the move (empty for namespace moves)
     * @return bool If the meta files were moved successfully
     */
    public function moveMediaMeta($src_ns, $src_name, $dst_ns, $dst_name)
    {
        global $conf;

        $regex = '\.[^.]+';
        return $this->execute($conf['mediametadir'], $src_ns, $src_name, $dst_ns, $dst_name, $regex);
    }

    /**
     * Move the old revisions of the media file that is specified in the options
     *
     * @param string $src_ns   The original namespace
     * @param string $src_name The original basename of the moved doc (empty for namespace moves)
     * @param string $dst_ns   The namespace after the move
     * @param string $dst_name The basename after the move (empty for namespace moves)
     * @return bool If the attic files were moved successfully
     */
    public function moveMediaAttic($src_ns, $src_name, $dst_ns, $dst_name)
    {
        global $conf;

        $ext = mimetype($src_name);
        if ($ext[0] !== false) {
            $name = substr($src_name, 0, -1 * strlen($ext[0]) - 1);
        } else {
            $name = $src_name;
        }
        $newext = mimetype($dst_name);
        if ($newext[0] !== false) {
            $newname = substr($dst_name, 0, -1 * strlen($newext[0]) - 1);
        } else {
            $newname = $dst_name;
        }
        $regex = '\.\d+\.' . preg_quote((string) $ext[0], '/');

        return $this->execute($conf['mediaolddir'], $src_ns, $name, $dst_ns, $newname, $regex);
    }

    /**
     * Moves the subscription file for a namespace
     *
     * @param string $src_ns
     * @param string $dst_ns
     * @return bool
     */
    public function moveNamespaceSubscription($src_ns, $dst_ns)
    {
        global $conf;

        $regex = '\.mlist';
        return $this->execute($conf['metadir'], $src_ns, '', $dst_ns, '', $regex);
    }

    /**
     * Executes the move op
     *
     * @param string $dir      The root path of the files (e.g. $conf['metadir'] or $conf['olddir']
     * @param string $src_ns   The original namespace
     * @param string $src_name The original basename of the moved doc (empty for namespace moves)
     * @param string $dst_ns   The namespace after the move
     * @param string $dst_name The basename after the move (empty for namespace moves)
     * @param string $extregex Regular expression for matching the extension of the file that shall be moved
     * @return bool If the files were moved successfully
     */
    protected function execute($dir, $src_ns, $src_name, $dst_ns, $dst_name, $extregex)
    {
        $old_path = $dir;
        if ($src_ns != '') $old_path .= '/' . utf8_encodeFN(str_replace(':', '/', $src_ns));
        $new_path = $dir;
        if ($dst_ns != '') $new_path .= '/' . utf8_encodeFN(str_replace(':', '/', $dst_ns));
        $regex = '/^' . preg_quote(utf8_encodeFN($src_name)) . '(' . $extregex . ')$/u';

        if (!is_dir($old_path)) return true; // no media files found

        $dh = @opendir($old_path);
        if ($dh) {
            while (($file = readdir($dh)) !== false) {
                if ($file == '.' || $file == '..') continue;
                $match = [];
                if (is_file($old_path . '/' . $file) && preg_match($regex, $file, $match)) {
                    if (!is_dir($new_path)) {
                        if (!io_mkdir_p($new_path)) {
                            msg('Creating directory ' . hsc($new_path) . ' failed.', -1);
                            return false;
                        }
                    }
                    if (!io_rename($old_path . '/' . $file, $new_path . '/' . utf8_encodeFN($dst_name . $match[1]))) {
                        msg('Moving ' . hsc($old_path . '/' . $file) . ' to ' . hsc($new_path . '/' . utf8_encodeFN($dst_name . $match[1])) . ' failed.', -1);
                        return false;
                    }
                }
            }
            closedir($dh);
        } else {
            msg('Directory ' . hsc($old_path) . ' couldn\'t be opened.', -1);
            return false;
        }
        return true;
    }
}
