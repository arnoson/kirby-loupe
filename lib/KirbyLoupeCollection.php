<?php

namespace arnoson;

use Kirby\Cms\Collection;
use Kirby\Cms\Page;
use Kirby\Cms\Pagination;
use Loupe\Loupe\SearchResult;

class KirbyLoupeCollection extends Collection {
  private array $hitsByUuid = [];

  public function __construct(protected SearchResult $searchResult) {
    parent::__construct();
    foreach ($searchResult->getHits() as $hit) {
      $uuid = $hit["uuid"];
      $this->hitsByUuid[$uuid] = $hit;
      $this->append(page($uuid));
    }
  }

  public function searchResult() {
    return $this->searchResult;
  }

  /**
   * Get the raw search hit from Loupe for the specified page.
   */
  public function hit(Page $page) {
    return $this->hitsByUuid[$page->uuid()->toString()];
  }

  /**
   * Get the formatted attributes from Loupe for the specified page.
   */
  public function formatted(Page $page) {
    return $this->hit($page)["_formatted"];
  }

  public function paginate(...$arguments): static {
    // The original Collection class uses Pagination::for() which will
    // automatically set the total parameter based on the collection length
    // which we don't want, so we create new Pagination instance manually.
    $this->pagination = new Pagination($arguments);

    // We don't need to slice since the results are already paginated.
    return $this;
  }
}
