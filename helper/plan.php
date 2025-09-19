<?php
/**
 * Move Plugin Operation Planner
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Michael Hamann <michael@content-space.de>
 * @author     Andreas Gohr <gohr@cosmocode.de>
 */
// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

/**
 * Class helper_plugin_move_plan
 *
 * This thing prepares and keeps progress info on complex move operations (eg. where more than a single
 * object is affected).
 *
 * Please note: this has not a complex move resolver. Move operations may not depend on each other (eg. you
 * can not use a namespace as source that will only be created by a different move operation) instead all given
 * operations should be operations on the current state to come to a wanted future state. The tree manager takes
 * care of that by abstracting all moves on a DOM representation first, then submitting the needed changes (eg.
 * differences between now and wanted).
 *
 * Glossary:
 *
 *   document - refers to either a page or a media file here
 */
class helper_plugin_move_plan extends DokuWiki_Plugin {
    /** Number of operations per step  */
    const OPS_PER_RUN = 10;

    const TYPE_PAGES = 1;
    const TYPE_MEDIA = 2;
    const CLASS_NS   = 4;
    const CLASS_DOC  = 8;

    /**
     * @var array the options for this move plan
     */
    protected $options = array(); // defaults are set in loadOptions()

    /**
     * @var array holds the location of the different list and state files
     */
    protected $files = array();

    /**
     * @var array the planned moves
     */
    protected $plan = array();

    /**
     * @var array temporary holder of document lists
     */
    protected $tmpstore = array(
        'pages' => array(),
        'media' => array(),
        'ns'    => array(),
        'affpg' => array(),
        'miss'  => array(),
        'miss_media'  => array(),
    );

    /** @var helper_plugin_move_op $MoveOperator */
    protected $MoveOperator = null;

    /**
     * Constructor
     *
     * initializes state (if any) for continuiation of a running move op
     */
    public function __construct() {
        global $conf;

        // set the file locations
        $this->files = array(
            'opts'       => $conf['metadir'] . '/__move_opts',
            'pagelist'   => $conf['metadir'] . '/__move_pagelist',
            'medialist'  => $conf['metadir'] . '/__move_medialist',
            'affected'   => $conf['metadir'] . '/__move_affected',
            'namespaces' => $conf['metadir'] . '/__move_namespaces',
            'missing'    => $conf['metadir'] . '/__move_missing',
            'missing_media'    => $conf['metadir'] . '/__move_missing_media',
        );

        $this->MoveOperator = plugin_load('helper', 'move_op');

        $this->loadOptions();
    }

    /**
     * Load the current options if any
     *
     * If no options are found, the default options will be extended by any available
     * config options
     */
    protected function loadOptions() {
        // (re)set defaults
        $this->options = array(
            // status
            'committed'   => false,
            'started'     => 0,

            // counters
            'pages_all'   => 0,
            'pages_run'   => 0,
            'media_all'   => 0,
            'media_run'   => 0,
            'affpg_all'   => 0,
            'affpg_run'   => 0,

            // options
            'autoskip'    => $this->getConf('autoskip'),
            'autorewrite' => $this->getConf('autorewrite'),

            // errors
            'lasterror'   => false
        );

        // merge whatever options are saved currently
        $file = $this->files['opts'];
        if(file_exists($file)) {
            $options       = unserialize(io_readFile($file, false));
            $this->options = array_merge($this->options, $options);
        }
    }

    /**
     * Save the current options
     *
     * @return bool
     */
    protected function saveOptions() {
        return io_saveFile($this->files['opts'], serialize($this->options));
    }

    /**
     * Return the current state of an option, null for unknown options
     *
     * @param $name
     * @return mixed|null
     */
    public function getOption($name) {
        if(isset($this->options[$name])) {
            return $this->options[$name];
        }
        return null;
    }

    /**
     * Set an option
     *
     * Note, this otpion will only be set to the current instance of this helper object. It will only
     * be written to the option file once the plan gets committed
     *
     * @param $name
     * @param $value
     */
    public function setOption($name, $value) {
        $this->options[$name] = $value;
    }

    /**
     * Returns the progress of this plan in percent
     *
     * @return float
     */
    public function getProgress() {
        $max =
            $this->options['pages_all'] +
            $this->options['media_all'];

        $remain =
            $this->options['pages_run'] +
            $this->options['media_run'];

        if($this->options['autorewrite']) {
            $max += $this->options['affpg_all'];
            $remain += $this->options['affpg_run'];
        }

        if($max == 0) return 0;
        return round((($max - $remain) * 100) / $max, 2);
    }

