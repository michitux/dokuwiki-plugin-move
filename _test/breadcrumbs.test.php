<?php

// must be run within Dokuwiki
if (!defined('DOKU_INC')) die();

/**
 * Test cases for breadcrumb handling during page moves
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_breadcrumbs_test extends DokuWikiTest {

    function setUp(): void {
        parent::setUpBeforeClass();
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
    }

    /**
     * Test that breadcrumbs are updated when a page is moved
     */
    function test_breadcrumbs_updated_on_move() {
        $src = 'test:old_page';
        $dst = 'test:new_page';

        saveWikiText($src, 'This is test content', 'Test setup');
        idx_addPage($src);

        // Populate session breadcrumbs with a trace: other1 -> src -> other2
        $_SESSION[DOKU_COOKIE]['bc'] = [
            'wiki:start' => 'Start',
            $src => 'old_page',
            'wiki:syntax' => 'Syntax',
        ];

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage($src, $dst));

        $expected = [
            'wiki:start' => 'Start',
            $dst => 'new_page',
            'wiki:syntax' => 'Syntax',
        ];

        $this->assertEquals($expected, $_SESSION[DOKU_COOKIE]['bc']);
        $this->assertArrayNotHasKey($src, $_SESSION[DOKU_COOKIE]['bc']);
    }

    /**
     * Test that moving a page not in breadcrumbs leaves breadcrumbs unchanged
     */
    function test_breadcrumbs_unaffected_when_not_in_bc() {
        $src = 'test:unrelated_page';
        $dst = 'test:unrelated_moved';

        saveWikiText($src, 'Content', 'Test setup');
        idx_addPage($src);

        $initial = [
            'wiki:start' => 'Start',
            'wiki:syntax' => 'Syntax',
        ];
        $_SESSION[DOKU_COOKIE]['bc'] = $initial;

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage($src, $dst));

        $this->assertEquals($initial, $_SESSION[DOKU_COOKIE]['bc']);
    }

    /**
     * Test that if dst was already in breadcrumbs, no duplicate entries are created
     */
    function test_breadcrumbs_deduplication() {
        $src = 'test:dup_src';
        $dst = 'test:dup_dst';

        saveWikiText($src, 'Content', 'Test setup');
        idx_addPage($src);

        // Suppose dst was visited earlier, then wiki:syntax, then src
        $_SESSION[DOKU_COOKIE]['bc'] = [
            $dst => 'Old Dst Title',
            'wiki:syntax' => 'Syntax',
            $src => 'dup_src',
        ];

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage($src, $dst));

        $expected = [
            'wiki:syntax' => 'Syntax',
            $dst => 'dup_dst',
        ];

        $this->assertEquals($expected, $_SESSION[DOKU_COOKIE]['bc']);
    }

    /**
     * Test heading navigation respects page title if useheading navigation is active
     */
    function test_breadcrumbs_with_heading() {
        global $conf;
        $conf['useheading'] = 'navigation';

        $src = 'test:heading_src';
        $dst = 'test:heading_dst';

        saveWikiText($src, "====== Custom Page Title ======\nPage body here", 'Test setup');
        idx_addPage($src);

        $_SESSION[DOKU_COOKIE]['bc'] = [
            'wiki:start' => 'Start',
            $src => 'Custom Page Title',
        ];

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage($src, $dst));

        $expected = [
            'wiki:start' => 'Start',
            $dst => 'Custom Page Title',
        ];

        $this->assertEquals($expected, $_SESSION[DOKU_COOKIE]['bc']);
    }
}
