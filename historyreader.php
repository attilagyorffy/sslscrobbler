#!/usr/bin/env php
<?php

/**
 *  @author      Ben XO (me@ben-xo.com)
 *  @copyright   Copyright (c) 2010 Ben XO
 *  @license     MIT License (http://www.opensource.org/licenses/mit-license.html)
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *  
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *  
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 */

require_once 'SSL/SSLParser.php';
require_once 'SSL/SSLHistoryDom.php';
require_once 'SSL/SSLHistoryPrinter.php';
require_once 'SSL/SSLRealtimeModel.php';

class HistoryReader
{
    protected $debug = false;
    protected $sleep = 2;
    protected $history_dir;
    
    /**
     * @var SSLHistoryDom
     */
    protected $tree;
    
    /**
     * @var SSLRealtimeModel
     */
    protected $rtm;
    
    public function main($argc, array $argv)
    {
        date_default_timezone_set('UTC');
    
        try
        {
            $appname = array_shift($argv);
            
            while($arg = array_shift($argv))
            {
                if($arg == '--debug')
                {
                    $this->debug = true;
                    continue;
                }
                
                $filename = $arg;
                break;
            }
            
            if(empty($filename))
            {
                // guess history file (always go for the most recently modified)
                $historydir = getenv('HOME') . '/Music/ScratchLIVE/History/Sessions';
                $filename = $this->getMostRecentFile($historydir, '.session');
                echo "Using file $filename ...\n";
            }
                            
            if(!file_exists($filename))
                throw new InvalidArgumentException("No such file $filename.");
                
            if(!is_readable($filename))
                throw new InvalidArgumentException("File $filename not readable.");
                
            $this->tree = $this->read($filename);
            
            if($this->debug)
            {
                // Sets up all the right parsing.
                //
                // There's no particular reason to assume that e.g. all Adat chunks 
                // encountered are going to be tracks, so the assumption-of-trackiness
                // is only made in the SSLHistoryDom and a Track Parser passed in to the
                // Adat chunk during the getTracks() call on the SSLHistoryDom.
                //
                // Basically, what I'm saying, is that without this line you'll just get
                // hexdumps, which is not very exciting.
                $this->tree->getTracks(); 
                
                // After the parsing has occurred, we get much more exciting debug output.
                echo $this->tree;
                
                echo "Memory usage: " . memory_get_peak_usage() . " bytes\n";
                return;
            }
            
            echo "Initial Log:\n";
            $this->output($this->tree);
            echo "************\n\n";
            
            $this->monitor($filename, $this->tree);            
        }
        catch(Exception $e)
        {   
            echo $e->getMessage() . "\n";  
            echo $e->getTraceAsString() . "\n";  
            $this->usage($appname, $argv);
        }
    }
    
    public function output(SSLHistoryDom $tree)
    {
        $sp = new SSLHistoryPrinter();
        $sp->printOut($tree);        
    }
    
    public function usage($appname, array $argv)
    {
        echo "Usage: {$appname} [--debug] <session file>\n";
    }
    
    /**
     * @return SSLHistoryDom
     */
    protected function read($filename)
    {
        $parser = new SSLParser(new SSLHistoryDom());
        $tree = $parser->parse($filename);
        $parser->close();
        return $tree;
    }
    
    // TODO
    protected function diff($tree)
    {
        return $tree;
    }
    
    protected function monitor($filename, SSLHistoryDom $tree)
    {
        $this->rtm = new SSLRealtimeModel();
        
        echo "\n\n\n\n\n\n\n\n\n";
        while(true)
        {
            sleep($this->sleep);
            $new_tree = $this->read($filename);
            $changed = $new_tree->getNewOrUpdatedTracksSince($tree);
            if(count($changed->getTracks()) > 0 )
            {
                //echo date("Y-m-d H:i:s") . " tick...";
                //echo " " . count($new_tree) . " chunks";
                $this->rtm->notify($changed);
                $this->tree = $new_tree;
                $tree = $new_tree;
                //echo "\n";
                //$this->output($changed);
            }
            $this->rtm->tick();
            //echo chr(10) . chr(27) . '[9A';
            echo $this->rtm . "\n";
            echo "Date: " . date('Y-m-d H:i:s') . " Memory Usage: " . number_format(memory_get_usage()) . " bytes\n";
        }        
    }
    
    protected function getMostRecentFile($from_dir, $type)
    {
        $newest_mtime = 0;
        $fp = '';
        
        $di = new DirectoryIterator($from_dir);
        foreach($di as $f)
        {
            if(!$f->isFile() || !substr($f->getFilename(), -4) == '.' . $type)
                continue;
    
            $mtime = $f->getMTime();
            if($mtime > $newest_mtime)
            {
                $newest_mtime = $mtime;
                $fp = $f->getPathname();
            }
        }
        if($fp) return $fp;
        throw new RuntimeException("No $type file found in $from_dir");
    }    
}

$h = new HistoryReader();
$h->main($argc, $argv);