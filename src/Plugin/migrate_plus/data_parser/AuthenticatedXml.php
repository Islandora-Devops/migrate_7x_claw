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
  protected function fetchNextRow() {
    $target_element = array_shift($this->matches);
    // If we've found the desired element, populate the currentItem and
    // currentId with its data.
    if ($target_element !== FALSE && !is_null($target_element)) {
      foreach ($this->fieldSelectors() as $field_name => $xpath) {
        foreach ($target_element->xpath($xpath) as $value) {
          if ($value->children() && !trim((string) $value)) {
            $this->currentItem[$field_name] = $value;
          }
          elseif (!trim((string) $value)){
            $this->currentItem[$field_name][] = $value->asXML();
          }
          else {
            $this->currentItem[$field_name][] = (string) $value;
          }
        }
      }
      // Reduce single-value results to scalars.
      foreach ($this->currentItem as $field_name => $values) {
        if (count($values) == 1) {
          $this->currentItem[$field_name] = reset($values);
        }
      }
    }
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
