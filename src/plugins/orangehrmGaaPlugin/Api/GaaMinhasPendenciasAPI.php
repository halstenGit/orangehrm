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

namespace OrangeHRM\Gaa\Api;

use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Gaa\Api\Model\GaaSolicitacaoModel;
use OrangeHRM\Gaa\Traits\Service\GaaServiceTrait;

class GaaMinhasPendenciasAPI extends Endpoint implements CollectionEndpoint
{
    use GaaServiceTrait;
    use AuthUserTrait;

    public function getAll(): EndpointResult
    {
        $userRole = (string)$this->getAuthUser()->getUserRoleName();
        $empNumber = $this->getAuthUser()->getEmpNumber();

        if ($userRole === 'Admin') {
            $solicitacoes = $this->getGaaService()->getGaaDao()->getMinhasPendenciasComoTi();
        } else {
            $solicitacoes = $empNumber !== null
                ? $this->getGaaService()->getGaaDao()->getMinhasPendenciasComoLider($empNumber)
                : [];
        }

        return new EndpointCollectionResult(GaaSolicitacaoModel::class, $solicitacoes);
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }
}
