<?php

namespace App\Controller;

use App\Entity\Server;
use App\Entity\Visit;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

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
     * ServerManagementController constructor.
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->em = $entityManager;
    }

    /**
     * @Route("/app/server/management", name="server_management")
     */
    public function index()
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1
        ]);

        return $this->render('server_management/index.html.twig', [
            'servers' => $servers,
            'user_info' => $this->session->all(),
            'active' => 'servers'
        ]);
    }

    /**
     * @Route("/app/server/add", name="server_add")
     * @param Request $request
     * @return RedirectResponse
     * @throws \Exception
     */
    public function addServer(Request $request)
    {
        if(!empty($request->request->get('server_name')) && !empty($request->request->get('loading_url'))) {
            $em = $this->getDoctrine()->getManager();
            $hash = substr(md5(random_bytes(16)),0,16);

            $server = new Server();
            $server->setName($request->request->get('server_name'));
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
     * @return RedirectResponse|Response
     */
    public function setServer(Request $request)
    {
        if($request->query->has('id') && $request->query->has('backurl')) {
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
     * @return RedirectResponse
     */
    public function deleteServer($id)
    {
        $server = $this->em->getRepository(Server::class)->find($id);
        if(!empty($server)) {
            $visit = $this->em->getRepository(Visit::class)->findBy([
                'server' => $server->getId()
            ]);

            if(empty($visit)) {
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
     * @param $id
     * @param Request $request
     * @return RedirectResponse
     */
    public function editServer($id, Request $request) {
        if($request->request->has('server_name') && $request->request->has('loading_url')) {
            $server = $this->em->getRepository(Server::class)->find($id);

            $server->setName($request->request->get('server_name'));
            $server->setLoadingUrl($request->request->get('loading_url'));

            $this->em->persist($server);
            $this->em->flush();

            $this->addFlash('success', 'Server was edited!');
        } else {
            $this->addFlash('error', 'Missed required data!');
        }

        return new RedirectResponse($this->generateUrl('server_management'));
    }
}
