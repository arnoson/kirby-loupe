<p align="center">
  <picture>
      <source media="(prefers-color-scheme: dark)" srcset="./.github/logo-dark.svg">
      <img src="./.github/logo-light.svg" alt="" />
  </picture>
</p>

<h1 align="center">Kirby Loupe</h1>

A thin wrapper around [loupe](https://github.com/loupe-php/loupe/), an SQLite based, PHP-only fulltext search engine.

## Install

```bash
composer require arnoson/kirby-loupe
```

## Options

```php
'arnoson.kirby-loupe' => [
    // Loupe options, see: https://github.com/loupe-php/loupe/blob/main/docs/configuration.md
    'configuration' => fn() => Loupe\Loupe\Configuration::create()
      ->withFilterableAttributes(['interests']),

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
        'interests' => fn($page) => $page->interests()->split()
    ]
]
```

## Usage

You have to index your existing content once. After the initial indexing the index will be updated automatically via Kirby's page hooks.

```php
// Call this once (or create a panel button with Kirby Janitor, see `/example`) 
arnoson\KirbyLoupe::reindex();
```

```php
// Inside your template/controller.

$query = 'Kirby'
$searchParameters = SearchParameters::create()
  ->withAttributesToRetrieve(['uuid'])
  ->withQuery($query)
  // Optional pagination. Note that loupe will always paginate (default is 100
  // items per page)
  ->withHitsPerPage(5)
  ->withPage(1);

$results = KirbyLoupe::loupe()->search($searchParameters);

// Convert the returned hits from loupe to kirby pages.
$hits = $results->getHit;
$hitPages = array_map(fn($hit) => page($hit['uuid']), $results->getHits())

// Do something with the pages...
```

See `example/site/templates/default.php` for a more advanced example.