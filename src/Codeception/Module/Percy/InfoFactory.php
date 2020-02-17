<?php

namespace Codeception\Module\Percy;

use Codeception\Module\WebDriver;

/**
 * Class InfoFactory
 *
 * @package Codeception\Module\Percy
 */
final class InfoFactory
{
    /**
     * Create environment info
     *
     * @param WebDriver $webDriver
     * @return string
     */
    public static function createEnvironmentInfo(WebDriver $webDriver) : string
    {
        $webDriverCapabilities = $webDriver->webDriver->getCapabilities();

        return sprintf(
            'codeception-php; %s; %s/%s',
            $webDriverCapabilities->getPlatform(),
            $webDriverCapabilities->getBrowserName(),
            $webDriverCapabilities->getVersion()
        );
    }

    /**
     * Create client info
     *
     * @return string
     */
    public static function createClientInfo() : string
    {
        return sprintf('%s/%s', getenv('CODECEPTION_PERCY_CLIENT_NAME'), getenv('CODECEPTION_PERCY_CLIENT_VERSION'));
    }
}
