<?php

use dokuwiki\Input\Input;

/**
 * Moves only happen on a POST with a valid security token
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_request_test extends DokuWikiTest
{
    public function setUp(): void
    {
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
        // requests here have a REMOTE_USER but no groups, so allow jane by name
        global $conf;
        $conf['plugin']['move']['allowrename'] = 'jane';
    }

    public function tearDown(): void
    {
        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');
        if ($plan->isCommited()) $plan->abort();
        parent::tearDown();
    }

    private function token($user)
    {
        global $INPUT;
        $server = $_SERVER;
        $input = $INPUT;
        $_SERVER['REMOTE_USER'] = $user;
        $INPUT = new Input();
        $token = getSecurityToken();
        $_SERVER = $server;
        $INPUT = $input;
        return $token;
    }

    /**
     * A page that could be moved: one in the search index
     */
    private function page($id)
    {
        saveWikiText($id, 'text', 'created');
        (new \dokuwiki\Search\Indexer())->addPage($id, false, true);
        $this->waitForTick();
    }

    private function request($user)
    {
        $request = new TestRequest();
        $request->setServer('REMOTE_USER', $user);
        return $request;
    }

    private function rename($id, $method, $token)
    {
        $fields = ['call' => 'plugin_move_rename', 'id' => $id, 'newid' => $id . '_moved', 'sectok' => $token];
        $request = $this->request('jane');
        $response = $method === 'POST'
            ? $request->post($fields, '/lib/exe/ajax.php')
            : $request->get($fields, '/lib/exe/ajax.php');
        return (array)json_decode($response->getContent(), true);
    }

    public function testRenameWithToken()
    {
        $this->page('req:token');

        $result = $this->rename('req:token', 'POST', $this->token('jane'));

        $this->assertArrayHasKey('redirect_url', $result);
        $this->assertTrue(page_exists('req:token_moved'));
    }

    public function testRenameByGetIsRefused()
    {
        $this->page('req:get');
        $result = $this->rename('req:get', 'GET', $this->token('jane'));

        $this->assertArrayHasKey('error', $result);
        $this->assertTrue(page_exists('req:get'));
        $this->assertFalse(page_exists('req:get_moved'));
    }

    public function testRenameWithoutTokenIsRefused()
    {
        $this->page('req:notoken');
        $result = $this->rename('req:notoken', 'POST', 'not-the-real-token');

        $this->assertArrayHasKey('error', $result);
        $this->assertTrue(page_exists('req:notoken'));
        $this->assertFalse(page_exists('req:notoken_moved'));
    }

    public function testProgressWithoutTokenIsRefused()
    {
        $this->page('req:progress');

        /** @var helper_plugin_move_plan $plan */
        $plan = plugin_load('helper', 'move_plan');
        $plan->addPageMove('req:progress', 'req:progress_moved');
        $plan->commit();

        $this->request('testuser')->post(
            ['call' => 'plugin_move_progress', 'sectok' => 'not-the-real-token'],
            '/lib/exe/ajax.php'
        );

        $this->assertTrue(page_exists('req:progress'));
        $this->assertFalse(page_exists('req:progress_moved'));
    }

    public function testAdminWithoutTokenIsRefused()
    {
        saveWikiText('req:admin', 'text', 'created');

        $this->request('testuser')->post(
            ['id' => 'req:admin', 'do' => 'admin', 'page' => 'move_main', 'class' => 'page',
                'dst' => 'req:admin_moved', 'sectok' => 'not-the-real-token'],
            '/doku.php'
        );

        $this->assertFalse(plugin_load('helper', 'move_plan')->isCommited());
        $this->assertTrue(page_exists('req:admin'));
    }
}
