<?php

namespace App\Entity;

use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\PlayerRepository")
 */
class Player
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $steamid;

    /**
     * @ORM\Column(type="datetime")
     */
    private $last_login;

    /**
     * @ORM\Column(type="string")
     */
    private $register_ip;

    /**
     * @ORM\Column(type="integer")
     */
    private $server;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSteamid(): ?string
    {
        return $this->steamid;
    }

    public function setSteamid(string $steamid): self
    {
        $this->steamid = $steamid;

        return $this;
    }

    public function getLastLogin()
    {
        return $this->last_login->format('Y-m-d H:i:s');
    }

    public function setLastLogin(DateTimeInterface $last_login): self
    {
        $this->last_login = $last_login;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getRegisterIp()
    {
        return $this->register_ip;
    }

    /**
     * @param mixed $register_ip
     */
    public function setRegisterIp($register_ip): void
    {
        $this->register_ip = $register_ip;
    }

    /**
     * @return mixed
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @param mixed $server
     */
    public function setServer($server): void
    {
        $this->server = $server;
    }
}
