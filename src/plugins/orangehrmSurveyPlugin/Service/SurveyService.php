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

namespace OrangeHRM\Survey\Service;

use OrangeHRM\Core\Traits\Service\ConfigServiceTrait;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Entity\SurveyAnswer;
use OrangeHRM\Entity\SurveyQuestion;
use OrangeHRM\Entity\SurveyQuestionOption;
use OrangeHRM\Entity\SurveyResponse;
use OrangeHRM\Entity\SurveyTarget;
use OrangeHRM\Survey\Dao\SurveyDao;
use OrangeHRM\Survey\Dto\SurveySearchFilterParams;

class SurveyService
{
    use ConfigServiceTrait;

    /**
     * @var SurveyDao|null
     */
    protected ?SurveyDao $surveyDao = null;

    /**
     * @return SurveyDao
     */
    public function getSurveyDao(): SurveyDao
    {
        return $this->surveyDao ??= new SurveyDao();
    }

    /**
     * @param Survey $survey
     * @return Survey
     */
    public function saveSurvey(Survey $survey): Survey
    {
        return $this->getSurveyDao()->saveSurvey($survey);
    }

    /**
     * @param int $id
     * @return Survey|null
     */
    public function getSurveyById(int $id): ?Survey
    {
        return $this->getSurveyDao()->getSurveyById($id);
    }

    /**
     * @param SurveySearchFilterParams $surveySearchFilterParams
     * @return array
     */
    public function getSurveyList(SurveySearchFilterParams $surveySearchFilterParams): array
    {
        return $this->getSurveyDao()->getSurveyList($surveySearchFilterParams);
    }

    /**
     * @param SurveySearchFilterParams $surveySearchFilterParams
     * @return int
     */
    public function getSurveyCount(SurveySearchFilterParams $surveySearchFilterParams): int
    {
        return $this->getSurveyDao()->getSurveyCount($surveySearchFilterParams);
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteSurveys(array $ids): int
    {
        return $this->getSurveyDao()->deleteSurveys($ids);
    }

    /**
     * @param SurveyQuestion $question
     * @return SurveyQuestion
     */
    public function saveQuestion(SurveyQuestion $question): SurveyQuestion
    {
        return $this->getSurveyDao()->saveQuestion($question);
    }

    /**
     * @param int $id
     * @return SurveyQuestion|null
     */
    public function getQuestionById(int $id): ?SurveyQuestion
    {
        return $this->getSurveyDao()->getQuestionById($id);
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getQuestionsBySurveyId(int $surveyId): array
    {
        return $this->getSurveyDao()->getQuestionsBySurveyId($surveyId);
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteQuestions(array $ids): int
    {
        return $this->getSurveyDao()->deleteQuestions($ids);
    }

    /**
     * @param SurveyQuestionOption $option
     * @return SurveyQuestionOption
     */
    public function saveQuestionOption(SurveyQuestionOption $option): SurveyQuestionOption
    {
        return $this->getSurveyDao()->saveQuestionOption($option);
    }

    /**
     * @param int $questionId
     * @return array
     */
    public function getOptionsByQuestionId(int $questionId): array
    {
        return $this->getSurveyDao()->getOptionsByQuestionId($questionId);
    }

    /**
     * @param int $questionId
     * @return void
     */
    public function deleteOptionsByQuestionId(int $questionId): void
    {
        $this->getSurveyDao()->deleteOptionsByQuestionId($questionId);
    }

    /**
     * @param SurveyTarget $target
     * @return SurveyTarget
     */
    public function saveSurveyTarget(SurveyTarget $target): SurveyTarget
    {
        return $this->getSurveyDao()->saveSurveyTarget($target);
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getTargetsBySurveyId(int $surveyId): array
    {
        return $this->getSurveyDao()->getTargetsBySurveyId($surveyId);
    }

    /**
     * @param int $surveyId
     * @return void
     */
    public function deleteTargetsBySurveyId(int $surveyId): void
    {
        $this->getSurveyDao()->deleteTargetsBySurveyId($surveyId);
    }

    /**
     * @param SurveyResponse $response
     * @return SurveyResponse
     */
    public function saveResponse(SurveyResponse $response): SurveyResponse
    {
        return $this->getSurveyDao()->saveResponse($response);
    }

    /**
     * @param SurveyAnswer $answer
     * @return SurveyAnswer
     */
    public function saveAnswer(SurveyAnswer $answer): SurveyAnswer
    {
        return $this->getSurveyDao()->saveAnswer($answer);
    }

    /**
     * @param int $surveyId
     * @param int $empNumber
     * @return bool
     */
    public function hasEmployeeResponded(int $surveyId, int $empNumber): bool
    {
        return $this->getSurveyDao()->hasEmployeeResponded($surveyId, $empNumber);
    }

    /**
     * @param int $surveyId
     * @return int
     */
    public function getResponseCount(int $surveyId): int
    {
        return $this->getSurveyDao()->getResponseCount($surveyId);
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getResultsForSurvey(int $surveyId): array
    {
        return $this->getSurveyDao()->getResultsForSurvey($surveyId);
    }

    /**
     * @param int $surveyId
     * @return int[]
     */
    public function getTargetedEmployeeIds(int $surveyId): array
    {
        return $this->getSurveyDao()->getTargetedEmployeeIds($surveyId);
    }

    /**
     * Determines whether the given employee is in the target audience for the survey.
     *
     * @param int $surveyId
     * @param int $empNumber
     * @return bool
     */
    public function isEmployeeTargeted(int $surveyId, int $empNumber): bool
    {
        $targetedIds = $this->getSurveyDao()->getTargetedEmployeeIds($surveyId);
        return in_array($empNumber, $targetedIds, true);
    }

    /**
     * Determines whether a user with the given role is allowed to create surveys.
     * Admin users are always allowed. Supervisors may be allowed depending on the
     * `survey.allow_supervisor_create` configuration value. ESS users are never allowed.
     *
     * @param string $userRole
     * @return bool
     */
    public function canUserCreateSurvey(string $userRole): bool
    {
        if ($userRole === 'Admin') {
            return true;
        }
        if ($userRole === 'Supervisor') {
            $configValue = $this->getConfigService()->getConfigDao()->getValue('survey.allow_supervisor_create');
            return $configValue === 'true' || $configValue === '1';
        }
        return false;
    }
}
