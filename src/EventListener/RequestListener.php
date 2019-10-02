<?php

namespace App\EventListener;

use App\Entity\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class RequestListener
 * @package App\EventListener
 */
class RequestListener
{
    /**
     * @var string
     */
    private $restricedArea = 'app';

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var
     */
    private $router;

    /**
     * RequestListener constructor.
     *
     * @param SessionInterface       $session
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface        $router
     */
    public function __construct(SessionInterface $session,EntityManagerInterface $entityManager, RouterInterface $router)
    {
        $this->session = $session;
        $this->entityManager = $entityManager;
        $this->router = $router;
    }

    /**
     * Checking user`s authorization and setting locale
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();

        if(preg_match('/app/',$request->getRequestUri()) && !preg_match('/login/', $request->getRequestUri())) {
            if(!$this->session->has('steamid')) {
                $event->setResponse(new RedirectResponse($this->router->generate('login')));
            } else {
                $request->setLocale($this->entityManager->getRepository(Configuration::class)->find(1)->getLocale());
            }
        }
    }
}