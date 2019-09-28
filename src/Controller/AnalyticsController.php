<?php

namespace App\Controller;

use App\Entity\Server;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class AnalyticsController extends AbstractController
{
    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * AnalyticsController constructor.
     * @param SessionInterface $session
     */
    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * @Route("/app/analytics", name="analytics")
     */
    public function index()
    {
        $servers = $this->getDoctrine()->getRepository(Server::class)->findBy([
            'active' => 1
        ]);

        return $this->render('analytics/index.html.twig', [
            'servers' => $servers,
            'user_info' => $this->session->all(),
            'active' => 'analytics'
        ]);
    }
}
