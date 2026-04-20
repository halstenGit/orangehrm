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

use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\BadRequestException;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Entity\GaaSolicitacaoItem;
use OrangeHRM\Entity\User;
use OrangeHRM\Gaa\Api\Model\GaaSolicitacaoItemModel;
use OrangeHRM\Gaa\Traits\Service\GaaServiceTrait;

class GaaRevisaoTiAPI extends Endpoint implements CrudEndpoint
{
    use GaaServiceTrait;
    use AuthUserTrait;

    public const PARAMETER_ACAO = 'acao';
    public const PARAMETER_MOTIVO = 'motivo';

    public const ACAO_APROVAR_ADHOC = 'APROVAR_ADHOC';
    public const ACAO_PROMOVER = 'PROMOVER';
    public const ACAO_REJEITAR = 'REJEITAR';

    public function getAll(): EndpointResult
    {
        $itens = $this->getGaaService()->getGaaDao()->getItensPendentesRevisaoTi();
        return new EndpointCollectionResult(GaaSolicitacaoItemModel::class, $itens);
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection();
    }

    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $item = $this->getGaaService()->getGaaDao()->getItemById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($item, GaaSolicitacaoItem::class);

        if ($item->getStatus() !== GaaSolicitacaoItem::STATUS_PENDENTE_TI_REVISAO) {
            throw new BadRequestException('Item não está aguardando revisão do TI.');
        }

        $acao = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ACAO);
        $user = $this->getCurrentUser();

        switch ($acao) {
            case self::ACAO_APROVAR_ADHOC:
                $item = $this->getGaaService()->aprovarItemAdhoc($item, $user);
                break;
            case self::ACAO_PROMOVER:
                $this->getGaaService()->promoverItemAoCatalogo($item, $user);
                $item = $this->getGaaService()->getGaaDao()->getItemById($id);
                break;
            case self::ACAO_REJEITAR:
                $motivo = $this->getRequestParams()->getString(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_MOTIVO);
                $item = $this->getGaaService()->rejeitarItem($item, $user, $motivo);
                break;
        }

        return new EndpointResourceResult(GaaSolicitacaoItemModel::class, $item);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_ACAO, new Rule(Rules::IN, [[self::ACAO_APROVAR_ADHOC, self::ACAO_PROMOVER, self::ACAO_REJEITAR]])),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_MOTIVO, new Rule(Rules::STRING_TYPE)), true)
        );
    }

    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
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

    private function getCurrentUser(): ?User
    {
        $userId = $this->getAuthUser()->getUserId();
        if ($userId === null) {
            return null;
        }
        return $this->getGaaService()->getGaaDao()->getRepository(User::class)->find($userId);
    }
}
