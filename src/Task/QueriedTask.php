<?php

namespace Datainfo\DatainfoApi\Task;

use Datainfo\DatainfoApi\Activity\Activity;
use Datainfo\DatainfoApi\Effort\EffortType;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
final class QueriedTask extends LaunchingTask
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var Activity
     */
    private $activity;

    /**
     * @var EffortType
     */
    private $effortType;

    /**
     * Construtor.
     *
     * @param \DateTimeInterface $date        Data da tarefa.
     * @param \DateTimeInterface $startTime   Hora de início da tarefa.
     * @param \DateTimeInterface $endTime     Hora de conclusão da tarefa.
     * @param string             $description Descrição da tarefa.
     * @param string             $ticket      Ticket da tarefa.
     * @param string             $id
     * @param Activity           $activity
     * @param EffortType         $effortType
     */
    public function __construct(
        \DateTimeInterface $date,
        \DateTimeInterface $startTime,
        \DateTimeInterface $endTime,
        string $description,
        string $ticket,
        string $id,
        Activity $activity,
        EffortType $effortType
    ) {
        parent::__construct($date, $startTime, $endTime, $description, $ticket);

        $this->id = $id;
        $this->activity = $activity;
        $this->effortType = $effortType;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getActivity(): Activity
    {
        return $this->activity;
    }

    public function getEffortType(): EffortType
    {
        return $this->effortType;
    }
}
