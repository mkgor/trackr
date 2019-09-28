<?php

namespace App\Service\Entity;

/**
 * Class Dates
 *
 * @package App\Service\Entity
 */
class Dates
{
    /**
     * @var string
     */
    private $maxDate;

    /**
     * Dates constructor.
     *
     * @param string $maxDate
     * @param string $minDate
     */
    public function __construct(string $maxDate = null, string $minDate = null)
    {
        $this->maxDate = $maxDate;
        $this->minDate = $minDate;
    }

    /**
     * @return string
     */
    public function getMaxDate(): ?string
    {
        return $this->maxDate;
    }

    /**
     * @param string $maxDate
     */
    public function setMaxDate(string $maxDate): void
    {
        $this->maxDate = $maxDate;
    }

    /**
     * @return string
     */
    public function getMinDate(): ?string
    {
        return $this->minDate;
    }

    /**
     * @param string $minDate
     */
    public function setMinDate(string $minDate): void
    {
        $this->minDate = $minDate;
    }

    /**
     * @var string
     */
    private $minDate;
}