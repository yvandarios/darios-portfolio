
<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'DARIOS | Software Engineer & Web Developer';
$pageDescription = 'Discover the portfolio of Yvan Darios, a Software Engineer and Web Developer focused on building modern digital solutions.';

$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$featuredProjects = [];
$services = [];
$skills = [];

try {
    $stmt = $pdo->query("
        SELECT id, title, slug, description, technologies, image,
               project_url, github_url
        FROM projects
        WHERE status = 'published' AND featured = 1
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $featuredProjects = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT id, title, description, icon
        FROM services
        WHERE status = 'active'
        ORDER BY created_at DESC
        LIMIT 3
    ");
    $services = $stmt->fetchAll();

    $stmt = $pdo->query("
        SELECT id, name
        FROM skills
        ORDER BY name ASC
    ");
    $skills = $stmt->fetchAll();

} catch (PDOException $e) {
    error_log('Homepage data error: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="hero-section">
    <div class="hero-content">
        <span class="section-tag">
            <span class="status-dot"></span>
            Available for opportunities
        </span>

        <p class="hero-intro">Hello, I'm</p>

        <h1>
            Yvan <span class="gradient-text">Darios</span>
        </h1>

        <h2>Software Engineer <span>&</span> Web Developer</h2>

        <p class="hero-description">
            I design and develop modern, functional and user-focused digital
            experiences. I enjoy transforming ideas into practical solutions
            through clean code, creativity and continuous learning.
        </p>

        <div class="hero-actions">
            <a href="project.php" class="btn btn-primary">
                Explore My Projects <i class="fa-solid fa-arrow-right"></i>
            </a>

            <a href="contact.php" class="btn btn-outline">
                Let's Talk
            </a>
        </div>

        <div class="hero-socials">
            <a href="https://www.linkedin.com/in/yvandarios"
               target="_blank" rel="noopener noreferrer"
               aria-label="LinkedIn">
                <i class="fa-brands fa-linkedin-in"></i>
            </a>

            <a href="https://github.com/yvandarios"
               target="_blank" rel="noopener noreferrer"
               aria-label="GitHub">
                <i class="fa-brands fa-github"></i>
            </a>

            <a href="mailto:negoumyvandarios@gmail.com"
               aria-label="Email">
                <i class="fa-solid fa-envelope"></i>
            </a>
        </div>
    </div>

    <div class="hero-visual">
        <div class="hero-image-frame">
            <div class="hero-image-placeholder">
                <i class="fa-solid fa-code"></i>
                <span>DARIOS</span>
                <small>Building ideas into reality</small>
            </div>
        </div>

        <div class="floating-card floating-card-top">
            <i class="fa-solid fa-laptop-code"></i>
            <span>Web Development</span>
        </div>

        <div class="floating-card floating-card-bottom">
            <i class="fa-solid fa-lightbulb"></i>
            <span>Creative Solutions</span>
        </div>
    </div>
</section>

<section class="section stats-section">
    <div class="stat-item">
        <h3>01+</h3>
        <p>Developer Mindset</p>
    </div>
    <div class="stat-item">
        <h3>100%</h3>
        <p>Commitment to Learning</p>
    </div>
    <div class="stat-item">
        <h3>∞</h3>
        <p>Ideas to Build</p>
    </div>
</section>

<section class="section" id="about-preview">
    <div class="section-heading">
        <span class="section-tag">A little about me</span>
        <h2>Building solutions with <span class="gradient-text">purpose.</span></h2>
        <p>
            I am passionate about software engineering, web technologies
            and solving real-world problems through technology.
        </p>
    </div>

    <div class="about-preview-content">
        <div class="about-preview-icon">
            <i class="fa-solid fa-terminal"></i>
        </div>

        <div>
            <h3>From ideas to digital experiences</h3>
            <p>
                My work combines technical thinking, problem-solving and
                attention to detail. I am continuously developing my skills
                and exploring better ways to build useful applications.
            </p>
            <a href="about.php" class="text-link">
                More about me <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="section" id="featured-projects">
    <div class="section-heading">
        <span class="section-tag">Selected work</span>
        <h2>Featured <span class="gradient-text">Projects</span></h2>
        <p>A selection of projects that demonstrate my development journey.</p>
    </div>

    <?php if (!empty($featuredProjects)): ?>
        <div class="projects-grid">
            <?php foreach ($featuredProjects as $project): ?>
                <article class="project-card">
                    <a class="project-image"
                       href="project.php?slug=<?= urlencode($project['slug']) ?>">
                        <?php if (!empty($project['image'])): ?>
                            <img
                                src="assets/images/projects/<?= $escape(basename($project['image'])) ?>"
                                alt="<?= $escape($project['title']) ?>"
                                loading="lazy">
                        <?php else: ?>
                            <div class="project-image-placeholder">
                                <i class="fa-solid fa-code"></i>
                            </div>
                        <?php endif; ?>
                    </a>

                    <div class="project-card-content">
                        <h3><?= $escape($project['title']) ?></h3>

                        <p>
                            <?= $escape(mb_strimwidth(
                                strip_tags($project['description']),
                                0,
                                150,
                                '...'
                            )) ?>
                        </p>

                        <?php if (!empty($project['technologies'])): ?>
                            <div class="technology-list">
                                <?php foreach (explode(',', $project['technologies']) as $technology): ?>
                                    <span><?= $escape(trim($technology)) ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <a href="project.php?slug=<?= urlencode($project['slug']) ?>"
                           class="text-link">
                            View Project <i class="fa-solid fa-arrow-right"></i>
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-folder-open"></i>
            <p>Projects will be displayed here soon.</p>
        </div>
    <?php endif; ?>

    <div class="section-footer">
        <a href="project.php" class="btn btn-outline">
            View All Projects
        </a>
    </div>
</section>

<section class="section" id="services-preview">
    <div class="section-heading">
        <span class="section-tag">What I do</span>
        <h2>My <span class="gradient-text">Services</span></h2>
        <p>Practical digital solutions designed around real needs.</p>
    </div>

    <?php if (!empty($services)): ?>
        <div class="services-grid">
            <?php foreach ($services as $service): ?>
                <article class="service-card">
                    <div class="service-icon">
                        <i class="<?= $escape($service['icon'] ?: 'fa-solid fa-code') ?>"></i>
                    </div>
                    <h3><?= $escape($service['title']) ?></h3>
                    <p><?= $escape($service['description']) ?></p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-solid fa-layer-group"></i>
            <p>Services will be displayed here soon.</p>
        </div>
    <?php endif; ?>

    <div class="section-footer">
        <a href="services.php" class="text-link">
            Discover all services <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</section>

<section class="section" id="skills-preview">
    <div class="section-heading">
        <span class="section-tag">My toolkit</span>
        <h2>Technologies & <span class="gradient-text">Skills</span></h2>
        <p>Technologies I work with and continue to improve.</p>
    </div>

    <?php if (!empty($skills)): ?>
        <div class="skills-list">
            <?php foreach ($skills as $skill): ?>
                <span class="skill-tag">
                    <i class="fa-solid fa-code"></i>
                    <?= $escape($skill['name']) ?>
                </span>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="empty-state">My skills will be listed here soon.</p>
    <?php endif; ?>
</section>

<section class="section cta-section">
    <div class="cta-content">
        <span class="section-tag">Have a project in mind?</span>
        <h2>Let's build something <span class="gradient-text">great.</span></h2>
        <p>
            Have an idea, an opportunity or a project to discuss?
            I would be happy to hear from you.
        </p>
        <a href="contact.php" class="btn btn-primary">
            Get in Touch <i class="fa-solid fa-paper-plane"></i>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
