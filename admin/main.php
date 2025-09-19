<?php
/**
 * Plugin : Move
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Hamann <michael@content-space.de>
 * @author     Gary Owen,
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Admin component of the move plugin. Provides the user interface.
 */
class admin_plugin_move_main extends DokuWiki_Admin_Plugin {

    /** @var helper_plugin_move_plan $plan */
    protected $plan;

    public function __construct() {
        $this->plan = plugin_load('helper', 'move_plan');
    }

    /**
     * @param $language
     * @return string
     */
    public function getMenuText($language) {
        $label = $this->getLang('menu');
        if($this->plan->isCommited()) $label .= ' '.$this->getLang('inprogress');
        return $label;
    }


    /**
     * Get the sort number that defines the position in the admin menu.
     *
     * @return int The sort number
     */
    function getMenuSort() {
        return 1011;
    }

    /**
     * If this admin plugin is for admins only
     *
     * @return bool false
     */
    function forAdminOnly() {
        return false;
    }

    /**
     * Handle the input
     */
    function handle() {
        global $INPUT;

        // create a new plan if possible and sufficient data was given
        $this->createPlanFromInput();

        // handle workflow button presses
        if($this->plan->isCommited()) {
            helper_plugin_move_rewrite::addLock(); //todo: right place?
            switch($INPUT->str('ctl')) {
                case 'continue':
                    $this->plan->nextStep();
                    break;
                case 'skip':
                    $this->plan->nextStep(true);
                    break;
                case 'abort':
                    $this->plan->abort();
                    break;
            }
        }
    }

    /**
     * Display the interface
     */
    function html() {
        // decide what to do based on the plan's state
        if($this->plan->isCommited()) {
            $this->GUI_progress();
        } else {
            // display form
            $this->GUI_simpleForm();
        }
    }

    /**
     * Get input variables and create a move plan from them
     *
     * @return bool
     */
    protected function createPlanFromInput() {
        global $INPUT;
        global $ID;

        if($this->plan->isCommited()) return false;

        $this->plan->setOption('autoskip', $INPUT->bool('autoskip'));
        $this->plan->setOption('autorewrite', $INPUT->bool('autorewrite'));

        if($ID && $INPUT->has('dst')) {
            $dst = trim($INPUT->str('dst'));
            if($dst == '') {
                msg($this->getLang('nodst'), -1);
                return false;
            }

            // input came from form
            if($INPUT->str('class') == 'namespace') {
                $src = getNS($ID);

                if($INPUT->str('type') == 'both') {
                    $this->plan->addPageNamespaceMove($src, $dst);
                    $this->plan->addMediaNamespaceMove($src, $dst);
                } else if($INPUT->str('type') == 'page') {
                    $this->plan->addPageNamespaceMove($src, $dst);
                } else if($INPUT->str('type') == 'media') {
                    $this->plan->addMediaNamespaceMove($src, $dst);
                }
            } else {
                $this->plan->addPageMove($ID, $INPUT->str('dst'));
            }
            $this->plan->commit();
            return true;
        } elseif($INPUT->has('json')) {
            // input came via JSON from tree manager
            $data = json_decode($INPUT->str('json'), true);

            foreach((array) $data as $entry) {
                if($entry['class'] == 'ns') {
                    if($entry['type'] == 'page') {
                        $this->plan->addPageNamespaceMove($entry['src'], $entry['dst']);
                    } elseif($entry['type'] == 'media') {
                        $this->plan->addMediaNamespaceMove($entry['src'], $entry['dst']);
                    }
                } elseif($entry['class'] == 'doc') {
                    if($entry['type'] == 'page') {
                        $this->plan->addPageMove($entry['src'], $entry['dst']);
                    } elseif($entry['type'] == 'media') {
                        $this->plan->addMediaMove($entry['src'], $entry['dst']);
                    }
                }
            }

            $this->plan->commit();
            return true;
        }

        return false;
    }

