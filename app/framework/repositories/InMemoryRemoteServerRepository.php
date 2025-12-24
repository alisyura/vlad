<?php

class InMemoryRemoteServerRepository implements RemoteServerRepositoryInterface 
{
    private array $servers = [];

    public function __construct(array $config) 
    {
        foreach ($config as $alias => $data) {
            $this->servers[$alias] = new RemoteServerConfig(
                $data['url'],
                $data['login'],
                $data['password'],
                $data['secret_key']
            );
        }
    }

    public function findByAlias(string $alias): ?RemoteServerConfig 
    {
        return $this->servers[$alias] ?? null;
    }
}