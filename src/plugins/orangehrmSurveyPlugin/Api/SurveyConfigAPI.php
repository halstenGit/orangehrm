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

namespace OrangeHRM\Survey\Api;

use OpenApi\Annotations as OA;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;

class SurveyConfigAPI extends Endpoint implements ResourceEndpoint
{
    use ConfigServiceTrait;

    public const CONFIG_KEY_ALLOW_SUPERVISOR_CREATE = 'survey.allow_supervisor_create';
    public const PARAMETER_ALLOW_SUPERVISOR_CREATE = 'allowSupervisorCreate';

    /**
     * @OA\Get(
     *     path="/api/v2/survey/config",
     *     tags={"Survey/Config"},
     *     summary="Get Survey Configuration",
     *     operationId="get-survey-configuration",
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="allowSupervisorCreate", type="boolean")
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $configValue = $this->getConfigService()->getConfigDao()->getValue(self::CONFIG_KEY_ALLOW_SUPERVISOR_CREATE);
        $allowSupervisorCreate = $configValue === 'true' || $configValue === '1';

        return new EndpointResourceResult(
            ArrayModel::class,
            [self::PARAMETER_ALLOW_SUPERVISOR_CREATE => $allowSupervisorCreate]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        $paramsRules = new ParamRuleCollection();
        $paramsRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramsRules;
    }

    /**
     * @OA\Put(
     *     path="/api/v2/survey/config",
     *     tags={"Survey/Config"},
     *     summary="Update Survey Configuration",
     *     operationId="update-survey-configuration",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="allowSupervisorCreate", type="boolean"),
     *             required={"allowSupervisorCreate"}
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="allowSupervisorCreate", type="boolean")
     *             ),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        $allowSupervisorCreate = $this->getRequestParams()->getBoolean(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ALLOW_SUPERVISOR_CREATE
        );

        $this->getConfigService()->getConfigDao()->setValue(
            self::CONFIG_KEY_ALLOW_SUPERVISOR_CREATE,
            $allowSupervisorCreate ? 'true' : 'false'
        );

        return new EndpointResourceResult(
            ArrayModel::class,
            [self::PARAMETER_ALLOW_SUPERVISOR_CREATE => $allowSupervisorCreate]
        );
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        $paramsRules = new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_ALLOW_SUPERVISOR_CREATE,
                new Rule(Rules::BOOL_TYPE)
            )
        );
        $paramsRules->addExcludedParamKey(CommonParams::PARAMETER_ID);
        return $paramsRules;
    }

    /**
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }
}
