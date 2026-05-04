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
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Entity\GaaCatalogo;
use OrangeHRM\Entity\GaaHistorico;
use OrangeHRM\Entity\GaaSolicitacao;
use OrangeHRM\Entity\GaaSolicitacaoItem;
use OrangeHRM\Gaa\Api\Model\GaaSolicitacaoItemModel;
use OrangeHRM\Gaa\Traits\Service\GaaServiceTrait;

class GaaSolicitacaoItemAPI extends Endpoint implements CrudEndpoint
{
    use GaaServiceTrait;
    use AuthUserTrait;

    public const PARAMETER_SOLICITACAO_ID = 'solicitacaoId';
    public const PARAMETER_CATALOGO_ID = 'catalogoId';
    public const PARAMETER_LABEL_CUSTOM = 'labelCustom';
    public const PARAMETER_TIPO_ITEM = 'tipoItem';
    public const PARAMETER_QUANTIDADE = 'quantidade';
    public const PARAMETER_OBSERVACOES = 'observacoes';
    public const PARAMETER_COMENTARIO = 'comentario';
    public const PARAMETER_IDS = 'ids';

    public function getAll(): EndpointResult
    {
        $solicitacaoId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SOLICITACAO_ID);
        $itens = $this->getGaaService()->getGaaDao()->getItensBySolicitacao($solicitacaoId);
        return new EndpointCollectionResult(GaaSolicitacaoItemModel::class, $itens);
    }

    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(new ParamRule(self::PARAMETER_SOLICITACAO_ID, new Rule(Rules::POSITIVE)));
    }

    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $item = $this->getGaaService()->getGaaDao()->getItemById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($item, GaaSolicitacaoItem::class);
        return new EndpointResourceResult(GaaSolicitacaoItemModel::class, $item);
    }

    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SOLICITACAO_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE))
        );
    }

    public function create(): EndpointResult
    {
        $solicitacaoId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SOLICITACAO_ID);
        $solicitacao = $this->getGaaService()->getGaaDao()->getSolicitacaoById($solicitacaoId);
        $this->throwRecordNotFoundExceptionIfNotExist($solicitacao, GaaSolicitacao::class);

        $catalogoId = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CATALOGO_ID);
        $labelCustom = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_LABEL_CUSTOM);
        if ($catalogoId === null && ($labelCustom === null || trim($labelCustom) === '')) {
            throw new BadRequestException('Informe um item do catálogo ou descreva um item customizado.');
        }
        if ($catalogoId !== null && $labelCustom !== null && trim($labelCustom) !== '') {
            throw new BadRequestException('Informe item do catálogo OU customizado, não ambos.');
        }

        $item = new GaaSolicitacaoItem();
        $item->setSolicitacao($solicitacao);
        $this->setItemFields($item);
        $this->getGaaService()->getGaaDao()->saveItem($item);

        $this->getGaaService()->getGaaDao()->registrarHistorico(
            $item,
            $this->getCurrentUser(),
            GaaHistorico::ACAO_CRIADO
        );

        return new EndpointResourceResult(GaaSolicitacaoItemModel::class, $item);
    }

    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SOLICITACAO_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_TIPO_ITEM, new Rule(Rules::IN, [[GaaSolicitacaoItem::TIPO_ACESSO, GaaSolicitacaoItem::TIPO_EQUIPAMENTO]])),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_CATALOGO_ID, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_LABEL_CUSTOM, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 255])), true),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_QUANTIDADE, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_OBSERVACOES, new Rule(Rules::STRING_TYPE)), true)
        );
    }

    public function update(): EndpointResult
    {
        $solicitacaoId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SOLICITACAO_ID);
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $item = $this->getGaaService()->getGaaDao()->getItemById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($item, GaaSolicitacaoItem::class);

        if ($item->getSolicitacao()->getId() !== $solicitacaoId) {
            throw new BadRequestException('Item não pertence à solicitação informada.');
        }

        $payloadAntes = [
            'catalogo_id' => $item->getCatalogo()?->getId(),
            'label_custom' => $item->getLabelCustom(),
            'quantidade' => $item->getQuantidade(),
            'observacoes' => $item->getObservacoes(),
        ];

        $this->setItemFields($item);
        $this->getGaaService()->getGaaDao()->saveItem($item);

        $comentario = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_COMENTARIO);
        $this->getGaaService()->getGaaDao()->registrarHistorico(
            $item,
            $this->getCurrentUser(),
            $comentario !== null ? GaaHistorico::ACAO_COMENTARIO : GaaHistorico::ACAO_ATUALIZADO,
            $comentario,
            ['antes' => $payloadAntes]
        );

        return new EndpointResourceResult(GaaSolicitacaoItemModel::class, $item);
    }

    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SOLICITACAO_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(CommonParams::PARAMETER_ID, new Rule(Rules::POSITIVE)),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_TIPO_ITEM, new Rule(Rules::IN, [[GaaSolicitacaoItem::TIPO_ACESSO, GaaSolicitacaoItem::TIPO_EQUIPAMENTO]]))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_CATALOGO_ID, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_LABEL_CUSTOM, new Rule(Rules::STRING_TYPE), new Rule(Rules::LENGTH, [null, 255])), true),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_QUANTIDADE, new Rule(Rules::POSITIVE))),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_OBSERVACOES, new Rule(Rules::STRING_TYPE)), true),
            $this->getValidationDecorator()->notRequiredParamRule(new ParamRule(self::PARAMETER_COMENTARIO, new Rule(Rules::STRING_TYPE)), true)
        );
    }

    public function delete(): EndpointResult
    {
        $solicitacaoId = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, self::PARAMETER_SOLICITACAO_ID);
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_IDS);
        $deleted = [];
        foreach ($ids as $id) {
            $intId = (int)$id;
            $item = $this->getGaaService()->getGaaDao()->getItemById($intId);
            if ($item === null || $item->getSolicitacao()->getId() !== $solicitacaoId) {
                continue;
            }
            if ($this->getGaaService()->getGaaDao()->deleteItem($intId)) {
                $deleted[] = $intId;
            }
        }
        return new EndpointResourceResult(ArrayModel::class, $deleted);
    }

    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(self::PARAMETER_SOLICITACAO_ID, new Rule(Rules::POSITIVE)),
            new ParamRule(self::PARAMETER_IDS, new Rule(Rules::INT_ARRAY))
        );
    }

    private function setItemFields(GaaSolicitacaoItem $item): void
    {
        $catalogoId = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_CATALOGO_ID);
        $labelCustom = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_LABEL_CUSTOM);

        // Status só é (re)definido enquanto o item ainda está em fluxo de líder.
        // Itens já promovidos/aprovados/rejeitados/concluídos NÃO devem retroceder de status
        // mesmo se o líder editar a referência catálogo/custom.
        $isInLiderFlow = in_array(
            $item->getStatus() ?? GaaSolicitacaoItem::STATUS_PENDENTE_LIDER,
            [GaaSolicitacaoItem::STATUS_PENDENTE_LIDER, GaaSolicitacaoItem::STATUS_PENDENTE_TI_REVISAO],
            true
        );

        if ($catalogoId !== null) {
            $catalogo = $this->getGaaService()->getGaaDao()->getCatalogoById($catalogoId);
            if ($catalogo === null) {
                throw new BadRequestException('Catálogo não encontrado.');
            }
            $item->setCatalogo($catalogo);
            $item->setLabelCustom(null);
            if ($isInLiderFlow) {
                $item->setStatus(GaaSolicitacaoItem::STATUS_PENDENTE_LIDER);
            }
        } elseif ($labelCustom !== null) {
            $item->setCatalogo(null);
            $item->setLabelCustom($labelCustom);
            if ($isInLiderFlow) {
                $item->setStatus(GaaSolicitacaoItem::STATUS_PENDENTE_TI_REVISAO);
            }
        }

        $tipoItem = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TIPO_ITEM);
        if ($tipoItem !== null) {
            $item->setTipoItem($tipoItem);
        } elseif ($item->getCatalogo() !== null) {
            $item->setTipoItem($item->getCatalogo()->getTipoItem());
        }

        $quantidade = $this->getRequestParams()->getIntOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_QUANTIDADE);
        if ($quantidade !== null) {
            $item->setQuantidade($quantidade);
        }

        $observacoes = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_OBSERVACOES);
        if ($observacoes !== null) {
            $item->setObservacoes($observacoes);
        }
    }

    private function getCurrentUser(): ?\OrangeHRM\Entity\User
    {
        $userId = $this->getAuthUser()->getUserId();
        if ($userId === null) {
            return null;
        }
        return $this->getGaaService()->getGaaDao()->getRepository(\OrangeHRM\Entity\User::class)->find($userId);
    }
}
