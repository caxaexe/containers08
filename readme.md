# Лабораторная работа №8. Непрерывная интеграция с помощью Github Actions

## Цель работы
Цель данной лабораторной работы является изучение настройки непрерывной интеграции с помощью Github Actions.

## Задание
Создать Web приложение, написать тесты для него и настроить непрерывную интеграцию с помощью Github Actions на базе контейнеров.

## Ход работы
Для выполнения данной лабораторной работы я создаю репозиторий `containers08`. Для начала создаю внутри папку `site/`, где будет располагаться Web приложение на базе PHP, со следующим содержимым:
```
site
├── modules/
│   ├── database.php
│   └── page.php
├── templates/
│   └── index.tpl
├── styles/
│   └── style.css
├── config.php
└── index.php
```
Файл `modules/database.php` содержит класс Database для работы с базой данных:
```php
<?php

class Database {
    private $pdo;
    private $dbPath;

    public function __construct($path) {
        $this->dbPath = $path;
        try {
            $this->pdo = new PDO("sqlite:" . $path);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Ошибка подключения к базе данных: " . $e->getMessage());
        }
    }

    public function Execute($sql) {
        try {
            return $this->pdo->exec($sql);
        } catch (PDOException $e) {
            error_log("Ошибка выполнения SQL: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Fetch($sql) {
        try {
            $statement = $this->pdo->query($sql);
            return $statement ? $statement->fetch(PDO::FETCH_ASSOC) : false;
        } catch (PDOException $e) {
            error_log("Ошибка выполнения SQL и выборки: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Create($table, $data) {
        if (empty($data)) {
            return false;
        }

        $fields = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        $sql = "INSERT INTO {$table} ({$fields}) VALUES ({$placeholders})";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            error_log("Ошибка создания записи в таблице {$table}: " . $e->getMessage() . " SQL: " . $sql . " Data: " . print_r($data, true));
            return false;
        }
    }

    public function Read($table, $id) {
        $sql = "SELECT * FROM {$table} WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Ошибка чтения записи из таблицы {$table} с ID {$id}: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Update($table, $id, $data) {
        if (empty($data)) {
            return false;
        }

        $setClauses = [];
        foreach (array_keys($data) as $field) {
            $setClauses[] = "{$field} = :{$field}";
        }
        $setClause = implode(", ", $setClauses);

        $sql = "UPDATE {$table} SET {$setClause} WHERE id = :id";
        $data['id'] = $id;

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($data);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Ошибка обновления записи в таблице {$table} с ID {$id}: " . $e->getMessage() . " SQL: " . $sql . " Data: " . print_r($data, true));
            return false;
        }
    }

    public function Delete($table, $id) {
        $sql = "DELETE FROM {$table} WHERE id = :id";
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("Ошибка удаления записи из таблицы {$table} с ID {$id}: " . $e->getMessage() . " SQL: " . $sql);
            return false;
        }
    }

    public function Count($table) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        $result = $this->Fetch($sql);
        return $result ? intval($result['COUNT(*)']) : 0;
    }
}
```

Файл `modules/page.php` содержит класс Page для работы с страницами:
```php
<?php

class Page {
    private $templatePath;

    public function __construct($template) {
        $this->templatePath = $template;
    }

    public function Render($data = []) {
        if (!file_exists($this->templatePath)) {
            die("Ошибка: шаблон '{$this->templatePath}' не найден.");
        }

        extract($data); // Extract data into variables for the template

        ob_start();
        include $this->templatePath;
        $content = ob_get_clean();

        echo $content;
    }
}
```
Файл `templates/index.tpl` содержит шаблон страницы:
```tpl
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
```
Файл `styles/style.css` содержит стили для страницы:
```css
body {
    font-family: sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f4;
    color: #333;
}

.container {
    width: 80%;
    margin: 20px auto;
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}

header {
    text-align: center;
    padding-bottom: 20px;
    border-bottom: 1px solid #ccc;
    margin-bottom: 20px;
}

main {
    padding-bottom: 20px;
}

footer {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid #ccc;
    color: #777;
    font-size: 0.9em;
}
```

Файл `index.php` содержит код для отображения страницы:
```php
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
```

---

Создаю в корневом каталоге директорию `sql/`, а в созданной директории создаю файл `schema.sql` со следующим содержимым:
```sql
CREATE TABLE page (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT,
    content TEXT
);

INSERT INTO page (title, content) VALUES ('Page 1', 'Content 1');
INSERT INTO page (title, content) VALUES ('Page 2', 'Content 2');
INSERT INTO page (title, content) VALUES ('Page 3', 'Content 3');
```

---

