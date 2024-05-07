<?php

use dokuwiki\Extension\AdminPlugin;

class admin_plugin_move_tree extends AdminPlugin
{
    public const TYPE_PAGES = 1;
    public const TYPE_MEDIA = 2;

    /**
     * @param $language
     * @return bool
     */
    public function getMenuText($language)
    {
        return false; // do not show in Admin menu
    }


    /**
     * If this admin plugin is for admins only
     *
     * @return bool false
     */
    public function forAdminOnly()
    {
        return false;
    }

    /**
     * no-op
     */
    public function handle()
    {
    }

    public function html()
    {
        global $ID;

        echo $this->locale_xhtml('tree');

        echo '<noscript><div class="error">' . $this->getLang('noscript') . '</div></noscript>';

        echo '<div id="plugin_move__tree">';

        echo '<div class="tree_root tree_pages">';
        echo '<h3>' . $this->getLang('move_pages') . '</h3>';
        $this->htmlTree(self::TYPE_PAGES);
        echo '</div>';

        echo '<div class="tree_root tree_media">';
        echo '<h3>' . $this->getLang('move_media') . '</h3>';
        $this->htmlTree(self::TYPE_MEDIA);
        echo '</div>';

        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');
        echo '<div class="controls">';
        if ($plan->isCommited()) {
            echo '<div class="error">' . $this->getLang('moveinprogress') . '</div>';
        } else {
            $form = new Doku_Form(['action' => wl($ID), 'id' => 'plugin_move__tree_execute']);
            $form->addHidden('id', $ID);
            $form->addHidden('page', 'move_main');
            $form->addHidden('json', '');
            $form->addElement(form_makeCheckboxField('autoskip', '1', $this->getLang('autoskip'), '', '', ($this->getConf('autoskip') ? ['checked' => 'checked'] : [])));
            $form->addElement('<br />');
            $form->addElement(form_makeCheckboxField('autorewrite', '1', $this->getLang('autorewrite'), '', '', ($this->getConf('autorewrite') ? ['checked' => 'checked'] : [])));
            $form->addElement('<br />');
            $form->addElement('<br />');
            $form->addElement(form_makeButton('submit', 'admin', $this->getLang('btn_start')));
            $form->printForm();
        }
        echo '</div>';

        echo '</div>';
    }

    /**
     * print the HTML tree structure
     *
     * @param int $type
     */
    protected function htmlTree($type = self::TYPE_PAGES)
    {
        $data = $this->tree($type);

        // wrap a list with the root level around the other namespaces
        array_unshift(
            $data,
            ['level' => 0, 'id' => '*', 'type' => 'd', 'open'  => 'true', 'label' => $this->getLang('root')]
        );
        echo html_buildlist(
            $data,
            'tree_list idx',
            [$this, 'html_list'],
            [$this, 'html_li']
        );
    }

    /**
     * Build a tree info structure from media or page directories
     *
     * @param int    $type
     * @param string $open The hierarchy to open FIXME not supported yet
     * @param string $base The namespace to start from
     * @return array
     */
    public function tree($type = self::TYPE_PAGES, $open = '', $base = '')
    {
        global $conf;

        utf8_encodeFN(str_replace(':', '/', $open));
        $basedir = utf8_encodeFN(str_replace(':', '/', $base));

        $opts = ['pagesonly'  => ($type == self::TYPE_PAGES), 'listdirs'   => true, 'listfiles'  => true, 'sneakyacl'  => $conf['sneaky_index'], 'showmsg'    => false, 'depth'      => 1, 'showhidden' => true];

        $data = [];
        if ($type == self::TYPE_PAGES) {
            search($data, $conf['datadir'], 'search_universal', $opts, $basedir);
        } elseif ($type == self::TYPE_MEDIA) {
            search($data, $conf['mediadir'], 'search_universal', $opts, $basedir);
        }

        return $data;
    }

    /**
     * Item formatter for the tree view
     *
     * User function for html_buildlist()
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     */
    public function html_list($item)
    {
        $ret = '';
        // what to display
        if (!empty($item['label'])) {
            $base = $item['label'];
        } else {
            $base = ':' . $item['id'];
            $base = substr($base, strrpos($base, ':') + 1);
        }

        if ($item['id'] == '*') $item['id'] = '';

        if ($item['id']) {
            $ret .= '<input type="checkbox" /> ';
        }

        // namespace or page?
        if ($item['type'] == 'd') {
            $ret .= '<a href="' . $item['id'] . '" class="idx_dir">';
            $ret .= $base;
            $ret .= '</a>';
        } else {
            $ret .= '<a class="wikilink1">';
            $ret .= noNS($item['id']);
            $ret .= '</a>';
        }

        if ($item['id']) $ret .= '<img class="rename" src="' . DOKU_BASE . 'lib/plugins/move/images/rename.png" />';
        else $ret .= '<img class="add" src="' . DOKU_BASE . 'lib/plugins/move/images/folder_add.png" />';

        return $ret;
    }

    /**
     * print the opening LI for a list item
     *
     * @param array $item
     * @return string
     */
    public function html_li($item)
    {
        if ($item['id'] == '*') $item['id'] = '';

        $params          = [];
        $params['class'] = ' type-' . $item['type'];
        if ($item['type'] == 'd') $params['class'] .= ' ' . ($item['open'] ? 'open' : 'closed');
        $params['data-name']   = noNS($item['id']);
        $params['data-id']     = $item['id'];
        $attr                  = buildAttributes($params);

        return  "<li $attr>";
    }
}
