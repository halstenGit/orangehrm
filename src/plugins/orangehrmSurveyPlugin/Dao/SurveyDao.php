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

namespace OrangeHRM\Survey\Dao;

use OrangeHRM\Core\Dao\BaseDao;
use OrangeHRM\Entity\Employee;
use OrangeHRM\Entity\Survey;
use OrangeHRM\Entity\SurveyAnswer;
use OrangeHRM\Entity\SurveyQuestion;
use OrangeHRM\Entity\SurveyQuestionOption;
use OrangeHRM\Entity\SurveyResponse;
use OrangeHRM\Entity\SurveyTarget;
use OrangeHRM\ORM\Paginator;
use OrangeHRM\Survey\Dto\SurveySearchFilterParams;

class SurveyDao extends BaseDao
{
    /**
     * @param Survey $survey
     * @return Survey
     */
    public function saveSurvey(Survey $survey): Survey
    {
        $this->persist($survey);
        return $survey;
    }

    /**
     * @param int $id
     * @return Survey|null
     */
    public function getSurveyById(int $id): ?Survey
    {
        return $this->getRepository(Survey::class)->findOneBy(['id' => $id, 'isDeleted' => false]);
    }

    /**
     * @param SurveySearchFilterParams $surveySearchFilterParams
     * @return array
     */
    public function getSurveyList(SurveySearchFilterParams $surveySearchFilterParams): array
    {
        $qb = $this->getSurveyPaginator($surveySearchFilterParams);
        return $qb->getQuery()->execute();
    }

    /**
     * @param SurveySearchFilterParams $surveySearchFilterParams
     * @return Paginator
     */
    protected function getSurveyPaginator(SurveySearchFilterParams $surveySearchFilterParams): Paginator
    {
        $q = $this->createQueryBuilder(Survey::class, 'survey');
        $this->setSortingAndPaginationParams($q, $surveySearchFilterParams);

        if (!is_null($surveySearchFilterParams->getTitle())) {
            $q->andWhere($q->expr()->like('survey.title', ':title'));
            $q->setParameter('title', '%' . $surveySearchFilterParams->getTitle() . '%');
        }
        if (!is_null($surveySearchFilterParams->getStatus())) {
            $q->andWhere('survey.status = :status');
            $q->setParameter('status', $surveySearchFilterParams->getStatus());
        }
        if (!is_null($surveySearchFilterParams->getCreatedById())) {
            $q->andWhere('survey.createdBy = :createdById');
            $q->setParameter('createdById', $surveySearchFilterParams->getCreatedById());
        }
        $q->andWhere('survey.isDeleted = :isDeleted');
        $q->setParameter('isDeleted', false);

        return $this->getPaginator($q);
    }

