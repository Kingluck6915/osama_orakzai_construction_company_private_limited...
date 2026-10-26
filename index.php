<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';

$projects = db()->query('SELECT id, title, category, location, summary, image_path FROM projects WHERE published = 1 ORDER BY sort_order ASC, created_at DESC LIMIT 6')->fetchAll();
$services = db()->query('SELECT title, summary FROM services WHERE published = 1 ORDER BY sort_order ASC, created_at ASC')->fetchAll();
$inquirySent = isset($_GET['sent']) && $_GET['sent'] === '1';
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    if (!empty($_POST['website'])) {
        redirect('/?sent=1#contact');
    }

    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $projectType = trim((string) ($_POST['project_type'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || mb_strlen($name) > 120 || !filter_var($email, FILTER_VALIDATE_EMAIL)
        || mb_strlen($email) > 190 || mb_strlen($phone) > 40 || $projectType === ''
        || mb_strlen($projectType) > 100 || mb_strlen($message) < 10 || mb_strlen($message) > 5000) {
        $formError = 'Please check the required fields and try again.';
    } else {
        $stmt = db()->prepare('INSERT INTO inquiries (name, email, phone, project_type, message, status, created_at) VALUES (?, ?, ?, ?, ?, "New", NOW())');
        $stmt->execute([$name, $email, $phone, $projectType, $message]);
        redirect('/?sent=1#contact');
    }
}

$waMessage = rawurlencode('Hello, I would like to discuss a construction project with Osama Orakzai Construction Company.');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Osama Orakzai Construction Company — considered construction, clear communication, and enduring workmanship for your next project.">
    <meta name="theme-color" content="#171a18">
    <meta property="og:title" content="Osama Orakzai Construction Company">
    <meta property="og:description" content="Thoughtful construction. Built to endure.">
    <meta property="og:type" content="website">
    <title>Osama Orakzai Construction Company | Built to Endure</title>
    <link rel="icon" type="image/svg+xml" href="<?= e(app_path('/assets/images/favicon.svg')) ?>">
    <link rel="preconnect" href="https://images.unsplash.com">
    <link rel="stylesheet" href="<?= e(app_path('/assets/css/site.css')) ?>">
    <script src="<?= e(app_path('/assets/js/site.js')) ?>" defer></script>
</head>
<body>
<a class="skip-link" href="#main">Skip to content</a>
<header class="site-header" data-header>
    <a class="brand" href="#top" aria-label="Osama Orakzai Construction Company home">
        <span class="brand-mark">OO</span><span class="brand-name">OSAMA ORAKZAI<small>CONSTRUCTION COMPANY</small></span>
    </a>
    <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-nav" aria-label="Open navigation"><span></span><span></span></button>
    <nav class="primary-nav" id="primary-nav" aria-label="Main navigation">
        <a href="#approach">Approach</a><a href="#services">Services</a><a href="#projects">Projects</a><a href="#contact" class="nav-contact">Start a conversation <span aria-hidden="true">↗</span></a>
    </nav>
</header>
<main id="main">
    <section class="hero" id="top" aria-labelledby="hero-title">
        <div class="hero-image" role="img" aria-label="Modern concrete architecture framed by warm evening light"></div>
        <div class="hero-shade"></div>
        <div class="hero-content">
            <p class="eyebrow light"><span class="eyebrow-line"></span> CONSTRUCTION · ENGINEERING · CRAFT</p>
            <h1 id="hero-title">A stronger<br><em>foundation</em><br>for what comes next.</h1>
            <p class="hero-intro">Thoughtful construction, clear communication, and a commitment to work made to last.</p>
            <div class="hero-actions"><a href="#projects" class="button button-light">Explore our work <span aria-hidden="true">↘</span></a><a href="#contact" class="text-link light-link">Discuss a project <span aria-hidden="true">↗</span></a></div>
        </div>
        <div class="hero-index"><span>01</span><span class="index-rule"></span><span>BUILT WITH INTENTION</span></div>
        <a class="scroll-cue" href="#approach"><span>SCROLL TO EXPLORE</span><b aria-hidden="true">↓</b></a>
    </section>

    <section class="intro section-pad" id="approach">
        <div class="intro-label"><span class="eyebrow"><span class="eyebrow-line"></span> OUR APPROACH</span><span class="section-number">01 / 04</span></div>
        <div class="intro-copy">
            <h2>Built on care.<br><em>Made for life.</em></h2>
            <div class="intro-description"><p>Good construction is more than what you see on completion day. It is the care in every decision, the discipline behind every detail, and the trust built along the way.</p><p>Osama Orakzai Construction Company brings a considered, hands-on approach to building — from the first conversation through to the final finish.</p><a href="#contact" class="text-link">Tell us what you have in mind <span aria-hidden="true">↗</span></a></div>
        </div>
        <div class="intro-rule"></div>
        <div class="principles"><div><span>01</span><h3>Clear communication</h3><p>Open conversations and shared expectations at every stage.</p></div><div><span>02</span><h3>Thoughtful planning</h3><p>Careful attention to the details that make a project work.</p></div><div><span>03</span><h3>Lasting workmanship</h3><p>A focus on quality, durability, and the finished experience.</p></div></div>
    </section>

    <section class="services section-pad" id="services">
        <div class="section-heading">
            <div><span class="eyebrow"><span class="eyebrow-line"></span> WHAT WE DO</span><h2>Considered at<br><em>every scale.</em></h2></div>
            <p>From early planning to the final details, we bring focus and care to the work that shapes your space.</p>
        </div>
        <?php if ($services): ?>
            <div class="service-list">
                <?php foreach ($services as $index => $service): ?>
                    <article class="service-row"><span class="service-number"><?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span><h3><?= e($service['title']) ?></h3><p><?= e($service['summary']) ?></p><span class="service-arrow" aria-hidden="true">↗</span></article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="service-empty">Service details are being updated. <a href="#contact">Contact us to discuss your project <span aria-hidden="true">↗</span></a></p>
        <?php endif; ?>
    </section>

    <section class="work section-pad" id="projects">
        <div class="section-heading work-heading">
            <div><span class="eyebrow"><span class="eyebrow-line"></span> SELECTED WORK</span><h2>Spaces with<br><em>purpose.</em></h2></div>
            <p>Every project begins with a conversation. Here is where our work will take shape.</p>
        </div>
        <?php if ($projects): ?>
            <div class="project-grid">
                <?php foreach ($projects as $index => $project): ?>
                    <article class="project-card">
                        <div class="project-image-wrap">
                            <img src="<?= e(app_path($project['image_path'] ?: '/assets/images/project-placeholder.svg')) ?>" alt="<?= e($project['title']) ?>" loading="lazy">
                            <span class="project-index">PROJECT <?= str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) ?></span>
                        </div>
                        <div class="project-meta"><div><h3><?= e($project['title']) ?></h3><p><?= e($project['summary']) ?></p></div><span><?= e($project['category']) ?><?php if ($project['location']): ?> · <?= e($project['location']) ?><?php endif; ?></span></div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="work-empty"><span class="empty-mark" aria-hidden="true">✳</span><div><h3>The portfolio is taking shape.</h3><p>We’re preparing a selection of completed work. In the meantime, tell us about the project you have in mind.</p></div><a href="#contact" class="text-link">Start a conversation <span aria-hidden="true">↗</span></a></div>
        <?php endif; ?>
    </section>

    <section class="statement">
        <div class="statement-image" role="img" aria-label="Architectural concrete detail"></div><div class="statement-overlay"></div>
        <div class="statement-content"><span class="eyebrow light"><span class="eyebrow-line"></span> THE DETAILS MATTER</span><p>“The quality of a place is felt long after the work is done.”</p><span class="statement-caption">A PRINCIPLE WE BUILD BY</span></div>
    </section>

    <section class="contact section-pad" id="contact">
        <div class="contact-heading"><span class="eyebrow"><span class="eyebrow-line"></span> YOUR NEXT CHAPTER</span><h2>Let’s make<br><em>something lasting.</em></h2><p>Share a little about your project. We’ll be glad to start a conversation.</p>
            <div class="contact-direct"><a href="tel:+923336703761">+92 333 6703761</a><a href="mailto:osamaorakzai63@gmail.com">osamaorakzai63@gmail.com</a></div>
            <a class="whatsapp-link" href="https://wa.me/<?= e($config['whatsapp']) ?>?text=<?= $waMessage ?>" target="_blank" rel="noopener noreferrer"><span class="whatsapp-icon" aria-hidden="true">↗</span> Or message us on WhatsApp</a>
        </div>
        <div class="contact-form-wrap">
            <?php if ($inquirySent): ?><div class="form-success" role="status"><span>THANK YOU</span><h3>Your message is with us.</h3><p>We appreciate you reaching out and look forward to learning more.</p></div><?php else: ?>
                <?php if ($formError): ?><p class="form-error" role="alert"><?= e($formError) ?></p><?php endif; ?>
                <form class="contact-form" method="post" action="#contact">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <label class="honeypot" aria-hidden="true">Leave this field empty<input name="website" tabindex="-1" autocomplete="off"></label>
                    <div class="form-row"><label>Your name<input name="name" required maxlength="120" autocomplete="name" placeholder="Name"></label><label>Email address<input name="email" type="email" required maxlength="190" autocomplete="email" placeholder="name@example.com"></label></div>
                    <div class="form-row"><label>Phone number <span>(optional)</span><input name="phone" type="tel" maxlength="40" autocomplete="tel" placeholder="+92"></label><label>Project type<select name="project_type" required><option value="">Select a project type</option><option>Residential construction</option><option>Commercial construction</option><option>Renovation / refurbishment</option><option>Project coordination</option><option>Other</option></select></label></div>
                    <label>Tell us about your project<textarea name="message" required minlength="10" maxlength="5000" rows="4" placeholder="A few details about the project, location, or what you’re looking for..."></textarea></label>
                    <button type="submit" class="button button-dark">Send project enquiry <span aria-hidden="true">↗</span></button>
                </form>
            <?php endif; ?>
        </div>
    </section>
</main>
<footer class="site-footer">
    <div class="footer-top"><a class="brand footer-brand" href="#top"><span class="brand-mark">OO</span><span class="brand-name">OSAMA ORAKZAI<small>CONSTRUCTION COMPANY</small></span></a><span class="footer-manifesto">Built with care.<br>Made to endure.</span><a class="back-top" href="#top">BACK TO TOP <span aria-hidden="true">↑</span></a></div>
    <div class="footer-bottom"><span>© <?= date('Y') ?> Osama Orakzai Construction Company</span><span>Construction · Engineering · Craft</span><a href="mailto:<?= e($config['email']) ?>"><?= e($config['email']) ?></a></div>
</footer>
</body>
</html>
