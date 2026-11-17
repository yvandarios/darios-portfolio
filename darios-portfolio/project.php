
<?php
require_once __DIR__ . '/config/database.php';

$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$project = null;
$projects = [];

if ($slug !== '') {
    $stmt = $pdo->prepare("
        SELECT id, title, slug, description, technologies, image,
               project_url, github_url, created_at
        FROM projects
        WHERE slug = :slug AND status = 'published'
        LIMIT 1
    ");
    $stmt->execute(['slug' => $slug]);
    $project = $stmt->fetch();

    if (!$project) {
        http_response_code(404);
    }

    $pageTitle = $project
        ? $project['title'] . ' | DARIOS Projects'
        : 'Project Not Found | DARIOS';

    $pageDescription = $project
        ? mb_strimwidth(strip_tags($project['description']), 0, 155, '...')
        : 'The requested project could not be found.';
} else {
    $pageTitle = 'My Projects | DARIOS';
    $pageDescription = 'Explore software development and web development projects by Yvan Darios.';

    $stmt = $pdo->query("
        SELECT id, title, slug, description, technologies, image,
               project_url, github_url, created_at
        FROM projects
        WHERE status = 'published'
        ORDER BY created_at DESC
    ");
    $projects = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
?>

<?php if ($slug !== '' && $project): ?>

    <section class="page-hero">
        <span class="section-tag">Project details</span>
        <h1><?= $escape($project['title']) ?></h1>
        <p>Explore the details, technologies and purpose of this project.</p>
    </section>

    <section class="section project-detail-section">
        <a href="project.php" class="back-link">
            <i class="fa-solid fa-arrow-left"></i> Back to all projects
        </a>

        <?php if (!empty($project['image'])): ?>
            <div class="project-detail-image">
                <img
                    src="assets/images/projects/<?= $escape(basename($project['image'])) ?>"
                    alt="<?= $escape($project['title']) ?>">
            </div>
        <?php endif; ?>

        <div class="project-detail-content">
            <div class="project-detail-main">
                <span class="section-tag">Overview</span>
                <h2>About this project</h2>

                <div class="project-description">
                    <?= nl2br($escape($project['description'])) ?>
                </div>
            </div>

            <aside class="project-detail-sidebar">
                <h3>Project Information</h3>

                <?php if (!empty($project['technologies'])): ?>
                    <div class="project-info-group">
                        <h4>Technologies Used</h4>
                        <div class="technology-list">
                            <?php foreach (explode(',', $project['technologies']) as $technology): ?>
                                <span><?= $escape(trim($technology)) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($project['project_url'])): ?>
                    <a href="<?= $escape($project['project_url']) ?>"
                       class="btn btn-primary"
                       target="_blank" rel="noopener noreferrer">
                        Live Preview <i class="fa-solid fa-arrow-up-right-from-square"></i>
                    </a>
                <?php endif; ?>

                <?php if (!empty($project['github_url'])): ?>
                    <a href="<?= $escape($project['github_url']) ?>"
                       class="btn btn-outline"
                       target="_blank" rel="noopener noreferrer">
                        View Source Code <i class="fa-brands fa-github"></i>
                    </a>
                <?php endif; ?>
            </aside>
        </div>
    </section>

<?php elseif ($slug !== ''): ?>

    <section class="section">
        <div class="empty-state">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <h2>Project Not Found</h2>
            <p>This project may have been removed or is not currently published.</p>
            <a href="project.php" class="btn btn-primary">Browse Projects</a>
        </div>
    </section>

<?php else: ?>

    <section class="page-hero">
        <span class="section-tag">My portfolio</span>
        <h1>My <span class="gradient-text">Projects</span></h1>
        <p>
            A collection of applications, experiments and solutions
            built throughout my development journey.
        </p>
    </section>

    <section class="section">
        <?php if (!empty($projects)): ?>
            <div class="projects-grid">
                <?php foreach ($projects as $item): ?>
                    <article class="project-card">
                        <a class="project-image"
                           href="project.php?slug=<?= urlencode($item['slug']) ?>">
                            <?php if (!empty($item['image'])): ?>
                                <img
                                    src="assets/images/projects/<?= $escape(basename($item['image'])) ?>"
                                    alt="<?= $escape($item['title']) ?>"
                                    loading="lazy">
                            <?php else: ?>
                                <div class="project-image-placeholder">
                                    <i class="fa-solid fa-code"></i>
                                </div>
                            <?php endif; ?>
                        </a>

                        <div class="project-card-content">
                            <h3><?= $escape($item['title']) ?></h3>

                            <p>
                                <?= $escape(mb_strimwidth(
                                    strip_tags($item['description']),
                                    0,
                                    160,
                                    '...'
                                )) ?>
                            </p>

                            <?php if (!empty($item['technologies'])): ?>
                                <div class="technology-list">
                                    <?php foreach (explode(',', $item['technologies']) as $technology): ?>
                                        <span><?= $escape(trim($technology)) ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <a href="project.php?slug=<?= urlencode($item['slug']) ?>"
                               class="text-link">
                                View Details <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-folder-open"></i>
                <h2>No Published Projects Yet</h2>
                <p>Published projects will appear here.</p>
            </div>
        <?php endif; ?>
    </section>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
