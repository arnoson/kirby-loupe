<?php

namespace arnoson;

use Kirby\Cms\Collection;
use Kirby\Cms\Pagination;
use Loupe\Loupe\SearchResult;

class KirbyLoupeCollection extends Collection {
  public function __construct(protected SearchResult $searchResult) {
    parent::__construct();
    foreach ($searchResult->getHits() as $hit) {
      $this->append(page($hit["uuid"]));
    }
  }

  public function searchResult() {
    return $this->searchResult;
  }

  public function paginate(...$arguments) {
    // The original Collection class uses Pagination::for() which will
    // automatically set the total parameter based on the collection length
    // which we don't want, so we create new Pagination instance manually.
    $this->pagination = new Pagination($arguments);

    // We don't need to slice since the results are already paginated.
    return $this;
  }
}
