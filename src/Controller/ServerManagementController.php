<?php

namespace App\Controller;

use App\Entity\Player;
use App\Entity\Server;
use App\Entity\Visit;
use App\Service\ServerInfoService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ServerManagementController
 *
 * @package App\Controller
 */
class ServerManagementController extends AbstractController
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var ServerInfoService
     */
    private $info;

    /**
     * ServerManagementController constructor.
     *
     * @param SessionInterface       $session
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(SessionInterface $session, EntityManagerInterface $entityManager, ServerInfoService $infoService)
    {
        $this->session = $session;
        $this->em = $entityManager;
        $this->info = $infoService;
    }

    /**
     * @Route("/app/server/management", name="server_management")
     */
    public function index()
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1,
        ]);

        return $this->render('server_management/index.html.twig', [
            'servers'   => $servers,
            'user_info' => $this->session->all(),
            'active'    => 'servers',
        ]);
    }

    /**
     * @Route("/app/server/add", name="server_add")
     * @param Request $request
     *
     * @return RedirectResponse
     * @throws Exception
     */
    public function addServer(Request $request)
    {
        if (!empty($request->request->get('server_name')) && !empty($request->request->get('loading_url'))) {
            $em = $this->getDoctrine()->getManager();
            $hash = substr(md5(random_bytes(16)), 0, 16);

            $server = new Server();
            $server->setName($request->request->get('server_name'));
            $server->setIp($request->request->get('server_ip'));
            $server->setHash($hash);
            $server->setLoadingUrl($request->request->get('loading_url'));
            $server->setActive(1);

            $em->persist($server);
            $em->flush();

            $this->addFlash('success', 'Server was added!');
        } else {
            $this->addFlash('error', 'Missed required data!');

        }

        return new RedirectResponse($this->generateUrl('server_management'));
    }

    /**
     * @Route("/app/server/set", name="server_set")
     * @param Request $request
     *
     * @return RedirectResponse|Response
     */
    public function setServer(Request $request)
    {
        if ($request->query->has('id') && $request->query->has('backurl')) {
            $server = $this->getDoctrine()->getRepository(Server::class);
            $serverEntity = $server->find($request->query->get('id'));

            if (!empty($serverEntity)) {
                $this->session->set('active_server', $serverEntity->getId());
            }

            return new RedirectResponse($request->query->get('backurl'));
        } else {
            return new Response('Bad request. Server <b>id</b> and <b>backurl</b> needed', 400);
        }
    }

    /**
     * @Route("/app/server/delete/{id}", name="server_delete")
     * @param int $id
     *
     * @return RedirectResponse
     */
    public function deleteServer($id)
    {
        $server = $this->em->getRepository(Server::class)->find($id);
        if (!empty($server)) {
            $visit = $this->em->getRepository(Visit::class)->findBy([
                'server' => $server->getId(),
            ]);

            if (empty($visit)) {
                $this->em->remove($server);
            } else {
                $server->setActive(0);
                $this->em->persist($server);
            }

            $this->em->flush();

            $this->addFlash('success', 'Server was deleted!');
        } else {
            $this->addFlash('error', 'Server with specified id not found!');
        }

        return new RedirectResponse($this->generateUrl('server_management'));
    }

    /**
     * @Route("/app/server/edit/{id}", name="server_edit")
     * @param int     $id
     * @param Request $request
     *
     * @return RedirectResponse
     */
    public function editServer($id, Request $request)
    {
        if ($request->request->has('server_name') && $request->request->has('loading_url')) {
            $server = $this->em->getRepository(Server::class)->find($id);

            $server->setName($request->request->get('server_name'));
            $server->setIp($request->request->get('server_ip'));
            $server->setLoadingUrl($request->request->get('loading_url'));

            $this->em->persist($server);
            $this->em->flush();

            $this->addFlash('success', 'Server was edited!');
        } else {
            $this->addFlash('error', 'Missed required data!');
        }

        return new RedirectResponse($this->generateUrl('server_management'));
    }


    /**
     * @Route("/api/server/info", name="server_info")
     * @param Request $request
     *
     * @return Response
     */
    public function getServerInfo(Request $request)
    {
        if ($request->query->has('id')) {
            $data = $this->em->getRepository(Server::class)->find($request->query->get('id'));

            $response = json_encode([
                'online' => $data->getOnline(),
                'maxonline' => $data->getMaxOnline()
            ], JSON_UNESCAPED_UNICODE);
            $code = 200;
        } else {
            $response = json_encode(['error' => 'Bad request. Server id is not specified']);
            $code = 400;
        }

        return new Response($response, $code, [
            'Content-type' => 'application/json',
        ]);
    }

    /**
     * @Route("/app/server/players", name="server_players")
     * @param Request $request
     *
     * @return Response
     */
    public function getServerPlayers(Request $request)
    {
        if ($request->query->has('id')) {
            $serverHash = $this->em->getRepository(Server::class)
                ->find($this->session->get('active_server'))
                ->getHash();

            $response = file_get_contents(__DIR__ . '/../Resources/' . $serverHash . '.json');
            $code = 200;
        } else {
            $response = json_encode(['error' => 'Bad request. Server id is not specified']);
            $code = 400;
        }

        return new Response($response, $code, [
            'Content-type' => 'application/json',
        ]);
    }

    /**
     * @param $o
     *
     * @return string
     */
    private function toJSON($o)
    {
        switch (gettype($o)) {
            case 'NULL':
                return 'null';
            case 'integer':
            case 'double':
                return strval($o);
            case 'string':
                return '"' . addslashes($o) . '"';
            case 'boolean':
                return $o ? 'true' : 'false';
            case 'object':
                $o = (array)$o;
            case 'array':
                $foundKeys = false;

                foreach ($o as $k => $v) {
                    if (!is_numeric($k)) {
                        $foundKeys = true;
                        break;
                    }
                }

                $result = [];

                if ($foundKeys) {
                    foreach ($o as $k => $v) {
                        $result [] = $this->toJSON($k) . ':' . $this->toJSON($v);
                    }

                    return '{' . implode(',', $result) . '}';
                } else {
                    foreach ($o as $k => $v) {
                        $result [] = $this->toJSON($v);
                    }
                    return '[' . implode(',', $result) . ']';
                }
        }
    }

}
