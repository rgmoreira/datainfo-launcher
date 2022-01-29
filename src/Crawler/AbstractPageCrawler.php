<?php

namespace Datainfo\DatainfoApi\Crawler;

use Symfony\Component\BrowserKit\CookieJar as BrowserKitCookieJar;
use Symfony\Component\BrowserKit\Cookie as BrowserKitCookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
abstract class AbstractPageCrawler
{
    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $oraWwvApp104Cookie;

    /**
     * @var string
     */
    protected $instance;

    /**
     * @var \Symfony\Contracts\HttpClient\ResponseInterface
     */
    protected $lastResponse;

    /**
     * @var Form
     */
    protected $form;

    /**
     * @var Crawler
     */
    protected $crawler;

    /**
     * Construtor.
     *
     * @param HttpClientInterface $client
     * @param string              $instance                 p_instance.
     * @param string              $oraWwvApp104Cookie
     */
    public function __construct(HttpClientInterface $client, string $instance, string $oraWwvApp104Cookie = null)
    {
        $this->client = $client;
        $this->instance = $instance;
        $this->oraWwvApp104Cookie = $oraWwvApp104Cookie;
    }

    /**
     * Obtém o URI.
     *
     * URI onde está a página para fazer o crawling.
     *
     * @return string URI.
     */
    abstract protected function getUri(): string;

    /**
     * Obtém o texto do botão do formulário.
     *
     * Usado para obter pSalt, pPageItemsProtected, etc.
     *
     * @return string
     */
    abstract protected function getFormButtonText(): string;

    /**
     * Obtém o conteúdo HTML da página.
     *
     * @return self
     */
    public function crawl(): self
    {
        $headers = [];
        if (null !== $this->oraWwvApp104Cookie) {
            $headers['cookie'] = $this->oraWwvApp104Cookie;
        }

        $uri = $this->getUri();
        $this->lastResponse = $this->client->request('GET', $uri, [
            'headers' => $headers,
        ]);

        return $this;
    }

    public function getOraWwvApp104Cookie(): ?string
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

        $oraWwvApp104Cookie = $cookieJar->get('ORA_WWV_APP_104', '/apex/');

        return sprintf(
            '%s=%s',
            $oraWwvApp104Cookie->getName(),
            $oraWwvApp104Cookie->getValue()
        );
    }

    /**
     * Obtém o salt contido na tela de login.
     *
     * @return string
     */
    public function getSalt(): string
    {
        if (null === $this->crawler) {
            $this->crawler = $this->createCrawler();
        }

        return $this->crawler->filter('input#pSalt')->attr('value');
    }

    /**
     * Obtém o protected contido na tela de login.
     *
     * @return string
     */
    public function getProtected(): string
    {
        if (null === $this->crawler) {
            $this->crawler = $this->createCrawler();
        }

        return $this->crawler->filter('input#pPageItemsProtected')->attr('value');
    }

    /**
     * Obtém o ajaxId.
     *
     * @param string $subject     String que contém o ajaxIdentifier.
     * @param string $leftRegExp  Expressão regular à esquerda da expressão regular do ajaxIdentifier.
     * @param string $rightRegExp Expressão regular à direita da expressão regular do ajaxIdentifier.
     *
     * @return string ajaxId.
     *
     * @throws \LengthException Quando não é possível obter o ajaxIdentifier.
     */
    protected function getAjaxId(string $subject, string $leftRegExp, string $rightRegExp): string
    {
        if (null === $ajaxId = $this->parseAjaxId($subject, $leftRegExp, $rightRegExp)) {
            throw new \LengthException('ajaxIdentifier não encontrado.');
        }

        return $ajaxId;
    }

    /**
     * Obtém o formulário.
     *
     * @return Form
     */
    protected function getForm(): Form
    {
        if (null === $this->crawler) {
            $this->crawler = $this->createCrawler();
        }

        return $this->crawler->selectButton($this->getFormButtonText())->form();
    }

    /**
     * Obtém instância de Crawler.
     *
     * @return Crawler
     */
    protected function createCrawler(): Crawler
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $uri = sprintf('%s%s', 'http://sistema.datainfo.inf.br', $this->getUri());

        return new Crawler($this->lastResponse->getContent(), $uri);
    }

    /**
     * Obtém o valor do ajaxIdentifier.
     *
     * @param string $subject     String que contém o ajaxIdentifier.
     * @param string $leftRegExp  Expressão regular à esquerda da expressão regular do ajaxIdentifier.
     * @param string $rightRegExp Expressão regular à direita da expressão regular do ajaxIdentifier.
     *
     * @return string|null ajaxIdentifier encontrado ou null caso não encontrado.
     */
    private function parseAjaxId(string $subject, string $leftRegExp, string $rightRegExp): ?string
    {
        $pattern = sprintf('#%s"ajaxIdentifier":"(?P<ajax_id>.+)"%s#', $leftRegExp, $rightRegExp);

        if (preg_match($pattern, $subject, $matches)) {
            return $matches['ajax_id'];
        }

        return null;
    }
}
