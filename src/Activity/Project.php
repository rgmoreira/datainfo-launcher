<?php

namespace Datainfo\DatainfoApi\Activity;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class Project implements \JsonSerializable
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
     * Construtor.
     *
     * @param string $id
     * @param string $description
     */
    public function __construct(string $id, string $description)
    {
        $this->id = $id;
        $this->description = $description;
    }

    /**
     * Obtém o ID.
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
     * Obtém o objeto em formato de array.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'description' => $this->getDescription(),
        ];
    }
}
