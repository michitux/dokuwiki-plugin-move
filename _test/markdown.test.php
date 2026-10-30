<?php

/**
 * Rewriting of Markdown links and images ($conf['syntax'] = 'md')
 *
 * @group plugin_move
 * @group plugins
 */
class plugin_move_markdown_test extends DokuWikiTest
{
    public function setUp(): void
    {
        $this->pluginsEnabled[] = 'move';
        parent::setUp();
        global $conf, $USERINFO, $INPUT;
        $conf['syntax'] = 'md';
        $_SERVER['REMOTE_USER'] = 'testuser';
        $USERINFO = ['name' => 'Test', 'mail' => 'test@example.com', 'grps' => ['admin', 'user']];
        $INPUT = new \dokuwiki\Input\Input();
    }

    /**
     * Rewrite $text as the content of $id, after $page was moved to $newPage and $media to $newMedia
     */
    private function rewrite($id, $text, $page, $newPage, $media = null, $newMedia = null)
    {
        // move metadata is only stored for pages that exist
        if (!page_exists($id)) saveWikiText($id, '# Page', 'test');

        /** @var helper_plugin_move_rewrite $rewriter */
        $rewriter = plugin_load('helper', 'move_rewrite');
        $rewriter->unsetMoveMeta($id);
        $rewriter->setMoveMeta($id, $page, $newPage, 'pages');
        if ($media !== null) $rewriter->setMoveMeta($id, $media, $newMedia, 'media');
        return $rewriter->rewrite($id, $text);
    }

    /**
     * Save a page and add it to the search index, which a move needs
     */
    private function saveIndexed($id, $text)
    {
        saveWikiText($id, $text, 'test');
        (new \dokuwiki\Search\Indexer())->addPage($id, false, true);
    }

    public static function provideLinks()
    {
        return [
            'absolute' => ['[Creation](hd:creation)', '[Creation](hd:lifecycle:creation)'],
            'bold label' => ['**[Creation](hd:creation)**: text', '**[Creation](hd:lifecycle:creation)**: text'],
            'relative' => ['[Creation](creation)', '[Creation](.lifecycle:creation)'],
            'dot relative' => ['[Creation](.:creation)', '[Creation](.lifecycle:creation)'],
            'leading colon' => ['[Creation](:hd:creation)', '[Creation](hd:lifecycle:creation)'],
            'hash' => ['[DNS](hd:creation#dns)', '[DNS](hd:lifecycle:creation#dns)'],
            'params and hash' => ['[Old](hd:creation?rev=1#dns)', '[Old](hd:lifecycle:creation?rev=1#dns)'],
            'title' => ['[Creation](hd:creation "The page")', '[Creation](hd:lifecycle:creation "The page")'],
            'spaces' => ['[Creation]( hd:creation )', '[Creation]( hd:lifecycle:creation )'],
            'two on a line' => [
                '[a](hd:creation) and [b](hd:creation)',
                '[a](hd:lifecycle:creation) and [b](hd:lifecycle:creation)',
            ],
            'other page' => ['[Repo](hd:repository_migration)', '[Repo](hd:repository_migration)'],
            'external' => ['[Slack](https://cql.slack.com/docs/hd:creation)', '[Slack](https://cql.slack.com/docs/hd:creation)'],
            'mail' => ['[Mail](someone@example.com)', '[Mail](someone@example.com)'],
            'local anchor' => ['[Up](#creation)', '[Up](#creation)'],
            'interwiki' => ['[Doku](doku>creation)', '[Doku](doku>creation)'],
            'code span' => ['`[x](hd:creation)`', '`[x](hd:creation)`'],
            'code block' => ["```\n[x](hd:creation)\n```", "```\n[x](hd:creation)\n```"],
            'empty' => ['[x]( )', '[x]( )'],
            'list' => [
                "* **[Creation](hd:creation)**: how.\n* [Repo](hd:repository_migration)",
                "* **[Creation](hd:lifecycle:creation)**: how.\n* [Repo](hd:repository_migration)",
            ],
            'nested list' => [
                "- [a](hd:creation)\n  - [b](hd:creation)\n    1. [c](hd:creation)",
                "- [a](hd:lifecycle:creation)\n  - [b](hd:lifecycle:creation)\n    1. [c](hd:lifecycle:creation)",
            ],
            'ordered list' => ["1. [a](hd:creation)\n2. b", "1. [a](hd:lifecycle:creation)\n2. b"],
            'quote' => ["> see [a](hd:creation)\n> more", "> see [a](hd:lifecycle:creation)\n> more"],
            'callout' => ["> [!INFO]\n> see [a](hd:creation)", "> [!INFO]\n> see [a](hd:lifecycle:creation)"],
            'list in quote' => ["> * [a](hd:creation)", "> * [a](hd:lifecycle:creation)"],
            'code span in list' => ["* `[x](hd:creation)` and [a](hd:creation)", "* `[x](hd:creation)` and [a](hd:lifecycle:creation)"],
            'code block in list' => [
                "* item\n\n  ```\n  [x](hd:creation)\n  ```",
                "* item\n\n  ```\n  [x](hd:creation)\n  ```",
            ],
            'code block in quote' => ["> ```\n> [x](hd:creation)\n> ```", "> ```\n> [x](hd:creation)\n> ```"],
            // headings are rendered as plain text, so a link in one isn't a link
            'heading' => ['## See [a](hd:creation)', '## See [a](hd:creation)'],
            'table' => [
                "| a | b |\n| - | - |\n| [Creation](hd:creation) | x |",
                "| a | b |\n| - | - |\n| [Creation](hd:lifecycle:creation) | x |",
            ],
        ];
    }

