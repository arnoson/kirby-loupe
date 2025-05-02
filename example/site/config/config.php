<?php

use arnoson\KirbyLoupe;
use Kirby\Toolkit\Str;
use Loupe\Loupe\Config\TypoTolerance;
use Loupe\Loupe\Configuration;

return [
  'debug' => true,

  'arnoson.kirby-loupe' => [
    'configuration' => fn() => Configuration::create()
      ->withTypoTolerance(TypoTolerance::create()->withFirstCharTypoCountsDouble(false))
      ->withFilterableAttributes(['interests']),
    'pages' => fn($page) => $page->intendedTemplate()->name() === 'item',
    'fields' => [
      'title',
      'text' => fn($page) => strip_tags($page->text()),
      'interests' => fn($page) => $page->interests()->split()
    ]
  ],

  'routes' => [
    [
      'pattern' => '/seed/(:num)',
      'action' => function($count) {
        set_time_limit(0);
        
        kirby()->impersonate('kirby');

        page('items')?->delete(true);
        $items = site()->createChild([
          'slug' => 'items',
          'template' => 'items'
        ])->publish();
                
        $faker = \Faker\Factory::create();
        for ($i = 0; $i < $count; $i++) {
          $name = $faker->name();
          $tagPool = ['technology', 'design', 'art', 'science', 'music', 'food', 'travel', 'sports', 'nature', 'business'];
          $numTags = rand(1, 3);
          $tags = array_rand(array_flip($tagPool), $numTags);
          $tags = is_array($tags) ? $tags : [$tags];
          $items->createChild([
            'slug' => Str::slug($name . '-' . rand(1000, 9999)),
            'template' => 'item',
            'content' => [
              'title' => $name,
              'interests' => implode(', ', $tags),
              'text' => $faker->text(400),
            ]
          ])->publish();
        }

        KirbyLoupe::reindex();

        return "Seed created and indexed!";
      }
    ]
  ]
];