<?php

namespace Datainfo\DatainfoApi\Security\User;

/**
 * @author Hallison Boaventura <hallisonboaventura@gmail.com>
 */
interface DatainfoUserInterface
{
    /**
     * Obtém o PIS do funcionário.
     *
     * @return string
     */
    public function getPis(): string;

    /**
     * Obtém o nome de usuário do funcionário.
     *
     * @return string
     */
    public function getDatainfoUsername(): string;

    /**
     * Obtém a senha do funcionário nos sistemas da Datainfo.
     *
     * @return string
     */
    public function getDatainfoPassword(): string;
}
