<?php
declare(strict_types=1);

$old = $old ?? [];
$errors = $errors ?? [];
$catalogs = $catalogs ?? ['genres' => [], 'platforms' => [], 'tags' => []];
$game = $game ?? null;
$isEdit = (bool) ($isEdit ?? false);

$selectedGenres = array_map('strval', $selectedGenres ?? []);
$selectedPlatforms = array_map('strval', $selectedPlatforms ?? []);
$selectedTags = array_map('strval', $selectedTags ?? []);

$value = static function (string $key, mixed $default = '') use ($old, $game): string {
    if (array_key_exists($key, $old)) {
        $val = $old[$key];
        return is_array($val) ? '' : (string) $val;
    }

    if (is_array($game) && array_key_exists($key, $game)) {
        return (string) $game[$key];
    }

    return (string) $default;
};

$arrayFromOld = static function (string $key) use ($old): array {
    if (array_key_exists($key, $old) && is_array($old[$key])) {
        return array_values(array_map('strval', $old[$key]));
    }

    return [];
};

$selectedFromSource = static function (string $key, array $fallback = []) use ($arrayFromOld): array {
    $fromOld = $arrayFromOld($key);
    if (!empty($fromOld)) {
        return $fromOld;
    }

    return array_values(array_map('strval', $fallback));
};

$selectedGenres = $selectedFromSource('genres', $selectedGenres);
$selectedPlatforms = $selectedFromSource('platforms', $selectedPlatforms);
$selectedTags = $selectedFromSource('tags', $selectedTags);

$errorFor = static function (string $key) use ($errors): string {
    if (!isset($errors[$key]) || !is_array($errors[$key])) {
        return '';
    }

    return implode(' ', array_map('strval', $errors[$key]));
};

$isChecked = static function (string $key, string $expected = '1') use ($old, $game): bool {
    $source = $old[$key] ?? ($game[$key] ?? null);

    if (is_bool($source)) {
        return $source;
    }

    return (string) $source === $expected;
};

$publicationFee = $value('publication_fee', '100.00');
$accentColor = $value('accent_color', '#1b2838');
$ratingValue = $value('age_rating', 'T');

$hasErrorClass = static function (string $key) use ($errorFor): string {
    return $errorFor($key) !== '' ? 'input--error' : '';
};

$displayName = static function (string $text): string {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
};
?>

<div class="page-grid page-grid--form">
<section class="panel panel--wide">
<div class="panel__header">
<div>
<p class="eyebrow">Publishing workflow</p>
<h2 class="panel__title"><?= $displayName($isEdit ? 'Edit Game' : 'Publish New Game') ?></h2>
<p class="panel__subtitle">Structured for real-world publishing rules, validation, and dashboard reporting.</p>
</div>

<div class="panel__badge">
Publication fee
<strong>$<?= $displayName(number_format((float) $publicationFee, 2)) ?></strong>
</div>
</div>

<form action="<?= $displayName($formAction) ?>" method="<?= $displayName($formMethod) ?>" enctype="multipart/form-data" class="form-shell" novalidate>
<?php if ($isEdit && !empty($game['id'])): ?>
<input type="hidden" name="id" value="<?= $displayName((string) $game['id']) ?>">
<?php endif; ?>

<div class="form-section">
<h3 class="form-section__title">Core details</h3>

<div class="form-grid">
<div class="field">
<label for="title">Game title</label>
<input
type="text"
id="title"
name="title"
value="<?= $displayName($value('title')) ?>"
placeholder="e.g. Neon Rift"
minlength="3"
maxlength="120"
required
class="input <?= $hasErrorClass('title') ?>"
>
<small class="field__hint">Title must be unique and descriptive.</small>
<?php if ($errorFor('title')): ?><small class="field__error"><?= $displayName($errorFor('title')) ?></small><?php endif; ?>
</div>

<div class="field">
<label for="studio">Studio / Developer</label>
<input
type="text"
id="studio"
name="studio"
value="<?= $displayName($value('studio')) ?>"
placeholder="e.g. Black Circuit Labs"
minlength="2"
maxlength="120"
required
class="input <?= $hasErrorClass('studio') ?>"
>
<?php if ($errorFor('studio')): ?><small class="field__error"><?= $displayName($errorFor('studio')) ?></small><?php endif; ?>
</div>

