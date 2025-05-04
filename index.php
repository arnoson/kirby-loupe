<?php

use arnoson\KirbyLoupe;
use Kirby\Cms\App;
use Kirby\Cms\Page;

App::plugin("arnoson/kirby-loupe", [
  "options" => [
    "pages" => fn() => true,
    "fields" => [],
    "commands" => [
      "reindex" => function () {
        $count = KirbyLoupe::reindex();
        return [
          "status" => 200,
          "message" =>
            $count === 1 ? "Reindexed 1 page" : "Reindexed $count pages",
        ];
      },
    ],
  ],

  "fields" => [
    "loupe-reindex" => [],
  ],

  "hooks" => [
    "page.changeStatus:after" => function (Page $newPage, Page $oldPage) {
      if ($newPage->status() === "draft") {
        KirbyLoupe::loupe()->deleteDocument($newPage->uuid()->toString());
      } elseif (
        KirbyLoupe::includePage($newPage) &&
        $oldPage->status() === "draft"
      ) {
        KirbyLoupe::indexPage($newPage);
      }
    },
    "page.update:after" => function (Page $newPage) {
      if (KirbyLoupe::includePage($newPage) && $newPage->status() !== "draft") {
        KirbyLoupe::indexPage($newPage);
      } else {
        KirbyLoupe::loupe()->deleteDocument($newPage->uuid()->toString());
      }
    },
    "page.delete:before" => function (Page $page) {
      KirbyLoupe::loupe()->deleteDocument($page->uuid()->toString());
    },
  ],

  "api" => [
    "routes" => [
      [
        "pattern" => "plugin-kirby-loupe/reindex-all",
        "action" => fn() => ["count" => KirbyLoupe::reindex()],
      ],
      [
        "pattern" => "plugin-kirby-loupe/reindex-chunk/start",
        "action" => function () {
          KirbyLoupe::clearIndex();
          $uuids = [];
          $pages = site()
            ->index()
            ->filterBy(fn($page) => KirbyLoupe::includePage($page));
          foreach ($pages as $page) {
            $uuids[] = $page->uuid()->toString();
          }
          return $uuids;
        },
      ],
      [
        "pattern" => "plugin-kirby-loupe/reindex-chunk",
        "method" => "POST",
        "action" => function () {
          $uuids = kirby()->request()->data()["uuids"];
          foreach ($uuids as $uuid) {
            KirbyLoupe::indexPage(page($uuid));
          }
          return true;
        },
      ],
    ],
  ],

  "translations" => [
    "en" => [
      "arnoson.kirby-loupe.reindex-site" => "Reindex Site",
      "arnoson.kirby-loupe.success" => "Successfully reindexed {count} pages",
      "arnoson.kirby-loupe.error" => "Indexing couldn't no be completed",
      "arnoson.kirby-loupe.info-chunk-reindex" =>
        "Please keep the browser tab open until the indexing is complete",
    ],
    "de" => [
      "arnoson.kirby-loupe.reindex-site" => "Seite neu indizieren",
      "arnoson.kirby-loupe.success" =>
        "{count} Seiten erfolgreich neu indiziert",
      "arnoson.kirby-loupe.error" =>
        "Indizierung konnte nicht abgeschlossen werden",
      "arnoson.kirby-loupe.info-chunk-reindex" =>
        "Bis zum Abschluss der Indizierung bitte den Browser-Tab geöffnet lassen",
    ],
  ],
]);
