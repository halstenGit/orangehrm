<?php

/**
 * OrangeHRM is a comprehensive Human Resource Management (HRM) System that captures
 * all the essential functionalities required for any enterprise.
 * Copyright (C) 2006 OrangeHRM Inc., http://www.orangehrm.com
 *
 * OrangeHRM is free software: you can redistribute it and/or modify it under the terms of
 * the GNU General Public License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * OrangeHRM is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
 * without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 * See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with OrangeHRM.
 * If not, see <https://www.gnu.org/licenses/>.
 */

namespace OrangeHRM\Gaa\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\GaaSolicitacao;

class GaaSolicitacaoModel implements Normalizable
{
    private GaaSolicitacao $solicitacao;

    public function __construct(GaaSolicitacao $solicitacao)
    {
        $this->solicitacao = $solicitacao;
    }

    public function toArray(): array
    {
        $emp = $this->solicitacao->getEmployee();
        $lider = $this->solicitacao->getLider();

        return [
            'id' => $this->solicitacao->getId(),
            'tipo' => $this->solicitacao->getTipo(),
            'status' => $this->solicitacao->getStatus(),
            'employee' => [
                'empNumber' => $emp->getEmpNumber(),
                'firstName' => $emp->getFirstName(),
                'lastName' => $emp->getLastName(),
                'employeeId' => $emp->getEmployeeId(),
            ],
            'lider' => $lider !== null ? [
                'empNumber' => $lider->getEmpNumber(),
                'firstName' => $lider->getFirstName(),
                'lastName' => $lider->getLastName(),
            ] : null,
            'criadoEm' => $this->solicitacao->getCriadoEm()->format('Y-m-d H:i:s'),
            'atualizadoEm' => $this->solicitacao->getAtualizadoEm()->format('Y-m-d H:i:s'),
            'concluidoEm' => $this->solicitacao->getConcluidoEm()?->format('Y-m-d H:i:s'),
            'observacoes' => $this->solicitacao->getObservacoes(),
        ];
    }
}
