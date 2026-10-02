<?php

class PageController extends Controlador {
    public function __construct() {
        parent::__construct();
    }

    public function index(): void {
        $config = require __DIR__ . '/../config/config.php';
        $appUrl = $config['app']['url'];
        require __DIR__ . '/../views/index.php';
    }
}
