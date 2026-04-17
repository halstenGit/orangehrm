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
 * @ORM\Table(name="ohrm_survey_answer")
 * @ORM\Entity
 */
class SurveyAnswer
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
     * @var SurveyResponse
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\SurveyResponse")
     * @ORM\JoinColumn(name="response_id", referencedColumnName="id", nullable=false)
     */
    private SurveyResponse $response;

    /**
     * @var SurveyQuestion
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\SurveyQuestion")
     * @ORM\JoinColumn(name="question_id", referencedColumnName="id", nullable=false)
     */
    private SurveyQuestion $question;

    /**
     * @var string|null
     *
     * @ORM\Column(name="answer_text", type="text", nullable=true)
     */
    private ?string $answerText = null;

    /**
     * @var SurveyQuestionOption|null
     *
     * @ORM\ManyToOne(targetEntity="OrangeHRM\Entity\SurveyQuestionOption")
     * @ORM\JoinColumn(name="answer_option_id", referencedColumnName="id", nullable=true)
     */
    private ?SurveyQuestionOption $answerOption = null;

    /**
     * @var int|null
     *
     * @ORM\Column(name="answer_scale", type="smallint", nullable=true)
     */
    private ?int $answerScale = null;

    /**
     * @var bool|null
     *
     * @ORM\Column(name="answer_yes_no", type="boolean", nullable=true)
     */
    private ?bool $answerYesNo = null;

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
     * @return SurveyResponse
     */
    public function getResponse(): SurveyResponse
    {
        return $this->response;
    }

    /**
     * @param SurveyResponse $response
     */
    public function setResponse(SurveyResponse $response): void
    {
        $this->response = $response;
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
     * @return string|null
     */
    public function getAnswerText(): ?string
    {
        return $this->answerText;
    }

    /**
     * @param string|null $answerText
     */
    public function setAnswerText(?string $answerText): void
    {
        $this->answerText = $answerText;
    }

    /**
     * @return SurveyQuestionOption|null
     */
    public function getAnswerOption(): ?SurveyQuestionOption
    {
        return $this->answerOption;
    }

    /**
     * @param SurveyQuestionOption|null $answerOption
     */
    public function setAnswerOption(?SurveyQuestionOption $answerOption): void
    {
        $this->answerOption = $answerOption;
    }

    /**
     * @return int|null
     */
    public function getAnswerScale(): ?int
    {
        return $this->answerScale;
    }

    /**
     * @param int|null $answerScale
     */
    public function setAnswerScale(?int $answerScale): void
    {
        $this->answerScale = $answerScale;
    }

    /**
     * @return bool|null
     */
    public function getAnswerYesNo(): ?bool
    {
        return $this->answerYesNo;
    }

    /**
     * @param bool|null $answerYesNo
     */
    public function setAnswerYesNo(?bool $answerYesNo): void
    {
        $this->answerYesNo = $answerYesNo;
    }
}
