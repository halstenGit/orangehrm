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

namespace OrangeHRM\Installer\Migration\V5_10_1;

use OrangeHRM\Installer\Util\V1\AbstractMigration;

class Migration extends AbstractMigration
{
    /**
     * Inserts Survey lang_string entries that the Vue components reference but
     * the original V5_9_0/lang-string/survey.yaml didn't ship.
     *
     * Uses INSERT IGNORE so it's safe to re-run.
     */
    public function up(): void
    {
        $surveyGroupId = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_i18n_group')
            ->where('name = :name')
            ->setParameter('name', 'survey')
            ->executeQuery()
            ->fetchOne();

        if (!$surveyGroupId) {
            return;
        }

        $missing = [
            'respond_to_survey' => 'Respond to Survey',
            'total_responses' => 'Total Responses',
            'no_responses_yet' => 'No responses yet',
            'no_answers' => 'No answers',
            'average' => 'Average',
            'questions_count' => 'Questions',
            'response_submitted_successfully' => 'Response submitted successfully',
            'your_answer' => 'Your Answer',
            'target_type' => 'Target Type',
            'created_at' => 'Created At',
            'allow_supervisors_create' => 'Allow Supervisors to Create',
        ];

        $stmt = $this->getConnection()->prepare(
            'INSERT IGNORE INTO ohrm_i18n_lang_string (group_id, unit_id, value, version) VALUES (?, ?, ?, ?)'
        );

        foreach ($missing as $unitId => $value) {
            $stmt->executeStatement([(int)$surveyGroupId, $unitId, $value, '5.10.1']);
        }
    }

    public function getVersion(): string
    {
        return '5.10.1';
    }
}
