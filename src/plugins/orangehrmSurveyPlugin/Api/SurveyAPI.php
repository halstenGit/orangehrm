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

use DateTime;
use OpenApi\Annotations as OA;
use OrangeHRM\Core\Api\CommonParams;
use OrangeHRM\Core\Api\V2\CrudEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\ForbiddenException;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Survey\Api\Model\SurveyModel;
use OrangeHRM\Survey\Dto\SurveySearchFilterParams;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class SurveyAPI extends Endpoint implements CrudEndpoint
{
    use SurveyServiceTrait;
    use AuthUserTrait;

    public const PARAMETER_TITLE = 'title';
    public const PARAMETER_DESCRIPTION = 'description';
    public const PARAMETER_IS_ANONYMOUS = 'isAnonymous';
    public const PARAMETER_TARGET_TYPE = 'targetType';
    public const PARAMETER_STATUS = 'status';
    public const PARAMETER_IDS = 'ids';

    public const TITLE_MAX_LENGTH = 255;
    public const DESCRIPTION_MAX_LENGTH = 255;

    public const ALLOWED_TARGET_TYPES = [
        Survey::TARGET_ALL,
        Survey::TARGET_SUBUNIT,
        Survey::TARGET_JOB_TITLE,
        Survey::TARGET_SPECIFIC,
    ];

    /**
     * @OA\Post(
     *     path="/api/v2/survey/surveys",
     *     tags={"Survey/Surveys"},
     *     summary="Create a Survey",
     *     operationId="create-a-survey",
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="isAnonymous", type="boolean"),
     *             @OA\Property(property="targetType", type="string"),
     *             required={"title"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Survey-SurveyModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     * @throws ForbiddenException
     */
    public function create(): EndpointResult
    {
        $userRole = $this->getAuthUser()->getUserRoleName();
        if (!$this->getSurveyService()->canUserCreateSurvey((string)$userRole)) {
            throw $this->getForbiddenException();
        }

        $survey = new Survey();
        $survey->setStatus(Survey::STATUS_DRAFT);
        $survey->setCreatedAt(new DateTime());
        $this->setSurveyFields($survey);
        $survey->getDecorator()->setCreatedByUserId($this->getAuthUser()->getUserId());
        $this->getSurveyService()->saveSurvey($survey);

        return new EndpointResourceResult(SurveyModel::class, $survey);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_TITLE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::TITLE_MAX_LENGTH])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DESCRIPTION,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::DESCRIPTION_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_IS_ANONYMOUS,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_TARGET_TYPE,
                    new Rule(Rules::IN, [self::ALLOWED_TARGET_TYPES])
                )
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/surveys",
     *     tags={"Survey/Surveys"},
     *     summary="List All Surveys",
     *     operationId="list-all-surveys",
     *     @OA\Parameter(name="title", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(name="status", in="query", required=false, @OA\Schema(type="string")),
     *     @OA\Parameter(ref="#/components/parameters/sortOrder"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/Survey-SurveyModel")),
     *             @OA\Property(property="meta", type="object", @OA\Property(property="total", type="integer"))
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $filterParams = new SurveySearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        $filterParams->setTitle(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_TITLE)
        );
        $filterParams->setStatus(
            $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_QUERY, self::PARAMETER_STATUS)
        );

        $surveys = $this->getSurveyService()->getSurveyList($filterParams);
        $count = $this->getSurveyService()->getSurveyCount($filterParams);

        return new EndpointCollectionResult(
            SurveyModel::class,
            $surveys,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => $count])
        );
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_TITLE,
                    new Rule(Rules::STRING_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_STATUS,
                    new Rule(Rules::IN, [[Survey::STATUS_DRAFT, Survey::STATUS_PUBLISHED, Survey::STATUS_CLOSED]])
                )
            ),
            ...$this->getSortingAndPaginationParamsRules(SurveySearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/surveys/{id}",
     *     tags={"Survey/Surveys"},
     *     summary="Get a Survey",
     *     operationId="get-a-survey",
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Survey-SurveyModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $survey = $this->getSurveyService()->getSurveyById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        return new EndpointResourceResult(SurveyModel::class, $survey);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            )
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/survey/surveys/{id}",
     *     tags={"Survey/Surveys"},
     *     summary="Update a Survey",
     *     operationId="update-a-survey",
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="title", type="string"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="isAnonymous", type="boolean"),
     *             @OA\Property(property="targetType", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Survey-SurveyModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     * @throws ForbiddenException
     */
    public function update(): EndpointResult
    {
        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $survey = $this->getSurveyService()->getSurveyById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        if ($survey->getStatus() !== Survey::STATUS_DRAFT) {
            throw $this->getForbiddenException();
        }

        $this->setSurveyFields($survey);
        $this->getSurveyService()->saveSurvey($survey);

        return new EndpointResourceResult(SurveyModel::class, $survey);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_TITLE,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::TITLE_MAX_LENGTH])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_DESCRIPTION,
                    new Rule(Rules::STRING_TYPE),
                    new Rule(Rules::LENGTH, [null, self::DESCRIPTION_MAX_LENGTH])
                ),
                true
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_IS_ANONYMOUS,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_TARGET_TYPE,
                    new Rule(Rules::IN, [self::ALLOWED_TARGET_TYPES])
                )
            )
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/survey/surveys",
     *     tags={"Survey/Surveys"},
     *     summary="Delete Surveys",
     *     operationId="delete-surveys",
     *     @OA\RequestBody(ref="#/components/requestBodies/DeleteRequestBody"),
     *     @OA\Response(response="200", ref="#/components/responses/DeleteResponse"),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $ids = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_IDS);
        $this->throwRecordNotFoundExceptionIfEmptyIds($ids);
        $this->getSurveyService()->deleteSurveys($ids);

        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_IDS,
                new Rule(Rules::INT_ARRAY)
            )
        );
    }

    /**
     * @param Survey $survey
     */
    private function setSurveyFields(Survey $survey): void
    {
        $title = $this->getRequestParams()->getStringOrNull(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_TITLE);
        if ($title !== null) {
            $survey->setTitle($title);
        }

        $description = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_DESCRIPTION
        );
        $survey->setDescription($description);

        $isAnonymous = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_IS_ANONYMOUS
        );
        if ($isAnonymous !== null) {
            $survey->setIsAnonymous($isAnonymous);
        }

        $targetType = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_TARGET_TYPE
        );
        if ($targetType !== null) {
            $survey->setTargetType($targetType);
        }
    }
}
