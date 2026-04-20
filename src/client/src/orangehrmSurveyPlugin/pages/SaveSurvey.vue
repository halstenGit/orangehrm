<!--
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
 -->

<template>
  <div class="orangehrm-background-container">
    <div class="orangehrm-card-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ id ? $t('survey.edit_survey') : $t('survey.add_survey') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="survey.title"
              :label="$t('general.title')"
              :rules="rules.title"
              required
            />
          </oxd-grid-item>

          <oxd-grid-item class="--offset-row-2">
            <oxd-input-field
              v-model="survey.description"
              type="textarea"
              :label="$t('general.description')"
              :rules="rules.description"
            />
          </oxd-grid-item>

          <oxd-grid-item class="--offset-row-3">
            <oxd-input-field
              v-model="survey.targetType"
              type="select"
              :label="$t('survey.target_type')"
              :options="targetTypeOptions"
              :rules="rules.targetType"
              required
            />
          </oxd-grid-item>

          <oxd-grid-item class="--offset-row-4">
            <div class="orangehrm-sm-field">
              <oxd-text tag="p" class="orangehrm-sm-field-label">
                {{ $t('survey.anonymous') }}
              </oxd-text>
              <oxd-switch-input v-model="survey.anonymous" />
            </div>
          </oxd-grid-item>
        </oxd-grid>

        <oxd-divider />

        <oxd-form-actions>
          <required-text />
          <oxd-button
            display-type="ghost"
            :label="$t('general.cancel')"
            @click="onCancel"
          />
          <submit-button />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {OxdSwitchInput} from '@ohrm/oxd';
import {navigate} from '@ohrm/core/util/helper/navigation';
import {APIService} from '@/core/util/services/api.service';
import {
  required,
  shouldNotExceedCharLength,
} from '@ohrm/core/util/validation/rules';

const initialSurvey = {
  title: '',
  description: '',
  anonymous: false,
  targetType: null,
};

export default {
  components: {
    'oxd-switch-input': OxdSwitchInput,
  },

  props: {
    id: {
      type: Number,
      default: null,
    },
  },

  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/survey/surveys',
    );
    return {http};
  },

  data() {
    return {
      isLoading: false,
      survey: {...initialSurvey},
      targetTypeOptions: [
        {id: 'ALL', label: this.$t('survey.all_employees')},
        {id: 'SUBUNIT', label: this.$t('survey.by_department')},
        {id: 'JOB_TITLE', label: this.$t('survey.by_job_title')},
        {id: 'SPECIFIC', label: this.$t('survey.specific_employees')},
      ],
      rules: {
        title: [required, shouldNotExceedCharLength(200)],
        description: [shouldNotExceedCharLength(1000)],
        targetType: [required],
      },
    };
  },

  beforeMount() {
    if (this.id) {
      this.isLoading = true;
      this.http
        .get(this.id)
        .then((response) => {
          const {data} = response.data;
          this.survey = {
            title: data.title,
            description: data.description,
            anonymous: data.isAnonymous,
            targetType: this.targetTypeOptions.find(
              (opt) => opt.id === data.targetType,
            ) || null,
          };
        })
        .finally(() => {
          this.isLoading = false;
        });
    }
  },

  methods: {
    onCancel() {
      navigate('/survey/viewSurveys');
    },
    onSave() {
      this.isLoading = true;
      const payload = {
        title: this.survey.title.trim(),
        description: this.survey.description,
        isAnonymous: this.survey.anonymous,
        targetType: this.survey.targetType?.id || null,
      };

      const request = this.id
        ? this.http.update(this.id, payload)
        : this.http.create(payload);

      request
        .then((response) => {
          return this.$toast.saveSuccess().then(() => response);
        })
        .then((response) => {
          const savedId = this.id || response.data?.data?.id;
          if (savedId) {
            navigate('/survey/surveyBuilder/{id}', {id: savedId});
          } else {
            this.onCancel();
          }
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
