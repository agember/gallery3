<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * JPEG XMP Reader
 *
 * PHP version 5
 *
 * LICENSE:
 *
 * This source file is subject to the MIT license as follows:
 *
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
 * @package   Image_JpegXmpReader
 * @author    Tom Boutell <tom@punkave.com>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008-2009 P'unk Avenue LLC, 2009 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @version   $Id$
 * @link      http://pear.php.net/package/Image_JpegXmpReader
 * @since     File available since release 0.5.0.
 */

/**
 * JpegMarkerReader class definition.
 */
require_once MODPATH . 'xmp/lib/Image/JpegMarkerReader.php';

/**
 * Reads Photoshop-style XMP metadata from a JPEG file with reasonable
 * efficiency
 *
 * By default, the XMP tags read in the methods of this class are in the
 * Dublin-Core (dc) namespace.
 *
 * @category  Image
 * @package   Image_JpegXmpReader
 * @author    Tom Boutell <tom@punkave.com>
 * @author    Michael Gauthier <mike@silverorange.com>
 * @copyright 2008-2009 P'unk Avenue LLC, 2009 silverorange
 * @license   http://www.opensource.org/licenses/mit-license.html MIT License
 * @link      http://pear.php.net/package/Image_JpegXmpReader
 * @since     Class available since release 0.5.0.
 */
class Image_JpegXmpReader extends Image_JpegMarkerReader
{
    /**
     * APP1 JPEG marker
     *
     * This designates the location of an XMP packet embedded in a JPEG data-
     * stream.
     *
     * @since Constant available since release 0.6.0.
     */
    const MARKER_APP1 = 0xE1;

    /**
     * The XMP basic schema namespace used to detect an XMP packet
     *
     * Note: this must be null-terminated.
     *
     * @since Constant available since release 0.6.0.
     */
    const MARKER_NS = "http://ns.adobe.com/xap/1.0/\x00";

    /**
     * RDF schema namespace
     *
     * @since Constant available since release 0.6.0.
     */
    const NS_RDF  = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#';

    /**
     * Dublin Core schema namespace
     *
     * @since Constant available since release 0.6.0.
     */
    const NS_DC   = 'http://purl.org/dc/elements/1.1/';

    /**
     * EXIF schema namespace
     *
     * @since Constant available since release 0.6.0.
     */
    const NS_EXIF = 'http://ns.adobe.com/exif/1.0/';

    /**
     * EXIF schema for additional exif properties
     *
     * @since Constant available since release 0.6.0.
     */
    const NS_AUX  = 'http://ns.adobe.com/exif/1.0/aux';

    /**
     * EXIF schema for TIFF properties namespace
     *
     * @since Constant available since release 0.6.0.
     */
    const NS_TIFF = 'http://ns.adobe.com/tiff/1.0/';

    /**
     * A SimpleXMLElement object containing the XMP data read from the JPEG
     * file
     *
     * Rather than using this directly, call the
     * {@link Image_JpegXmpReader::readXmp()} method. The results are cached
     * in this variable.
     *
     * @var SimpleXMLElement|boolean
     *
     * @see Image_JpegXmpReader::readXmp()
     */
    protected $simpleXml = false;

    /**
     * A DOMDocument object containing the XMP data read from the JPEG file
     *
     * Rather than using this directly, call the
     * {@link Image_JpegXmpReader::getDocument()} method. The results are
     * cached in this variable.
     *
     * @var DOMDocument|boolean
     *
     * @see Image_JpegXmpReader::getDocument()
     */
    protected $document = false;

    /**
     * A DOMXPath object which can be used to query the XMP data
     *
     * Rather than using this directly, call the
     * {@link Image_JpegXmpReader::getXPath()} method. The results are cached
     * in this variable.
     *
     * @var DOMXPath|boolean
     *
     * @see Image_JpegXmpReader::getXPath()
     */
    protected $xpath = false;

    /**
     * An array containing the namespaces of the XMP data
     *
     * Rather than using this directly, call the
     * {@link Image_JpegXmpReader::getNamespaces()} method. The results are
     * cached in this variable.
     *
     * The array is indexed by namespace id. The array values are the prefixes
     * used in the XMP data XML.
     *
     * @var array|boolean
     *
     * @see Image_JpegXmpReader::getNamespaces()
     */
    protected $namespaces = false;

    /**
     * Creates a new JpegXmpReader object which will read from the specified
     * file
     *
     * @param string $filename the JPEG file to open.
     */
    public function __construct($filename)
    {
        parent::__construct($filename);
    }

