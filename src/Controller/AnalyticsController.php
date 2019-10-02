<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Visit;
use App\Service\AnalyticsService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use xPaw\SourceQuery\Exception\InvalidPacketException;
use xPaw\SourceQuery\SourceQuery;

/**
 * Class AnalyticsController
 *
 * @package App\Controller
 */
class AnalyticsController extends AbstractController
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var AnalyticsService
     */
    private $analytics;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * AnalyticsController constructor.
     *
     * @param SessionInterface       $session
     * @param AnalyticsService       $analytics
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(SessionInterface $session, AnalyticsService $analytics, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->analytics = $analytics;
        $this->em = $entityManager;
    }

    /**
     * @Route("/app/analytics", name="analytics")
     *
     * @throws Exception
     */
    public function index()
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1,
        ]);

        $availableYears = $this->analytics->getAvailableYears();

        $peak = $this->analytics->getPeak();

        $peakTimes = $this->em->createQuery(/** @lang DQL */ "SELECT visit.time FROM App\Entity\Visit visit WHERE YEAR(visit.time) = YEAR(CURRENT_DATE())")->getResult();

        $totaltime = 0;

        foreach($peakTimes as $time){
            $timestamp = strtotime($time['time']->format('H:i:s'));
            $totaltime += $timestamp;
        }


        return $this->render('analytics/index.html.twig', [
            'servers'   => $servers,
            'user_info' => $this->session->all(),
            'active'    => 'analytics',
            'months'    => $this->analytics->months,
            'years'     => $availableYears,
            'peak_visit' => $peak,
            'average_peak_time' => (empty($peakTimes)) ? '00:00' : date('H:i', $totaltime/count($peakTimes))
        ]);
    }

    /**
     * @Route("/app/analytics/api", name="analytics_api")
     * @param Request $request
     *
     * @return Response
     */
    public function getAnalyticsData(Request $request)
    {
        $code = 200;

        if ($request->query->has('year') && !$request->query->has('month') || empty($request->query->get('month')) || $request->query->get('month') == 0) {
            $response = json_encode($this->analytics->getDataByYear($request->query->get('year')));
        } else if ($request->query->has('year') && $request->query->has('month')) {
            $response = json_encode($this->analytics->getDataByMonth($request->query->get('year'), $request->query->get('month')));
        } else {
            $response = json_encode(['error' => 'Bad request']);
            $code = 400;
        }

        return new Response($response, $code, [
            'Content-type' => 'application/json',
        ]);
    }

    /**
     * @Route("/app/analytics/api_peaks", name="analytics_peaks_api")
     * @param Request $request
     *
     * @return Response
     */
    public function getPeaksData(Request $request)
    {
        $code = 200;

        if ($request->query->has('year') && !$request->query->has('month') || empty($request->query->get('month')) || $request->query->get('month') == 0) {
            $response = json_encode($this->analytics->getPeaksByYear($request->query->get('year')));
        } else if ($request->query->has('year') && $request->query->has('month')) {
            $response = json_encode($this->analytics->getPeaksByMonth($request->query->get('year'), $request->query->get('month')));
        } else {
            $response = json_encode(['error' => 'Bad request']);
            $code = 400;
        }

        return new Response($response, $code, [
            'Content-type' => 'application/json',
        ]);
    }
}
