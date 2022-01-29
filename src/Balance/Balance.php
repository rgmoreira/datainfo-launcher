<?php

namespace Datainfo\DatainfoApi\Balance;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Balance implements \JsonSerializable
{
    /**
     * @var string
     */
    private $workedTime;

    /**
     * @var string
     */
    private $timeToWork;

    /**
     * Construtor.
     *
     * @param string $workedTime
     * @param string $timeToWork
     */
    public function __construct(string $workedTime, string $timeToWork)
    {
        $this->workedTime = $workedTime;
        $this->timeToWork = $timeToWork;
    }

    /**
     * Obtém o tempo trabalhado.
     *
     * @return string
     */
    public function getWorkedTime(): string
    {
        return $this->workedTime;
    }

    /**
     * Obtém o tempo à trabalhar.
     *
     * @return string
     */
    public function getTimeToWork(): string
    {
        return $this->timeToWork;
    }

    /**
     * Obtém o objeto em formato de array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'worked_time' => $this->toIso8601Interval($this->getWorkedTime()),
            'time_to_work' => $this->toIso8601Interval($this->getTimeToWork()),
        ];
    }

    /**
     * Converte string em timestamp.
     *
     * @return string
     */
    private function toIso8601Interval(string $time): string
    {
        if (!((null !== $replaced = preg_replace('#(\d+):(\d+)#', 'PT$1H$2M', $time)) && $replaced !== $time)) {
            throw new \RuntimeException(sprintf('Falha ao resolver o tempo "%s".', $time));
        }

        return $replaced;
    }
}
