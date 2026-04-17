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
 * @ORM\Table(name="ohrm_gaa_historico")
 * @ORM\Entity
 */
class GaaHistorico
{
    public const ACAO_CRIADO = 'CRIADO';
    public const ACAO_ATUALIZADO = 'ATUALIZADO';
    public const ACAO_APROVADO_ADHOC = 'APROVADO_ADHOC';
    public const ACAO_PROMOVIDO_CATALOGO = 'PROMOVIDO_CATALOGO';
    public const ACAO_REJEITADO = 'REJEITADO';
    public const ACAO_CONCLUIDO = 'CONCLUIDO';
    public const ACAO_COMENTARIO = 'COMENTARIO';

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\GaaSolicitacaoItem")
     * @ORM\JoinColumn(name="item_id", referencedColumnName="id", nullable=false)
     */
    private GaaSolicitacaoItem $item;

    /**
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=true)
     */
    private ?User $user = null;

    /**
     * @ORM\Column(name="acao", type="string", length=50, nullable=false)
     */
    private string $acao;

    /**
     * @ORM\Column(name="payload_json", type="text", nullable=true)
     */
    private ?string $payloadJson = null;

    /**
     * @ORM\Column(name="comentario", type="text", nullable=true)
     */
    private ?string $comentario = null;

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

    public function getItem(): GaaSolicitacaoItem
    {
        return $this->item;
    }

    public function setItem(GaaSolicitacaoItem $item): void
    {
        $this->item = $item;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): void
    {
        $this->user = $user;
    }

    public function getAcao(): string
    {
        return $this->acao;
    }

    public function setAcao(string $acao): void
    {
        $this->acao = $acao;
    }

    public function getPayloadJson(): ?string
    {
        return $this->payloadJson;
    }

    public function setPayloadJson(?string $payloadJson): void
    {
        $this->payloadJson = $payloadJson;
    }

    public function getComentario(): ?string
    {
        return $this->comentario;
    }

    public function setComentario(?string $comentario): void
    {
        $this->comentario = $comentario;
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