    /**
     * Display the simple move form
     */
    protected function GUI_simpleForm() {
        global $ID;

        echo $this->locale_xhtml('move');

        $treelink = wl($ID, array('do'=>'admin', 'page'=>'move_tree'));
        echo '<p id="plugin_move__treelink">';
        printf($this->getLang('treelink'), $treelink);
        echo '</p>';

        $form = new Doku_Form(array('action' => wl($ID), 'method' => 'post', 'class' => 'plugin_move_form'));
        $form->addHidden('page', 'move_main');
        $form->addHidden('id', $ID);

        $form->startFieldset($this->getLang('legend'));

        $form->addElement(form_makeRadioField('class', 'page', $this->getLang('movepage') . ' <code>' . $ID . '</code>', '', 'block radio click-page', array('checked' => 'checked')));
        $form->addElement(form_makeRadioField('class', 'namespace', $this->getLang('movens') . ' <code>' . getNS($ID) . '</code>', '', 'block radio click-ns'));

        $form->addElement(form_makeTextField('dst', $ID, $this->getLang('dst'), '', 'block indent'));
        $form->addElement(form_makeMenuField('type', array('pages' => $this->getLang('move_pages'), 'media' => $this->getLang('move_media'), 'both' => $this->getLang('move_media_and_pages')), 'both', $this->getLang('content_to_move'), '', 'block indent select'));

        $form->addElement(form_makeCheckboxField('autoskip', '1', $this->getLang('autoskip'), '', 'block', ($this->getConf('autoskip') ? array('checked' => 'checked') : array())));
        $form->addElement(form_makeCheckboxField('autorewrite', '1', $this->getLang('autorewrite'), '', 'block', ($this->getConf('autorewrite') ? array('checked' => 'checked') : array())));

        $form->addElement(form_makeButton('submit', 'admin', $this->getLang('btn_start')));
        $form->endFieldset();
        $form->printForm();
    }

    /**
     * Display the GUI while the move progresses
     */
    protected function GUI_progress() {
        echo '<div id="plugin_move__progress">';

        echo $this->locale_xhtml('progress');

        $progress = $this->plan->getProgress();

        if(!$this->plan->inProgress()) {
            echo '<div id="plugin_move__preview">';
            echo '<p>';
            echo '<strong>' . $this->getLang('intro') . '</strong> ';
            echo '<span>' . $this->getLang('preview') . '</span>';
            echo '</p>';
            echo $this->plan->previewHTML();
            echo '</div>';

        }

        echo '<div class="progress" data-progress="' . $progress . '">' . $progress . '%</div>';

        echo '<div class="output">';
        if($this->plan->getLastError()) {
            echo '<p><div class="error">' . $this->plan->getLastError() . '</div></p>';
        } elseif ($this->plan->inProgress()) {
            echo '<p><div class="info">' . $this->getLang('inexecution') . '</div></p>';
        }
        echo '</div>';

        // display all buttons but toggle visibility according to state
        echo '<p></p>';
        echo '<div class="controls">';
        echo '<img src="' . DOKU_BASE . 'lib/images/throbber.gif" class="hide" />';
        $this->btn('start', !$this->plan->inProgress());
        $this->btn('retry', $this->plan->getLastError());
        $this->btn('skip', $this->plan->getLastError());
        $this->btn('continue', $this->plan->inProgress() && !$this->plan->getLastError());
        $this->btn('abort');
        echo '</div>';

        echo '</div>';
    }

    /**
     * Display a move workflow button
     *
     * continue, start, retry - continue next steps
     * abort - abort the whole move
     * skip - skip error and continue
     *
     * @param string $control
     * @param bool   $show should this control be visible?
     */
    protected function btn($control, $show = true) {
        global $ID;

        $skip  = 0;
        $label = $this->getLang('btn_' . $control);
        $id    = $control;
        if($control == 'start') $control = 'continue';
        if($control == 'retry') {
            $control = 'continue';
            $skip    = 0;
        }

        $class = 'move__control ctlfrm-' . $id;
        if(!$show) $class .= ' hide';

        $form = new Doku_Form(array('action' => wl($ID), 'method' => 'post', 'class' => $class));
        $form->addHidden('page', 'move_main');
        $form->addHidden('id', $ID);
        $form->addHidden('ctl', $control);
        $form->addHidden('skip', $skip);
        $form->addElement(form_makeButton('submit', 'admin', $label, array('class' => 'btn ctl-' . $control)));
        $form->printForm();
    }
}
