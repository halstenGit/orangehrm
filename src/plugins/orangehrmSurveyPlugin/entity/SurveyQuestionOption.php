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

namespace OrangeHRM\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="ohrm_survey_question_option")
 * @ORM\Entity
 */
class SurveyQuestionOption
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private int $id;

    /**
     * @var SurveyQuestion
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\SurveyQuestion")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=false)
     */
    private SurveyQuestion $question;

    /**
     * @var string
     *
     * @ORM\Column(name="option_text", type="string", nullable=false, length=255)
     */
    private string $optionText;

    /**
     * @var int
     *
     * @ORM\Column(name="sort_order", type="integer", nullable=false, options={"default": 0})
     */
    private int $sortOrder = 0;

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id): void
    {
        $this->id = $id;
    }

    /**
     * @return SurveyQuestion
     */
    public function getQuestion(): SurveyQuestion
    {
        return $this->question;
    }

    /**
     * @param SurveyQuestion $question
     */
    public function setQuestion(SurveyQuestion $question): void
    {
        $this->question = $question;
    }

    /**
     * @return string
     */
    public function getOptionText(): string
    {
        return $this->optionText;
    }

    /**
     * @param string $optionText
     */
    public function setOptionText(string $optionText): void
    {
        $this->optionText = $optionText;
    }

    /**
     * @return int
     */
    public function getSortOrder(): int
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     */
    public function setSortOrder(int $sortOrder): void
    {
        $this->sortOrder = $sortOrder;
    }
}
