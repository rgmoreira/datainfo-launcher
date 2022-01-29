<?php

namespace Datainfo\DatainfoApi\Activity;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class ProjectCollection implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $projects = [];

    /**
     * Construtor.
     *
     * @param Project[] $projects
     */
    public function __construct(array $projects = [])
    {
        foreach ($projects as $project) {
            $this->add($project);
        }
    }

    public function add(Project $project): self
    {
        $this->projects[] = $project;

        return $this;
    }

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->projects);
    }

    public function count(): int
    {
        return count($this->projects);
    }
}
