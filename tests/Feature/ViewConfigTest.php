<?php

test('compiled view path is configured', function () {
  $compiledPath = config('view.compiled');

  expect($compiledPath)
    ->not->toBeNull()
    ->and($compiledPath)->not->toBe('');
});
