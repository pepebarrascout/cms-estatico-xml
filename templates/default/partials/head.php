<?php
/**
 * Common HTML <head> section
 * Variables:
 *   $settings   - array: site_name, site_description, meta_keywords, meta_author, site_url
 *   $pageTitle  - string (required)
 *   $pageDescription - string (optional, falls back to site_description)
 *   $canonicalUrl    - string (optional, falls back to $settings['site_url'])
 *   $ogImage         - string (optional, OG image URL)
 *   $ogType          - string (optional, defaults to 'website')
 */
if (!isset($settings)) { $settings = []; }
$siteName        = $settings['site_name'] ?? 'Mi Sitio';
$siteDesc        = $settings['site_description'] ?? '';
$metaKeywords    = $settings['meta_keywords'] ?? '';
$metaAuthor      = $settings['meta_author'] ?? '';
$siteUrl         = rtrim($settings['site_url'] ?? '/', '/');

$pageTitle       = $pageTitle ?? $siteName;
$pageDescription = $pageDescription ?? $siteDesc;
$canonicalUrl    = $canonicalUrl ?? $siteUrl;
$ogImage         = $ogImage ?? '';
$ogType          = $ogType ?? 'website';

if ($ogImage !== '' && strpos($ogImage, '://') === false) {
    $ogImage = $siteUrl . $ogImage;
}

/* Ensure canonical has the site origin if it is a relative path */
if (strpos($canonicalUrl, '://') === false) {
    $canonicalUrl = $siteUrl . $canonicalUrl;
}
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>

    <?php if (!empty($pageDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <?php if (!empty($metaKeywords)): ?>
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <?php if (!empty($metaAuthor)): ?>
    <meta name="author" content="<?= htmlspecialchars($metaAuthor, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <meta name="robots" content="index, follow">
    <meta name="generator" content="CMS Estatico XML">

    <!-- Canonical URL -->
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">

    <!-- Open Graph -->
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:type" content="<?= htmlspecialchars($ogType, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($canonicalUrl, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:site_name" content="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($ogImage)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <?php endif; ?>

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($pageDescription, ENT_QUOTES, 'UTF-8') ?>">
    <?php if (!empty($ogImage)): ?>
    <meta name="twitter:image" content="<?= htmlspecialchars($ogImage, ENT_QUOTES, 'UTF-8') ?>">
    <?php endif; ?>

    <!-- RSS -->
    <link rel="alternate" type="application/rss+xml" title="<?= htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8') ?> - RSS" href="<?= htmlspecialchars($siteUrl, ENT_QUOTES, 'UTF-8') ?>/feed.xml">

    <!-- Stylesheet -->
    <link rel="stylesheet" href="<?= htmlspecialchars($theme_css_url ?? '/templates/default/css/style.css', ENT_QUOTES, 'UTF-8') ?>">

    <!-- JSON-LD Structured Data: WebSite -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": <?= json_encode($siteName, JSON_UNESCAPED_UNICODE) ?>,
        "url": <?= json_encode($siteUrl, JSON_UNESCAPED_UNICODE) ?>,
        <?php if (!empty($siteDesc)): ?>
        "description": <?= json_encode($siteDesc, JSON_UNESCAPED_UNICODE) ?>,
        <?php endif; ?>
        "potentialAction": {
            "@type": "SearchAction",
            "target": <?= json_encode($siteUrl . '/buscar?q={search_term_string}', JSON_UNESCAPED_UNICODE) ?>,
            "query-input": "required name=search_term_string"
        }
    }
    </script>

    <!-- Page timing start -->
    <script>window._cms_start = performance.now();</script>
</head>
