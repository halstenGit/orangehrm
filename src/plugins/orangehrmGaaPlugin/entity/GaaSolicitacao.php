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

namespace OrangeHRM\Entity;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_gaa_solicitacao")
 * @ORM\Entity
 */
class GaaSolicitacao
{
    public const TIPO_ADMISSAO = 'ADMISSAO';
    public const TIPO_DESLIGAMENTO = 'DESLIGAMENTO';

    public const STATUS_PENDENTE_LIDER = 'PENDENTE_LIDER';
    public const STATUS_PENDENTE_TI = 'PENDENTE_TI';
    public const STATUS_CONCLUIDA = 'CONCLUIDA';
    public const STATUS_CANCELADA = 'CANCELADA';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="emp_number", referencedColumnName="emp_number", nullable=false)
     */
    private Employee $employee;

    /**
     * @ORM\Column(name="tipo", type="string", length=20, nullable=false)
     */
    private string $tipo;

    /**
     * @ORM\Column(name="status", type="string", length=30, nullable=false, options={"default":"PENDENTE_LIDER"})
     */
    private string $status = self::STATUS_PENDENTE_LIDER;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\Employee")
     * @ORM\JoinColumn(name="lider_emp_number", referencedColumnName="emp_number", nullable=true)
     */
    private ?Employee $lider = null;

    /**
     * @ORM\Column(name="criado_em", type="datetime", nullable=false)
     */
    private DateTime $criadoEm;

    /**
     * @ORM\Column(name="atualizado_em", type="datetime", nullable=false)
     */
    private DateTime $atualizadoEm;

    /**
     * @ORM\Column(name="concluido_em", type="datetime", nullable=true)
     */
    private ?DateTime $concluidoEm = null;

    /**
     * @ORM\Column(name="observacoes", type="text", nullable=true)
     */
    private ?string $observacoes = null;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getEmployee(): Employee
    {
        return $this->employee;
    }

    public function setEmployee(Employee $employee): void
    {
        $this->employee = $employee;
    }

    public function getTipo(): string
    {
        return $this->tipo;
    }

    public function setTipo(string $tipo): void
    {
        $this->tipo = $tipo;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getLider(): ?Employee
    {
        return $this->lider;
    }

    public function setLider(?Employee $lider): void
    {
        $this->lider = $lider;
    }

    public function getCriadoEm(): DateTime
    {
        return $this->criadoEm;
    }

    public function setCriadoEm(DateTime $criadoEm): void
    {
        $this->criadoEm = $criadoEm;
    }

    public function getAtualizadoEm(): DateTime
    {
        return $this->atualizadoEm;
    }

    public function setAtualizadoEm(DateTime $atualizadoEm): void
    {
        $this->atualizadoEm = $atualizadoEm;
    }

    public function getConcluidoEm(): ?DateTime
    {
        return $this->concluidoEm;
    }

    public function setConcluidoEm(?DateTime $concluidoEm): void
    {
        $this->concluidoEm = $concluidoEm;
    }

    public function getObservacoes(): ?string
    {
        return $this->observacoes;
    }

    public function setObservacoes(?string $observacoes): void
    {
        $this->observacoes = $observacoes;
    }
}
