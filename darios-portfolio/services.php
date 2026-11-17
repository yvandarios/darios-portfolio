
<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'Services | DARIOS';
$pageDescription = 'Explore web development and digital services offered by Yvan Darios.';

$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$services = [];

try {
    $stmt = $pdo->query("
        SELECT id, title, description, icon
        FROM services
        WHERE status = 'active'
        ORDER BY created_at DESC
    ");

    $services = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('Services page error: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <span class="section-tag">What I can do</span>
    <h1>My <span class="gradient-text">Services</span></h1>
    <p>
        Digital solutions built with creativity, technical knowledge
        and a focus on solving real problems.
    </p>
</section>

<section class="section">
    <?php if (!empty($services)): ?>

        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <article class="service-card">
                    <div class="service-icon">
                        <i class="<?= $escape(
                            $service['icon'] ?: 'fa-solid fa-code'
                        ) ?>"></i>
                    </div>

                    <h3><?= $escape($service['title']) ?></h3>

                    <p><?= nl2br($escape($service['description'])) ?></p>

                    <a href="contact.php?service=<?= urlencode($service['title']) ?>"
                       class="text-link">
                        Discuss this service
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>
                </article>
            <?php endforeach; ?>
        </div>

    <?php else: ?>

        <div class="empty-state">
            <i class="fa-solid fa-layer-group"></i>
            <h2>Services Coming Soon</h2>
            <p>
                My services will be listed here. Please feel free
                to contact me to discuss your project.
            </p>
            <a href="contact.php" class="btn btn-primary">
                Contact Me
            </a>
        </div>

    <?php endif; ?>
</section>

<section class="section cta-section">
    <div class="cta-content">
        <span class="section-tag">Let's work together</span>
        <h2>Have an idea worth <span class="gradient-text">building?</span></h2>
        <p>
            Tell me what you have in mind. Let's discuss how
            technology can help bring your idea to life.
        </p>
        <a href="contact.php" class="btn btn-primary">
            Start a Conversation
            <i class="fa-solid fa-paper-plane"></i>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
