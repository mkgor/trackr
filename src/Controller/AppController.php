<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Visit;
use App\Service\ActivityCounterService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\KnpPaginatorBundle;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class AppController
 * @package App\Controller
 */
class AppController extends AbstractController
{

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var ActivityCounterService
     */
    private $counter;

    /**
     * @var KnpPaginatorBundle
     */
    private $knp;

    /**
     * @var
     */
    private $em;

    /**
     * AppController constructor.
     * @param SessionInterface $session
     * @param ActivityCounterService $activityCounterService
     * @param PaginatorInterface $bundle
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(SessionInterface $session, ActivityCounterService $activityCounterService, PaginatorInterface $bundle, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->counter = $activityCounterService;
        $this->knp = $bundle;
        $this->em = $entityManager;
    }

    /**
     * @Route("/app", name="app")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1
        ]);

        $visits = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.server = ?1 ORDER BY visit.time DESC')
            ->setParameter(1, $this->session->get('active_server'));

        $pagination = $this->knp->paginate(
            $visits,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        $min_date = $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.server = ?1 ORDER BY visit.time ASC ')->setParameter(1, $this->session->get('active_server'))->setMaxResults(1)->getResult();
        $max_date = $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.server = ?1 ORDER BY visit.time DESC')->setParameter(1, $this->session->get('active_server'))->setMaxResults(1)->getResult();

        return $this->render('app/index.html.twig', [
            'min_date' => (!empty($min_date)) ? $min_date[0]['time']->format('Y-m-d H:i:s') : null,
            'max_date' => (!empty($min_date)) ? $max_date[0]['time']->format('Y-m-d H:i:s') : null,
            'user_info' => $this->session->all(),
            'servers' => $servers,
            'active' => 'dashboard',
            'visits' => [
                'total' => $this->counter->getTotal(),
                'totalDifference' => $this->counter->getTotalDifference(),
                'unique' => $this->counter->getUnique(),
                'uniqueDifference' => $this->counter->getUniqueDifference(),
                'new' => $this->counter->getNew(),
                'newDifference' => $this->counter->getNewDifference(),
                'list' => $pagination
            ]
        ]);
    }

    /**
     * @Route("/app/filter",name="app_filter")
     * @param Request $request
     * @return Response
     */
    public function filter(Request $request)
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1
        ]);

        $parametersArray = [];

        $visits = $this->em->createQueryBuilder()
            ->select('visit')
            ->from('App:Visit','visit')
            ->where('visit.server = :server');

        $parametersArray['server'] = $this->session->get('active_server');

        if($request->query->has('datetimes') && !empty($request->query->get('datetimes'))) {
            $visits->andWhere('visit.time >= :from');
            $visits->andWhere('visit.time <= :to');

            $datetime = explode('-', $request->query->get('datetimes'));

            $parametersArray['from'] = trim($datetime[0]);
            $parametersArray['to'] = trim($datetime[1]);
        }

        if($request->query->has('steamid')  && !empty($request->query->get('steamid'))) {
            $visits->andWhere('visit.steamid = :steamid');

            $parametersArray['steamid'] = trim($request->query->get('steamid'));
        }

        if($request->query->has('ip')  && !empty($request->query->get('ip'))) {

            $visits->andWhere('visit.ip = :ip');

            $parametersArray['ip'] = trim($request->query->get('ip'));
        }

        $visits->setParameters($parametersArray);
        $visits->orderBy('visit.time', 'desc');

        $pagination = $this->knp->paginate(
            $visits,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        $min_date = $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.server = ?1 ORDER BY visit.time ASC ')->setParameter(1, $this->session->get('active_server'))->setMaxResults(1)->getResult();
        $max_date = $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.server = ?1 ORDER BY visit.time DESC')->setParameter(1, $this->session->get('active_server'))->setMaxResults(1)->getResult();

        $min_date = (!empty($min_date)) ? $min_date[0]['time']->format('Y-m-d H:i:s') : null;
        $max_date = (!empty($min_date)) ? $max_date[0]['time']->format('Y-m-d H:i:s') : null;

        return $this->render('app/filter.html.twig', [
            'min_date' => $min_date,
            'max_date' => $max_date,
            'user_info' => $this->session->all(),
            'servers' => $servers,
            'active' => 'filter',
            'from' => isset($parametersArray['from']) ? str_replace('/','-', $parametersArray['from']) : $min_date,
            'to' =>  isset($parametersArray['to']) ?  str_replace('/','-', $parametersArray['to']) : $max_date,
            'steamid' => $request->query->get('steamid'),
            'ip' => $request->query->get('ip'),
            'visits' => [
                'list' => $pagination
            ]
        ]);
    }

    /**
     * @Route("/app/dump_session")
     */
    public function checkSession()
    {
        var_dump($this->session->all());

        return new Response();
    }
}
