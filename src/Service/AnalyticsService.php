<?php

namespace App\Service;

use App\Entity\Visit;
use App\Repository\VisitRepository;
use DateTime;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Exception;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Class AnalyticsService
 *
 * @package App\Service
 */
class AnalyticsService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var VisitRepository|ObjectRepository
     */
    private $visitRepository;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var array
     */
    public $months;

    /**
     * AnalyticsService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface       $session
     */
    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session)
    {
        $this->em = $entityManager;
        $this->visitRepository = $this->em->getRepository(Visit::class);
        $this->session = $session;

        $this->months = [
            'January',
            'February',
            'March',
            'April',
            'May',
            'June',
            'July',
            'August',
            'September',
            'October',
            'November',
            'December',
        ];
    }

    /**
     * @param int $year
     *
     * @return array
     */
    public function getDataByYear(int $year = null): array
    {
        if($year == null) {
            $year = date('Y');
        }

        $labels = $this->months;

        if ($this->isValidYear($year)) {
            $queries = [
                'total'  => /** @lang DQL */
                    "SELECT MONTHNAME(visit.time) AS month, COUNT(visit) AS quantity FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 GROUP BY month",
                'unique' => /** @lang DQL */
                    "SELECT MONTHNAME(visit.time) AS month, COUNT(visit) AS quantity FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 AND visit.player_unique = 1 GROUP BY month",
                'new'    => /** @lang DQL */
                    "SELECT MONTHNAME(visit.time) AS month, COUNT(visit) AS quantity FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 AND visit.player_new = 1 GROUP BY month",
            ];

            $tmp = [];

            foreach ($queries as $key => $value) {
                $tmp[$key] = $this->em->createQuery($value)
                    ->setParameters([
                        $this->session->get('active_server'),
                        $year,
                    ])->getArrayResult();
            }


            $_tmp = [];

            foreach ($tmp as $key => $value) {
                foreach ($value as $item) {
                    $_tmp[$key][$item['month']] = (int)$item['quantity'];
                }

                if(empty($_tmp[$key])) {
                    $_tmp[$key][] = [];
                }
            }

            $data = [];

            foreach ($_tmp as $type => $values) {
                $labelDisc = $labels;

                foreach ($labelDisc as $label) {
                    if (!isset($_tmp[$type][$label])) {
                        $data[$type][$label] = 0;
                    } else {
                        $data[$type][$label] = $_tmp[$type][$label];
                    }
                }
            }

            $result = [];

            foreach ($data as $type => $values) {
                foreach ($values as $key => $value) {
                    $result[$type][] = $value;
                }
            }

            return [
                'labels' => $labels,
                'data'   => [
                    'total'  => $result['total'],
                    'unique' => $result['unique'],
                    'new'    => $result['new'],
                ],
            ];
        } else {
            return [
                'error' => 'Wrong year specified',
            ];
        }
    }

    /**
     * @param int $year
     * @param int $month
     *
     * @return array
     */
    public function getDataByMonth($year = null, $month = null): array
    {
        if($year == null) {
            $year = date('Y');
        }

        if($month == null) {
            $month = date('F');
        }

        if ($this->isValidYear($year) && $month >= 1 && $month <= 12) {
            $labels = [];

            for ($i = 1; $i <= $this->daysInMonth($month, $year); $i++) {
                $labels[] = $i;
            }

            $queries = [
                'total'  => /** @lang DQL */
                    "SELECT DAY(visit.time) AS day, COUNT(visit) AS quantity FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 AND MONTH(visit.time) = ?2 GROUP BY day",
                'unique' => /** @lang DQL */
                    "SELECT DAY(visit.time) AS day, COUNT(visit) AS quantity FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 AND MONTH(visit.time) = ?2 AND visit.player_unique = 1 GROUP BY day",
                'new'    => /** @lang DQL */
                    "SELECT DAY(visit.time) AS day, COUNT(visit) AS quantity FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 AND MONTH(visit.time) = ?2 AND visit.player_new = 1 GROUP BY day",
            ];

            $tmp = [];

            foreach ($queries as $key => $value) {
                $tmp[$key] = $this->em->createQuery($value)
                    ->setParameters([
                        $this->session->get('active_server'),
                        $year,
                        $month,
                    ])->getArrayResult();
            }

            $_tmp = [];

            foreach ($tmp as $key => $value) {
                foreach ($value as $item) {
                    $_tmp[$key][$item['day']] = (int)$item['quantity'];
                }

                if(empty($_tmp[$key])) {
                    $_tmp[$key][] = [];
                }
            }

            $data = [];

            foreach ($_tmp as $type => $values) {
                $labelDisc = $labels;

                foreach ($labelDisc as $label) {
                    if (!isset($_tmp[$type][$label])) {
                        $data[$type][$label] = 0;
                    } else {
                        $data[$type][$label] = $_tmp[$type][$label];
                    }
                }
            }
            $result = [];

            foreach ($data as $type => $values) {
                foreach ($values as $key => $value) {
                    $result[$type][] = $value;
                }
            }


            return [
                'labels' => $labels,
                'data'   => [
                    'total'  => $result['total'],
                    'unique' => $result['unique'],
                    'new'    => $result['new'],
                ],
            ];
        } else {
            return [
                'error' => 'Wrong date specified',
            ];
        }
    }

    /**
     * @param null $date
     *
     * @return mixed
     * @throws Exception
     */
    public function getPeak($date = null)
    {
        if($date == null) {
            $date = new DateTime();
        }

        $dql = /** @lang DQL */ "SELECT visit FROM App\Entity\Visit visit WHERE visit.server = ?1 AND DATE(visit.time) = :date ORDER BY visit.online DESC";

        $peak = $this->em->createQuery($dql)->setParameters([
            1 => $this->session->get('active_server'),
            'date' => $date->format('Y-m-d')
        ])->setMaxResults(1)->getResult();

        return (!empty($peak)) ? $peak[0] : 0;
    }

    public function getPeaksByYear($year = null)
    {
        if($year == null) {
            $year = date('Y');
        }

        if ($this->isValidYear($year)) {
            $labels = $this->months;

            $dql = /** @lang DQL */ "SELECT MONTHNAME(visit.time) AS month, MAX(visit.online) as peak FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 GROUP BY month";

            $query = $this->em->createQuery($dql)
                ->setParameters([
                    $this->session->get('active_server'),
                    $year,
                ])->getArrayResult();


            $tmp = [];

            foreach($query as $peak) {
                $tmp[$peak['month']] = $peak['peak'];
            }

            $data = [];

            foreach($labels as $label) {
                if(isset($tmp[$label])) {
                    $data[] = $tmp[$label];
                } else {
                    $data[] = 0;
                }
            }

            return [
                'labels' => $labels,
                'data'   => $data,
            ];
        } else {
            return [
                'error' => 'Wrong date specified',
            ];
        }
    }

    public function getPeaksByMonth($year = null, $month = null)
    {
        if($year == null) {
            $year = date('Y');
        }

        if($month == null) {
            $month = date('m');
        }

        if ($this->isValidYear($year) && $month >= 1 && $month <= 12) {
            $labels = [];

            for ($i = 1; $i <= $this->daysInMonth($month, $year); $i++) {
                $labels[] = $i;
            }

            $dql = /** @lang DQL */ "SELECT DAY(visit.time) AS day, MAX(visit.online) as peak FROM App\Entity\Visit visit WHERE visit.server = ?0 AND YEAR(visit.time) = ?1 AND MONTH(visit.time) = ?2 GROUP BY day";

            $query = $this->em->createQuery($dql)
                ->setParameters([
                    $this->session->get('active_server'),
                    $year,
                    $month,
                ])->getArrayResult();

            $tmp = [];

            foreach($query as $peak) {
                $tmp[$peak['day']] = $peak['peak'];
            }

            $data = [];

            for ($i = 1; $i <= $this->daysInMonth($month, $year); $i++) {
                if(isset($tmp[$i])) {
                    $data[] = $tmp[$i];
                } else {
                    $data[] = 0;
                }
            }

            return [
                'labels' => $labels,
                'data'   => $data,
            ];
        } else {
            return [
                'error' => 'Wrong date specified',
            ];
        }
    }

    /**
     * @return array
     */
    public function getAvailableYears()
    {
        $visits = $this->em->createQuery(/** @lang DQL */'SELECT DISTINCT YEAR(visit.time) FROM App\Entity\Visit visit WHERE visit.server = ?1')
            ->setParameter(1, $this->session->get('active_server'))
            ->getResult();

        $yearArray = [];

        foreach($visits as $visit) {
            $yearArray[] = $visit[1];
        }

        return (empty($yearArray)) ? [date('Y')] : $yearArray;
    }

    private function daysInMonth($month, $year)
    {
        return $month == 2 ? ($year % 4 ? 28 : ($year % 100 ? 29 : ($year % 400 ? 28 : 29))) : (($month - 1) % 7 % 2 ? 30 : 31);
    }

    /**
     * @param int $year
     *
     * @return bool
     */
    private function isValidYear(int $year)
    {
        return ($year > 2004 && $year < 2030);
    }
}