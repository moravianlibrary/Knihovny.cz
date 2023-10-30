<?php

declare(strict_types=1);

namespace KnihovnyCz\RecordDriver\Feature;

use VuFind\XSLT\Processor as XSLTProcessor;

/**
 * Trait BibframeTrait
 *
 * @category Knihovny.cz
 * @package  KnihovnyCz\RecordDriver\Feature
 * @author   Josef Moravec <moravec@mzk.cz>
 * @license  https://opensource.org/licenses/gpl-2.0.php GNU General Public License
 * @link     https://knihovny.cz Main Page
 */
trait BibframeTrait
{
    /**
     * Get an XML RDF representation of the data in this record using BIBFRAME
     * and MADS ontologies
     *
     * @return mixed XML RDF data (empty if unsupported or error).
     */
    public function getBibframeRdfXml()
    {
        return XSLTProcessor::process(
            'marc2bibframe2.xsl',
            trim($this->getMarcReader()->toFormat('MARCXML')),
            [
                'baseuri' => 'https://www.knihovny.cz/Record/',
                'id' => $this->getUniqueID(),
            ]
        );
    }
}
