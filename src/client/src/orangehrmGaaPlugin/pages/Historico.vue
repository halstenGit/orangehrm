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
        {{ $t('gaa.historico_funcionario') }}
      </oxd-text>
    </div>

    <oxd-form-row>
      <oxd-input-field
        v-model="empNumberInput"
        type="number"
        :label="$t('gaa.selecionar_funcionario')"
        :placeholder="$t('gaa.selecionar_funcionario')"
      />
      <oxd-button
        :label="$t('general.search')"
        display-type="secondary"
        @click="fetch"
      />
    </oxd-form-row>

    <div v-if="historico.length > 0" class="gaa-timeline">
      <div v-for="h in historico" :key="h.id" class="gaa-timeline-item">
        <div class="gaa-timeline-date">{{ h.criadoEm }}</div>
        <div class="gaa-timeline-acao">
          <strong>{{ h.acao }}</strong>
          <span v-if="h.usuario"> — {{ h.usuario.username }}</span>
        </div>
        <div v-if="h.comentario" class="gaa-timeline-comentario">{{ h.comentario }}</div>
        <div class="gaa-timeline-meta">
          {{ $t('gaa.solicitacao') }} #{{ h.solicitacaoId }} ·
          item #{{ h.itemId }}
        </div>
      </div>
    </div>
    <div v-else-if="searched" class="orangehrm-empty-state">
      <oxd-text tag="p">{{ $t('gaa.sem_pendencias') }}</oxd-text>
    </div>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';

export default {
  data() {
    return {
      empNumberInput: null,
      historico: [],
      searched: false,
    };
  },
  methods: {
    async fetch() {
      if (!this.empNumberInput) return;
      const http = new APIService(
        window.appGlobal.baseUrl,
        `/api/v2/gaa/historico/${this.empNumberInput}`,
      );
      const res = await http.getAll({});
      this.historico = res.data?.data || [];
      this.searched = true;
    },
  },
};
</script>

<style scoped>
.gaa-timeline {
  margin-top: 1rem;
}
.gaa-timeline-item {
  border-left: 2px solid #e0e0e0;
  padding: 0.5rem 1rem;
  margin-bottom: 0.5rem;
}
.gaa-timeline-date {
  font-size: 0.85rem;
  color: #888;
}
.gaa-timeline-acao {
  font-weight: 500;
}
.gaa-timeline-comentario {
  margin-top: 0.25rem;
  font-style: italic;
}
.gaa-timeline-meta {
  font-size: 0.75rem;
  color: #888;
  margin-top: 0.25rem;
}
</style>
