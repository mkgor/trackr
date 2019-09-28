<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Server;
use App\Entity\Visit;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/gate/{id}", name="gate")
     * @param $id
     *
     * @return Response
     */
    public function index($id)
    {
        $server = $this->getDoctrine()->getRepository(Server::class);

        $specifiedServer = $server->findBy([
            'hash'   => $id,
            'active' => 1,
        ]);

        if (!empty($specifiedServer[0])) {
            $specifiedServer = $specifiedServer[0];

            return $this->render('gate/index.html.twig', [
                'loading_link' => $specifiedServer->getLoadingUrl(),
                'server_id'    => $specifiedServer->getId(),
            ]);
        } else {
            return $this->render('gate/error.html.twig');
        }
    }

    /**
     * @Route("/gate/visit/handle", name="api_handle_visit")
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function handleVisit(Request $request)
    {
        $requestData = $request->request->all();
        $doctrine = $this->getDoctrine();

        $visit = new Visit();
        $visit->setSteamid($requestData['steamid']);
        $visit->setServer($doctrine->getRepository(Server::class)->find($requestData['server_id']));
        $visit->setIp($request->getClientIp());

        $checkForUnique = $this->em->createQuery('SELECT visit FROM App\Entity\Visit visit WHERE visit.steamid = ?1 AND visit.server = ?2 AND visit.time >= CURRENT_DATE()')
            ->setParameter(1, $requestData['steamid'])
            ->setParameter(2, $requestData['server_id'])
            ->getResult();

        if (empty($checkForUnique)) {
            $visit->setUnique(true);
        }

        $checkForNew = $doctrine->getRepository(Visit::class)->findBy([
            'server'  => $requestData['server_id'],
            'steamid' => $requestData['steamid'],
        ]);

        if (empty($checkForNew)) {
            $visit->setNew(true);

            $player = new Player();
            $player->setSteamid($requestData['steamid']);
            $player->setLastLogin(new DateTime());
            $player->setRegisterIp($request->getClientIp());
            $player->setServer($requestData['server_id']);

            $this->em->persist($player);
            $this->em->flush();
        } else {
            $player = $doctrine->getRepository(Player::class)->findBy([
                'steamid' => $requestData['steamid'],
            ]);

            $player[0]->setLastLogin(new DateTime());
            $this->em->persist($player[0]);
            $this->em->flush();
        }

        $this->em->persist($visit);
        $this->em->flush();

        return new Response();
    }
}
