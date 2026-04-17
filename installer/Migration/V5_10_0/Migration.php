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

namespace OrangeHRM\Installer\Migration\V5_10_0;

use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Types\Types;
use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->insertI18nGroup();
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'gaa');

        $this->createGaaTables();

        $this->getConnection()->createQueryBuilder()
            ->insert('ohrm_module')
            ->values([
                'name' => ':name',
                'status' => ':status',
                'display_name' => ':display_name',
            ])
            ->setParameter('name', 'gaa')
            ->setParameter('status', 1)
            ->setParameter('display_name', 'GAA')
            ->executeQuery();

        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screens.yaml');

        $this->insertMenuItems();
    }

    public function getVersion(): string
    {
        return '5.10.0';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if (is_null($this->langStringHelper)) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
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
                'name' => 'gaa',
                'title' => 'GAA',
            ])
            ->executeQuery();
    }

    private function createGaaTables(): void
    {
        if (!$this->getSchemaHelper()->tableExists(['ohrm_gaa_catalogo'])) {
            $this->getSchemaHelper()->createTable('ohrm_gaa_catalogo')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('tipo_item', Types::STRING, ['Notnull' => true, 'Length' => 20])
                ->addColumn('nome', Types::STRING, ['Notnull' => true, 'Length' => 255])
                ->addColumn('descricao', Types::TEXT, ['Notnull' => false])
                ->addColumn('quantidade_padrao', Types::INTEGER, ['Notnull' => true, 'Default' => 1])
                ->addColumn('ativo', Types::SMALLINT, ['Notnull' => true, 'Default' => 1])
                ->addColumn('criado_de_solicitacao_item_id', Types::INTEGER, ['Notnull' => false])
                ->addColumn('criado_em', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->setPrimaryKey(['id'])
                ->create();
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_gaa_solicitacao'])) {
            $this->getSchemaHelper()->createTable('ohrm_gaa_solicitacao')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('emp_number', Types::INTEGER, ['Notnull' => true])
                ->addColumn('tipo', Types::STRING, ['Notnull' => true, 'Length' => 20])
                ->addColumn('status', Types::STRING, ['Notnull' => true, 'Length' => 30, 'Default' => 'PENDENTE_LIDER'])
                ->addColumn('lider_emp_number', Types::INTEGER, ['Notnull' => false])
                ->addColumn('criado_em', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('atualizado_em', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->addColumn('concluido_em', Types::DATETIME_MUTABLE, ['Notnull' => false])
                ->addColumn('observacoes', Types::TEXT, ['Notnull' => false])
                ->setPrimaryKey(['id'])
                ->create();
            $this->getSchemaHelper()->addForeignKey('ohrm_gaa_solicitacao', new ForeignKeyConstraint(
                ['emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'fk_gaa_solicitacao_emp_number',
                ['onDelete' => 'CASCADE']
            ));
            $this->getSchemaHelper()->addForeignKey('ohrm_gaa_solicitacao', new ForeignKeyConstraint(
                ['lider_emp_number'],
                'hs_hr_employee',
                ['emp_number'],
                'fk_gaa_solicitacao_lider',
                ['onDelete' => 'SET NULL']
            ));
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_gaa_solicitacao_item'])) {
            $this->getSchemaHelper()->createTable('ohrm_gaa_solicitacao_item')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('solicitacao_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('catalogo_id', Types::INTEGER, ['Notnull' => false])
                ->addColumn('label_custom', Types::STRING, ['Notnull' => false, 'Length' => 255])
                ->addColumn('tipo_item', Types::STRING, ['Notnull' => true, 'Length' => 20])
                ->addColumn('quantidade', Types::INTEGER, ['Notnull' => true, 'Default' => 1])
                ->addColumn('status', Types::STRING, ['Notnull' => true, 'Length' => 30, 'Default' => 'PENDENTE_LIDER'])
                ->addColumn('motivo_rejeicao', Types::TEXT, ['Notnull' => false])
                ->addColumn('observacoes', Types::TEXT, ['Notnull' => false])
                ->setPrimaryKey(['id'])
                ->create();
            $this->getSchemaHelper()->addForeignKey('ohrm_gaa_solicitacao_item', new ForeignKeyConstraint(
                ['solicitacao_id'],
                'ohrm_gaa_solicitacao',
                ['id'],
                'fk_gaa_item_solicitacao',
                ['onDelete' => 'CASCADE']
            ));
            $this->getSchemaHelper()->addForeignKey('ohrm_gaa_solicitacao_item', new ForeignKeyConstraint(
                ['catalogo_id'],
                'ohrm_gaa_catalogo',
                ['id'],
                'fk_gaa_item_catalogo',
                ['onDelete' => 'SET NULL']
            ));
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_gaa_historico'])) {
            $this->getSchemaHelper()->createTable('ohrm_gaa_historico')
                ->addColumn('id', Types::INTEGER, ['Autoincrement' => true])
                ->addColumn('item_id', Types::INTEGER, ['Notnull' => true])
                ->addColumn('user_id', Types::INTEGER, ['Notnull' => false])
                ->addColumn('acao', Types::STRING, ['Notnull' => true, 'Length' => 50])
                ->addColumn('payload_json', Types::TEXT, ['Notnull' => false])
                ->addColumn('comentario', Types::TEXT, ['Notnull' => false])
                ->addColumn('criado_em', Types::DATETIME_MUTABLE, ['Notnull' => true])
                ->setPrimaryKey(['id'])
                ->create();
            $this->getSchemaHelper()->addForeignKey('ohrm_gaa_historico', new ForeignKeyConstraint(
                ['item_id'],
                'ohrm_gaa_solicitacao_item',
                ['id'],
                'fk_gaa_historico_item',
                ['onDelete' => 'CASCADE']
            ));
            $this->getSchemaHelper()->addForeignKey('ohrm_gaa_historico', new ForeignKeyConstraint(
                ['user_id'],
                'ohrm_user',
                ['id'],
                'fk_gaa_historico_user',
                ['onDelete' => 'SET NULL']
            ));
        }

        if (!$this->getSchemaHelper()->tableExists(['ohrm_gaa_solicitacao'])) {
            return;
        }
        $this->getSchemaHelper()->addForeignKey('ohrm_gaa_catalogo', new ForeignKeyConstraint(
            ['criado_de_solicitacao_item_id'],
            'ohrm_gaa_solicitacao_item',
            ['id'],
            'fk_gaa_catalogo_origem_item',
            ['onDelete' => 'SET NULL']
        ));
    }

    private function insertMenuItems(): void
    {
        if ($this->checkGaaMenuExists()) {
            return;
        }

        $viewGaaModuleScreenId = $this->getConnection()
            ->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :action_url')
            ->setParameter('action_url', 'viewGaaModule')
            ->executeQuery()
            ->fetchOne();

        $this->insertMenuItem('GAA', $viewGaaModuleScreenId, null, 1, 1500, 1, '{"icon":"laptop"}');

        $gaaMenuItemId = $this->getConnection()
            ->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :menu_title')
            ->setParameter('menu_title', 'GAA')
            ->executeQuery()
            ->fetchOne();

        $minhasPendenciasScreenId = $this->getMenuScreenId('GAA Minhas Pendencias');
        $this->insertMenuItem('Minhas Pendências', $minhasPendenciasScreenId, $gaaMenuItemId, 2, 100, 1, null);

        $revisaoTiScreenId = $this->getMenuScreenId('GAA Revisao TI');
        $this->insertMenuItem('Revisão TI', $revisaoTiScreenId, $gaaMenuItemId, 2, 200, 1, null);

        $catalogoScreenId = $this->getMenuScreenId('GAA Catalogo');
        $this->insertMenuItem('Catálogo', $catalogoScreenId, $gaaMenuItemId, 2, 300, 1, null);

        $historicoScreenId = $this->getMenuScreenId('GAA Historico');
        $this->insertMenuItem('Histórico', $historicoScreenId, $gaaMenuItemId, 2, 400, 1, null);
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

    private function checkGaaMenuExists(): bool
    {
        return (bool)$this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :menu_title')
            ->setParameter('menu_title', 'GAA')
            ->executeQuery()
            ->fetchOne();
    }
}
