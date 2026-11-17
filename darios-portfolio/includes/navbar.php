
<?php

$currentPage = basename($_SERVER['PHP_SELF']);

$navigationLinks = [
    'index.php' => 'Home',
    'about.php' => 'About',
    'services.php' => 'Services',
    'project.php' => 'Projects',
    'contact.php' => 'Contact'
];

?>

<header class="site-header">

    <nav class="navbar container">

        <a href="/darios-portfolio/index.php" class="logo">
            DARIOS<span>.</span>
        </a>

        <button class="menu-toggle"
                id="menuToggle"
                aria-label="Toggle navigation"
                aria-expanded="false">

            <i class="fas fa-bars"></i>

        </button>

        <div class="nav-menu" id="navMenu">

            <?php foreach ($navigationLinks as $url => $label): ?>

                <a href="/darios-portfolio/<?= $url ?>"
                   class="nav-link <?= $currentPage === $url ? 'active' : '' ?>">

                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>

                </a>

            <?php endforeach; ?>

            <a href="https://wa.me/237657890793"
               target="_blank"
               rel="noopener noreferrer"
               class="nav-cta">

                Let's Talk <i class="fas fa-arrow-up-right-from-square"></i>

            </a>

        </div>

    </nav>

</header>
