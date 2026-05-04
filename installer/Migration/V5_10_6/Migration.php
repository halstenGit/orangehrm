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

namespace OrangeHRM\Installer\Migration\V5_10_6;

use OrangeHRM\Installer\Util\V1\AbstractMigration;

/**
 * Backfill: V5_10_5 forgot to register the API permission for the new
 * Menu Configuration endpoint, so /api/v2/admin/menu-items returned 403
 * even for Admins (the screen loaded but the AJAX call to populate it
 * was rejected at the data-group ACL layer).
 *
 * This migration only inserts the api.yaml — V5_10_5 had already inserted
 * the screen, lang_strings and menu item.
 */
class Migration extends AbstractMigration
{
    public function up(): void
    {
        $this->getDataGroupHelper()->insertApiPermissions(__DIR__ . '/permission/api.yaml');
    }

    public function getVersion(): string
    {
        return '5.10.6';
    }
}
