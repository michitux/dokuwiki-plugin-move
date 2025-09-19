<?php
/**
 * Move Plugin AJAX handler to step through a move plan
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class action_plugin_move_progress
 */
class action_plugin_move_progress extends DokuWiki_Action_Plugin {

    /**
     * Register event handlers.
     *
     * @param Doku_Event_Handler $controller The plugin controller
     */
    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AJAX_CALL_UNKNOWN', 'BEFORE', $this, 'handle_ajax');
    }

    /**
     * Step up
     *
     * @param Doku_Event $event
     */
    public function handle_ajax(Doku_Event $event) {
        if($event->data != 'plugin_move_progress') return;
        $event->preventDefault();
        $event->stopPropagation();

        global $INPUT;
        global $USERINFO;

        if(!auth_ismanager($_SERVER['REMOTE_USER'], $USERINFO['grps'])) {
            http_status(403);
            exit;
        }

        $return = array(
            'error'    => '',
            'complete' => false,
            'progress' => 0
        );

        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');

        if(!$plan->isCommited()) {
            // There is no plan. Something went wrong
            $return['complete'] = true;
        } else {
            $todo               = $plan->nextStep($INPUT->bool('skip'));
            $return['progress'] = $plan->getProgress();
            $return['error']    = $plan->getLastError();
            if($todo === 0) $return['complete'] = true;
        }

        header('Content-Type: application/json');
        echo json_encode($return);
    }
}
