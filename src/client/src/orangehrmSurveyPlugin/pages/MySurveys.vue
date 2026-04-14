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
  <div class="orangehrm-paper-container">
    <div class="orangehrm-header-container">
      <oxd-text tag="h6" class="orangehrm-main-title">
        {{ $t('survey.my_surveys') }}
      </oxd-text>
    </div>
    <table-header
      :total="total"
      :loading="isLoading"
      :selected="0"
    />
    <div class="orangehrm-container">
      <oxd-card-table
        :items="items.data"
        :headers="headers"
        :selectable="false"
        :clickable="false"
        :loading="isLoading"
        row-decorator="oxd-table-decorator-card"
      />
    </div>
    <div class="orangehrm-bottom-container">
      <oxd-pagination
        v-if="showPaginator"
        v-model:current="currentPage"
        :length="pages"
      />
    </div>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import usePaginate from '@ohrm/core/util/composable/usePaginate';
import {navigate} from '@/core/util/helper/navigation';

export default {
  setup() {
    const http = new APIService(
      window.appGlobal.baseUrl,
      '/api/v2/survey/my-surveys',
    );

    const {
      showPaginator,
      currentPage,
      total,
      pages,
      pageSize,
      response,
      isLoading,
      execQuery,
    } = usePaginate(http);

    return {
      http,
      showPaginator,
      currentPage,
      isLoading,
      total,
      pages,
      pageSize,
      execQuery,
      items: response,
    };
  },

  data() {
    return {
      headers: [
        {
          name: 'title',
          title: this.$t('general.title'),
          style: {flex: 3},
        },
        {
          name: 'publishedAt',
          title: this.$t('survey.published_date'),
          style: {flex: 2},
        },
        {
          name: 'responseStatus',
          title: this.$t('general.status'),
          style: {flex: 2},
        },
        {
          name: 'actions',
          title: this.$t('general.actions'),
          slot: 'action',
          style: {flex: 1},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            respond: {
              onClick: this.onClickRespond,
              component: 'oxd-button',
              props: {
                label: this.$t('survey.respond'),
                displayType: 'secondary',
                size: 'medium',
              },
              condition: (item) => item.responseStatus !== 'RESPONDED',
            },
          },
        },
      ],
    };
  },

  methods: {
    onClickRespond(item) {
      navigate('/survey/respondSurvey/{id}', {id: item.id});
    },
  },
};
</script>
