<?php

/**
 * JPEG Marker Reader
 *
 * PHP versions >=5
 *
 * LICENSE: This source file is subject to the MIT license as follows:
 * Copyright (c) 2008 P'unk Avenue, LLC
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use,
 * copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following
 * conditions:
 * 
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
 * OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
 * FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
 * OTHER DEALINGS IN THE SOFTWARE.
 *
 * @category  Image
 * @package   Image_JpegMarkerReader
 * @author    Tom Boutell <tom@punkave.com>
 * @copyright 2008 P'unk Avenue LLC
 * @license   MIT License
 * @link      http://pear.php.net/package/Image_JpegMarkerReader
 */

// /* vim: set expandtab tabstop=4 shiftwidth=4: */

require_once 'PEAR.php';
require_once 'PEAR/Exception.php';

/**
 * Read markers from a JPEG file with reasonable efficiency.
 *
 * @category  Image
 * @package   Image_JpegMarkerReader
 * @author    Tom Boutell <tom@punkave.com>
 * @copyright 2008 P'unk Avenue LLC
 * @license   MIT License
 * @link      http://pear.php.net/package/Image_JpegMarkerReader
 */

class Image_JpegMarkerReader
{
    /**
      * These JPEG markers have no data associated with them, and
      * therefore no two-byte length field to parse.
      */

    private $_standalone = array(
        0xD0,
        0xD1,
        0xD2,
        0xD3,
        0xD4,
        0xD5,
        0xD6,
        0xD7,
        0xD8,
        0xD9,
        0x01
    );

    /**
      * This marker is followed by the actual entropy-coded image data.
      * In most cases users of this class will expect it to give up at
      * this point, because metadata markers are never stored after the 
      * image data (which is hard to skip efficiently and slow to read 
      * over a network link). But for completeness we support reading
      * this marker (though not the image data, which is separate from
      * the marker payload) and will skip the image data correctly to
      * reach markers at the very end of the stream. 
      */
    private $_SOS = 0xDA;
    /**
      * The last marker read/skipped was SOS - the next read attempt
      * must skip the actual image data. Delaying that to the next read
      * attempt gives those who are fetching all markers a fighting
      * chance to notice SOS and give up before this time-consuming step.
      */
    private $_readScanNext = false;
    /**
      * By default, we don't try to skip actual image data - we consider
      * the SOS marker to be the last marker worth returning.
      */ 
    private $_readPastImageData = false;
    /**
      *
      * The file resource being read.
      */
    private $_in = false;
    /**
      *
      * The file being read from.
      */
    private $_filename = false;
    /**
      *
      * True if the end of the file has been encountered
      */
    private $_eof = false;
    /**
      * Creates a new Image_JpegMarkerReader object which will read 
      * from the specified file
      *
      * @param string $filename file to open
      */
    public function __construct($filename)
    {
        $this->_filename = $filename;
    }

    /**
      * By default, Image_JpegMarkerReader will return the SOS marker
      * (0xDA) as the last marker and begin indicating EOF at that point.
      * If this method is called with readPastImageData set to true, 
      * Image_JpegMarkerReader will skip over the actual entropy-coded 
      * image data (a very slow operation) to look for markers at the
      * very end of the file, such as (and probably limited to) EOI.
      *
      * You don't want to do this unless you absolutely have to.
      *
      * @param boolean $readPastImageData set to true if markers after image
      * data are considered interesting (warning: slow)
      *
      * @return none
      */
    public function setReadPastImageData($readPastImageData)
    {
        $this->_readPastImageData = $readPastImageData;
    }
  
    /**
      * Automatically closes the file, if it is open
      */
    public function __destruct()
    {
        if ($this->_in) {
            fclose($this->_in); 
        }
    }

