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
        {{ $t('gaa.minhas_pendencias') }}
      </oxd-text>
    </div>
    <div class="orangehrm-container">
      <oxd-card-table
        :items="items"
        :headers="headers"
        :selectable="false"
        :clickable="false"
        :loading="isLoading"
        row-decorator="oxd-table-decorator-card"
      />
      <div v-if="!isLoading && items.length === 0" class="orangehrm-empty-state">
        <oxd-text tag="p">{{ $t('gaa.sem_pendencias') }}</oxd-text>
      </div>
    </div>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {navigate} from '@/core/util/helper/navigation';

export default {
  data() {
    return {
      isLoading: false,
      items: [],
      headers: [
        {
          name: 'employee',
          title: this.$t('gaa.funcionario'),
          style: {flex: 3},
          slot: 'employee',
        },
        {
          name: 'tipo',
          title: this.$t('gaa.tipo_solicitacao'),
          style: {flex: 2},
        },
        {
          name: 'status',
          title: this.$t('general.status'),
          style: {flex: 2},
        },
        {
          name: 'criadoEm',
          title: this.$t('gaa.criada_em'),
          style: {flex: 2},
        },
        {
          name: 'actions',
          title: this.$t('general.actions'),
          slot: 'action',
          style: {flex: 1},
          cellType: 'oxd-table-cell-actions',
          cellConfig: {
            abrir: {
              onClick: this.onClickAbrir,
              component: 'oxd-button',
              props: {
                label: this.$t('general.edit'),
                displayType: 'secondary',
                size: 'medium',
              },
            },
          },
        },
      ],
    };
  },
  beforeMount() {
    this.fetch();
  },
  methods: {
    async fetch() {
      this.isLoading = true;
      const http = new APIService(
        window.appGlobal.baseUrl,
        '/api/v2/gaa/minhas-pendencias',
      );
      try {
        const res = await http.getAll({});
        this.items = (res.data?.data || []).map((s) => ({
          id: s.id,
          tipo: s.tipo,
          status: s.status,
          criadoEm: s.criadoEm,
          employee: `${s.employee.firstName} ${s.employee.lastName}`,
        }));
      } finally {
        this.isLoading = false;
      }
    },
    onClickAbrir(item) {
      navigate('/gaa/gaaPreencherSolicitacao/{id}', {id: item.id});
    },
  },
};
</script>
