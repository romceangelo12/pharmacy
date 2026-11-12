<?php
/**
 * Application Entry Point - Serves Built React SPA
 */
if (file_exists(__DIR__ . '/dist/index.html')) {
    include __DIR__ . '/dist/index.html';
} else {
    include __DIR__ . '/index.html';
}
