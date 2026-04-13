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
use OrangeHRM\Core\Api\V2\Endpoint;
use OrangeHRM\Core\Api\V2\EndpointResourceResult;
use OrangeHRM\Core\Api\V2\EndpointResult;
use OrangeHRM\Core\Api\V2\Exception\InvalidParamException;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\ResourceEndpoint;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Survey\Api\Model\SurveyModel;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class SurveyActionAPI extends Endpoint implements ResourceEndpoint
{
    use SurveyServiceTrait;

    public const PARAMETER_SURVEY_ID = 'surveyId';
    public const PARAMETER_ACTION = 'action';

    public const ACTION_PUBLISH = 'publish';
    public const ACTION_CLOSE = 'close';

    public const ALLOWED_ACTIONS = [self::ACTION_PUBLISH, self::ACTION_CLOSE];

    /**
     * @OA\Put(
     *     path="/api/v2/survey/surveys/{surveyId}/action",
     *     tags={"Survey/Surveys"},
     *     summary="Perform an Action on a Survey",
     *     operationId="perform-an-action-on-a-survey",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="action", type="string", enum={"publish", "close"}),
     *             required={"action"}
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
     * @throws InvalidParamException
     */
    public function update(): EndpointResult
    {
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $action = $this->getRequestParams()->getString(
            RequestParams::PARAM_TYPE_BODY,
            self::PARAMETER_ACTION
        );

        $survey = $this->getSurveyService()->getSurveyById($surveyId);
        $this->throwRecordNotFoundExceptionIfNotExist($survey, Survey::class);

        if ($action === self::ACTION_PUBLISH) {
            if ($survey->getStatus() !== Survey::STATUS_DRAFT) {
                throw $this->getInvalidParamException(self::PARAMETER_ACTION);
            }
            $survey->setStatus(Survey::STATUS_PUBLISHED);
            $survey->setPublishedAt(new DateTime());
        } elseif ($action === self::ACTION_CLOSE) {
            if ($survey->getStatus() !== Survey::STATUS_PUBLISHED) {
                throw $this->getInvalidParamException(self::PARAMETER_ACTION);
            }
            $survey->setStatus(Survey::STATUS_CLOSED);
            $survey->setClosedAt(new DateTime());
        }

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
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            ),
            new ParamRule(
                self::PARAMETER_ACTION,
                new Rule(Rules::IN, [self::ALLOWED_ACTIONS])
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/surveys/{surveyId}/action",
     *     tags={"Survey/Surveys"},
     *     summary="Get Survey Status",
     *     operationId="get-survey-status",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
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
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $survey = $this->getSurveyService()->getSurveyById($surveyId);
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
                self::PARAMETER_SURVEY_ID,
                new Rule(Rules::POSITIVE)
            )
        );
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
