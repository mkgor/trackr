<?php

namespace App\Service;

use Exception;
use xPaw\SourceQuery\SourceQuery;

/**
 * Class ServerInfoService
 *
 * @package App\Service
 */
class ServerInfoService
{
    /**
     * @param string $address
     *
     * @return array|bool
     */
    public function getInfo(string $address)
    {
        $address = $this->parseAddress($address);
        $query = new SourceQuery();

        try {
            $query->Connect($address['ip'], $address['port'], 1, SourceQuery::SOURCE);

            $result = $query->GetInfo();
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        } finally {
            $query->Disconnect();
        }

        return $result;
    }

    /**
     * @param string $address
     *
     * @return array|bool
     */
    public function getPlayers(string $address)
    {
        $address = $this->parseAddress($address);
        $query = new SourceQuery();

        try {
            $query->Connect($address['ip'], $address['port'], 1, SourceQuery::SOURCE);

            $result = $query->GetPlayers();
        } catch (Exception $e) {
            $result = ['error' => $e->getMessage()];
        } finally {
            $query->Disconnect();
        }

        return $result;
    }

    /**
     * @param string $address
     *
     * @return array
     */
    private function parseAddress(string $address)
    {
        $address = explode(':', $address);

        return [
            'ip'   => $address[0],
            'port' => $address[1],
        ];
    }
}