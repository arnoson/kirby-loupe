<?php

use Kirby\Filesystem\Dir;
use Kirby\Toolkit\Str;

return [
  "description" => "Creates dummy items for the demo",
  "args" => [
    "count" => [
      "description" => "Number of items to create",
      "defaultValue" => 1000,
      "castTo" => "int",
    ],
  ],
  "command" => static function ($cli): void {
    $count = $cli->arg("count");
    $kirby = $cli->kirby();
    $kirby->impersonate("kirby");

    if ($existing = page("items")) {
      Dir::remove($existing->root());
      $existing->delete(true);
    }

    $items = site()
      ->createChild([
        "slug" => "items",
        "template" => "items",
      ])
      ->publish();

    $faker = \Faker\Factory::create();
    // prettier-ignore
    $allInterests = ["technology", "design", "art", "science", "music", "food", "travel", "sports", "nature", "business"];

    for ($i = 0; $i < $count; $i++) {
      $name = $faker->name();
      $interests = array_rand(array_flip($allInterests), rand(1, 3));
      $interests = is_array($interests) ? $interests : [$interests];
      $items
        ->createChild([
          "slug" => Str::slug($name . "-" . rand(1000, 9999)),
          "template" => "item",
          "content" => [
            "title" => $name,
            "interests" => implode(", ", $interests),
            "text" => $faker->realText(400),
          ],
        ])
        ->publish();
    }

    $cli->success("Seed created, make sure to index it in the panel!");
  },
];
