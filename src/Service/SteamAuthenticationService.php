<?php

namespace App\Service;

use App\Entity\Configuration;
use App\Entity\Server;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Class SteamAuthenticationService
 * @package Service
 */
class SteamAuthenticationService
{
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var SessionInterface
     */
    private $session;

    /**
     * @var UrlGeneratorInterface
     */
    private $generator;

    /**
     * SteamAuthenticationService constructor.
     * @param EntityManagerInterface $entityManager
     * @param SessionInterface $session
     * @param UrlGeneratorInterface $generator
     */
    public function __construct(EntityManagerInterface $entityManager, SessionInterface $session, UrlGeneratorInterface $generator)
    {
        $this->em = $entityManager;
        $this->session = $session;
        $this->generator = $generator;
    }

    /**
     * @param \LightOpenID $openID
     * @param Request $request
     * @return RedirectResponse
     */
    public function authorize(\LightOpenID $openID, Request $request)
    {
        if($openID->mode == 'cancel') {
            return new RedirectResponse($this->generator->generate('login'));
        }

        $identityArray = explode('/',$openID->data['openid_identity']);
        $steamID = array_pop($identityArray);

        $configurationRepository = $this->em->getRepository(Configuration::class);

        $apikey = $configurationRepository->find(1)->getApikey();

        $url = "http://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/?key=$apikey&steamids=".$steamID;
        $playerinfo = json_decode(file_get_contents($url))->response->players[0];

        $user = $this->em->getRepository(User::class)->findBy([
            'steamid' => $steamID
        ]);

        $servers = $this->em->getRepository(Server::class)->findAll();

        if(!empty($user)) {
            $this->session->start();

            $this->session->set('steamid', $playerinfo->steamid);
            $this->session->set('name', $playerinfo->personaname);
            $this->session->set('avatar', $playerinfo->avatarfull);
            $this->session->set('roles', $user[0]->getRoles());
            $this->session->set('active_server', (!empty($servers)) ? $servers[0]->getId() : 0);

            return new RedirectResponse($this->generator->generate('app'));
        } else {
            return new RedirectResponse($this->generator->generate('login'));
        }
    }

    public function logout()
    {
        $this->session->clear();

        return new RedirectResponse($this->generator->generate('login'));
    }
}