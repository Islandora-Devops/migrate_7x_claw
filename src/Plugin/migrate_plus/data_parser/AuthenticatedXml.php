<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\SimpleXml;

/**
 * Obtain XML data for migration using the XMLReader pull parser.
 *
 * @DataParser(
 *   id = "authenticated_xml",
 *   title = @Translation("Authenticated XML")
 * )
 */
class AuthenticatedXml extends SimpleXml {

  /**
   * Update the configuration for the dataparserplugin.
   *
   * The XML dataParserPlugin assumes you give it all the URLs to start,
   * but I am dynamically generating them based on the batch.
   *
   * @param array|string $urls
   *   New array of URLs to add to the FedoraDatastream processor.
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
    // Clear XML error buffer. Other Drupal code that executed during the
    // migration may have polluted the error buffer and could create false
    // positives in our error check below. We are only concerned with errors
    // that occur from attempting to load the XML string into an object here.
    libxml_clear_errors();

    $xml_data = $this->getDataFetcherPlugin()->getResponseContent($url)->getContents();
    $xml = simplexml_load_string($xml_data);
    $this->registerNamespaces($xml);
    $xpath = $this->configuration['item_selector'];
    $this->matches = $xml->xpath($xpath);
    foreach (libxml_get_errors() as $error) {
      $error_string = self::parseLibXmlError($error);
      throw new MigrateException($error_string);
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Islandora Source can provide 0 urls, we need to exit or it throws an
   * error.
   */
  protected function nextSource() {
    if (count($this->urls) == 0) {
      return FALSE;
    }
    return parent::nextSource();
  }

}
