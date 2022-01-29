<?php

namespace Datainfo\DatainfoApi\Apex;

use Datainfo\DatainfoApi\Activity\Activity;
use Datainfo\DatainfoApi\Activity\ActivityCollection;
use Datainfo\DatainfoApi\Activity\Project;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class ActivityLoader
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
     * Carrega as atividades.
     *
     * @param Project $project
     * @param string  $instance
     * @param string  $ajaxId
     * @param string  $salt
     * @param string  $protected
     * @param string  $oraWwvApp104Cookie
     *
     * @return ActivityCollection
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \UnexpectedValueException Quando a resposta do Service é insuficiente para ler as atividades.
     * @throws \UnexpectedValueException Quando a resposta do Service é insuficiente para ler a atividade.
     */
    public function load(Project $project, string $instance, string $ajaxId, string $salt, string $protected, string $oraWwvApp104Cookie): ActivityCollection
    {
        $body = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '100',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', $ajaxId),
            'p_json' => \json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P100_PROJETOUSUARIO', 'v' => $project->getId()],
                    ],
                    'protected' => $protected,
                ],
            ]),
        ];

        $headers = [
            'Cookie' => $oraWwvApp104Cookie,
        ];

        $response = $this->client->request('POST', '/apex/wwv_flow.ajax', [
            'headers' => $headers,
            'body' => $body,
        ]);

        $json = $response->toArray();

        if (!isset($json['values'])) {
            throw new \UnexpectedValueException('A resposta do Service é insuficiente para ler as atividades.');
        }

        $activities = new ActivityCollection();

        foreach ($json['values'] as $value) {
            if (!isset($value['d'], $value['r'])) {
                throw new \UnexpectedValueException('A resposta do Service é insuficiente para ler a atividade.');
            }

            $activity = new Activity(
                $value['r'],
                html_entity_decode($value['d'], ENT_QUOTES, 'UTF-8'),
                $project
            );

            $activities->add($activity);
        }

        return $activities;
    }
}