    /**
     * Check if there is a move in progress currently
     *
     * @return bool
     */
    public function inProgress() {
        return (bool) $this->options['started'];
    }

    /**
     * Check if this plan has been committed, yet
     *
     * @return bool
     */
    public function isCommited() {
        return $this->options['committed'];
    }

    /**
     * Add a single page to be moved to the plan
     *
     * @param string $src
     * @param string $dst
     */
    public function addPageMove($src, $dst) {
        $this->addMove($src, $dst, self::CLASS_DOC, self::TYPE_PAGES);
    }

    /**
     * Add a single media file to be moved to the plan
     *
     * @param string $src
     * @param string $dst
     */
    public function addMediaMove($src, $dst) {
        $this->addMove($src, $dst, self::CLASS_DOC, self::TYPE_MEDIA);
    }

    /**
     * Add a page namespace to be moved to the plan
     *
     * @param string $src
     * @param string $dst
     */
    public function addPageNamespaceMove($src, $dst) {
        $this->addMove($src, $dst, self::CLASS_NS, self::TYPE_PAGES);
    }

    /**
     * Add a media namespace to be moved to the plan
     *
     * @param string $src
     * @param string $dst
     */
    public function addMediaNamespaceMove($src, $dst) {
        $this->addMove($src, $dst, self::CLASS_NS, self::TYPE_MEDIA);
    }

    /**
     * Plans the move of a namespace or document
     *
     * @param string $src   ID of the item to move
     * @param string $dst   new ID of item namespace
     * @param int    $class (self::CLASS_NS|self::CLASS_DOC)
     * @param int    $type  (PLUGIN_MOVE_TYPE_PAGE|self::TYPE_MEDIA)
     * @throws Exception
     */
    protected function addMove($src, $dst, $class = self::CLASS_NS, $type = self::TYPE_PAGES) {
        if($this->options['committed']) throw new Exception('plan is committed already, can not be added to');

        $src = cleanID($src);
        $dst = cleanID($dst);

        $this->plan[] = array(
            'src'   => $src,
            'dst'   => $dst,
            'class' => $class,
            'type'  => $type
        );
    }

    /**
     * Abort any move or plan in progress and reset the helper
     */
    public function abort() {
        foreach($this->files as $file) {
            @unlink($file);
        }
        $this->plan = array();
        $this->loadOptions();
        helper_plugin_move_rewrite::removeAllLocks();
    }

    /**
     * This locks up the plan and prepares execution
     *
     * the plan is reordered an the needed move operations are gathered and stored in the appropriate
     * list files
     *
     * @throws Exception if you try to commit a plan twice
     * @return bool true if the plan was committed
     */
    public function commit() {
        global $conf;

        if($this->options['committed']) throw new Exception('plan is committed already, can not be committed again');

        helper_plugin_move_rewrite::addLock();


        usort($this->plan, array($this, 'planSorter'));

        // get all the documents to be moved and store them in their lists
        foreach($this->plan as $move) {
            if($move['class'] == self::CLASS_DOC) {
                // these can just be added
                $this->addToDocumentList($move['src'], $move['dst'], $move['type']);
            } else {
                // here we need a list of content first, search for it
                $docs = array();
                $path = utf8_encodeFN(str_replace(':', '/', $move['src']));
                $opts = array('depth' => 0, 'skipacl' => true);
                if($move['type'] == self::TYPE_PAGES) {
                    search($docs, $conf['datadir'], 'search_allpages', $opts, $path);
                } else {
                    search($docs, $conf['mediadir'], 'search_media', $opts, $path);
                }

                // how much namespace to strip?
                if($move['src'] !== '') {
                    $strip = strlen($move['src']) + 1;
                } else {
                    $strip = 0;
                }
                if($move['dst']) $move['dst'] .= ':';

                // now add all the found documents to our lists
                foreach($docs as $doc) {
                    $from = $doc['id'];
                    $to   = $move['dst'] . substr($doc['id'], $strip);
                    $this->addToDocumentList($from, $to, $move['type']);
                }

                // remember the namespace move itself
                if($move['type'] == self::TYPE_PAGES) {
                    // FIXME we use this to move namespace subscriptions later on and for now only do it on
                    //       page namespace moves, but subscriptions work for both, but what when only one of
                    //       them is moved? Should it be copied then? Complicated. This is good enough for now
                    $this->addToDocumentList($move['src'], $move['dst'], self::CLASS_NS);
                }
                $this->findMissingDocuments($move['src'] . ':', $move['dst'],$move['type']);
            }
            // store what pages are affected by this move
            $this->findAffectedPages($move['src'], $move['dst'], $move['class'], $move['type']);
        }

        $this->storeDocumentLists();

        if(!$this->options['pages_all'] && !$this->options['media_all']) {
            msg($this->getLang('noaction'), -1);
            return false;
        }

        $this->options['committed'] = true;
        $this->saveOptions();

        return true;
    }

