<?php

require_once __DIR__ . 'config.php';
require_once __DIR__ . 'modules/database.php';
require_once __DIR__ . 'modules/page.php';

// Инициализация базы данных
$db = new Database(DB_PATH);

// Пример создания таблицы (если ее нет)
$db->Execute("CREATE TABLE IF NOT EXISTS items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT
)");

// Пример добавления данных
$itemId = $db->Create('items', ['name' => 'Элемент 1', 'description' => 'Описание элемента 1']);
if ($itemId) {
    echo "Создан элемент с ID: " . $itemId . "<br>";
}

// Пример получения количества элементов
$itemCount = $db->Count('items');

// Инициализация страницы
$page = new Page(TEMPLATE_PATH);

// Подготовка данных для шаблона
$pageData = [
    'title' => 'Главная страница',
    'heading' => 'Добро пожаловать на мой сайт!',
    'content' => 'Это пример веб-приложения на PHP с использованием SQLite.',
    'itemCount' => $itemCount
];

// Отображение страницы
$page->Render($pageData);

?>