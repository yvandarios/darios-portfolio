
<?php
require_once __DIR__ . '/config/database.php';

$pageTitle = 'About Me | DARIOS';
$pageDescription = 'Learn more about Yvan Darios, his software engineering background, interests and development approach.';

$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};

$skills = [];

try {
    $stmt = $pdo->query("SELECT id, name FROM skills ORDER BY name ASC");
    $skills = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('About page skills error: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <span class="section-tag">Get to know me</span>
    <h1>About <span class="gradient-text">Me</span></h1>
    <p>
        A developer driven by curiosity, creativity and the desire
        to build meaningful digital solutions.
    </p>
</section>

<section class="section about-main">
    <div class="about-layout">
        <div class="about-visual">
            <div class="about-image-placeholder">
                <i class="fa-solid fa-user-astronaut"></i>
                <span>DARIOS</span>
            </div>
            <div class="about-visual-caption">
                <span class="status-dot"></span>
                Always learning. Always building.
            </div>
        </div>

        <div class="about-text">
            <span class="section-tag">Who I am</span>
            <h2>I'm Yvan <span class="gradient-text">Darios.</span></h2>

            <p>
                I am a Software Engineering student and aspiring Software
                Engineer & Web Developer with an interest in creating
                useful, reliable and well-designed digital products.
            </p>

            <p>
                I enjoy understanding how systems work, solving technical
                problems and turning ideas into applications that people
                can actually use.
            </p>

            <p>
                My development journey is built around continuous learning,
                practical projects and improving the quality of my work
                one step at a time.
            </p>

            <a href="contact.php" class="btn btn-primary">
                Contact Me <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <span class="section-tag">My journey</span>
        <h2>Education & <span class="gradient-text">Experience</span></h2>
    </div>

    <div class="timeline">
        <article class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <span class="timeline-date">2026</span>
                <h3>Higher National Diploma — Software Engineering</h3>
                <p>
                    IUH. Developing knowledge in software engineering,
                    programming, databases and application development.
                </p>
            </div>
        </article>

        <article class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <span class="timeline-date">July – August 2025</span>
                <h3>IT Internship — Global Technologies</h3>
                <p>
                    Practical exposure to IT and digital services,
                    including computer maintenance, networking,
                    graphic design and programming.
                </p>
            </div>
        </article>

        <article class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <span class="timeline-date">2024</span>
                <h3>GCE Advanced Level</h3>
                <p>Completed Advanced Level studies.</p>
            </div>
        </article>

        <article class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
                <span class="timeline-date">2022</span>
                <h3>GCE Ordinary Level</h3>
                <p>Completed Ordinary Level studies.</p>
            </div>
        </article>
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <span class="section-tag">What drives me</span>
        <h2>My <span class="gradient-text">Approach</span></h2>
    </div>

    <div class="values-grid">
        <article class="value-card">
            <i class="fa-solid fa-brain"></i>
            <h3>Problem Solving</h3>
            <p>I approach challenges with logic, patience and curiosity.</p>
        </article>

        <article class="value-card">
            <i class="fa-solid fa-graduation-cap"></i>
            <h3>Continuous Learning</h3>
            <p>I keep exploring new concepts and improving my technical skills.</p>
        </article>

        <article class="value-card">
            <i class="fa-solid fa-rocket"></i>
            <h3>Building & Creating</h3>
            <p>I enjoy transforming concepts into working digital products.</p>
        </article>
    </div>
</section>

<section class="section">
    <div class="section-heading">
        <span class="section-tag">Technical toolkit</span>
        <h2>My <span class="gradient-text">Skills</span></h2>
        <p>These skills are loaded directly from the portfolio database.</p>
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
        <div class="empty-state">
            <p>Skills will appear here when they are added through the admin panel.</p>
        </div>
    <?php endif; ?>
</section>

<section class="section cta-section">
    <div class="cta-content">
        <h2>Interested in working together?</h2>
        <p>Let's connect and discuss your next idea.</p>
        <a href="contact.php" class="btn btn-primary">
            Let's Talk <i class="fa-solid fa-paper-plane"></i>
        </a>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
