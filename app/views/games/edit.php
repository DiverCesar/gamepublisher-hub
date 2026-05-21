<?php
declare(strict_types=1);

$formAction = url('/games/update');
$formMethod = 'POST';
$isEdit = true;
$title = 'Edit Game';

require BASE_PATH . '/app/views/games/partials/form.php';
