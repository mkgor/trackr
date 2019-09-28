<?php

namespace App\Twig\Extensions;

use App\Entity\Server;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class ActiveServerExtension
 * @package Twig\Extensions
 */
class ActiveServerExtension extends AbstractExtension
{
    /**
     * @return array|TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('active_server', [$this, 'getActiveServer']),
        ];
    }

    /**
     * @param Server[] $list
     * @param int $id
     * @return Server|bool
     */
    public function getActiveServer(array $list, int $id) {
        foreach($list as $item) {
            if($item->getId() == $id) {
                return $item->getName();
            }
        }

        return "Server is not selected";
    }
}