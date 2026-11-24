<?php

use arnoson\KirbyLoupe;

return [
  "arnoson.kirby-loupe" => [
    "pages" => fn($page) => $page->intendedTemplate()->name() === "item",
    "fields" => [
      "title",
      "text" => fn($page) => strip_tags($page->text()),
      "interests" => fn($page) => $page->interests()->split(),
    ],
    "searchable" => ["title", "text"],
    "filterable" => ["interests"],
  ],
];
