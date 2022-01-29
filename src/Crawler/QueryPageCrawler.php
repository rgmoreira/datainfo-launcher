<?php

namespace Datainfo\DatainfoApi\Crawler;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class QueryPageCrawler extends AbstractPageCrawler
{
    /**
     * {@inheritDoc}
     */
    public function getUri(): string
    {
        return sprintf('/apex/f?p=104:10:%s::NO::P10_W_DAT_INICIO,P10_W_DAT_TERMINO:', $this->instance);
    }

    /**
     * {@inheritDoc}
     */
    public function getFormButtonText(): string
    {
        return 'Consultar';
    }

    /**
     * Obtém ajaxId para consultar o saldo.
     *
     * @return string
     */
    public function getAjaxIdForBalanceChecking(): string
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $leftRegExp = 'attribute05.+,';
        $rightRegExp = '\,"attribute01":"\#P10_W_DAT_INICIO';
        $ajaxId = $this->getAjaxId($this->lastResponse->getContent(), $leftRegExp, $rightRegExp);

        // Convertendo caracteres unicodes para utf-8 ("\u002F" -> "/")
        return json_decode(sprintf('"%s"', $ajaxId));
    }

    public function getAjaxIdForReporting(): string
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $pattern = '#apex\.widget\.report\.init\("P10_LISTA"\,"(?P<ajax_id>.+)"\,\{"pageItems#';
        if (!preg_match($pattern, $this->lastResponse->getContent(), $matches)) {
            throw new \LengthException('ajaxIdentifier não encontrado.');
        }

        return json_decode(sprintf('"%s"', $matches['ajax_id']));
    }
}
