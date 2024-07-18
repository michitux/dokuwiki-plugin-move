<?php

class admin_plugin_move_tree extends DokuWiki_Admin_Plugin
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
        global $INPUT;
        echo $this->locale_xhtml('tree');

        $dual = $INPUT->bool('dual', $this->getConf('dual'));

        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');
        if ($plan->isCommited()) {
            echo '<div class="error">' . $this->getLang('moveinprogress') . '</div>';
        } else {
            echo '<noscript><div class="error">' . $this->getLang('noscript') . '</div></noscript>';

            echo '<ul class="tabs">';
            foreach ([1, 0] as $set) {
                echo '<li>';
                if ($set == $dual) {
                    echo '<strong>';
                    echo $this->getLang('dual' . $set);
                    echo '</strong>';
                } else {
                    echo '<a href="' . wl($ID, ['do' => 'admin', 'page' => 'move_tree', 'dual' => $set]) . '">';
                    echo $this->getLang('dual' . $set);
                    echo '</a>';
                }
                echo '</li>';
            }
            echo '</ul>';

            echo '<div id="plugin_move__tree">';
            echo '<div class="trees">';
            if ($dual) {
                echo '<ul class="tree_root move-pages move-ns" data-id="" data-orig=""></ul>';
                echo '<ul class="tree_root move-media move-ns" data-id="" data-orig=""></ul>';
            } else {
                echo '<ul class="tree_root move-pages move-media move-ns" data-id="" data-orig=""></ul>';
            }
            echo '</div>';


            $form = new dokuwiki\Form\Form(['method' => 'post']);
            $form->setHiddenField('page', 'move_main');

            $cb = $form->addCheckbox('autoskip', $this->getLang('autoskip'));
            if ($this->getConf('autoskip')) $cb->attr('checked', 'checked');

            $cb = $form->addCheckbox('autorewrite', $this->getLang('autorewrite'));
            if ($this->getConf('autorewrite')) $cb->attr('checked', 'checked');

            $form->addButton('submit', $this->getLang('btn_start'));
            echo $form->toHTML();
            echo '</div>';
        }
    }

    /**
     * Build a tree info structure from media or page directories
     *
     * @param int $type
     * @param string $open The hierarchy to open FIXME not supported yet
     * @param string $base The namespace to start from
     * @return array
     */
    public function tree($type = self::TYPE_PAGES, $open = '', $base = '')
    {
        global $conf;

        $opendir = utf8_encodeFN(str_replace(':', '/', $open));
        $basedir = utf8_encodeFN(str_replace(':', '/', $base));

        $opts = array(
            'pagesonly' => ($type == self::TYPE_PAGES),
            'listdirs' => true,
            'listfiles' => true,
            'sneakyacl' => $conf['sneaky_index'],
            'showmsg' => false,
            'depth' => 1,
            'showhidden' => true
        );

        $data = array();
        if ($type == self::TYPE_PAGES) {
            search($data, $conf['datadir'], 'search_universal', $opts, $basedir);
        } elseif ($type == self::TYPE_MEDIA) {
            search($data, $conf['mediadir'], 'search_universal', $opts, $basedir);
        }

        return $data;
    }
}
