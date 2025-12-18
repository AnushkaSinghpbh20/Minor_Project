<?php
// logger.php

require_once __DIR__ . '/vendor/autoload.php';

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

// Create logs folder if not exists
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    mkdir($logDir, 0777, true);
}

// Create logger
$logger = new Logger('TrainingSystem');

// Log format
$dateFormat = "Y-m-d H:i:s";
$output = "[%datetime%] %channel%.%level_name%: %message% %context%\n";
$formatter = new LineFormatter($output, $dateFormat, true, true);

// Log file (daily)
$logFile = $logDir . '/app-' . date('Y-m-d') . '.log';
$handler = new StreamHandler($logFile, Logger::DEBUG);
$handler->setFormatter($formatter);

$logger->pushHandler($handler);

// 🔥 TEST LOG (IMPORTANT)
$logger->info("Logger initialized successfully");

return $logger;
