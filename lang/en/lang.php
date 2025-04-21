<?php
/**
 * english language file
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Gary Owen <>
 */

$lang['menu']       = 'Move pages and namespaces';
$lang['inprogress'] = '(move pending)';
$lang['treelink']   = 'Alternatively to this simple form you can manage complex restructuring of your wiki using the <a href="%s">tree-based move manager</a>.';

// page errors
$lang['notexist']      = 'The page %s does not exist';
$lang['norights']      = 'You have insufficient permissions to edit %s.';
$lang['filelocked']    = 'The page %s is locked. Try again later.';
$lang['notchanged']    = 'No new destination given for page %s (location unchanged).';
$lang['exists']        = 'Page %s can\'t be moved to %s, the target already exists.';
$lang['notargetperms'] = 'You don\'t have the permission to create the page %s.';

// media errors
$lang['medianotexist']      = 'The media file %s does not exist';
$lang['nomediarights']      = 'You have insufficient permissions to delete %s.';
$lang['medianotchanged']    = 'No new destination given for page %s (location unchanged).';
$lang['mediaexists']        = 'Media %s can\'t be moved to %s, the target already exists.';
$lang['nomediatargetperms'] = 'You don\'t have the permission to create the media file %s.';
$lang['extensionchange']    = 'Extension of the new file is not the same as the original.';

// system errors
$lang['indexerror']          = 'Error while updating the search index %s';
$lang['metamoveerror']       = 'The meta files of page %s couldn\'t be moved';
$lang['atticmoveerror']      = 'The attic files of page %s couldn\'t be moved. Please move them manually.';
$lang['mediametamoveerror']  = 'The meta files of the media file %s couldn\'t be moved';
$lang['mediamoveerror']      = 'Moving the media file %s failed';
$lang['mediaatticmoveerror'] = 'The attic files of media file %s couldn\'t be moved. Please move them manually.';

// changelog summaries
$lang['renamed']     = 'Page name changed from %s to %s';
$lang['moved']       = 'Page moved from %s to %s';
$lang['move_rename'] = 'Page moved and renamed from %s to %s';
$lang['delete']      = 'Deleted by move plugin';
$lang['linkchange']  = 'Links adapted because of a move operation';

// progress view
$lang['intro']        = 'The move operation has not been started, yet!';
$lang['preview']      = 'Preview changes to be executed.';
$lang['inexecution']  = 'A previous move was not completed - use the buttons below to continue or abort the execution.';
$lang['btn_start']    = 'Start';
$lang['btn_continue'] = 'Continue';
$lang['btn_retry']    = 'Retry item';
$lang['btn_skip']     = 'Skip item';
$lang['btn_abort']    = 'Abort';

// Form labels
$lang['legend']               = 'Move current page or namespace';
$lang['movepage']             = 'Move page';
$lang['movens']               = 'Move namespace';
$lang['dst']                  = 'New name:';
$lang['content_to_move']      = 'Content to move:';
$lang['autoskip']             = 'Ignore errors and skip pages or files that can\'t be moved';
$lang['autorewrite']          = 'Rewrite links right after the move completed';
$lang['move_pages']           = 'Pages';
$lang['move_media']           = 'Media files';
$lang['move_media_and_pages'] = 'Pages and media files';
$lang['nodst']                = 'No new name given';
$lang['noaction']             = 'There were no moves defined';

// Rename feature
$lang['renamepage']       = 'Rename Page';
$lang['cantrename']       = 'The page can\'t be renamed right now. Please try later.';
$lang['notallowed']       = 'You are not allowed to rename pages or media.';
$lang['js']['rename']     = 'Rename';
$lang['js']['cancel']     = 'Cancel';
$lang['js']['newname']    = 'New name:';
$lang['js']['inprogress'] = 'renaming page and adjusting links...';
$lang['js']['complete']   = 'Move operation finished.';

// Tree Manager
$lang['root']             = '[Root namespace]';
$lang['noscript']         = 'This feature requires JavaScript';
$lang['moveinprogress']   = 'There is another move operation in progress currently, you can\'t use this tool right now.';
$lang['js']['renameitem'] = 'Rename this item';
$lang['js']['add']        = 'Create a new namespace';
$lang['js']['duplicate']  = 'Sorry, "%s" already exists in this namespace.';

// Media Manager
$lang['js']['moveButton'] = 'Move file';
$lang['js']['dialogIntro'] = 'Enter new file destination. You may change the namespace but not the file extension.';
