<?php

namespace Datainfo\DatainfoApi\Test;

use Datainfo\DatainfoApi\Activity\Activity;
use Datainfo\DatainfoApi\Activity\ActivityCollection;
use Datainfo\DatainfoApi\Activity\Project;
use Datainfo\DatainfoApi\Activity\ProjectCollection;
use Datainfo\DatainfoApi\Apex\ActivityLoader;
use Datainfo\DatainfoApi\Apex\Authenticator;
use Datainfo\DatainfoApi\Apex\BalanceChecker;
use Datainfo\DatainfoApi\Apex\Launcher;
use Datainfo\DatainfoApi\Apex\TaskDeleter;
use Datainfo\DatainfoApi\Apex\WidgetReporter;
use Datainfo\DatainfoApi\Crawler\LauncherPageCrawler;
use Datainfo\DatainfoApi\Crawler\LoginPageCrawler;
use Datainfo\DatainfoApi\Crawler\QueryPageCrawler;
use Datainfo\DatainfoApi\Effort\EffortType;
use Datainfo\DatainfoApi\Effort\FilteringEffortType;
use Datainfo\DatainfoApi\Exception\InvalidCredentialsException;
use Datainfo\DatainfoApi\Security\User\DatainfoUserInterface;
use Datainfo\DatainfoApi\Task\LaunchingTask;
use Datainfo\DatainfoApi\Task\QueriedTask;
use Datainfo\DatainfoApi\Task\TaskCollection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\VarDumper\Test\VarDumperTestTrait;

class DatainfoApiTest extends TestCase
{
    use VarDumperTestTrait;

    private $client;
    private $user;

    protected function setUp(): void
    {
        if (false === getenv('DATAINFO_USERNAME') || false === getenv('DATAINFO_PASSWORD')) {
            $this->markTestSkipped('As variáveis de ambiente DATAINFO_USERNAME e DATAINFO_PASSWORD devem estar configuradas.');
        }

        $this->client = HttpClient::create([
            'base_uri' => 'http://sistema.datainfo.inf.br',
        ]);

        $this->user = new class implements DatainfoUserInterface
        {
            public function getDatainfoUsername(): string
            {
                return getenv('DATAINFO_USERNAME');
            }

            public function getDatainfoPassword(): string
            {
                return getenv('DATAINFO_PASSWORD');
            }

            public function getPis(): string
            {
                return '';
            }
        };
    }

