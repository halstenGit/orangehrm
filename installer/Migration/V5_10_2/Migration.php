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

namespace OrangeHRM\Installer\Migration\V5_10_2;

use OrangeHRM\Installer\Util\V1\AbstractMigration;

/**
 * Adds remaining Survey lang_string unit_ids referenced by Vue components
 * but not present in V5_9_0/lang-string/survey.yaml or V5_10_1.
 *
 * Idempotent (INSERT IGNORE). seed-pt-br.php picks up their pt_BR
 * translations from V5_9_0/translation/pt_BR.yaml on next boot.
 */
class Migration extends AbstractMigration
{
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
            'required' => 'Required',
            'published_date' => 'Published Date',
        ];

        $stmt = $this->getConnection()->prepare(
            'INSERT IGNORE INTO ohrm_i18n_lang_string (group_id, unit_id, value, version) VALUES (?, ?, ?, ?)'
        );

        foreach ($missing as $unitId => $value) {
            $stmt->executeStatement([(int)$surveyGroupId, $unitId, $value, '5.10.2']);
        }
    }

    public function getVersion(): string
    {
        return '5.10.2';
    }
}
