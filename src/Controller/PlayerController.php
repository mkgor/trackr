<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Server;
use App\Entity\Visit;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Knp\Component\Pager\PaginatorInterface;
use App\Service\FilterService;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\Exception\TransportException;
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
 *
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
     * @var
     */
    private $filter;

    /**
     * PlayerController constructor.
     *
     * @param SessionInterface       $session
     * @param PaginatorInterface     $bundle
     * @param EntityManagerInterface $entityManager
     * @param FilterService          $filter
     */
    public function __construct(SessionInterface $session, PaginatorInterface $bundle, EntityManagerInterface $entityManager, FilterService $filter)
    {
        $this->session = $session;
        $this->knp = $bundle;
        $this->em = $entityManager;
        $this->filter = $filter;
    }

    /**
     * @Route("/app/player/list", name="players")
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1,
        ]);

        $parametersArray = [];

        $players = $this->em->createQueryBuilder()
            ->select('player')
            ->from('App:Player', 'player')
            ->where('player.server = :server');

        $parametersArray['server'] = $this->session->get('active_server');

        if ($request->query->has('datetimes') && !empty($request->query->get('datetimes'))) {
            $players->andWhere('player.last_login >= :from');
            $players->andWhere('player.last_login <= :to');

            $datetime = explode('-', $request->query->get('datetimes'));

            $parametersArray['from'] = trim($datetime[0]);
            $parametersArray['to'] = trim($datetime[1]);
        }

        if ($request->query->has('steamid') && !empty($request->query->get('steamid'))) {
            $players->andWhere('player.steamid = :steamid');

            $parametersArray['steamid'] = trim($request->query->get('steamid'));
        }

        if ($request->query->has('ip') && !empty($request->query->get('ip'))) {

            $players->andWhere('player.register_ip = :ip');

            $parametersArray['ip'] = trim($request->query->get('ip'));
        }

        $players->setParameters($parametersArray);
        $players->orderBy('player.last_login', $sort = $this->filter->getSort($request));

        $pagination = $this->knp->paginate(
            $players,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        $getQuery = function ($ordering) {
            return $this->em->createQuery('SELECT player.last_login FROM App\Entity\Player player WHERE player.server = ?1 ORDER BY player.last_login ' . $ordering)
                ->setParameter(1, $this->session->get('active_server'))
                ->setMaxResults(1)
                ->getResult();
        };

        $min_date = $getQuery('ASC');
        $max_date = $getQuery('DESC');

        $dates = $this->filter->handleMinMaxDates($min_date, $max_date, 'last_login');

        $pagination = $this->knp->paginate(
            $players,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        return $this->render('player/index.html.twig', [
            'min_date'  => $dates->getMinDate(),
            'max_date'  => $dates->getMaxDate(),
            'from'      => isset($parametersArray['from']) ? str_replace('/', '-', $parametersArray['from']) : $dates->getMinDate(),
            'to'        => isset($parametersArray['to']) ? str_replace('/', '-', $parametersArray['to']) : $dates->getMaxDate(),
            'steamid'   => $request->query->get('steamid'),
            'ip'        => $request->query->get('ip'),
            'user_info' => $this->session->all(),
            'servers'   => $servers,
            'active'    => 'players',
            'players'   => $pagination,
            'sort'      => $sort,
        ]);
    }

    /**
     * @Route("/app/player/lookup/{steamid}", name="player_lookup")
     * @param $steamid
     *
     * @return Response
     * @throws TransportExceptionInterface
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function lookup($steamid = null, Request $request)
    {
        $httpClient = HttpClient::create();

        try {
            $playerInfo = $httpClient->request('GET', 'https://steamcommunity.com/profiles/' . $steamid . '/?xml=1', ['timeout' => 5]);

            if($playerInfo->getStatusCode() == 503) {
                $this->addFlash('error', 'Steam API is unavailable now.');

                return new RedirectResponse($this->generateUrl('players'));
            } else {
                try {
                    $playerInfo = (new SimpleXMLElement($playerInfo->getContent()));
                } catch (Exception $e) {
                    $this->addFlash('error', 'Wrong steamID');

                    return new RedirectResponse($this->generateUrl('players'));
                }
            }

        } catch (TransportException $e) {
            $this->addFlash('error', 'Steam API connection timeout (maybe servers are down or overloaded)');

            return new RedirectResponse($this->generateUrl('players'));
        }

        $serverRepository = $this->getDoctrine()->getRepository(Server::class);
        $playerRepository = $this->getDoctrine()->getRepository(Player::class);
        $visitRepository = $this->getDoctrine()->getRepository(Visit::class);

        $servers = $serverRepository->findBy([
            'active' => 1,
        ]);


        $players = $playerRepository->findBy([
            'steamid' => $steamid,
        ]);

        if (empty($players)) {
            $this->addFlash('error', 'Player does not exists or dont play on specified server!');

            return new RedirectResponse($this->generateUrl('players'));
        } else {
            $trackedInfo = $players[0];
        }

        if($trackedInfo->getName() != $playerInfo->steamID) {
            $trackedInfo->setName($playerInfo->steamID);

            $this->em->persist($trackedInfo);
            $this->em->flush();
        }

        $playerServers = $playerRepository->findBy([
            'steamid' => $steamid,
        ]);

        $serversArray = [];

        foreach ($playerServers as $server) {
            foreach ($servers as $item) {
                if ($server->getServer() == $item->getid()) {
                    $serversArray[] = $item->getName();
                }
            }
        }

        $visits = $this->em->createQueryBuilder()
            ->select('visit')
            ->from('App:Visit', 'visit')
            ->where('visit.server = :server', 'visit.steamid = :steamid');

        $parametersArray['server'] = $this->session->get('active_server');
        $parametersArray['steamid'] = $steamid;

        if ($request->query->has('datetimes') && !empty($request->query->get('datetimes'))) {
            $visits->andWhere('visit.time >= :from');
            $visits->andWhere('visit.time <= :to');

            $datetime = explode('-', $request->query->get('datetimes'));

            $parametersArray['from'] = trim($datetime[0]);
            $parametersArray['to'] = trim($datetime[1]);
        }

        if ($request->query->has('ip') && !empty($request->query->get('ip'))) {
            $visits->andWhere('visit.ip = :ip');

            $parametersArray['ip'] = trim($request->query->get('ip'));
        }

        $visits->setParameters($parametersArray);
        $visits->orderBy('visit.time', $sort = $this->filter->getSort($request));

        $pagination = $this->knp->paginate(
            $visits,
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

        $getQuery = function ($ordering) use ($steamid) {
            return $this->em->createQuery('SELECT visit.time FROM App\Entity\Visit visit WHERE visit.steamid = :steamid AND visit.server = ?1 ORDER BY visit.time ' . $ordering)
                ->setParameter(1, $this->session->get('active_server'))
                ->setParameter('steamid', $steamid)
                ->setMaxResults(1)
                ->getResult();
        };

        $min_date = $getQuery('ASC');
        $max_date = $getQuery('DESC');

        $dates = $this->filter->handleMinMaxDates($min_date, $max_date, 'time');

        return $this->render('player/info.html.twig', [
            'min_date'   => $dates->getMinDate(),
            'max_date'   => $dates->getMaxDate(),
            'from'       => isset($parametersArray['from']) ? str_replace('/', '-', $parametersArray['from']) : $dates->getMinDate(),
            'to'         => isset($parametersArray['to']) ? str_replace('/', '-', $parametersArray['to']) : $dates->getMaxDate(),
            'user_info'  => $this->session->all(),
            'servers'    => $servers,
            'active'     => 'lookup',
            'playerInfo' => [
                'steamid'       => $steamid,
                'name'          => $playerInfo->steamID,
                'avatar'        => $playerInfo->avatarFull,
                'realname'      => (isset($playerInfo->realname)) ? $playerInfo->realname : null,
                'location'      => (isset($playerInfo->location)) ? $playerInfo->location : null,
                'last_login'    => $trackedInfo->getLastLogin(),
                'register_date' => $visitRepository->findBy(['steamid' => $steamid], ['time' => 'asc'], 1)[0]->getTime(),
                'register_ip'   => $trackedInfo->getRegisterIp(),
                'last_ip'       => $visitRepository->findBy(['steamid' => $steamid], ['time' => 'desc'], 1)[0]->getIp(),
                'server'        => $serverRepository->find($trackedInfo->getServer()),
                'serversArray'  => (count($serversArray) > 1) ? $serversArray : null,
            ],
            'sort'       => $sort,
            'visits'     => $pagination,
            'ip'         => $request->query->get('ip'),
        ]);
    }

    /**
     * @Route("/app/player/search", name="player_search")
     * @param Request $request
     *
     * @return Response
     */
    public function searchSteamId(Request $request)
    {
        $player = $this->em->getRepository(Player::class)->findBy([
            'name' => $request->query->get('name')
        ]);

        if(isset($player[0]) && !empty($player[0]->getSteamid())) {
            $response = json_encode(['found' => 'true', 'steamid' => $player[0]->getSteamid()]);
        } else {
            $response = json_encode(['found' => 'false']);
        }

        return new Response($response, 200, [
            'Content-type' => 'application/json'
        ]);
    }
}
