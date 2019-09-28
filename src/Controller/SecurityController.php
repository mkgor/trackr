<?php

namespace App\Controller;

use ErrorException;
use LightOpenID;
use App\Service\SteamAuthenticationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class SecurityController
 *
 * @package App\Controller
 */
class SecurityController extends AbstractController
{
    /**
     * @var LightOpenID
     */
    private $openid;

    private $steamAuthService;

    /**
     * SecurityController constructor.
     *
     * @param SteamAuthenticationService $authenticationService
     *
     * @throws ErrorException
     */
    public function __construct(SteamAuthenticationService $authenticationService)
    {
        $this->openid = new LightOpenID('http://' . $_SERVER['HTTP_HOST'] . '/login');
        $this->steamAuthService = $authenticationService;
    }

    /**
     * @Route("/login", name="login")
     * @param Request $request
     *
     * @return Response
     * @throws ErrorException
     */
    public function index(Request $request)
    {
        if (!$this->openid->mode) {
            $this->openid->identity = 'http://steamcommunity.com/openid/?l=english';

            return $this->render('security/login.html.twig', [
                'loginurl' => $this->openid->authUrl(),
            ]);
        } else {
            return $this->steamAuthService->authorize($this->openid, $request);
        }
    }

    /**
     * @Route("/logout", name="logout")
     * @return RedirectResponse
     */
    public function logout()
    {
        return $this->steamAuthService->logout();
    }
}
