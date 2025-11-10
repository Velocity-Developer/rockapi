<?php

/**
 * Build Extraction Script
 *
 * Extracts backend and frontend build ZIP files to specified directories.
 * Supports dry-run mode, progress tracking, and comprehensive logging.
 *
 * Usage: GET /extract-build.php?secret=SECRET_KEY&target=all&dry_run=false
 *
 * @version 1.0.0
 * @author Claude Code
 */

class ExtractBuildManager
{
    private $config;
    private $logger;
    private $startTime;
    private $response;

    // Default configuration
    private $defaultConfig = [
        'EXTRACT_BUILD_SECRET_KEY' => '',
        'BACKEND_BUILD_PATH' => '/laravel/build.zip',
        'FRONTEND_BUILD_PATH' => '/public_html/build.zip',
        'BACKEND_EXTRACT_PATH' => '/laravel/',
        'FRONTEND_EXTRACT_PATH' => '/public_html/',
        'LOG_PATH' => '/laravel/logs/extract-build.log',
        'LOG_MAX_SIZE' => 10485760, // 10MB
        'LOG_RETENTION_DAYS' => 30
    ];

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->response = [
            'status' => 'error',
            'message' => '',
            'execution' => [
                'dry_run' => false,
                'timestamp' => date('Y-m-d H:i:s'),
                'duration_ms' => 0
            ]
        ];

