<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Test cases for namespace move functionality of the move plugin
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_namespace_move_test extends DokuWikiTest {

    public function setUp(): void {
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
    }

    /**
     * @coversNothing
     */
    public function tearDown(): void {
        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');
        $plan->abort();

        io_rmdir(DOKU_TMP_DATA."pages/newns",true);
        io_rmdir(DOKU_TMP_DATA."media/newns",true);
        io_rmdir(DOKU_TMP_DATA."meta/newns",true);

        parent::tearDown();
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_wiki_namespace() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "wiki:*\t@ALL\t16";

        idx_addPage('wiki:dokuwiki');
        idx_addPage('wiki:syntax');

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('wiki', 'foo');
        $plan->addMediaNamespaceMove('wiki', 'foo');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(),'pages');
        $this->assertSame(1, $plan->nextStep(),'media');
        $this->assertSame(1, $plan->nextStep(),'missing');
        $this->assertSame(1, $plan->nextStep(),'namespace');
        $this->assertSame(1, $plan->nextStep(),'autorewrite');
        $this->assertSame(0, $plan->nextStep(),'done');

        $this->assertFileExists(wikiFN('foo:dokuwiki'));
        $this->assertFileDoesNotExist(wikiFN('wiki:syntax'));
        $this->assertFileExists(mediaFN('foo:dokuwiki-128.png'));
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_missing() {
        saveWikiText('oldspace:page', '[[missing]]', 'setup');
        idx_addPage('oldspace:page');

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('oldspace', 'newspace');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(),'page');
        $this->assertSame(1, $plan->nextStep(),'missing');
        $this->assertSame(1, $plan->nextStep(),'namespace');
        $this->assertSame(1, $plan->nextStep(),'autorewrite');
        $this->assertSame(0, $plan->nextStep(),'done');

        $this->assertFileExists(wikiFN('newspace:page'));
        $this->assertFileDoesNotExist(wikiFN('oldspace:page'));

        $this->assertEquals('[[missing]]', rawWiki('newspace:page'));
    }

    /**
     * @covers helper_plugin_move_plan::findAffectedPages
     * @uses Doku_Indexer
     */
    public function test_move_affected() {
        saveWikiText('oldaffectedspace:page', '[[missing]]', 'setup');
        idx_addPage('oldaffectedspace:page');
        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('oldaffectedspace', 'newaffectedspace');

        $plan->commit();

        $affected_file = file(TMP_DIR . '/data/meta/__move_affected');
        $this->assertSame('newaffectedspace:page',trim($affected_file[0]));
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    function test_move_large_ns(){

        $this->markTestSkipped(
                'This test randomly fails with the page "testns:start" being moved, but "start" not being rewritten in the request.'
        );

        global $conf;

        $test = '[[testns:start]] [[testns:test_page17]]';
        $summary = 'testsetup';


        saveWikiText(':start', $test, $summary);
        idx_addPage(':start');
        saveWikiText('testns:start', $test, $summary);
        idx_addPage('testns:start');
        saveWikiText('testns:test_page1', $test, $summary);
        idx_addPage('testns:test_page1');
        saveWikiText('testns:test_page2', $test, $summary);
        idx_addPage('testns:test_page2');
        saveWikiText('testns:test_page3', $test, $summary);
        idx_addPage('testns:test_page3');
        saveWikiText('testns:test_page4', $test, $summary);
        idx_addPage('testns:test_page4');
        saveWikiText('testns:test_page5', $test, $summary);
        idx_addPage('testns:test_page5');
        saveWikiText('testns:test_page6', $test, $summary);
        idx_addPage('testns:test_page6');
        saveWikiText('testns:test_page7', $test, $summary);
        idx_addPage('testns:test_page7');
        saveWikiText('testns:test_page8', $test, $summary);
        idx_addPage('testns:test_page8');
        saveWikiText('testns:test_page9', $test, $summary);
        idx_addPage('testns:test_page9');
        saveWikiText('testns:test_page10', $test, $summary);
        idx_addPage('testns:test_page10');
        saveWikiText('testns:test_page11', $test, $summary);
        idx_addPage('testns:test_page11');
        saveWikiText('testns:test_page12', $test, $summary);
        idx_addPage('testns:test_page12');
        saveWikiText('testns:test_page13', $test, $summary);
        idx_addPage('testns:test_page13');
        saveWikiText('testns:test_page14', $test, $summary);
        idx_addPage('testns:test_page14');
        saveWikiText('testns:test_page15', $test, $summary);
        idx_addPage('testns:test_page15');
        saveWikiText('testns:test_page16', $test, $summary);
        idx_addPage('testns:test_page16');
        saveWikiText('testns:test_page17', $test, $summary);
        idx_addPage('testns:test_page17');
        saveWikiText('testns:test_page18', $test, $summary);
        idx_addPage('testns:test_page18');
        saveWikiText('testns:test_page19', $test, $summary);
        idx_addPage('testns:test_page19');

        $conf['plugin']['move']['autorewrite'] = 0;

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('testns', 'foo:testns');

        $plan->commit();
        global $conf;
        $lockfile = $conf['lockdir'] . 'move.lock';

        $this->assertSame(10, $plan->nextStep(),"After processing first chunk of pages, 10 steps should be left");

        $request = new TestRequest();
        $response = $request->get();
        $actual_response = $response->getContent();
        //clean away clutter
        $actual_response = substr($actual_response,strpos($actual_response,"<!-- wikipage start -->") + 23);
        $actual_response = substr($actual_response,strpos($actual_response, 'doku.php'));
        $actual_response = substr($actual_response,0,strpos($actual_response,"<!-- wikipage stop -->"));
        $actual_response = trim($actual_response);
        $actual_response = rtrim($actual_response,"</p>");
        $actual_response = trim($actual_response);

        $expected_response = 'doku.php?id=foo:testns:start" class="wikilink1" title="foo:testns:start">testns</a> <a href="/./doku.php?id=testns:test_page17" class="wikilink1" title="testns:test_page17">test_page17</a>';
        $this->assertSame($expected_response,$actual_response); // todo: this assert fails occaisionally, but not reproduciably. It then has the following oputput: <a href="/./doku.php?id=testns:start" class="wikilink2" title="testns:start" rel="nofollow">testns</a> <a href="/./doku.php?id=testns:test_page17" class="wikilink1" title="testns:test_page17">test_page17</a>

        $expected_file_contents = '[[testns:start]] [[testns:test_page17]]';
        $start_file = file(TMP_DIR . '/data/pages/start.txt');
        $actual_file_contents = $start_file[0];
        $this->assertSame($expected_file_contents,$actual_file_contents);

        /** @var helper_plugin_move_rewrite $rewrite */
        $rewrite = plugin_load('helper', 'move_rewrite');
        $expected_move_meta = array('origin'=> 'testns:start', 'pages' => array(array('testns:start','foo:testns:start')),'media' => array());
        $actual_move_media = $rewrite->getMoveMeta('foo:testns:start');
        $this->assertSame($expected_move_meta,$actual_move_media);
        $this->assertFileExists($lockfile);

    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_small_namespace_pages() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "oldns:*\t@ALL\t16";
        $AUTH_ACL[] = "newns:*\t@ALL\t16";

        saveWikiText('start', '[[oldns:start]] [[oldns:page]] [[oldns:missing]]', 'setup');
        idx_addPage('start');
        saveWikiText('oldns:start', '[[oldns:start]] [[oldns:page]] [[oldns:missing]] [[missing]] [[page]]', 'setup');
        idx_addPage('oldns:start');
        saveWikiText('oldns:page', '[[oldns:start]] [[oldns:page]] [[oldns:missing]] [[missing]] [[start]]', 'setup');
        idx_addPage('oldns:page');


        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('oldns', 'newns');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(), 'pages');
        $this->assertSame(1, $plan->nextStep(), 'missing');
        $this->assertSame(1, $plan->nextStep(), 'namespace');
        $this->assertSame(1, $plan->nextStep(), 'autorewrite');
        $this->assertSame(0, $plan->nextStep(), 'done');

        $this->assertFileExists(wikiFN('newns:start'));
        $this->assertFileExists(wikiFN('newns:page'));
        $this->assertFileDoesNotExist(wikiFN('oldns:start'));
        $this->assertFileDoesNotExist(wikiFN('oldns:page'));

        $this->assertSame('[[newns:start]] [[newns:page]] [[newns:missing]] [[missing]] [[page]]',rawWiki('newns:start'));
        $this->assertSame('[[newns:start]] [[newns:page]] [[newns:missing]] [[missing]] [[start]]',rawWiki('newns:page'));
        $this->assertSame('[[newns:start]] [[newns:page]] [[newns:missing]]',rawWiki('start'));
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_small_namespace_media() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "oldns:*\t@ALL\t16";
        $AUTH_ACL[] = "newns:*\t@ALL\t16";

        $filepath = DOKU_TMP_DATA.'media/oldns/oldnsimage.png';
        io_makeFileDir($filepath);
        io_saveFile($filepath,'');
        saveWikiText('start', '{{oldns:oldnsimage.png}} {{oldns:oldnsimage_missing.png}} {{image_missing.png}}', 'setup');
        idx_addPage('start');

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addMediaNamespaceMove('oldns', 'newns');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(), 'media');
        $this->assertSame(1, $plan->nextStep(), 'missing_media');
        $this->assertSame(1, $plan->nextStep(), 'autorewrite');
        $this->assertSame(0, $plan->nextStep(), 'done');

        $this->assertFileExists(mediaFN('newns:oldnsimage.png'));
        $this->assertFileDoesNotExist(mediaFN('oldns:oldnsimage.png'));

        $this->assertSame('{{newns:oldnsimage.png}} {{newns:oldnsimage_missing.png}} {{image_missing.png}}',rawWiki('start'));
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_small_namespace_media_affected() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "oldns:*\t@ALL\t16";
        $AUTH_ACL[] = "newns:*\t@ALL\t16";

        $filepath = DOKU_TMP_DATA.'media/oldns/oldnsimage.png';
        io_makeFileDir($filepath);
        io_saveFile($filepath,'');
        saveWikiText('oldns:start', '{{:oldns:oldnsimage.png}} {{oldns:oldnsimage_missing.png}} {{oldnsimage_missing.png}} {{oldnsimage.png}}', 'setup');
        idx_addPage('oldns:start');

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addMediaNamespaceMove('oldns', 'newns');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(), 'media');
        $this->assertSame(1, $plan->nextStep(), 'missing_media');
        $this->assertSame(1, $plan->nextStep(), 'autorewrite');
        $this->assertSame(0, $plan->nextStep(), 'done');

        $this->assertFileExists(mediaFN('newns:oldnsimage.png'));
        $this->assertFileDoesNotExist(mediaFN('oldns:oldnsimage.png'));

        $this->assertSame('{{newns:oldnsimage.png}} {{newns:oldnsimage_missing.png}} {{newns:oldnsimage_missing.png}} {{newns:oldnsimage.png}}',rawWiki('oldns:start'));
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_small_namespace_combi() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "oldns:*\t@ALL\t16";
        $AUTH_ACL[] = "newns:*\t@ALL\t16";

        $filepath = DOKU_TMP_DATA.'media/oldns/oldnsimage.png';
        io_makeFileDir($filepath);
        io_saveFile($filepath,'');
        saveWikiText('start', "[[oldns:start]] [[oldns:page]] [[oldns:missing]]\n{{oldns:oldnsimage.png}} {{oldns:oldnsimage_missing.png}} {{oldnsimage_missing.png}}", 'setup');
        idx_addPage('start');
        saveWikiText('oldns:start', '[[oldns:start]] [[oldns:page]] [[oldns:missing]] [[missing]] [[page]]', 'setup');
        idx_addPage('oldns:start');
        saveWikiText('oldns:page', '[[oldns:start]] [[oldns:page]] [[oldns:missing]] [[missing]] [[start]]', 'setup');
        idx_addPage('oldns:page');

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addMediaNamespaceMove('oldns', 'newns');
        $plan->addPageNamespaceMove('oldns', 'newns');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(), 'pages');
        $this->assertSame(1, $plan->nextStep(), 'media');
        $this->assertSame(1, $plan->nextStep(), 'missing');
        $this->assertSame(1, $plan->nextStep(), 'missing_media');
        $this->assertSame(1, $plan->nextStep(), 'namespaces');
        $this->assertSame(1, $plan->nextStep(), 'autorewrite');
        $this->assertSame(0, $plan->nextStep(), 'done');

        $this->assertFileExists(mediaFN('newns:oldnsimage.png'));
        $this->assertFileDoesNotExist(mediaFN('oldns:oldnsimage.png'));

        $this->assertSame("[[newns:start]] [[newns:page]] [[newns:missing]]\n{{newns:oldnsimage.png}} {{newns:oldnsimage_missing.png}} {{oldnsimage_missing.png}}",rawWiki('start'));
    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_small_namespace_subscription_ns() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "subns:*\t@ALL\t16";
        $AUTH_ACL[] = "newns:*\t@ALL\t16";

        saveWikiText('subns:start', 'Lorem Ipsum', 'setup');
        idx_addPage('subns:start');

        $oldfilepath = DOKU_TMP_DATA.'meta/subns/.mlist';
        $subscription = 'doe every 1427984341';
        io_makeFileDir($oldfilepath);
        io_saveFile($oldfilepath,$subscription);
        $newfilepath = DOKU_TMP_DATA.'meta/newns/.mlist';

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('subns', 'newns');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(), 'pages');
        $this->assertSame(1, $plan->nextStep(), 'namespace');
        $this->assertSame(0, $plan->nextStep(), 'done');

        $this->assertFileExists(wikiFN('newns:start'));
        $this->assertFileExists($newfilepath);
        $this->assertFileDoesNotExist(wikiFN('subns:start'));
        $this->assertFileDoesNotExist($oldfilepath);

        $this->assertSame($subscription,file_get_contents($newfilepath));

    }

    /**
     * This is an integration test, which checks the correct working of an entire namespace move.
     * Hence it is not an unittest, hence it @coversNothing
     *
     * @group slow
     */
    public function test_move_small_namespace_subscription_page() {
        global $AUTH_ACL;

        $AUTH_ACL[] = "subns:*\t@ALL\t16";
        $AUTH_ACL[] = "newns:*\t@ALL\t16";

        saveWikiText('subns:start', 'Lorem Ipsum', 'setup');
        idx_addPage('subns:start');

        $oldfilepath = DOKU_TMP_DATA.'meta/subns/start.mlist';
        $subscription = 'doe every 1427984341';
        io_makeFileDir($oldfilepath);
        io_saveFile($oldfilepath,$subscription);
        $newfilepath = DOKU_TMP_DATA.'meta/newns/start.mlist';

        /** @var helper_plugin_move_plan $plan  */
        $plan = plugin_load('helper', 'move_plan');

        $this->assertFalse($plan->inProgress());

        $plan->addPageNamespaceMove('subns', 'newns');

        $plan->commit();

        $this->assertSame(1, $plan->nextStep(), 'pages');
        $this->assertSame(1, $plan->nextStep(), 'namespace');
        $this->assertSame(0, $plan->nextStep(), 'done');

        $this->assertFileExists(wikiFN('newns:start'));
        $this->assertFileExists($newfilepath);
        $this->assertFileDoesNotExist(wikiFN('subns:start'));
        $this->assertFileDoesNotExist($oldfilepath);

        $this->assertSame($subscription,file_get_contents($newfilepath));

    }

}
