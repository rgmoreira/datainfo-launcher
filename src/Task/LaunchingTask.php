<?php

namespace Datainfo\DatainfoApi\Task;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class LaunchingTask implements TaskInterface
{
    /**
     * @var \DateTimeInterface
     */
    private $date;

    /**
     * @var \DateTimeInterface
     */
    private $startTime;

    /**
     * @var \DateTimeInterface
     */
    private $endTime;

    /**
     * @var string
     */
    private $description;

    /**
     * @var string
     */
    private $ticket;

    /**
     * Construtor.
     *
     * @param \DateTimeInterface $date        Data da tarefa.
     * @param \DateTimeInterface $startTime   Hora de início da tarefa.
     * @param \DateTimeInterface $endTime     Hora de conclusão da tarefa.
     * @param string             $description Descrição da tarefa.
     * @param string             $ticket      Ticket da tarefa.
     */
    public function __construct(\DateTimeInterface $date, \DateTimeInterface $startTime, \DateTimeInterface $endTime, string $description, string $ticket)
    {
        $this->date = $date;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->description = $description;
        $this->ticket = $ticket;
    }

    /**
     * {@inheritDoc}
     */
    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    /**
     * {@inheritDoc}
     */
    public function getStartTime(): \DateTimeInterface
    {
        return $this->startTime;
    }

    /**
     * {@inheritDoc}
     */
    public function getEndTime(): \DateTimeInterface
    {
        return $this->endTime;
    }

    /**
     * {@inheritDoc}
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * {@inheritDoc}
     */
    public function getTicket(): string
    {
        return $this->ticket;
    }
}
