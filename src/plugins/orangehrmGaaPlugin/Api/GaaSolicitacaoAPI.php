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
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\GaaSolicitacao;
use OrangeHRM\Gaa\Api\Model\GaaSolicitacaoModel;
use OrangeHRM\Gaa\Dto\GaaSolicitacaoSearchFilterParams;
use OrangeHRM\Gaa\Traits\Service\GaaServiceTrait;

class GaaSolicitacaoAPI extends Endpoint implements CrudEndpoint
{
    use GaaServiceTrait;

    public const PARAMETER_TIPO = 'tipo';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_LIDER_EMP_NUMBER = 'liderEmpNumber';
    public const PARAMETER_OBSERVACOES = 'observacoes';
    public const PARAMETER_ACAO = 'acao';

    public const ACAO_AVANCAR_TI = 'AVANCAR_TI';
    public const ACAO_CONCLUIR = 'CONCLUIR';

    public function getAll(): EndpointResult
    {
        $params = new GaaSolicitacaoSearchFilterParams();
        $this->setSortingAndPaginationParams($params);
        $params->setEmpNumber($this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_EMP_NUMBER));
        $params->setLiderEmpNumber($this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_LIDER_EMP_NUMBER));
        $params->setTipo($this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_TIPO));
        $params->setStatus($this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_STATUS));

        $list = $this->getGaaService()->getGaaDao()->getSolicitacaoList($params);
        $count = $this->getGaaService()->getGaaDao()->getSolicitacaoCount($params);

        return new EndpointCollectionResult(
            GaaSolicitacaoModel::class,
            $list,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_EMP_NUMBER, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_LIDER_EMP_NUMBER, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_TIPO, new Rule(Rules::IN, [[GaaSolicitacao::TIPO_ADMISSAO, GaaSolicitacao::TIPO_DESLIGAMENTO]]))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_STATUS, new Rule(Rules::STRING_TYPE))),
            ...$this->getSortingAndPaginationParamsRules(GaaSolicitacaoSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $solicitacao = $this->getGaaService()->getGaaDao()->getSolicitacaoById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($solicitacao, GaaSolicitacao::class);
        return new EndpointResourceResult(GaaSolicitacaoModel::class, $solicitacao);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)));
    }

    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $solicitacao = $this->getGaaService()->getGaaDao()->getSolicitacaoById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($solicitacao, GaaSolicitacao::class);

        $observacoes = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_OBSERVACOES);
        if ($observacoes !== null) {
            $solicitacao->setObservacoes($observacoes);
        }

        $acao = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ACAO);
        if ($acao === self::ACAO_AVANCAR_TI) {
            if ($solicitacao->getStatus() !== GaaSolicitacao::STATUS_PENDENTE_LIDER) {
                throw new BadRequestException('Solicitação não está pendente do líder.');
            }
            $solicitacao = $this->getGaaService()->avancarSolicitacaoParaTi($solicitacao);
        } elseif ($acao === self::ACAO_CONCLUIR) {
            if ($solicitacao->getStatus() !== GaaSolicitacao::STATUS_PENDENTE_TI) {
                throw new BadRequestException('Solicitação não está pendente do TI.');
            }
            $solicitacao = $this->getGaaService()->concluirSolicitacao($solicitacao);
        } else {
            $solicitacao = $this->getGaaService()->getGaaDao()->saveSolicitacao($solicitacao);
        }

        return new EndpointResourceResult(GaaSolicitacaoModel::class, $solicitacao);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_OBSERVACOES, new Rule(Rules::STRING_TYPE)), true),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_ACAO, new Rule(Rules::IN, [[self::ACAO_AVANCAR_TI, self::ACAO_CONCLUIR]])))
        );
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
