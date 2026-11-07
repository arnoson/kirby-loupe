<?php

namespace arnoson;

use Kirby\Cms\App;
use Kirby\Cms\Page;
use Kirby\Http\Uri;
use Kirby\Toolkit\A;
use Kirby\Toolkit\Pagination;
use Loupe\Loupe\Configuration;
use Loupe\Loupe\Loupe;
use Loupe\Loupe\LoupeFactory;
use Loupe\Loupe\SearchParameters;

class KirbyLoupe {
  static Loupe|null $loupe = null;

  public static function loupe() {
    if (static::$loupe) {
      return static::$loupe;
    }

    $userConfig = option("arnoson.kirby-loupe.configuration");
    /** @var Configuration */
    $config = is_callable($userConfig)
      ? $userConfig()
      : Configuration::create();

    $config = $config->withPrimaryKey("uuid");

    if ($searchable = option("arnoson.kirby-loupe.searchable")) {
      $config = $config->withSearchableAttributes($searchable);
    }

    if ($filterable = option("arnoson.kirby-loupe.filterable")) {
      $config = $config->withFilterableAttributes($filterable);
    }

    if ($sortable = option("arnoson.kirby-loupe.sortable")) {
      $config = $config->withSortableAttributes($sortable);
    }

    return static::$loupe = (new LoupeFactory())->create(
      kirby()->root("cache") . "/kirby-loupe",
      $config
    );
  }

  public static function includePage(Page $page) {
    $includePage = option("arnoson.kirby-loupe.pages");
    if (!is_callable($includePage)) {
      return false;
    }
    return $includePage($page);
  }

  public static function reindex(): int {
    static::loupe()->deleteAllDocuments();
    $count = 0;
    foreach (site()->index() as $page) {
      if (!static::includePage($page)) {
        continue;
      }
      static::indexPage($page);
      $count++;
    }
    return $count;
  }

  public static function clearIndex() {
    static::loupe()->deleteAllDocuments();
  }

  public static function indexPage(Page $page) {
    $fields = option("arnoson.kirby-loupe.fields");
    $data = ["uuid" => $page->uuid()->toString()];
    foreach ($fields as $key => $field) {
      if (is_callable($field)) {
        $data[$key] = $field($page);
      } else {
        $data[$field] = $page->content()->get($field)->value();
      }
    }
    static::loupe()->addDocument($data);
  }

  public static function search(
    string|null $query = null,
    string|null $filter = null,
    array|null $sort = null,
    array|int|null $paginate = null,
    SearchParameters|null $searchParams = null
  ) {
    $paginationParams = static::resolvePaginationParams($paginate);
    $paginationParams["page"] ??= 1;

    // The requested page may come straight from the URL. Loupe needs a valid
    // integer; Kirby's Pagination is left to validate the original value.
    $requestedPage = $paginationParams["page"];
    $searchPage = is_numeric($requestedPage) ? max(1, (int) $requestedPage) : 1;

    $searchParams ??= SearchParameters::create();

    $attributesToRetrieve = [
      "uuid",
      // When retrieveAttributes is empty, Loupe returns '*' (all fields).
      // This makes it impossible to distinguish between no additional
      // attributes being set and '*' being explicitly requested.
      ...A::filter(
        $searchParams->getAttributesToRetrieve(),
        fn($attribute) => $attribute !== "*"
      ),
    ];

    $searchParams = $searchParams
      ->withAttributesToRetrieve($attributesToRetrieve)
      ->withHitsPerPage($paginationParams["limit"]);

    if ($query) {
      $searchParams = $searchParams->withQuery($query);
    }

    if ($filter) {
      $searchParams = $searchParams->withFilter($filter);
    }

    if ($sort) {
      $searchParams = $searchParams->withSort($sort);
    }

    $runSearch = fn(int $page) => static::loupe()->search(
      $searchParams->withPage($page)
    );

    $result = $runSearch($searchPage);

    // The total is only known after the search. A page within range always has
    // hits, so an empty result for a page > 1 means the page is out of range.
    // Loupe reports a total of 0 in that case, so query the first page to get
    // the real total (this only happens for out-of-range requests).
    if ($searchPage > 1 && count($result->getHits()) === 0) {
      $result = $runSearch(1);
      $lastPage = max(1, $result->getTotalPages());

      if (!Pagination::$validate) {
        // Kirby is configured to clamp instead of throwing: show the last
        // valid page so hits and pagination match.
        if ($lastPage > 1) {
          $result = $runSearch($lastPage);
        }
        $paginationParams["page"] = $lastPage;
      }
      // Otherwise keep the requested page so Kirby's Pagination throws the
      // usual error page (with correct bounds), like native collections.
    }

    $pages = new KirbyLoupeCollection($result);
    $paginationParams["total"] = $result->getTotalHits();

    return $pages->paginate(...$paginationParams);
  }

  /**
   * This is the same logic as in https://github.com/getkirby/kirby/blob/4.7.0/src/Toolkit/Pagination.php#L44
   * We need to extract it since we have to get the current page before we know
   * the total amount.
   */
  private static function resolvePaginationParams(array|int|null $paginate) {
    $params = is_array($paginate) ? $paginate : ["limit" => $paginate];

    $kirby = App::instance();
    $config = $kirby->option("pagination", []);
    $request = $kirby->request();

    $params["limit"] ??= $config["limit"] ?? 20;
    $params["method"] ??= $config["method"] ?? "param";
    $params["variable"] ??= $config["variable"] ?? "page";

    if (empty($params["url"]) === true) {
      $params["url"] = new Uri($kirby->url("current"), [
        "params" => $request->params(),
        "query" => $request->query()->toArray(),
      ]);
    }

    if ($params["method"] === "query") {
      $params["page"] ??= $params["url"]->query()->get($params["variable"]);
    } elseif ($params["method"] === "param") {
      $params["page"] ??= $params["url"]->params()->get($params["variable"]);
    }

    return $params;
  }

  /**
   * An alias for Loupe\Loupe\SearchParameters::escapeFilterValue()
   */
  public static function escapeFilterValue(
    string|int|float|bool $value
  ): string {
    return SearchParameters::escapeFilterValue($value);
  }
}
