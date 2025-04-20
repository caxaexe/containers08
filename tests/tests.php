<?php

require_once __DIR__ . '/testframework.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../site/modules/database.php';
require_once __DIR__ . '/../site/modules/page.php';

$testFramework = new TestFramework();

// Инициализация базы данных для тестов
$db = new Database('/var/www/db/database.db'); // Используйте путь внутри контейнера

// Выполнение SQL файла для создания структуры и данных
$sqlContent = file_get_contents(__DIR__ . '/../sql/schema.sql');
$statements = explode(';', $sqlContent);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if (!empty($statement)) {
        $db->Execute($statement);
    }
}

// Тесты для класса Database
function testDbConnection() {
    global $db;
    return assertExpression(is_object($db), 'Database connection successful', 'Database connection failed');
}

function testDbCount() {
    global $db;
    $count = $db->Count('page');
    return assertExpression($count === 3, "Count method returns $count (expected 3)", "Count method failed");
}

function testDbCreate() {
    global $db;
    $id = $db->Create('page', ['title' => 'Test Page', 'content' => 'Test Content']);
    return assertExpression(is_numeric($id) && $id > 0, "Create method successful, new ID: $id", "Create method failed");
}

function testDbRead() {
    global $db;
    $item = $db->Read('page', 1);
    return assertExpression(is_array($item) && $item['title'] === 'Page 1', "Read method successful, title: {$item['title']}", "Read method failed");
}

function testDbUpdate() {
    global $db;
    $updated = $db->Update('page', 1, ['title' => 'Updated Page']);
    $item = $db->Read('page', 1);
    return assertExpression($updated > 0 && $item['title'] === 'Updated Page', "Update method successful, new title: {$item['title']}", "Update method failed");
}

function testDbDelete() {
    global $db;
    $deleted = $db->Delete('page', 3);
    $count = $db->Count('page');
    return assertExpression($deleted > 0 && $count === 2, "Delete method successful, new count: $count (expected 2)", "Delete method failed");
}

// Тесты для класса Page
function testPageRender() {
    global $config;
    $page = new Page(__DIR__ . '/../site/templates/index.tpl'); // Обновите путь к шаблону
    ob_start();
    $page->Render(['title' => 'Test Title', 'heading' => 'Test Heading', 'content' => 'Test Content']);
    $output = ob_get_clean();
    return assertExpression(strpos($output, '<title>Test Title</title>') !== false &&
                              strpos($output, '<h1>Test Heading</h1>') !== false &&
                              strpos($output, '<p>Test Content</p>') !== false,
                              "Render method successful", "Render method failed");
}

// Добавление тестов
$testFramework->add('Database connection', 'testDbConnection');
$testFramework->add('Database count', 'testDbCount');
$testFramework->add('Database create', 'testDbCreate');
$testFramework->add('Database read', 'testDbRead');
$testFramework->add('Database update', 'testDbUpdate');
$testFramework->add('Database delete', 'testDbDelete');
$testFramework->add('Page render', 'testPageRender');

// Запуск тестов
$testFramework->run();

// Вывод результатов
echo "Результаты тестов: " . $testFramework->getResult() . PHP_EOL;

?>