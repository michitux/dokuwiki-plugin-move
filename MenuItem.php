<?php

namespace dokuwiki\plugin\move;

use dokuwiki\Menu\Item\AbstractItem;

/**
 * Class MenuItem
 *
 * Implements the Rename button for DokuWiki's menu system
 *
 * @package dokuwiki\plugin\move
 */
class MenuItem extends AbstractItem
{
    /** @var string icon file */
    protected $svg = __DIR__ . '/images/rename.svg';

    protected $type = "plugin_move";

    public function getLinkAttributes($classprefix = 'menuitem ')
    {
        $attr = parent::getLinkAttributes($classprefix);
        if (empty($attr['class'])) {
            $attr['class'] = '';
        }
        $attr['class'] .= ' plugin_move_page ';
        return $attr;
    }
    /**
     * Get label from plugin language file
     *
     * @return string
     */
    public function getLabel()
    {
        $hlp = plugin_load('action', 'move_rename');
        return $hlp->getLang('renamepage');
    }
}
