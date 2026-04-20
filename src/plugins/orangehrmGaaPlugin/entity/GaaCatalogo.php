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
 * @ORM\Table(name="ohrm_gaa_catalogo")
 * @ORM\Entity
 */
class GaaCatalogo
{
    public const TIPO_ACESSO = 'ACESSO';
    public const TIPO_EQUIPAMENTO = 'EQUIPAMENTO';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\Column(name="tipo_item", type="string", length=20, nullable=false)
     */
    private string $tipoItem;

    /**
     * @ORM\Column(name="nome", type="string", length=255, nullable=false)
     */
    private string $nome;

    /**
     * @ORM\Column(name="descricao", type="text", nullable=true)
     */
    private ?string $descricao = null;

    /**
     * @ORM\Column(name="quantidade_padrao", type="integer", nullable=false, options={"default":1})
     */
    private int $quantidadePadrao = 1;

    /**
     * @ORM\Column(name="ativo", type="smallint", nullable=false, options={"default":1})
     */
    private int $ativo = 1;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\GaaSolicitacaoItem")
     * @ORM\JoinColumn(name="criado_de_solicitacao_item_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     */
    private ?GaaSolicitacaoItem $criadoDeSolicitacaoItem = null;

    /**
     * @ORM\Column(name="criado_em", type="datetime", nullable=false)
     */
    private DateTime $criadoEm;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getTipoItem(): string
    {
        return $this->tipoItem;
    }

    public function setTipoItem(string $tipoItem): void
    {
        $this->tipoItem = $tipoItem;
    }

    public function getNome(): string
    {
        return $this->nome;
    }

    public function setNome(string $nome): void
    {
        $this->nome = $nome;
    }

    public function getDescricao(): ?string
    {
        return $this->descricao;
    }

    public function setDescricao(?string $descricao): void
    {
        $this->descricao = $descricao;
    }

    public function getQuantidadePadrao(): int
    {
        return $this->quantidadePadrao;
    }

    public function setQuantidadePadrao(int $quantidadePadrao): void
    {
        $this->quantidadePadrao = $quantidadePadrao;
    }

    public function getAtivo(): int
    {
        return $this->ativo;
    }

    public function setAtivo(int $ativo): void
    {
        $this->ativo = $ativo;
    }

    public function getCriadoDeSolicitacaoItem(): ?GaaSolicitacaoItem
    {
        return $this->criadoDeSolicitacaoItem;
    }

    public function setCriadoDeSolicitacaoItem(?GaaSolicitacaoItem $item): void
    {
        $this->criadoDeSolicitacaoItem = $item;
    }

    public function getCriadoEm(): DateTime
    {
        return $this->criadoEm;
    }

    public function setCriadoEm(DateTime $criadoEm): void
    {
        $this->criadoEm = $criadoEm;
    }
}
