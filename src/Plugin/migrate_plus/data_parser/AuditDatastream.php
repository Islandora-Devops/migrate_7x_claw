<?php

namespace Drupal\migrate_7x_claw\Plugin\migrate_plus\data_parser;

use Drupal\migrate_plus\Plugin\migrate_plus\data_parser\Xml;

/**
 * Obtain XML data for migration using the XMLReader pull parser.
 *
 * @DataParser(
 *   id = "audit_datastream",
 *   title = @Translation("Audit Datastream")
 * )
 */
class AuditDatastream extends Xml {

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
    // Probably a hack...
    $url = preg_replace('#objectXML$#', 'export', $url);

    $this->reader->close();
    libxml_clear_errors();

    if (is_null($url)) {
      return FALSE;
    }

    $xml = $this->getDataFetcherPlugin()
      ->getResponseContent($url)
      ->getContents();

    $dom = new \DOMDocument();
    $dom->loadXML($xml);
    foreach ($dom->getElementsByTagNameNS('info:fedora/fedora-system:def/audit#', 'auditTrail') as $auditTrail) {
      $audit_trail_xml = $dom->saveXML($auditTrail);
    }

    return $this->reader->XML($audit_trail_xml, NULL, \LIBXML_NOWARNING);
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
