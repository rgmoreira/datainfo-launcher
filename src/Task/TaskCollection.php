<?php

namespace Datainfo\DatainfoApi\Task;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class TaskCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $tasks = [];

    /**
     * Construtor.
     *
     * @param array $tasks
     */
    public function __construct(array $tasks = [])
    {
        foreach ($tasks as $task) {
            $this->add($task);
        }
    }

    public function add(TaskInterface $task): self
    {
        $this->tasks[] = $task;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->tasks);
    }

    public function count(): int
    {
        return count($this->tasks);
    }
}
