<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate_plus\data_parser;

use Drupal\Core\Annotation\Translation;
use Drupal\migrate_plus\Annotation\DataParser;
use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Xml;

/**
 * Obtain XML data for migration using the XMLReader pull parser.
 *
 * @DataParser(
 *   id = "authenticated_xml",
 *   title = @Translation("Authenticated XML")
 * )
 */
class AuthenticatedXml extends Xml {

  /**
   * Update the configuration for the dataparserplugin.
   *
   * The XML dataParserPlugin assumes you give it all the URLs to start,
   * but I am dynamically generating them based on the batch.
   *
   * @param array|string $urls
   */
  public function updateUrls($urls) {
    if (!is_array($urls)) {
      $urls = [$urls];
    }
    $this->urls = $urls;
  }

  /**
   * {@inheritdoc}
   */
  protected function openSourceUrl($url) {
    // (Re)open the provided URL.
    $this->reader->close();

    // Clear XML error buffer. Other Drupal code that executed during the
    // migration may have polluted the error buffer and could create false
    // positives in our error check below. We are only concerned with errors
    // that occur from attempting to load the XML string into an object here.
    libxml_clear_errors();

    if (is_null($url)) {
      // No URL means no source.
      return FALSE;
    }

    // Get the XML using the data fetcher to allow us to access URLs requiring
    // authentication.
    $xml = $this->getDataFetcherPlugin()
      ->getResponseContent($url)
      ->getContents();

    return $this->reader->XML($xml, NULL, \LIBXML_NOWARNING);

  }

}