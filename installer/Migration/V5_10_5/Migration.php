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

namespace OrangeHRM\Installer\Migration\V5_10_5;

use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

/**
 * Adds the "Menu Configuration" admin screen — a UI for reordering / hiding /
 * re-iconing top-level and second-level menu items without touching the DB.
 *
 * - Inserts the screen + Admin/Supervisor/ESS permissions.
 * - Inserts the corresponding ohrm_menu_item under the Admin → Configuration
 *   sub-tree (or Admin top-level if no Configuration parent exists).
 * - Seeds admin lang_string unit_ids consumed by MenuConfig.vue.
 *
 * Idempotent: skips menu insert if "Menu Configuration" already exists.
 */
class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->getDataGroupHelper()->insertScreenPermissions(__DIR__ . '/permission/screens.yaml');
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'admin');
        $this->insertMenuItem();
    }

    public function getVersion(): string
    {
        return '5.10.5';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if (is_null($this->langStringHelper)) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }

    private function insertMenuItem(): void
    {
        if ($this->menuExists()) {
            return;
        }

        $screenId = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_screen')
            ->where('action_url = :url')
            ->setParameter('url', 'menuConfig')
            ->executeQuery()
            ->fetchOne();
        if ($screenId === false) {
            return;
        }

        $parentId = $this->getConfigurationMenuId() ?? $this->getAdminMenuId();
        $level = $parentId !== null ? 2 : 1;
        $orderHint = $parentId !== null ? 1000 : 1600;

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
                'menu_title' => 'Menu Configuration',
                'screen_id' => (int)$screenId,
                'parent_id' => $parentId,
                'level' => $level,
                'order_hint' => $orderHint,
                'status' => 1,
                'additional_params' => null,
            ])
            ->executeQuery();
    }

    private function menuExists(): bool
    {
        return (bool)$this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->setParameter('title', 'Menu Configuration')
            ->executeQuery()
            ->fetchOne();
    }

    private function getConfigurationMenuId(): ?int
    {
        $id = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 2')
            ->setParameter('title', 'Configuration')
            ->executeQuery()
            ->fetchOne();
        return $id !== false ? (int)$id : null;
    }

    private function getAdminMenuId(): ?int
    {
        $id = $this->getConnection()->createQueryBuilder()
            ->select('id')
            ->from('ohrm_menu_item')
            ->where('menu_title = :title')
            ->andWhere('level = 1')
            ->setParameter('title', 'Admin')
            ->executeQuery()
            ->fetchOne();
        return $id !== false ? (int)$id : null;
    }
}
