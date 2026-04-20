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

namespace OrangeHRM\Survey\Api\Model;

use OrangeHRM\Core\Api\V2\Serializer\Normalizable;
use OrangeHRM\Entity\SurveyQuestion;
use OrangeHRM\Survey\Traits\Service\SurveyServiceTrait;

class SurveyQuestionModel implements Normalizable
{
    use SurveyServiceTrait;

    private SurveyQuestion $question;

    public function __construct(SurveyQuestion $question)
    {
        $this->question = $question;
    }

    public function toArray(): array
    {
        $options = $this->getSurveyService()->getOptionsByQuestionId($this->question->getId());

        return [
            'id' => $this->question->getId(),
            'questionText' => $this->question->getQuestionText(),
            'questionType' => $this->question->getQuestionType(),
            'sortOrder' => $this->question->getSortOrder(),
            'isRequired' => $this->question->isRequired(),
            'options' => array_map(
                static fn ($option) => [
                    'id' => $option->getId(),
                    'optionText' => $option->getOptionText(),
                    'sortOrder' => $option->getSortOrder(),
                ],
                $options
            ),
        ];
    }
}
