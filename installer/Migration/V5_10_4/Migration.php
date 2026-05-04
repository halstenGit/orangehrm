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

namespace OrangeHRM\Installer\Migration\V5_10_4;

use OrangeHRM\Installer\Util\V1\AbstractMigration;
use OrangeHRM\Installer\Util\V1\LangStringHelper;

/**
 * Adds GAA lang_string unit_ids referenced by Vue (Catalogo, Historico,
 * PreencherSolicitacao, RevisaoTi) but absent from the V5_10_0/lang-string/gaa.yaml
 * seed: action enums (acao_*), confirmation prompts (confirmar_*) and the
 * "catalog OR custom" hint.
 *
 * Idempotent — relies on insertOrUpdateLangStrings semantics.
 */
class Migration extends AbstractMigration
{
    protected ?LangStringHelper $langStringHelper = null;

    public function up(): void
    {
        $this->getLangStringHelper()->insertOrUpdateLangStrings(__DIR__, 'gaa');
    }

    public function getVersion(): string
    {
        return '5.10.4';
    }

    private function getLangStringHelper(): LangStringHelper
    {
        if (is_null($this->langStringHelper)) {
            $this->langStringHelper = new LangStringHelper($this->getConnection());
        }
        return $this->langStringHelper;
    }
}
