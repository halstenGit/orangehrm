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
use OrangeHRM\Entity\GaaHistorico;

class GaaHistoricoModel implements Normalizable
{
    private GaaHistorico $historico;

    public function __construct(GaaHistorico $historico)
    {
        $this->historico = $historico;
    }

    public function toArray(): array
    {
        $user = $this->historico->getUser();
        $item = $this->historico->getItem();

        return [
            'id' => $this->historico->getId(),
            'itemId' => $item->getId(),
            'solicitacaoId' => $item->getSolicitacao()->getId(),
            'acao' => $this->historico->getAcao(),
            'comentario' => $this->historico->getComentario(),
            'payloadJson' => $this->historico->getPayloadJson(),
            'usuario' => $user !== null ? [
                'id' => $user->getId(),
                'username' => $user->getUserName(),
            ] : null,
            'criadoEm' => $this->historico->getCriadoEm()->format('Y-m-d H:i:s'),
        ];
    }
}
