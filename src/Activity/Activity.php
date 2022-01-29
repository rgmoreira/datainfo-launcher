<?php

namespace Datainfo\DatainfoApi\Activity;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Activity implements \JsonSerializable
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $description;

    /**
     * @var Project
     */
    private $project;

    /**
     * Construtor.
     *
     * @param string  $id
     * @param string  $description
     * @param Project $project
     */
    public function __construct(string $id, string $description, Project $project)
    {
        $this->id = $id;
        $this->description = $description;
        $this->project = $project;
    }

    /**
     * Obtém o id.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Obtém a descrição.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * Obtém o projeto.
     *
     * @return Project
     */
    public function getProject(): Project
    {
        return $this->project;
    }

    /**
     * Obtém o objeto em formato de array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $project = $this->getProject();

        return [
            'id' => $this->getId(),
            'description' => $this->getDescription(),
            'project_id' => $project->getId(),
            'project_description' => $project->getDescription(),
        ];
    }
}
