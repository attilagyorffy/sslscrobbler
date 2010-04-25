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

require_once dirname(__FILE__) . '/../SSLStruct.php';

class SSLTrack extends SSLStruct
{
    protected
        $row,
        $deck,
        $artist, 
        $title, 
        $played, 
        $length,
        $start_time, 
        $end_time,
        $updated_at = 0,
        $added,
        $playtime,
        $fields = array()
    ;
    
    public function getParser()
    {
        $parser = file_get_contents(dirname(__FILE__) . '/SSLTrackAdat.xoup');
        if(empty($parser)) 
        {
            throw new RuntimeException('Could not load SSLTrackAdat.xoup');
        }
        return $parser;
    }
    
    public function populateFrom(array $fields)
    {
        $this->fields = $fields;
        isset($fields['row']) && $this->row = $fields['row'];
        isset($fields['title']) && $this->title = $fields['title'];
        isset($fields['artist']) && $this->artist = $fields['artist'];
        isset($fields['deck']) && $this->deck = $fields['deck'];
        isset($fields['starttime']) && $this->start_time = $fields['starttime'];
        isset($fields['endtime']) && $this->end_time = $fields['endtime'];
        isset($fields['played']) && $this->played = (bool) $fields['played'];
        isset($fields['added']) && $this->added = $fields['added'];
        isset($fields['updatedAt']) && $this->updated_at = $fields['updatedAt'];
        isset($fields['playtime']) && $this->playtime = $fields['playtime'];
        isset($fields['length']) && $this->length = $fields['length'];
    }
    
    public function getRow()
    {
        return $this->row;
    }
    
    public function getDeck()
    {
        return $this->deck;
    }
    
    public function getArtist()
    {
        return $this->artist;
    }

    public function getTitle()
    {
        return $this->title;
    }

    public function getPlayed()
    {
        return $this->played;
    }

    public function getPlayTime()
    {
        return $this->playtime;
    }

    public function getStartTime()
    {
        return $this->start_time;
    }

    public function getEndTime()
    {
        return $this->end_time;
    }
    
    public function getLength()
    {
        return $this->length;
    }
    
    public function getLengthInSeconds()
    {
        if(preg_match('/^(\d+):(\d+)\./', $this->length, $matches))
        {
            return $matches[1] * 60 + $matches[2];
        }
        return 0;
    }
    
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
    
    public function isPlayed()
    {
        return (bool) $this->played;
    }
    
    public function getStatus()
    {
        if($this->isPlayed())
        {
            if($this->getPlaytime())
            {
                // 1 N
                return 'PLAYED';
            }
            else
            {
                // 1 0
                return 'PLAYING';
            }
        }
        else
        {
            if($this->getPlaytime())
            {
                // 0 N
                return 'SKIPPED';
            }
            else
            {
                // 0 0
                return 'NEW';
            }
            
        }
    }
    
    
    public function __toString()
    {
        return sprintf("PLAYED:%s - ADDED:%s - DECK:%s - %s - %s - %s", 
            $this->played ? '1' : '0', isset($this->added) ? ($this->added ? '1' : '0') : 'X', 
            $this->deck,
            $this->artist, $this->title,  floor($this->playtime / 60) . ':' . ($this->playtime % 60)
        );
        
        // debugging
        $s = '';
        foreach($this->fields as $k => $v)
        {
            $s .= "$k => $v\n";
        }
        return $s;
    }
}