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

?>