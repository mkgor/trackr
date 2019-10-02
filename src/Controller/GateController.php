<?php

namespace App\Controller;

use App\Entity\Configuration;
use App\Entity\Player;
use App\Entity\Server;
use App\Entity\Visit;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Class GateController
 *
 * @package App\Controller
 */
class GateController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * GateController constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->em = $entityManager;
    }

    /**
     * @Route("/gate/entrypoint", name="server_entrypoint")
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function serverEntryPoint(Request $request)
    {
        $code = 200;

        if ($request->request->has('key')) {
            $doctrine = $this->getDoctrine();
            $requestData = $request->request->all();

            file_put_contents(__DIR__ . '/../Resources/' . $request->request->get('key') . '.json', $requestData['players']);

            $requestData['serverInfo'] = json_decode($requestData['serverInfo'], true);

            $server = $doctrine->getRepository(Server::class);

            $specifiedServer = $server->findBy([
                'hash'   => $request->request->get('key'),
                'active' => 1,
            ]);

            if (!empty($specifiedServer[0])) {
                $specifiedServer = $specifiedServer[0];

                $specifiedServer->setOnline($requestData['serverInfo']['players']);
                $specifiedServer->setMaxOnline($requestData['serverInfo']['maxplayers']);

                if ($request->request->get('type') == 'connect') {
                    $requestData['player'] = json_decode($requestData['player'], true);

                    if($requestData['player']['bot']) {
                        $this->em->persist($specifiedServer);
                        $this->em->flush();
                        return new Response('Bot visit detected, processing stopped.');
                    }

                    $visit = new Visit();
                    $visit->setSteamid($requestData['player']['steamid']);
                    $visit->setServer($doctrine->getRepository(Server::class)->find($specifiedServer->getId()));
                    $visit->setIp($requestData['player']['ip']);

                    $checkForUnique = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.steamid = ?1 AND visit.server = ?2 AND visit.time >= CURRENT_DATE()')
                        ->setParameter(1, $requestData['player']['steamid'])
                        ->setParameter(2, $specifiedServer->getId())
                        ->getResult();

                    if (empty($checkForUnique)) {
                        $visit->setUnique(true);
                    }

                    $checkForNew = $doctrine->getRepository(Visit::class)->findBy([
                        'server'  => $specifiedServer->getId(),
                        'steamid' => $requestData['player']['steamid'],
                    ]);

                    if (empty($checkForNew)) {
                        $visit->setNew(true);

                        $player = new Player();
                        $player->setSteamid($requestData['player']['steamid']);
                        $player->setLastLogin(new DateTime());
                        $player->setRegisterIp($requestData['player']['ip']);
                        $player->setServer($specifiedServer->getId());
                        $player->setName($requestData['player']['name']);

                        $this->em->persist($player);
                    } else {
                        $player = $doctrine->getRepository(Player::class)->findBy([
                            'steamid' => $requestData['player']['steamid'],
                        ]);

                        $player[0]->setLastLogin(new DateTime());

                        $this->em->persist($player[0]);
                    }

                    $visit->setOnline($requestData['serverInfo']['players']);

                    $this->em->persist($visit);
                    $this->em->persist($specifiedServer);

                    $response = 'Visit tracked successfully';

                } else if ($request->request->get('type') == 'disconnect') {
                    $requestData['player'] = json_decode($requestData['player'], true);

                    $player = $doctrine->getRepository(Player::class)->findBy([
                        'steamid' => $requestData['player']['steamid'],
                    ]);

                    $player[0]->setLastLogin(new DateTime());

                    $this->em->persist($player[0]);
                } else if ($request->request->get('type') == 'init') {
                    $response = "Trackr module initialized";
                } else {
                    $response = "Wrong type!";
                    $code = 400;
                }
            } else {
                $response = 'Server with specified key not found!';
                $code = 401;
            }
        } else {
            $response = 'API key is not specified!';
            $code = 403;
        }

        $this->em->flush();

        return new Response(json_encode($response), $code);
    }
}
