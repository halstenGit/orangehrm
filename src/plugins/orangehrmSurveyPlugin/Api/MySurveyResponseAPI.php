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
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\Auth\AuthUserTrait;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Entity\SurveyAnswer;
use OrangeHRM\Entity\SurveyQuestion;
use OrangeHRM\Entity\SurveyQuestionOption;
use OrangeHRM\Entity\SurveyResponse;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class MySurveyResponseAPI extends Endpoint implements CrudEndpoint
{
    use SurveyServiceTrait;
    use AuthUserTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_SURVEY_ID = 'surveyId';
    public const PARAMETER_ANSWERS = 'answers';
    public const PARAMETER_HAS_RESPONDED = 'hasResponded';

    /**
     * @OA\Post(
     *     path="/api/v2/survey/my-surveys/{surveyId}/response",
     *     tags={"Survey/MySurveyResponse"},
     *     summary="Submit a Survey Response",
     *     operationId="submit-a-survey-response",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="answers", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="questionId", type="integer"),
     *                     @OA\Property(property="answerText", type="string"),
     *                     @OA\Property(property="answerOptionId", type="integer"),
     *                     @OA\Property(property="answerScale", type="integer"),
     *                     @OA\Property(property="answerYesNo", type="boolean")
     *                 )
     *             ),
     *             required={"answers"}
     *         )
     *     ),
     *     @OA\Response(response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     )
     * )
     * @inheritDoc
     */
    public function create(): EndpointResult
    {
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $survey = $this->getSurveyService()->getSurveyById($surveyId);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        $empNumber = $this->getAuthUser()->getEmpNumber();

        $response = new SurveyResponse();
        $response->setSurvey($survey);
        $response->setSubmittedAt(new DateTime());

        if ($survey->isAnonymous()) {
            $response->setEmployee(null);
        } else {
            $response->setEmployee($this->getReference(Employee::class, $empNumber));
        }

        $this->getSurveyService()->saveResponse($response);

        $answers = $this->getRequestParams()->getArray(RequestParams::PARAM_TYPE_BODY, self::PARAMETER_ANSWERS, []);

        foreach ($answers as $answerData) {
            $answer = new SurveyAnswer();
            $answer->setResponse($response);

            $questionId = $answerData['questionId'] ?? null;
            if ($questionId !== null) {
                $answer->setQuestion($this->getReference(SurveyQuestion::class, (int)$questionId));
            }

            $answerText = $answerData['answerText'] ?? null;
            if ($answerText !== null) {
                $answer->setAnswerText($answerText);
            }

            $answerOptionId = $answerData['answerOptionId'] ?? null;
            if ($answerOptionId !== null) {
                $answer->setAnswerOption($this->getReference(SurveyQuestionOption::class, (int)$answerOptionId));
            }

            $answerScale = $answerData['answerScale'] ?? null;
            if ($answerScale !== null) {
                $answer->setAnswerScale((int)$answerScale);
            }

            $answerYesNo = $answerData['answerYesNo'] ?? null;
            if ($answerYesNo !== null) {
                $answer->setAnswerYesNo((bool)$answerYesNo);
            }

            $this->getSurveyService()->saveAnswer($answer);
        }

        return new EndpointResourceResult(
            ArrayModel::class,
            ['responseId' => $response->getId(), 'surveyId' => $surveyId]
        );
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
                    self::PARAMETER_ANSWERS,
                    new Rule(Rules::ARRAY_TYPE)
                )
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/my-surveys/{surveyId}/response",
     *     tags={"Survey/MySurveyResponse"},
     *     summary="Check if Employee Has Responded to Survey",
     *     operationId="check-survey-response-status",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="hasResponded", type="boolean")
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
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $survey = $this->getSurveyService()->getSurveyById($surveyId);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        $empNumber = $this->getAuthUser()->getEmpNumber();
        $hasResponded = $this->getSurveyService()->hasEmployeeResponded($surveyId, $empNumber);

        return new EndpointResourceResult(
            ArrayModel::class,
            [self::PARAMETER_HAS_RESPONDED => $hasResponded]
        );
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
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getAll(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetAll(): ParamRuleCollection
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