    /**
     * Reads the next (typically the only) XMP metadata marker in the file
     *
     * On success, returns a SimpleXML object. You don't have to call this
     * method directly if you are not interested in accessing the XML
     * directly. Just call {@link Image_JpegXmpReader::getTitle()},
     * {@link Image_JpegXmpReader::getDescription()}, and so on, which
     * automatically call <kbd>readXmp()</kbd> if it has not already been
     * called at least once. Calling this method yourself is also a good way
     * to check whether a valid XMP metadata marker is present in the file at
     * all.
     *
     * Since version 0.5.1, if no XMP data is present, this method returns
     * false. This matches the behavior expected by
     * {@link Image_JpegXmpReader::getField()}.
     *
     * If an unexpected condition occurs, such as failure to open the image
     * file or a file which is not a valid JPEG data-stream, a
     * {@link Image_JpegMarkerReaderOpenException} or
     * {@link Image_JpegMarkerReaderDamagedException} will be thrown.
     *
     * Warning: XMP loves namespaces. This method registers the relevant
     * namespaces as an aid to making successful queries against the XMP
     * object, but var_dump() may not report anything if called on the XMP
     * object. That's normal. Again, for an easier interface, use the various
     * "get" methods of this class.
     *
     * @return boolean|SimpleXML a SimpleXML object on success and false on
     *                           failure.
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @since Method available since release 0.5.0.
     */
    public function readXmp()
    {
        while (true) {
            $data = $this->readMarker(self::MARKER_APP1);
            if ($data === false) {
                return false;
            }
            $id = self::MARKER_NS;
            if (substr($data, 0, strlen($id)) !== $id) {
                // Keep looking for another APP1 marker. This will be
                // necessary if a file also has EXIF, for instance.
                continue;
            }
            $data = substr($data, strlen($id));
            break;
        }

        // Ignore the weird nulls and @'s and crap that surround XMP,
        // extract the juicy XML goodness.
        $matches    = array();
        $expression = '!
            (
                <                   # start opening tag
                    (?P<ns>[^<>]+?) # opening tag namespace
                    :xmpmeta.*?
                >                   # end opening tag
                .*?                 # tag content
                </                  # start closing tag
                    (?P=ns)          # closing tag namespace (matches opening)
                    :xmpmeta
                >                   # end closing tag
            )
            !sx';

        if (preg_match($expression, $data, $matches) === 1) {
            $data = "<?xml version='1.0'?" . ">\n" . $matches[1];

            $document = new DOMDocument();
            if (!$document->loadXML($data)) {
                return false;
            }

            $this->simpleXml = simplexml_import_dom($document);

            if ($this->simpleXml === false) {
                return false;
            }

            $this->document = $document;
            $this->xpath    = new DOMXPath($this->document);

            // register XPath namespaces
            $namespaces = $this->getNamespaces();
            foreach ($namespaces as $namespace => $prefix) {
                $this->xpath->registerNamespace($prefix, $namespace);
            }

            return $this->simpleXml;
        } else {
            return false;
        }
    }

    /**
     * @return DOMDocument|boolean
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @since Method available since release 0.6.0.
     */
    public function getDocument()
    {
        if ($this->document === false) {
            if ($this->readXmp() === false) {
                return false;
            }
        }

        return $this->document;
    }

    /**
     * @return DOMXPath|boolean
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @since Method available since release 0.6.0.
     */
    public function getXPath()
    {
        if ($this->xpath === false) {
            if ($this->readXmp() === false) {
                return false;
            }
        }

        return $this->xpath;
    }

    /**
     * Gets the namespaces of the XMP data
     *
     * Returns an arrays indexed by namespace id. The array values are the
     * prefixes used in the XMP data XML.
     *
     * Returns false if no valid XMP metadata markers are present in the file.
     *
     * @return array|boolean
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @since  Method available since release 0.6.0.
     */
    protected function getNamespaces()
    {
        if ($this->namespaces === false) {
            if ($this->simpleXml === false) {
                if ($this->readXmp() === false) {
                    return false;
                }
            }
            $this->namespaces = array_flip(
                $this->simpleXml->getNamespaces(true)
            );
        }

        return $this->namespaces;
    }

    /**
     * Retrieves title fields
     *
     * Returns an array consisting of all title fields found in the XMP
     * metadata (most images only have one, so you may prefer to just call
     * {@link Image_JpegXmpReader::getTitle()}).
     *
     * Returns false if no valid XMP metadata markers are present in the file.
     *
     * @return boolean|array
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getTitle()
     */
    public function getTitles()
    {
        return $this->getField('title');
    }

    /**
     * Retrieves title field or fields as a single string
     *
     * Returns a string consisting of all title fields found in the XMP
     * metadata. If more than one is present, they are joined by newlines. If
     * there are no valid XMP metadata markers in the file, this method returns
     * false.
     *
     * @return boolean|string
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getTitles()
     */
    public function getTitle()
    {
        return $this->getImplodedField('title');
    }

    /**
     * Retrieves description fields
     *
     * Returns an array consisting of all description fields found in the XMP
     * metadata (most images only have one, so you may prefer to just call
     * {@link Image_JpegXmpReader::getDescription()).
     *
     * Returns false if no valid XMP metadata markers are present in the file.
     *
     * See also getDescription().
     *
     * @return boolean|array
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getDescription()
     */
    public function getDescriptions()
    {
        return $this->getField('description');
    }

