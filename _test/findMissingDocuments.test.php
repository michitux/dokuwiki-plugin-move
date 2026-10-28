<?php

use dokuwiki\Search\Indexer;


class helper_plugin_move_plan_findMissingDocuments_mock extends helper_plugin_move_plan {

    public function findMissingDocuments($src, $dst,  $type = self::TYPE_PAGES) {
        parent::findMissingDocuments($src, $dst, $type);
    }

    public function getTmpstore() {
        return $this->tmpstore;
    }

}


/**
 * Test cases for helper_plugin_move_plan::stepThroughDocuments function of the move plugin
 *
 * @group plugin_move
 * @group plugin_move_unittests
 * @group plugins
 * @group unittests
 * @covers helper_plugin_move_plan::findMissingDocuments
 */
class plugin_move_findMissingPages_test extends DokuWikiTest {

    protected $pluginsEnabled = array('move',);
    /** @var  helper_plugin_move_plan_findMissingDocuments_mock $plan */
    protected $plan;

    /**
     * @coversNothing
     */
    public function setUp(): void {
        parent::setUp();
        $this->plan = new helper_plugin_move_plan_findMissingDocuments_mock();
    }


    /**
     * @coversNothing
     */
    public function tearDown(): void {
        global $conf;

        $dirs = array('indexdir','datadir','metadir', 'mediadir');
        foreach ($dirs as $dir) {
            io_rmdir($conf[$dir],true);
            mkdir($conf[$dir]);
        }
        $this->plan->abort();
        parent::tearDown();
    }


    function test_findMissingPages_empty () {
        $this->plan->findMissingDocuments('oldns','newns:');
        $tmpstore = $this->plan->getTmpstore();
        $this->assertSame(array(),$tmpstore['miss']);
    }

    function test_findMissingPages_missingPage_default () {
        saveWikiText('start','[[oldns:missing]]','test edit');
        (new Indexer())->addPage('start');
        $this->plan->findMissingDocuments('oldns:','newns:');
        $tmpstore = $this->plan->getTmpstore();
        $this->assertSame(array('oldns:missing' => 'newns:missing',),$tmpstore['miss']);
    }

    function test_findMissingPages_missingPage_explicit () {
        saveWikiText('start','[[oldns:missing]]','test edit');
        (new Indexer())->addPage('start');
        $this->plan->findMissingDocuments('oldns:','newns:',helper_plugin_move_plan::TYPE_PAGES);
        $tmpstore = $this->plan->getTmpstore();
        $this->assertSame(array('oldns:missing' => 'newns:missing',),$tmpstore['miss']);
    }

    function test_findMissingPages_missingPage_integrated () {
        saveWikiText('oldns:start','[[oldns:missing]] {{oldns:missing.png}}','test edit');
        (new Indexer())->addPage('oldns:start');
        $this->plan->addPageNamespaceMove('oldns', 'newns');
        $this->plan->addMediaNamespaceMove('oldns', 'newns');

        $this->plan->commit();

        $missing_file = file(TMP_DIR . '/data/meta/__move_missing');
        $this->assertSame(array("oldns:missing\tnewns:missing\n",),$missing_file,'new configuration fails');

        $missing_media_file = file(TMP_DIR . '/data/meta/__move_missing_media');
        $this->assertSame(array("oldns:missing.png\tnewns:missing.png\n",),$missing_media_file,'new configuration fails');

    }

    function test_findMissingPages_missingMedia () {
        saveWikiText('start','{{oldns:missing.png}}','test edit');
        (new Indexer())->addPage('start');
        $this->plan->findMissingDocuments('oldns:','newns:',helper_plugin_move_plan::TYPE_MEDIA);
        $tmpstore = $this->plan->getTmpstore();
        $this->assertSame(array('oldns:missing.png' => 'newns:missing.png',),$tmpstore['miss_media']);
    }

    function test_findMissingDocuments_nonMissingMedia () {
        $filepath = DOKU_TMP_DATA.'media/oldns/oldnsimage.png';
        io_makeFileDir($filepath);
        io_saveFile($filepath,'');
        saveWikiText('start','{{oldns:oldnsimage.png}}','test edit');
        (new Indexer())->addPage('start');
        $this->plan->findMissingDocuments('oldns:','newns:',helper_plugin_move_plan::TYPE_MEDIA);
        $tmpstore = $this->plan->getTmpstore();
        $this->assertSame(array(),$tmpstore['miss_media']);
    }
}
