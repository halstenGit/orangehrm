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
  <span :class="['orangehrm-survey-status-badge', badgeClass]">
    {{ label }}
  </span>
</template>

<script>
export default {
  props: {
    status: {
      type: String,
      required: true,
    },
  },

  computed: {
    badgeClass() {
      switch (this.status) {
        case 'PUBLISHED':
          return 'orangehrm-survey-status-badge--published';
        case 'CLOSED':
          return 'orangehrm-survey-status-badge--closed';
        case 'DRAFT':
        default:
          return 'orangehrm-survey-status-badge--draft';
      }
    },
    label() {
      switch (this.status) {
        case 'PUBLISHED':
          return this.$t('survey.published');
        case 'CLOSED':
          return this.$t('survey.closed');
        case 'DRAFT':
        default:
          return this.$t('survey.draft');
      }
    },
  },
};
</script>

<style scoped>
.orangehrm-survey-status-badge {
  display: inline-block;
  padding: 0.15rem 0.65rem;
  border-radius: 12px;
  font-size: 0.8rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.03em;
}
.orangehrm-survey-status-badge--draft {
  background-color: #f0f0f0;
  color: #555;
}
.orangehrm-survey-status-badge--published {
  background-color: #e8f5e9;
  color: #2e7d32;
}
.orangehrm-survey-status-badge--closed {
  background-color: #ffebee;
  color: #c62828;
}
</style>