<div class="field field--full">
<label for="short_description">Short description</label>
<textarea
id="short_description"
name="short_description"
rows="5"
minlength="20"
maxlength="500"
required
class="textarea <?= $hasErrorClass('short_description') ?>"
placeholder="Write a concise description of the game..."
><?= $displayName($value('short_description')) ?></textarea>
<small class="field__hint">This feeds the dashboard and the game card preview.</small>
<?php if ($errorFor('short_description')): ?><small class="field__error"><?= $displayName($errorFor('short_description')) ?></small><?php endif; ?>
</div>
</div>
</div>

<div class="form-section">
<h3 class="form-section__title">Commercial data</h3>

<div class="form-grid">
<div class="field">
<label for="price">Base price</label>
<div class="input-group">
<span class="input-group__prefix">$</span>
<input
type="number"
id="price"
name="price"
value="<?= $displayName($value('price', '0.00')) ?>"
min="0"
step="0.01"
required
class="input input--group <?= $hasErrorClass('price') ?>"
>
</div>
<?php if ($errorFor('price')): ?><small class="field__error"><?= $displayName($errorFor('price')) ?></small><?php endif; ?>
</div>

<div class="field">
<label for="publication_fee">Publication fee</label>
<div class="input-group">
<span class="input-group__prefix">$</span>
<input
type="number"
id="publication_fee"
name="publication_fee"
value="<?= $displayName($publicationFee) ?>"
min="0"
step="0.01"
required
class="input input--group <?= $hasErrorClass('publication_fee') ?>"
>
</div>
<small class="field__hint">Use the simulated fee like Steam’s onboarding flow.</small>
<?php if ($errorFor('publication_fee')): ?><small class="field__error"><?= $displayName($errorFor('publication_fee')) ?></small><?php endif; ?>
</div>

<div class="field">
<label for="release_date">Release date</label>
<input
type="date"
id="release_date"
name="release_date"
value="<?= $displayName($value('release_date')) ?>"
class="input <?= $hasErrorClass('release_date') ?>"
>
<?php if ($errorFor('release_date')): ?><small class="field__error"><?= $displayName($errorFor('release_date')) ?></small><?php endif; ?>
</div>

<div class="field">
<label for="age_rating">Age rating</label>
<select id="age_rating" name="age_rating" required class="select <?= $hasErrorClass('age_rating') ?>">
<?php foreach (['E', 'E10+', 'T', 'M', 'AO', 'RP'] as $rating): ?>
<option value="<?= $displayName($rating) ?>" <?= $ratingValue === $rating ? 'selected' : '' ?>>
<?= $displayName($rating) ?>
</option>
<?php endforeach; ?>
</select>
<?php if ($errorFor('age_rating')): ?><small class="field__error"><?= $displayName($errorFor('age_rating')) ?></small><?php endif; ?>
</div>

<div class="field">
<label for="accent_color">Accent color</label>
<input
type="color"
id="accent_color"
name="accent_color"
value="<?= $displayName($accentColor) ?>"
class="color-input"
>
<small class="field__hint">Used to tint the card and banner preview.</small>
<?php if ($errorFor('accent_color')): ?><small class="field__error"><?= $displayName($errorFor('accent_color')) ?></small><?php endif; ?>
</div>
</div>
</div>

<div class="form-section">
<h3 class="form-section__title">Catalog selections</h3>
<p class="field__hint">Multiple selection is allowed for genres, platforms, and tags.</p>

<div class="catalog-grid">
<fieldset class="catalog-card">
<legend class="catalog-card__title">Genres</legend>
<div class="chip-grid">
<?php foreach ($catalogs['genres'] as $genre): ?>
<?php $gid = (string) $genre['id']; ?>
<label class="chip <?= in_array($gid, $selectedGenres, true) ? 'chip--active' : '' ?>">
<input
type="checkbox"
name="genres[]"
value="<?= $displayName($gid) ?>"
<?= in_array($gid, $selectedGenres, true) ? 'checked' : '' ?>
>
<span><?= $displayName((string) $genre['name']) ?></span>
</label>
<?php endforeach; ?>
</div>
<?php if ($errorFor('genres')): ?><small class="field__error"><?= $displayName($errorFor('genres')) ?></small><?php endif; ?>
</fieldset>

