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
use OrangeHRM\Core\Api\V2\Model\ArrayModel;
use OrangeHRM\Core\Api\V2\ParameterBag;
use OrangeHRM\Core\Api\V2\RequestParams;
use OrangeHRM\Core\Api\V2\Validator\ParamRule;
use OrangeHRM\Core\Api\V2\Validator\ParamRuleCollection;
use OrangeHRM\Core\Api\V2\Validator\Rule;
use OrangeHRM\Core\Api\V2\Validator\Rules;
use OrangeHRM\Core\Traits\ORM\EntityManagerHelperTrait;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\JobTitle;
use OrangeHRM\Entity\Subunit;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Entity\SurveyTarget;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class SurveyTargetAPI extends Endpoint implements CrudEndpoint
{
    use SurveyServiceTrait;
    use EntityManagerHelperTrait;

    public const PARAMETER_SURVEY_ID = 'surveyId';
    public const PARAMETER_SUBUNIT_ID = 'subunitId';
    public const PARAMETER_JOB_TITLE_ID = 'jobTitleId';
    public const PARAMETER_EMP_NUMBER = 'empNumber';
    public const PARAMETER_IDS = 'ids';

    /**
     * @OA\Post(
     *     path="/api/v2/survey/surveys/{surveyId}/targets",
     *     tags={"Survey/Targets"},
     *     summary="Add a Target Entry to a Survey",
     *     operationId="add-a-target-entry-to-a-survey",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="subunitId", type="integer"),
     *             @OA\Property(property="jobTitleId", type="integer"),
     *             @OA\Property(property="empNumber", type="integer")
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

        $target = new SurveyTarget();
        $target->setSurvey($survey);

        $targetType = $survey->getTargetType();

        if ($targetType === Survey::TARGET_SUBUNIT) {
            $subunitId = $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_SUBUNIT_ID
            );
            if ($subunitId !== null) {
                $target->setSubunit($this->getReference(Subunit::class, $subunitId));
            }
        } elseif ($targetType === Survey::TARGET_JOB_TITLE) {
            $jobTitleId = $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_JOB_TITLE_ID
            );
            if ($jobTitleId !== null) {
                $target->setJobTitle($this->getReference(JobTitle::class, $jobTitleId));
            }
        } elseif ($targetType === Survey::TARGET_SPECIFIC) {
            $empNumber = $this->getRequestParams()->getIntOrNull(
                RequestParams::PARAM_TYPE_BODY,
                self::PARAMETER_EMP_NUMBER
            );
            if ($empNumber !== null) {
                $target->setEmployee($this->getReference(Employee::class, $empNumber));
            }
        }

        $this->getSurveyService()->saveSurveyTarget($target);

        return new EndpointResourceResult(
            ArrayModel::class,
            ['id' => $target->getId(), 'surveyId' => $surveyId]
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
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_SUBUNIT_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_JOB_TITLE_ID,
                    new Rule(Rules::POSITIVE)
                )
            ),
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_EMP_NUMBER,
                    new Rule(Rules::POSITIVE)
                )
            )
        );
    }

    /**
     * @OA\Get(
     *     path="/api/v2/survey/surveys/{surveyId}/targets",
     *     tags={"Survey/Targets"},
     *     summary="List Targets for a Survey",
     *     operationId="list-targets-for-a-survey",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\Response(
     *         response="200",
     *         description="Success",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
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

        $targets = $this->getSurveyService()->getTargetsBySurveyId($surveyId);

        $result = [];
        foreach ($targets as $target) {
            $entry = ['id' => $target->getId()];
            if ($target->getSubunit() !== null) {
                $entry['subunitId'] = $target->getSubunit()->getId();
            }
            if ($target->getJobTitle() !== null) {
                $entry['jobTitleId'] = $target->getJobTitle()->getId();
            }
            if ($target->getEmployee() !== null) {
                $entry['empNumber'] = $target->getEmployee()->getEmpNumber();
            }
            $result[] = $entry;
        }

        return new EndpointCollectionResult(
            ArrayModel::class,
            $result,
            new ParameterBag([CommonParams::PARAMETER_TOTAL => count($result)])
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
     * @OA\Delete(
     *     path="/api/v2/survey/surveys/{surveyId}/targets",
     *     tags={"Survey/Targets"},
     *     summary="Delete Survey Targets",
     *     operationId="delete-survey-targets",
     *     @OA\PathParameter(name="surveyId", @OA\Schema(type="integer")),
     *     @OA\RequestBody(ref="#/components/requestBodies/DeleteRequestBody"),
     *     @OA\Response(response="200", ref="#/components/responses/DeleteResponse"),
     *     @OA\Response(response="404", ref="#/components/responses/RecordNotFound")
     * )
     * @inheritDoc
     */
    public function delete(): EndpointResult
    {
        $surveyId = $this->getRequestParams()->getInt(
            RequestParams::PARAM_TYPE_ATTRIBUTE,
            self::PARAMETER_SURVEY_ID
        );
        $this->getSurveyService()->deleteTargetsBySurveyId($surveyId);

        return new EndpointResourceResult(ArrayModel::class, ['surveyId' => $surveyId]);
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
            $this->getValidationDecorator()->notRequiredParamRule(
                new ParamRule(
                    self::PARAMETER_IDS,
                    new Rule(Rules::INT_ARRAY)
                )
            )
        );
    }

    /**
     * @inheritDoc
     */
    public function getOne(): EndpointResult
    {
        throw $this->getNotImplementedException();
    }

    /**
     * @inheritDoc
     */
    public function getValidationRuleForGetOne(): ParamRuleCollection
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
}
