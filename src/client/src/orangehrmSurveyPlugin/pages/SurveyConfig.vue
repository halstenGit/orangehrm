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
        {{ $t('survey.survey_configuration') }}
      </oxd-text>

      <oxd-divider />

      <oxd-form :loading="isLoading" @submit-valid="onSave">
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <div class="orangehrm-sm-field">
              <oxd-text tag="p" class="orangehrm-sm-field-label">
                {{ $t('survey.allow_supervisors_create') }}
              </oxd-text>
              <oxd-switch-input v-model="config.allowSupervisorsToCreate" />
            </div>
          </oxd-grid-item>
        </oxd-grid>

        <oxd-divider />

        <oxd-form-actions>
          <submit-button />
        </oxd-form-actions>
      </oxd-form>
    </div>
  </div>
</template>

<script>
import {OxdSwitchInput} from '@ohrm/oxd';
import {APIService} from '@/core/util/services/api.service';

const initialConfig = {
  allowSupervisorsToCreate: false,
};

export default {
  components: {
    'oxd-switch-input': OxdSwitchInput,
  },

  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/survey/config',
    );
    return {http};
  },

  data() {
    return {
      isLoading: false,
      config: {...initialConfig},
    };
  },

  beforeMount() {
    this.isLoading = true;
    this.http
      .getAll()
      .then((response) => {
        const data = response.data?.data;
        if (data) {
          this.config = {
            allowSupervisorsToCreate: data.allowSupervisorsToCreate ?? false,
          };
        }
      })
      .finally(() => {
        this.isLoading = false;
      });
  },

  methods: {
    onSave() {
      this.isLoading = true;
      this.http
        .request({
          method: 'PUT',
          url: `${window.appGlobal.baseUrl}/api/v2/survey/config`,
          data: {
            allowSupervisorsToCreate: this.config.allowSupervisorsToCreate,
          },
        })
        .then(() => {
          return this.$toast.saveSuccess();
        })
        .finally(() => {
          this.isLoading = false;
        });
    },
  },
};
</script>
