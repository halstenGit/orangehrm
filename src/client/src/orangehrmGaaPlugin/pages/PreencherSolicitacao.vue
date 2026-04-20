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
  <div class="orangehrm-card-container">
    <oxd-text type="card-title" class="orangehrm-main-title">
      {{ $t('gaa.solicitacao') }} #{{ id }} —
      {{ solicitacao?.tipo }}
      <span class="gaa-status-pill">{{ solicitacao?.status }}</span>
    </oxd-text>

    <div v-if="solicitacao" class="gaa-meta">
      <p>
        <strong>{{ $t('gaa.funcionario') }}:</strong>
        {{ solicitacao.employee.firstName }} {{ solicitacao.employee.lastName }}
      </p>
      <p v-if="solicitacao.lider">
        <strong>{{ $t('gaa.lider_responsavel') }}:</strong>
        {{ solicitacao.lider.firstName }} {{ solicitacao.lider.lastName }}
      </p>
    </div>

    <oxd-divider />

    <oxd-text tag="h6" class="orangehrm-section-title">
      {{ $t('gaa.itens_solicitacao') }}
    </oxd-text>

    <table class="gaa-itens-table">
      <thead>
        <tr>
          <th>{{ $t('gaa.tipo_item') }}</th>
          <th>{{ $t('gaa.item_catalogo') }}/{{ $t('gaa.item_custom') }}</th>
          <th>{{ $t('gaa.quantidade') }}</th>
          <th>{{ $t('general.status') }}</th>
          <th>{{ $t('general.actions') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="item in itens" :key="item.id">
          <td>{{ item.tipoItem }}</td>
          <td>
            <span v-if="item.catalogo">{{ item.catalogo.nome }}</span>
            <em v-else>{{ item.labelCustom }} ({{ $t('gaa.item_custom') }})</em>
          </td>
          <td>{{ item.quantidade }}</td>
          <td>{{ item.status }}</td>
          <td>
            <oxd-icon-button
              name="trash"
              @click="deleteItem(item.id)"
            />
          </td>
        </tr>
      </tbody>
    </table>

    <oxd-divider />

    <oxd-text tag="h6">{{ $t('gaa.adicionar_item') }}</oxd-text>
    <oxd-form @submitValid="addItem">
      <oxd-form-row>
        <oxd-grid :cols="3" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="novoItem.tipoItem"
              type="select"
              :label="$t('gaa.tipo_item')"
              :options="tipoItemOptions"
              :rules="rules.tipoItem"
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="novoItem.catalogoId"
              type="select"
              :label="$t('gaa.item_catalogo')"
              :options="catalogoOptions"
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="novoItem.labelCustom"
              :label="$t('gaa.item_custom')"
              :placeholder="$t('gaa.item_custom')"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>
      <oxd-form-row>
        <oxd-grid :cols="2" class="orangehrm-full-width-grid">
          <oxd-grid-item>
            <oxd-input-field
              v-model="novoItem.quantidade"
              type="number"
              :label="$t('gaa.quantidade')"
              :rules="rules.quantidade"
            />
          </oxd-grid-item>
          <oxd-grid-item>
            <oxd-input-field
              v-model="novoItem.observacoes"
              type="textarea"
              :label="$t('gaa.observacoes')"
            />
          </oxd-grid-item>
        </oxd-grid>
      </oxd-form-row>
      <oxd-divider />
      <oxd-form-actions>
        <oxd-button
          :label="$t('gaa.adicionar_item')"
          display-type="secondary"
          type="submit"
        />
      </oxd-form-actions>
    </oxd-form>

    <oxd-divider />

    <oxd-form-row>
      <oxd-input-field
        v-model="solicitacaoObservacoes"
        type="textarea"
        :label="$t('gaa.observacoes')"
      />
    </oxd-form-row>

    <oxd-form-actions>
      <oxd-button
        :label="$t('general.save')"
        display-type="ghost"
        @click="salvarObservacoes"
      />
      <oxd-button
        v-if="solicitacao?.status === 'PENDENTE_LIDER'"
        :label="$t('general.submit')"
        display-type="secondary"
        @click="avancarParaTi"
      />
      <oxd-button
        v-if="solicitacao?.status === 'PENDENTE_TI'"
        :label="$t('gaa.concluir')"
        display-type="secondary"
        @click="concluirSolicitacao"
      />
    </oxd-form-actions>
  </div>
</template>

<script>
import {APIService} from '@/core/util/services/api.service';
import {navigate} from '@/core/util/helper/navigation';
import {required} from '@ohrm/core/util/validation/rules';

export default {
  props: {
    id: {type: Number, required: true},
  },
  data() {
    return {
      solicitacao: null,
      itens: [],
      catalogoOptions: [],
      tipoItemOptions: [
        {id: 'ACESSO', label: this.$t('gaa.acesso')},
        {id: 'EQUIPAMENTO', label: this.$t('gaa.equipamento')},
      ],
      novoItem: {
        tipoItem: null,
        catalogoId: null,
        labelCustom: '',
        quantidade: 1,
        observacoes: '',
      },
      solicitacaoObservacoes: '',
      rules: {
        tipoItem: [required],
        quantidade: [required],
      },
    };
  },
  beforeMount() {
    this.fetchAll();
  },
  methods: {
    async fetchAll() {
      const httpSol = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/solicitacoes');
      const httpCat = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/catalogo');
      const httpItens = new APIService(
        window.appGlobal.baseUrl,
        `/api/v2/gaa/solicitacoes/${this.id}/itens`,
      );

      const [sRes, cRes, iRes] = await Promise.all([
        httpSol.get(this.id),
        httpCat.getAll({limit: 0, ativo: 1}),
        httpItens.getAll({}),
      ]);

      this.solicitacao = sRes.data?.data;
      this.solicitacaoObservacoes = this.solicitacao?.observacoes || '';
      this.catalogoOptions = (cRes.data?.data || []).map((c) => ({
        id: c.id,
        label: `${c.nome} (${c.tipoItem})`,
      }));
      this.itens = iRes.data?.data || [];
    },
    async addItem() {
      const http = new APIService(
        window.appGlobal.baseUrl,
        `/api/v2/gaa/solicitacoes/${this.id}/itens`,
      );
      await http.create({
        tipoItem: this.novoItem.tipoItem?.id || this.novoItem.tipoItem,
        catalogoId: this.novoItem.catalogoId?.id || null,
        labelCustom: this.novoItem.labelCustom || null,
        quantidade: parseInt(this.novoItem.quantidade, 10) || 1,
        observacoes: this.novoItem.observacoes || null,
      });
      this.novoItem = {
        tipoItem: null,
        catalogoId: null,
        labelCustom: '',
        quantidade: 1,
        observacoes: '',
      };
      this.fetchAll();
    },
    async deleteItem(itemId) {
      const http = new APIService(
        window.appGlobal.baseUrl,
        `/api/v2/gaa/solicitacoes/${this.id}/itens`,
      );
      await http.deleteAll({ids: [itemId]});
      this.fetchAll();
    },
    async salvarObservacoes() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/solicitacoes');
      await http.update(this.id, {observacoes: this.solicitacaoObservacoes});
      this.fetchAll();
    },
    async avancarParaTi() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/solicitacoes');
      await http.update(this.id, {acao: 'AVANCAR_TI', observacoes: this.solicitacaoObservacoes});
      navigate('/gaa/gaaMinhasPendencias');
    },
    async concluirSolicitacao() {
      const http = new APIService(window.appGlobal.baseUrl, '/api/v2/gaa/solicitacoes');
      await http.update(this.id, {acao: 'CONCLUIR'});
      navigate('/gaa/gaaMinhasPendencias');
    },
  },
};
</script>

<style scoped>
.gaa-itens-table {
  width: 100%;
  border-collapse: collapse;
  margin-bottom: 1rem;
}
.gaa-itens-table th,
.gaa-itens-table td {
  text-align: left;
  padding: 0.5rem;
  border-bottom: 1px solid #eee;
}
.gaa-status-pill {
  display: inline-block;
  margin-left: 0.5rem;
  padding: 0.1rem 0.5rem;
  border-radius: 0.5rem;
  background: #f4f4f4;
  font-size: 0.85rem;
}
.gaa-meta p {
  margin: 0.25rem 0;
}
</style>
