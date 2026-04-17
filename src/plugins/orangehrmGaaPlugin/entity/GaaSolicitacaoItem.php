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

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_gaa_solicitacao_item")
 * @ORM\Entity
 */
class GaaSolicitacaoItem
{
    public const TIPO_ACESSO = 'ACESSO';
    public const TIPO_EQUIPAMENTO = 'EQUIPAMENTO';

    public const STATUS_PENDENTE_LIDER = 'PENDENTE_LIDER';
    public const STATUS_PENDENTE_TI_REVISAO = 'PENDENTE_TI_REVISAO';
    public const STATUS_APROVADO_ADHOC = 'APROVADO_ADHOC';
    public const STATUS_PROMOVIDO = 'PROMOVIDO';
    public const STATUS_CONCLUIDO = 'CONCLUIDO';
    public const STATUS_REJEITADO = 'REJEITADO';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\GaaSolicitacao")
     * @ORM\JoinColumn(name="solicitacao_id", referencedColumnName="id", nullable=false)
     */
    private GaaSolicitacao $solicitacao;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\GaaCatalogo")
     * @ORM\JoinColumn(name="catalogo_id", referencedColumnName="id", nullable=true)
     */
    private ?GaaCatalogo $catalogo = null;

    /**
     * @ORM\Column(name="label_custom", type="string", length=255, nullable=true)
     */
    private ?string $labelCustom = null;

    /**
     * @ORM\Column(name="tipo_item", type="string", length=20, nullable=false)
     */
    private string $tipoItem;

    /**
     * @ORM\Column(name="quantidade", type="integer", nullable=false, options={"default":1})
     */
    private int $quantidade = 1;

    /**
     * @ORM\Column(name="status", type="string", length=30, nullable=false, options={"default":"PENDENTE_LIDER"})
     */
    private string $status = self::STATUS_PENDENTE_LIDER;

    /**
     * @ORM\Column(name="motivo_rejeicao", type="text", nullable=true)
     */
    private ?string $motivoRejeicao = null;

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

    public function getSolicitacao(): GaaSolicitacao
    {
        return $this->solicitacao;
    }

    public function setSolicitacao(GaaSolicitacao $solicitacao): void
    {
        $this->solicitacao = $solicitacao;
    }

    public function getCatalogo(): ?GaaCatalogo
    {
        return $this->catalogo;
    }

    public function setCatalogo(?GaaCatalogo $catalogo): void
    {
        $this->catalogo = $catalogo;
    }

    public function getLabelCustom(): ?string
    {
        return $this->labelCustom;
    }

    public function setLabelCustom(?string $labelCustom): void
    {
        $this->labelCustom = $labelCustom;
    }

    public function getTipoItem(): string
    {
        return $this->tipoItem;
    }

    public function setTipoItem(string $tipoItem): void
    {
        $this->tipoItem = $tipoItem;
    }

    public function getQuantidade(): int
    {
        return $this->quantidade;
    }

    public function setQuantidade(int $quantidade): void
    {
        $this->quantidade = $quantidade;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getMotivoRejeicao(): ?string
    {
        return $this->motivoRejeicao;
    }

    public function setMotivoRejeicao(?string $motivoRejeicao): void
    {
        $this->motivoRejeicao = $motivoRejeicao;
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
