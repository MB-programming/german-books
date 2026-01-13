<?php
require_once 'config.php';
require_once 'seo-functions.php';

header('Content-Type: application/xml; charset=utf-8');

echo generateSitemapXml($pdo);
?>