<fieldset class="catalog-card">
<legend class="catalog-card__title">Platforms</legend>
<div class="chip-grid">
<?php foreach ($catalogs['platforms'] as $platform): ?>
<?php $pid = (string) $platform['id']; ?>
<label class="chip <?= in_array($pid, $selectedPlatforms, true) ? 'chip--active' : '' ?>">
<input
type="checkbox"
name="platforms[]"
value="<?= $displayName($pid) ?>"
<?= in_array($pid, $selectedPlatforms, true) ? 'checked' : '' ?>
>
<span><?= $displayName((string) $platform['name']) ?></span>
</label>
<?php endforeach; ?>
</div>
<?php if ($errorFor('platforms')): ?><small class="field__error"><?= $displayName($errorFor('platforms')) ?></small><?php endif; ?>
</fieldset>

<fieldset class="catalog-card catalog-card--full">
<legend class="catalog-card__title">Tags</legend>
<div class="chip-grid">
<?php foreach ($catalogs['tags'] as $tag): ?>
<?php $tid = (string) $tag['id']; ?>
<label class="chip <?= in_array($tid, $selectedTags, true) ? 'chip--active' : '' ?>">
<input
type="checkbox"
name="tags[]"
value="<?= $displayName($tid) ?>"
<?= in_array($tid, $selectedTags, true) ? 'checked' : '' ?>
>
<span><?= $displayName((string) $tag['name']) ?></span>
</label>
<?php endforeach; ?>
</div>
<?php if ($errorFor('tags')): ?><small class="field__error"><?= $displayName($errorFor('tags')) ?></small><?php endif; ?>
</fieldset>
</div>
</div>

<div class="form-section">
<h3 class="form-section__title">Media</h3>

<div class="form-grid">
<div class="field">
<label for="banner">Banner image</label>
<input
type="file"
id="banner"
name="banner"
accept="image/png,image/jpeg,image/webp"
class="file-input"
>
<small class="field__hint">Recommended ratio: wide banner, PNG/JPG/WebP.</small>
</div>

<div class="field">
<label for="cover">Cover image</label>
<input
type="file"
id="cover"
name="cover"
accept="image/png,image/jpeg,image/webp"
class="file-input"
>
<small class="field__hint">Used as the compact thumbnail in listings.</small>
</div>
</div>
</div>

<div class="form-section form-section--highlight">
<div class="form-row">
<div>
<h3 class="form-section__title">Payment simulation</h3>
<p class="field__hint">This mimics a Steam-like onboarding step for the academic presentation.</p>
</div>

<label class="switch">
<input type="checkbox" name="simulate_payment" value="1" <?= $isChecked('simulate_payment', '1') ? 'checked' : '' ?>>
<span class="switch__track"></span>
<span class="switch__label">Mark as paid</span>
</label>
</div>
</div>

<?php if ($errorFor('general')): ?>
<div class="alert alert--error">
<?= $displayName($errorFor('general')) ?>
</div>
<?php endif; ?>

<div class="form-actions">
<a href="<?= $displayName(url('/games')) ?>" class="button button--ghost">Cancel</a>
<button type="submit" class="button button--primary">
<?= $displayName($isEdit ? 'Update Game' : 'Publish Game') ?>
</button>
</div>
</form>
</section>

<aside class="panel panel--side">
<div class="preview-card" style="--accent: <?= $displayName($accentColor) ?>;">
<div class="preview-card__banner"></div>
<div class="preview-card__body">
<div class="preview-card__avatar">G</div>
<div>
<p class="preview-card__label">Preview</p>
<h3 class="preview-card__title"><?= $displayName($value('title', 'Game title preview')) ?></h3>
<p class="preview-card__meta"><?= $displayName($value('studio', 'Studio name')) ?></p>
</div>
</div>

<p class="preview-card__text">
<?= $displayName($value('short_description', 'The short description will appear here as a storefront summary.')) ?>
</p>

<div class="preview-card__tags">
<?php if (!empty($selectedGenres)): ?>
<span class="mini-badge"><?= count($selectedGenres) ?> genre(s)</span>
<?php endif; ?>
<?php if (!empty($selectedPlatforms)): ?>
<span class="mini-badge"><?= count($selectedPlatforms) ?> platform(s)</span>
<?php endif; ?>
<?php if (!empty($selectedTags)): ?>
<span class="mini-badge"><?= count($selectedTags) ?> tag(s)</span>
<?php endif; ?>
</div>
</div>

<div class="note-card">
<h4>Validation approach</h4>
<p>
This form is intentionally strict in the backend. That way, date, money, and catalog selections stay consistent
and the dashboard can produce clean metrics.
</p>
</div>
</aside>
</div>