    /**
     * Execute the next steps
     *
     * @param bool $skip set to true to skip the next first step (skip error)
     * @return bool|int false on errors, otherwise the number of remaining steps
     * @throws Exception
     */
    public function nextStep($skip = false) {
        if(!$this->options['committed']) throw new Exception('plan is not committed yet!');

        // execution has started
        if(!$this->options['started']) $this->options['started'] = time();

        helper_plugin_move_rewrite::addLock();

        if(@filesize($this->files['pagelist']) > 1) {
            $todo = $this->stepThroughDocuments(self::TYPE_PAGES, $skip);
            if($todo === false) return $this->storeError();
            return max($todo, 1); // force one more call
        }

        if(@filesize($this->files['medialist']) > 1) {
            $todo = $this->stepThroughDocuments(self::TYPE_MEDIA, $skip);
            if($todo === false) return $this->storeError();
            return max($todo, 1); // force one more call
        }

        if(@filesize($this->files['missing']) > 1 && @filesize($this->files['affected']) > 1) {
            $todo = $this->stepThroughMissingDocuments(self::TYPE_PAGES);
            if($todo === false) return $this->storeError();
            return max($todo, 1); // force one more call
        }

        if(@filesize($this->files['missing_media']) > 1 && @filesize($this->files['affected']) > 1) {
            $todo = $this->stepThroughMissingDocuments(self::TYPE_MEDIA);
            if($todo === false)return $this->storeError();
            return max($todo, 1); // force one more call
        }

        if(@filesize($this->files['namespaces']) > 1) {
            $todo = $this->stepThroughNamespaces();
            if($todo === false) return $this->storeError();
            return max($todo, 1); // force one more call
        }

        helper_plugin_move_rewrite::removeAllLocks();

        if($this->options['autorewrite'] && @filesize($this->files['affected']) > 1) {
            $todo = $this->stepThroughAffectedPages();
            if($todo === false) return $this->storeError();
            return max($todo, 1); // force one more call
        }

        // we're done here, clean up
        $this->abort();
        return 0;
    }

