<?php

namespace Datainfo\DatainfoApi\Apex;

use Datainfo\DatainfoApi\Activity\Activity;
use Datainfo\DatainfoApi\Effort\EffortType;
use Datainfo\DatainfoApi\Security\User\DatainfoUserInterface;
use Datainfo\DatainfoApi\Task\TaskCollection;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Launcher
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
     * Lança um ponto.
     *
     * A porcentagem sempre será 99%.
     *
     * @param DatainfoUserInterface $user               Usuário da Datainfo.
     * @param TaskCollection        $tasks              Tarefa contendo data, hora inicial, hora final, ticket e descrição.
     * @param Activity              $activity           Execução da sprint, planejamento da sprint, etc.
     * @param EffortType            $effortType         Normal, extra, viagem, etc.
     * @param string                $instance           p_instance.
     * @param string                $ajaxId             ajaxIdentifier.
     * @param string                $salt
     * @param string                $protected
     * @param string                $oraWwvApp104Cookie
     *
     * @return array Mensagens.
     *
     * @throws \UnexpectedValueException Quando a resposta não está no tipo application/json.
     * @throws \DomainException          Quando o Service não lança o ponto e também não retorna nenhum erro.
     */
    public function launch(DatainfoUserInterface $user, TaskCollection $tasks, Activity $activity, EffortType $effortType, string $instance, string $ajaxId, string $salt, string $protected, string $oraWwvApp104Cookie): array
    {
        $itemsToSubmit = [
            ['n' => 'P100_F_APEX_USER',       'v' => strtoupper($user->getDatainfoUsername())],
            ['n' => 'P100_PROJETOUSUARIO',    'v' => $activity->getProject()->getId()],
            ['n' => 'P100_SEQORDEMSERVICO',   'v' => $activity->getId()],
            ['n' => 'P100_TIPOESFORCO',       'v' => $effortType->getId()],
            ['n' => 'P100_PERCONCLUSAO',      'v' => '99'],
            ['n' => 'P100_DIAFUTURO',         'v' => 'N'],
            ['n' => 'P100_PERMISSAO',         'v' => 'S'],
            ['n' => 'P100_TIP_ORDEM_SERVICO', 'v' => '1'],
        ];

        $pJson = [
            'salt' => $salt,
            'pageItems' => [
                'itemsToSubmit' => $itemsToSubmit,
                'protected' => $protected,
            ],
        ];

        $body = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '100',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', $ajaxId),
        ];

        $headers = [
            'Cookie' => $oraWwvApp104Cookie,
        ];

        $responses = [];
        foreach ($tasks as $task) {
            $taskRelatedItemsToSubmit = [
                ['n' => 'P100_DATAESFORCO', 'v' => $task->getDate()->format('d/m/Y')],
                ['n' => 'P100_DESCRICAO',   'v' => $task->getDescription()],
                ['n' => 'P100_HORINICIO',   'v' => $task->getStartTime()->format('H:i')],
                ['n' => 'P100_HORFIM',      'v' => $task->getEndTime()->format('H:i')],
                ['n' => 'P100_CHAMADO',     'v' => $task->getTicket()],
            ];

            $pJson['pageItems']['itemsToSubmit'] = array_merge($itemsToSubmit, $taskRelatedItemsToSubmit);
            $body['p_json'] = json_encode($pJson);

            $responses[] = $this->client->request('POST', '/apex/wwv_flow.ajax', [
                'headers' => $headers,
                'body' => $body,
                'user_data' => [
                    'task' => $task,
                ],
            ]);
        }

        $messages = [];
        foreach ($responses as $response) {
            $json = $response->toArray();

            $validator = Validation::createValidator();
            $violations = $validator->validate($json, $this->createConstraint());

            if (count($violations) > 0) {
                $formatted = [];
                foreach ($violations as $violation) {
                    $formatted[] = [
                        $violation->getPropertyPath() => $violation->getMessage(),
                    ];
                }

                return $formatted;
            }

            $message = $this->getMessage($json);

            $info = $response->getInfo('user_data');
            $task = $info['task'];

            $messages[] = [
                'date' => $task->getDate()->format(\DateTime::ISO8601),
                'start_time' => $task->getStartTime()->format(\DateTime::ISO8601),
                'end_time' => $task->getEndTime()->format(\DateTime::ISO8601),
                'message' => $message,
            ];
        }

        return $messages;
    }

    private function getMessage(array $json): string
    {
        // Se tiver P100_SYSMSG
        if (isset($json['item'][0]['value']) && '' !== $json['item'][0]['value']) {
            return $json['item'][0]['value'];
        }

        // Se tiver P100_MSG
        if (isset($json['item'][1]['value']) && '' !== $json['item'][1]['value']) {
            return $json['item'][1]['value'];
        }

        // P100_SALVOU
        if ('TRUE' === $json['item'][2]['value']) {
            return 'OK';
        }

        throw new \DomainException('O Service não salvou o lançamento e também não apresentou nenhum erro.');
    }

    /**
     * Cria constraint da resposta do Service ao lançar ponto.
     *
     * @return Assert\Collection
     */
    private function createConstraint(): Assert\Collection
    {
        return new Assert\Collection([
            'item' => new Assert\Collection([
                0 => new Assert\Collection([
                    'allowMissingFields' => true,
                    'fields' => [
                        'id' => new Assert\IdenticalTo('P100_SYSMSG'),
                        'value' => new Assert\Type('string'),
                    ],
                ]),
                1 => new Assert\Collection([
                    'allowMissingFields' => true,
                    'fields' => [
                        'id' => new Assert\IdenticalTo('P100_MSG'),
                        'value' => new Assert\Type('string'),
                    ],
                ]),
                2 => new Assert\Collection([
                    'allowMissingFields' => true,
                    'fields' => [
                        'id' => new Assert\IdenticalTo('P100_SALVOU'),
                        'value' => [
                            new Assert\Type('string'),
                            new Assert\Choice(['TRUE', 'FALSE', '']),
                        ],
                    ],
                ]),
            ]),
        ]);
    }
}
