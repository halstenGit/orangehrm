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

import SurveyList from '@/orangehrmSurveyPlugin/pages/SurveyList.vue';
import SaveSurvey from '@/orangehrmSurveyPlugin/pages/SaveSurvey.vue';
import SurveyBuilder from '@/orangehrmSurveyPlugin/pages/SurveyBuilder.vue';
import SurveyConfig from '@/orangehrmSurveyPlugin/pages/SurveyConfig.vue';
import SurveyResults from '@/orangehrmSurveyPlugin/pages/SurveyResults.vue';
import MySurveys from '@/orangehrmSurveyPlugin/pages/MySurveys.vue';
import RespondSurvey from '@/orangehrmSurveyPlugin/pages/RespondSurvey.vue';

export default {
  'survey-list': SurveyList,
  'survey-create': SaveSurvey,
  'survey-edit': SaveSurvey,
  'survey-builder': SurveyBuilder,
  'survey-config': SurveyConfig,
  'survey-results': SurveyResults,
  'my-surveys': MySurveys,
  'survey-respond': RespondSurvey,
};
