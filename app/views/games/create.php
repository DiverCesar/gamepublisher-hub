<?php
declare(strict_types=1);

$formAction = url('/games/store');
$formMethod = 'POST';
$isEdit = false;
$title = 'Create Game';

require BASE_PATH . '/app/views/games/partials/form.php';
