<?php

use arnoson\KirbyLoupe;
use Kirby\Toolkit\Str;

$query = param("query", "");
$interest = param("interest", "");

$startTime = microtime(true);
$filter = $interest
  ? "interests = " . KirbyLoupe::escapeFilterValue($interest)
  : null;
$results = KirbyLoupe::search($query, filter: $filter, paginate: 5);
$searchTime = round((microtime(true) - $startTime) * 1000);
?>

<?php snippet("head"); ?>
<body>
  <main>
    <h1><?= $site->title() ?></h1>

    <?php if (!page("items")): ?>
    <p>
      To use this demo first <a href="/seed/1000">create some dummy data</a> (This might take while)
    </p>
    <?php return; ?>
    <?php endif; ?>


    <form id="form">
      <div>
        <label for="field-search">Search</label>
        <input id="field-search" type="text" name="query" value="<?= $query ?>">
      </div>
      <div>
        <label for="field-interest">Interest</label>
        <select id="field-interest" name="interest">
          <option value="">All</option>
          <?php
          // prettier-ignore
          $interests = [ "technology", "design", "art", "science", "music", "food", "travel", "sports", "nature", "business",
          ];
          foreach ($interests as $value): ?>
            <option
              value="<?= $value ?>"
              <?php e($interest === $value, "selected"); ?>
            ><?= Str::ucfirst($value) ?></option>
          <?php endforeach;
          ?>
        </select>
      </div>
    </form>

    <div id="search-results">
      <p><i>Search took <?= $searchTime ?>ms</i></p>

      <!-- Results -->
      <?php foreach ($results as $result):
        $page = page($result["uuid"]); ?>
      <article>
        <h2><?= $page->title() ?></h2>
        <p><?= $page->text() ?></p>
        <p class="interests">Interests: <?= $page->interests() ?></p>
      </article>  
      <?php
      endforeach; ?>

      <!-- Pagination -->
      <?php $pagination = $results->pagination(); ?>
      <nav class="pagination">
        <ul>
          <!-- Prev -->
          <?php if ($pagination->hasPrevPage()): ?>
          <li><a href="<?= $pagination->prevPageURL() ?>">‹</a></li>
          <?php else: ?>
          <li>‹</li>
          <?php endif; ?>
          <!-- Range -->
          <?php foreach ($pagination->range(10) as $r): ?>
          <li>
            <a
              <?= e($pagination->page() === $r, 'aria-current="page"') ?>
              href="<?= $pagination->pageURL($r) ?>"
            ><?= $r ?>
            </a>
          </li>
          <?php endforeach; ?>
          <!-- Next -->
          <?php if ($pagination->hasNextPage()): ?>
          <li><a href="<?= $pagination->nextPageURL() ?>">›</a></li>
          <?php else: ?>
          <li>›</li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>
  </main>
</body>
</html>