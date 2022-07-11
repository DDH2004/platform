<?php

namespace Utopia\Platform;

use Utopia\App;
use Utopia\CLI\CLI;

abstract class Platform
{
    protected array $services = [
        'all' => [],
        Service::TYPE_CLI => [],
        Service::TYPE_HTTP => [],
        Service::TYPE_GRAPHQL => []
    ];

    protected CLI $cli;

    /**
     * Initialize Application
     *
     * @return void
     */
    public function init(string $type): void
    {
        switch ($type) {
            case Service::TYPE_HTTP:
                $this->initHttp();
                break;
            case Service::TYPE_CLI:
                $this->initCLI();
                break;
            case Service::TYPE_GRAPHQL:
                $this->initGraphQL();
                break;
            case 'all':
            default:
                $this->initHttp();
                $this->initCli();
                $this->initGraphQL();
                break;
        }
    }

    /**
     * Init HTTP service
     *
     * @param Service $service
     * @return void
     */
    protected function initHttp(): void
    {
        foreach ($this->services[Service::TYPE_HTTP] as $service) {
            foreach ($service->getActions() as $action) {
                /** @var Action $action */
                $route = App::addRoute($action->getHttpMethod(), $action->getHttpPath());
                $route
                    ->groups($action->getGroups())
                    ->alias($action->getHttpAliasPath(), $action->getHttpAliasParams());

                foreach ($action->getParams() as $key => $param) {
                    $route->param($key, $param['default'], $param['validator'], $param['description'], $param['optional'], $param['injections']);
                }

                foreach ($action->getInjections() as $injection) {
                    $route->inject($injection);
                }

                foreach ($action->getLabels() as $key => $label) {
                    $route->label($key, $label);
                }

                $route->action($action->getCallback());
            }
        }
    }

    /**
     * Init CLI Services
     *
     * @return void
     */
    protected function initCLI(): void
    {
        $this->cli ??= new CLI();
        foreach ($this->services[Service::TYPE_CLI] as $service) {
            foreach ($service as $key => $action) {
                $task = $this->cli->task($key);
                $task
                    ->desc($action->getDesc())
                    ->action($action->getCallback());

                foreach ($action->getParams() as $key => $param) {
                    $task->param($key, $param['default'], $param['validator'], $param['description'], $param['optional'], $param['injections']);
                }

                foreach ($action->getLabels() as $key => $label) {
                    $task->label($key, $label);
                }
            }
        }
    }

    /**
     * Initialize GraphQL Services
     *
     * @return void
     */
    protected function initGraphQL(): void
    {
    }

    /**
     * Add Service
     *
     * @param string $key
     * @param Service $service
     * @return Platform
     */
    public function addService(string $key, Service $service): Platform
    {
        $this->services['all'][$key] = $service;
        $this->services[$service->getType()] = $service;
        return $this;
    }

    /**
     * Remove Service
     *
     * @param string $key
     * @return Platform
     */
    public function removeService(string $key): Platform
    {
        unset($this->services[$key]);
        return $this;
    }


    /**
     * Get Service
     *
     * @param string $key
     * @return Service|null
     */
    public function getService(string $key): ?Service
    {
        return $this->services['all'][$key] ?? null;
    }


    /**
     * Get Services
     *
     * @return array
     */
    public function getServices(): array
    {
        return $this->services['all'];
    }
}