        $this->loadEnvironment();
        $this->initLogger();
    }

    /**
     * Load environment variables from .env file
     */
    private function loadEnvironment()
    {
        $envPath = __DIR__ . '/.env';

        if (!file_exists($envPath)) {
            $this->error('Environment file not found', 500);
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            if (strpos($line, '#') === 0) {
                continue; // Skip comments
            }

            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                // Remove quotes if present
                $value = trim($value, '"\'');

                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }

        // Load configuration with defaults
        foreach ($this->defaultConfig as $key => $default) {
            $this->config[$key] = $_ENV[$key] ?? $default;
        }

        // Convert relative paths to absolute
        $basePath = dirname(__DIR__);
        $this->config['BACKEND_BUILD_PATH'] = $basePath . '/build.zip';
        $this->config['FRONTEND_BUILD_PATH'] = $basePath . '/../public_html/build.zip';
        $this->config['BACKEND_EXTRACT_PATH'] = $basePath . '/';
        $this->config['FRONTEND_EXTRACT_PATH'] = $basePath . '/../public_html/';
        $this->config['LOG_PATH'] = $basePath . '/logs/extract-build.log';
    }

    /**
     * Initialize logger
     */
    private function initLogger()
    {
        $logDir = dirname($this->config['LOG_PATH']);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $this->rotateLogIfNeeded();
    }

    /**
     * Rotate log file if it exceeds size limit
     */
    private function rotateLogIfNeeded()
    {
        if (!file_exists($this->config['LOG_PATH'])) {
            return;
        }

        if (filesize($this->config['LOG_PATH']) > $this->config['LOG_MAX_SIZE']) {
            $timestamp = date('Y-m-d-H-i-s');
            $rotatedPath = str_replace('.log', "-{$timestamp}.log", $this->config['LOG_PATH']);
            rename($this->config['LOG_PATH'], $rotatedPath);

            // Clean old logs
            $this->cleanOldLogs();
        }
    }

    /**
     * Clean old log files
     */
    private function cleanOldLogs()
    {
        $logDir = dirname($this->config['LOG_PATH']);
        $cutoffDate = strtotime("-{$this->config['LOG_RETENTION_DAYS']} days");

        foreach (glob($logDir . '/extract-build-*.log') as $file) {
            if (filemtime($file) < $cutoffDate) {
                unlink($file);
            }
        }
    }

    /**
     * Log message to file
     */
    private function log($message, $level = 'INFO')
    {
        $timestamp = date('Y-m-d H:i:s');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        $logEntry = "[{$timestamp}] {$level}: {$message} (IP: {$ip})" . PHP_EOL;

        file_put_contents($this->config['LOG_PATH'], $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Send error response and exit
     */
    private function error($message, $code = 400, $errorType = 'general')
    {
        $this->response['status'] = 'error';
        $this->response['error_type'] = $errorType;
        $this->response['message'] = $message;
        $this->response['code'] = $code;
        $this->response['execution']['duration_ms'] = round((microtime(true) - $this->startTime) * 1000);

        $this->log("ERROR: {$message}", 'ERROR');
        $this->sendJsonResponse($code);
    }

    /**
     * Send JSON response
     */
    private function sendJsonResponse($httpCode = 200)
    {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        http_response_code($httpCode);

        echo json_encode($this->response, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * Validate secret key
     */
    private function validateSecretKey()
    {
        $providedSecret = $_GET['secret'] ?? '';

        if (empty($providedSecret)) {
            $this->error('Secret key is required', 403, 'authentication');
        }

        if ($providedSecret !== $this->config['EXTRACT_BUILD_SECRET_KEY']) {
            $this->error('Invalid secret key', 403, 'authentication');
        }

        $this->log('Authentication successful');
        return true;
    }

    /**
     * Get request parameters
     */
    private function getRequestParams()
    {
        $target = $_GET['target'] ?? 'all';
        $dryRun = filter_var($_GET['dry_run'] ?? 'false', FILTER_VALIDATE_BOOLEAN);

        if (!in_array($target, ['backend', 'frontend', 'all'])) {
            $this->error('Invalid target parameter. Must be: backend, frontend, or all', 400, 'validation');
        }

        return [
            'target' => $target,
            'dry_run' => $dryRun
        ];
    }

    /**
     * Check if file exists and is readable
     */
    private function checkZipFile($path)
    {
        if (!file_exists($path)) {
            return ['exists' => false, 'error' => 'File not found'];
        }

        if (!is_readable($path)) {
            return ['exists' => false, 'error' => 'File not readable'];
        }

        $size = filesize($path);
        if ($size === 0) {
            return ['exists' => false, 'error' => 'File is empty'];
        }

        return [
            'exists' => true,
            'size' => $size,
            'size_formatted' => $this->formatBytes($size)
        ];
    }

    /**
     * Check if directory is writable
     */
    private function checkDirectoryWritable($path)
    {
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true)) {
                return false;
            }
        }

        return is_writable($path);
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Extract ZIP file with progress tracking
     */
    private function extractZip($zipPath, $extractPath, $targetName)
    {
        $this->log("Starting {$targetName} extraction from {$zipPath} to {$extractPath}");

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== true) {
            $errorMessage = $this->getZipErrorMessage($result);
            $this->log("{$targetName} ZIP open failed: {$errorMessage}");
            return [
                'status' => 'error',
                'message' => "Failed to open ZIP file: {$errorMessage}"
            ];
        }

        $fileCount = $zip->numFiles;
        $this->log("{$targetName} ZIP contains {$fileCount} files");

        // Extract files
        $extractResult = $zip->extractTo($extractPath);
        $zip->close();

        if (!$extractResult) {
            $this->log("{$targetName} extraction failed");
            return [
                'status' => 'error',
                'message' => 'Failed to extract ZIP file'
            ];
        }

        $this->log("{$targetName} extraction completed successfully");

        return [
            'status' => 'extracted',
            'message' => "{$targetName} extracted successfully",
            'files_count' => $fileCount,
            'extract_path' => $extractPath
        ];
    }

    /**
     * Get ZIP error message
     */
    private function getZipErrorMessage($resultCode)
    {
        $errors = [
            ZipArchive::ER_EXISTS => 'File already exists',
            ZipArchive::ER_INCONS => 'Zip archive inconsistent',
            ZipArchive::ER_INVAL => 'Invalid argument',
            ZipArchive::ER_MEMORY => 'Malloc failure',
            ZipArchive::ER_NOENT => 'No such file',
            ZipArchive::ER_NOZIP => 'Not a zip archive',
            ZipArchive::ER_OPEN => 'Can\'t open file',
            ZipArchive::ER_READ => 'Read error',
            ZipArchive::ER_SEEK => 'Seek error'
        ];

        return $errors[$resultCode] ?? 'Unknown error';
    }

    /**
     * Process dry run
     */
    private function processDryRun($target)
    {
        $this->log('Starting dry run mode');

        $results = [];
        $targets = $target === 'all' ? ['backend', 'frontend'] : [$target];

        foreach ($targets as $currentTarget) {
            $zipPath = $this->config[strtoupper($currentTarget) . '_BUILD_PATH'];
            $extractPath = $this->config[strtoupper($currentTarget) . '_EXTRACT_PATH'];

            $zipCheck = $this->checkZipFile($zipPath);
            $dirWritable = $this->checkDirectoryWritable($extractPath);

            $results[$currentTarget] = [
                'zip_exists' => $zipCheck['exists'],
                'zip_size' => $zipCheck['size_formatted'] ?? '0 B',
                'extract_path_writable' => $dirWritable,
                'will_extract' => $zipCheck['exists'] && $dirWritable,
                'zip_path' => $zipPath,
                'extract_path' => $extractPath
            ];

            if (!$zipCheck['exists']) {
                $results[$currentTarget]['error'] = $zipCheck['error'];
            }
        }

        $this->response['status'] = 'success';
        $this->response['message'] = 'Dry run completed - no files extracted';
        $this->response['preview'] = $results;

        $this->log('Dry run completed');
    }

    /**
     * Process actual extraction
     */
    private function processExtraction($target)
    {
        $this->log('Starting extraction process');

        $results = [];
        $targets = $target === 'all' ? ['backend', 'frontend'] : [$target];

        foreach ($targets as $currentTarget) {
            $zipPath = $this->config[strtoupper($currentTarget) . '_BUILD_PATH'];
            $extractPath = $this->config[strtoupper($currentTarget) . '_EXTRACT_PATH'];

            // Check ZIP file
            $zipCheck = $this->checkZipFile($zipPath);
            if (!$zipCheck['exists']) {
                $results[$currentTarget] = [
                    'status' => 'error',
                    'message' => "ZIP file not found or invalid: {$zipCheck['error']}",
                    'zip_path' => $zipPath
                ];
                continue;
            }

            // Check extract directory
            if (!$this->checkDirectoryWritable($extractPath)) {
                $results[$currentTarget] = [
                    'status' => 'error',
                    'message' => 'Extract directory is not writable',
                    'extract_path' => $extractPath
                ];
                continue;
            }

            // Extract ZIP
            $extractResult = $this->extractZip($zipPath, $extractPath, $currentTarget);
            $extractResult['zip_path'] = $zipPath;
            $extractResult['extract_path'] = $extractPath;
            $extractResult['zip_size'] = $zipCheck['size_formatted'];

            $results[$currentTarget] = $extractResult;
        }

        // Check if any extractions were successful
        $hasSuccess = false;
        foreach ($results as $result) {
            if ($result['status'] === 'extracted') {
                $hasSuccess = true;
                break;
            }
        }

        if ($hasSuccess) {
            $this->response['status'] = 'success';
            $this->response['message'] = 'Build extraction completed';
        } else {
            $this->response['status'] = 'error';
            $this->response['message'] = 'No files were extracted successfully';
        }

        $this->response['results'] = $results;
        $this->log('Extraction process completed');
    }

    /**
     * Main execution method
     */
    public function execute()
    {
        try {
            // Validate secret key
            $this->validateSecretKey();

            // Get request parameters
            $params = $this->getRequestParams();
            $this->response['execution']['dry_run'] = $params['dry_run'];

            $this->log("START: target={$params['target']}, dry_run=" . ($params['dry_run'] ? 'true' : 'false'));

            // Process based on dry run flag
            if ($params['dry_run']) {
                $this->processDryRun($params['target']);
            } else {
                $this->processExtraction($params['target']);
            }

        } catch (Exception $e) {
            $this->error('Unexpected error: ' . $e->getMessage(), 500, 'exception');
        } finally {
            // Calculate execution time
            $this->response['execution']['duration_ms'] = round((microtime(true) - $this->startTime) * 1000);

            // Send response
            $httpCode = $this->response['status'] === 'success' ? 200 : 500;
            $this->sendJsonResponse($httpCode);
        }
    }
}

// Execute the script
$manager = new ExtractBuildManager();
$manager->execute();