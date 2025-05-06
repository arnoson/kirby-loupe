<p align="center">
  <picture>
      <source media="(prefers-color-scheme: dark)" srcset="./.github/logo-dark.svg">
      <img src="./.github/logo-light.svg" alt="" />
  </picture>
</p>

<h1 align="center">Kirby Loupe</h1>

A thin wrapper around [Loupe](https://github.com/loupe-php/loupe/), an SQLite based, PHP-only fulltext search engine.

## Key Features

- 🎯 Typo tolerant / fuzzy search
- 🔍 Filtering and sorting
- 📄 Built-in pagination
- 🌍 Works everywhere, no SQLite extensions needed
- ⚡ Decent performance (~100ms for 5000 documents, ~300ms on low-end shared hosting)

## Install

```bash
composer require arnoson/kirby-loupe
```

## Usage

You have to index your existing content once. After the initial indexing the index will be updated automatically via Kirby's page hooks.

```php
// Call this once or use the panel field (see #reindex)
arnoson\KirbyLoupe::reindex();
```

```php
// Inside your template/controller.

$results = KirbyLoupe::search(
  // See https://github.com/loupe-php/loupe/blob/main/docs/searching.md#query
  query: "...",

  //  See https://github.com/loupe-php/loupe/blob/main/docs/searching.md#filter
  filter: "interests = 'music'",

  // See https://github.com/loupe-php/loupe/blob/main/docs/searching.md#sort
  sort: ["title:asc"],

  // Loupe always paginates the results, default is 20.
  paginate: 100
);

foreach ($results as $result) {
  echo $result->title();
}
```

## Options

```php
'arnoson.kirby-loupe' => [
    // Which pages to include in the search.
    'pages' => fn($page) => $page->intendedTemplate()->name() === 'item',

    // Which fields to include in the search.
    'fields' => [
        // Can be just the field name ...
        'title',
        // or a callback, transforming the field for better searching.
        'text' => fn($page) => strip_tags($page->text()),
        // loupe also supports so called multi fields (arrays) allowing you to
        // do advanced filtering.
        'interests' => fn($page) => $page->interests()->split(),
    ],

    // Which fields to include in the text search, default is all fields.
    'searchable' => ['title', 'text'],

    // Which fields to include in the filtering.
    'filterable' => ['interests'],

    // Which fields to include in the sorting.
    'sortable' => ['title'],

    // Additional loupe configuration, see: https://github.com/loupe-php/loupe/blob/main/docs/configuration.md
    'configuration' => fn() => Loupe\Loupe\Configuration::create()
      ->withLanguages(['en', 'fr', 'de']),
]
```

## Reindex

This plugin will automatically update Loupe's search index via page hooks. But for your initial content, or after you have manually uploaded content, you will need to index your site explicitly. To do so either call

```php
KirbyLoupe::reindex();
```

Or use the panel field

```yaml
fields:
  reindex:
    type: loupe-reindex
```

As indexing large numbers of pages (thousands) can potentially break due to maximum execution time, especially on shared hosting, indexing via the Panel is done in chunks. The default is `100`, so 100 pages will be indexed at a time and a progress bar will be displayed. If you only have a small number of pages, or a good enough server, you can disable chunks and index all pages at once.

```yaml
fields:
  reindex:
    type: loupe-reindex
    chunk: false # disable chunks
    chunk: 500 # or increase chunk size
```

## Pagination

The result has a pagination attached, so you can build your navigation, see the `/example/site/templates/default` for a full example.

```php
<a href="<?= $results->pagination()->prevPageURL() ?>">‹</a>
<a href="<?= $results->pagination()->nextPageURL() ?>">›</a>
```

This uses Kirby's native pagination class. You can pass advanced navigation options in `search()`:

```php
KirbyLoupe::search(
  paginate: [
    "limit" => 50,
    "method" => "query",
    "variable" => "p",
  ]
);
// This will use 50 results per page and URLs in the format example.com/?p=10
```

## Loupe Search Result

```php
// Access to the raw loupe search result.
dump($results->searchResult()->getHits());
```

See `example/site/templates/default.php` for a more advanced example.
