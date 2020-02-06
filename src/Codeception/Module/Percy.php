<?php

namespace Codeception\Module;

use Codeception\Module;
use Codeception\TestInterface;
use Codeception\Module\Percy\Exchange\Client;

/**
 * Class Percy
 *
 * @package Codeception\Module
 */
class Percy extends Module
{
    /**
     * @var array
     */
    private $config = [
        'module' => 'WebDriver',
        'agentEndpoint' => 'http://localhost:5338',
        'agentConfig' => [
            'handleAgentCommunication' => false
        ]
    ];

    /**
     * @var \Codeception\Module\WebDriver
     */
    private $webDriver;

    /**
     * @var string
     */
    private $percyAgentJs;

    /**
     * @inheritDoc
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Codeception\Module\Percy\Exception\ClientException
     * @param \Codeception\TestInterface $test
     */
    public function _before(TestInterface $test) : void
    {
        $this->webDriver = $this->getModule($this->config['module']);
        $this->percyAgentJs = Client::fromUrl($this->buildUrl('percy-agent.js'))->get();
    }

    /**
     * Take snapshot of DOM and send to https://percy.io
     *
     * @throws \Codeception\Exception\ModuleException
     * @throws \Codeception\Module\Percy\Exception\ClientException
     * @param string      $name
     * @param int|null    $minHeight
     * @param string|null $percyCss
     * @param bool        $enableJavaScript
     * @param array|null  $widths
     * @author Daniel Doyle <dd@amp.co>
     */
    public function snapshot(
        string $name,
        ?int $minHeight = null,
        ?string $percyCss = null,
        bool $enableJavaScript = false,
        ?array $widths = null
    ) : void {
        $this->webDriver->executeJS($this->percyAgentJs);

        try {
            $domSnapshot = $this->webDriver->executeJS(
                sprintf(
                    'var percyAgentClient = new PercyAgent(%s); return percyAgentClient.snapshot(\'not used\')',
                    json_encode($this->config['agentConfig'])
                )
            );
        } catch (\Exception $exception) {
            return;
        }

        $this->postSnapshot(
            $domSnapshot,
            $name,
            $this->webDriver->_getCurrentUri(),
            $minHeight,
            $percyCss,
            $enableJavaScript,
            $widths
        );
    }

    /**
     * Post to https://percy.io
     *
     * @throws \Codeception\Module\Percy\Exception\ClientException
     * @param string      $domSnapshot
     * @param string      $name
     * @param string      $url
     * @param int|null    $minHeight
     * @param string|null $percyCss
     * @param bool        $enableJavaScript
     * @param array|null  $widths
     * @author Daniel Doyle <dd@amp.co>
     */
    private function postSnapshot(
        string $domSnapshot,
        string $name,
        string $url,
        ?int $minHeight = null,
        ?string $percyCss = null,
        bool $enableJavaScript = false,
        ?array $widths = null
    ) : void {
        $payload = [
            'url' => $url,
            'name' => $name,
            'percyCSS' => $percyCss,
            'minHeight' => $minHeight,
            'domSnapshot' => $domSnapshot,
            'enableJavaScript' => $enableJavaScript
        ];

        if ($widths) {
            $payload['widths'] = $widths;
        }

        Client::fromUrl($this->buildUrl('percy/snapshot'))->post($payload);
    }

    /**
     * Build URL relative to agent endpoint
     *
     * @param string|null $path
     * @return string
     * @author Daniel Doyle <dd@amp.co>
     */
    private function buildUrl(?string $path = null) : string
    {
        return rtrim($this->config['agentEndpoint'], '/') . '/' . $path;
    }
}