    /**
      * Locates and returns the next instance of the desired marker type
      *
      * Scans through the JPEG file until a marker of the specified
      * type is encountered. JPEG marker types consist of two bytes:
      * 0xFF, and a second byte which identifies that particular marker.
      * Pass only the second byte, as an integer. See the JpegXmpReader 
      * package for an example of usage.
      *
      * If the marker is not found before EOF, returns false 
      * (test for this with === false). On success, returns the data for 
      * the specified marker, as a string.
      *
      * Note that some marker types can occur more than once in a single
      * file. You may call readMarker again if, after examining the data,
      * you determine that it is not the marker you wanted or you simply wish
      * to fetch another. 
      *
      * @param integer $m Marker byte, as an integer
      *
      * @return boolean|string
      */

    public function readMarker($m)
    {
        if (!$this->_in) {
            // Open only once, so we can read multiple matching markers;
            // but open late, so people can construct objects before using them
            // without losing convenient error return
            $this->_in = fopen($this->_filename, "rb");
            if (!$this->_in) {
                throw new Image_JpegMarkerReaderOpenException($this->_filename);
            }
        }
        while (true) {
            $result = $this->_skipMarkerIfNot($m);
            if ($result === false) {
                return false;
            }
            if ($result[0] === $m) {
                return $result[1];
            }
        }
    }

    /** 
      * Reads and returns the next marker, regardless of marker type
      *
      * When you really want the next marker no matter what it is.
      * On EOF, returns false (just false, not an array).
      * On success, returns an array of two elements: the marker type
      * (as an integer) and the marker data (as a string).
      *
      * If the marker is a _standalone marker which, by definition,
      * has no data associated with it, the second element of the array 
      * will be set to false.
      *
      * @return boolean|array
      */

    public function readNextMarker()
    {
        return $this->_skipMarkerIfNot(false);
    }

    /** 
      * Returns true if the end of the file has been encountered
      *
      * After a 'false' return from readMarker or readNextMarker,
      * This function can be used to determine whether the end of the file
      * has been encountered. EOF (or the start of image data) is also 
      * indicated by a false return from readMarker or readNextMarker 
      * (as opposed to an actual error condition, such as a damaged file, 
      * which throws a JpegMarkerReaderException).
      *
      * @return boolean
      */

    public function isEof()
    {
        return $this->_eof;
    }

    /**
      * Returns true if the start of the entropy-coded image data
      * has been reached. Note that if setReadPastImageData has been
      * called with a true value this point may be reached and then
      * skipped past depending on what markers are asked for, in which 
      * case this method will return false. In most cases you 
      * won't care whether isEof or isAtImageData returns true, but
      * if you really want to know you can tell the difference.
      *
      * @return boolean
      */
    public function isAtImageData()
    {
        return $this->_readScanNext;
    }

    /** 
      * Reads the next marker, returning data only if the type matches
      *
      * If the next marker is of type $fm, this function returns 
      * an array consisting of the marker type (as an integer) and
      * the marker data (as a string), just as readNextMarker would. 
      * If the next marker is of any other type, the
      * return value is the same, except that the data is not actually 
      * read and the second element of the array is false. 
      * On end of file, false is returned.
      * Uses fseek() for efficient skipping of uninteresting markers.
      *
      * @param integer $fm Marker byte, as an integer
      *
      * @return boolean|array
      */
    private function _skipMarkerIfNot($fm)
    {
        if ($this->_readScanNext) {
            if (!$this->_readPastImageData) {
                return false;
            }
            // Must skip actual entropy coded image scanline data at this 
            // point.
            //
            // Fortunately there is a simple rule for this. It's a SLOW
            // operation, which we support only for completeness. A
            // more efficient implementation is doubtless possible,
            // but apps don't hide useful metadata after the image data
            $this->_readScanNext = false;
            while (true) {
                $m = $this->_read();
                if ($m === false) {
                    throw new Image_JpegMarkerReaderDamagedException(
                        $this->_filename);
                }  
                if ($m === 0xFF) {
                    $m = $this->_read();
                    if ($m === false) {
                        throw new Image_JpegMarkerReaderDamagedException(
                            $this->_filename);
                    }  
                    if ($m === 0x00) {
                        // Escape sequence for literal 0xFF in data
                        continue;
                    } elseif ($m === 0xFF) {
                        // The spec says there can be extra 0xFFs for padding
                        do {
                            $m = $this->_read();
                        } while ($m == 0xFF);
                        if ($m === false) {
                            throw new Image_JpegMarkerReaderDamagedException(
                                $this->_filename);
                        }
                    }
                    break;
                }
            }
        } else {
            $m = $this->_read();
            if ($m === false) {
                $this->_eof = true;
                return false;
            }
            if ($m != 0xFF) {
                throw new Image_JpegMarkerReaderDamagedException($this->_filename);
            }
            // The spec says there can be extra 0xFFs for padding
            do {
                $m = $this->_read();
            } while ($m == 0xFF);
            if ($m === false) {
                throw new Image_JpegMarkerReaderDamagedException($this->_filename);
            }
        }
        if (in_array($m, $this->_standalone)) {
            return array($m, false);
        }
        $length = $this->_readLength($this->_in);
        if ($m == $this->_SOS) {
            $this->_readScanNext = true;
        }
        if (($m === $fm) || ($fm === false)) {
            $data = fread($this->_in, $length);
            if (strlen($data) !== $length) {
                throw new Image_JpegMarkerReaderDamagedException($this->_filename);
                return false;
            }
            return array($m, $data);
        } else {
            $result = fseek($this->_in, $length, SEEK_CUR);
            return array($m, false);
        }
    }

