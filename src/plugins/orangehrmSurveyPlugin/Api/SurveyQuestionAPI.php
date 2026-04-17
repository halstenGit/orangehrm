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
use OrangeHRM\Entity\Survey;
use OrangeHRM\Entity\SurveyQuestion;
use OrangeHRM\Entity\SurveyQuestionOption;
use OrangeHRM\Survey\Api\Model\SurveyQuestionModel;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class SurveyQuestionAPI extends Endpoint implements CrudEndpoint
{
    use SurveyServiceTrait;

    public const PARAMETER_SURVEY_ID = 'surveyId';
    public const PARAMETER_QUESTION_TEXT = 'questionText';
    public const PARAMETER_QUESTION_TYPE = 'questionType';
    public const PARAMETER_SORT_ORDER = 'sortOrder';
    public const PARAMETER_IS_REQUIRED = 'isRequired';
    public const PARAMETER_OPTIONS = 'options';
    public const PARAMETER_IDS = 'ids';

    public const ALLOWED_QUESTION_TYPES = [
        SurveyQuestion::TYPE_TEXT,
        SurveyQuestion::TYPE_MULTIPLE_CHOICE,
        SurveyQuestion::TYPE_SCALE_5,
        SurveyQuestion::TYPE_SCALE_10,
        SurveyQuestion::TYPE_YES_NO,
    ];

    /**
     * @OA\Post(
     *     path="/api/v2/survey/surveys/{surveyId}/questions",
     *     tags={"Survey/Questions"},
     *     summary="Add a Question to a Survey",
     *     operationId="add-a-question-to-a-survey",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="questionText", type="string"),
     *             @OA\Property(property="questionType", type="string"),
     *             @OA\Property(property="sortOrder", type="integer"),
     *             @OA\Property(property="isRequired", type="boolean"),
     *             @OA\Property(property="options", type="array", @OA\Items(type="object")),
     *             required={"questionText","questionType"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Survey-SurveyQuestionModel"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     * @throws ForbiddenException
     */
    public function create(): EndpointResult
    {
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $survey = $this->getSurveyService()->getSurveyById($surveyId);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        if ($survey->getStatus() !== Survey::STATUS_DRAFT) {
            throw $this->getForbiddenException();
        }

        $question = new SurveyQuestion();
        $question->setSurvey($survey);
        $this->setQuestionFields($question);
        $this->getSurveyService()->saveQuestion($question);

        if ($question->getQuestionType() === SurveyQuestion::TYPE_MULTIPLE_CHOICE) {
            $this->saveOptions($question);
        }

        return new EndpointResourceResult(SurveyQuestionModel::class, $question);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForCreate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_QUESTION_TEXT,
                    new Rule(Rules::STRING_TYPE)
                )
            ),
            $this->getValidationDecorator()->requiredParamRule(
                new ParamRule(
                    self::PARAMETER_QUESTION_TYPE,
                    new Rule(Rules::IN, [self::ALLOWED_QUESTION_TYPES])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SORT_ORDER,
                    new Rule(Rules::INT_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_IS_REQUIRED,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_OPTIONS,
                    new Rule(Rules::ARRAY_TYPE)
                )
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/surveys/{surveyId}/questions",
     *     tags={"Survey/Questions"},
     *     summary="List Questions for a Survey",
     *     operationId="list-questions-for-a-survey",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\Parameter(ref="#/components/parameters/limit"),
     *     @OA\Parameter(ref="#/components/parameters/offset"),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(ref="#/components/schemas/Survey-SurveyQuestionModel")
     *             ),
     *             @OA\Property(property="meta", type="object", @OA\Property(property="total", type="integer"))
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $survey = $this->getSurveyService()->getSurveyById($surveyId);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        $questions = $this->getSurveyService()->getQuestionsBySurveyId($surveyId);

        return new EndpointCollectionResult(
            SurveyQuestionModel::class,
            $questions,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($questions)])
        );
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            ),
            ...$this->getSortingAndPaginationParamsRules([])
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/surveys/{surveyId}/questions/{id}",
     *     tags={"Survey/Questions"},
     *     summary="Get a Survey Question",
     *     operationId="get-a-survey-question",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Survey-SurveyQuestionModel"),
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
        $question = $this->getSurveyService()->getQuestionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($question, SurveyQuestion::class);

        return new EndpointResourceResult(SurveyQuestionModel::class, $question);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            )
        );
    }

    /**
     * @OA\Put(
     *     path="/api/v2/survey/surveys/{surveyId}/questions/{id}",
     *     tags={"Survey/Questions"},
     *     summary="Update a Survey Question",
     *     operationId="update-a-survey-question",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\PathParameter(name="id", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="questionText", type="string"),
     *             @OA\Property(property="questionType", type="string"),
     *             @OA\Property(property="sortOrder", type="integer"),
     *             @OA\Property(property="isRequired", type="boolean"),
     *             @OA\Property(property="options", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/Survey-SurveyQuestionModel"),
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
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $survey = $this->getSurveyService()->getSurveyById($surveyId);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        if ($survey->getStatus() !== Survey::STATUS_DRAFT) {
            throw $this->getForbiddenException();
        }

        $id = $this->getRequestParams()->getInt(RequestParams::PARAM_TYPE_ATTRIBUTE, CommonParams::PARAMETER_ID);
        $question = $this->getSurveyService()->getQuestionById($id);
        $this->throwRecordNotFoundExceptionIfNotExist($question, SurveyQuestion::class);

        $this->setQuestionFields($question);
        $this->getSurveyService()->saveQuestion($question);

        if ($question->getQuestionType() === SurveyQuestion::TYPE_MULTIPLE_CHOICE) {
            $this->getSurveyService()->deleteOptionsByQuestionId($question->getId());
            $this->saveOptions($question);
        }

        return new EndpointResourceResult(SurveyQuestionModel::class, $question);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForUpdate(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                CommonParams::PARAMETER_ID,
                new Rule(Rules::POSITIVE)
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_QUESTION_TEXT,
                    new Rule(Rules::STRING_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_QUESTION_TYPE,
                    new Rule(Rules::IN, [self::ALLOWED_QUESTION_TYPES])
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SORT_ORDER,
                    new Rule(Rules::INT_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_IS_REQUIRED,
                    new Rule(Rules::BOOL_TYPE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_OPTIONS,
                    new Rule(Rules::ARRAY_TYPE)
                )
            )
        );
    }

    /**
     * @OA\Delete(
     *     path="/api/v2/survey/surveys/{surveyId}/questions",
     *     tags={"Survey/Questions"},
     *     summary="Delete Survey Questions",
     *     operationId="delete-survey-questions",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
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
        $this->getSurveyService()->deleteQuestions($ids);

        return new EndpointResourceResult(ArrayModel::class, $ids);
    }

    /**
     * @return ParamRuleCollection
     */
    public function getValidationRuleForDelete(): ParamRuleCollection
    {
        return new ParamRuleCollection(
            new ParamRule(
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_IDS,
                new Rule(Rules::INT_ARRAY)
            )
        );
    }

    /**
     * @param SurveyQuestion $question
     */
    private function setQuestionFields(SurveyQuestion $question): void
    {
        $questionText = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_QUESTION_TEXT
        );
        if ($questionText !== null) {
            $question->setQuestionText($questionText);
        }

        $questionType = $this->getRequestParams()->getStringOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_QUESTION_TYPE
        );
        if ($questionType !== null) {
            $question->setQuestionType($questionType);
        }

        $sortOrder = $this->getRequestParams()->getIntOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_SORT_ORDER
        );
        if ($sortOrder !== null) {
            $question->setSortOrder($sortOrder);
        }

        $isRequired = $this->getRequestParams()->getBooleanOrNull(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_IS_REQUIRED
        );
        if ($isRequired !== null) {
            $question->setIsRequired($isRequired);
        }
    }

    /**
     * @param SurveyQuestion $question
     */
    private function saveOptions(SurveyQuestion $question): void
    {
        $options = $this->getRequestParams()->getArray(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_OPTIONS,
            []
        );

        foreach ($options as $index => $optionData) {
            $option = new SurveyQuestionOption();
            $option->setQuestion($question);
            $option->setOptionText($optionData['optionText'] ?? '');
            $option->setSortOrder((int)($optionData['sortOrder'] ?? $index));
            $this->getSurveyService()->saveQuestionOption($option);
        }
    }
}
