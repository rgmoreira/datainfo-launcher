<?php

namespace Datainfo\DatainfoApi\Apex;

use Datainfo\DatainfoApi\Activity\Activity;
use Datainfo\DatainfoApi\Effort\EffortType;
use Datainfo\DatainfoApi\Effort\FilteringEffortType;
use Datainfo\DatainfoApi\Activity\Project;
use Datainfo\DatainfoApi\Security\User\DatainfoUserInterface;
use Datainfo\DatainfoApi\Task\QueriedTask;
use Datainfo\DatainfoApi\Task\TaskCollection;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class WidgetReporter
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
     * Consulta um relatório.
     *
     * Retorna as informações em um array com até 9999 linhas.
     *
     * @param DatainfoUserInterface $user
     * @param \DateTimeInterface    $startDate
     * @param \DateTimeInterface    $endDate
     * @param FilteringEffortType   $effort    Tipo de esforço para filtro.
     * @param string                $instance
     * @param string                $ajaxId
     * @param string                $salt
     * @param string                $protected
     * @param string                $oraWwvApp104Cookie
     *
     * @return array Informações obtidas através da análise da resposta recebida.
     *
     * @throws \UnexpectedValueException Quando não é possível ler corretamente o conteúdo do elemento que possui a classe .apex_report_break.
     */
    public function report(DatainfoUserInterface $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate, FilteringEffortType $effort, string $instance, string $ajaxId, string $salt, string $protected, string $oraWwvApp104Cookie): array
    {
        $body = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '10',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', $ajaxId),
            'p_widget_action' => 'paginate',
            'p_pg_min_row' => '1',
            'p_pg_max_rows' => '9999',
            'p_pg_rows_fetched' => '9999',
            'x01' => '88237305110178876',
            'p_json' => \json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P10_COD_USER',      'v' => strtoupper($user->getDatainfoUsername())],
                        ['n' => 'P10_W_DAT_INICIO',  'v' => $startDate->format('d/m/Y')],
                        ['n' => 'P10_W_DAT_TERMINO', 'v' => $endDate->format('d/m/Y')],
                        ['n' => 'P10_W_TIP_ESFORCO', 'v' => $effort->getId()],
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

        return $this->parseReportContent($response->getContent());
    }

    /**
     * Obtém as tasks de uma data.
     *
     * @param \DateTime $date               Data das tasks.
     * @param string    $instance
     * @param string    $ajaxId
     * @param string    $salt
     * @param string    $protected
     * @param string    $oraWwvApp104Cookie
     *
     * @return TaskCollection
     */
    public function getTasksOfDate(\DateTime $date, string $instance, string $ajaxId, string $salt, string $protected, string $oraWwvApp104Cookie): TaskCollection
    {
        $body = [
            'p_flow_id' => '104',
            'p_flow_step_id' => '100',
            'p_instance' => $instance,
            'p_request' => sprintf('PLUGIN=%s', $ajaxId),
            'p_widget_action' => 'reset',
            'x01' => '785933231868771829',
            'p_json' => json_encode([
                'salt' => $salt,
                'pageItems' => [
                    'itemsToSubmit' => [
                        ['n' => 'P100_DATAESFORCO', 'v' => $date->format('d/m/Y')],
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

        // <tr class="highlight-row">
        //     <td headers="SEQ_ESFORCO_PROJE" class="data">
        //         <a href="#" onclick="$s('P100_NUMSEQESFORCO',11890006); return false;">
        //             <img src="/i/menu/pencil2_16x16.gif" alt="">
        //         </a>
        //     </td>
        //     <td headers="DES_ESFORCO" class="data">Nuxa...</td>
        //     <td headers="NOM_PROJE" class="data">PJ588</td>
        //     <td headers="DES_SERVICO" class="data">6810 - Integração</td>
        //     <td headers="HOR_INICIO" class="data">21:26</td>
        //     <td headers="HOR_TERMINO" class="data">21:31</td>
        //     <td headers="QTD_HORA_ESFORCO" class="data">00:05</td>
        //     <td headers="TIP_ESFORCO" class="data">Normal (Faturado)</td>
        //     <td headers="NUM_CHAMDO" class="data">
        //         <a href="javascript:void(0);" onclick="setPageGesti(NXA-0001)">NXA-0001</a>
        //     </td>
        // </tr>

        $crawler = new Crawler(
            $response->getContent(),
            sprintf('%s%s', 'http://sistema.datainfo.inf.br', '/apex/wwv_flow.ajax')
        );

        $tasks = new TaskCollection();
        $crawler->filter('.highlight-row')->each(function (Crawler $tr) use (&$tasks, $date): void {
            $expr = $tr->filter('td[headers="SEQ_ESFORCO_PROJE"] > a')->attr('onclick');
            if (!preg_match('#P100_NUMSEQESFORCO\'\,(?P<id>\d+)\)#', $expr, $matches)) {
                throw new \UnexpectedValueException(sprintf('Falha ao obter ID da tarefa com expr: %s.', $expr));
            }

            list($h, $m) = sscanf($tr->filter('td[headers="HOR_INICIO"]')->text(), '%d:%d');
            $startTime   = (clone $date)->add(new \DateInterval(sprintf('PT%dH%dM', $h, $m)));

            list($h, $m) = sscanf($tr->filter('td[headers="HOR_TERMINO"]')->text(), '%d:%d');
            $endTime     = (clone $date)->add(new \DateInterval(sprintf('PT%dH%dM', $h, $m)));

            $description = $tr->filter('td[headers="DES_ESFORCO"]')->text();
            $ticket      = $tr->filter('td[headers="NUM_CHAMDO"]')->text();
            $id          = $matches['id'];
            $project     = new Project($tr->filter('td[headers="NOM_PROJE"]')->text(), 'not_used');
            $activity    = new Activity('not_used', $tr->filter('td[headers="DES_SERVICO"]')->text(), $project);
            // $effortType  = new EffortType($tr->filter('td[headers="TIP_ESFORCO"]')->text());
            $effortType  = new EffortType('normal');

            $task = new QueriedTask(
                $date,
                $startTime,
                $endTime,
                $description,
                $ticket,
                $id,
                $activity,
                $effortType,
            );

            $tasks->add($task);
        });

        return $tasks;
    }

    /**
     * Analisa retorno do serviço de obter tarefas realizadas.
     *
     * @param string $content
     *
     * @return array
     */
    private function parseReportContent(string $content): array
    {
        $crawler = new Crawler(
            $content,
            sprintf('%s%s', 'http://sistema.datainfo.inf.br', '/apex/wwv_flow.ajax')
        );

        if (1 === $crawler->filter('.nodatafound')->count()) {
            return [];
        }

        $data = [];
        $dataIndex = 0;
        $keyMap = [
            'description',
            'project',
            'activity',
            'start_time',
            'end_time',
            'worked_time',
            'effort_type',
        ];

        $crawler->filter('.report-standard > tr')->each(function (Crawler $tr, int $i) use (&$data, &$date, &$keyMap, &$dataIndex) {
            // Existem três tipos de <tr>'s
            // 1. .apex_report_break: contém a data e o total de horas trabalhadas
            //                        <tr><td colspan="9" id="DAT_ESFORCO" class="apex_report_break">Data do Realizado: 05/01/2018 - Total de horas: 8:11</td></tr>

            // 2. <tr> sem atributo:  não utilizado; contém cabeçalhos visuais
            //                        <tr><th align="left" id="DES_ESFORCO_000" class="header">Descrição</th> <th>...</th> </tr>

            // 3. .highlight-row:     contém os valores que foram postados pelo usuário
            //                        <tr class="highlight-row"><td headers="DES_ESFORCO_000" class="data">foo bar</td> <td>...</td> </tr>

            // Obtém todos os nós filhos do <tr> que tenham a classe .apex_report_break
            $break = $tr->children()->filter('.apex_report_break');

            // Se o <tr> atual da iteração conter informações do realizado
            if (1 === $break->count()) {
                // ... então faz parse do texto "Data do Realizado: 05/01/2018 - Total de horas: 8:11"
                $html = $break->first()->html();

                if (!preg_match('#(?P<current_date>\d{2}\/\d{2}\/\d{4}).*\ (?P<worked_time>\d{1,2}\:\d{2})#', $html, $matches)) {
                    throw new \UnexpectedValueException(sprintf('preg_match failed to parse contents of apex_report_break class.'));
                }

                $date = \DateTime::createFromFormat('!d/m/Y', $matches['current_date'], new \DateTimeZone('America/Sao_Paulo'));

                $data[$dataIndex] = [
                    'date_formatted' => $date->format('d/m/Y'),
                    'worked_time' => $matches['worked_time'],
                ];

                $dataIndex++;
            } elseif ('highlight-row' === $tr->attr('class')) {
                // ... então vamos tratar os valores postados pelo usuário

                $details = [];
                $tr->filter('.data')->each(function (Crawler $data, $i) use (&$details, &$keyMap) {
                    $html = trim($data->html());

                    // Um caracter vazio que parece um caracter de espaço que simboliza que
                    // a descrição é igual a anterior
                    if (0 === $i && "\xc2\xa0" === $html) {
                        $html = null;
                    }

                    $details[$keyMap[$i]] = $html;
                });

                $data[$dataIndex - 1]['details'][] = $details;
            }
        });

        return $data;
    }
}
