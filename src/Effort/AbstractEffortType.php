<?php

namespace Datainfo\DatainfoApi\Effort;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
abstract class AbstractEffortType
{
    /**
     * @var string
     */
    private $id;

    /**
     * Construtor.
     *
     * @param string $name
     */
    public function __construct(string $name)
    {
        if (null === $this->id = $this->resolve($name)) {
            throw new \InvalidArgumentException('Tipo de esforço inválido.');
        }
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
     * Obtém o mapeamento de eforços.
     *
     * @return array
     */
    abstract public static function getEffortMapping(): array;

    /**
     * Resolve o ID a partir do nome.
     *
     * @param string $name
     *
     * @return string|null ID do esforço ou nulo caso o nome do esforço não seja encontrado.
     */
    protected function resolve(string $name): ?string
    {
        foreach ($this->getEffortMapping() as $effortName => $id) {
            if ($effortName === $name) {
                return $id;
            }
        }

        return null;
    }
}
