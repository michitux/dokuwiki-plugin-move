<?php

use dokuwiki\Search\Indexer;

/**
 * tests for the template button of the move plugin
 *
 * @author Michael Große <grosse@cosmocode.de>
 * @group plugin_move
 * @group plugins
 */
class move_tpl_test extends DokuWikiTest {

    public function setUp(): void {
        parent::setUp();
    }

    protected $pluginsEnabled = array('move');

    /**
     * @coversNothing
     * Integration-ish kind of test testing action_plugin_move_rename::handle_pagetools
     *//*
    function test_tpl () {
        saveWikiText('wiki:foo:start', '[[..:..:one_ns_up:]]', 'Test setup');
        (new Indexer())->addPage('wiki:foo:start');

        $request = new TestRequest();
        $response = $request->get(array(),'/doku.php?id=wiki:foo:start');

        $this->assertTrue(strstr($response->getContent(),'class="plugin_move_page"') !== false);
    }*/

    /**
     * @covers action_plugin_move_rename::renameOkay
     */
    function test_renameOkay() {
        global $conf;
        global $USERINFO;
        $conf['superuser'] = 'john';
        $_SERVER['REMOTE_USER'] = 'john';
        $USERINFO['grps'] = array('admin','user');

        saveWikiText('wiki:foo:start', '[[..:..:one_ns_up:]]', 'Test setup');
        (new Indexer())->addPage('wiki:foo:start');

        $move_rename = new action_plugin_move_rename();
        $this->assertTrue($move_rename->renameOkay('wiki:foo:start'));

    }
}
