<?php
/**
 * Performance Analysis Script
 * 
 * This script analyzes the performance logs to identify bottlenecks
 * in the accounts/all page loading.
 */

echo "🔧 Performance Analysis for Accounts/All Page\n";
echo "============================================\n\n";

// Read the log file - use the most recent one
$logDir = '/var/www/html/storage/logs/';
$logFiles = glob($logDir . 'ff3-fpm-fcgi-*.log');
if (empty($logFiles)) {
    echo "❌ No log files found in $logDir\n";
    exit(1);
}

// Get the most recent log file
$logFile = max($logFiles);
echo "📁 Using log file: " . basename($logFile) . "\n\n";

$logContent = file_get_contents($logFile);
$lines = explode("\n", $logContent);

// Filter for performance logs
$perfLogs = array_filter($lines, function($line) {
    return strpos($line, '[PERF]') !== false;
});

if (empty($perfLogs)) {
    echo "❌ No performance logs found. Please refresh the accounts/all page first.\n";
    exit(1);
}

echo "📊 Found " . count($perfLogs) . " performance log entries\n\n";

// Group logs by type
$logsByType = [];
foreach ($perfLogs as $line) {
    if (preg_match('/\[PERF\] ([^:]+):/', $line, $matches)) {
        $type = $matches[1];
        if (!isset($logsByType[$type])) {
            $logsByType[$type] = [];
        }
        $logsByType[$type][] = $line;
    }
}

// Analyze each type
foreach ($logsByType as $type => $logs) {
    echo "🔍 $type\n";
    echo str_repeat("-", strlen($type) + 3) . "\n";
    
    // Extract timing data
    $times = [];
    foreach ($logs as $log) {
        if (preg_match('/"time_ms":\s*([0-9.]+)/', $log, $matches)) {
            $times[] = floatval($matches[1]);
        }
    }
    
    if (!empty($times)) {
        $avg = array_sum($times) / count($times);
        $max = max($times);
        $min = min($times);
        $total = array_sum($times);
        
        echo "  📈 Times: " . count($times) . " calls\n";
        echo "  ⏱️  Average: " . round($avg, 2) . "ms\n";
        echo "  🚀 Fastest: " . round($min, 2) . "ms\n";
        echo "  🐌 Slowest: " . round($max, 2) . "ms\n";
        echo "  📊 Total: " . round($total, 2) . "ms\n";
        
        // Show breakdown if available
        if (preg_match('/"breakdown":\s*\{[^}]+\}/', $logs[0], $matches)) {
            echo "  📋 Breakdown:\n";
            if (preg_match('/"db_query_percent":\s*([0-9.]+)/', $logs[0], $dbMatch)) {
                echo "    - Database queries: " . $dbMatch[1] . "%\n";
            }
            if (preg_match('/"transaction_queries_percent":\s*([0-9.]+)/', $logs[0], $txMatch)) {
                echo "    - Transaction queries: " . $txMatch[1] . "%\n";
            }
            if (preg_match('/"balance_calc_percent":\s*([0-9.]+)/', $logs[0], $balMatch)) {
                echo "    - Balance calculations: " . $balMatch[1] . "%\n";
            }
            if (preg_match('/"other_percent":\s*([0-9.]+)/', $logs[0], $otherMatch)) {
                echo "    - Other operations: " . $otherMatch[1] . "%\n";
            }
        }
    }
    
    echo "\n";
}

// Show recent logs
echo "📝 Recent Performance Logs (last 10):\n";
echo "=====================================\n";
$recentLogs = array_slice($perfLogs, -10);
foreach ($recentLogs as $log) {
    $timestamp = '';
    if (preg_match('/^\[([^\]]+)\]/', $log, $matches)) {
        $timestamp = $matches[1];
    }
    echo "[$timestamp] " . substr($log, strpos($log, '[PERF]')) . "\n";
}

echo "\n✅ Analysis complete!\n";
echo "\n💡 Tips:\n";
echo "- Look for high 'total_time_ms' values in the main summary\n";
echo "- Check if balance calculations are taking too long\n";
echo "- Database queries should be fast (< 50ms)\n";
echo "- If accounts have no transactions, balance calc should be instant\n";
