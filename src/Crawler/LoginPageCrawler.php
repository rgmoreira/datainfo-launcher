<?php

namespace Datainfo\DatainfoApi\Crawler;

use Symfony\Component\BrowserKit\CookieJar as BrowserKitCookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LoginPageCrawler extends AbstractPageCrawler
{
    /**
     * {@inheritDoc}
     */
    public function getUri(): string
    {
        return '/apex/f?p=104:LOGIN';
    }

    /**
     * {@inheritDoc}
     */
    public function getFormButtonText(): string
    {
        return 'Conectar';
    }

    /**
     * Obtém instance (p_instance) obtido na tela de login.
     *
     * @return string
     */
    public function getInstance(): string
    {
        if (null === $this->form) {
            $this->form = $this->getForm();
        }

        return $this->form->get('p_instance')->getValue();
    }

    public function getParsedOraWwvApp104Cookie(): ?string
    {
        if (null === $this->lastResponse) {
            throw new \LogicException('O método "crawl" deve ser chamado primeiramente.');
        }

        $headers = $this->lastResponse->getHeaders();
        if (!isset($headers['set-cookie'])) {
            return null;
        }

        $cookieJar = new BrowserKitCookieJar();
        foreach ($headers['set-cookie'] as $cookieStr) {
            $cookie = BrowserKitCookie::fromString($cookieStr);
            $cookieJar->set($cookie);
        }

        // $oraWwvApp104Cookie = $cookieJar->get('ORA_WWV_APP_7BE0D4E4C778F', '/apex/');
        $oraWwvApp104Cookie = $cookieJar->get('LOGIN', '/apex/');

        return sprintf(
            '%s=%s',
            $oraWwvApp104Cookie->getName(),
            $oraWwvApp104Cookie->getValue()
        );
    }
}
