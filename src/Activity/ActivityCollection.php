<?php

namespace Datainfo\DatainfoApi\Activity;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class ActivityCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $activities = [];

    /**
     * Construtor.
     *
     * @param Activity[] $activities
     */
    public function __construct(array $activities = [])
    {
        foreach ($activities as $activity) {
            $this->add($activity);
        }
    }

    public function add(Activity $activity): self
    {
        $this->activities[] = $activity;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->activities);
    }

    public function count(): int
    {
        return count($this->activities);
    }
}
