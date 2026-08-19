<?php

namespace AUS\SentryCronMonitor\Tests;

use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;

if ((new Typo3Version())->getMajorVersion() >= 14) {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
    final readonly class TestingRequestFactory extends RequestFactory
    {
        use TestingRequestFactoryTrait;
    }
} else {
    // phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
    final class TestingRequestFactory extends RequestFactory
    {
        use TestingRequestFactoryTrait;
    }
}
