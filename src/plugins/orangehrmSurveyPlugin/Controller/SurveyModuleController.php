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

namespace OrangeHRM\Survey\Controller;

use OrangeHRM\Core\Controller\AbstractModuleController;
use OrangeHRM\Core\Traits\UserRoleManagerTrait;
use OrangeHRM\Framework\Http\RedirectResponse;

class SurveyModuleController extends AbstractModuleController
{
    use UserRoleManagerTrait;

    /**
     * @inheritDoc
     */
    public function handle(): RedirectResponse
    {
        $defaultPath = $this->getUserRoleManager()->getModuleDefaultPage('survey');
        // Fallback when no default-page mapping exists for 'survey' in
        // ohrm_home_page / ohrm_module_default_page (the V5_9_0 migration
        // didn't seed one). Point to the survey list by default.
        if (empty($defaultPath)) {
            $defaultPath = 'survey/viewSurveys';
        }
        return $this->redirect($defaultPath);
    }
}
