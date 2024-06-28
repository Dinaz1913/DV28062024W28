<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\HttpKernel\EventListener\ErrorListener;
use Symfony\Component\HttpKernel\HttpKernel;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Dotenv\Dotenv;
use Reelz222z\Cryptoexchange\Controller\AuthController;
use Reelz222z\Cryptoexchange\Controller\CryptoController;
use Reelz222z\Cryptoexchange\Model\User;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

//TODO: initialize twig
$loader = new FilesystemLoader(__DIR__ . '/../src/View');
$twig = new Environment($loader);

$session = new Session();
$session->start();

$request = Request::createFromGlobals();
$request->setSession($session);

$routes = new RouteCollection();
$routes->add('login', new Route('/login', ['_controller' => 'Reelz222z\Cryptoexchange\Controller\AuthController::login']));
$routes->add('cryptoList', new Route('/crypto', ['_controller' => 'Reelz222z\Cryptoexchange\Controller\CryptoController::index']));
$routes->add('cryptoDetail', new Route('/crypto/{symbol}', ['_controller' => 'Reelz222z\Cryptoexchange\Controller\CryptoController::detail']));
$routes->add('portfolio', new Route('/portfolio', ['_controller' => 'Reelz222z\Cryptoexchange\Controller\CryptoController::portfolio']));
$routes->add('cryptoBuy', new Route('/crypto/buy', ['_controller' => 'Reelz222z\Cryptoexchange\Controller\CryptoController::buy']));
$routes->add('cryptoSell', new Route('/crypto/sell', ['_controller' => 'Reelz222z\Cryptoexchange\Controller\CryptoController::sell']));

$context = new RequestContext();
$context->fromRequest($request);

$matcher = new UrlMatcher($routes, $context);
$dispatcher = new EventDispatcher();
$dispatcher->addSubscriber(new RouterListener($matcher, new RequestStack()));
$dispatcher->addSubscriber(new ErrorListener('Reelz222z\Cryptoexchange\Controller\ErrorController::exception'));

$controllerResolver = new ControllerResolver();
$argumentResolver = new ArgumentResolver();

$kernel = new HttpKernel($dispatcher, $controllerResolver, new RequestStack(), $argumentResolver);

$response = $kernel->handle($request);

switch ($request->attributes->get('_route')) {
    case 'login':
        $controller = new AuthController();
        $result = $controller->login($request);
        if (!$result instanceof User) {
            echo $twig->render('auth/login.html.twig', ['error' => $result]);
            break;
        }
        $session->set('user', $result);
        header('Location: /crypto');
        exit;
        break;

    case 'cryptoList':
        $controller = new CryptoController();
        $cryptos = $controller->index($request);
        echo $twig->render('crypto/index.html.twig', ['cryptos' => $cryptos]);
        break;

    case 'cryptoDetail':
        $controller = new CryptoController();
        $crypto = $controller->detail($request, $request->attributes->get('symbol'));
        echo $twig->render('crypto/detail.html.twig', ['crypto' => $crypto]);
        break;

    case 'portfolio':
        $controller = new CryptoController();
        $portfolio = $controller->portfolio($request);
        echo $twig->render('crypto/portfolio.html.twig', ['user' => $session->get('user'), 'transactions' => $portfolio]);
        break;

    case 'cryptoBuy':
        $controller = new CryptoController();
        $result = $controller->buy($request);
        if ($result !== true) {
            echo $twig->render('crypto/detail.html.twig', ['crypto' => $result['crypto'], 'error' => $result['error']]);
            break;
        }
        header('Location: /portfolio');
        exit;
        break;

    case 'cryptoSell':
        $controller = new CryptoController();
        $result = $controller->sell($request);
        if ($result !== true) {
            echo $twig->render('crypto/detail.html.twig', ['crypto' => $result['crypto'], 'error' => $result['error']]);
            break;
        }
        header('Location: /portfolio');
        exit;
        break;

    default:
        echo '404 Not Found';
        break;
}

$response->send();
$kernel->terminate($request, $response);
