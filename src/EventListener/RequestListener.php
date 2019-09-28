<?php

namespace App\EventListener;

use App\Entity\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
     * RequestListener constructor.
     *
     * @param SessionInterface $session
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(SessionInterface $session, UrlGeneratorInterface $generator, EntityManagerInterface $entityManager)
    {
        $this->session = $session;
        $this->urlGenerator = $generator;
        $this->entityManager = $entityManager;
    }

    /**
     * Checking user`s authorization and setting locale
     *
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        $request = $event->getRequest();
        $requestUri = substr($request->getRequestUri(), 1);
        $requestUriExploded = explode('/',$requestUri);

        if(array_shift($requestUriExploded) == $this->restricedArea) {
            if(!$this->session->has('steamid')) {
                $event->setResponse(new RedirectResponse($this->urlGenerator->generate('login')));
            } else {
                $request->setLocale($this->entityManager->getRepository(Configuration::class)->find(1)->getLocale());
            }
        }
    }
}