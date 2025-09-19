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

}