    /**
     * @param SurveySearchFilterParams $surveySearchFilterParams
     * @return int
     */
    public function getSurveyCount(SurveySearchFilterParams $surveySearchFilterParams): int
    {
        return $this->getSurveyPaginator($surveySearchFilterParams)->count();
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteSurveys(array $ids): int
    {
        $q = $this->createQueryBuilder(Survey::class, 'survey');
        $q->update()
            ->set('survey.isDeleted', ':isDeleted')
            ->where($q->expr()->in('survey.id', ':ids'))
            ->andWhere('survey.status = :status')
            ->setParameter('ids', $ids)
            ->setParameter('isDeleted', true)
            ->setParameter('status', Survey::STATUS_DRAFT);
        return $q->getQuery()->execute();
    }

    /**
     * @param SurveyQuestion $question
     * @return SurveyQuestion
     */
    public function saveQuestion(SurveyQuestion $question): SurveyQuestion
    {
        $this->persist($question);
        return $question;
    }

    /**
     * @param int $id
     * @return SurveyQuestion|null
     */
    public function getQuestionById(int $id): ?SurveyQuestion
    {
        return $this->getRepository(SurveyQuestion::class)->find($id);
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getQuestionsBySurveyId(int $surveyId): array
    {
        $q = $this->createQueryBuilder(SurveyQuestion::class, 'question');
        $q->andWhere('question.survey = :surveyId');
        $q->setParameter('surveyId', $surveyId);
        $q->orderBy('question.sortOrder', 'ASC');
        return $q->getQuery()->execute();
    }

    /**
     * @param int[] $ids
     * @return int
     */
    public function deleteQuestions(array $ids): int
    {
        $q = $this->createQueryBuilder(SurveyQuestion::class, 'question');
        $q->delete()
            ->where($q->expr()->in('question.id', ':ids'))
            ->setParameter('ids', $ids);
        return $q->getQuery()->execute();
    }

    /**
     * @param SurveyQuestionOption $option
     * @return SurveyQuestionOption
     */
    public function saveQuestionOption(SurveyQuestionOption $option): SurveyQuestionOption
    {
        $this->persist($option);
        return $option;
    }

    /**
     * @param int $questionId
     * @return array
     */
    public function getOptionsByQuestionId(int $questionId): array
    {
        $q = $this->createQueryBuilder(SurveyQuestionOption::class, 'option');
        $q->andWhere('option.question = :questionId');
        $q->setParameter('questionId', $questionId);
        $q->orderBy('option.sortOrder', 'ASC');
        return $q->getQuery()->execute();
    }

    /**
     * @param int $questionId
     * @return void
     */
    public function deleteOptionsByQuestionId(int $questionId): void
    {
        $q = $this->createQueryBuilder(SurveyQuestionOption::class, 'option');
        $q->delete()
            ->andWhere('option.question = :questionId')
            ->setParameter('questionId', $questionId);
        $q->getQuery()->execute();
    }

    /**
     * @param SurveyTarget $target
     * @return SurveyTarget
     */
    public function saveSurveyTarget(SurveyTarget $target): SurveyTarget
    {
        $this->persist($target);
        return $target;
    }

    /**
     * @param int $surveyId
     * @return array
     */
    public function getTargetsBySurveyId(int $surveyId): array
    {
        $q = $this->createQueryBuilder(SurveyTarget::class, 'target');
        $q->andWhere('target.survey = :surveyId');
        $q->setParameter('surveyId', $surveyId);
        return $q->getQuery()->execute();
    }

    /**
     * @param int $surveyId
     * @return void
     */
    public function deleteTargetsBySurveyId(int $surveyId): void
    {
        $q = $this->createQueryBuilder(SurveyTarget::class, 'target');
        $q->delete()
            ->andWhere('target.survey = :surveyId')
            ->setParameter('surveyId', $surveyId);
        $q->getQuery()->execute();
    }

    /**
     * @param SurveyResponse $response
     * @return SurveyResponse
     */
    public function saveResponse(SurveyResponse $response): SurveyResponse
    {
        $this->persist($response);
        return $response;
    }

    /**
     * @param SurveyAnswer $answer
     * @return SurveyAnswer
     */
    public function saveAnswer(SurveyAnswer $answer): SurveyAnswer
    {
        $this->persist($answer);
        return $answer;
    }

    /**
     * @param int $surveyId
     * @param int $empNumber
     * @return bool
     */
    public function hasEmployeeResponded(int $surveyId, int $empNumber): bool
    {
        $qb = $this->createQueryBuilder(SurveyResponse::class, 'response');
        $qb->andWhere('response.survey = :surveyId');
        $qb->setParameter('surveyId', $surveyId);
        $qb->andWhere('response.employee = :empNumber');
        $qb->setParameter('empNumber', $empNumber);
        return $this->getPaginator($qb)->count() > 0;
    }

    /**
     * @param int $surveyId
     * @return int
     */
    public function getResponseCount(int $surveyId): int
    {
        $qb = $this->createQueryBuilder(SurveyResponse::class, 'response');
        $qb->andWhere('response.survey = :surveyId');
        $qb->setParameter('surveyId', $surveyId);
        return $this->getPaginator($qb)->count();
    }

    /**
     * Returns aggregated results for each question in the survey.
     * For TEXT questions: array of answer_text values.
     * For MULTIPLE_CHOICE questions: option counts keyed by option id.
     * For SCALE_5/SCALE_10 questions: average and scale value distribution.
     * For YES_NO questions: yes count and no count.
     *
     * @param int $surveyId
     * @return array
     */
    public function getResultsForSurvey(int $surveyId): array
    {
        $questions = $this->getQuestionsBySurveyId($surveyId);
        $results = [];

        foreach ($questions as $question) {
            $questionId = $question->getId();
            $questionType = $question->getQuestionType();

            $entry = [
                'question_id' => $questionId,
                'question_text' => $question->getQuestionText(),
                'question_type' => $questionType,
            ];

            $qb = $this->createQueryBuilder(SurveyAnswer::class, 'answer');
            $qb->andWhere('answer.question = :questionId');
            $qb->setParameter('questionId', $questionId);
            $answers = $qb->getQuery()->execute();

            if ($questionType === SurveyQuestion::TYPE_TEXT) {
                $texts = [];
                foreach ($answers as $answer) {
                    if ($answer->getAnswerText() !== null) {
                        $texts[] = $answer->getAnswerText();
                    }
                }
                $entry['answers'] = $texts;
            } elseif ($questionType === SurveyQuestion::TYPE_MULTIPLE_CHOICE) {
                $optionCounts = [];
                foreach ($answers as $answer) {
                    $option = $answer->getAnswerOption();
                    if ($option !== null) {
                        $optionId = $option->getId();
                        $optionCounts[$optionId] = ($optionCounts[$optionId] ?? 0) + 1;
                    }
                }
                $entry['option_counts'] = $optionCounts;
            } elseif (
                $questionType === SurveyQuestion::TYPE_SCALE_5
                || $questionType === SurveyQuestion::TYPE_SCALE_10
            ) {
                $scaleValues = [];
                $distribution = [];
                foreach ($answers as $answer) {
                    $scale = $answer->getAnswerScale();
                    if ($scale !== null) {
                        $scaleValues[] = $scale;
                        $distribution[$scale] = ($distribution[$scale] ?? 0) + 1;
                    }
                }
                $entry['average'] = count($scaleValues) > 0
                    ? array_sum($scaleValues) / count($scaleValues)
                    : null;
                $entry['distribution'] = $distribution;
            } elseif ($questionType === SurveyQuestion::TYPE_YES_NO) {
                $yesCount = 0;
                $noCount = 0;
                foreach ($answers as $answer) {
                    $yesNo = $answer->getAnswerYesNo();
                    if ($yesNo === true) {
                        $yesCount++;
                    } elseif ($yesNo === false) {
                        $noCount++;
                    }
                }
                $entry['yes_count'] = $yesCount;
                $entry['no_count'] = $noCount;
            }

            $results[] = $entry;
        }

        return $results;
    }

    /**
     * Resolves the target audience for a survey to a list of employee numbers.
     * Target types:
     *   ALL      — all active (non-terminated) employees
     *   SUBUNIT  — employees in specified subunits from the target table
     *   JOB_TITLE — employees with specified job titles from the target table
     *   SPECIFIC — specific employees listed in the target table
     *
     * @param int $surveyId
     * @return int[]
     */
    public function getTargetedEmployeeIds(int $surveyId): array
    {
        $survey = $this->getSurveyById($surveyId);
        if ($survey === null) {
            return [];
        }

        $targetType = $survey->getTargetType();

        if ($targetType === Survey::TARGET_ALL) {
            $qb = $this->createQueryBuilder(Employee::class, 'employee');
            $qb->select('employee.empNumber')
                ->andWhere($qb->expr()->isNull('employee.employeeTerminationRecord'));
            return $qb->getQuery()->getSingleColumnResult();
        }

        if ($targetType === Survey::TARGET_SUBUNIT) {
            $targets = $this->getTargetsBySurveyId($surveyId);
            $subunitIds = [];
            foreach ($targets as $target) {
                if ($target->getSubunit() !== null) {
                    $subunitIds[] = $target->getSubunit()->getId();
                }
            }
            if (empty($subunitIds)) {
                return [];
            }
            $qb = $this->createQueryBuilder(Employee::class, 'employee');
            $qb->select('employee.empNumber')
                ->andWhere($qb->expr()->in('employee.subDivision', ':subunitIds'))
                ->andWhere($qb->expr()->isNull('employee.employeeTerminationRecord'))
                ->setParameter('subunitIds', $subunitIds);
            return $qb->getQuery()->getSingleColumnResult();
        }

        if ($targetType === Survey::TARGET_JOB_TITLE) {
            $targets = $this->getTargetsBySurveyId($surveyId);
            $jobTitleIds = [];
            foreach ($targets as $target) {
                if ($target->getJobTitle() !== null) {
                    $jobTitleIds[] = $target->getJobTitle()->getId();
                }
            }
            if (empty($jobTitleIds)) {
                return [];
            }
            $qb = $this->createQueryBuilder(Employee::class, 'employee');
            $qb->select('employee.empNumber')
                ->andWhere($qb->expr()->in('employee.jobTitle', ':jobTitleIds'))
                ->andWhere($qb->expr()->isNull('employee.employeeTerminationRecord'))
                ->setParameter('jobTitleIds', $jobTitleIds);
            return $qb->getQuery()->getSingleColumnResult();
        }

        if ($targetType === Survey::TARGET_SPECIFIC) {
            $targets = $this->getTargetsBySurveyId($surveyId);
            $empNumbers = [];
            foreach ($targets as $target) {
                if ($target->getEmployee() !== null) {
                    $empNumbers[] = $target->getEmployee()->getEmpNumber();
                }
            }
            return $empNumbers;
        }

        return [];
    }
}
