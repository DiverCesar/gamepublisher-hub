<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Database;
use App\Core\UploadService;
use App\Core\Validator;
use PDO;
use Throwable;

final class GameController extends Controller
{
    public function index(): void
    {
        $pdo = Database::connection();
        $page = $this->currentPage();
        $perPage = 10;
        $offset = ($page - 1) * $perPage;
        $search = trim((string) ($_GET['search'] ?? ''));

        [$games, $total] = $this->loadGames($pdo, $search, $perPage, $offset);
        $totalPages = max(1, (int) ceil($total / $perPage));

        $this->render('games/index', [
            'title' => 'Games',
            'games' => $games,
            'search' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'totalPages' => $totalPages,
        ]);
    }

    public function create(): void
    {
        $pdo = Database::connection();
        $catalogs = $this->loadCatalogs($pdo);

        $this->render('games/create', [
            'title' => 'Create Game',
            'catalogs' => $catalogs,
            'selectedGenres' => [],
            'selectedPlatforms' => [],
            'selectedTags' => [],
        ]);
    }

    private function loadGameSelections(PDO $pdo, string $gameId): array
    {
        $genresStmt = $pdo->prepare('select genre_id from public.game_genres where game_id = :game_id');
        $genresStmt->execute([':game_id' => $gameId]);

        $platformsStmt = $pdo->prepare('select platform_id from public.game_platforms where game_id = :game_id');
        $platformsStmt->execute([':game_id' => $gameId]);

        $tagsStmt = $pdo->prepare('select tag_id from public.game_tags where game_id = :game_id');
        $tagsStmt->execute([':game_id' => $gameId]);

        return [
            'genres' => array_map(
                static fn (array $row) => (string) $row['genre_id'],
                                  $genresStmt->fetchAll() ?: []
            ),
            'platforms' => array_map(
                static fn (array $row) => (string) $row['platform_id'],
                                     $platformsStmt->fetchAll() ?: []
            ),
            'tags' => array_map(
                static fn (array $row) => (string) $row['tag_id'],
                                $tagsStmt->fetchAll() ?: []
            ),
        ];
    }

