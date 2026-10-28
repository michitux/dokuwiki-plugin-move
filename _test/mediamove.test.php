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
class plugin_move_mediamove_test extends DokuWikiTest {

    public function setUp(): void {
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
    }

    /**
     * @group slow
     */
    public function test_movePageWithRelativeMedia() {
        $src = 'mediareltest:foo';
        saveWikiText($src,
            '{{ myimage.png}} [[:start|{{ testimage.png?200x800 }}]] [[bar|{{testimage.gif?400x200}}]]
[[doku>wiki:dokuwiki|{{wiki:logo.png}}]] [[http://www.example.com|{{testimage.jpg}}]]
[[doku>wiki:foo|{{foo.gif?200x3000}}]]', 'Test setup');
        (new Indexer())->addPage($src);

        $dst = 'foo';

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->movePage($src, $dst));

        $this->assertEquals('{{ mediareltest:myimage.png}} [[:start|{{ mediareltest:testimage.png?200x800 }}]] [[mediareltest:bar|{{mediareltest:testimage.gif?400x200}}]]
[[doku>wiki:dokuwiki|{{wiki:logo.png}}]] [[http://www.example.com|{{mediareltest:testimage.jpg}}]]
[[doku>wiki:foo|{{mediareltest:foo.gif?200x3000}}]]', rawWiki('foo'));
    }

    /**
     * @group slow
     */
    public function test_moveSingleMedia() {
        global $AUTH_ACL;
        $AUTH_ACL[] = "wiki:*\t@ALL\t16";
        $AUTH_ACL[] = "foobar:*\t@ALL\t8";

        saveWikiText('wiki:movetest', '{{wiki:dokuwiki-128.png?200}}', 'Test initialized');
        (new Indexer())->addPage('wiki:movetest');

        $src = 'wiki:dokuwiki-128.png';
        $dst = 'foobar:logo.png';

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->moveMedia($src, $dst));

        $this->assertTrue(@file_exists(mediaFn('foobar:logo.png')));

        $this->assertEquals('{{foobar:logo.png?200}}', rawWiki('wiki:movetest'));
    }

    /**
     * @group slow
     */
    public function test_moveSingleMedia_colonstart() {
        global $AUTH_ACL;
        $AUTH_ACL[] = "wiki:*\t@ALL\t16";
        $AUTH_ACL[] = "foobar:*\t@ALL\t16";
        $AUTH_ACL[] = "*\t@ALL\t8";

        $filepath = DOKU_TMP_DATA.'media/wiki/testimage.png';
        io_makeFileDir($filepath);
        io_saveFile($filepath,'');

        saveWikiText('wiki:movetest', '{{:wiki:testimage.png?200}}', 'Test initialized');
        (new Indexer())->addPage('wiki:movetest');

        $src = 'wiki:testimage.png';
        $dst = 'foobar:logo_2.png';

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->moveMedia($src, $dst));

        $this->assertTrue(@file_exists(mediaFn('foobar:logo_2.png')));

        $this->assertEquals('{{foobar:logo_2.png?200}}', rawWiki('wiki:movetest'));

        $this->assertTrue($move->moveMedia($dst, 'logo_2.png'));

        $this->assertTrue(@file_exists(mediaFn('logo_2.png')));

        $this->assertEquals('{{:logo_2.png?200}}', rawWiki('wiki:movetest'));
    }

    /**
     * @group slow
     */
    public function test_moveSingleMedia_space() {
        global $AUTH_ACL;
        $AUTH_ACL[] = "wiki:*\t@ALL\t16";
        $AUTH_ACL[] = "foobar:*\t@ALL\t8";

        $filepath = DOKU_TMP_DATA.'media/wiki/foo/test_image.png';
        io_makeFileDir($filepath);
        io_saveFile($filepath,'');

        saveWikiText('wiki:movetest', '{{:wiki:foo:test image.png?200|test image}}', 'Test initialized');
        (new Indexer())->addPage('wiki:movetest');

        $src = 'wiki:foo:test_image.png';
        $dst = 'wiki:foobar:test_image.png';

        /** @var helper_plugin_move_op $move */
        $move = plugin_load('helper', 'move_op');
        $this->assertTrue($move->moveMedia($src, $dst));

        $this->assertTrue(@file_exists(mediaFn('wiki:foobar:test_image.png')));

        $this->assertEquals('{{wiki:foobar:test_image.png?200|test image}}', rawWiki('wiki:movetest'));
    }
}