    /**
     * Returns the list of page and media moves and the affected pages as a HTML list
     *
     * @return string
     */
    public function previewHTML() {
        $html = '';

        $html .= '<ul>';
        if(@file_exists($this->files['pagelist'])) {
            $pagelist = file($this->files['pagelist']);
            foreach($pagelist as $line) {
                list($old, $new) = explode("\t", trim($line));

                $html .= '<li class="page"><div class="li">';
                $html .= hsc($old);
                $html .= '→';
                $html .= hsc($new);
                $html .= '</div></li>';
            }
        }
        if(@file_exists($this->files['medialist'])) {
            $medialist = file($this->files['medialist']);
            foreach($medialist as $line) {
                list($old, $new) = explode("\t", trim($line));

                $html .= '<li class="media"><div class="li">';
                $html .= hsc($old);
                $html .= '→';
                $html .= hsc($new);
                $html .= '</div></li>';
            }
        }
        if(@file_exists($this->files['affected'])) {
            $medialist = file($this->files['affected']);
            foreach($medialist as $page) {
                $html .= '<li class="affected"><div class="li">';
                $html .= '↷';
                $html .= hsc($page);
                $html .= '</div></li>';
            }
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Step through the next bunch of pages or media files
     *
     * @param int  $type (self::TYPE_PAGES|self::TYPE_MEDIA)
     * @param bool $skip should the first item be skipped?
     * @return bool|int false on error, otherwise the number of remaining documents
     */
    protected function stepThroughDocuments($type = self::TYPE_PAGES, $skip = false) {

        if($type == self::TYPE_PAGES) {
            $file    = $this->files['pagelist'];
            $mark    = 'P';
            $call    = 'movePage';
            $items_run_counter = 'pages_run';
        } else {
            $file    = $this->files['medialist'];
            $mark    = 'M';
            $call    = 'moveMedia';
            $items_run_counter = 'media_run';
        }

        $doclist = fopen($file, 'a+');

        for($i = 0; $i < helper_plugin_move_plan::OPS_PER_RUN; $i++) {
            $log = "";
            $line = $this->getLastLine($doclist);
            if($line === false) {
                break;
            }
            list($src, $dst) = explode("\t", trim($line));

            // should this item be skipped?
            if($skip === true) {
                $skip = false;
            } else {
            // move the page
                if(!$this->MoveOperator->$call($src, $dst)) {
                    $log .= $this->build_log_line($mark, $src, $dst, false); // FAILURE!

                    // automatically skip this item only if wanted...
                    if(!$this->options['autoskip']) {
                        // ...otherwise abort the operation
                        fclose($doclist);
                        $return_items_run = false;
                        break;
                    }
                } else {
                    $log .= $this->build_log_line($mark, $src, $dst, true); // SUCCESS!
                }
            }

            /*
             * This adjusts counters and truncates the document list correctly
             * It is used to finalize a successful or skipped move
             */

            ftruncate($doclist, ftell($doclist));
            $this->options[$items_run_counter]--;
            $return_items_run = $this->options[$items_run_counter];
            $this->write_log($log);
            $this->saveOptions();
        }

        if ($return_items_run !== false) {
            fclose($doclist);
        }
        return $return_items_run;
    }

    /**
     * Step through the next bunch of pages that need link corrections
     *
     * @return bool|int false on error, otherwise the number of remaining documents
     */
    protected function stepThroughAffectedPages() {
        /** @var helper_plugin_move_rewrite $Rewriter */
        $Rewriter = plugin_load('helper', 'move_rewrite');

        // handle affected pages
        $doclist = fopen($this->files['affected'], 'a+');
        for($i = 0; $i < helper_plugin_move_plan::OPS_PER_RUN; $i++) {
            $page = $this->getLastLine($doclist);
            if($page === false) break;

            // rewrite it
            $Rewriter->rewritePage($page);

            // update the list file
            ftruncate($doclist, ftell($doclist));
            $this->options['affpg_run']--;
            $this->saveOptions();
        }

        fclose($doclist);
        return $this->options['affpg_run'];
    }

    /**
     * Step through all the links to missing pages that should be moved
     *
     * This simply adds the moved missing pages to all affected pages meta data. This will add
     * the meta data to pages not linking to the affected pages but this should still be faster
     * than figuring out which pages need this info.
     *
     * This does not step currently, but handles all pages in one step.
     *
     * @param int $type
     *
     * @return int always 0
     * @throws Exception
     */
    protected function stepThroughMissingDocuments($type = self::TYPE_PAGES) {
        if($type != self::TYPE_PAGES && $type != self::TYPE_MEDIA) {
            throw new Exception('wrong type specified');
        }
        /** @var helper_plugin_move_rewrite $Rewriter */
        $Rewriter = plugin_load('helper', 'move_rewrite');

        $miss = array();
        if ($type == self::TYPE_PAGES) {
            $missing_fn = $this->files['missing'];
        } else {
            $missing_fn = $this->files['missing_media'];
        }
        $missing = file($missing_fn);
        foreach($missing as $line) {
            $line = trim($line);
            if($line == '') continue;
            list($src, $dst) = explode("\t", $line);
            $miss[$src] = $dst;
        }

        $affected = file($this->files['affected']);
        foreach($affected as $page){
            $page = trim($page);

            if ($type == self::TYPE_PAGES) {
                $Rewriter->setMoveMetas($page, $miss, 'pages');
            } else {
                $Rewriter->setMoveMetas($page, $miss, 'media');
            }
        }

        unlink($missing_fn);
        return 0;
    }

    /**
     * Step through all the namespace moves
     *
     * This does not step currently, but handles all namespaces in one step.
     *
     * Currently moves namespace subscriptions only.
     *
     * @return int always 0
     * @todo maybe add an event so plugins can move more stuff?
     * @todo fixed that $src and $dst are seperated by tab, not newline. This method has no tests?
     */
    protected function stepThroughNamespaces() {
        /** @var helper_plugin_move_file $FileMover */
        $FileMover = plugin_load('helper', 'move_file');

        $lines = io_readFile($this->files['namespaces']);
        $lines = explode("\n", $lines);

        foreach($lines as $line) {
            // There is an empty line at the end of the list.
            if ($line === '') continue;

            list($src, $dst) = explode("\t", trim($line));
            $FileMover->moveNamespaceSubscription($src, $dst);
        }

        @unlink($this->files['namespaces']);
        return 0;
    }

    /**
     * Retrieve the last error from the MSG array and store it in the options
     *
     * @todo rebuild error handling based on exceptions
     *
     * @return bool always false
     */
    protected function storeError() {
        global $MSG;

        if(is_array($MSG) && count($MSG)) {
            $last                       = array_shift($MSG);
            $this->options['lasterror'] = $last['msg'];
            unset($GLOBALS['MSG']);
        } else {
            $this->options['lasterror'] = 'Unknown error';
        }
        $this->saveOptions();

        return false;
    }

    /**
     * Reset the error state
     */
    protected function clearError() {
        $this->options['lasterror'] = false;
        $this->saveOptions();
    }

    /**
     * Get the last error message or false if no error occured
     *
     * @return bool|string
     */
    public function getLastError() {
        return $this->options['lasterror'];
    }

    /**
     * Appends a page move operation in the list file
     *
     * If the src has been added before, this is ignored. This makes sure you can move a single page
     * out of a namespace first, then move the namespace somewhere else.
     *
     * @param string $src
     * @param string $dst
     * @param int    $type
     * @throws Exception
     */
    protected function addToDocumentList($src, $dst, $type = self::TYPE_PAGES) {
        if($type == self::TYPE_PAGES) {
            $store = 'pages';
        } else if($type == self::TYPE_MEDIA) {
            $store = 'media';
        } else if($type == self::CLASS_NS) {
            $store = 'ns';
        } else {
            throw new Exception('Unknown type ' . $type);
        }

        if(!isset($this->tmpstore[$store][$src])) {
            $this->tmpstore[$store][$src] = $dst;
        }
    }

    /**
     * Add the list of pages to the list of affected pages whose links need adjustment
     *
     * @param string|array $pages
     */
    protected function addToAffectedPagesList($pages) {
        if(!is_array($pages)) $pages = array($pages);

        foreach($pages as $page) {
            if(!isset($this->tmpstore['affpg'][$page])) {
                $this->tmpstore['affpg'][$page] = true;
            }
        }
    }

    /**
     * Looks up pages that will be affected by a move of $src
     *
     * Calls addToAffectedPagesList() directly to store the result
     *
     * @param string $src source namespace
     * @param string $dst destination namespace
     * @param int    $class
     * @param int    $type
     */
    protected function findAffectedPages($src, $dst, $class, $type) {
        $idx = idx_get_indexer();

        if($class == self::CLASS_NS) {
            $src_ = "$src:*"; // use wildcard lookup for namespaces
        } else {
            $src_ = $src;
        }

        $pages = array();
        if($type == self::TYPE_PAGES) {
            $pages = $idx->lookupKey('relation_references', $src_);
            $len = strlen($src);
            foreach($pages as &$page) {
                if (substr($page, 0, $len + 1) === "$src:") {
                    $page = $dst . substr($page, $len + 1);
                }
            }
            unset($page);
        } else if($type == self::TYPE_MEDIA) {
            $pages = $idx->lookupKey('relation_media', $src_);
        }

        $this->addToAffectedPagesList($pages);
    }

    /**
     * Find missing pages in the $src namespace
     *
     * @param string $src source namespace
     * @param string $dst destination namespace
     * @param int    $type either self::TYPE_PAGES or self::TYPE_MEDIA
     */
    protected function findMissingDocuments($src, $dst, $type = self::TYPE_PAGES) {
        global $conf;

        // FIXME this duplicates Doku_Indexer::getIndex()
        if ($type == self::TYPE_PAGES) {
            $fn = $conf['indexdir'] . '/relation_references_w.idx';
        } else {
            $fn = $conf['indexdir'] . '/relation_media_w.idx';
        }
        if (!@file_exists($fn)){
            $referenceidx = array();
        } else {
            $referenceidx = file($fn, FILE_IGNORE_NEW_LINES);
        }

        $len = strlen($src);
        foreach($referenceidx as $idx => $page) {
            if(substr($page, 0, $len) != "$src") continue;

            // remember missing pages
            if ($type == self::TYPE_PAGES) {
                if(!page_exists($page)) {
                    $newpage = $dst . substr($page, $len);
                    $this->tmpstore['miss'][$page] = $newpage;
                }
            } else {
                if(!file_exists(mediaFN($page))){
                    $newpage = $dst . substr($page, $len);
                    $this->tmpstore['miss_media'][$page] = $newpage;
                }
            }
        }
    }

    /**
     * Store the aggregated document lists in the file system and reset the internal storage
     *
     * @throws Exception
     */
    protected function storeDocumentLists() {
        $lists = array(
            'pages' => $this->files['pagelist'],
            'media' => $this->files['medialist'],
            'ns'    => $this->files['namespaces'],
            'affpg' => $this->files['affected'],
            'miss'  => $this->files['missing'],
            'miss_media'  => $this->files['missing_media'],
        );

        foreach($lists as $store => $file) {
            // anything to do?
            $count = count($this->tmpstore[$store]);
            if(!$count) continue;

            // prepare and save content
            $data                   = '';
            $this->tmpstore[$store] = array_reverse($this->tmpstore[$store]); // store in reverse order
            foreach($this->tmpstore[$store] as $src => $dst) {
                if($dst === true) {
                    $data .= "$src\n"; // for affected pages only one ID is saved
                } else {
                    $data .= "$src\t$dst\n";
                }

            }
            io_saveFile($file, $data);

            // set counters
            if($store != 'ns') {
                $this->options[$store . '_all'] = $count;
                $this->options[$store . '_run'] = $count;
            }

            // reset the list
            $this->tmpstore[$store] = array();
        }
    }

    /**
     * Get the last line from the list that is stored in the file that is referenced by the handle
     * The handle is set to the newline before the file id
     *
     * @param resource $handle The file handle to read from
     * @return string|bool the last id from the list or false if there is none
     */
    protected function getLastLine($handle) {
        // begin the seek at the end of the file
        fseek($handle, 0, SEEK_END);
        $line = '';

        // seek one backwards as long as it's possible
        while(fseek($handle, -1, SEEK_CUR) >= 0) {
            $c = fgetc($handle);
            if($c === false) return false; // EOF, i.e. the file is empty
            fseek($handle, -1, SEEK_CUR); // reset the position to the character that was read

            if($c == "\n") {
                if($line === '') {
                    continue; // this line was empty, continue
                } else {
                    break; // we have a line, finish
                }
            }

            $line = $c . $line; // prepend char to line
        }

        if($line === '') return false; // beginning of file reached and no content

        return $line;
    }

    /**
     * Callback for usort to sort the move plan
     *
     * @param $a
     * @param $b
     * @return int
     */
    public function planSorter($a, $b) {
        // do page moves before namespace moves
        if($a['class'] == self::CLASS_DOC && $b['class'] == self::CLASS_NS) {
            return -1;
        }
        if($a['class'] == self::CLASS_NS && $b['class'] == self::CLASS_DOC) {
            return 1;
        }

        // do pages before media
        if($a['type'] == self::TYPE_PAGES && $b['type'] == self::TYPE_MEDIA) {
            return -1;
        }
        if($a['type'] == self::TYPE_MEDIA && $b['type'] == self::TYPE_PAGES) {
            return 1;
        }

        // from here on we compare only apples to apples
        // we sort by depth of namespace, deepest namespaces first

        $alen = substr_count($a['src'], ':');
        $blen = substr_count($b['src'], ':');

        if($alen > $blen) {
            return -1;
        } elseif($alen < $blen) {
            return 1;
        }
        return 0;
    }

    /**
     * Create line to log result of an operation
     *
     * @param string $type
     * @param string $from
     * @param string $to
     * @param bool   $success
     *
     * @return string
     *
     * @author Andreas Gohr <gohr@cosmocode.de>
     * @author Michael Große <grosse@cosmocode.de>
     */
    public function build_log_line ($type, $from, $to, $success) {
        global $MSG;

        $now = time();
        $date   = date('Y-m-d H:i:s', $now); // for human readability
        if($success) {
            $ok  = 'success';
            $msg = '';
        } else {
            $ok  = 'failed';
            $msg = $MSG[count($MSG) - 1]['msg']; // get detail from message array
        }

        $log = "$now\t$date\t$type\t$from\t$to\t$ok\t$msg\n";
        return $log;
    }

    /**
     * write log to file
     *
     * @param $log
     */
    protected function write_log ($log) {
        global $conf;
        $optime = $this->options['started'];
        $file   = $conf['cachedir'] . '/move/' . dformat($optime, '%Y%m%d-%H%M%S') . '.log';
        io_saveFile($file, $log, true);
    }

}