Создаю в корневом каталоге директорию `tests/`. В созданном каталоге создаю файл `testframework.php` со следующим содержимым:
```php
<?php

function message($type, $message) {
    $time = date('Y-m-d H:i:s');
    echo "{$time} [{$type}] {$message}" . PHP_EOL;
}

function info($message) {
    message('INFO', $message);
}

function error($message) {
    message('ERROR', $message);
}

function assertExpression($expression, $pass = 'Pass', $fail = 'Fail'): bool {
    if ($expression) {
        info($pass);
        return true;
    }
    error($fail);
    return false;
}

class TestFramework {
    private $tests = [];
    private $success = 0;

    public function add($name, $test) {
        $this->tests[$name] = $test;
    }

    public function run() {
        foreach ($this->tests as $name => $test) {
            info("Running test {$name}");
            if ($test()) {
                $this->success++;
            }
            info("End test {$name}");
        }
    }

    public function getResult() {
        return "{$this->success} / " . count($this->tests);
    }
}
```

Также создаю в данной директории файл `tests.php` со следующим содержимым:
```php
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
```

---

Создаю в корневом каталоге файл `Dockerfile` со следующим содержимым:
```dockerfile
FROM php:8.4-fpm as base

RUN apt-get update && \
    apt-get install -y sqlite3 libsqlite3-dev && \
    docker-php-ext-install pdo_sqlite

VOLUME ["/var/www/db"]

COPY sql/schema.sql /var/www/db/schema.sql

RUN echo "prepare database" && \
    cat /var/www/db/schema.sql | sqlite3 /var/www/db/db.sqlite && \
    chmod 777 /var/www/db/db.sqlite && \
    rm -rf /var/www/db/schema.sql && \
    echo "database is ready"

COPY site /var/www/html
```

---

Создаю в корневом каталоге репозитория файл `.github/workflows/main.yml` со следующим содержимым:
```yml
name: CI

on:
  push:
    branches:
      - main

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - name: Checkout
        uses: actions/checkout@v4
      - name: Build the Docker image
        run: docker build -t containers08 .
      - name: Create `container`
        run: docker create --name container --volume database:/var/www/db containers08
      - name: Copy project to the container
        run: docker cp . container:/var/www/html
      - name: Up the container
        run: docker start container
      - name: Run tests
        run: docker exec container php /var/www/html/tests/tests.php
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
```

--- 

В итоге провожу тестирование в GitHub Actions и после сотой попытки вижу следующий результат:
![Снимок экрана 2025-04-20 180504](https://github.com/user-attachments/assets/a8652eb9-0fce-4ba0-ae49-4f383b3205f6)  
Все работает (×_×) 

## Вывод
В ходе работы было создано Web-приложение на PHP с использованием SQLite и реализованы классы Database и Page. Для проверки корректности работы разработаны тесты и собственный тестовый фреймворк. Настроена автоматическая сборка и тестирование проекта с помощью Docker и GitHub Actions. Таким образом, получен практический опыт настройки CI/CD и автоматизации тестирования в процессе разработки.

## Контрольные вопросы
**1. Что такое непрерывная интеграция?**  
Непрерывная интеграция (Continuous Integration, CI) — это практика разработки, при которой изменения в коде регулярно (обычно при каждом коммите) интегрируются в общий репозиторий, и автоматически запускаются проверки (тесты, сборка, статический анализ и т.д.).
Цель — как можно раньше обнаружить ошибки и проблемы, чтобы обеспечить стабильность проекта.

**2. Для чего нужны юнит-тесты? Как часто их нужно запускать?**  
Юнит-тесты — это тесты, которые проверяют работу отдельных компонентов (функций, методов, классов) в изоляции от остальной системы.  
Зачем нужны:
- Проверяют корректность логики.
- Помогают избежать регрессий при изменениях.
- Повышают уверенность в стабильности кода.  

Как часто запускать:
- Автоматически при каждом коммите.
- При каждом Pull Request.
- Локально перед отправкой изменений в репозиторий.

**3. Что нужно изменить в файле .github/workflows/main.yml для того, чтобы тесты запускались при каждом создании запроса на слияние (Pull Request)?**  
Изменить блок on: следующим образом:
```yaml
on:
  push:
    branches:
      - main
  pull_request:
    branches:
      - main
```
Теперь тесты будут запускаться при push в main и при создании Pull Request в main.

**4. Что нужно добавить в файл .github/workflows/main.yml для того, чтобы удалять созданные образы после выполнения тестов?** 
Добавить шаг в конце с удалением Docker-образа:
```yaml
      - name: Remove Docker image
        run: docker rmi containers08
```
Полный пример окончания steps:
```yaml
      - name: Stop the container
        run: docker stop container
      - name: Remove the container
        run: docker rm container
      - name: Remove Docker image
        run: docker rmi containers08
```