    /**
      * Returns the next byte from the file, as an integer
      *
      * On EOF/error, returns false.
      *
      * @return boolean|integer
      */
    private function _read() 
    {
        $byte = fread($this->_in, 1);
        if (strlen($byte) != 1) {
            if (feof($this->_in)) {
                return false;
            } else {
                throw new Image_JpegMarkerReaderDamagedException($this->_filename);
            }  
        }
        return ord($byte);
    }

    /**
      * Reads the length of the next marker
      *
      * Reads the length of the next marker. Called only for markers that
      * are not _standalone. On success, returns an integer. 
      * On failure, throws an exception.
      *
      * @return boolean|integer
      */
    private function _readLength()
    {
        $l1 = $this->_read();
        if ($l1 === false) {
            throw new Image_JpegMarkerReaderDamagedException($this->_filename);
        }
        $l2 = $this->_read();
        if ($l2 === false) {
            throw new Image_JpegMarkerReaderDamagedException($this->_filename);
        }
        $length = $l1 * 256 + $l2;
        // The two length bytes are included in the length. We
        // subtract them here to make this function less of a pain to use.
        // But first we check for a nonsensical value.
        if ($length < 2) {
            throw new Image_JpegMarkerReaderDamagedException($this->_filename);
        }
        return $length - 2;
    }
}

/**
 * Thrown when the file does not contain a valid JPEG data stream.
 * JpegMarkerReader does not promise to identify all invalid JPEG
 * data streams, only those which fail to follow the fundamental structure
 * of markers beginning with an FF byte.
 *
 * @category Image
 * @package  JpegMarkerReader
 * @author   Tom Boutell <tom@punkave.com>
 * @license  MIT License
 * @link     http://pear.php.net/package/Image_JpegMarkerReader
 */

class Image_JpegMarkerReaderDamagedException extends PEAR_Exception
{
    /**
      * Creates an exception object indicating that the stream was
      * inconsistent with the JPEG specification.
      *
      * @param string $filename file that did not meet the specification
      */
    public function __construct($filename)
    {
        parent::__construct("The file $filename is damaged or does " .
          "not contain a valid JPEG data stream.");
    }
}

/**
 * Thrown when the file cannot be opened.
 *
 * @category Image
 * @package  JpegMarkerReader
 * @author   Tom Boutell <tom@punkave.com>
 * @license  MIT License
 * @link     http://pear.php.net/package/Image_JpegMarkerReader
 */

class Image_JpegMarkerReaderOpenException extends PEAR_Exception
{
    /**
      * Creates an exception object indicating that the file could not
      * be opened.
      *
      * @param string $filename file that could not be opened
      */
    public function __construct($filename)
    {
        parent::__construct("The file $filename could not be opened.");
    }
}

