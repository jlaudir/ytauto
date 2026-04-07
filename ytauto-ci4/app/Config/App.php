<?php
// app/Config/App.php
namespace Config;
use CodeIgniter\Config\BaseConfig;

class App extends BaseConfig
{
    public string $baseURL = 'http://localhost/ytauto/public/';
    public string $indexPage = '';
    public string $uriProtocol = 'REQUEST_URI';
    public string $defaultLocale = 'pt';
    public string $negotiateLocale = 'false';
    public array $supportedLocales = ['pt'];
    public string $appTimezone = 'America/Sao_Paulo';
    public string $charset = 'UTF-8';
    public bool $forceGlobalSecureRequests = false;
    public string $sessionDriver = 'CodeIgniter\Session\Handlers\FileHandler';
    public string $sessionCookieName = 'ytauto_session';
    public int $sessionExpiration = 7200;
    public string $sessionSavePath = WRITEPATH . 'session';
    public bool $sessionMatchIP = false;
    public int $sessionTimeToUpdate = 300;
    public bool $sessionRegenerateDestroy = false;
    public string $cookiePrefix = '';
    public string $cookieDomain = '';
    public string $cookiePath = '/';
    public bool $cookieSecure = false;
    public bool $cookieHTTPOnly = true;
    public string $cookieSameSite = 'Lax';
    public string $proxyIPs = '';
    public string $CSPEnabled = 'false';
}
