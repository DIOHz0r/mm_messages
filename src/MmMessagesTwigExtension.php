<?php

namespace Drupal\mm_messages;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension.
 */
class MmMessagesTwigExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter('format_time', [$this, 'formatTimeFilter']),
    ];
  }

  /**
   * Call module function to format the timestamps.
   *
   * @param $timestamp
   *
   * @return string
   */
  public function formatTimeFilter($timestamp): string {
    return mm_messages_format_time($timestamp);
  }

}
