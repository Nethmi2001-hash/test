<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'Monastery Healthcare System' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- CSS Framework -->
    <link rel="stylesheet" href="/assets/css/framework.css">
    
    <!-- Additional CSS -->
    <?php if (isset($additional_css)): ?>
        <?php foreach ($additional_css as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Meta tags -->
    <meta name="description" content="<?= $description ?? 'Modern monastery healthcare and donation management system' ?>">
    <meta name="robots" content="<?= $robots ?? 'index,follow' ?>">
    
    <!-- Open Graph -->
    <meta property="og:title" content="<?= $title ?? 'Monastery Healthcare System' ?>">
    <meta property="og:description" content="<?= $description ?? 'Modern monastery healthcare and donation management system' ?>">
    <meta property="og:type" content="website">
</head>
<body class="<?= $body_class ?? '' ?>">
    <!-- Main Application Container -->
    <div id="app" class="min-h-screen">
        <?php if ($show_navigation ?? true): ?>
            <?php include __DIR__ . '/../components/navigation.php'; ?>
        <?php endif; ?>
        
        <main class="<?= $main_class ?? 'main-content' ?>">
            <?php if (isset($page_header)): ?>
                <div class="page-header mb-6">
                    <?= $page_header ?>
                </div>
            <?php endif; ?>
            
            <?php include __DIR__ . '/../components/alerts.php'; ?>
            
            <div class="content">
                <?= $content ?? '' ?>
            </div>
        </main>
        
        <?php if ($show_footer ?? true): ?>
            <?php include __DIR__ . '/../components/footer.php'; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modals Container -->
    <div id="modals-container"></div>
    
    <!-- Notification Container -->
    <div class="notification-container fixed top-4 right-4 z-50 space-y-2"></div>
    
    <!-- JavaScript Framework -->
    <script src="/assets/js/framework.js"></script>
    
    <!-- Chart.js for analytics -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- Additional JavaScript -->
    <?php if (isset($additional_js)): ?>
        <?php foreach ($additional_js as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Inline JavaScript -->
    <?php if (isset($inline_js)): ?>
        <script>
            <?= $inline_js ?>
        </script>
    <?php endif; ?>
</body>
</html>