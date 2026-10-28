<?php

use dokuwiki\Search\Indexer;

/**
 * Test cases for the move plugin
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_affectedPagesNS_test extends DokuWikiTest {

    protected $pluginsEnabled = array('move',);

    public function setUp(): void {
        parent::setUp();
        global $USERINFO;
        global $conf;
        $conf['useacl']    = 1;
        $conf['superuser'] = 'john';
        $_SERVER['REMOTE_USER'] = 'john'; //now it's testing as admin
        $USERINFO['grps'] = array('admin','user');
    }

    /**
     * @coversNothing
     */
    public function tearDown(): void {
        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');
        $plan->abort();
        parent::tearDown();
    }

    /**
     * @covers helper_plugin_move_plan::findAffectedPages
     * @uses Doku_Indexer
     */
    public function test_affectedPagesNS_Media() {

        saveWikiText('oldns:start', '{{oldnsimage_missing.png}}', 'setup');
        (new Indexer())->addPage('oldns:start');

        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper','move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addMediaNamespaceMove('oldns', 'newns');

        $plan->commit();

        $affected_file = file(TMP_DIR . '/data/meta/__move_affected');

        $this->assertSame('oldns:start',trim($affected_file[0]));

    }

}
