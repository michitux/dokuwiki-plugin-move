<?php

/**
 * Test cases for the move plugin
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_handler_test extends DokuWikiTest {

    public function setUp(): void {
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
    }

    public function test_relativeLink() {
        $handler = new helper_plugin_move_handler();
        $handler->init('deep:namespace:page', 'used:to:be:here', array(), array(), array());

        $tests = array(
            'deep:namespace:new1' => 'new1',
            'deep:new2'  => '..:new2',
            'new3'   => ':new3', // absolute is shorter than relative
            'deep:namespace:deeper:new4' => '.deeper:new4',
            'deep:namespace:deeper:deepest:new5' => '.deeper:deepest:new5',
            'deep:foobar:new6'  => '..:foobar:new6',
        );

        foreach($tests as $new => $rel) {
            $this->assertEquals($rel, $handler->relativeLink('foo', $new, 'page'));
        }

        $this->assertEquals('.deeper:', $handler->relativeLink('.deeper:', 'deep:namespace:deeper:start', 'page'));
        $this->assertEquals('.:', $handler->relativeLink('.:', 'deep:namespace:start', 'page'));
    }

    public function test_resolveMoves() {
        $handler = new helper_plugin_move_handler();
        $handler->init(
            'deep:namespace:page',
            'used:to:be:here',
            array(
                 array('used:to:be:here', 'deep:namespace:page'),
                 array('foo', 'bar'),
                 array('used:to:be:this1', 'used:to:be:that1'),
                 array('used:to:be:this2', 'deep:namespace:that1'),
                 array('used:to:be:this3', 'deep:that3'),
                 array('deep:that3', 'but:got:moved3'),
            ),
            array(),
            array()
        );

        $tests = array(
            'used:to:be:here' => 'deep:namespace:page', // full link to self
            ':foo' => 'bar', // absolute link that moved
            ':bang' => 'bang', // absolute link that did not move
            'foo' => 'used:to:be:foo', // relative link that did not move
            'this1' => 'used:to:be:that1', // relative link that did not move but is in move list
            'this2' => 'deep:namespace:that1', // relative link that moved
            'this3' => 'but:got:moved3', // relative link that moved twice
        );

        foreach($tests as $match => $id) {
            $this->assertEquals($id, $handler->resolveMoves($match, 'page'));
        }
    }

    public function test_rewriteInterwikiLink() {
        $handler = new helper_plugin_move_handler();
        $handler->init(
            'test:page',
            'test:page',
            array(
                array('used:to:be:this1', 'used:to:be:that1'),
                array('old:page', 'new:page'),
            ),
            array(
                array('foo:bar.png', 'baz:qux.png'),
                array('fusion:server:old.png', 'fusion:server:new.png'),
            ),
            array()
        );

        $tests = array(
            // Mode 0: query parameter (user case)
            '[[this>lib/exe/fetch.php?media=fusion:server:old.png|24x7]]'
                => '[[this>lib/exe/fetch.php?media=fusion:server:new.png|24x7]]',
            // Mode 0: without label
            '[[this>lib/exe/fetch.php?media=foo:bar.png]]'
                => '[[this>lib/exe/fetch.php?media=baz:qux.png]]',
            // Mode 0: with extra params before and after
            '[[this>lib/exe/fetch.php?tok=123&media=foo:bar.png&w=400|title]]'
                => '[[this>lib/exe/fetch.php?tok=123&media=baz:qux.png&w=400|title]]',
            // Mode 0: detail.php
            '[[this>lib/exe/detail.php?media=foo:bar.png|title]]'
                => '[[this>lib/exe/detail.php?media=baz:qux.png|title]]',
            // Mode 1: _media
            '[[this>_media/foo:bar.png|title]]'
                => '[[this>_media/baz:qux.png|title]]',
            // Mode 1: _detail
            '[[this>_detail/foo:bar.png|title]]'
                => '[[this>_detail/baz:qux.png|title]]',
            // Mode 1: slash separator
            '[[this>_media/foo/bar.png|title]]'
                => '[[this>_media/baz/qux.png|title]]',
            // Mode 2: fetch.php PATH_INFO
            '[[this>lib/exe/fetch.php/foo:bar.png|title]]'
                => '[[this>lib/exe/fetch.php/baz:qux.png|title]]',
            // Mode 2: fetch.php PATH_INFO with slashes
            '[[this>lib/exe/fetch.php/foo/bar.png|title]]'
                => '[[this>lib/exe/fetch.php/baz/qux.png|title]]',
            // Page move: doku.php?id=
            '[[this>doku.php?id=used:to:be:this1|title]]'
                => '[[this>doku.php?id=used:to:be:that1|title]]',
            // Page move: doku.php PATH_INFO (Mode 2)
            '[[this>doku.php/used:to:be:this1|title]]'
                => '[[this>doku.php/used:to:be:that1|title]]',
            // URL-encoded colons
            '[[this>lib/exe/fetch.php?media=foo%3Abar.png|title]]'
                => '[[this>lib/exe/fetch.php?media=baz%3Aqux.png|title]]',
            // External wiki (untouched)
            '[[otherwiki>lib/exe/fetch.php?media=foo:bar.png|title]]'
                => '[[otherwiki>lib/exe/fetch.php?media=foo:bar.png|title]]',
            // Unrelated media (untouched)
            '[[this>lib/exe/fetch.php?media=unrelated:file.png|title]]'
                => '[[this>lib/exe/fetch.php?media=unrelated:file.png|title]]',
        );

        foreach($tests as $input => $expected) {
            $handler->wikitext = '';
            $handler->internallink($input, DOKU_LEXER_SPECIAL, 0);
            $this->assertEquals($expected, $handler->wikitext);
        }
    }

}
