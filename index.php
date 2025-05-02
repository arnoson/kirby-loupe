<?php

use arnoson\KirbyLoupe;
use Kirby\Cms\App;
use Kirby\Cms\Page;

require "lib/KirbyLoupe.php";

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
]);
