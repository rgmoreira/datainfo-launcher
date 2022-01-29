<?php

namespace Datainfo\DatainfoApi\Crawler;

use Symfony\Component\DomCrawler\Crawler;
use Datainfo\DatainfoApi\Activity\Project;
use Datainfo\DatainfoApi\Activity\ProjectCollection;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LauncherPageCrawler extends AbstractPageCrawler
{
    /**
     * {@inheritDoc}
     */
    protected function getUri(): string
    {
        return sprintf('/apex/f?p=104:100:%s', $this->instance);
    }

    /**
     * {@inheritDoc}
     */
    protected function getFormButtonText(): string
    {
        return 'Salvar';
    }

    /**
     * Obtém o ajaxId usado para obter as atividades.
     *
     * @return string
     */
    public function getAjaxIdForActivitiesFetching(): string
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $leftRegExp = '\#P100_USUARIO,\#P100_PROJETOUSUARIO,\#P100_SEQ_ESFORCO"\,';
        $ajaxId = $this->getAjaxId($this->lastResponse->getContent(), $leftRegExp, '');

        // Convertendo caracteres unicodes para utf-8 ("\u002F" -> "/")
        return json_decode(sprintf('"%s"', $ajaxId));
    }

    /**
     * Obtém o ajaxId usado para lançamento do realizado.
     *
     * @return string
     */
    public function getAjaxIdForLaunching(): string
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $rightRegExp = '\,"attribute01":".*P100_DATAESFORCO\,\#P100_DESCRICAO';
        $ajaxId = $this->getAjaxId($this->lastResponse->getContent(), '', $rightRegExp);

        // Convertendo caracteres unicodes para utf-8 ("\u002F" -> "/")
        return json_decode(sprintf('"%s"', $ajaxId));
    }

    /**
     * Obtém o ajaxId usado para deleção do realizado.
     *
     * @return string
     */
    public function getAjaxIdForTaskDeleting(): string
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $rightRegExp = '\,"attribute01":"\#P100_NUMSEQESFORCO\,\#P100_USUARIO\,\#P100_DATAESFORCO\,\#P100_F_APEX_USER';
        $ajaxId = $this->getAjaxId($this->lastResponse->getContent(), '', $rightRegExp);

        // Convertendo caracteres unicodes para utf-8 ("\u002F" -> "/")
        return json_decode(sprintf('"%s"', $ajaxId));
    }

    /**
     * Obtém o ajaxId usado para deleção do realizado.
     *
     * @return string
     */
    public function getAjaxIdForLaunchedTasks(): string
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $pattern = '#apex\.widget\.report\.init\("QUERY_ESFORCO"\,"(?P<ajax_id>.+)"\,\{"pageItems#';
        if (!preg_match($pattern, $this->lastResponse->getContent(), $matches)) {
            throw new \LengthException('ajaxIdentifier não encontrado.');
        }

        // Convertendo caracteres unicodes para utf-8 ("\u002F" -> "/")
        return json_decode(sprintf('"%s"', $matches['ajax_id']));
    }

    /**
     * Obtém os projetos.
     *
     * @return ProjectCollection
     */
    public function getProjects(): ProjectCollection
    {
        if (null === $this->lastResponse) {
            $this->crawl();
        }

        $projects = new ProjectCollection();

        $crawler = new Crawler($this->lastResponse->getContent());
        $crawler->filter('#P100_PROJETOUSUARIO option:not([value=""])')->each(function (Crawler $option) use ($projects) {
            $projects->add(new Project($option->attr('value'), $option->text()));
        });

        return $projects;
    }
}
