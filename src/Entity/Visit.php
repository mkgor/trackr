<?php

namespace App\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\VisitRepository")
 */
class Visit
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Server", inversedBy="visits")
     */
    private $server;

    /**
     * @ORM\Column(type="datetime", options={"default": "CURRENT_TIMESTAMP"})
     */
    private $time;

    /**
     * @ORM\Column(type="string")
     */
    private $steamid;

    /**
     * @ORM\Column(type="string")
     */
    private $ip;

    /**
     * @ORM\Column(type="boolean")
     */
    private $player_unique;

    /**
     * @ORM\Column(type="boolean")
     */
    private $player_new;

    /**
     * Visit constructor.
     * @throws \Exception
     */
    public function __construct()
    {
        $this->time = new DateTime();
        $this->player_unique = false;
        $this->player_new = false;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Server|null
     */
    public function getServer(): ?Server
    {
        return $this->server;
    }

    /**
     * @param Server|null $server
     * @return Visit
     */
    public function setServer(?Server $server): self
    {
        $this->server = $server;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getTime()
    {
        return $this->time->format('Y-m-d H:i:s');
    }

    /**
     * @param mixed $time
     */
    public function setTime($time): void
    {
        $this->time = $time;
    }

    /**
     * @return mixed
     */
    public function getSteamid()
    {
        return $this->steamid;
    }

    /**
     * @param mixed $steamid
     */
    public function setSteamid($steamid): void
    {
        $this->steamid = $steamid;
    }

    /**
     * @return mixed
     */
    public function getIp()
    {
        return $this->ip;
    }

    /**
     * @param mixed $ip
     */
    public function setIp($ip): void
    {
        $this->ip = $ip;
    }

    /**
     * @return mixed
     */
    public function getNew()
    {
        return $this->player_new;
    }

    /**
     * @param mixed $new
     */
    public function setNew($new): void
    {
        $this->player_new = $new;
    }

    /**
     * @return mixed
     */
    public function getUnique()
    {
        return $this->player_unique;
    }

    /**
     * @param mixed $unique
     */
    public function setUnique($unique): void
    {
        $this->player_unique = $unique;
    }
}
