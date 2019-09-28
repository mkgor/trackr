<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Server;
use App\Entity\Visit;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class PlayerController
 * @package App\Controller
 */
class PlayerController extends AbstractController
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var PaginatorInterface
     */
    private $knp;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * PlayerController constructor.
     *
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session, PaginatorInterface $bundle, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->knp = $bundle;
        $this->em = $entityManager;
    }

    /**
     * @Route("/app/player/list", name="players")
     * @param Request $request
     * @return Response
     */
    public function index(Request $request)
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1
        ]);

        $parametersArray = [];

        $players = $this->em->createQueryBuilder()
            ->select('player')
            ->from('App:Player','player')
            ->where('player.server = :server');

        $parametersArray['server'] = $this->session->get('active_server');

        if($request->query->has('datetimes') && !empty($request->query->get('datetimes'))) {
            $players->andWhere('player.last_login >= :from');
            $players->andWhere('player.last_login <= :to');

            $datetime = explode('-', $request->query->get('datetimes'));

            $parametersArray['from'] = trim($datetime[0]);
            $parametersArray['to'] = trim($datetime[1]);
        }

        if($request->query->has('steamid')  && !empty($request->query->get('steamid'))) {
            $players->andWhere('player.steamid = :steamid');

            $parametersArray['steamid'] = trim($request->query->get('steamid'));
        }

        if($request->query->has('ip')  && !empty($request->query->get('ip'))) {

            $players->andWhere('player.register_ip = :ip');

            $parametersArray['ip'] = trim($request->query->get('ip'));
        }

        $players->setParameters($parametersArray);

        if($request->query->has('p_sort')) {
            if($request->query->get('p_sort') == 'desc' || $request->query->get('p_sort') == 'asc') {
                $sort = $request->query->get('p_sort');
            } else {
                $sort = 'desc';
            }
        } else {
            $sort = 'desc';
        }

        $players->orderBy('player.last_login', $sort);

        $pagination = $this->knp->paginate(
            $players,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        $min_date = $this->em->createQuery('SELECT player.last_login FROM App\Entity\Player player WHERE player.server = ?1 ORDER BY player.last_login ASC ')->setParameter(1, $this->session->get('active_server'))->setMaxResults(1)->getResult();
        $max_date = $this->em->createQuery('SELECT player.last_login FROM App\Entity\Player player WHERE player.server = ?1 ORDER BY player.last_login DESC')->setParameter(1, $this->session->get('active_server'))->setMaxResults(1)->getResult();

        $min_date = (!empty($min_date)) ? $min_date[0]['last_login']->format('Y-m-d H:i:s') : null;
        $max_date = (!empty($min_date)) ? $max_date[0]['last_login']->format('Y-m-d H:i:s') : null;

        $pagination = $this->knp->paginate(
            $players,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        return $this->render('player/index.html.twig', [
            'min_date' => $min_date,
            'max_date' => $max_date,
            'from' => isset($parametersArray['from']) ? str_replace('/','-', $parametersArray['from']) : $min_date,
            'to' =>  isset($parametersArray['to']) ?  str_replace('/','-', $parametersArray['to']) : $max_date,
            'steamid' => $request->query->get('steamid'),
            'ip' => $request->query->get('ip'),
            'user_info' => $this->session->all(),
            'servers' => $servers,
            'active' => 'players',
            'players' => $pagination,
            'sort' => $sort
        ]);
    }

    /**
     * @Route("/app/player/lookup/{steamid}", name="player_lookup")
     * @param $steamid
     * @return Response
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function lookup($steamid, Request $request)
    {
        $httpClient = HttpClient::create();
        $playerInfo = $httpClient->request('GET', 'https://steamcommunity.com/profiles/'. $steamid .'/?xml=1');

        try {
            $playerInfo = (new SimpleXMLElement($playerInfo->getContent()));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Wrong steamID!');

            return new RedirectResponse($this->generateUrl('players'));
        }

        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1
        ]);

        $playerRepository = $this->getDoctrine()->getRepository(Player::class);

        $players = $playerRepository->findBy([
            'steamid' => $steamid,
            'server' => $this->session->get('active_server')
        ]);

        if(empty($players)) {
            $this->addFlash('error', 'Player does not exists or dont play on specified server!');

            return new RedirectResponse($this->generateUrl('players'));
        } else {
            $trackedInfo = $players[0];
        }

        $playerServers = $playerRepository->findBy([
           'steamid' => $steamid,

        ]);

        $serversArray = [];

        foreach($playerServers as $server) {
            foreach($servers as $item) {
                if($server->getServer() == $item->getid()) {
                    $serversArray[] = $item->getName();
                }
            }
        }

        $visits = $this->em->createQueryBuilder()
            ->select('visit')
            ->from('App:Visit','visit')
            ->where('visit.server = :server', 'visit.steamid = :steamid');

        $parametersArray['server'] = $this->session->get('active_server');
        $parametersArray['steamid'] = $steamid;

        if($request->query->has('datetimes') && !empty($request->query->get('datetimes'))) {
            $visits->andWhere('visit.time >= :from');
            $visits->andWhere('visit.time <= :to');

            $datetime = explode('-', $request->query->get('datetimes'));

            $parametersArray['from'] = trim($datetime[0]);
            $parametersArray['to'] = trim($datetime[1]);
        }

        if($request->query->has('ip')  && !empty($request->query->get('ip'))) {

            $visits->andWhere('visit.ip = :ip');

            $parametersArray['ip'] = trim($request->query->get('ip'));
        }

        $visits->setParameters($parametersArray);

        if($request->query->has('p_sort')) {
            if($request->query->get('p_sort') == 'desc' || $request->query->get('p_sort') == 'asc') {
                $sort = $request->query->get('p_sort');
            } else {
                $sort = 'desc';
            }
        } else {
            $sort = 'desc';
        }

        $visits->orderBy('visit.time', $sort);

        $pagination = $this->knp->paginate(
            $visits,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        $min_date = $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.steamid = :steamid AND visit.server = ?1 ORDER BY visit.time ASC ')
            ->setParameter(1, $this->session->get('active_server'))
            ->setParameter('steamid', $steamid)
            ->setMaxResults(1)
            ->getResult();

        $max_date = $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.steamid = :steamid AND visit.server = ?1 ORDER BY visit.time DESC')
            ->setParameter(1, $this->session->get('active_server'))
            ->setParameter('steamid', $steamid)
            ->setMaxResults(1)
            ->getResult();

        $min_date = (!empty($min_date)) ? $min_date[0]['time']->format('Y-m-d H:i:s') : null;
        $max_date = (!empty($min_date)) ? $max_date[0]['time']->format('Y-m-d H:i:s') : null;


        return $this->render('player/info.html.twig', [
            'min_date' => $min_date,
            'max_date' => $max_date,
            'from' => isset($parametersArray['from']) ? str_replace('/','-', $parametersArray['from']) : $min_date,
            'to' =>  isset($parametersArray['to']) ?  str_replace('/','-', $parametersArray['to']) : $max_date,
            'user_info' => $this->session->all(),
            'servers' => $servers,
            'active' => 'lookup',
            'playerInfo' => [
                'steamid' => $steamid,
                'name' => $playerInfo->steamID,
                'avatar' => $playerInfo->avatarFull,
                'realname' => (isset($playerInfo->realname)) ? $playerInfo->realname : null,
                'location' =>(isset($playerInfo->location)) ? $playerInfo->location : null,
                'last_login' => $trackedInfo->getLastLogin(),
                'register_date' => $this->getDoctrine()->getRepository(Visit::class)->findBy(['steamid' => $steamid], ['time' => 'asc'], 1)[0]->getTime(),
                'register_ip' => $trackedInfo->getRegisterIp(),
                'last_ip' => $this->getDoctrine()->getRepository(Visit::class)->findBy(['steamid' => $steamid], ['time' => 'desc'], 1)[0]->getIp(),
                'server' => $this->getDoctrine()->getRepository(Server::class)->find($trackedInfo->getServer()),
                'serversArray' => (count($serversArray) > 1) ? $serversArray : null
            ],
            'sort' => $sort,
            'visits' => $pagination,
            'ip' => $request->query->get('ip')
        ]);
    }
}
