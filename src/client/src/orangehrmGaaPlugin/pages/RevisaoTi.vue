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
        {{ $t('gaa.revisao_ti') }}
      </oxd-text>
    </div>

    <div class="orangehrm-container">
      <table class="gaa-revisao-table">
        <thead>
          <tr>
            <th>{{ $t('gaa.solicitacao') }}</th>
            <th>{{ $t('gaa.tipo_item') }}</th>
            <th>{{ $t('gaa.item_custom') }}</th>
            <th>{{ $t('gaa.quantidade') }}</th>
            <th>{{ $t('gaa.observacoes') }}</th>
            <th>{{ $t('general.actions') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in itens" :key="item.id">
            <td>#{{ item.solicitacaoId }}</td>
            <td>{{ item.tipoItem }}</td>
            <td>{{ item.labelCustom }}</td>
            <td>{{ item.quantidade }}</td>
            <td>{{ item.observacoes }}</td>
            <td>
              <oxd-button
                :label="$t('gaa.aprovar_adhoc')"
                display-type="ghost"
                size="small"
                @click="acao(item.id, 'APROVAR_ADHOC')"
              />
              <oxd-button
                :label="$t('gaa.promover_catalogo')"
                display-type="secondary"
                size="small"
                @click="acao(item.id, 'PROMOVER')"
              />
              <oxd-button
                :label="$t('gaa.rejeitar')"
                display-type="ghost"
                size="small"
                @click="abrirRejeicao(item.id)"
              />
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="itens.length === 0" class="orangehrm-empty-state">
        <oxd-text tag="p">{{ $t('gaa.sem_pendencias') }}</oxd-text>
      </div>
    </div>

    <oxd-dialog v-if="rejecting" @update:show="rejecting = false">
      <template #header>
        <oxd-text type="card-title">{{ $t('gaa.motivo_rejeicao') }}</oxd-text>
      </template>
      <oxd-input-field v-model="motivoRejeicao" type="textarea" />
      <template #footer>
        <oxd-button :label="$t('general.cancel')" @click="rejecting = false" />
        <oxd-button
          :label="$t('gaa.rejeitar')"
          display-type="secondary"
          @click="confirmarRejeicao"
        />
      </template>
    </oxd-dialog>
  </div>
</template>

<script>
import {OxdDialog} from '@ohrm/oxd';
import {APIService} from '@/core/util/services/api.service';

export default {
  components: {
    'oxd-dialog': OxdDialog,
  },
  data() {
    return {
      itens: [],
      rejecting: false,
      itemRejId: null,
      motivoRejeicao: '',
    };
  },
  beforeMount() {
    this.fetch();
  },
  methods: {
    async fetch() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/revisao-ti');
      const res = await http.getAll({});
      this.itens = res.data?.data || [];
    },
    async acao(id, acao) {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/revisao-ti');
      await http.update(id, {acao});
      this.fetch();
    },
    abrirRejeicao(id) {
      this.itemRejId = id;
      this.motivoRejeicao = '';
      this.rejecting = true;
    },
    async confirmarRejeicao() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/revisao-ti');
      await http.update(this.itemRejId, {acao: 'REJEITAR', motivo: this.motivoRejeicao});
      this.rejecting = false;
      this.fetch();
    },
  },
};
</script>

<style scoped>
.gaa-revisao-table {
  width: 100%;
  border-collapse: collapse;
}
.gaa-revisao-table th,
.gaa-revisao-table td {
  text-align: left;
  padding: 0.5rem;
  border-bottom: 1px solid #eee;
}
</style>
