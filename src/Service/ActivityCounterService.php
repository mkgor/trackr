<?php

namespace App\Service;

use App\Entity\Visit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class ActivityCounterService
 * @package App\Service
 */
class ActivityCounterService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var \App\Repository\VisitRepository|\Doctrine\Common\Persistence\ObjectRepository
     */
    private $visitsRepository;

    /**
     * @var int|null
     */
    private $totalToday;

    /**
     * @var int|null
     */
    private $uniqueToday;

    /**
     * @var int|null
     */
    private $newToday;

    /**
     * @var int
     */
    private $activeServer;

    /**
     * ActivityCounterService constructor.
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     */
    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->em = $entityManager;
        $this->visitsRepository = $this->em->getRepository(Visit::class);
        $this->activeServer = $session->get('active_server');
    }

    /**
     * @return int
     */
    public function getTotal()
    {
        $visits = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.server = ?1 AND visit.time >= CURRENT_DATE()')->setParameter(1, $this->activeServer);
        $visits = $visits->getResult();

        return $this->totalToday = count($visits);
    }

    /**
     * @return float|int|string
     */
    public function getTotalDifference()
    {
        if ($this->totalToday == null) {
            $this->getTotal();
        }

        $visits = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.server = ?1 AND visit.time >= (CURRENT_DATE() - 1) AND visit.time < CURRENT_DATE()')->setParameter(1, $this->activeServer);
        $visits = $visits->getResult();

        $yesterdayVisitsCount = count($visits);

        return $this->getDifference($yesterdayVisitsCount, $this->totalToday);
    }

    /**
     * @return int
     */
    public function getUnique()
    {
        $visits = $this->em->createQuery('SELECT visit.steamid FROM App\Entity\Visit visit WHERE visit.server = ?1 AND visit.player_unique = 1 AND visit.time >= CURRENT_DATE()')->setParameter(1, $this->activeServer);
        $visits = $visits->getResult();

        return $this->uniqueToday = count($visits);
    }

    /**
     * @return float|int|string
     */
    public function getUniqueDifference()
    {
        if ($this->uniqueToday == null) {
            $this->getUnique();
        }

        $visits = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.server = ?1 AND visit.player_unique = 1 AND visit.time >= (CURRENT_DATE() - 1) AND visit.time < CURRENT_DATE()')->setParameter(1, $this->activeServer);
        $visits = $visits->getResult();

        $yesterdayVisitsCount = count($visits);

        return $this->getDifference($yesterdayVisitsCount, $this->uniqueToday);
    }

    public function getNew()
    {
        $visits = $this->em->createQuery('SELECT visit.steamid FROM App\Entity\Visit visit WHERE visit.server = ?1 AND visit.player_new = 1 AND visit.time >= CURRENT_DATE()')->setParameter(1, $this->activeServer);
        $visits = $visits->getResult();

        return $this->newToday = count($visits);
    }

    public function getNewDifference()
    {
        if ($this->newToday == null) {
            $this->getNew();
        }

        $visits = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.server = ?1 AND visit.player_new = 1 AND visit.time >= (CURRENT_DATE() - 1) AND visit.time < CURRENT_DATE()')->setParameter(1, $this->activeServer);
        $visits = $visits->getResult();

        $yesterdayVisitsCount = count($visits);

        return $this->getDifference($yesterdayVisitsCount, $this->newToday);
    }

    /**
     * Returns the difference in percentage between two numbers
     *
     * @param int $num1
     * @param int $num2
     *
     * @return float|int|string
     */
    private function getDifference($num1, $num2)
    {
        if ($num1 != 0) {
            if ($num1 < $num2) {
                $result = (($num2 - $num1) / $num1) * 100;
            } else {
                $result = (($num1 - $num2) / $num1) * 100 * (-1);
            }
        } else {
            $result = "∞";
        }

        return $result;
    }
}