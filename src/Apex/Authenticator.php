<?php

namespace Datainfo\DatainfoApi\Apex;

use Datainfo\DatainfoApi\Exception\InvalidCredentialsException;
use Datainfo\DatainfoApi\Security\User\DatainfoUserInterface;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\BrowserKit\CookieJar as BrowserKitCookieJar;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Authenticator
{
    /**
     * @var HttpClientInterface
     */
    private $client;

    /**
     * Construtor.
     *
     * @param HttpClientInterface $client
     */
    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    /**
     * Autentica o usuário.
     *
     * Se tudo ocorrer bem, os cookies estarão salvos no $client.
     *
     * @param DatainfoUserInterface $user
     * @param string                $instance           p_instance.
     * @param string                $salt
     * @param string                $protected
     * @param string                $oraWwvApp104Cookie
     *
     * @return string
     *
     * @throws InvalidCredentialsException Quando o usuário e/ou a senha estão incorretos.
     */
    public function authenticate(DatainfoUserInterface $user, string $instance, string $salt, string $protected, string $oraWwvApp104Cookie): string
    {
        $body = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '101',
            'p_instance' => $instance,
            'p_json' => \json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P101_USERNAME', 'v' => $user->getDatainfoUsername()],
                        ['n' => 'P101_PASSWORD', 'v' => $user->getDatainfoPassword()],
                    ],
                    'protected' => $protected,
                ],
            ]),
        ];

        $requestHeaders = [
            'Cookie' => $oraWwvApp104Cookie,
        ];

        $response = $this->client->request('POST', '/apex/wwv_flow.accept', [
            'headers' => $requestHeaders,
            'max_redirects' => 0,
            'body' => $body,
        ]);

        $responseHeaders = $response->getHeaders($throw = false);
        $cookieJar = new BrowserKitCookieJar();

        foreach ($responseHeaders['set-cookie'] as $cookieStr) {
            $cookie = BrowserKitCookie::fromString($cookieStr);
            $cookieJar->set($cookie);
        }

        $oraWwvApp104CookieUpdated = $cookieJar->get('ORA_WWV_APP_7BE0D4E4C778F', '/apex/');
        $oraWwvApp104CookieUpdated = sprintf(
            '%s=%s',
            $oraWwvApp104CookieUpdated->getName(),
            $oraWwvApp104CookieUpdated->getValue()
        );

        $requestHeaders = [
            'Cookie' => $oraWwvApp104CookieUpdated,
        ];

        $response = $this->client->request('GET', $response->getInfo('redirect_url'), [
            'headers' => $requestHeaders,
            'max_redirects' => 0,
        ]);

        if (false === strpos($response->getContent(), 'Sair')) {
            throw new InvalidCredentialsException('Usuário e/ou senha inválido(s).');
        }

        return $oraWwvApp104CookieUpdated;
    }
}
