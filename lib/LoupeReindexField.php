<?php

namespace arnoson;

use Kirby\Form\Field\DisplayField;
use Kirby\Reflection\Attributes\Prop;

/**
 * Panel field with a button to reindex the site
 */
class LoupeReindexField extends DisplayField
{
  /**
   * Reindex in chunks (`true`, or the number of pages per chunk) to
   * avoid timeouts, or all at once (`false`)
   */
  #[Prop]
  protected bool|int $chunk = true;

  public function chunk(): bool|int
  {
    return $this->chunk;
  }
}