    /**
     * @covers Hallboav\DatainfoApi\Apex\Authenticator::__construct
     * @covers Hallboav\DatainfoApi\Apex\Authenticator::authenticate
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getForm
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getOraWwvApp104Cookie
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\LoginPageCrawler::getFormButtonText
     * @covers Hallboav\DatainfoApi\Crawler\LoginPageCrawler::getInstance
     * @covers Hallboav\DatainfoApi\Crawler\LoginPageCrawler::getUri
     */
    public function testLogin(): array
    {
        ///////////
        // Login //
        ///////////

        $loginPageCrawler = new LoginPageCrawler($this->client, 'not_used');
        $instance = $loginPageCrawler->getInstance();
        $oraWwvApp104Cookie = $loginPageCrawler->getOraWwvApp104Cookie();
        $salt = $loginPageCrawler->getSalt();
        $protected = $loginPageCrawler->getProtected();

        $authenticator = new Authenticator($this->client);
        $oraWwvApp104Cookie = $authenticator->authenticate(
            $this->user,
            $instance,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        // $this->assertTrue(1 === preg_match('#^ORA_WWV_APP_7BE0D4E4C778F\=.*$#', $oraWwvApp104Cookie));
        $this->assertTrue(1 === preg_match('#^LOGIN\=.*$#', $oraWwvApp104Cookie));

        return [
            $oraWwvApp104Cookie,
            $instance,
        ];
    }

    /**
     * @covers Hallboav\DatainfoApi\Apex\Authenticator::__construct
     * @covers Hallboav\DatainfoApi\Apex\Authenticator::authenticate
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getForm
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getOraWwvApp104Cookie
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\LoginPageCrawler::getFormButtonText
     * @covers Hallboav\DatainfoApi\Crawler\LoginPageCrawler::getInstance
     * @covers Hallboav\DatainfoApi\Crawler\LoginPageCrawler::getUri
     */
    public function testLoginIncorreto()
    {
        $user = new class implements DatainfoUserInterface
        {
            public function getDatainfoUsername(): string
            {
                return 'data12345';
            }

            public function getDatainfoPassword(): string
            {
                return 'wr0ngp4ssw0rd';
            }

            public function getPis(): string
            {
                return '';
            }
        };

        $loginPageCrawler = new LoginPageCrawler($this->client, 'not_used');
        $instance = $loginPageCrawler->getInstance();
        $oraWwvApp104Cookie = $loginPageCrawler->getOraWwvApp104Cookie();
        $salt = $loginPageCrawler->getSalt();
        $protected = $loginPageCrawler->getProtected();

        $authenticator = new Authenticator($this->client);

        $this->expectException(InvalidCredentialsException::class);
        $this->expectExceptionMessage('Usuário e/ou senha inválido(s).');

        $authenticator->authenticate(
            $user,
            $instance,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );
    }

    /**
     * @depends testLogin
     * @covers Hallboav\DatainfoApi\Apex\BalanceChecker::__construct
     * @covers Hallboav\DatainfoApi\Apex\BalanceChecker::check
     * @covers Hallboav\DatainfoApi\Balance\Balance::__construct
     * @covers Hallboav\DatainfoApi\Balance\Balance::getTimeToWork
     * @covers Hallboav\DatainfoApi\Balance\Balance::getWorkedTime
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::parseAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\QueryPageCrawler::getAjaxIdForBalanceChecking
     * @covers Hallboav\DatainfoApi\Crawler\QueryPageCrawler::getUri
     */
    public function testSaldo(array $loginData): array
    {
        ///////////
        // Saldo //
        ///////////

        list($oraWwvApp104Cookie, $instance) = $loginData;

        $tz = new \DateTimeZone('America/Sao_Paulo');

        $queryPageCrawler = new QueryPageCrawler($this->client, $instance, $oraWwvApp104Cookie);
        $ajaxIdForBalanceChecking = $queryPageCrawler->getAjaxIdForBalanceChecking();
        $salt = $queryPageCrawler->getSalt();
        $protected = $queryPageCrawler->getProtected();

        $balanceChecker = new BalanceChecker($this->client);
        $balance = $balanceChecker->check(
            new \DateTime('2022-01-01T00:00:00-0300', $tz),
            new \DateTime('2022-01-12T00:00:00-0300', $tz),
            $instance,
            $ajaxIdForBalanceChecking,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        $this->assertEquals('69:38', $balance->getWorkedTime());
        $this->assertRegExp('#\d{2}\:\d{2}#', $balance->getTimeToWork());

        $balance = $balanceChecker->check(
            new \DateTime('2021-12-25T00:00:00-0300', $tz),
            new \DateTime('2021-12-25T00:00:00-0300', $tz),
            $instance,
            $ajaxIdForBalanceChecking,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        $this->assertNull($balance);

        return [
            $oraWwvApp104Cookie,
            $instance,
        ];
    }

    /**
     * @depends testLogin
     * @covers Hallboav\DatainfoApi\Apex\WidgetReporter::__construct
     * @covers Hallboav\DatainfoApi\Apex\WidgetReporter::parseReportContent
     * @covers Hallboav\DatainfoApi\Apex\WidgetReporter::report
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\QueryPageCrawler::getAjaxIdForReporting
     * @covers Hallboav\DatainfoApi\Crawler\QueryPageCrawler::getUri
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::__construct
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::getId
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::resolve
     * @covers Hallboav\DatainfoApi\Effort\EffortType::getEffortMapping
     * @covers Hallboav\DatainfoApi\Effort\FilteringEffortType::getEffortMapping
     */
    public function testRelatorio(array $loginData): void
    {
        ///////////////
        // Relatório //
        ///////////////

        list($oraWwvApp104Cookie, $instance) = $loginData;

        $tz = new \DateTimeZone('America/Sao_Paulo');

        $queryPageCrawler = new QueryPageCrawler($this->client, $instance, $oraWwvApp104Cookie);
        $ajaxId = $queryPageCrawler->getAjaxIdForReporting();
        $salt = $queryPageCrawler->getSalt();
        $protected = $queryPageCrawler->getProtected();

        $widgetReporter = new WidgetReporter($this->client);
        $report = $widgetReporter->report(
            $this->user,
            new \DateTime('2021-12-02T00:00:00-0300', $tz),
            new \DateTime('2021-12-02T00:00:00-0300', $tz),
            new FilteringEffortType('todos'),
            $instance,
            $ajaxId,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        $this->assertGreaterThan(0, count($report));

        $expectedReport = <<<EXPECTED_REPORT
array:1 [
  0 => array:3 [
    "date_formatted" => "02/12/2021"
    "worked_time" => "07:09"
    "details" => array:2 [
      0 => array:7 [
        "description" => "#1303 preparar template java"
        "project" => "PJ8508 - MDC - Ministério da Cidadania - Garantia da Qualidade - 2021"
        "activity" => "274750 - CID - Ministério da Cidadania - Garantia da Qualidade - Dezembro 2021 (Rafael Moreira)"
        "start_time" => "08:03"
        "end_time" => "12:09"
        "worked_time" => "4:06"
        "effort_type" => "Normal (Faturado)"
      ]
      1 => array:7 [
        "description" => null
        "project" => "PJ8508 - MDC - Ministério da Cidadania - Garantia da Qualidade - 2021"
        "activity" => "274750 - CID - Ministério da Cidadania - Garantia da Qualidade - Dezembro 2021 (Rafael Moreira)"
        "start_time" => "13:16"
        "end_time" => "16:19"
        "worked_time" => "3:03"
        "effort_type" => "Normal (Faturado)"
      ]
    ]
  ]
]
EXPECTED_REPORT;

        $this->assertDumpEquals($expectedReport, $report);
    }

    /**
     * @depends testLogin
     * @covers Hallboav\DatainfoApi\Activity\Project::__construct
     * @covers Hallboav\DatainfoApi\Activity\Project::getDescription
     * @covers Hallboav\DatainfoApi\Activity\ProjectCollection::__construct
     * @covers Hallboav\DatainfoApi\Activity\ProjectCollection::add
     * @covers Hallboav\DatainfoApi\Activity\ProjectCollection::count
     * @covers Hallboav\DatainfoApi\Activity\ProjectCollection::getIterator
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getProjects
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getUri
     */
    public function testProjetos(array $loginData): array
    {
        //////////////
        // Projetos //
        //////////////

        list($oraWwvApp104Cookie, $instance) = $loginData;

        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $oraWwvApp104Cookie);
        $projects = $launcherPageCrawler->getProjects();

        $onlyInstanceOfProjects = array_filter((array) $projects->getIterator(), function ($project) {
            return $project instanceof Project;
        });

        $this->assertInstanceOf(ProjectCollection::class, $projects);
        $this->assertEquals(count($onlyInstanceOfProjects), count($projects));

        fwrite(STDERR, sprintf('%sProjetos encontrados:%s', PHP_EOL, PHP_EOL));
        foreach ($projects as $project) {
            fwrite(STDERR, sprintf('%s%s', $project->getDescription(), PHP_EOL));
        }

        return [
            $oraWwvApp104Cookie,
            $instance,
            $projects,
        ];
    }

    /**
     * @depends testProjetos
     * @covers Hallboav\DatainfoApi\Activity\Activity::__construct
     * @covers Hallboav\DatainfoApi\Activity\Activity::getDescription
     * @covers Hallboav\DatainfoApi\Activity\ActivityCollection::__construct
     * @covers Hallboav\DatainfoApi\Activity\ActivityCollection::add
     * @covers Hallboav\DatainfoApi\Activity\ActivityCollection::count
     * @covers Hallboav\DatainfoApi\Activity\ActivityCollection::getIterator
     * @covers Hallboav\DatainfoApi\Activity\Project::getId
     * @covers Hallboav\DatainfoApi\Activity\ProjectCollection::getIterator
     * @covers Hallboav\DatainfoApi\Apex\ActivityLoader::__construct
     * @covers Hallboav\DatainfoApi\Apex\ActivityLoader::load
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::parseAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getAjaxIdForActivitiesFetching
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getUri
     */
    public function testAtividades(array $projetosData): array
    {
        ////////////////
        // Atividades //
        ////////////////

        list($oraWwvApp104Cookie, $instance, $projects) = $projetosData;

        if (!$projects->getIterator()->offsetExists(0)) {
            $this->markTestSkipped('Não há nenhum projeto.');
        }

        $project = $projects->getIterator()->offsetGet(0);

        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $oraWwvApp104Cookie);
        $ajaxId = $launcherPageCrawler->getAjaxIdForActivitiesFetching();
        $salt = $launcherPageCrawler->getSalt();
        $protected = $launcherPageCrawler->getProtected();

        $activityLoader = new ActivityLoader($this->client);
        $activities = $activityLoader->load(
            $project,
            $instance,
            $ajaxId,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        $onlyInstanceOfActivities = array_filter((array) $activities->getIterator(), function ($activity) {
            return $activity instanceof Activity;
        });

        $this->assertInstanceOf(ActivityCollection::class, $activities);
        $this->assertEquals(count($onlyInstanceOfActivities), count($activities));

        fwrite(STDERR, sprintf('%sAtividades encontradas:%s', PHP_EOL, PHP_EOL));
        foreach ($activities as $activity) {
            fwrite(STDERR, sprintf('%s%s', $activity->getDescription(), PHP_EOL));
        }

        return [
            $oraWwvApp104Cookie,
            $instance,
            $activities,
        ];
    }

    /**
     * @depends testAtividades
     * @covers Hallboav\DatainfoApi\Activity\Activity::getId
     * @covers Hallboav\DatainfoApi\Activity\Activity::getProject
     * @covers Hallboav\DatainfoApi\Activity\ActivityCollection::getIterator
     * @covers Hallboav\DatainfoApi\Activity\Project::getId
     * @covers Hallboav\DatainfoApi\Apex\Launcher::__construct
     * @covers Hallboav\DatainfoApi\Apex\Launcher::createConstraint
     * @covers Hallboav\DatainfoApi\Apex\Launcher::getMessage
     * @covers Hallboav\DatainfoApi\Apex\Launcher::launch
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::parseAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getAjaxIdForLaunching
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getUri
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::__construct
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::getId
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::resolve
     * @covers Hallboav\DatainfoApi\Effort\EffortType::getEffortMapping
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::__construct
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getDate
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getDescription
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getEndTime
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getStartTime
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getTicket
     * @covers Hallboav\DatainfoApi\Task\TaskCollection::__construct
     * @covers Hallboav\DatainfoApi\Task\TaskCollection::add
     * @covers Hallboav\DatainfoApi\Task\TaskCollection::getIterator
     */
    public function testLancador(array $atividadesData): array
    {
        //////////////
        // Lançador //
        //////////////

        list($oraWwvApp104Cookie, $instance, $activities) = $atividadesData;

        if (!$activities->getIterator()->offsetExists(0)) {
            $this->markTestSkipped('Não há nenhuma atividade.');
        }

        $tz = new \DateTimeZone('America/Sao_Paulo');
        $date = new \DateTime('midnight', $tz);
        $starTime = new \DateTime('now', $tz);
        $endTime = new \DateTime('5 minutes', $tz);

        $tasks = new TaskCollection([
            new LaunchingTask(
                $date,     // date
                $starTime, // start_time
                $endTime,  // end_time
                'Testing...',
                'TST-0001'
            ),
        ]);

        $activity = $activities->getIterator()->offsetGet(0);
        $effortType = new EffortType('normal');

        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $oraWwvApp104Cookie);
        $ajaxId = $launcherPageCrawler->getAjaxIdForLaunching();
        $salt = $launcherPageCrawler->getSalt();
        $protected = $launcherPageCrawler->getProtected();

        $launcher = new Launcher($this->client);
        $messages = $launcher->launch(
            $this->user,
            $tasks,
            $activity,
            $effortType,
            $instance,
            $ajaxId,
            $salt,
            $protected,
            $oraWwvApp104Cookie
        );

        $expectedMessages = <<<MESSAGES
array:1 [
  0 => array:4 [
    "date" => "{$date->format(\DateTime::ISO8601)}"
    "start_time" => "{$starTime->format(\DateTime::ISO8601)}"
    "end_time" => "{$endTime->format(\DateTime::ISO8601)}"
    "message" => "OK"
  ]
]
MESSAGES;

        $this->assertDumpEquals($expectedMessages, $messages);

        return [
            $oraWwvApp104Cookie,
            $instance,
            $tasks,
        ];
    }

    /**
     * @depends testLancador
     * @covers Hallboav\DatainfoApi\Activity\Activity::__construct
     * @covers Hallboav\DatainfoApi\Activity\Project::__construct
     * @covers Hallboav\DatainfoApi\Apex\TaskDeleter::__construct
     * @covers Hallboav\DatainfoApi\Apex\TaskDeleter::createConstraint
     * @covers Hallboav\DatainfoApi\Apex\TaskDeleter::deleteTask
     * @covers Hallboav\DatainfoApi\Apex\WidgetReporter::__construct
     * @covers Hallboav\DatainfoApi\Apex\WidgetReporter::getTasksOfDate
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::__construct
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::crawl
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::createCrawler
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getProtected
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::getSalt
     * @covers Hallboav\DatainfoApi\Crawler\AbstractPageCrawler::parseAjaxId
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getAjaxIdForLaunchedTasks
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getAjaxIdForTaskDeleting
     * @covers Hallboav\DatainfoApi\Crawler\LauncherPageCrawler::getUri
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::__construct
     * @covers Hallboav\DatainfoApi\Effort\AbstractEffortType::resolve
     * @covers Hallboav\DatainfoApi\Effort\EffortType::getEffortMapping
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::__construct
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getDate
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getDescription
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getEndTime
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getStartTime
     * @covers Hallboav\DatainfoApi\Task\LaunchingTask::getTicket
     * @covers Hallboav\DatainfoApi\Task\QueriedTask::__construct
     * @covers Hallboav\DatainfoApi\Task\QueriedTask::getId
     * @covers Hallboav\DatainfoApi\Task\TaskCollection::__construct
     * @covers Hallboav\DatainfoApi\Task\TaskCollection::add
     * @covers Hallboav\DatainfoApi\Task\TaskCollection::getIterator
     */
    public function testDeletarLancado(array $lancadorData): void
    {
        ///////////////////////
        // Excluindo lançado //
        ///////////////////////

        list($oraWwvApp104Cookie, $instance, $tasks) = $lancadorData;

        $launcherPageCrawler = new LauncherPageCrawler($this->client, $instance, $oraWwvApp104Cookie);
        $ajaxId = $launcherPageCrawler->getAjaxIdForLaunchedTasks();
        $protected = $launcherPageCrawler->getProtected();
        $salt = $launcherPageCrawler->getSalt();

        $task = $tasks->getIterator()->offsetGet(0);

        $widgetReporter = new WidgetReporter($this->client);
        $tasks = $widgetReporter->getTasksOfDate(
            $task->getDate(),
            $instance,
            $ajaxId,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        $this->assertInstanceOf(TaskCollection::class, $tasks);

        $foundTasks = array_filter((array) $tasks->getIterator(), function (QueriedTask $subject) use ($task): bool {
            return
                $subject->getDate()->format('d/m/Y')    === $task->getDate()->format('d/m/Y')
                && $subject->getDescription()              === $task->getDescription()
                && $subject->getStartTime()->format('H:i') === $task->getStartTime()->format('H:i')
                && $subject->getEndTime()->format('H:i')   === $task->getEndTime()->format('H:i')
                // && $subject->getTicket()                   === $task->getTicket()
            ;
        });

        $this->assertCount(1, $foundTasks);
        $foundTask = $foundTasks[0];

        $taskDeleter = new TaskDeleter($this->client);
        $ajaxId = $launcherPageCrawler->getAjaxIdForTaskDeleting();
        $message = $taskDeleter->deleteTask(
            $this->user,
            $foundTask,
            $instance,
            $ajaxId,
            $salt,
            $protected,
            $oraWwvApp104Cookie,
        );

        $this->assertEquals('OK', $message);
    }
}