    /**
     * @dataProvider provideLinks
     */
    public function testLinks($text, $expected)
    {
        $this->assertSame($expected, $this->rewrite('hd:start', $text, 'hd:creation', 'hd:lifecycle:creation'));
    }

    public static function provideImages()
    {
        return [
            'image' => ['![Diagram](hd:flow.png)', '![Diagram](hd:lifecycle:flow.png)'],
            'image with size' => ['![Diagram](hd:flow.png?200x100)', '![Diagram](hd:lifecycle:flow.png?200x100)'],
            'image with title' => ['![Diagram](hd:flow.png "Flow")', '![Diagram](hd:lifecycle:flow.png "Flow")'],
            'other image' => ['![Logo](hd:logo.png)', '![Logo](hd:logo.png)'],
            'external image' => ['![x](https://example.com/hd:flow.png)', '![x](https://example.com/hd:flow.png)'],
            'image as label' => [
                '[![Diagram](hd:flow.png)](hd:creation)',
                '[![Diagram](hd:lifecycle:flow.png)](hd:lifecycle:creation)',
            ],
        ];
    }

    /**
     * @dataProvider provideImages
     */
    public function testImages($text, $expected)
    {
        $this->assertSame(
            $expected,
            $this->rewrite('hd:start', $text, 'hd:creation', 'hd:lifecycle:creation', 'hd:flow.png', 'hd:lifecycle:flow.png')
        );
    }

    /**
     * A moved page's own relative links still point to where they did
     */
    public function testRelativeLinksOfMovedPage()
    {
        $this->saveIndexed('rel:creation', '[Start](start) and [Repo](repository_migration)');
        $this->waitForTick();

        /** @var helper_plugin_move_op $op */
        $op = plugin_load('helper', 'move_op');
        $this->assertTrue($op->movePage('rel:creation', 'rel:lifecycle:creation'));
        plugin_load('helper', 'move_rewrite')->rewritePage('rel:lifecycle:creation');

        $this->assertSame('[Start](..:start) and [Repo](..:repository_migration)', rawWiki('rel:lifecycle:creation'));
    }

    /**
     * The whole move, as the wiki does it: pages linking to the moved one get rewritten
     */
    public function testMovePage()
    {
        $this->saveIndexed('mv:creation', "# Creation\n\nSee [start](start).");
        $this->saveIndexed('mv:start', "# Start\n\n* [Creation](mv:creation): how.\n* [Other](mv:other).");
        $this->waitForTick();

        /** @var helper_plugin_move_op $op */
        $op = plugin_load('helper', 'move_op');
        $this->assertTrue($op->movePage('mv:creation', 'mv:lifecycle:creation'));
        $this->assertContains('mv:start', $op->getAffectedPages());

        /** @var helper_plugin_move_rewrite $rewriter */
        $rewriter = plugin_load('helper', 'move_rewrite');
        $rewriter->rewritePage('mv:start');
        $rewriter->rewritePage('mv:lifecycle:creation');

        $this->assertSame("# Start\n\n* [Creation](mv:lifecycle:creation): how.\n* [Other](mv:other).", rawWiki('mv:start'));
        $this->assertSame("# Creation\n\nSee [start](..:start).", rawWiki('mv:lifecycle:creation'));
        $this->assertFalse(page_exists('mv:creation'));
    }
}
