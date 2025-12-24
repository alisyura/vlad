<?php

interface RemoteServerRepositoryInterface 
{
    /**
     * @param string $alias Название сервера (например, '1c_sklad', '1c_test')
     * @return RemoteServerConfig|null
     */
    public function findByAlias(string $alias): ?RemoteServerConfig;
}