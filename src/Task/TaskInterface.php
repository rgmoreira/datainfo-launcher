<?php

namespace Datainfo\DatainfoApi\Task;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
interface TaskInterface
{
    /**
     * Obtém a data.
     *
     * @return \DateTimeInterface
     */
    public function getDate(): \DateTimeInterface;

    /**
     * Obtém a hora de início.
     *
     * @return \DateTimeInterface
     */
    public function getStartTime(): \DateTimeInterface;

    /**
     * Obtém a hora de conclusão.
     *
     * @return \DateTimeInterface
     */
    public function getEndTime(): \DateTimeInterface;

    /**
     * Obtém a descrição.
     *
     * @return string
     */
    public function getDescription(): string;

    /**
     * Obtém o ticket.
     *
     * @return string
     */
    public function getTicket(): string;
}