    public function store(): never
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/games/create');
        }

        $pdo = Database::connection();
        $config = require BASE_PATH . '/config/config.php';
        $catalogs = $this->loadCatalogs($pdo);

        $data = $this->normalizeInput($_POST);
        $files = $_FILES ?? [];

        $validator = new Validator($data);
        $rules = [
            'title' => ['required', 'min:3', 'max:120'],
            'studio' => ['required', 'min:2', 'max:120'],
            'short_description' => ['required', 'min:20', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'publication_fee' => ['required', 'numeric', 'min:0'],
            'release_date' => ['nullable', 'date'],
            'age_rating' => ['required', 'in:E,E10+,T,M,AO,RP'],
            'accent_color' => ['required', 'regex:^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$'],
            'genres' => ['required', 'array', 'min:1'],
            'platforms' => ['required', 'array', 'min:1'],
            'tags' => ['nullable', 'array'],
            'simulate_payment' => ['nullable', 'in:0,1'],
        ];

        $isValid = $validator->validate($rules);

        $selectedGenres = $data['genres'] ?? [];
        $selectedPlatforms = $data['platforms'] ?? [];
        $selectedTags = $data['tags'] ?? [];

        if (!$this->validateSelectedIds($selectedGenres, $catalogs['genres'])) {
            $this->pushCustomError($validator, 'genres', 'One or more selected genres are invalid.');
            $isValid = false;
        }

        if (!$this->validateSelectedIds($selectedPlatforms, $catalogs['platforms'])) {
            $this->pushCustomError($validator, 'platforms', 'One or more selected platforms are invalid.');
            $isValid = false;
        }

        if (!empty($selectedTags) && !$this->validateSelectedIds($selectedTags, $catalogs['tags'])) {
            $this->pushCustomError($validator, 'tags', 'One or more selected tags are invalid.');
            $isValid = false;
        }

        if (!$isValid) {
            $this->withInput($data);
            $this->withErrors($validator->errors());
            $this->flash('error', 'Please review the form fields.');
            $this->redirect('/games/create');
        }

        try {
            $pdo->beginTransaction();

            $slug = $this->generateUniqueSlug($pdo, (string) $data['title']);

            $bannerPath = null;
            $coverPath = null;

            $uploader = new UploadService(
                BASE_PATH . '/public/uploads',
                $config['uploads']['allowed_image_mimes'],
                (int) $config['uploads']['max_size']
            );

            if (!empty($files['banner']['name'])) {
                $bannerPath = $uploader->store($files['banner'], 'banner');
            }

            if (!empty($files['cover']['name'])) {
                $coverPath = $uploader->store($files['cover'], 'cover');
            }

            $simulatePayment = (int) ($data['simulate_payment'] ?? 0) === 1;
            $status = $simulatePayment ? 'pending_review' : 'draft';
            $paymentStatus = $simulatePayment ? 'paid' : 'unpaid';

            $stmt = $pdo->prepare(<<<SQL
            insert into public.games (
                slug,
                title,
                studio,
                short_description,
                price,
                publication_fee,
                age_rating,
                status,
                payment_status,
                release_date,
                banner_path,
                cover_path,
                accent_color,
                published_at
            ) values (
                :slug,
                :title,
                :studio,
                :short_description,
                :price,
                :publication_fee,
                :age_rating,
                :status,
                :payment_status,
                :release_date,
                :banner_path,
                :cover_path,
                :accent_color,
                :published_at
            )
            returning id
            SQL);

            $stmt->execute([
            ':slug' => $slug,
            ':title' => $data['title'],
            ':studio' => $data['studio'],
            ':short_description' => $data['short_description'],
            ':price' => $data['price'],
            ':publication_fee' => $data['publication_fee'],
            ':age_rating' => $data['age_rating'],
            ':status' => $status,
            ':payment_status' => $paymentStatus,
            ':release_date' => $data['release_date'] ?: null,
            ':banner_path' => $bannerPath,
            ':cover_path' => $coverPath,
            ':accent_color' => $data['accent_color'],
            ':published_at' => $simulatePayment ? date('c') : null,
            ]);

            $gameId = (string) $stmt->fetchColumn();

            $this->syncManyToMany($pdo, 'public.game_genres', 'game_id', 'genre_id', $gameId, $selectedGenres);
            $this->syncManyToMany($pdo, 'public.game_platforms', 'game_id', 'platform_id', $gameId, $selectedPlatforms);

            if (!empty($selectedTags)) {
                $this->syncManyToMany($pdo, 'public.game_tags', 'game_id', 'tag_id', $gameId, $selectedTags);
        }

        if ($simulatePayment) {
            $paymentStmt = $pdo->prepare(<<<SQL
            insert into public.payments (
                game_id,
                amount,
                method,
                status,
                transaction_ref,
                paid_at
            ) values (
                :game_id,
                :amount,
                :method,
                :status,
                :transaction_ref,
                :paid_at
            )
            SQL);

            $paymentStmt->execute([
            ':game_id' => $gameId,
            ':amount' => $data['publication_fee'],
            ':method' => 'simulated_card',
            ':status' => 'paid',
            ':transaction_ref' => 'SIM-' . strtoupper(bin2hex(random_bytes(5))),
                                  ':paid_at' => date('c'),
            ]);
        }

        $pdo->commit();

            $this->flash('success', 'Game saved successfully.');
            $this->redirect('/games');
        } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $this->withInput($data);
            $this->withErrors(['general' => [$e->getMessage()]]);
            $this->flash('error', 'Could not save the game.');
            $this->redirect('/games/create');
        }
        }

        public function show(): void
        {
        $id = (string) ($_GET['id'] ?? '');
            if ($id === '') {
                $this->redirect('/games');
        }

        $pdo = Database::connection();
            $game = $this->findGameDetail($pdo, $id);

            if (!$game) {
                http_response_code(404);
            echo 'Game not found.';
            return;
        }

        $this->render('games/show', [
        'title' => $game['title'],
        'game' => $game,
        ]);
        }

        public function edit(): void
        {
        $id = (string) ($_GET['id'] ?? '');
            if ($id === '') {
                $this->redirect('/games');
        }

        $pdo = Database::connection();
            $game = $this->findGameDetail($pdo, $id);

            if (!$game) {
                http_response_code(404);
            echo 'Game not found.';
            return;
        }

        $catalogs = $this->loadCatalogs($pdo);
            $selections = $this->loadGameSelections($pdo, $id);

            $this->render('games/edit', [
            'title' => 'Edit Game',
            'game' => $game,
            'catalogs' => $catalogs,
            'selectedGenres' => $selections['genres'],
            'selectedPlatforms' => $selections['platforms'],
            'selectedTags' => $selections['tags'],
            ]);
        }

        $pdo = Database::connection();
            $game = $this->findGameDetail($pdo, $id);

            if (!$game) {
                http_response_code(404);
            echo 'Game not found.';
            return;
        }

        $catalogs = $this->loadCatalogs($pdo);

            $this->render('games/edit', [
            'title' => 'Edit Game',
            'game' => $game,
            'catalogs' => $catalogs,
        ]);
        }

        public function update(): never
        {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/games');
        }

        $id = (string) ($_POST['id'] ?? '');
            if ($id === '') {
                $this->redirect('/games');
        }

        $pdo = Database::connection();
            $config = require BASE_PATH . '/config/config.php';
            $catalogs = $this->loadCatalogs($pdo);
            $existing = $this->findGameDetail($pdo, $id);

            if (!$existing) {
                $this->flash('error', 'Game not found.');
            $this->redirect('/games');
        }

        $data = $this->normalizeInput($_POST);
            $files = $_FILES ?? [];

            $validator = new Validator($data);
            $rules = [
            'title' => ['required', 'min:3', 'max:120'],
            'studio' => ['required', 'min:2', 'max:120'],
            'short_description' => ['required', 'min:20', 'max:500'],
            'price' => ['required', 'numeric', 'min:0'],
            'publication_fee' => ['required', 'numeric', 'min:0'],
            'release_date' => ['nullable', 'date'],
            'age_rating' => ['required', 'in:E,E10+,T,M,AO,RP'],
            'accent_color' => ['required', 'regex:^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$'],
                                  'genres' => ['required', 'array', 'min:1'],
                                  'platforms' => ['required', 'array', 'min:1'],
                                  'tags' => ['nullable', 'array'],
        ];

        $isValid = $validator->validate($rules);

            if (!$this->validateSelectedIds($data['genres'] ?? [], $catalogs['genres'])) {
                $this->pushCustomError($validator, 'genres', 'One or more selected genres are invalid.');
            $isValid = false;
        }

        if (!$this->validateSelectedIds($data['platforms'] ?? [], $catalogs['platforms'])) {
            $this->pushCustomError($validator, 'platforms', 'One or more selected platforms are invalid.');
            $isValid = false;
        }

        if (!empty($data['tags']) && !$this->validateSelectedIds($data['tags'], $catalogs['tags'])) {
            $this->pushCustomError($validator, 'tags', 'One or more selected tags are invalid.');
            $isValid = false;
        }

        if (!$isValid) {
            $this->withInput($data);
            $this->withErrors($validator->errors());
            $this->flash('error', 'Please review the form fields.');
            $this->redirect('/games/edit?id=' . urlencode($id));
        }

        try {
        $pdo->beginTransaction();

            $bannerPath = $existing['banner_path'] ?? null;
            $coverPath = $existing['cover_path'] ?? null;

            $uploader = new UploadService(
                BASE_PATH . '/public/uploads',
                $config['uploads']['allowed_image_mimes'],
                (int) $config['uploads']['max_size']
            );

            if (!empty($files['banner']['name'])) {
                $bannerPath = $uploader->store($files['banner'], 'banner');
        }

        if (!empty($files['cover']['name'])) {
            $coverPath = $uploader->store($files['cover'], 'cover');
        }

        $simulatePayment = (int) ($_POST['simulate_payment'] ?? 0) === 1;
        $status = $simulatePayment ? 'pending_review' : ($existing['status'] ?? 'draft');
            $paymentStatus = $simulatePayment ? 'paid' : ($existing['payment_status'] ?? 'unpaid');

            $stmt = $pdo->prepare(<<<SQL
            update public.games
            set
            title = :title,
            studio = :studio,
            short_description = :short_description,
            price = :price,
            publication_fee = :publication_fee,
                age_rating = :age_rating,
                status = :status,
                payment_status = :payment_status,
                release_date = :release_date,
                banner_path = :banner_path,
                cover_path = :cover_path,
                accent_color = :accent_color,
                published_at = coalesce(published_at, :published_at)
            where id = :id
            SQL);

            $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'],
            ':studio' => $data['studio'],
            ':short_description' => $data['short_description'],
            ':price' => $data['price'],
            ':publication_fee' => $data['publication_fee'],
            ':age_rating' => $data['age_rating'],
            ':status' => $status,
            ':payment_status' => $paymentStatus,
            ':release_date' => $data['release_date'] ?: null,
            ':banner_path' => $bannerPath,
            ':cover_path' => $coverPath,
            ':accent_color' => $data['accent_color'],
            ':published_at' => $simulatePayment ? date('c') : null,
            ]);

            $this->syncManyToMany($pdo, 'public.game_genres', 'game_id', 'genre_id', $id, $data['genres'] ?? []);
            $this->syncManyToMany($pdo, 'public.game_platforms', 'game_id', 'platform_id', $id, $data['platforms'] ?? []);
            $this->syncManyToMany($pdo, 'public.game_tags', 'game_id', 'tag_id', $id, $data['tags'] ?? []);

            if ($simulatePayment) {
                $paymentStmt = $pdo->prepare(<<<SQL
                insert into public.payments (
                    game_id,
                    amount,
                    method,
                    status,
                    transaction_ref,
                    paid_at
            ) values (
                :game_id,
                :amount,
                :method,
                :status,
                :transaction_ref,
                :paid_at
            )
            SQL);

            $paymentStmt->execute([
            ':game_id' => $id,
            ':amount' => $data['publication_fee'],
            ':method' => 'simulated_card',
            ':status' => 'paid',
            ':transaction_ref' => 'SIM-' . strtoupper(bin2hex(random_bytes(5))),
                                  ':paid_at' => date('c'),
            ]);
        }

        $pdo->commit();

            $this->flash('success', 'Game updated successfully.');
            $this->redirect('/games/show?id=' . urlencode($id));
        } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $this->withInput($data);
            $this->withErrors(['general' => [$e->getMessage()]]);
            $this->flash('error', 'Could not update the game.');
            $this->redirect('/games/edit?id=' . urlencode($id));
        }
        }

        public function destroy(): never
        {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            $this->redirect('/games');
        }

        $id = (string) ($_POST['id'] ?? '');
            if ($id === '') {
                $this->redirect('/games');
        }

        $pdo = Database::connection();

            $stmt = $pdo->prepare('delete from public.games where id = :id');
            $stmt->execute([':id' => $id]);

            $this->flash('success', 'Game deleted successfully.');
            $this->redirect('/games');
        }

        private function loadCatalogs(PDO $pdo): array
        {
        $genres = $pdo->query('select id, name from public.genres order by display_order asc, name asc')->fetchAll() ?: [];
        $platforms = $pdo->query('select id, name from public.platforms order by display_order asc, name asc')->fetchAll() ?: [];
        $tags = $pdo->query('select id, name from public.tags order by name asc')->fetchAll() ?: [];

        return [
        'genres' => $genres,
        'platforms' => $platforms,
        'tags' => $tags,
        ];
        }

        private function loadGames(PDO $pdo, string $search, int $perPage, int $offset): array
        {
        $where = '';
        if ($search !== '') {
            $where = "where g.title ilike :search or g.studio ilike :search";
        }

        $countSql = "select count(*) from public.games g {$where}";
        $countStmt = $pdo->prepare($countSql);

            if ($search !== '') {
                $countStmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
        }

        $countStmt->execute();
            $total = (int) $countStmt->fetchColumn();

            $sql = <<<SQL
            select
            g.id,
            g.slug,
            g.title,
            g.studio,
            g.price,
            g.status,
            g.payment_status,
            g.accent_color,
            g.banner_path,
            g.cover_path,
            g.created_at,
            coalesce(string_agg(distinct ge.name, ', '), '') as genres,
                                  coalesce(string_agg(distinct pl.name, ', '), '') as platforms,
                                  coalesce(string_agg(distinct tg.name, ', '), '') as tags
                                  from public.games g
                                  left join public.game_genres gg on gg.game_id = g.id
                                  left join public.genres ge on ge.id = gg.genre_id
                                  left join public.game_platforms gp on gp.game_id = g.id
                                  left join public.platforms pl on pl.id = gp.platform_id
                                  left join public.game_tags gt on gt.game_id = g.id
                                  left join public.tags tg on tg.id = gt.tag_id
                                  {$where}
                                  group by g.id
                                  order by g.created_at desc
                                  limit :limit offset :offset
                                  SQL;

                                  $stmt = $pdo->prepare($sql);

                                  if ($search !== '') {
                                      $stmt->bindValue(':search', '%' . $search . '%', PDO::PARAM_STR);
                                  }

                                  $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
                                  $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                                  $stmt->execute();

                                  return [$stmt->fetchAll() ?: [], $total];
        }

        private function findGameDetail(PDO $pdo, string $id): array|false
        {
            $stmt = $pdo->prepare(<<<SQL
            select
            g.*,
            coalesce(string_agg(distinct ge.name, ', '), '') as genres,
                                  coalesce(string_agg(distinct pl.name, ', '), '') as platforms,
                                  coalesce(string_agg(distinct tg.name, ', '), '') as tags
                                  from public.games g
                                  left join public.game_genres gg on gg.game_id = g.id
                                  left join public.genres ge on ge.id = gg.genre_id
                                  left join public.game_platforms gp on gp.game_id = g.id
                                  left join public.platforms pl on pl.id = gp.platform_id
                                  left join public.game_tags gt on gt.game_id = g.id
                                  left join public.tags tg on tg.id = gt.tag_id
                                  where g.id = :id
                                  group by g.id
                                  limit 1
                                  SQL);

            $stmt->execute([':id' => $id]);
            return $stmt->fetch() ?: false;
        }

        private function normalizeInput(array $input): array
        {
        return [
        'title' => trim((string) ($input['title'] ?? '')),
                                  'studio' => trim((string) ($input['studio'] ?? '')),
                                  'short_description' => trim((string) ($input['short_description'] ?? '')),
                                  'price' => (string) ($input['price'] ?? ''),
                                  'publication_fee' => (string) ($input['publication_fee'] ?? '100'),
                                  'release_date' => trim((string) ($input['release_date'] ?? '')),
                                  'age_rating' => trim((string) ($input['age_rating'] ?? 'T')),
                                  'accent_color' => trim((string) ($input['accent_color'] ?? '#1b2838')),
                                  'genres' => array_values(array_filter((array) ($input['genres'] ?? []), static fn ($v) => $v !== '')),
                                  'platforms' => array_values(array_filter((array) ($input['platforms'] ?? []), static fn ($v) => $v !== '')),
                                  'tags' => array_values(array_filter((array) ($input['tags'] ?? []), static fn ($v) => $v !== '')),
                                  'simulate_payment' => (string) ($input['simulate_payment'] ?? '0'),
                                  ];
        }

        private function validateSelectedIds(array $selectedIds, array $catalog): bool
        {
        if (empty($selectedIds)) {
            return false;
        }

        $allowed = array_column($catalog, 'id');
            foreach ($selectedIds as $id) {
                if (!in_array($id, $allowed, true)) {
                    return false;
        }
        }

        return true;
        }

        private function syncManyToMany(
            PDO $pdo,
            string $table,
            string $gameColumn,
            string $foreignColumn,
            string $gameId,
            array $selectedIds
            ): void {
            $delete = $pdo->prepare("delete from {$table} where {$gameColumn} = :game_id");
            $delete->execute([':game_id' => $gameId]);

            if (empty($selectedIds)) {
                return;
        }

        $insert = $pdo->prepare("insert into {$table} ({$gameColumn}, {$foreignColumn}) values (:game_id, :foreign_id)");

            foreach ($selectedIds as $foreignId) {
                $insert->execute([
                ':game_id' => $gameId,
                ':foreign_id' => $foreignId,
                                  ]);
        }
        }

        private function generateUniqueSlug(PDO $pdo, string $title): string
        {
        $base = strtolower(trim($title));
            $base = preg_replace('/[^a-z0-9]+/i', '-', $base) ?: 'game';
            $base = trim($base, '-');

            $slug = $base;
            $counter = 1;

            while (true) {
                $stmt = $pdo->prepare('select count(*) from public.games where slug = :slug');
            $stmt->execute([':slug' => $slug]);

            if ((int) $stmt->fetchColumn() === 0) {
                return $slug;
        }

        $slug = $base . '-' . $counter++;
        }
        }

        private function pushCustomError(Validator $validator, string $field, string $message): void
        {
        $errors = $validator->errors();
            $errors[$field][] = $message;

            $ref = new \ReflectionClass($validator);
            $prop = $ref->getProperty('errors');
            $prop->setAccessible(true);
            $prop->setValue($validator, $errors);
        }
        }
