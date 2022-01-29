<?php

namespace Datainfo\DatainfoApi\Effort;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
class EffortType extends AbstractEffortType
{
    const NORMAL = '1';
    const A_COMPENSAR = '2';
    const EXTRA = '3';
    const VIAGEM = '4';
    const CONCILIACAO = '5';
    const NAO_REMUNERADO = '6';
    const VARIAVEL = '7';

    /**
     * {@inheritDoc}
     */
    public static function getEffortMapping(): array
    {
        return [
            'normal' => self::NORMAL,
            'a-compensar' => self::A_COMPENSAR,
            'extra' => self::EXTRA,
            'viagem' => self::VIAGEM,
            'conciliacao' => self::CONCILIACAO,
            'nao-remunerado' => self::NAO_REMUNERADO,
            'variavel' => self::VARIAVEL,
        ];
    }
}
