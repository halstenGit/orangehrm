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

use DateTime;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\GaaCatalogo;
use OrangeHRM\Gaa\Api\Model\GaaCatalogoModel;
use OrangeHRM\Gaa\Dto\GaaCatalogoSearchFilterParams;
use OrangeHRM\Gaa\Traits\Service\GaaServiceTrait;

class GaaCatalogoAPI extends Endpoint implements CrudEndpoint
{
    use GaaServiceTrait;

    public const PARAMETER_TIPO_ITEM = 'tipoItem';
    public const PARAMETER_NOME = 'nome';
    public const PARAMETER_DESCRICAO = 'descricao';
    public const PARAMETER_QUANTIDADE_PADRAO = 'quantidadePadrao';
    public const PARAMETER_ATIVO = 'ativo';
    public const PARAMETER_IDS = 'ids';

    public const NOME_MAX_LENGTH = 255;

    public function getAll(): EndpointResult
    {
        $params = new GaaCatalogoSearchFilterParams();
        $this->setSortingAndPaginationParams($params);
        $params->setTipoItem($this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_TIPO_ITEM));
        $params->setNome($this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_NOME));
        $params->setAtivo($this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_ATIVO));

        $list = $this->getGaaService()->getGaaDao()->getCatalogoList($params);
        $count = $this->getGaaService()->getGaaDao()->getCatalogoCount($params);

        return new EndpointCollectionResult(
            GaaCatalogoModel::class,
            $list,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_TIPO_ITEM, new Rule(Rules::IN, [[GaaCatalogo::TIPO_ACESSO, GaaCatalogo::TIPO_EQUIPAMENTO]]))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_NOME, new Rule(Rules::STRING_TYPE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_ATIVO, new Rule(Rules::IN, [[0, 1]]))),
            ...$this->getSortingAndPaginationParamsRules(GaaCatalogoSearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $catalogo = $this->getGaaService()->getGaaDao()->getCatalogoById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($catalogo, GaaCatalogo::class);
        return new EndpointResourceResult(GaaCatalogoModel::class, $catalogo);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)));
    }

    public function create(): EndpointResult
    {
        $catalogo = new GaaCatalogo();
        $catalogo->setCriadoEm(new DateTime());
        $catalogo->setAtivo(1);
        $this->setCatalogoFields($catalogo);
        $this->getGaaService()->getGaaDao()->saveCatalogo($catalogo);
        return new EndpointResourceResult(GaaCatalogoModel::class, $catalogo);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_TIPO_ITEM, new Rule(Rules::IN, [[GaaCatalogo::TIPO_ACESSO, GaaCatalogo::TIPO_EQUIPAMENTO]])),
            new ParamRule(self::PARAMETER_NOME, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, self::NOME_MAX_LENGTH])),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_DESCRICAO, new Rule(Rules::STRING_TYPE)), true),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_QUANTIDADE_PADRAO, new Rule(Rules::POSITIVE)))
        );
    }

    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $catalogo = $this->getGaaService()->getGaaDao()->getCatalogoById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($catalogo, GaaCatalogo::class);
        $this->setCatalogoFields($catalogo);
        $this->getGaaService()->getGaaDao()->saveCatalogo($catalogo);
        return new EndpointResourceResult(GaaCatalogoModel::class, $catalogo);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_TIPO_ITEM, new Rule(Rules::IN, [[GaaCatalogo::TIPO_ACESSO, GaaCatalogo::TIPO_EQUIPAMENTO]]))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_NOME, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, self::NOME_MAX_LENGTH]))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_DESCRICAO, new Rule(Rules::STRING_TYPE)), true),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_QUANTIDADE_PADRAO, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_ATIVO, new Rule(Rules::IN, [[0, 1]])))
        );
    }

    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_IDS);
        $this->throwRecordNotFoundExceptionIfEmptyIds($ids);
        $this->getGaaService()->getGaaDao()->deleteCatalogo($ids);
        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule(self::PARAMETER_IDS, new Rule(Rules::INT_ARRAY)));
    }

    private function setCatalogoFields(GaaCatalogo $catalogo): void
    {
        $tipo = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TIPO_ITEM);
        if ($tipo !== null) {
            $catalogo->setTipoItem($tipo);
        }
        $nome = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_NOME);
        if ($nome !== null) {
            $catalogo->setNome($nome);
        }
        $descricao = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_DESCRICAO);
        $catalogo->setDescricao($descricao);
        $qtd = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_QUANTIDADE_PADRAO);
        if ($qtd !== null) {
            $catalogo->setQuantidadePadrao($qtd);
        }
        $ativo = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ATIVO);
        if ($ativo !== null) {
            $catalogo->setAtivo($ativo);
        }
    }
}
