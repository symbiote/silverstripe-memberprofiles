<?php

// Handle local testing
$PROJECT_DIR = dirname(__FILE__).'/../../../..';
$BOOTSTRAP_FILE = $PROJECT_DIR . '/vendor/silverstripe/cms/tests/bootstrap.php';
if (!file_exists($BOOTSTRAP_FILE)) {
    // Handle Travis build
    $PROJECT_DIR = dirname(__FILE__).'/../..';
    $BOOTSTRAP_FILE = $PROJECT_DIR . '/vendor/silverstripe/cms/tests/bootstrap.php';
}
require_once($BOOTSTRAP_FILE);

// NOTE(Jake): 2018-05-01
//
// Hack to make Page / PageController work in a local dev project as
// I'm not sure how to load all classes and bootstrap through the SilverStripe config
// system.
//
if (!class_exists(PageController::class)) {
    class PageController extends \SilverStripe\CMS\Controllers\ContentController
    {
    }
}

if (!class_exists(Page::class)) {
    class Page extends \SilverStripe\CMS\Model\SiteTree
    {
    }
}