    /**
     * Retrieves description field or fields as a single string
     *
     * Returns a string consisting of all description fields found in the XMP
     * metadata. If more than one is present, they are joined by newlines. If
     * there are no valid XMP metadata markers in the file, this method returns
     * false.
     *
     * @return boolean|string
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getDescriptions()
     */
    public function getDescription()
    {
        return $this->getImplodedField('description');
    }

    /**
     * Retrieves subject fields
     *
     * Returns an array consisting of all subject fields found in the XMP
     * metadata (most images only have one, so you may prefer to just call
     * {@link Image_JpegXmpReader::getSubject()}).
     *
     * Returns false if no valid XMP metadata markers are present in the file.
     *
     * @return boolean|array
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getSubject()
     */
    public function getSubjects()
    {
        return $this->getField('subject');
    }

    /**
     * Retrieves subject field or fields as a single string
     *
     * Returns a string consisting of all subject fields found in the XMP
     * metadata. If more than one is present, they are joined by newlines. If
     * there are no valid XMP metadata markers in the file, this method returns
     * false.
     *
     * @return boolean|string
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getSubjects()
     */
    public function getSubject()
    {
        return $this->getImplodedField('subject');
    }

    /**
     * Retrieves creator fields
     *
     * Returns an array consisting of all creator fieldsfound in the XMP
     * metadata (most images only have one, so you may prefer to just call
     * {@link Image_JpegXmpReader::getCreator()}).
     *
     * Returns false if no valid XMP metadata markers are present in the file.
     *
     * @return boolean|array
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getCreator()
     */
    public function getCreators()
    {
        return $this->getField('creator');
    }

    /**
     * Retrieves creator field or fields as a single string
     *
     * Returns a string consisting of all creator fields found in the XMP
     * metadata. If more than one is present, they are joined by newlines. If
     * there are no valid XMP metadata markers in the file, this method returns
     * false.
     *
     * @return boolean|string
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getCreators()
     */
    public function getCreator()
    {
        return $this->getImplodedField('creator');
    }

    /**
     * Retrieves all instances of a specified field
     *
     * Returns an array of strings consisting of all instances of the specified
     * field found in the XMP metadata.
     *
     * Returns false if no valid XMP metadata markers are present in the file.
     *
     * Also returns false if the specified field is not present.
     *
     * @param string $field     the name of the field to retrieve.
     * @param string $namespace optional. The schema namespace of the field. If
     *                          not specified, the Dublin Code namespace
     *                          ({@link Image_JpegXmpReader::NS_DC}) is used.
     *
     * @return boolean|array
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getImplodedField()
     */
    public function getField($field, $namespace = self::NS_DC)
    {
        if ($this->xpath === false) {
            if ($this->readXmp() === false) {
                return false;
            }
        }

        if (!isset($this->namespaces[$namespace])) {
            return false;
        }

        $prefix    = $this->namespaces[$namespace];
        $rdfPrefix = $this->namespaces[self::NS_RDF];

        // first check for RDF data
        $elements = $this->xpath->query(
            "//{$prefix}:{$field}//{$rdfPrefix}:li/text()"
        );

        // then check for non-RDF data as elements
        if ($elements->length === 0) {
            $elements = $this->xpath->query("//{$prefix}:{$field}/text()");
        }

        // then check for non-RDF data as attributes
        if ($elements->length === 0) {
            $elements = $this->xpath->query("//@{$prefix}:{$field}");
        }

        $values = array();
        foreach ($elements as $element) {
            if ($element instanceof DOMAttr) {
                $values[] = $element->value;
            } else {
                $values[] = $element->data;
            }
        }
        return $values;
    }

    /**
     * Retrieves all instances of a specified field as a single string
     *
     * Returns a string consisting of all occurrences of the specified field
     * found in the XMP metadata. If more than one is present, they are joined
     * by newlines. If there are no valid XMP metadata markers in the file,
     * this method returns false. This method also returns false if the
     * specified field does not occur in the file.
     *
     * @param string $field     the name of the field to retrieve.
     * @param string $namespace optional. The schema namespace of the field. If
     *                          not specified, the Dublin Code namespace
     *                          ({@link Image_JpegXmpReader::NS_DC}) is used.
     *
     * @return boolean|string
     *
     * @throws Image_JpegMarkerReaderOpenException if the image file could not
     *         be opened.
     * @throws Image_JpegMarkerReaderDamagedException if the image file is
     *         not a valid JPEG data-stream.
     *
     * @see Image_JpegXmpReader::getField()
     */
    public function getImplodedField($field, $namespace = self::NS_DC)
    {
        $result = $this->getField($field, $namespace);
        if ($result) {
            return implode("\n", $result);
        }
        return false;
    }
}

?>
