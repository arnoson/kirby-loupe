<?php

use arnoson\KirbyLoupe;
use Kirby\Filesystem\Dir;
use Kirby\Toolkit\Str;

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

  "routes" => [
    [
      "pattern" => "/seed/(:num)",
      "action" => function ($count) {
        if (!kirby()->user()) {
          return;
        }
        set_time_limit(0);

        if (page("items")) {
          Dir::remove(page("items")->root());
        }
        page("items")?->delete(true);
        $items = site()
          ->createChild([
            "slug" => "items",
            "template" => "items",
          ])
          ->publish();

        $faker = \Faker\Factory::create();
        for ($i = 0; $i < $count; $i++) {
          $name = $faker->name();
          // prettier-ignore
          $allInterests = ["technology", "design", "art", "science", "music", "food", "travel", "sports", "nature", "business"];
          $interests = array_rand(array_flip($allInterests), rand(1, 3));
          $interests = is_array($interests) ? $interests : [$interests];
          $items
            ->createChild([
              "slug" => Str::slug($name . "-" . rand(1000, 9999)),
              "template" => "item",
              "content" => [
                "title" => $name,
                "interests" => implode(", ", $interests),
                "text" => $faker->text(400),
              ],
            ])
            ->publish();
        }

        return "Seed created, make sure to index it in the panel!";
      },
    ],
  ],
];
