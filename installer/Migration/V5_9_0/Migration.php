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

namespace OrangeHRM\Installer\Migration\V5_9_0;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    /**
     * @inheritDoc
     */
    public function up(): void
    {
        $this->insertI18nGroup();
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'survey');

        $this->createSurveyTables();

        $this->getConnection()->createQueryBuilder()
            ->insert('ohrm_module')
            ->values(
                [
                    'name' => ':name',
                    'status' => ':status',
                    'display_name' => ':display_name',
                ]
            )
            ->setParameter('name', 'survey')
            ->setParameter('status', 1)
            ->setParameter('display_name', 'Survey')
            ->executeQuery();

        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screens.yaml');

        $this->insertMenuItems();
        $this->insertDefaultConfig();
    }

    /**
     * @inheritDoc
     */
    public function getVersion(): string
    {
        return '5.9.0';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if (is_null($this->langStringHelper)) {
            $this->langStringHelper = new LangStringHelper(
                $this->getConnection()
            );
        }
        return $this->langStringHelper;
    }

    private function insertI18nGroup(): void
    {
        $this->getConnection()->createQueryBuilder()
            ->insert('ohrm_i18n_group')
            ->values([
                'name' => ':name',
                'title' => ':title',
            ])
            ->setParameters([
                'name' => 'survey',
                'title' => 'Survey',
            ])
            ->executeQuery();
    }

    private function createSurveyTables(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_survey'])) {
            $this->getSchemaHelper()->createTable('ohrm_survey')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('title', Types::STRING, ['Notnull' => true, 'Length' => 255])
                ->addColumn('description', Types::TEXT, ['Notnull' => false])
                ->addColumn('status', Types::STRING, ['Notnull' => true, 'Length' => 20, 'Default' => 'draft'])
                ->addColumn('is_anonymous', Types::SMALLINT, ['Notnull' => true, 'Default' => 0])
                ->addColumn('target_type', Types::STRING, ['Notnull' => true, 'Length' => 30, 'Default' => 'all_employees'])
                ->addColumn('created_by', Types::INTEGER, ['Notnull' => false])
                ->addColumn('created_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('published_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('closed_at', Types::DATETIME_MUTABLE, ['Notnull' => false, 'Default' => null])
                ->addColumn('is_deleted', Types::SMALLINT, ['Notnull' => true, 'Default' => 0])
                ->setPrimaryKey(['id'])
                ->create();
            $foreignKeyConstraint = new ForeignKeyConstraint(
                ['created_by'],
                'ohrm_user',
                ['id'],
                'fk_survey_created_by',
                ['onDelete' => 'SET NULL']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey', $foreignKeyConstraint);
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_survey_question'])) {
            $this->getSchemaHelper()->createTable('ohrm_survey_question')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('survey_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('question_text', Types::TEXT, ['Notnull' => true])
                ->addColumn('question_type', Types::STRING, ['Notnull' => true, 'Length' => 30])
                ->addColumn('sort_order', Types::INTEGER, ['Notnull' => true, 'Default' => 0])
                ->addColumn('is_required', Types::SMALLINT, ['Notnull' => true, 'Default' => 0])
                ->setPrimaryKey(['id'])
                ->create();
            $foreignKeyConstraint = new ForeignKeyConstraint(
                ['survey_id'],
                'ohrm_survey',
                ['id'],
                'fk_survey_question_survey_id',
                ['onDelete' => 'CASCADE']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_question', $foreignKeyConstraint);
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_survey_question_option'])) {
            $this->getSchemaHelper()->createTable('ohrm_survey_question_option')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('question_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('option_text', Types::STRING, ['Notnull' => true, 'Length' => 500])
                ->addColumn('sort_order', Types::INTEGER, ['Notnull' => true, 'Default' => 0])
                ->setPrimaryKey(['id'])
                ->create();
            $foreignKeyConstraint = new ForeignKeyConstraint(
                ['question_id'],
                'ohrm_survey_question',
                ['id'],
                'fk_survey_question_option_question_id',
                ['onDelete' => 'CASCADE']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_question_option', $foreignKeyConstraint);
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_survey_target'])) {
            $this->getSchemaHelper()->createTable('ohrm_survey_target')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('survey_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('target_type', Types::STRING, ['Notnull' => true, 'Length' => 30])
                ->addColumn('target_id', Types::INTEGER, ['Notnull' => false])
                ->setPrimaryKey(['id'])
                ->create();
            $foreignKeyConstraint = new ForeignKeyConstraint(
                ['survey_id'],
                'ohrm_survey',
                ['id'],
                'fk_survey_target_survey_id',
                ['onDelete' => 'CASCADE']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_target', $foreignKeyConstraint);
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_survey_response'])) {
            $this->getSchemaHelper()->createTable('ohrm_survey_response')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('survey_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => false])
                ->addColumn('submitted_at', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->setPrimaryKey(['id'])
                ->create();
            $foreignKeyConstraint1 = new ForeignKeyConstraint(
                ['survey_id'],
                'ohrm_survey',
                ['id'],
                'fk_survey_response_survey_id',
                ['onDelete' => 'CASCADE']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_response', $foreignKeyConstraint1);
            $foreignKeyConstraint2 = new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'fk_survey_response_emp_number',
                ['onDelete' => 'SET NULL']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_response', $foreignKeyConstraint2);
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_survey_answer'])) {
            $this->getSchemaHelper()->createTable('ohrm_survey_answer')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('response_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('question_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('answer_text', Types::TEXT, ['Notnull' => false])
                ->addColumn('answer_option_id', Types::INTEGER, ['Notnull' => false])
                ->addColumn('answer_scale', Types::SMALLINT, ['Notnull' => false])
                ->addColumn('answer_yes_no', Types::BOOLEAN, ['Notnull' => false])
                ->setPrimaryKey(['id'])
                ->create();
            $foreignKeyConstraint1 = new ForeignKeyConstraint(
                ['response_id'],
                'ohrm_survey_response',
                ['id'],
                'fk_survey_answer_response_id',
                ['onDelete' => 'CASCADE']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_answer', $foreignKeyConstraint1);
            $foreignKeyConstraint2 = new ForeignKeyConstraint(
                ['question_id'],
                'ohrm_survey_question',
                ['id'],
                'fk_survey_answer_question_id',
                ['onDelete' => 'CASCADE']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_answer', $foreignKeyConstraint2);
            $foreignKeyConstraint3 = new ForeignKeyConstraint(
                ['answer_option_id'],
                'ohrm_survey_question_option',
                ['id'],
                'fk_survey_answer_option_id',
                ['onDelete' => 'SET NULL']
            );
            $this->getSchemaHelper()->addForeignKey('ohrm_survey_answer', $foreignKeyConstraint3);
        }
    }

    private function insertMenuItems(): void
    {
        if ($this->checkSurveyMenuExists()) {
            return;
        }

        $viewSurveyModuleScreenId = $this->getConnection()
            ->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :action_url')
            ->setParameter('action_url', 'viewSurveyModule')
            ->executeQuery()
            ->fetchOne();

        $this->insertMenuItem('Survey', $viewSurveyModuleScreenId, null, 1, 1400, 1, '{"icon":"survey"}');

        $surveyMenuItemId = $this->getConnection()
            ->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :menu_title')
            ->setParameter('menu_title', 'Survey')
            ->executeQuery()
            ->fetchOne();

        $surveysScreenId = $this->getMenuScreenId('Survey List');
        $this->insertMenuItem('Surveys', $surveysScreenId, $surveyMenuItemId, 2, 100, 1, null);

        $mySurveysScreenId = $this->getMenuScreenId('My Surveys');
        $this->insertMenuItem('My Surveys', $mySurveysScreenId, $surveyMenuItemId, 2, 200, 1, null);

        $this->insertMenuItem('Configuration', null, $surveyMenuItemId, 2, 300, 1, null);

        $surveyConfigMenuItemId = $this->getMenuParentId('Configuration', $surveyMenuItemId);

        $surveyConfigScreenId = $this->getMenuScreenId('Survey Configuration');
        $this->insertMenuItem('Survey Configuration', $surveyConfigScreenId, $surveyConfigMenuItemId, 3, 100, 1, null);
    }

    private function insertMenuItem(
        string $menuTitle,
        ?int $screenId,
        ?int $parentId,
        int $level,
        int $orderHint,
        int $status,
        ?string $additionalParams
    ): void {
        $this->getConnection()->createQueryBuilder()
            ->insert('ohrm_menu_item')
            ->values([
                'menu_title' => ':menu_title',
                'screen_id' => ':screen_id',
                'parent_id' => ':parent_id',
                'level' => ':level',
                'order_hint' => ':order_hint',
                'status' => ':status',
                'additional_params' => ':additional_params',
            ])
            ->setParameters([
                'menu_title' => $menuTitle,
                'screen_id' => $screenId,
                'parent_id' => $parentId,
                'level' => $level,
                'order_hint' => $orderHint,
                'status' => $status,
                'additional_params' => $additionalParams,
            ])
            ->executeQuery();
    }

    private function getMenuScreenId(string $name): ?int
    {
        $result = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('name = :name')
            ->setParameter('name', $name)
            ->executeQuery()
            ->fetchOne();
        return $result !== false ? (int)$result : null;
    }

    private function getMenuParentId(string $menuTitle, ?int $parentId): int
    {
        return (int)$this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :menu_title')
            ->setParameter('menu_title', $menuTitle)
            ->andWhere('parent_id = :parent_id')
            ->setParameter('parent_id', $parentId)
            ->executeQuery()
            ->fetchOne();
    }

    private function checkSurveyMenuExists(): bool
    {
        return (bool)$this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :menu_title')
            ->setParameter('menu_title', 'Survey')
            ->executeQuery()
            ->fetchOne();
    }

    private function insertDefaultConfig(): void
    {
        $this->getConnection()->createQueryBuilder()
            ->insert('hs_hr_config')
            ->values([
                'key' => ':key',
                'value' => ':value',
            ])
            ->setParameter('key', 'survey.allow_supervisor_create')
            ->setParameter('value', 'false')
            ->executeQuery();
    }
}
