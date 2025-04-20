<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? 'Мой сайт'; ?></title>
    <link rel="stylesheet" href="styles/style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1><?php echo $heading ?? 'Добро пожаловать!'; ?></h1>
        </header>
        <main>
            <?php if (isset($content)): ?>
                <p><?php echo $content; ?></p>
            <?php else: ?>
                <p>Основной контент страницы.</p>
            <?php endif; ?>
            <?php if (isset($itemCount)): ?>
                <p>Количество элементов в базе данных: <?php echo $itemCount; ?></p>
            <?php endif; ?>
        </main>
        <footer>
            <p>&copy; <?php echo date('Y'); ?> Мой сайт</p>
        </footer>
    </div>
</body>
</html>