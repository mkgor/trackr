<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ServerRepository")
 */
class Server
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
    private $name;

    /**
     * @ORM\Column(type="string")
     */
    private $ip;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Visit", mappedBy="server")
     */
    private $visits;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $loading_url;

    /**
     * @ORM\Column(type="string", length=16)
     */
    private $hash;

    /**
     * @ORM\Column(type="integer")
     */
    private $active;

    /**
     * @ORM\Column(type="integer")
     */
    private $online;

    /**
     * @ORM\Column(type="integer")
     */
    private $maxOnline;


    public function __construct()
    {
        $this->visits = new ArrayCollection();
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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection|Visit[]
     */
    public function getVisits(): Collection
    {
        return $this->visits;
    }

    public function addVisit(Visit $visit): self
    {
        if (!$this->visits->contains($visit)) {
            $this->visits[] = $visit;
            $visit->setServer($this);
        }

        return $this;
    }

    public function removeVisit(Visit $visit): self
    {
        if ($this->visits->contains($visit)) {
            $this->visits->removeElement($visit);
            // set the owning side to null (unless already changed)
            if ($visit->getServer() === $this) {
                $visit->setServer(null);
            }
        }

        return $this;
    }

    /**
     * @return mixed
     */
    public function getLoadingUrl()
    {
        return $this->loading_url;
    }

    /**
     * @param mixed $loading_url
     */
    public function setLoadingUrl($loading_url): void
    {
        $this->loading_url = $loading_url;
    }

    /**
     * @return mixed
     */
    public function getHash()
    {
        return $this->hash;
    }

    /**
     * @param mixed $hash
     */
    public function setHash($hash): void
    {
        $this->hash = $hash;
    }

    /**
     * @return mixed
     */
    public function getActive()
    {
        return $this->active;
    }

    /**
     * @param mixed $active
     */
    public function setActive($active): void
    {
        $this->active = $active;
    }

    /**
     * @return mixed
     */
    public function getMaxOnline()
    {
        return $this->maxOnline;
    }

    /**
     * @param mixed $maxOnline
     */
    public function setMaxOnline($maxOnline): void
    {
        $this->maxOnline = $maxOnline;
    }

    /**
     * @return mixed
     */
    public function getOnline()
    {
        return $this->online;
    }

    /**
     * @param mixed $online
     */
    public function setOnline($online): void
    {
        $this->online = $online;
    }
}
