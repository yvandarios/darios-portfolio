
<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

$pageTitle = 'Contact | DARIOS';
$pageDescription = 'Get in touch with Yvan Darios about projects, collaborations and opportunities.';

$escape = static function ($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
};

if (empty($_SESSION['contact_csrf'])) {
    $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));
}

$errors = [];
$name = '';
$email = '';
$subject = isset($_GET['service'])
    ? 'Enquiry about ' . trim($_GET['service'])
    : '';
$message = '';

$success = isset($_GET['sent']) && $_GET['sent'] === '1';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals($_SESSION['contact_csrf'], $token)) {
        $errors[] = 'Your session has expired. Please refresh the page and try again.';
    }

    if ($name === '' || mb_strlen($name) > 150) {
        $errors[] = 'Please enter your name (maximum 150 characters).';
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }

    if ($subject === '' || mb_strlen($subject) > 200) {
        $errors[] = 'Please enter a subject (maximum 200 characters).';
    }

    if ($message === '' || mb_strlen($message) > 5000) {
        $errors[] = 'Your message is required and must not exceed 5,000 characters.';
    }

    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO messages
                    (name, email, subject, message, status)
                VALUES
                    (:name, :email, :subject, :message, 'unread')
            ");

            $stmt->execute([
                'name' => $name,
                'email' => $email,
                'subject' => $subject,
                'message' => $message
            ]);

            $_SESSION['contact_csrf'] = bin2hex(random_bytes(32));

            header('Location: contact.php?sent=1');
            exit;

        } catch (PDOException $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $errors[] = 'We could not send your message right now. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<section class="page-hero">
    <span class="section-tag">Get in touch</span>
    <h1>Let's <span class="gradient-text">Connect</span></h1>
    <p>
        Have a project, opportunity or question?
        Send me a message. I'd be happy to hear from you.
    </p>
</section>

<section class="section contact-section">

    <div class="contact-info">
        <span class="section-tag">Contact information</span>
        <h2>Let's start a <span class="gradient-text">conversation.</span></h2>

        <p>
            Whether you want to discuss a project, collaborate
            or simply connect, you can reach me through these channels.
        </p>

        <div class="contact-method">
            <div class="contact-method-icon">
                <i class="fa-solid fa-envelope"></i>
            </div>
            <div>
                <span>Email</span>
                <a href="mailto:negoumyvandarios@gmail.com">
                    negoumyvandarios@gmail.com
                </a>
            </div>
        </div>

        <div class="contact-method">
            <div class="contact-method-icon">
                <i class="fa-brands fa-whatsapp"></i>
            </div>
            <div>
                <span>WhatsApp</span>
                <a href="https://wa.me/237657890793"
                   target="_blank" rel="noopener noreferrer">
                    +237 657 890 793
                </a>
            </div>
        </div>

        <div class="contact-method">
            <div class="contact-method-icon">
                <i class="fa-brands fa-linkedin-in"></i>
            </div>
            <div>
                <span>LinkedIn</span>
                <a href="https://www.linkedin.com/in/yvandarios"
                   target="_blank" rel="noopener noreferrer">
                    Yvan Darios
                </a>
            </div>
        </div>

        <div class="contact-method">
            <div class="contact-method-icon">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div>
                <span>Location</span>
                <p>Cameroon</p>
            </div>
        </div>
    </div>

    <div class="contact-form-wrapper">
        <h2>Send Me a Message</h2>
        <p>Fill in the form below and I'll get back to you.</p>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                Your message has been sent successfully. Thank you for reaching out!
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= $escape($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="contact.php" method="POST" class="contact-form">
            <input
                type="hidden"
                name="csrf_token"
                value="<?= $escape($_SESSION['contact_csrf']) ?>">

            <div class="form-group">
                <label for="name">Your Name</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    maxlength="150"
                    value="<?= $escape($name) ?>"
                    placeholder="Enter your name"
                    autocomplete="name"
                    required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    maxlength="150"
                    value="<?= $escape($email) ?>"
                    placeholder="Enter your email"
                    autocomplete="email"
                    required>
            </div>

            <div class="form-group">
                <label for="subject">Subject</label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    maxlength="200"
                    value="<?= $escape($subject) ?>"
                    placeholder="What is this about?"
                    required>
            </div>

            <div class="form-group">
                <label for="message">Your Message</label>
                <textarea
                    id="message"
                    name="message"
                    rows="6"
                    maxlength="5000"
                    placeholder="Write your message here..."
                    required><?= $escape($message) ?></textarea>
            </div>

            <button type="submit" class="btn btn-primary">
                Send Message
                <i class="fa-solid fa-paper-plane"></i>
            </button>
        </form>
    </div>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
