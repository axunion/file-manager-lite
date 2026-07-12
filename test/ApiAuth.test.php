<?php

declare(strict_types=1);

define('TESTING_MODE', true);

require_once __DIR__ . '/TestHelpers.php';
require_once __DIR__ . '/../src/bootstrap.php';

// ---------- isApiKeyValid Tests ----------

// 1. Matching key is accepted
assertEquals(
    true,
    isApiKeyValid('secret', 'secret'),
    'isApiKeyValid: matching key accepted'
);

// 2. Wrong key is rejected
assertEquals(
    false,
    isApiKeyValid('secret', 'wrong'),
    'isApiKeyValid: wrong key rejected'
);

// 3. Missing key is rejected
assertEquals(
    false,
    isApiKeyValid('secret', null),
    'isApiKeyValid: null key rejected'
);

// 4. Empty key is rejected
assertEquals(
    false,
    isApiKeyValid('secret', ''),
    'isApiKeyValid: empty key rejected'
);

echo "All ApiAuth tests passed.\n";
