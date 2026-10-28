<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

use dokuwiki\Search\Indexer;

/**
 * Test cases for the move plugin
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_pagemove_test  extends DokuWikiTest {

    var $movedToId = '';
    var $movedId = 'parent_ns:current_ns:test_page';
    var $parentBacklinkingId = 'parent_ns:some_page';
    var $currentNsBacklinkingId = 'parent_ns:current_ns:some_page';
    var $otherBacklinkingId = 'level0:level1:other_backlinking_page';
    var $subNsPage = 'parent_ns:current_ns:sub_ns:some_page';

    // @todo Move page to an ID which already exists
    // @todo Check backlinks of a sub-namespace page (moving same, up, down, different)

    protected function indexReferences(string $id, array $references): void {
        (new \dokuwiki\Search\Collection\PageMetaCollection('relation_references'))
            ->lock()
            ->addEntity($id, $references)
            ->unlock();
    }

    function setUp(): void {
        parent::setUpBeforeClass();
        $this->pluginsEnabled[] = 'move';
        global $ID;
        global $INFO;
        global $conf;

        $ID = $this->movedId;

        $text = <<<EOT
[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
        $summary = 'Test';
        saveWikiText($this->movedId, $text, $summary);
        $INFO = pageinfo();

        $references = array_keys(p_get_metadata($this->movedId, 'relation references', METADATA_RENDER_UNLIMITED));
        $this->indexReferences($this->movedId, $references);

        $text = <<<EOT
[[$this->movedId|$this->movedId]]
[[:$this->movedId|:$this->movedId]]
[[.current_ns:test_page|.current_ns:test_page]]
[[.:current_ns:test_page|.:current_ns:test_page]]
[[..parent_ns:current_ns:test_page|..parent_ns:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
        saveWikiText($this->parentBacklinkingId, $text, $summary);
        $references = array_keys(p_get_metadata($this->parentBacklinkingId, 'relation references', METADATA_RENDER_UNLIMITED));
        $this->indexReferences($this->parentBacklinkingId, $references);

        $text = <<<EOT
[[$this->movedId|$this->movedId]]
[[:$this->movedId|:$this->movedId]]
[[..current_ns:test_page|..current_ns:test_page]]
[[..:current_ns:test_page|..:current_ns:test_page]]
[[test_page|test_page]]
[[.test_page|.test_page]]
[[.:test_page|.:test_page]]
[[..test_page|..test_page]]
[[..:test_page|..:test_page]]
[[.:..:test_page|.:..:test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
        saveWikiText($this->currentNsBacklinkingId, $text, $summary);
        $references = array_keys(p_get_metadata($this->currentNsBacklinkingId, 'relation references', METADATA_RENDER_UNLIMITED));
        $this->indexReferences($this->currentNsBacklinkingId, $references);

        $text = <<<EOT
[[$this->movedId|$this->movedId]]
[[:$this->movedId|:$this->movedId]]
[[.current_ns:test_page|.current_ns:test_page]]
[[.:current_ns:test_page|.:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
        saveWikiText($this->otherBacklinkingId, $text, $summary);
        $references = array_keys(p_get_metadata($this->otherBacklinkingId, 'relation references', METADATA_RENDER_UNLIMITED));
        $this->indexReferences($this->otherBacklinkingId, $references);

        $text = <<<EOT
[[$this->movedId|$this->movedId]]
[[:$this->movedId|:$this->movedId]]
[[..:..current_ns:test_page|..:..current_ns:test_page]]
[[..:..:current_ns:test_page|..:..:current_ns:test_page]]
[[test_page|test_page]]
[[..:test_page|..:test_page]]
[[..:test_page|..:test_page]]
[[.:..:test_page|.:..:test_page]]
[[new_page|new_page]]
[[..:new_page|..:new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
        saveWikiText($this->subNsPage, $text, $summary);
        $references = array_keys(p_get_metadata($this->subNsPage, 'relation references', METADATA_RENDER_UNLIMITED));
        $this->indexReferences($this->subNsPage, $references);

        parent::setUp();

        // we test under useslash conditions
        $conf['useslash'] = 1;
    }

    /**
     * @group slow
     */
    function test_move_page_in_same_ns() {
	    global $ID;
        $newId = getNS($ID).':new_page';
        $this->movedToId = $newId;

        /** @var helper_plugin_move_op $MoveOp */
        $MoveOp = plugin_load('helper', 'move_op');

        $result = $MoveOp->movePage($ID, $this->movedToId);
        $this->assertTrue($result);

	    $newContent = rawWiki($this->movedToId);
	    $expectedContent = <<<EOT
[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->parentBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:current_ns:new_page|$this->movedId]]
[[parent_ns:current_ns:new_page|:$this->movedId]]
[[.current_ns:new_page|.current_ns:test_page]]
[[.current_ns:new_page|.:current_ns:test_page]]
[[.current_ns:new_page|..parent_ns:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->currentNsBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:current_ns:new_page|$this->movedId]]
[[parent_ns:current_ns:new_page|:$this->movedId]]
[[new_page|..current_ns:test_page]]
[[new_page|..:current_ns:test_page]]
[[new_page|test_page]]
[[new_page|.test_page]]
[[new_page|.:test_page]]
[[..test_page|..test_page]]
[[..:test_page|..:test_page]]
[[.:..:test_page|.:..:test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->otherBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:current_ns:new_page|$this->movedId]]
[[$newId|:$this->movedId]]
[[.current_ns:test_page|.current_ns:test_page]]
[[.:current_ns:test_page|.:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);
	}

    /**
     * @group slow
     */
    function test_move_page_to_parallel_ns() {
	    global $ID;
        $newId = 'parent_ns:parallel_ns:new_page';
        $this->movedToId = $newId;

        /** @var helper_plugin_move_op $MoveOp */
        $MoveOp = plugin_load('helper', 'move_op');

        $result = $MoveOp->movePage($ID, $newId);
        $this->assertTrue($result);

        $newContent = rawWiki($this->movedToId);
	    $expectedContent = <<<EOT
[[..:current_ns:start|start]]
[[..:current_ns:parallel_page|parallel_page]]
[[..:current_ns:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->parentBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:parallel_ns:new_page|$this->movedId]]
[[parent_ns:parallel_ns:new_page|:$this->movedId]]
[[.parallel_ns:new_page|.current_ns:test_page]]
[[.parallel_ns:new_page|.:current_ns:test_page]]
[[.parallel_ns:new_page|..parent_ns:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->currentNsBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:parallel_ns:new_page|$this->movedId]]
[[$newId|:$this->movedId]]
[[..:parallel_ns:new_page|..current_ns:test_page]]
[[..:parallel_ns:new_page|..:current_ns:test_page]]
[[..:parallel_ns:new_page|test_page]]
[[..:parallel_ns:new_page|.test_page]]
[[..:parallel_ns:new_page|.:test_page]]
[[..test_page|..test_page]]
[[..:test_page|..:test_page]]
[[.:..:test_page|.:..:test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->otherBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:parallel_ns:new_page|$this->movedId]]
[[$newId|:$this->movedId]]
[[.current_ns:test_page|.current_ns:test_page]]
[[.:current_ns:test_page|.:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);
	}

    /**
     * @group slow
     */
    function test_move_page_to_parent_ns() {
	    global $ID;

        $newId = 'parent_ns:new_page';
        $this->movedToId = $newId;

        /** @var helper_plugin_move_op $MoveOp */
        $MoveOp = plugin_load('helper', 'move_op');

        $result = $MoveOp->movePage($ID, $newId); //parent_ns:current_ns:test_page ->  parent_ns:new_page
        $this->assertTrue($result);

        $newContent = rawWiki($this->movedToId);
	    $expectedContent = <<<EOT
[[.current_ns:start|start]]
[[.current_ns:parallel_page|parallel_page]]
[[.current_ns:|.:]]
[[.current_ns:|..current_ns:]]
[[.current_ns:|..:current_ns:]]
[[.parallel_ns:|..parallel_ns:]]
[[.parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
        // Note: ..:..: is not a great link for a page in a namespace 'parent_ns', but it is correctly resolved.
	    $this->assertEquals($expectedContent, $newContent);

	    // page is moved to same NS as backlinking page (parent_ns)
	    $newContent = rawWiki($this->parentBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:new_page|$this->movedId]]
[[parent_ns:new_page|:$this->movedId]]
[[new_page|.current_ns:test_page]]
[[new_page|.:current_ns:test_page]]
[[new_page|..parent_ns:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->currentNsBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:new_page|$this->movedId]]
[[$newId|:$this->movedId]]
[[..:new_page|..current_ns:test_page]]
[[..:new_page|..:current_ns:test_page]]
[[..:new_page|test_page]]
[[..:new_page|.test_page]]
[[..:new_page|.:test_page]]
[[..test_page|..test_page]]
[[..:test_page|..:test_page]]
[[.:..:test_page|.:..:test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);

	    $newContent = rawWiki($this->otherBacklinkingId);
	    $expectedContent = <<<EOT
[[parent_ns:new_page|$this->movedId]]
[[$newId|:$this->movedId]]
[[.current_ns:test_page|.current_ns:test_page]]
[[.:current_ns:test_page|.:current_ns:test_page]]
[[test_page|test_page]]
[[new_page|new_page]]
[[ftp://somewhere.com|ftp://somewhere.com]]
[[http://somewhere.com|http://somewhere.com]]

[[start|start]]
[[parallel_page|parallel_page]]
[[.:|.:]]
[[..current_ns:|..current_ns:]]
[[..:current_ns:|..:current_ns:]]
[[..parallel_ns:|..parallel_ns:]]
[[..:parallel_ns:|..:parallel_ns:]]
[[..:..:|..:..:]]
[[..:..:parent_ns:|..:..:parent_ns:]]
[[parent_ns:new_page|parent_ns:new_page]]
[[parent_ns/new_page|parent_ns/new_page]]
[[/start|/start]]
EOT;
	    $this->assertEquals($expectedContent, $newContent);
	}

    /**
     * Ensure that absolute links stay absolute. See https://github.com/michitux/dokuwiki-plugin-move/pull/6#discussion_r15698440
     *
     * @group slow
     */
    function test_move_startpage_of_ns() {
        saveWikiText('wiki:bar:test',
                     '[[wiki:foo:]]', 'Test setup');
        (new Indexer())->addPage('wiki:bar:test');
        saveWikiText('wiki:foo:start',
                     'bar', 'Test setup');
        (new Indexer())->addPage('wiki:foo:start');

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage('wiki:foo:start', 'wiki:foo2:start'));

        $this->assertEquals('[[wiki:foo2:]]', rawWiki('wiki:bar:test'));
    }

    /**
     * If the relative part would be too large, create an absolute link instead.
     * If the original link ended with a colon and the new link also points to a namespace's startpage: keep the colon.
     *
     * @group slow
     */
    function test_move_no_long_rel_links_keep_colon() {
        saveWikiText('wiki:foo:start', '[[..:..:one_ns_up:]]', 'Test setup');
        (new Indexer())->addPage('wiki:foo:start');

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');

        $this->assertTrue($move->movePage('wiki:foo:start', 'wiki:foo:bar:start'));
        $this->assertEquals('[[one_ns_up:]]', rawWiki('wiki:foo:bar:start'));

    }

    /**
     * @covers helper_plugin_move_handler::_nsStartCheck
     * @group slow
     */
    function test_move_to_thisns_start(){
        saveWikiText('wiki:foo:test_page', '[[..:..:bar:]]', 'Test setup');
        (new Indexer())->addPage('wiki:foo:test_page');
        saveWikiText('bar:start', 'foo', 'Test setup');
        (new Indexer())->addPage('bar:start');

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');

        $this->assertTrue($move->movePage('bar:start', 'wiki:foo:start'));
        $this->assertEquals('[[.:]]', rawWiki('wiki:foo:test_page'));
    }

	function test_move_ns_in_same_ns() {

	    $newNamespace = 'new_ns';
        $newPagename = '';

	    $opts = array();
	    $opts['page_ns'] = 'ns';
	    $opts['newns'] = 'parent_ns'.':'.$newNamespace;
	    $opts['newname'] = $newPagename;
	    $this->movedToId = $opts['newns'].':'.$newPagename;

	    //$this->move->_pm_move_recursive($opts);
        $this->markTestIncomplete('Test must yet be implemented.');

	}

    function test_move_start_ns_into_ns_page() {
        saveWikiText('bugs:start', 'Bug page', 'created');
        (new Indexer())->addPage('bugs:start');
        saveWikiText('foo:bugs:test', '[[bugs:start]]', 'created');
        (new Indexer())->addPage('foo:bugs:test');

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');

        $this->assertTrue($move->movePage('bugs:start', 'bugs'));
        $this->assertEquals('[[:bugs]]', rawWiki('foo:bugs:test'));

        $this->assertTrue($move->movePage('bugs', 'start'));
        $this->assertEquals('[[:start]]', rawWiki('foo:bugs:test'));
    }

    function test_clean_id_move() {
        saveWikiText('some_space:start', 'Space page', 'created');
        (new Indexer())->addPage('some_space:start');
        saveWikiText('foo:bar:test', '[[some space:start]]', 'created');
        (new Indexer())->addPage('foo:bar:test');

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');

        $this->assertTrue($move->movePage('some_space:start', 'spaceless:start'));
        $this->assertEquals('[[spaceless:start]]', rawWiki('foo:bar:test'));
    }
}

