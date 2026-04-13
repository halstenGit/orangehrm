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
use OrangeHRM\Core\Api\V2\CollectionEndpoint;
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointCollectionResult;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Survey\Api\Model\SurveyModel;
use OrangeHRM\Survey\Api\Model\SurveyQuestionModel;
use OrangeHRM\Survey\Dto\MySurveySearchFilterParams;
use OrangeHRM\Survey\Dto\SurveySearchFilterParams;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class MySurveyAPI extends Endpoint implements CollectionEndpoint, ResourceEndpoint
{
    use SurveyServiceTrait;
    use AuthUserTrait;

    /**
     * @OA\Get(
     *     path="/api/v2/survey/my-surveys",
     *     tags={"Survey/MySurveys"},
     *     summary="List My Surveys",
     *     operationId="list-my-surveys",
     *     @OA\Parameter(ref="#/components/parameters/sortOrder"),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Survey-SurveyModel")
     *             ),
     *             @OA\Property(property="meta", type="object", @OA\Property(property="total", type="integer"))
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $empNumber = $this->getAuthUser()->getEmpNumber();

        $filterParams = new SurveySearchFilterParams();
        $this->setSortingAndPaginationParams($filterParams);
        // Fetch all PUBLISHED surveys, then filter by targeting
        $filterParams->setStatus(Survey::STATUS_PUBLISHED);
        $allPublished = $this->getSurveyService()->getSurveyList($filterParams);
        $surveys = array_filter(
            $allPublished,
            fn ($survey) => $this->getSurveyService()->isEmployeeTargeted($survey->getId(), $empNumber)
        );
        $surveys = array_values($surveys);
        $count = count($surveys);

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
            ...$this->getSortingAndPaginationParamsRules(SurveySearchFilterParams::ALLOWED_SORT_FIELDS)
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/my-surveys/{id}",
     *     tags={"Survey/MySurveys"},
     *     summary="Get a My Survey Detail",
     *     operationId="get-a-my-survey-detail",
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="survey", ref="#/components/schemas/Survey-SurveyModel"),
     *                 @OA\Property(property="questions", type="array",
     *                     @OA\Items(ref="#/components/schemas/Survey-SurveyQuestionModel")
     *                 )
     *             ),
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

        $questions = $this->getSurveyService()->getQuestionsBySurveyId($id);

        $normalizedQuestions = array_map(
            static fn ($q) => (new SurveyQuestionModel($q))->toArray(),
            $questions
        );

        return new EndpointResourceResult(
            ArrayModel::class,
            [
                'survey' => (new SurveyModel($survey))->toArray(),
                'questions' => $normalizedQuestions,
            ]
        );
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
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function update(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        throw $this->getNotImplementedException();
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
